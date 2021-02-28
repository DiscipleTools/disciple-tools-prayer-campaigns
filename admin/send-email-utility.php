<?php

class DT_Prayer_Campaigns_Send_Email {
    public static function send_registration( $post_id ) {
        // @todo build this to use mailgun or mailsend
        $record = DT_Posts::get_post( 'subscriptions', $post_id );
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

        $commitment_list = '';
        foreach ( $commitments as $row ){
            $commitment_list .= gmdate( 'F d, Y', $row['time_begin'] ) . ' from ' . gmdate( 'H:i a', $row['time_begin'] ) . ' to ' . gmdate( 'H:i a', $row['time_end'] ) . ' for ' . $row['label'] . '<br>';
        }

        $subject = 'Registered to pray with us!';
        $message =
            '<h3>Thank you for praying with us!</h3>
            <p>Here are the times you have committed to pray:</p>
            <p>'.$commitment_list.'</p>
            <p>Please verify your commitment by visiting:</p>
            <p><a href="'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record['public_key'].'">Verify your prayer times!</a></p>

            <'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record['public_key'].'>
            ';


        $headers = [];
        $headers[] = 'From: Zume Community <no-reply@zume.community>';
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
        $headers[] = 'From: Zume Community <no-reply@zume.community>';
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
