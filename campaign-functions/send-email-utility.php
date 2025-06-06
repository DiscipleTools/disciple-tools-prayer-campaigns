<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DT_Prayer_Campaigns_Send_Email {
    public static function send_prayer_campaign_email( $to, $subject, $message, $headers = [], $attachments = [], $campaign_id = null ){
        if ( empty( $to ) ){
            return new WP_Error( 'send_prayer_campaign_email', 'No email address provided.' );
        }
        if ( !in_array( 'Content-Type: text/html', $headers ) ){
            $headers[] = 'Content-Type: text/html';
        }
        if ( !in_array( 'charset=UTF-8', $headers ) ){
            $headers[] = 'charset=UTF-8';
        }
        if ( $campaign_id ){
            $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
            if ( !empty( $campaign['from_name'] ) ){
                $headers[] = 'From: ' . $campaign['from_name'] . ' <' . apply_filters( 'wp_mail_from', '' ) . '>';
            }
            if ( !empty( $campaign['reply_to_email'] ) ){
                $headers[] = 'Reply-To: ' . $campaign['reply_to_email'];
            }
        }


        $sent = wp_mail( $to, $subject, $message, $headers, $attachments );
        if ( ! $sent ){
            dt_write_log( __METHOD__ . ': Unable to send email to: ' . $to );
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


    public static function management_link( $record, $campaign_id = null ){
        $key_name = 'public_key';
        if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
            $key_name = DT_Magic_URL::get_public_key_meta_key( 'subscriptions_app', 'manage' );
        }
        $link = trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record[$key_name];
        if ( !empty( $campaign_id ) ){
            $link .= '?campaign=' . $campaign_id;
        }
        return $link;
    }

    public static function prayer_fuel_link( $record, $campaign_id ){
        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        if ( isset( $campaign['reminder_content_disable_fuel']['key'] ) && $campaign['reminder_content_disable_fuel']['key'] === 'yes' ){
            return '';
        }
        if ( isset( $campaign['magic_fuel']['key'] ) && $campaign['magic_fuel']['key'] === 'yes' ){
            return DT_Magic_URL::get_link_url_for_post( 'subscriptions', $record['ID'], 'prayer', 'fuel' );
        }
        $campaign_url = DT_Campaign_Landing_Settings::get_landing_page_url( $campaign_id );
        return $campaign_url . '/list';
    }

    public static function send_verification( $email, $campaign_id, $subscriber_id ){
        $lang = dt_campaign_get_current_lang();
        self::switch_email_locale( $lang );

        $activation_code = get_post_meta( $subscriber_id, 'activation_code', true );

        if ( empty( $activation_code ) ){
            return false;
        }

        $verify_link = trailingslashit( site_url() ) . 'wp-json/campaign_app/v1/ongoing/verify-email?id=' . $subscriber_id . '&code=' . $activation_code;

        $subject = __( 'Verify your email address', 'disciple-tools-prayer-campaigns' );

        $message = Campaigns_Email_Template::email_content_part( __( 'Confirm your participation in prayer by activating your account.', 'disciple-tools-prayer-campaigns' ) );

        $message .= Campaigns_Email_Template::email_button_part(
            __( 'Activate Account', 'disciple-tools-prayer-campaigns' ),
            $verify_link
        );
        $message .= Campaigns_Email_Template::email_content_part(
            __( 'Or click this link:', 'disciple-tools-prayer-campaigns' ) . '<br>' .
            '<a href="' . $verify_link. '">' . $verify_link . '</a>'
        );

        $message .= Campaigns_Email_Template::email_content_part(
            __( 'If you did not request this email, please ignore it.', 'disciple-tools-prayer-campaigns' )
        );


        $full_email = Campaigns_Email_Template::build_campaign_email( $message, $campaign_id );

        $sent = self::send_prayer_campaign_email( $email, $subject, $full_email, [], [], $campaign_id );
        if ( !$sent ){
            dt_write_log( __METHOD__ . ': Unable to send email. ' . $email );
        }
        return $sent;
    }

    public static function generate_registration_attachments( $post_id ): array {
        $attachments = [];

        // Generate calendar ics contents.
        $ics_calendar = DT_Prayer_Subscription_Management_Magic_Link::generate_calendar_download_content( $post_id );

        // Store content within a temp file.
        if ( !empty( $ics_calendar ) ) {
            $filename = 'calendar';

            if ( !function_exists( 'wp_tempnam' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
            }

            $temp_file = wp_tempnam( $filename );
            if ( $temp_file ) {

                // Swap out default .tmp file extension with .ics.
                if ( rename( $temp_file, $filename . '.ics' ) ) {
                    $temp_file = $filename . '.ics';

                    // Save calendar content into temp file.
                    if ( WP_Filesystem( request_filesystem_credentials( '' ) ) ) {

                        global $wp_filesystem;
                        if ( $wp_filesystem->put_contents( $temp_file, $ics_calendar ) ) {
                            $attachments[] = $temp_file;
                        }
                    }
                }
            }
        }

        return $attachments;
    }

    public static function send_registration( $subscriber_id, $campaign_id, $recurring_signups = [] ) {

        $record = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
        if ( is_wp_error( $record ) ){
            dt_write_log( 'failed to record' );
            return;
        }
        if ( ! isset( $record['contact_email'] ) || empty( $record['contact_email'] ) ){
            return;
        }
        $commitments = DT_Subscriptions::get_subscriber_prayer_times( $campaign_id, $subscriber_id, $only_future = true );
        if ( empty( $commitments ) ) {
            dt_write_log( 'failed to commitments' );
            return;
        }
        $locale = $record['lang'] ?? 'en_US';
        self::switch_email_locale( $locale );

        $to = [];
        foreach ( $record['contact_email'] as $value ){
            $to[] = trim( $value['value'] );
        }
        $to = implode( ',', $to );

        $timezone = !empty( $record['timezone'] ) ? $record['timezone'] : 'America/Chicago';
        $tz = new DateTimeZone( $timezone );
        $commitment_list = '<ul>';
        foreach ( $commitments as $index => $row ){
            $begin_date = new DateTime( '@'.$row['time_begin'] );
            $end_date = new DateTime( '@'.$row['time_end'] );
            $begin_date->setTimezone( $tz );
            $end_date->setTimezone( $tz );
            $commitment_list .= '<li>';
            $commitment_list .= sprintf(
                _x( '%1$s from %2$s to %3$s', 'August 18, 2021 from 03:15 am to 03:30 am for Tokyo, Japan', 'disciple-tools-prayer-campaigns' ),
                DT_Time_Utilities::display_date_localized( $begin_date, $locale, $timezone ),
                '<strong>' . DT_Time_Utilities::display_hour_localized( $begin_date, $locale, $timezone ) . '</strong>',
                '<strong>' . DT_Time_Utilities::display_hour_localized( $end_date, $locale, $timezone ) . '</strong>'
            );
            $commitment_list .= '</li>';
            if ( $index > 3 ){
                $commitment_list .= '<li>' . __( 'see more on your account', 'disciple-tools-prayer-campaigns' ) . '</li>';
                break;
            }
        }
        $commitment_list .= '</ul>';

        $recurring_list = '<ul>';
        foreach ( $recurring_signups as $signup ){
            $recurring_list .= '<li><strong>' . $signup['label'] . '</strong></li>';
        }
        $recurring_list .= '</ul>';

        $subject = __( 'Registered to pray with us!', 'disciple-tools-prayer-campaigns' );

        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        $sign_up_email_extra_message = '';
        $sign_up_content_translation = DT_Campaign_Languages::get_translation( $campaign_id, 'signup_content', $record['lang'] ?? 'en_US', $campaign['signup_content'] ?? '' );
        if ( $sign_up_content_translation ){
            $sign_up_email_extra_message = '<p>' .  $sign_up_content_translation . '</p>';
        }

        if ( strpos( $sign_up_email_extra_message, '<a' ) === false ){
            $sign_up_email_extra_message = make_clickable( $sign_up_email_extra_message );
        }

        $calendar_url = trailingslashit( DT_Magic_URL::get_link_url_for_post( 'subscriptions', $subscriber_id, 'subscriptions_app', 'manage' ) ) . 'download_calendar';

        $sign_up_email_extra_message = apply_filters( 'dt_campaign_signup_content', $sign_up_email_extra_message );

        $link = self::management_link( $record, $campaign_id );

        $message = '';
        if ( !empty( $record['name'] ) ){
            $message .= Campaigns_Email_Template::email_greeting_part( sprintf( __( 'Hello %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $record['name'] ) ) );
        }
        $tagline = DT_Porch_Settings::get_field_translation( 'email_tagline' );
        $message .= Campaigns_Email_Template::email_content_part( $tagline );
        $message .= Campaigns_Email_Template::email_content_part( __( 'Access your account to see your commitments and make changes:', 'disciple-tools-prayer-campaigns' ) );
        $message .= Campaigns_Email_Template::email_button_part( __( 'Access Account', 'disciple-tools-prayer-campaigns' ), $link );
        if ( !empty( $recurring_signups ) ){
            $message .= Campaigns_Email_Template::email_content_part(
                __( 'You signed up to pray:', 'disciple-tools-prayer-campaigns' )
                . $recurring_list
            );
        }
        $message .= Campaigns_Email_Template::email_content_part(
            __( 'These are your next prayer commitments:', 'disciple-tools-prayer-campaigns' )
            . $commitment_list
        );
        $message .= Campaigns_Email_Template::email_content_part( sprintf( __( 'Times are shown according to: %s time', 'disciple-tools-prayer-campaigns' ), '<strong>' . esc_html( $timezone ) . '</strong>' ) );
        if ( !empty( $sign_up_email_extra_message ) ){
            $message .= Campaigns_Email_Template::email_content_part( $sign_up_email_extra_message );
        }
        $message .= Campaigns_Email_Template::email_button_part( __( 'Download Calendar', 'disciple-tools-prayer-campaigns' ), $calendar_url );

        $full_email = Campaigns_Email_Template::build_campaign_email( $message, $campaign_id );

        $attachments = self::generate_registration_attachments( $subscriber_id );

        $sent = self::send_prayer_campaign_email( $to, $subject, $full_email, [], $attachments, $campaign_id );
        if ( ! $sent ){
            dt_write_log( __METHOD__ . ': Unable to send email. ' . $to );
        }
        return $sent;
    }


    public static function send_prayer_time_reminder( $subscriber_id, $reports, $campaign_id, $prayer_fuel_link = null ){
        $record = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );

        $locale = $record['lang'] ?? 'en_US';
        self::switch_email_locale( $locale );

        $prayer_fuel_link_text = '';
        $prayer_fuel_link = self::prayer_fuel_link( $record, $campaign_id );
        if ( !empty( $prayer_fuel_link ) ){
            $prayer_fuel_link_text = '<p>' . sprintf( _x( 'Click here to see the prayer prompts for today: %s', 'Click here to see the prayer prompts for today: link-html-code-here', 'disciple-tools-prayer-campaigns' ), '' ) . '</p>';
        }

        $commitment_list = '';
        $timezone = !empty( $record['timezone'] ) ? $record['timezone'] : 'America/Chicago';
        $tz = new DateTimeZone( $timezone );
        foreach ( $reports as $row ){
            $begin_date = new DateTime( '@'.$row['time_begin'] );
            $end_date = new DateTime( '@'.$row['time_end'] );
            $begin_date->setTimezone( $tz );
            $end_date->setTimezone( $tz );
            $commitment_list .= sprintf(
                _x( '%1$s from %2$s to %3$s', 'August 18, 2021 from 03:15 am to 03:30 am for Tokyo, Japan', 'disciple-tools-prayer-campaigns' ),
                DT_Time_Utilities::display_date_localized( $begin_date, $locale, $timezone ),
                '<strong>' . DT_Time_Utilities::display_hour_localized( $begin_date, $locale, $timezone ) . '</strong>',
                '<strong>' . DT_Time_Utilities::display_hour_localized( $end_date, $locale, $timezone ) . '</strong>'
            );
            if ( !empty( $row['label'] ) ){
                $commitment_list .= ' ' . sprintf( _x( 'for %s', 'for Paris, France', 'disciple-tools-prayer-campaigns' ), $row['label'] );
            }
            $commitment_list .= '<br>';
        }
        $to = [];
        foreach ( $record['contact_email'] as $value ){
            $to[] = trim( $value['value'] );
        }
        $to = implode( ',', $to );

        $campaign_subject_line = __( 'Prayer Time reminder!', 'disciple-tools-prayer-campaigns' );

        $prayer_content_message = DT_Campaign_Languages::get_translation( $campaign_id, 'reminder_content', $record['lang'] ?? 'en_US' );
        if ( $prayer_content_message ){
            $prayer_content_message = '<p>' .  $prayer_content_message . '</p>';
        } elseif ( !empty( $campaign['reminder_content'] ) ){
            $prayer_content_message = '<p>' .  $campaign['reminder_content'] . '</p>';
        }

        if ( strpos( $prayer_content_message, '<a' ) === false ){
            $prayer_content_message = make_clickable( $prayer_content_message );
        }
        $prayer_content_message = apply_filters( 'dt_campaign_reminder_prayer_content', $prayer_content_message );

        $management_link = self::management_link( $record, $campaign_id );

        $message = Campaigns_Email_Template::email_greeting_part( sprintf( __( 'Hello %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $record['name'] ) ) );
        $message .= Campaigns_Email_Template::email_content_part( __( 'Thank you for praying with us.', 'disciple-tools-prayer-campaigns' ) );
        $message .= Campaigns_Email_Template::email_content_part( __( 'Here are your upcoming prayer times:', 'disciple-tools-prayer-campaigns' ) );
        $message .= Campaigns_Email_Template::email_content_part( $commitment_list );
        $message .= Campaigns_Email_Template::email_content_part( sprintf( __( 'Times are shown according to: %s time', 'disciple-tools-prayer-campaigns' ), '<strong>' . esc_html( $timezone ) . '</strong>' ) );
        if ( !empty( $prayer_fuel_link_text ) ){
            $message .= Campaigns_Email_Template::email_content_part( $prayer_fuel_link_text );
            $message .= Campaigns_Email_Template::email_button_part( __( 'Prayer Fuel', 'disciple-tools-prayer-campaigns' ), $prayer_fuel_link );
        }
        if ( !empty( $prayer_content_message ) ){
            $message .= Campaigns_Email_Template::email_content_part( $prayer_content_message );
        }
        $message .= Campaigns_Email_Template::email_content_part( __( 'Access your account to see your commitments and make changes:', 'disciple-tools-prayer-campaigns' ) );
        $message .= Campaigns_Email_Template::email_button_part( __( 'Access Account', 'disciple-tools-prayer-campaigns' ), $management_link, '#a3a3a3' );

        $full_email = Campaigns_Email_Template::build_campaign_email( $message, $campaign_id );

        return self::send_prayer_campaign_email( $to, $campaign_subject_line, $full_email, [], [], $campaign_id );
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

        if ( ! empty( $keys ) ) {
            foreach ( $keys as $public_key ) {
                $message = Campaigns_Email_Template::email_greeting_part( 'Thank you for praying with us.' );
                $message .= Campaigns_Email_Template::email_content_part( 'Here is the link you requested to your prayer commitments:' );
                $message .= Campaigns_Email_Template::email_content_part( '<a href="'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $public_key.'">Link to your commitments!</a>' );
                $message .= Campaigns_Email_Template::email_content_part( '<hr>' );
                $message .= Campaigns_Email_Template::email_content_part( 'If you did not request this link, just ignore this email.' );
                $message .= Campaigns_Email_Template::email_content_part( '<'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $public_key.'>' );

                $full_email = Campaigns_Email_Template::build_campaign_email( $message, $campaign_id );

                $sent = self::send_prayer_campaign_email( $email, $subject, $full_email, [], [], $campaign_id );
                if ( ! $sent ){
                    dt_write_log( __METHOD__ . ': Unable to send email. ' . $email );
                }
            }
        } else {
            $message = Campaigns_Email_Template::email_greeting_part( 'Thank you for praying with us.' );
            $message .= Campaigns_Email_Template::email_content_part( 'Sorry, we were unable to find a sign up associated with this email address.' );
            $full_email = Campaigns_Email_Template::build_campaign_email( $message, $campaign_id );

            $sent = self::send_prayer_campaign_email( $email, $subject, $full_email, [], [], $campaign_id );
            if ( ! $sent ){
                dt_write_log( __METHOD__ . ': Unable to send email. ' . $email );
            }
        }
    }


    public static function send_resubscribe_tickler( $subscriber_id, $campaign_id, $signups, $force_display = false ){
        $subscriber = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
        if ( is_wp_error( $subscriber ) || !isset( $subscriber['contact_email'][0]['value'] ) ){
            return false;
        }
        $locale = $subscriber['lang'] ?? 'en_US';
        self::switch_email_locale( $locale );
        $timezone = !empty( $subscriber['timezone'] ) ? $subscriber['timezone'] : 'America/Chicago';

        $manage_link = self::management_link( $subscriber );

        //Order signups by "last"
        usort( $signups, function( $a, $b ){
            return $a['last'] <=> $b['last'];
        } );
        $expiring_signups_list = '<ul>';
        foreach ( $signups as $signup ){
            if ( $force_display || ( $signup['last'] < time() + 3 * WEEK_IN_SECONDS && $signup['last'] > time() ) ){
                $string = DT_Subscriptions::get_recurring_signup_label( $signup, $timezone, $locale, true );
                $expiring_signups_list .= '<li>' . $string . '</li>';
            }
        }
        $expiring_signups_list .= '</ul>';

        $title = DT_Porch_Settings::get_field_translation( 'name', $locale, $campaign_id );

        $subject = __( '[ACTION NEEDED] Continue Praying?', 'disciple-tools-prayer-campaigns' );

        $message = Campaigns_Email_Template::email_greeting_part( sprintf( __( 'Hello %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $subscriber['name'] ) ) );

        $message .= Campaigns_Email_Template::email_content_part( __( 'Thank you for praying with us.', 'disciple-tools-prayer-campaigns' ) . ' ' . __( 'Some of your prayer times will be ending soon.', 'disciple-tools-prayer-campaigns' ) );
        $message .= Campaigns_Email_Template::email_content_part( __( 'The prayer times are:', 'disciple-tools-prayer-campaigns' ) );

        $message .= Campaigns_Email_Template::email_content_part( $expiring_signups_list );

        $message .= Campaigns_Email_Template::email_content_part( __( 'To continue praying, and to keep receiving the notifications, please access your account and click "extend" next to your commitment(s). You can also sign up for a different time if you wish.', 'disciple-tools-prayer-campaigns' ) );
        $message .= Campaigns_Email_Template::email_button_part( __( 'Access Account', 'disciple-tools-prayer-campaigns' ), $manage_link );
        $message .= Campaigns_Email_Template::email_content_part( __( 'You do not have to do anything if you do not want to continue praying when your prayer times end.', 'disciple-tools-prayer-campaigns' ) );

        $message .= Campaigns_Email_Template::email_content_part( __( 'Thank you', 'disciple-tools-prayer-campaigns' ) . ',<br>' . $title );
        $full_email = Campaigns_Email_Template::build_campaign_email( $message, $campaign_id );

        return self::send_prayer_campaign_email( $subscriber['contact_email'][0]['value'], $subject, $full_email, [], [], $campaign_id );
    }


    public static function send_end_of_campaign_email( $subscriber_id, $campaign_id, $force = false ){
        $record = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
        if ( is_wp_error( $record ) ){
            dt_write_log( 'failed to record' );
            return;
        }
        //check if email already sent
        if ( !$force && in_array( 'end_of_campaign_email_' . $campaign_id, $record['tags'] ?? [], true ) ){
            return;
        }

        $porch_fields = DT_Porch_Settings::settings();
        if ( !isset( $record['contact_email'] ) || empty( $record['contact_email'] ) ){
            return;
        }
        if ( !isset( $porch_fields['name']['value'] ) ){
            return;
        }

        self::switch_email_locale( $record['lang'] ?? 'en_US' );

        $to = [];
        foreach ( $record['contact_email'] as $value ){
            $to[] = $value['value'];
        }
        $to = implode( ',', $to );

        $current_campaign = DT_Campaign_Landing_Settings::get_campaign( $campaign_id );

        $locale = $record['lang'] ?? 'en_US';

        $title = DT_Porch_Settings::get_field_translation( 'name', $record['lang'] ?? 'en_US', $campaign_id );

        $subject = __( 'Thank you for praying with us!', 'disciple-tools-prayer-campaigns' );

        $campaign_url = DT_Campaign_Landing_Settings::get_landing_page_url( $campaign_id );

        $message = self::end_of_campaign_generic_email( $record['name'], $title, $campaign_url, $current_campaign, $locale );

        $full_email = Campaigns_Email_Template::build_campaign_email( $message, $campaign_id );

        $sent = self::send_prayer_campaign_email( $to, $subject, $full_email, [], [], $campaign_id );
        if ( !$sent ){
            dt_write_log( __METHOD__ . ': Unable to send email. ' . $to );
        } else {
            DT_Posts::update_post( 'subscriptions', $subscriber_id, [ 'tags' => [ 'values' => [ [ 'value' => 'end_of_campaign_email_' . $campaign_id ] ] ] ], true, false );
            DT_Posts::add_post_comment( 'subscribers', $subscriber_id, 'Sent end of campaign email', 'comment', [], false, true );
        }
        return $sent;
    }

    public static function end_of_campaign_generic_email( $name, $campaign_title, $campaign_url, $current_campaign, $locale ){

        $message = Campaigns_Email_Template::email_content_part( '<h3>' . sprintf( __( 'Dear %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $name ) ) . '</h3>' );

        if ( !empty( $current_campaign['email_tagline'] ) ) {
            $tag = DT_Porch_Settings::get_field_translation( 'email_tagline', $locale );
            $message .= Campaigns_Email_Template::email_content_part( $tag );
        } else {
            $tag = __( 'Strategic prayer for a Disciple Making Movement', 'disciple-tools-prayer-campaigns' );
            $message .= Campaigns_Email_Template::email_content_part(
                sprintf( __( 'Thank you for joining %1$s in %2$s.', 'disciple-tools-prayer-campaigns' ), esc_html( $campaign_title ), lcfirst( $tag ) )
            );
        }

        $custom_message = DT_Porch_Settings::get_field_translation( 'end_of_campaign_content', $locale );
        $custom_message = apply_filters( 'end_of_campaign_content', $custom_message, $current_campaign, $locale );

        if ( !empty( $custom_message ) ){
            $message .= Campaigns_Email_Template::email_content_part( $custom_message );
        } else {
            if ( isset( $campaign['porch_type']['key'] ) && $campaign['porch_type']['key'] === 'ramadan-porch' ){
                $message .= Campaigns_Email_Template::email_content_part(
                    __( "We will only know in eternity the full impact of the thousands of hours of prayer for the Muslim world during Ramadan. However, we know from God's Word that prayer is powerful and effective (James 5:16).", 'disciple-tools-prayer-campaigns' )
                );
                $message .= Campaigns_Email_Template::email_content_part(
                    __( 'Click the button below for a glimpse at what you contributed to this Ramadan. We would also love to hear impressions or words you received from God as you prayed.', 'disciple-tools-prayer-campaigns' )
                );
                $message .= Campaigns_Email_Template::email_content_part( __( 'Lastly, the Ramadan 24/7 Prayer Stats page also has a signup section at the bottom. Make sure you are signed up to receive news about future prayer opportunities by Prayer.Tools, the makers of this prayer tool.', 'disciple-tools-prayer-campaigns' ) );

            } else {
                $message .= Campaigns_Email_Template::email_content_part(
                    __( "We will only know in eternity the full impact of our prayer. However, we know from God's Word that prayer is powerful and effective (James 5:16).", 'disciple-tools-prayer-campaigns' )
                );
                $message .= Campaigns_Email_Template::email_content_part(
                    __( 'Click the button below for a glimpse at what you contributed to. We would also love to hear impressions or words you received from God as you prayed.', 'disciple-tools-prayer-campaigns' )
                );
                $message .= Campaigns_Email_Template::email_content_part( __( 'Finally, the folks at Prayer.Tools built this prayer tool. You can make sure you’re signed up to receive news about future prayer opportunities on the Stats page.', 'disciple-tools-prayer-campaigns' ) );
            }
        }

        $url = $campaign_url . '/stats';
        $message .= Campaigns_Email_Template::email_button_part( __( 'See Prayer Stats', 'disciple-tools-prayer-campaigns' ), $url );

        $custom_message_after_button = DT_Porch_Settings::get_field_translation( 'end_of_campaign_content_after_stats_button', $locale );
        if ( !empty( $custom_message_after_button ) ){
            $message .= Campaigns_Email_Template::email_content_part( $custom_message_after_button );
        }

        if ( !empty( $current_campaign['email_signature'] ) ) {
            $signature = DT_Porch_Settings::get_field_translation( 'email_signature', $locale );
        } else {
            $signature = __( 'In Christ with you,', 'disciple-tools-prayer-campaigns' ) . '<br>' . $campaign_title;
        }

        $message .= Campaigns_Email_Template::email_content_part( $signature );

        return $message;
    }


    public static function default_email_address(): string {
        $default_addr = apply_filters( 'wp_mail_from', '' );

        if ( empty( $default_addr ) ) {

            // Get the site domain and get rid of www.
            $sitename = wp_parse_url( network_home_url(), PHP_URL_HOST );
            if ( 'www.' === substr( $sitename, 0, 4 ) ) {
                $sitename = substr( $sitename, 4 );
            }

            $default_addr = 'wordpress@' . $sitename;
        }

        return $default_addr;
    }

    public static function default_email_name(): string {
        $default_name = apply_filters( 'wp_mail_from_name', '' );

        if ( empty( $default_name ) ) {
            $default_name = 'WordPress';
        }

        return $default_name;
    }
}

use WP_Queue\Job;
class End_Of_Campaign_Email_Job extends Job {
    public $subscriber_id;
    public $campaign_id;
    public function __construct( $subscriber_id, $campaign_id ){
        $this->subscriber_id = $subscriber_id;
        $this->campaign_id = $campaign_id;
    }

    /**
     * Handle job logic.
     */
    public function handle(){
        DT_Prayer_Campaigns_Send_Email::send_end_of_campaign_email( $this->subscriber_id, $this->campaign_id );
    }
}


class Campaigns_Email_Template {


    public static function build_campaign_email( $content, $campaign_id, $logo_url = null ){

        $email = self::email_head_part();
        $email .= self::email_logo_part( $campaign_id, $logo_url );
        $email .= $content;
        $email .= self::email_footer_part();
        return $email;
    }


    public static function email_head_part(){
        ob_start();
        ?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
        <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="format-detection" content="telephone=no"><meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Prayer.Tools</title><style type="text/css" emogrify="no">#outlook a { padding:0; } .ExternalClass { width:100%; } .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div { line-height: 100%; } table td { border-collapse: collapse; mso-line-height-rule: exactly; } .editable.image { font-size: 0 !important; line-height: 0 !important; } .nl2go_preheader { display: none !important; mso-hide:all !important; mso-line-height-rule: exactly; visibility: hidden !important; line-height: 0px !important; font-size: 0px !important; } body { width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; margin:0; padding:0; } img { outline:none; text-decoration:none; -ms-interpolation-mode: bicubic; } a img { border:none; } table { border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; } th { font-weight: normal; text-align: left; } *[class="gmail-fix"] { display: none !important; } </style>
            <style type="text/css" emogrify="no"> @media (max-width: 600px) { .gmx-killpill { content: ' \03D1';} } </style>
            <style type="text/css" emogrify="no">@media (max-width: 600px) { .gmx-killpill { content: ' \03D1';} .r0-c { box-sizing: border-box !important; text-align: center !important; valign: top !important; width: 320px !important } .r1-o { border-style: solid !important; margin: 0 auto 0 auto !important; width: 320px !important } .r2-c { box-sizing: border-box !important; text-align: center !important; valign: top !important; width: 100% !important } .r3-o { border-style: solid !important; margin: 0 auto 0 auto !important; width: 100% !important } .r4-i { background-color: #ffffff !important; padding-bottom: 20px !important; padding-left: 15px !important; padding-right: 15px !important; padding-top: 20px !important } .r5-c { box-sizing: border-box !important; display: block !important; valign: top !important; width: 100% !important } .r6-o { border-style: solid !important; width: 100% !important } .r7-i { padding-left: 0px !important; padding-right: 0px !important } .r8-o { background-size: auto !important; border-style: solid !important; margin: 0 auto 0 auto !important; width: 100% !important } .r9-i { padding-bottom: 15px !important; padding-top: 15px !important } .r10-c { box-sizing: border-box !important; text-align: left !important; valign: top !important; width: 100% !important } .r11-o { border-style: solid !important; margin: 0 auto 0 0 !important; width: 100% !important } .r12-i { padding-bottom: 15px !important; padding-top: 15px !important; text-align: left !important } .r13-o { border-style: solid !important; margin: 0 auto 0 auto !important; margin-bottom: 15px !important; margin-top: 15px !important; width: 100% !important } .r14-i { text-align: center !important } .r15-r { border-radius: 4px !important; border-width: 0px !important; box-sizing: border-box; height: initial !important; padding-bottom: 12px !important; padding-left: 5px !important; padding-right: 5px !important; padding-top: 12px !important; text-align: center !important; width: 100% !important } body { -webkit-text-size-adjust: none } .nl2go-responsive-hide { display: none } .nl2go-body-table { min-width: unset !important } .mobshow { height: auto !important; overflow: visible !important; max-height: unset !important; visibility: visible !important; border: none !important } .resp-table { display: inline-table !important } .magic-resp { display: table-cell !important } } </style><!--[if !mso]><!-->
            <style type="text/css" emogrify="no"> </style><!--<![endif]--><style type="text/css">p, h1, h2, h3, h4, ol, ul { margin: 0; } a, a:link { color: #2e2d2c; text-decoration: underline } .nl2go-default-textstyle { color: #3b3f44; font-family: arial,helvetica,sans-serif; font-size: 16px; line-height: 1.5; word-break: break-word } .default-button { color: #ffffff; font-family: arial,helvetica,sans-serif; font-size: 16px; font-style: normal; font-weight: bold; line-height: 1.15; text-decoration: none; word-break: break-word } .default-heading1 { color: #1F2D3D; font-family: arial,helvetica,sans-serif; font-size: 36px; word-break: break-word } .default-heading2 { color: #1F2D3D; font-family: arial,helvetica,sans-serif; font-size: 32px; word-break: break-word } .default-heading3 { color: #1F2D3D; font-family: arial,helvetica,sans-serif; font-size: 24px; word-break: break-word } .default-heading4 { color: #1F2D3D; font-family: arial,helvetica,sans-serif; font-size: 18px; word-break: break-word } a[x-apple-data-detectors] { color: inherit !important; text-decoration: inherit !important; font-size: inherit !important; font-family: inherit !important; font-weight: inherit !important; line-height: inherit !important; } .no-show-for-you { border: none; display: none; float: none; font-size: 0; height: 0; line-height: 0; max-height: 0; mso-hide: all; overflow: hidden; table-layout: fixed; visibility: hidden; width: 0; } </style><!--[if mso]><xml> <o:OfficeDocumentSettings> <o:AllowPNG/> <o:PixelsPerInch>96</o:PixelsPerInch> </o:OfficeDocumentSettings> </xml><![endif]--><style type="text/css">a:link{color: #2e2d2c; text-decoration: underline;}</style>
        </head>
        <body text="#3b3f44" link="#2e2d2c" yahoo="fix" style="">


        <table cellspacing="0" cellpadding="0" border="0" role="presentation" class="nl2go-body-table" width="100%" style="width: 100%;">
        <tr><td align="center" class="r0-c">
        <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="600" class="r1-o" style="table-layout: fixed; width: 600px;">
        <tr><td valign="top" class="">
        <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation">
        <?php
        $part = ob_get_clean();
        return $part;
    }

    public static function get_email_logo_url( $campaign_id, $logo_url = null ){
        if ( empty( $logo_url ) ){
            $campaign = DT_Campaign_Landing_Settings::get_campaign( $campaign_id );
            if ( !empty( $campaign['email_logo'] ) ){
                $logo_url = $campaign['email_logo'];
            }
        }
        if ( empty( $logo_url ) ){
            $logo_url = 'https://s3.prayer.tools/pt-logo.png';
        }
        return $logo_url;
    }

    public static function email_logo_part( $campaign_id, $logo_url = null ){
        $logo_url = self::get_email_logo_url( $campaign_id, $logo_url );
        ob_start();
        ?>
        <tr><td class="r2-c" align="center">
            <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r3-o" style="table-layout: fixed; width: 100%;"><!-- -->
            <tr><td class="r4-i" style="background-color: #ffffff; padding-bottom: 20px; padding-top: 20px;">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation">
            <tr><th width="100%" valign="top" class="r5-c" style="font-weight: normal;">
            <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r6-o" style="table-layout: fixed; width: 100%;"><!-- -->
            <tr><td valign="top" class="r7-i" style="padding-left: 15px; padding-right: 15px;">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation">
            <tr><td class="r2-c" align="center">
            <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="228" class="r8-o" style="table-layout: fixed; width: 228px;">
            <tr><td class="r9-i" style="font-size: 0px; line-height: 0px; padding-bottom: 15px; padding-top: 15px;">
                <img src="<?php echo esc_html( $logo_url ); ?>" width="228" border="0" class="" style="display: block; width: 100%;"></td>
            </tr></table></td> </tr></table></td> </tr></table></th> </tr></table></td> </tr></table></td>
        </tr>
        <?php
        $part = ob_get_clean();
        return $part;
    }

    public static function email_content_part( $content ){
        ob_start();
        ?>

        <tr><td class="r2-c" align="center">
            <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r3-o" style="table-layout: fixed; width: 100%;"><!-- -->
            <tr><td class="r4-i" style="background-color: #ffffff;">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation">
            <tr><th width="100%" valign="top" class="r5-c" style="font-weight: normal;">
            <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r6-o" style="table-layout: fixed; width: 100%;"><!-- -->
            <tr><td valign="top" class="r7-i" style="padding-left: 15px; padding-right: 15px;">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation">
            <tr><td class="r10-c" align="left">
            <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r11-o" style="table-layout: fixed; width: 100%;">
            <tr><td align="left" valign="top" class="r12-i nl2go-default-textstyle" style="color: #3b3f44; font-family: arial,helvetica,sans-serif; font-size: 16px; line-height: 1.5; word-break: break-word; padding-bottom: 15px; padding-top: 15px; text-align: left;">
            <div>
                <p style="margin: 0;"><?php echo nl2br( $content ) //phpcs:ignore ?></p>
            </div></td>
            </tr>
            </table></td> </tr></table></td> </tr></table></th> </tr></table></td> </tr>
            </table>
        </td></tr>
        <?php
        $part = ob_get_clean();
        return $part;
    }

    public static function email_greeting_part( $content ){
        ob_start();
        ?>

        <tr><td class="r2-c" align="center">
            <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r3-o" style="table-layout: fixed; width: 100%;"><!-- -->
            <tr><td class="r4-i" style="background-color: #ffffff;">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation">
            <tr><th width="100%" valign="top" class="r5-c" style="font-weight: normal;">
            <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r6-o" style="table-layout: fixed; width: 100%;"><!-- -->
            <tr><td valign="top" class="r7-i" style="padding-left: 15px; padding-right: 15px;">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation">
            <tr><td class="r10-c" align="left">
            <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r11-o" style="table-layout: fixed; width: 100%;">
            <tr><td align="left" valign="top" class="r12-i nl2go-default-textstyle" style="color: #3b3f44; font-family: arial,helvetica,sans-serif; font-size: 16px; line-height: 1.5; word-break: break-word; padding-bottom: 15px; padding-top: 15px; text-align: left;">
            <div>
                <h3 style="margin: 0;"><?php echo nl2br( $content ) //phpcs:ignore ?></h3>
            </div></td>
            </tr>
            </table></td> </tr></table></td> </tr></table></th> </tr></table></td> </tr>
            </table>
        </td></tr>
        <?php
        $part = ob_get_clean();
        return $part;
    }

    public static function email_button_part( $button_text, $button_url, $button_color = '#dc3822' ){

        ob_start();
        ?>
        <tr><td class="r2-c" align="center">
            <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r3-o" style="table-layout: fixed; width: 100%;"><!-- -->
                <tr><td class="r4-i" style="background-color: #ffffff;">
                <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation">
                <tr><th width="100%" valign="top" class="r5-c" style="font-weight: normal;">
                <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="100%" class="r6-o" style="table-layout: fixed; width: 100%;"><!-- -->
                <tr><td valign="top" class="r7-i" style="padding-left: 15px; padding-right: 15px;">
                <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation">
                <tr><td class="r2-c" align="center">
                <table cellspacing="0" cellpadding="0" border="0" role="presentation" width="285" class="r13-o" style="table-layout: fixed; width: 285px;">
                <tr class="nl2go-responsive-hide"><td height="15" style="font-size: 15px; line-height: 15px;">­</td> </tr>
                <tr><td height="18" align="center" valign="top" class="r14-i nl2go-default-textstyle" style="color: #3b3f44; font-family: arial,helvetica,sans-serif; font-size: 16px; line-height: 1.5; word-break: break-word;">
                    <!--[if mso]>
                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="<?php echo $button_url; //phpcs:ignore ?>" style="v-text-anchor:middle; height: 41px; width: 284px;" arcsize="10%" fillcolor="<?php echo esc_html( $button_color ); ?>" strokecolor="<?php echo esc_html( $button_color ); ?>" strokeweight="1px" data-btn="1">
                    <w:anchorlock> </w:anchorlock>
                    <v:textbox inset="0,0,0,0"> <div style="display:none;"> <center class="default-button">
                    <p><?php echo $button_text; //phpcs:ignore ?></p>
                    </center> </div> </v:textbox>
                    </v:roundrect> <![endif]-->
                    <!--[if !mso]><!-- -->
                    <a href="<?php echo $button_url; //phpcs:ignore ?>" class="r15-r default-button" target="_blank" title="<?php echo $button_text; //phpcs:ignore ?>" data-btn="1" style="font-style: normal; font-weight: bold; line-height: 1.15; text-decoration: none; word-break: break-word; border-style: solid; word-wrap: break-word; display: inline-block; -webkit-text-size-adjust: none; mso-hide: all; background-color: <?php echo esc_html( $button_color ); ?>; border-color: <?php echo esc_html( $button_color ); ?>; border-radius: 4px; border-width: 0px; color: #ffffff; font-family: arial,helvetica,sans-serif; font-size: 16px; height: 18px; padding-bottom: 12px; padding-left: 5px; padding-right: 5px; padding-top: 12px; width: 275px;">
                        <p style="margin: 0;"><?php echo $button_text; //phpcs:ignore ?></p>
                    </a> <!--<![endif]--> </td>
                </tr>
                <tr class="nl2go-responsive-hide"><td height="15" style="font-size: 15px; line-height: 15px;">­</td> </tr>
            </table></td> </tr></table></td> </tr></table></th> </tr></table></td> </tr></table></td> </tr>
        <?php
        $part = ob_get_clean();
        return $part;
    }

    public static function email_footer_part(){
        ob_start();
        ?>
        </table></td></tr></table></td></tr></table></body></html>
        <?php
        $part = ob_get_clean();
        return $part;
    }
}
