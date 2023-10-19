<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * The cron runs hourly and looks at the next x hours for reminders needed.
 * Effectively this gives a soft buffer where most emails will go out between x hours
 * before the commitment time.
 */
if ( !wp_next_scheduled( 'dt_prayer_campaign_reminders' ) ) {
    wp_schedule_event( time(), '15min', 'dt_prayer_campaign_reminders' );
}
add_action( 'dt_prayer_campaign_reminders', 'dt_prayer_campaign_prayer_time_reminder' );

function dt_prayer_campaign_prayer_time_reminder(){
    global $wpdb;

    $lead_time_in_hours = 4;

    // get all reminders needed in the next x hours that have not been sent
    $begin_time_range = time();
    $end_time_range = time() + $lead_time_in_hours * 3600;

    $key_name = 'public_key';
    if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
        $key_name = DT_Magic_URL::get_public_key_meta_key( 'subscriptions_app', 'manage' );
    }
    /**
     * Get reports
     * That have been confirmed
     * Where notification has not been sent (no 'prayer_time_reminder_sent' meta)
     * Of prayer time happening in the next x hours
     */
    $reminders = $wpdb->get_results($wpdb->prepare(
        "SELECT r.id, r.parent_id, r.post_id, r.time_begin, r.time_end, pm.meta_value as email, r.label, pk.meta_value as public_key, p.post_title as name
            FROM $wpdb->dt_reports r
            LEFT JOIN $wpdb->postmeta pm ON pm.post_id=r.post_id
                AND pm.meta_key LIKE %s
                AND pm.meta_key NOT LIKE %s
            LEFT JOIN $wpdb->postmeta pk ON pm.post_id=pk.post_id
                AND pk.meta_key = %s
            LEFT JOIN $wpdb->postmeta pn ON pm.post_id=pn.post_id
                AND pn.meta_key = 'receive_prayer_time_notifications'
            LEFT JOIN $wpdb->posts p ON p.ID=r.post_id
            LEFT JOIN $wpdb->dt_reportmeta rm  ON ( rm.report_id = r.id AND rm.meta_key = 'prayer_time_reminder_sent' )
            WHERE r.post_type = 'subscriptions'
            AND r.type = 'campaign_app'
            AND r.time_begin >= %d
            AND r.time_begin <= %d
            AND pn.meta_value = '1'
            AND rm.meta_id IS NULL
            ORDER BY r.time_begin;
            ",
        $wpdb->esc_like( 'contact_email' ) . '%',
        '%' . $wpdb->esc_like( 'details' ),
        $key_name,
        $begin_time_range,
        $end_time_range
    ), ARRAY_A );

    if ( empty( $reminders ) ){
        return;
    }

    // build nested array to separate campaigns and combine user reminders
    $grouped_reminders = [];
    foreach ( $reminders as $reminder ) {
        if ( ! isset( $grouped_reminders[$reminder['parent_id']] ) ) {
            $grouped_reminders[$reminder['parent_id']] = [];
        }
        if ( ! isset( $grouped_reminders[$reminder['parent_id']][$reminder['post_id']] ) ) {
            $grouped_reminders[$reminder['parent_id']][$reminder['post_id']] = [];
        }
        $grouped_reminders[$reminder['parent_id']][$reminder['post_id']][] = $reminder;
    }

    $porch_selected = DT_Porch_Selector::instance()->get_selected_porch_id();
    $prayer_fuel_link = '';
    $porch_email_settings = DT_Porch_Settings::settings( 'email-settings' );
    if ( !empty( $porch_selected ) && empty( $porch_email_settings['reminder_content_disable_fuel']['value'] ) ){
        $url = site_url() . '/prayer/list';
        $link = '<a href="' . $url . '">' . $url . '</a>';
        $prayer_fuel_link = '<p>' . sprintf( _x( 'Click here to see the prayer prompts for today: %s', 'Click here to see the prayer prompts for today: link-html-code-here', 'disciple-tools-prayer-campaigns' ), $link ) . '</p>';
    }

    // build message by campaign, and then by user, and grouping times per user message
    foreach ( $grouped_reminders as $campaign_id => $subscriber_values ) {
        // get campaign messages and links for the day

        foreach ( $subscriber_values as $subscriber_id => $reports ) {

            $record = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
            if ( is_wp_error( $record ) ){
                continue;
            }
            $sent = DT_Prayer_Campaigns_Send_Email::send_prayer_time_reminder( $subscriber_id, $reports, $campaign_id, $prayer_fuel_link );

            if ( ! $sent ){
                dt_write_log( __METHOD__ . ': Unable to send email to ' . $subscriber_id . '. Subscriber: ' . $subscriber_id );
            } else {
                // note in report that email was sent
                foreach ( $reports as $row ){
                    $payload = [ 'prayer_time_reminder_sent' => time() ];
                    Disciple_Tools_Reports::update( [
                        'id' => $row['id'],
                        'payload' => $payload
                    ] );
                    Disciple_Tools_Reports::add_meta( $row['id'], 'prayer_time_reminder_sent', time() );
                }
            }
        } // user loop

    } // campaign loop
}


