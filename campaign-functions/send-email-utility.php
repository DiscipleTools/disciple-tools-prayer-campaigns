<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DT_Prayer_Campaigns_Send_Email {
    public static function send_prayer_campaign_email( $to, $subject, $message, $headers = [], $attachments = [] ){

        if ( empty( $headers ) ){
            $headers[] = 'Content-Type: text/html';
            $headers[] = 'charset=UTF-8';
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


    public static function management_link( $record ){
        $key_name = 'public_key';
        if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
            $key_name = DT_Magic_URL::get_public_key_meta_key( 'subscriptions_app', 'manage' );
        }
        return trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record[$key_name];

    }

    public static function send_verification( $email, $code ){
        $lang = dt_campaign_get_current_lang();
        self::switch_email_locale( $lang );

        $subject = __( 'Verify your email address', 'disciple-tools-prayer-campaigns' );

        $message = Campaigns_Email_Template::email_content_part( __( 'Please copy and paste the following code into the Confirmation Code field.', 'disciple-tools-prayer-campaigns' ) );

        $message .= Campaigns_Email_Template::email_content_part(
            '<span style="font-size:30px">' . $code . '</span>'
        );

        $message .= Campaigns_Email_Template::email_content_part(
            __( 'This code will expire after 10 minutes.', 'disciple-tools-prayer-campaigns' ) . '<br>' .
            __( 'If you did not request this email, please ignore it.', 'disciple-tools-prayer-campaigns' )
        );

        $full_email = Campaigns_Email_Template::build_campaign_email( $message );

        $sent = self::send_prayer_campaign_email( $email, $subject, $full_email );
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
            $to[] = trim( $value['value'] );
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

        $subject = __( 'Registered to pray with us!', 'disciple-tools-prayer-campaigns' );

        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        $sign_up_email_extra_message = '';
        if ( isset( $record['lang'], $campaign['campaign_strings'][$record['lang']]['signup_content'] ) && $campaign['campaign_strings'][$record['lang']]['signup_content'] !== '' ){
            $sign_up_email_extra_message = '<p>' .  $campaign['campaign_strings'][$record['lang']]['signup_content'] . '</p>';
        } else if ( isset( $campaign['campaign_strings']['default']['signup_content'] ) && $campaign['campaign_strings']['default']['signup_content'] !== '' ) {
            $sign_up_email_extra_message = '<p>' .  $campaign['campaign_strings']['default']['signup_content'] . '</p>';
        }

        if ( strpos( $sign_up_email_extra_message, '<a' ) === false ){
            $url_regex = '@(http)?(s)?(://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
            $sign_up_email_extra_message = preg_replace( $url_regex, '<a href="http$2://$4" title="$0">$0</a>', $sign_up_email_extra_message );
        }

        $sign_up_email_extra_message = apply_filters( 'dt_campaign_signup_content', $sign_up_email_extra_message );

        $link = self::management_link( $record );

        $message = '';
        if ( !empty( $record['name'] ) ){
            $message .= Campaigns_Email_Template::email_greeting_part( sprintf( __( 'Hello %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $record['name'] ) ) );
        }
        $message .= Campaigns_Email_Template::email_content_part( __( 'Thank you for joining us in strategic prayer for a disciple making movement!', 'disciple-tools-prayer-campaigns' ) );
        $message .= Campaigns_Email_Template::email_content_part( __( 'Here are the times you have committed to pray:', 'disciple-tools-prayer-campaigns' ) );
        $message .= Campaigns_Email_Template::email_content_part( $commitment_list );
        $message .= Campaigns_Email_Template::email_content_part( sprintf( __( 'Times are shown according to: %s time', 'disciple-tools-prayer-campaigns' ), '<strong>' . esc_html( $timezone ) . '</strong>' ) );
        $message .= Campaigns_Email_Template::email_content_part( $sign_up_email_extra_message );
        $message .= Campaigns_Email_Template::email_content_part( '<hr><a href="'. $link .'">' .  __( 'Click here to manage your account and time commitments', 'disciple-tools-prayer-campaigns' ) . '</a>' );

        $full_email = Campaigns_Email_Template::build_campaign_email( $message );

        $attachments = self::generate_registration_attachments( $post_id );

        $sent = self::send_prayer_campaign_email( $to, $subject, $full_email, [], $attachments );
        if ( ! $sent ){
            dt_write_log( __METHOD__ . ': Unable to send email. ' . $to );
        }
        return $sent;
    }


    public static function send_prayer_time_reminder( $subscriber_id, $reports, $campaign_id, $prayer_fuel_link = null ){
        $record = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );

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
                $begin_date->format( 'F d, Y' ),
                '<strong>' . $begin_date->format( 'H:i a' ) . '</strong>',
                '<strong>' . $end_date->format( 'H:i a' ) . '</strong>'
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

        $prayer_content_message = '';
        if ( isset( $record['lang'], $campaign['campaign_strings'][$record['lang']]['reminder_content'] ) && $campaign['campaign_strings'][$record['lang']]['reminder_content'] !== '' ){
            $prayer_content_message = $campaign['campaign_strings'][$record['lang']]['reminder_content'];
        } else if ( isset( $campaign['campaign_strings']['default']['reminder_content'] ) && $campaign['campaign_strings']['default']['reminder_content'] !== '' ) {
            $prayer_content_message = $campaign['campaign_strings']['default']['reminder_content'];
        }

        if ( strpos( $prayer_content_message, '<a' ) === false ){
            $url_regex = '@(http)?(s)?(://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
            $prayer_content_message = preg_replace( $url_regex, '<a href="http$2://$4" title="$0">$0</a>', $prayer_content_message );
        }
        $prayer_content_message = apply_filters( 'dt_campaign_reminder_prayer_content', $prayer_content_message );

        $management_link = self::management_link( $record );

        $message = Campaigns_Email_Template::email_greeting_part( sprintf( __( 'Hello %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $record['name'] ) ) );
        $message .= Campaigns_Email_Template::email_content_part( __( 'Thank you for praying with us!', 'disciple-tools-prayer-campaigns' ) );
        $message .= Campaigns_Email_Template::email_content_part( __( 'Here are your upcoming prayer times:', 'disciple-tools-prayer-campaigns' ) );
        $message .= Campaigns_Email_Template::email_content_part( $commitment_list );
        $message .= Campaigns_Email_Template::email_content_part( sprintf( __( 'Times are shown according to: %s time', 'disciple-tools-prayer-campaigns' ), '<strong>' . esc_html( $timezone ) . '</strong>' ) );
        if ( !empty( $prayer_fuel_link ) ){
            $message .= Campaigns_Email_Template::email_content_part( $prayer_fuel_link );
        }
        if ( !empty( $prayer_content_message ) ){
            $message .= Campaigns_Email_Template::email_content_part( $prayer_content_message );
        }
        $message .= Campaigns_Email_Template::email_content_part( '<hr><a href="'. $management_link .'">' .  __( 'Click here to manage your account and time commitments', 'disciple-tools-prayer-campaigns' ) . '</a>' );

        $full_email = Campaigns_Email_Template::build_campaign_email( $message );

        return self::send_prayer_campaign_email( $to, $campaign_subject_line, $full_email );

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
                $message = Campaigns_Email_Template::email_greeting_part( 'Thank you for praying with us!' );
                $message .= Campaigns_Email_Template::email_content_part( 'Here is the link you requested to your prayer commitments:' );
                $message .= Campaigns_Email_Template::email_content_part( '<a href="'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $public_key.'">Link to your commitments!</a>' );
                $message .= Campaigns_Email_Template::email_content_part( '<hr>' );
                $message .= Campaigns_Email_Template::email_content_part( 'If you did not request this link, just ignore this email.' );
                $message .= Campaigns_Email_Template::email_content_part( '<'. trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $public_key.'>' );

                $full_email = Campaigns_Email_Template::build_campaign_email( $message );

                $sent = self::send_prayer_campaign_email( $email, $subject, $full_email );
                if ( ! $sent ){
                    dt_write_log( __METHOD__ . ': Unable to send email. ' . $email );
                }
            }
        } else {
            $message = Campaigns_Email_Template::email_greeting_part( 'Thank you for praying with us!' );
            $message .= Campaigns_Email_Template::email_content_part( 'Sorry, we were unable to find a sign up associated with this email address.' );
            $full_email = Campaigns_Email_Template::build_campaign_email( $message );

            $sent = self::send_prayer_campaign_email( $email, $subject, $full_email );
            if ( ! $sent ){
                dt_write_log( __METHOD__ . ': Unable to send email. ' . $email );
            }
        }
    }


    public static function send_resubscribe_tickler( $subscriber_id ){
        $subscriber = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
        if ( is_wp_error( $subscriber ) || !isset( $subscriber['contact_email'][0]['value'] ) ){
            return false;
        }
        $manage_link = self::management_link( $subscriber );
        $porch_fields = DT_Porch_Settings::settings();

        $title = isset( $porch_fields['title']['value'] ) ? $porch_fields['title']['value'] : site_url();

        $subject = __( 'Continue Praying?', 'disciple-tools-prayer-campaigns' );

        $message = Campaigns_Email_Template::email_greeting_part( sprintf( __( 'Hello %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $subscriber['name'] ) ) );
        $message .= Campaigns_Email_Template::email_content_part( __( 'You are 2 weeks out from your last prayer time. Would you like to keep praying?', 'disciple-tools-prayer-campaigns' ) );
        $message .= Campaigns_Email_Template::email_content_part( __( 'Sign up for more prayer times or extend your recurring time here:', 'disciple-tools-prayer-campaigns' ) );
        $message .= Campaigns_Email_Template::email_button_part( 'Access Portal', $manage_link );
        $message .= Campaigns_Email_Template::email_content_part( __( 'Or click this link:', 'disciple-tools-prayer-campaigns' ) . ' <a href="'. $manage_link.'">' . $manage_link .  '</a>' );
        $message .= Campaigns_Email_Template::email_content_part( __( 'Thank you', 'disciple-tools-prayer-campaigns' ) . ',<br>' . $title );
        $full_email = Campaigns_Email_Template::build_campaign_email( $message );

        return self::send_prayer_campaign_email( $subscriber['contact_email'][0]['value'], $subject, $full_email );
    }


    public static function send_end_of_campaign_email( $subscriber_id, $campaign_id ){
        $record = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
        if ( is_wp_error( $record ) ){
            dt_write_log( 'failed to record' );
            return;
        }
        //check if email already sent
        if ( in_array( 'end_of_campaign_email_' . $campaign_id, $record['tags'] ?? [], true ) ){
            return;
        }

        $porch_fields = DT_Porch_Settings::settings();
        if ( !isset( $record['contact_email'] ) || empty( $record['contact_email'] ) ){
            return;
        }
        if ( !isset( $porch_fields['title']['value'] ) ){
            return;
        }

        self::switch_email_locale( $record['lang'] ?? null );

        $to = [];
        foreach ( $record['contact_email'] as $value ){
            $to[] = $value['value'];
        }
        $to = implode( ',', $to );

        $current_campaign = DT_Campaign_Settings::get_campaign();
        $location = DT_Porch_Settings::get_field_translation( 'country_name', $record['lang'] ?? 'en_US' );
        if ( empty( $location ) ){
            $location_grid = [];
            foreach ( $current_campaign['location_grid'] ?? [] as $grid ){
                $location_grid[] = $grid['label'];
            }
            $location = implode( ', ', $location_grid );
        }

        $title = DT_Porch_Settings::get_field_translation( 'title' );

        $subject = __( 'Thank you for praying with us!', 'disciple-tools-prayer-campaigns' );

        $is_current_campaign = isset( $current_campaign['ID'] ) && (int) $campaign_id === (int) $current_campaign['ID'];
        $current_selected_porch = DT_Campaign_Settings::get( 'selected_porch' );
        if ( $is_current_campaign && $current_selected_porch === 'ramadan-porch' ){
            $message = self::end_of_campaign_ramadan_email( $record['name'], $title, $record['lang'] ?? 'en_US', $location );
        } else {
            $message = self::end_of_campaign_generic_email( $record['name'], $title, $location );
        }
        $full_email = Campaigns_Email_Template::build_campaign_email( $message );

        $sent = self::send_prayer_campaign_email( $to, $subject, $full_email );
        if ( !$sent ){
            dt_write_log( __METHOD__ . ': Unable to send email. ' . $to );
        } else {
            DT_Posts::update_post( 'subscriptions', $subscriber_id, [ 'tags' => [ 'values' => [ [ 'value' => 'end_of_campaign_email_' . $campaign_id ] ] ] ], true, false );
            DT_Posts::add_post_comment( 'subscribers', $subscriber_id, 'Sent end of campaign email', 'comment', [], false, true );
        }
    }

    public static function end_of_campaign_generic_email( $name, $campaign_title, $location = '' ){

        if ( !empty( $porch_fields['country_name']['value'] ) ){
            $tag = sprintf( __( 'Strategic prayer for a disciple making movement in %s', 'disciple-tools-prayer-campaigns' ), $location );
        } else {
            $tag = __( 'Strategic prayer for a disciple making movement', 'disciple-tools-prayer-campaigns' );
        }

        $url = trailingslashit( site_url() ) . 'prayer/stats';

        $message = Campaigns_Email_Template::email_content_part( '<h3>' . sprintf( __( 'Dear %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $name ) ) . '</h3>' );

        $message .= Campaigns_Email_Template::email_content_part(
            sprintf( __( 'Thank you for joining %1$s in %2$s.', 'disciple-tools-prayer-campaigns' ), esc_html( $campaign_title ), lcfirst( $tag ) )
        );
        $message .= Campaigns_Email_Template::email_content_part(
            __( "We will only know in eternity the full impact of our prayer. However, we know from God's Word that prayer is powerful and effective (James 5:16).", 'disciple-tools-prayer-campaigns' )
        );
        $message .= Campaigns_Email_Template::email_content_part(
            __( 'Click the button below for a glimpse at what you contributed to. We would also love to hear impressions or words you received from God as you prayed.', 'disciple-tools-prayer-campaigns' )
        );
        $message .= Campaigns_Email_Template::email_content_part( 'Finally, the folks at Pray4Movement built this prayer tool. You can make sure you’re signed up to receive news about future prayer opportunities on the Stats page.' );

        $message .= Campaigns_Email_Template::email_button_part( __( 'See Prayer Stats', 'disciple-tools-prayer-campaigns' ), $url );
        $message .= Campaigns_Email_Template::email_content_part(
            __( 'In Christ with you,', 'disciple-tools-prayer-campaigns' ) . '<br>' . $campaign_title
        );

        return $message;
    }

    public static function end_of_campaign_ramadan_email( $name, $campaign_title, $lang = 'en_US', $location = '' ){
        if ( $location ){
            $tag = sprintf( __( 'Strategic prayer for a disciple making movement in %s', 'disciple-tools-prayer-campaigns' ), $location );
        } else {
            $tag = __( 'Strategic prayer for a disciple making movement', 'disciple-tools-prayer-campaigns' );
        }

        $url = trailingslashit( site_url() ) . 'prayer/stats';

        $message = Campaigns_Email_Template::email_content_part( '<h3>' . sprintf( __( 'Dear %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $name ) ) . '</h3>' );

        $message .= Campaigns_Email_Template::email_content_part(
            sprintf( __( 'Thank you for joining %1$s in %2$s.', 'disciple-tools-prayer-campaigns' ), esc_html( $campaign_title ), lcfirst( $tag ) )
        );
        $message .= Campaigns_Email_Template::email_content_part(
            __( "We will only know in eternity the full impact of the thousands of hours of prayer for the Muslim world during Ramadan. However, we know from God's Word that prayer is powerful and effective (James 5:16).", 'disciple-tools-prayer-campaigns' )
        );
        if ( empty( $location ) || $lang !== 'en_US' ){
            $message .= Campaigns_Email_Template::email_content_part(
                __( 'Click the button below for a glimpse at what you contributed to this Ramadan. We would also love to hear impressions or words you received from God as you prayed.', 'disciple-tools-prayer-campaigns' )
            );
        } else {
            $message .= Campaigns_Email_Template::email_content_part(
                sprintf( 'Click the button below for a glimpse at what you contributed to this Ramadan. We would also love to hear impressions or words you received from God for %s as you prayed.', $location )
            );
        }
        $message .= Campaigns_Email_Template::email_content_part( __( 'Lastly, the Ramadan 24/7 Prayer Stats page also has a signup section at the bottom. Make sure you are signed up to receive news about future prayer opportunities by Pray4Movement, the makers of this prayer tool.', 'disciple-tools-prayer-campaigns' ) );

        $message .= Campaigns_Email_Template::email_button_part( __( 'See Ramadan 24/7 Prayer Stats', 'disciple-tools-prayer-campaigns' ), $url );
        $message .= Campaigns_Email_Template::email_content_part(
            __( 'In Christ with you,', 'disciple-tools-prayer-campaigns' ) . '<br>' . $campaign_title
        );

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


    public static function build_campaign_email( $content, $logo_url = null ){

        $email = self::email_head_part();
        $email .= self::email_logo_part( $logo_url );
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
            <title>Pray4Movement</title><style type="text/css" emogrify="no">#outlook a { padding:0; } .ExternalClass { width:100%; } .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div { line-height: 100%; } table td { border-collapse: collapse; mso-line-height-rule: exactly; } .editable.image { font-size: 0 !important; line-height: 0 !important; } .nl2go_preheader { display: none !important; mso-hide:all !important; mso-line-height-rule: exactly; visibility: hidden !important; line-height: 0px !important; font-size: 0px !important; } body { width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; margin:0; padding:0; } img { outline:none; text-decoration:none; -ms-interpolation-mode: bicubic; } a img { border:none; } table { border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; } th { font-weight: normal; text-align: left; } *[class="gmail-fix"] { display: none !important; } </style>
            <style type="text/css" emogrify="no"> @media (max-width: 600px) { .gmx-killpill { content: ' \03D1';} } </style>
            <style type="text/css" emogrify="no">@media (max-width: 600px) { .gmx-killpill { content: ' \03D1';} .r0-c { box-sizing: border-box !important; text-align: center !important; valign: top !important; width: 320px !important } .r1-o { border-style: solid !important; margin: 0 auto 0 auto !important; width: 320px !important } .r2-c { box-sizing: border-box !important; text-align: center !important; valign: top !important; width: 100% !important } .r3-o { border-style: solid !important; margin: 0 auto 0 auto !important; width: 100% !important } .r4-i { background-color: #ffffff !important; padding-bottom: 20px !important; padding-left: 15px !important; padding-right: 15px !important; padding-top: 20px !important } .r5-c { box-sizing: border-box !important; display: block !important; valign: top !important; width: 100% !important } .r6-o { border-style: solid !important; width: 100% !important } .r7-i { padding-left: 0px !important; padding-right: 0px !important } .r8-o { background-size: auto !important; border-style: solid !important; margin: 0 auto 0 auto !important; width: 100% !important } .r9-i { padding-bottom: 15px !important; padding-top: 15px !important } .r10-c { box-sizing: border-box !important; text-align: left !important; valign: top !important; width: 100% !important } .r11-o { border-style: solid !important; margin: 0 auto 0 0 !important; width: 100% !important } .r12-i { padding-bottom: 15px !important; padding-top: 15px !important; text-align: left !important } .r13-o { border-style: solid !important; margin: 0 auto 0 auto !important; margin-bottom: 15px !important; margin-top: 15px !important; width: 100% !important } .r14-i { text-align: center !important } .r15-r { background-color: #8bc34a !important; border-radius: 4px !important; border-width: 0px !important; box-sizing: border-box; height: initial !important; padding-bottom: 12px !important; padding-left: 5px !important; padding-right: 5px !important; padding-top: 12px !important; text-align: center !important; width: 100% !important } body { -webkit-text-size-adjust: none } .nl2go-responsive-hide { display: none } .nl2go-body-table { min-width: unset !important } .mobshow { height: auto !important; overflow: visible !important; max-height: unset !important; visibility: visible !important; border: none !important } .resp-table { display: inline-table !important } .magic-resp { display: table-cell !important } } </style><!--[if !mso]><!-->
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

    public static function get_email_logo_url( $logo_url = null ){
        if ( empty( $logo_url ) ){
            $settings_manager = new DT_Campaign_Settings();
            $logo_url = $settings_manager->get( 'email_logo' );
        }
        if ( empty( $logo_url ) ){
            $settings = DT_Porch_Settings::settings();
            if ( !empty( $settings['logo_url']['value'] ) ){
                $logo_url = $settings['logo_url']['value'];
            }
        }
        if ( empty( $logo_url ) ){
            $logo_url = 'https://gospelambition.s3.amazonaws.com/logos/pray4movement-logo.png';
        }
        return $logo_url;
    }

    public static function email_logo_part( $logo_url = null ){
        $logo_url = self::get_email_logo_url( $logo_url );
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

    public static function email_button_part( $button_text, $button_url ){
        $button_color = '#dc3822';

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
