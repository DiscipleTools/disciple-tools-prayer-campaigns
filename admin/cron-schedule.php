<?php
/**
 * The cron runs hourly and looks at the next x hours for reminders needed.
 * Effectively this gives a soft buffer where most emails will go out between x hours
 * before the commitment time.
 */
if ( !wp_next_scheduled( 'dt_prayer_campaign_reminders' )) {
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
    if ( method_exists( "DT_Magic_URL", "get_public_key_meta_key" ) ){
        $key_name = DT_Magic_URL::get_public_key_meta_key( "subscriptions_app", "manage" );
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
            AND r.time_begin >= %d
            AND r.time_begin <= %d
            AND r.value = '1'
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
    $campaign_ids = [];
    foreach ( $reminders as $reminder ) {
        if ( ! isset( $grouped_reminders[$reminder['parent_id']] ) ) {
            $grouped_reminders[$reminder['parent_id']] = [];
            $campaign_ids[] = $reminder['parent_id'];
        }
        if ( ! isset( $grouped_reminders[$reminder['parent_id']][$reminder['post_id']] ) ) {
            $grouped_reminders[$reminder['parent_id']][$reminder['post_id']] = [];
        }
        $grouped_reminders[$reminder['parent_id']][$reminder['post_id']][] = $reminder;
    }


    // build message by campaign, and then by user, and grouping times per user message
    foreach ( $grouped_reminders as $campaign_id => $subscriber_values ) {
        // get campaign messages and links for the day
        $campaign_subject_line = 'Prayer Time reminder!';

        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        foreach ( $subscriber_values as $key => $reports ) {
            $e = [
                'to' => '',
                'subject' => $campaign_subject_line,
                'message' => '',
                'headers' => [
                    'Content-Type: text/html',
                    'charset=UTF-8'
                ]
            ];

            $commitment_list = '';
            $to = [];
            $record = DT_Posts::get_post( 'subscriptions', $key, true, false );
            if ( is_wp_error( $record ) ){
                continue;
            }
            $lang_code = "en_US";
            if ( isset( $record["lang"] ) ){
                $lang_code = sanitize_text_field( wp_unslash( $record["lang"] ) );
            }
            add_filter( 'determine_locale', function ( $locale ) use ( $lang_code ){
                if ( !empty( $lang_code ) ){
                    return $lang_code;
                }
                return $locale;
            } );
            load_plugin_textdomain( 'disciple-tools-prayer-campaigns', false, trailingslashit( dirname( plugin_basename( __FILE__ ), 2 ) ). 'languages' );
            $timezone = !empty( $record["timezone"] ) ? $record["timezone"] : 'America/Chicago';
            $tz = new DateTimeZone( $timezone );
            foreach ( $reports as $row ){

                $to[$row['email']] = $row['email'];
                $begin_date = new DateTime( "@".$row['time_begin'] );
                $end_date = new DateTime( "@".$row['time_end'] );
                $begin_date->setTimezone( $tz );
                $end_date->setTimezone( $tz );
                $commitment_list .= sprintf(
                    _x( '%1$s from %2$s to %3$s', 'August 18, 2021 from 03:15 am to 03:30 am for Tokyo, Japan', 'disciple-tools-prayer-campaigns' ),
                    $begin_date->format( 'F d, Y' ),
                    '<strong>' . $begin_date->format( 'H:i a' ) . '</strong>',
                    '<strong>' . $end_date->format( 'H:i a' ) . '</strong>'
                );
                if ( !empty( $row["label"] ) ){
                    $commitment_list .= " " . sprintf( _x( 'for %s', 'for Paris, France', 'disciple-tools-prayer-campaigns' ), $row["label"] );
                }
                $commitment_list .= '<br>';
            }
            $e['to'] = implode( ',', $to );

            $prayer_content_message = "";
            if ( isset( $record["lang"], $campaign["campaign_strings"][$record["lang"]]["reminder_content"] ) ){
                $prayer_content_message = $campaign["campaign_strings"][$record["lang"]]["reminder_content"];
            }

            $e['message'] =
                '
                <h3>' . sprintf( __( 'Hello %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $record["name"] ) ) . '</h3>
                <h4>' . __( 'Thank you for praying with us!', 'disciple-tools-prayer-campaigns' ) . '</h4>
                <p>' . __( 'Here are your upcoming prayer times:', 'disciple-tools-prayer-campaigns' ) . '</p>
                <p>'.$commitment_list.'</p>
                <p>' . sprintf( __( 'Times are shown according to: %s time', 'disciple-tools-prayer-campaigns' ), '<strong>' . esc_html( $timezone ) . '</strong>' ) . '</p>
                <p>' . $prayer_content_message . '</p>
                <br>
                <hr>
                <p><a href="'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record[$key_name].'">' .  __( 'Click here Manage your account and time commitments', 'disciple-tools-prayer-campaigns' ) . '</a></p>
            ';

            $sent = wp_mail( $e['to'], $e['subject'], $e['message'], $e['headers'] );

            if ( ! $sent ){
                dt_write_log( __METHOD__ . ': Unable to send email. ' . $to );
            } else {
                // note in report that email was sent
                foreach ( $reports as $row ){
                    $payload = maybe_serialize( [ 'prayer_time_reminder_sent' => time() ] );
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
