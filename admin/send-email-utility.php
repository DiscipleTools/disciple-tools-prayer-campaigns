<?php

class DT_Prayer_Campaigns_Send_Email {
    public static function send_registration( $post_id ) {

        $record = DT_Posts::get_post( 'subscriptions', $post_id, true, false );
        if ( is_wp_error( $record ) ){
            dt_write_log( 'failed to record' );
            return;
        }
        if ( ! isset( $record['contact_email'] ) || empty( $record['contact_email'] ) ){
            return;
        }
        $commitments = Disciple_Tools_Reports::get( $post_id, 'post_id' );
        if ( empty( $commitments ) ) {
            dt_write_log( 'failed to commitments' );
            return;
        }

        $to = [];
        foreach ( $record['contact_email'] as $value ){
            $to[] = $value['value'];
        }
        $to = implode( ',', $to );

        $timezone = $record["timezone"] ?? 'America/Chicago';
        $tz = new DateTimeZone( $timezone );
        $commitment_list = '';
        foreach ( $commitments as $row ){
            $begin_date = new DateTime( "@".$row['time_begin'] );
            $end_date = new DateTime( "@".$row['time_end'] );
            $begin_date->setTimezone( $tz );
            $end_date->setTimezone( $tz );
            $commitment_list .= sprintf(
                '%1$s from <strong>%2$s</strong> to <strong>%3$s</strong>',
                $begin_date->format( 'F d, Y' ),
                $begin_date->format( 'H:i a' ),
                $end_date->format( 'H:i a' )
            );
            if ( !empty( $row["label"] ) ){
                $commitment_list .= " for " . $row["label"];
            }
            $commitment_list .= '<br>';
        }

        $subject = 'Registered to pray with us!';
        $message =
            '<h3>Thank you for praying with us!</h3>
            <p>Here are the times you have committed to pray:</p>
            <p>'.$commitment_list.'</p>
            <p>Times are shown according to: <strong>' . esc_html( $timezone ) . '</strong> time </p>
            <p>Please confirm your time commitments by visiting:</p>

            <p><a href="'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record['public_key'].'">Confirm your prayer times!</a></p>

            <p>Manage your account and time commitments <a href="'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record['public_key'].'">here.</a></p>
            ';


        $headers = [];
        $headers[] = 'Content-Type: text/html';
        $headers[] = 'charset=UTF-8';

        $sent = wp_mail( $to, $subject, $message, $headers );
        if ( ! $sent ){
            dt_write_log( __METHOD__ . ': Unable to send email. ' . $to );
        }
    }

    public static function send_account_access( $campaign_id, $email ) {
        // get post id for campaign
        global $wpdb;
        $email = trim( $email );
        $keys = $wpdb->get_col(  $wpdb->prepare( "SELECT pk.meta_value as public_key
                FROM $wpdb->p2p p2
                    JOIN $wpdb->postmeta pm ON pm.post_id=p2.p2p_to
                       AND pm.meta_key LIKE %s
                       AND pm.meta_key NOT LIKE %s
                    JOIN $wpdb->postmeta pk ON pk.post_id=p2.p2p_to AND pk.meta_key = 'public_key'
                WHERE p2.p2p_type = 'campaigns_to_subscriptions'
                    AND p2.p2p_from = %s
                    AND pm.meta_value = %s",
            $wpdb->esc_like( 'contact_email' ). '%',
            '%'.$wpdb->esc_like( 'details' ),
            $campaign_id,
            $email
        ) );

        $subject = 'Access to your prayer commitments!';

        $headers = [];
        $headers[] = 'Content-Type: text/html';
        $headers[] = 'charset=UTF-8';

        if ( ! empty( $keys ) ) {
            foreach ( $keys as $public_key ) {
                $message =
                    '<h3>Thank you for praying with us!</h3>
                    <p>Here is the link you requested to your prayer commitments:</p>
                    <p><a href="'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $public_key.'">Link to your commitments!</a></p>
                    <br><br><hr>
                    <p>If you did not request this link, just ignore this email.</p>

                    <'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $public_key.'>
                    ';

                $sent = wp_mail( $email, $subject, $message, $headers );
                if ( ! $sent ){
                    dt_write_log( __METHOD__ . ': Unable to send email. ' . $email );
                }
            }
        }
    }
}
