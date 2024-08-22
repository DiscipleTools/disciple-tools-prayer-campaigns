<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Subscriptions {

    public static function create_subscriber( $campaign_id, $email, $title, $times, $args = [], $pending = false ){
        $args = wp_parse_args(
            $args,
            [
                'receive_prayer_time_notifications' => false,
                'timezone' => '',
                'lang' => 'en_US',
                'status' => 'active',
                'activation_code' => '',
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
            'status' => $args['status'],
            'activation_code' => $args['activation_code'],
        ];

        // create post
        $new_subscriber = DT_Posts::create_post( 'subscriptions', $fields, true );
        if ( is_wp_error( $new_subscriber ) ) {
            return $new_subscriber;
        }

        $added_reports = self::add_subscriber_times( $campaign_id, $new_subscriber['ID'], $times, $pending );
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
        return $wpdb->get_var( $wpdb->prepare( "
            SELECT count(DISTINCT p2p_to) as count
            FROM $wpdb->p2p
            INNER JOIN $wpdb->postmeta pm ON ( pm.post_id = p2p_to AND pm.meta_key = 'status' AND pm.meta_value != 'pending' )
            WHERE p2p_type = 'campaigns_to_subscriptions'
            AND p2p_from = %s
        ", $campaign_id ) );
    }


    /**
     * @param int $campaign_id
     * @param int $subscription_id
     * @param array $times
     * @return bool|WP_Error
     */
    public static function add_subscriber_times( $campaign_id, $subscription_id, $times, $pending = false ){
        foreach ( $times as $time ){
            if ( !isset( $time['time'] ) ){
                continue;
            }
            $new_report = self::add_subscriber_time( $campaign_id, $subscription_id, $time['time'], $time['duration'], $time['grid_id'] ?? null, 0, [], $pending );
            if ( !$new_report ){
                return new WP_Error( __METHOD__, 'Sorry, Something went wrong', [ 'status' => 400 ] );
            }
        }
        do_action( 'subscriptions_added', $campaign_id, $subscription_id );
        return true;
    }


    /**
     * @param int $campaign_id
     * @param int $subscription_id
     * @param int $time the start time of the prayer time
     * @param int $duration int prayer time in minutes
     * @param null $location_id
     * @param int $recurring_sign_up_id
     * @param array $meta
     * @param bool $pending
     * @return false|int|WP_Error
     */
    public static function add_subscriber_time( $campaign_id, $subscription_id, $time, $duration, $location_id = null, $recurring_sign_up_id = 0, array $meta = [], bool $pending = false ){

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
            'type' => $pending ? 'pending_signup' : 'campaign_app',
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


    public static function save_recurring_signups( $subscriber_id, $campaign_id, $recurring_signups, bool $pending = false ){
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
            if ( empty( $report_id ) ){
                return false;
            }

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
                    $report_id,
                    [],
                    $pending
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

    public static function get_recurring_signup_label( $recurring_signup, $timezone, $locale, $with_ending = false ){
        $date = new DateTime( '@' . $recurring_signup['last'] );
        $tz = new DateTimeZone( $timezone );
        $date->setTimezone( $tz );
        $time = DT_Time_Utilities::display_hour_localized( $date, $locale, $timezone );

        if ( $recurring_signup['type'] === 'weekly' ){
            $week_day = DT_Time_Utilities::display_weekday_localized( $date, $locale, $timezone );
            $string = sprintf( _x( 'Every %1$s at %2$s for %3$s minutes', 'Every Wednesday at 5pm for 15 minutes', 'disciple-tools-prayer-campaigns' ), $week_day, $time, $recurring_signup['duration'] );
        } else {
            $string = sprintf( _x( 'Every day at %1$s for %2$s minutes', 'Every day at 5pm for 15 minutes', 'disciple-tools-prayer-campaigns' ), $time, $recurring_signup['duration'] );
        }

        if ( $with_ending ){
            $end_date_string = '<strong>' . DT_Time_Utilities::display_date_localized( $date, $locale, $timezone ) . '</strong>';
            $string .= ', ';
            $string .= sprintf( _x( 'ending on %s', 'Praying Daily at 4:15 PM, ending on July 18, 2026', 'disciple-tools-prayer-campaigns' ), $end_date_string );
        }

        return $string;

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
            "DELETE r
            FROM $wpdb->dt_reports r
            WHERE parent_id = %s
            AND post_id = %s
            AND type = 'campaign_app'
            AND subtype = 'recurring_signup'
            AND time_begin >= %s
            AND value = %s
            ",
            $campaign_id, $subscriber_id, time(), $recurring_signup_report_id
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

    public static function get_subscriber_prayer_times( $campaign_id, $subscriber_id, $only_future = false ){
        global $wpdb;
        $subs  = $wpdb->get_results( $wpdb->prepare( "
            SELECT time_begin, time_end, subtype, id, value
            FROM $wpdb->dt_reports
            WHERE parent_id = %s
            AND post_id = %s
            AND post_type = 'subscriptions'
            AND type = 'campaign_app'
            AND time_begin >= %s
            ORDER BY time_begin ASC
        ", $campaign_id, $subscriber_id, ( $only_future ? time() : 0 ) ), ARRAY_A );

        $my_commitments = [];
        foreach ( $subs as $commitments_report ){
            $my_commitments[] = [
                'time_begin' => (int) $commitments_report['time_begin'],
                'time_end' => (int) $commitments_report['time_end'],
                'report_id' => (int) $commitments_report['id'],
                'type' => $commitments_report['subtype'],
                'recurring_id' => (int) $commitments_report['value'] ?? null,
            ];
        }

        return $my_commitments;
    }
}

/**
 * Report structure
 * id: 1
 * parent_id: the id of the campaign
 * post_id: the id of the subscriber
 * post_type: subscriptions
 * type: recurring_signup
 * subtype: 'daily' | 'weekly' | 'monthly'
 * payload: {
 *    selected_times: array of individual times
 *    type: 'daily' | 'weekly' | 'monthly'
 *    duration: 15 minutes
 *    label: 'Daily at 8am'
 * }
 * value: not used
 * label: not used, for locations
 * time_begin: the first time of the recurring signup
 * time_end: the last time of the recurring signup
 * timestamp: the time the recurring signup was created
 *
 */

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
                'duration' => $selected_times[0]['duration'] ?? 15,
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
        return Disciple_Tools_Reports::insert( $args, true, true );
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
        $rc = [
            'report_id' => (int) $report['id'],
            'campaign_id' => (int) $report['parent_id'],
            'post_id' => (int) $report['post_id'],
            'type' => $report['subtype'],
            'label' => $report['payload']['label'] ?? $report['label'],
            'first' => (int) $report['time_begin'],
            'last' => (int) $report['time_end'],
            'duration' => (int) ( $report['payload']['duration'] ?? 15 ),
            'time' => (int) $report['payload']['time'] ?? 0,
            'week_day' => isset( $report['payload']['week_day'] ) ? ( (int) $report['payload']['week_day'] ) : null,
        ];
        if ( empty( $rc['label'] ) || $report['label'] === 'changed' ){
            $subscriber = DT_Posts::get_post( 'subscriptions', $report['post_id'], true, false );
            $rc['label'] = DT_Subscriptions::get_recurring_signup_label( $rc, $subscriber['timezone'], $subscriber['lang'] );
        }

        return $rc;
    }
}
