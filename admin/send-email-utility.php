<?php

class DT_Prayer_Campaigns_Send_Email {
    public static function send_registration( $post_id, $campaign_id ) {

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
                _x( '%1$s from %2$s to %3$s', 'August 18, 2021 from 03:15 am to 03:30 am for Tokyo, Japan', 'disciple-tools-prayer-campaigns' ),
                $begin_date->format( 'F d, Y' ),
                '<strong>' . $begin_date->format( 'H:i a' ) . '</strong>',
                '<strong>' . $end_date->format( 'H:i a' ) . '</strong>'
            );
            if ( !empty( $row["label"] ) ){
                $commitment_list .= " for " . $row["label"];
            }
            $commitment_list .= '<br>';
        }

        $headers = [];
        $headers[] = 'Content-Type: text/html';
        $headers[] = 'charset=UTF-8';

        $subject = __( 'Registered to pray with us!', 'disciple-tools-prayer-campaigns' );
        $message = '';
        if ( !empty( $record["name"] ) ){
            $message .= '<h3>' . sprintf( __( 'Hello %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $record["name"] ) ) . '</h3>';
        }
        $key_name = 'public_key';
        if ( method_exists( "DT_Magic_URL", "get_public_key_meta_key" ) ){
            $key_name = DT_Magic_URL::get_public_key_meta_key( "subscriptions_app", "manage" );
        }

        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        $sign_up_email_extra_message = "";
        if ( isset( $record["lang"], $campaign["campaign_strings"][$record["lang"]]["signup_content"] ) ){
            $sign_up_email_extra_message = '<p>' .  $campaign["campaign_strings"][$record["lang"]]["signup_content"] . '</p>';
        }

        $message .= '
            <h4>' . __( 'Thank you for praying with us!', 'disciple-tools-prayer-campaigns' ) . '</h4>
            <p><a href="'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record[$key_name].'">' . __( 'Click here to verify your email address and confirm your prayer times.', 'disciple-tools-prayer-campaigns' ) .  '</a></p>
            <p>' . __( 'Here are the times you have committed to pray:', 'disciple-tools-prayer-campaigns' ) . '</p>
            <p>'.$commitment_list.'</p>
            <p>' . sprintf( __( 'Times are shown according to: %s time', 'disciple-tools-prayer-campaigns' ), '<strong>' . esc_html( $timezone ) . '</strong>' ) . '</p>
            ' . $sign_up_email_extra_message . '
            <p><a href="'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record[$key_name].'">' .  __( 'Click here Manage your account and time commitments', 'disciple-tools-prayer-campaigns' ) . '</a></p>
        ';

        $sent = wp_mail( $to, $subject, $message, $headers );
        if ( ! $sent ){
            dt_write_log( __METHOD__ . ': Unable to send email. ' . $to );
        }
        return $sent;
    }


    public static function send_pre_registration( $post_id, $campaign_id ) {

        $record = DT_Posts::get_post( 'subscriptions', $post_id, true, false );
        if ( is_wp_error( $record ) ){
            dt_write_log( 'failed to record' );
            return;
        }
        if ( ! isset( $record['contact_email'] ) || empty( $record['contact_email'] ) ){
            return;
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


        $to = [];
        foreach ( $record['contact_email'] as $value ){
            $to[] = $value['value'];
        }
        $to = implode( ',', $to );

        $headers = [];
        $headers[] = 'Content-Type: text/html';
        $headers[] = 'charset=UTF-8';

        $subject = __( 'Registered to pray with us!', 'disciple-tools-prayer-campaigns' );
        $message = '';
        if ( !empty( $record["name"] ) ){
            $message .= '<h3>' . sprintf( __( 'Hello %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $record["name"] ) ) . '</h3>';
        }
        $key_name = 'public_key';
        if ( method_exists( "DT_Magic_URL", "get_public_key_meta_key" ) ){
            $key_name = DT_Magic_URL::get_public_key_meta_key( "subscriptions_app", "manage" );
        }

        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        $sign_up_email_extra_message = "";
        if ( isset( $record["lang"], $campaign["campaign_strings"][$record["lang"]]["signup_content"] ) ){
            $sign_up_email_extra_message = '<p>' .  $campaign["campaign_strings"][$record["lang"]]["signup_content"] . '</p>';
        }

        $manage_link = trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record[$key_name];
        $message .= '
            <h4>' . __( 'Thank you for praying with us!', 'disciple-tools-prayer-campaigns' ) . '</h4>
            <p>' . __( 'We will send you another email when it is time to choose prayer times.', 'disciple-tools-prayer-campaigns' ) . '</p>
            <p>' . __( 'If you are ready to choose prayer times you can do so at any time from this link:', 'disciple-tools-prayer-campaigns' ). '</p>
            <p><a href="'. $manage_link.'">' . $manage_link .  '</a></p>
        ';

        $sent = wp_mail( $to, $subject, $message, $headers );
        if ( ! $sent ){
            dt_write_log( __METHOD__ . ': Unable to send email. ' . $to );
        }
        return $sent;
    }

    public static function send_account_access( $campaign_id, $email ) {
        // get post id for campaign
        global $wpdb;
        $email = trim( $email );
        $key_name = 'public_key';
        if ( method_exists( "DT_Magic_URL", "get_public_key_meta_key" ) ){
            $key_name = DT_Magic_URL::get_public_key_meta_key( "subscriptions_app", "manage" );
        }
        $keys = $wpdb->get_col(  $wpdb->prepare( "SELECT pk.meta_value as public_key
                FROM $wpdb->p2p p2
                    JOIN $wpdb->postmeta pm ON pm.post_id=p2.p2p_to
                       AND pm.meta_key LIKE %s
                       AND pm.meta_key NOT LIKE %s
                    JOIN $wpdb->postmeta pk ON pk.post_id=p2.p2p_to AND pk.meta_key = %s
                WHERE p2.p2p_type = 'campaigns_to_subscriptions'
                    AND p2.p2p_from = %s
                    AND pm.meta_value = %s",
            $wpdb->esc_like( 'contact_email' ). '%',
            '%'.$wpdb->esc_like( 'details' ),
            $key_name,
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
