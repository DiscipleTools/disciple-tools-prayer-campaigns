<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
add_filter( 'cron_schedules', 'dt_prayer_campaign_cron_schedules' );

function dt_prayer_campaign_cron_schedules( $schedules ){
    if ( !isset( $schedules['15min'] ) ){
        $schedules['15min'] = array(
            'interval' => 15 * 60,
            'display' => __( 'Once every 15 minutes' )
        );
    }
    return $schedules;
}

//need to be accessible outside of D.T
require_once( plugin_dir_path( __DIR__ ) . 'shortcodes/24hour.php' );
require_once( plugin_dir_path( __DIR__ ) . 'shortcodes/ongoing.php' );
require_once( plugin_dir_path( __DIR__ ) . 'shortcodes/generic.php' );
require_once( plugin_dir_path( __DIR__ ) . 'shortcodes/prayer-timer.php' );


function dt_campaigns_build_shortcode_from_array( $shortcode, $array ){
    $shortcode = '[' . $shortcode;
    foreach ( $array as $key => $value ){
        if ( !empty( $value ) ){
            $shortcode .= ' ' . $key . '="' . $value . '"';
        }
    }
    $shortcode .= ']';
    return $shortcode;
}

add_filter( 'wp_mail_from', function ( $email ) {
    $prayer_campaign_email = get_option( 'dt_prayer_campaign_email' );
    if ( !empty( $prayer_campaign_email ) ){
        return $prayer_campaign_email;
    } else if ( strpos( $email, 'wordpress' ) !== false ){
        $domain = parse_url( home_url() )['host'];
        $email = 'no-reply@' . $domain;
    }
    return $email;
}, 200 );
add_filter( 'wp_mail_from_name', function ( $name ) {
    $prayer_campaign_email_name = get_option( 'dt_prayer_campaign_email_name' );
    if ( !empty( $prayer_campaign_email_name ) ){
        return $prayer_campaign_email_name;
    }
    $campaign_name = DT_Porch_Settings::get_field_translation( 'campaign_name' );
    if ( !empty( $campaign_name ) ){
        return $campaign_name;
    }
    $base_name = dt_get_option( 'dt_email_base_name' );
    if ( !empty( $base_name ) ){
        return $base_name;
    }
    return $name;
}, 200 );
