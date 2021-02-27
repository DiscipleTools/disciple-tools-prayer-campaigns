<?php

class DT_Prayer_Campaigns_Send_Email {
    public static function send_registration( $post_id ) {
        // @todo build this to use mailgun or mailsend
        $record = DT_Posts::get_post( 'subscriptions', $post_id );
        if ( is_wp_error( $record ) ){
            dt_write_log('failed to record');
            return;
        }
        dt_write_log($record);
        if ( ! isset( $record['contact_email'] ) || empty( $record['contact_email'] ) ){
            return;
        }
        $commitments = Disciple_Tools_Reports::get( $post_id, 'post_id' );
        if ( empty( $commitments ) ) {
            dt_write_log('failed to commitments');
            return;
        }

        $to = [];
        foreach( $record['contact_email'] as $value ){
            $to[] = $value['value'];
        }
        $to = implode( ',', $to );

        $commitment_list = '';
        foreach ( $commitments as $row ){
            $commitment_list .= gmdate( 'F d, Y @ H:i a', $row['time_begin'] ) . ' from ' . gmdate( 'H:i a', $row['time_begin'] ) . ' to ' . gmdate( 'H:i a', $row['time_end'] ) . ' for ' . $row['label'] . '<br>';
        }

        $subject = 'Registered to pray with us!';
        $message =
            '<h3>Thank you for praying with us!</h3>
            <p>Here are the times you have committed to pray:</p>
            <p>'.$commitment_list.'</p>
            <p>Please verify your commitment by visiting:</p>
            <p>'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record['public_key'].'</p>
            ';


        $headers = [];
        $headers[] = 'From: Zume Community <no-reply@zume.community>';
        $headers[] = 'Content-Type: text/html';
        $headers[] = 'charset=UTF-8';

        dt_write_log($message);

        $sent = wp_mail( $to, $subject, $message, $headers );
        dt_write_log($sent);
    }
}
