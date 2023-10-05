<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Subscriptions {

    public static function create_subscriber( $campaign_id, $email, $title, $times, $args = [] ) {
        $args = wp_parse_args(
            $args,
            [
                'receive_prayer_time_notifications' => false,
                'timezone' => '',
                'lang' => 'en_US'
            ]
        );

        $user = wp_get_current_user();
        $user->add_cap( 'create_subscriptions' );

        $hash = dt_create_unique_key();


        $key_name = 'public_key';
        if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
            $key_name = DT_Magic_URL::get_public_key_meta_key( 'subscriptions_app', 'manage' );
        }
        $fields = [
            'title' => $title,
            'contact_email' => [
                [ 'value' => $email ],
            ],
            'campaigns' => [
                'values' => [
                    [ 'value' => $campaign_id ],
                ],
            ],
            'timezone' => $args['timezone'],
            'lang' => $args['lang'],
            $key_name => $hash,
            'receive_prayer_time_notifications' => $args['receive_prayer_time_notifications'],
        ];

        // create post
        $new_subscriber = DT_Posts::create_post( 'subscriptions', $fields, true );
        if ( is_wp_error( $new_subscriber ) ) {
            return $new_subscriber;
        }

        $added_reports = self::add_subscriber_times( $campaign_id, $new_subscriber['ID'], $times );
        if ( is_wp_error( $added_reports ) ){
            return $added_reports;
        }
        return $new_subscriber['ID'];
    }

    /*
     * Get the subscribers of a prayer campaign
     * return subscriber ID, name, commitments count and commitments verified
     */
    public static function get_subscribers( $campaign_id ){
        global $wpdb;

        return $wpdb->get_results( $wpdb->prepare( "
            SELECT p.post_title as name, p.ID,
                (SELECT COUNT(r.post_id)
                  FROM $wpdb->dt_reports r
                  WHERE r.post_type = 'subscriptions'
                  AND r.parent_id = %s
                  AND r.post_id = p.ID) as commitments,
                (SELECT COUNT(r.post_id)
                  FROM $wpdb->dt_reports r
                  WHERE r.post_type = 'subscriptions'
                 AND r.parent_id = %s
                 AND r.value = 1
                 AND r.post_id = p.ID) as verified
            FROM $wpdb->p2p p2
            LEFT JOIN $wpdb->posts p ON p.ID=p2.p2p_to
            WHERE p2p_type = 'campaigns_to_subscriptions'
            AND p2p_from = %s", $campaign_id, $campaign_id, $campaign_id
        ), ARRAY_A );
    }


    public static function get_subscribers_count( $campaign_id ){
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( "SELECT count(DISTINCT p2p_to) as count FROM $wpdb->p2p WHERE p2p_type = 'campaigns_to_subscriptions' AND p2p_from = %s", $campaign_id ) );
    }


    /**
     * @param $campaign_id
     * @param $subscription_id
     * @param $times
     * @return bool|WP_Error
     */
    public static function add_subscriber_times( $campaign_id, $subscription_id, $times ){
        foreach ( $times as $time ){
            if ( !isset( $time['time'] ) ){
                continue;
            }
            $new_report = self::add_subscriber_time( $campaign_id, $subscription_id, $time['time'], $time['duration'], $time['grid_id'] ?? null );
            if ( !$new_report ){
                return new WP_Error( __METHOD__, 'Sorry, Something went wrong', [ 'status' => 400 ] );
            }
        }
        do_action( 'subscriptions_added', $campaign_id, $subscription_id );
        return true;
    }


    /**
     * @param $campaign_id
     * @param $subscription_id
     * @param $time int the start time of the prayer time
     * @param $duration int prayer time in minutes
     * @param null $location_id
     * @return false|int|WP_Error
     */
    public static function add_subscriber_time( $campaign_id, $subscription_id, $time, $duration, $location_id = null, $verified = true ){

        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        if ( is_wp_error( $campaign ) ){
            return new WP_Error( __METHOD__, 'Sorry, Something went wrong', [ 'status' => 400 ] );
        }

        $location_label = '';
        if ( empty( $location_id ) && isset( $campaign['location_grid'][0]['id'] ) ){
            $location_id = $campaign['location_grid'][0]['id'];
        }
        foreach ( $campaign['location_grid_meta'] ?? [] as $location ){
            if ( is_array( $location['label'] ) ){
                $location['label'] = $location['label'][0];
            }
            $location_label .= ( !empty( $location_label ) ? ' | ' : '' ) . $location['label'];
        }
        if ( empty( $location_label ) ){
            foreach ( $campaign['location_grid'] ?? [] as $location ){
                $location_label .= ( !empty( $location_label ) ? ' | ' : '' ) . $location['label'];
            }
        }

        $duration_mins = 15;
        if ( is_numeric( $duration ) ){
            $duration_mins = $duration;
        }
        $args = [
            'parent_id' => $campaign_id,
            'post_id' => $subscription_id,
            'post_type' => 'subscriptions',
            'type' => 'campaign_app',
            'subtype' => $campaign['type']['key'],
            'payload' => null,
            'value' => $verified ? 1 : 0,
            'lng' => null,
            'lat' => null,
            'level' => null,
            'label' => null,
            'grid_id' => $location_id,
            'time_begin' => $time,
            'time_end' => $time + $duration_mins * 60,
        ];

        if ( !empty( $location_id ) ){
            $grid_row = Disciple_Tools_Mapping_Queries::get_by_grid_id( $location_id );
            if ( ! empty( $grid_row ) ){
                $args['lng'] = $grid_row['longitude'];
                $args['lat'] = $grid_row['latitude'];
                $args['level'] = $grid_row['level_name'];
                $args['label'] = $location_label ?: Disciple_Tools_Mapping_Queries::get_full_name_by_grid_id( $location_id );
            }
        }
        $new_report = Disciple_Tools_Reports::insert( $args, true, false );

        $label = 'Commitment added: ' . gmdate( 'F d, Y @ H:i a', $args['time_begin'] ) . ' UTC for ' . $duration_mins . ' minutes';
        dt_activity_insert([
            'action' => 'add_subscription',
            'object_type' => $args['post_type'], // If this could be contacts/groups, that would be best
            'object_subtype' => 'report',
            'object_note' => $label,
            'object_id' => $args['post_id']
        ] );

        return $new_report;

    }


    public static function save_recurring_signups( $subscriber_id, $campaign_id, $recurring_signups ){
        $recurring_signups_class = new Recurring_Signups( $subscriber_id, $campaign_id );
        foreach ( $recurring_signups as $recurring_signup_info ){
            $first = $recurring_signup_info['selected_times'][0]['time'];
            $last = end( $recurring_signup_info['selected_times'] )['time'];

            $recurring_signups_class->create_recurring_signup(
                $recurring_signup_info['type'],
                $recurring_signup_info['label'],
                $recurring_signup_info['selected_times'],
                $first,
                $last
            );
        }
        return true;
    }

    public static function get_recurring_signups( $subscriber_id, $campaign_id ){
        $recurring_signups_class = new Recurring_Signups( $subscriber_id, $campaign_id );
        return $recurring_signups_class->get_recurring_signups();
    }


    public static function get_number_of_notification_emails_sent( $campaign_id ){
        global $wpdb;
        $a = $wpdb->get_var( $wpdb->prepare(
            "SELECT count(r.id)
            FROM $wpdb->dt_reports r
            INNER JOIN $wpdb->dt_reportmeta as rm on ( r.id = rm.report_id AND rm.meta_key = 'prayer_time_reminder_sent' )
            WHERE parent_id = %s
            ", $campaign_id
        ));
        return (int) $a;
    }

}


class Recurring_Signups {
    public $campaign_id;
    public $subscriber_id;

    public function __construct( $subscriber_id, $campaign_id ) {
        $this->campaign_id = $campaign_id;
        $this->subscriber_id = $subscriber_id;
    }

    public function create_recurring_signup( $type, $label, $selected_times, $first_time, $last_time ){
        $args = [
            'parent_id' => $this->campaign_id,
            'post_id' => $this->subscriber_id,
            'post_type' => 'subscriptions',
            'type' => 'recurring_signup',
            'subtype' => $type,
            'payload' => [
                'selected_times' => $selected_times,
                'type' => $type,
                'label' => $label,
            ],
            'value' => 0,
            'label' => $label,
            'time_begin' => $first_time,
            'time_end' => $last_time,
            'lng' => null,
            'level' => null,
            'lat' => null,
            'grid_id' => null,
        ];
        return Disciple_Tools_Reports::insert( $args, true, false );
    }

    public function get_recurring_signups(){
        global $wpdb;
        $reports = $wpdb->get_results( $wpdb->prepare(
            "SELECT *
            FROM $wpdb->dt_reports r
            WHERE r.parent_id = %s
            AND r.post_id = %s
            AND r.type = 'recurring_signup'
            ", $this->campaign_id, $this->subscriber_id
        ), ARRAY_A );
        $recurring_signups = [];
        foreach ( $reports as $report ){
            $recurring_signups[] = $this->format_from_report( $report );
        }
        return $recurring_signups;
    }

    private function format_from_report( $report ){
        $report['payload'] = maybe_unserialize( $report['payload'] );
        return [
            'type' => $report['subtype'],
            'label' => $report['payload']['label'] ?? $report['label'],
            'selected_times' => $report['payload']['selected_times'],
            'first' => (int) $report['time_begin'],
            'last' => (int) $report['time_end'],
        ];
    }
}
