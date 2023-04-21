<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DT_Prayer_Campaigns_Send_Email {
    public static function send_prayer_campaign_email( $to, $subject, $message, $headers = [] ){

        if ( empty( $headers ) ){
            $headers[] = 'Content-Type: text/html';
            $headers[] = 'charset=UTF-8';
        }
        $sent = wp_mail( $to, $subject, $message, $headers );
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

        $link = trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record[$key_name];

        $message .= '
            <h4>' . __( 'Thank you for joining us in strategic prayer for a disciple making movement! You\'re one click away from finishing your registration.', 'disciple-tools-prayer-campaigns' ) . '</h4>
            <p>' . __( 'Click here to verify your email address and confirm your prayer times:', 'disciple-tools-prayer-campaigns' ) .  '</p>
            <p>
                <a href="'. $link . '" style="display:inline-block; background-color: #4CAF50; border: none; color: white; padding: 15px 32px; font-size: 16px; cursor: pointer; border-radius: 2px;">' . __( 'Verify your account', 'disciple-tools-prayer-campaigns' ) .  '</a>
            </p>
            <p>
                ' . __( 'Or open this link:', 'disciple-tools-prayer-campaigns' ) . ' <a href="'. $link .'">' . trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $record[$key_name]. '</a>
            </p>

            <p>' . __( 'Here are the times you have committed to pray:', 'disciple-tools-prayer-campaigns' ) . '</p>
            <p>' . $commitment_list . '</p>
            <p>' . sprintf( __( 'Times are shown according to: %s time', 'disciple-tools-prayer-campaigns' ), '<strong>' . esc_html( $timezone ) . '</strong>' ) . '</p>
            ' . nl2br( $sign_up_email_extra_message ) . '
            <br>
            <hr>
            <p><a href="'. $link .'">' .  __( 'Click here to manage your account and time commitments', 'disciple-tools-prayer-campaigns' ) . '</a></p>
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

        $subject = __( 'Continue Praying?', 'disciple-tools-prayer-campaigns' );
        $message_body = '
            <h3>' . sprintf( __( 'Hello %s,', 'disciple-tools-prayer-campaigns' ), esc_html( $subscriber['name'] ) ) . '</h3>
            <p> ' . __( 'You are 2 weeks out from your last prayer time. Would you like to keep praying?', 'disciple-tools-prayer-campaigns' ) . '</p>
            <p>' . __( 'Sign up for more prayer times or extend your recurring time here:', 'disciple-tools-prayer-campaigns' ) . '</p>
            <p><a href="'. $manage_link.'">' . $manage_link .  '</a></p>
            <p>' . __( 'Thank you', 'disciple-tools-prayer-campaigns' ) . ',</p>
            <p>' .  ( isset( $porch_fields['title']['value'] ) ? $porch_fields['title']['value'] : site_url() ) . '</p>

        ';
        return self::send_prayer_campaign_email( $subscriber['contact_email'][0]['value'], $subject, $message_body );
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


    public static function build_campaign_email( $content ){

        $email = self::email_head_part();
        $email .= self::email_logo_part();
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
            <title>Pray4movement</title><style type="text/css" emogrify="no">#outlook a { padding:0; } .ExternalClass { width:100%; } .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div { line-height: 100%; } table td { border-collapse: collapse; mso-line-height-rule: exactly; } .editable.image { font-size: 0 !important; line-height: 0 !important; } .nl2go_preheader { display: none !important; mso-hide:all !important; mso-line-height-rule: exactly; visibility: hidden !important; line-height: 0px !important; font-size: 0px !important; } body { width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; margin:0; padding:0; } img { outline:none; text-decoration:none; -ms-interpolation-mode: bicubic; } a img { border:none; } table { border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; } th { font-weight: normal; text-align: left; } *[class="gmail-fix"] { display: none !important; } </style>
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

    public static function email_logo_part( $logo_url = null ){
        if ( empty( $logo_url ) ){
            $logo_url = 'https://gospelambition.s3.amazonaws.com/logos/pray4movement-logo.png';
        }
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