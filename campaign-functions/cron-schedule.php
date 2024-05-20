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
            INNER JOIN $wpdb->posts p ON p.ID = r.post_id
            INNER JOIN $wpdb->posts p_campaign ON ( p_campaign.ID = r.parent_id )
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

    // build message by campaign, and then by user, and grouping times per user message
    foreach ( $grouped_reminders as $campaign_id => $subscriber_values ) {
        // get campaign messages and links for the day
        foreach ( $subscriber_values as $subscriber_id => $reports ) {

            $record = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
            if ( is_wp_error( $record ) ){
                continue;
            }
            $sent = DT_Prayer_Campaigns_Send_Email::send_prayer_time_reminder( $subscriber_id, $reports, $campaign_id );

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


if ( !wp_next_scheduled( 'dt_prayer_resubscription_reminders' ) ) {
    wp_schedule_event( time(), 'hourly', 'dt_prayer_resubscription_reminders' );
}
add_action( 'dt_prayer_resubscription_reminders', 'dt_invitation_to_renew_subscription' );

/**
 * Send an email to subscribers for recurring prayer commitments that are about to expire
 * find active campaigns
 * find recurring subscriptions that are about to expire
 * send email to subscribers
 * add report
 */
function dt_invitation_to_renew_subscription(){
    $one_month_in_future = time() + 30 * DAY_IN_SECONDS;
    $two_weeks_in_future = time() + 14 * DAY_IN_SECONDS;

    //find active campaigns that don't end or don't end in the next month
    //select the recurring subscriptions that are about to expire
    //where time_end is in the future and is less than 2 weeks away
    //and we didn't send a tickler for the current end time
    global $wpdb;
    $expiring_recurring_signups = $wpdb->get_results( $wpdb->prepare( "
            SELECT post_id, parent_id, time_end
            FROM $wpdb->dt_reports r
            WHERE parent_id IN ( 
                SELECT p.ID
                FROM $wpdb->posts p
                INNER JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_id AND pm.meta_key = 'status' AND pm.meta_value = 'active' )
                LEFT JOIN $wpdb->postmeta pm2 ON ( p.ID = pm2.post_id AND pm2.meta_key = 'end_date' )
                WHERE post_type = 'campaigns'
                AND ( pm2.meta_value IS NULL OR pm2.meta_value > %d )
            )
            AND r.time_end < %d
            AND r.time_end > %d
            AND post_type = 'subscriptions'
            AND type = 'recurring_signup'
            AND r.post_id NOT IN (
                SELECT post_id from $wpdb->dt_reports r3
                WHERE r3.post_id = r.post_id
                AND r3.parent_id = r.parent_id
                AND r3.type = '2_week_tickler'
                AND ( r3.time_end = r.time_end )
            )
        ", $one_month_in_future, $two_weeks_in_future, time() ), ARRAY_A );

    foreach ( $expiring_recurring_signups as $row ){

        $sent = DT_Prayer_Campaigns_Send_Email::send_resubscribe_tickler( $row['post_id'], $row['parent_id'] );
        if ( $sent ){
            $report = [
                'post_type' => 'subscriptions',
                'type' => '2_week_tickler',
                'post_id' => $row['post_id'], // subscriber id
                'parent_id' => $row['parent_id'], //campaign id
                'time_end' => $row['time_end'], //the end time this reminder is for
            ];
            Disciple_Tools_Reports::insert( $report, true, false );
            DT_Posts::add_post_comment( 'subscriptions', $row['post_id'], 'Sent email invitation to extend prayer times on [campaign](' . site_url( 'campaigns/' . $row['parent_id'] ) . ').', 'comment', [], false, true );
        }
    }
}
