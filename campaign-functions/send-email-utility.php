<?php

class DT_Prayer_Campaigns_Send_Email {
    public static function send_prayer_campaign_email( $to, $subject, $message, $headers = [] ){

        if ( empty( $headers ) ){
            $headers[] = 'Content-Type: text/html';
            $headers[] = 'charset=UTF-8';
        }
        add_filter( 'wp_mail_from', function ( $email ) {
            $prayer_campaign_email = get_option( 'dt_prayer_campaign_email' );
            if ( !empty( $prayer_campaign_email ) ){
                return $prayer_campaign_email;
            }
            return $email;
        }, 200 );
        add_filter( 'wp_mail_from_name', function ( $name ) {
            $prayer_campaign_email_name = get_option( 'dt_prayer_campaign_email_name' );
            if ( !empty( $prayer_campaign_email_name ) ){
                return $prayer_campaign_email_name;
            }
            return dt_get_option( 'dt_email_base_name' );
        }, 200);
        $sent = wp_mail( $to, $subject, $message, $headers );
        if ( ! $sent ){
            dt_write_log( __METHOD__ . ': Unable to send email. ' . $to );
        }
        return $sent;
    }

    public static function switch_email_locale( $subscriber_locale = null ){
        $lang_code = 'en_US';
        if ( !empty( $subscriber_locale ) ){
            $lang_code = $subscriber_locale;
        }
        add_filter( 'determine_locale', function ( $locale ) use ( $lang_code ){
            if ( !empty( $lang_code ) ){
                return $lang_code;
            }
            return $locale;
        } );
        unload_textdomain( 'disciple-tools-prayer-campaigns' );
        dt_campaign_reload_text_domain();
    }


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

        self::switch_email_locale( $record['lang'] ?? null );

        $to = [];
        foreach ( $record['contact_email'] as $value ){
            $to[] = $value['value'];
        }
        $to = implode( ',', $to );

        $timezone = !empty( $record['timezone'] ) ? $record['timezone'] : 'America/Chicago';
        $tz = new DateTimeZone( $timezone );
        $commitment_list = '';
        foreach ( $commitments as $row ){
            $begin_date = new DateTime( '@'.$row['time_begin'] );
            $end_date = new DateTime( '@'.$row['time_end'] );
            $begin_date->setTimezone( $tz );
            $end_date->setTimezone( $tz );
            $commitment_list .= sprintf(
                _x( '%1$s from %2$s to %3$s', 'August 18, 2021 from 03:15 am to 03:30 am for Tokyo, Japan', 'disciple-tools-prayer-campaigns' ),
                $begin_date->format( 'F d, Y' ),
                '<strong>' . $begin_date->format( 'H:i a' ) . '</strong>',
                '<strong>' . $end_date->format( 'H:i a' ) . '</strong>'
            );
            if ( !empty( $row['label'] ) ){
                $commitment_list .= ' for ' . $row['label'];
            }
            $commitment_list .= '<br>';
        }

        $headers = [];
        $headers[] = 'Content-Type: text/html';
        $headers[] = 'charset=UTF-8';

        $subject = __( 'Registered to pray with us!', 'disciple-tools-prayer-campaigns' );
        $message = '';
        if ( !empty( $record['name'] ) ){
            $message .= '<h3>' . sprintf( __( 'Hello %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $record['name'] ) ) . '</h3>';
        }
        $key_name = 'public_key';
        if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
            $key_name = DT_Magic_URL::get_public_key_meta_key( 'subscriptions_app', 'manage' );
        }

        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        $sign_up_email_extra_message = '';
        if ( isset( $record['lang'], $campaign['campaign_strings'][$record['lang']]['signup_content'] ) ){
            $sign_up_email_extra_message = '<p>' .  $campaign['campaign_strings'][$record['lang']]['signup_content'] . '</p>';
        }

        $sign_up_email_extra_message = apply_filters( 'dt_campaign_signup_content', $sign_up_email_extra_message );

        $message .= '
            <h4>' . __( 'Thank you for joining us in strategic prayer for a disciple making movement! You\'re one click away from finishing your registration.', 'disciple-tools-prayer-campaigns' ) . '</h4>
            <p><a href="'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record[$key_name].'">' . __( 'Click here to verify your email address and confirm your prayer times.', 'disciple-tools-prayer-campaigns' ) .  '</a></p>
            <p>' . __( 'Here are the times you have committed to pray:', 'disciple-tools-prayer-campaigns' ) . '</p>
            <p>'.$commitment_list.'</p>
            <p>' . sprintf( __( 'Times are shown according to: %s time', 'disciple-tools-prayer-campaigns' ), '<strong>' . esc_html( $timezone ) . '</strong>' ) . '</p>
            ' . $sign_up_email_extra_message . '
            <br>
            <hr>
            <p><a href="'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record[$key_name].'">' .  __( 'Click here to manage your account and time commitments', 'disciple-tools-prayer-campaigns' ) . '</a></p>
        ';

        $sent = self::send_prayer_campaign_email( $to, $subject, $message, $headers );
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

        self::switch_email_locale( $record['lang'] ?? null );

        $to = [];
        foreach ( $record['contact_email'] as $value ){
            $to[] = $value['value'];
        }
        $to = implode( ',', $to );

        $headers = [];
        $headers[] = 'Content-Type: text/html';
        $headers[] = 'charset=UTF-8';

        $message = '';
        if ( !empty( $record['name'] ) ){
            $message .= '<h3>' . sprintf( __( 'Hello %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $record['name'] ) ) . '</h3>';
        }
        $key_name = 'public_key';
        if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
            $key_name = DT_Magic_URL::get_public_key_meta_key( 'subscriptions_app', 'manage' );
        }

        $subject = __( 'Registration Confirmation', 'disciple-tools-prayer-campaigns' );
        $manage_link = trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record[$key_name];
        $message .= '
            <h4>' . __( 'Thank you for signing up to pray!', 'disciple-tools-prayer-campaigns' ) . '</h4>
            <p>' . __( 'We have some time before the campaign starts. We will send you another email a couple months before the first day asking you to choose specific times to pray. Working together we will cover the region in 24/7 prayer.', 'disciple-tools-prayer-campaigns' ) . '</p>
            <p>' . __( 'If you know your schedule already you can choose your times here:', 'disciple-tools-prayer-campaigns' ). '</p>
            <p><a href="'. $manage_link.'">' . $manage_link .  '</a></p>
        ';

        $sent = self::send_prayer_campaign_email( $to, $subject, $message, $headers );
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
        if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
            $key_name = DT_Magic_URL::get_public_key_meta_key( 'subscriptions_app', 'manage' );
        }
        //find subscribers management links for the email and campaign provided
        $keys = $wpdb->get_col(  $wpdb->prepare( "
            SELECT pk.meta_value as public_key
            FROM $wpdb->p2p p2
            JOIN $wpdb->postmeta pm ON ( pm.post_id=p2.p2p_to AND pm.meta_key LIKE %s AND pm.meta_key NOT LIKE %s )
            JOIN $wpdb->postmeta pk ON ( pk.post_id=p2.p2p_to AND pk.meta_key = %s )
            WHERE p2.p2p_type = 'campaigns_to_subscriptions'
            AND p2.p2p_from = %s
            AND pm.meta_value = %s",
            $wpdb->esc_like( 'contact_email' ) . '%',
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

                $sent = self::send_prayer_campaign_email( $email, $subject, $message, $headers );
                if ( ! $sent ){
                    dt_write_log( __METHOD__ . ': Unable to send email. ' . $email );
                }
            }
        } else {
            $message = '
                <h3>Thank you for praying with us!</h3>
                <p>Sorry, we were unable to find a profile associated with this email address</p>';

            $sent = self::send_prayer_campaign_email( $email, $subject, $message, $headers );
            if ( ! $sent ){
                dt_write_log( __METHOD__ . ': Unable to send email. ' . $email );
            }
        }
    }

    /**
     * Email sent to the subscribers who have not chosen any prayer times
     * @param $campaign_id
     * @return bool[]
     */
    public static function send_pre_sign_up_email( $campaign_id ): array{

        $pre_sign_up_subscriptions = DT_Posts::list_posts( 'subscriptions', [ 'tags' => [ 'pre-signup' ], 'campaigns' => [ $campaign_id ] ] );

        foreach ( $pre_sign_up_subscriptions['posts'] as $subscriber ){
            //send email
            $key_name = 'public_key';
            if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
                $key_name = DT_Magic_URL::get_public_key_meta_key( 'subscriptions_app', 'manage' );
            }
            $manage_link = trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $subscriber[$key_name];
            if ( isset( $subscriber['contact_email'][0]['value'] ) ){
                $subject = __( 'What time(s) will you pray?', 'disciple-tools-prayer-campaigns' );
                $message = '
                    <h3>' . sprintf( __( 'Hello %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $subscriber['name'] ) ) . '</h3>
                    <p>' . __( 'Thank you for signing up to help us cover the region in nonstop prayer!!', 'disciple-tools-prayer-campaigns' ) . '</p>
                    <p>' . __( 'It is now time to choose the specific times you will pray. Below is a link to the prayer times tool.', 'disciple-tools-prayer-campaigns' ) . '</p>
                    <p>' . __( 'To pray at the same time every day (preferred!) click the "Add a Daily Prayer Time" button.', 'disciple-tools-prayer-campaigns' ) . '</p>
                    <p>' . __( 'If you need to choose different times or can\'t commit to pray every day click the "Add individual Prayer Times" button.', 'disciple-tools-prayer-campaigns' ) . '</p>
                    <p>' . __( 'Choose prayer times link:', 'disciple-tools-prayer-campaigns' ). '</p>
                    <p><a href="'. $manage_link.'">' . $manage_link .  '</a></p>
                ';
                $sent = self::send_prayer_campaign_email( $subscriber['contact_email'][0]['value'], $subject, $message );
                if ( ! $sent ){
                    dt_write_log( __METHOD__ . ': Unable to send email. ' . $subscriber['contact_email'][0]['value'] );
                } else {
                    DT_Posts::update_post( 'subscriptions', $subscriber['ID'], [ 'tags' => [ 'values' => [ [ 'value' => 'pre-signup', 'delete' => true ], [ 'value' => 'sent-pre-signup' ] ] ] ] );
                    DT_Posts::add_post_comment( 'subscriptions', $subscriber['ID'], 'Sent email to ' . $subscriber['contact_email'][0]['value'] . ' asking them to choose prayer times' );
                }
            }
        }

        return [ 'emails_sent' => true ];
    }


    public static function sent_resubscribe_tickler( $subscriber_id ){
        $subscriber = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
        if ( is_wp_error( $subscriber ) || !isset( $subscriber['contact_email'][0]['value'] ) ){
            return false;
        }
        $key_name = 'public_key';
        if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
            $key_name = DT_Magic_URL::get_public_key_meta_key( 'subscriptions_app', 'manage' );
        }
        $manage_link = trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $subscriber[$key_name];
        $porch_fields = DT_Porch_Settings::settings();

        $subject = 'Continue Praying';
        $message = '
            <h3>' . sprintf( __( 'Hello %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $subscriber['name'] ) ) . '</h3>
            <p> ' . __( 'You are 2 weeks out for your last prayer time. Would you like to keep praying?', 'disciple-tools-prayer-campaigns' ) . '</p>
            <p>' . __( 'Sign up for more prayer times or extend your recurring time here:', 'disciple-tools-prayer-campaigns' ) . '</p>
            <p><a href="'. $manage_link.'">' . $manage_link .  '</a></p>
            <p>' . __( 'Thank you', 'disciple-tools-prayer-campaigns' ) . ',</p>
            <p>' .  isset( $porch_fields['title']['value'] ) ? $porch_fields['title']['value'] : site_url() . '</p>

        ';
        return self::send_prayer_campaign_email( $subscriber['contact_email'][0]['value'], $subject, $message );
    }

}
