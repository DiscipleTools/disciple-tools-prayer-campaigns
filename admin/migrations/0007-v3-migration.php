<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Prayer_Campaign_Migration_0006
 */
class DT_Prayer_Campaign_Migration_0007 extends DT_Prayer_Campaign_Migration {
    /**
     * @throws \Exception  Got error when creating table $name.
     */
    public function up() {
        //porch type -> generic
        //recurring records

        $active_campaign = get_option( 'dt_campaign_selected_campaign' );
        if ( empty( $active_campaign ) ){
            return;
        }

        //$porch_settings = DT_Porch_Settings::settings();
        //$settings_manager = new DT_Campaign_Settings();
        $campaign_settings = get_option( 'dt_prayer_campaign_settings' );
        if ( isset( $campaign_settings['selected_porch'] ) && $campaign_settings['selected_porch'] === 'ongoing-porch' ){
            $campaign_settings['selected_porch'] = 'generic-porch';
        }
        update_option( 'dt_prayer_campaign_settings', $campaign_settings );


        global $wpdb;
        $current_subscribers = $wpdb->get_results( $wpdb->prepare( "
            SELECT post_id, parent_id, time_end
            FROM $wpdb->dt_reports r
            WHERE post_type = 'subscriptions'
            AND ( subtype = 'ongoing' OR subtype = '24hour' )
            GROUP BY parent_id, post_ID
            
        " ), ARRAY_A );

        foreach ( $current_subscribers as $subscriber ){
            $subscriber_times = $wpdb->get_results( $wpdb->prepare( "
                SELECT time_begin, time_end
                FROM $wpdb->dt_reports r
                WHERE parent_id = %d
                AND post_id = %d
                AND post_type = 'subscriptions'
                AND ( subtype = 'ongoing' OR subtype = '24hour' )
                ORDER BY time_end DESC
            ", $subscriber['parent_id'], $subscriber['post_id'] ), ARRAY_A );

            if ( count( $subscriber_times ) < 10 ){
                continue;
            }

            $is_daily = $subscriber_times[0]['time_begin'] - $subscriber_times[1]['time_begin'] === DAY_IN_SECONDS;
            $is_daily = $is_daily && $subscriber_times[1]['time_begin'] - $subscriber_times[2]['time_begin'] === DAY_IN_SECONDS;
            $is_daily = $is_daily && $subscriber_times[2]['time_begin'] - $subscriber_times[3]['time_begin'] === DAY_IN_SECONDS;

            if ( $is_daily ){
                $time_hours_mins = gmdate( 'H:i a', (int) $subscriber_times[0]['time_begin'] );

                //create recurring record
                $rec_call = new Recurring_Signups( $subscriber['post_id'], $subscriber['parent_id'] );
                $recurring_signup_id = $rec_call->create_recurring_signup( 'daily', "Every day at $time_hours_mins", [], $subscriber_times[count( $subscriber_times ) - 1]['time_begin'], $subscriber_times[0]['time_begin'], null, null );

                //update subscriber reports subtype to 'recurring_signup' and value to $recurring_signup_id
                //if time_begin is within one day difference more or less one hour using modular arithmetic
                $wpdb->query( $wpdb->prepare( "
                    UPDATE $wpdb->dt_reports
                    SET subtype = 'recurring_signup', value = %d
                    WHERE post_id = %d
                    AND parent_id = %d
                    AND post_type = 'subscriptions'
                    AND ( subtype = 'ongoing' OR subtype = '24hour' )
                    AND ABS( MOD( time_begin, 86400 ) - MOD( %d, 86400 ) ) <= 3600
                ", $recurring_signup_id, $subscriber['post_id'], $subscriber['parent_id'], $subscriber_times[0]['time_begin'] ) );
            }
        }


        //change Subscriber dt_reports subtype to 'selected_time'
        $wpdb->query( "UPDATE $wpdb->dt_reports SET subtype = 'selected_time', value = '0'
            WHERE subtype = 'ongoing'
            OR subtype = '24hour'
        " );
    }

    /**
     * @throws \Exception  Got error when dropping table $name.
     */
    public function down() {
    }

    /**
     * Test function
     */
    public function test() {
    }

}


