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
            $new_report = self::add_subscriber_time( $campaign_id, $subscription_id, $time['time'], $time['duration'], $time['grid_id'] ?? null, 0 );
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
    public static function add_subscriber_time( $campaign_id, $subscription_id, $time, $duration, $location_id = null, $recurring_sign_up_id = 0, $meta = [] ){

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
            'subtype' => $recurring_sign_up_id > 0 ? 'recurring_signup' : 'selected_time',
            'payload' => null,
            'value' => $recurring_sign_up_id,
            'lng' => null,
            'lat' => null,
            'level' => null,
            'label' => null,
            'grid_id' => $location_id,
            'time_begin' => $time,
            'time_end' => $time + $duration_mins * 60,
            'meta_input' => $meta,
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

            $report_id = $recurring_signups_class->create_recurring_signup(
                $recurring_signup_info['type'],
                $recurring_signup_info['label'],
                $recurring_signup_info['selected_times'],
                $first,
                $last,
                $recurring_signup_info['time'],
                $recurring_signup_info['week_day'] ?? null,
            );

            foreach ( $recurring_signup_info['selected_times'] as $time ){
                if ( !isset( $time['time'] ) ){
                    continue;
                }
                $new_report = self::add_subscriber_time(
                    $campaign_id,
                    $subscriber_id,
                    $time['time'],
                    $time['duration'],
                    $time['grid_id'] ?? null,
                    $report_id
                );
                if ( !$new_report ){
                    return new WP_Error( __METHOD__, 'Sorry, Something went wrong', [ 'status' => 400 ] );
                }
            }
        }
        return true;
    }

    public static function get_recurring_signups( $subscriber_id, $campaign_id ){
        $recurring_signups_class = new Recurring_Signups( $subscriber_id, $campaign_id );
        return $recurring_signups_class->get_recurring_signups();
    }

    public static function get_recurring_signup( $report_id ){
        global $wpdb;
        $report = $wpdb->get_row( $wpdb->prepare(
            "SELECT r.*
            FROM $wpdb->dt_reports r
            WHERE r.id = %s
            ", $report_id
        ), ARRAY_A );
        $times_report_ids = $wpdb->get_results( $wpdb->prepare( "
            SELECT report_id
            FROM $wpdb->dt_reportmeta
            WHERE meta_key = 'recurring_signup'
            AND meta_value = %s
        ", $report_id ), ARRAY_A );
        $report['signups'] = $times_report_ids;
        return Recurring_Signups::format_from_report( $report );
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

    public static function delete_recurring_signup( $subscriber_id, $recurring_signup_report_id ){
        global $wpdb;
        $recurring_signup = Disciple_Tools_Reports::get( $recurring_signup_report_id, 'id' );
        $campaign_id = $recurring_signup['parent_id'];
        //delete the report
        $wpdb->delete( $wpdb->dt_reports, [ 'id' => $recurring_signup_report_id ] );

        //delete the reports with the recurring_signup meta
        $wpdb->query( $wpdb->prepare(
            "DELETE r, rm
            FROM $wpdb->dt_reports r
            INNER JOIN $wpdb->dt_reportmeta as rm on ( id = rm.report_id AND rm.meta_key = 'recurring_signup' AND rm.meta_value = %s )
            WHERE parent_id = %s
            AND post_id = %s
            AND type = 'campaign_app'
            ",
            $recurring_signup_report_id, $campaign_id, $subscriber_id
        ) );

        return true;
    }

    public static function update_recurring_signup( $subscriber_id, $recurring_signup_report_id, $recurring_signup_info ){
        if ( empty( $subscriber_id ) || empty( $recurring_signup_report_id ) || empty( $recurring_signup_info ) ){
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }
        $recurring_signup = Disciple_Tools_Reports::get( $recurring_signup_report_id, 'id' );
        if ( empty( $recurring_signup ) ){
            return new WP_Error( __METHOD__, 'Missing recurring signup', [ 'status' => 400 ] );
        }

        if ( empty( $recurring_signup_info['selected_times'] ) ){
            return new WP_Error( __METHOD__, 'Missing times', [ 'status' => 400 ] );
        }
        $campaign_id = $recurring_signup['parent_id'];

        foreach ( $recurring_signup_info['selected_times'] as $time ){
            if ( !isset( $time['time'] ) ){
                continue;
            }
            $new_report = self::add_subscriber_time(
                $campaign_id,
                $subscriber_id,
                $time['time'],
                $time['duration'],
                $time['grid_id'] ?? null,
                $recurring_signup_report_id
            );
            if ( !$new_report ){
                return new WP_Error( __METHOD__, 'Sorry, Something went wrong', [ 'status' => 400 ] );
            }
        }

        $recurring_signup['time_end'] = end( $recurring_signup_info['selected_times'] )['time'];
        Disciple_Tools_Reports::update( $recurring_signup );

        return self::get_recurring_signup( $recurring_signup_report_id );
    }

}


class Recurring_Signups {
    public $campaign_id;
    public $subscriber_id;

    public function __construct( $subscriber_id, $campaign_id ) {
        $this->campaign_id = $campaign_id;
        $this->subscriber_id = $subscriber_id;
    }

    public function create_recurring_signup( $type, $label, $selected_times, $first_time, $last_time, $time, $week_day ){
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
                'duration' => $selected_times[0]['duration'],
                'time' => $time ?? null,
                'week_day' => $week_day,
            ],
            'value' => 0,
            'label' => '',
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
            GROUP BY r.id
            ", $this->campaign_id, $this->subscriber_id
        ), ARRAY_A );
        $recurring_signups = [];
        foreach ( $reports as $index => $report ){
            $recurring_signups[] = self::format_from_report( $report );
        }
        return $recurring_signups;
    }

    public static function format_from_report( $report ){
        $report['payload'] = maybe_unserialize( $report['payload'] );
        return [
            'report_id' => $report['id'],
            'campaign_id' => $report['parent_id'],
            'type' => $report['subtype'],
            'label' => $report['payload']['label'] ?? $report['label'],
            'first' => (int) $report['time_begin'],
            'last' => (int) $report['time_end'],
            'duration' => (int) ( $report['payload']['duration'] ?? 15 ),
            'time' => (int) $report['payload']['time'] ?? 0,
            'week_day' => isset( $report['payload']['week_day'] ) ? ( (int) $report['payload']['week_day'] ) : null,
        ];
    }
}
