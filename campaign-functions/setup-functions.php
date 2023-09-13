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

if ( !function_exists( 'dt_recursive_sanitize_array' ) ){
    function dt_recursive_sanitize_array( array $array ) : array {
        foreach ( $array as $key => &$value ) {
            if ( is_array( $value ) ) {
                $value = dt_recursive_sanitize_array( $value );
            }
            else {
                $value = sanitize_text_field( wp_unslash( $value ) );
            }
        }
        return $array;
    }
}

//need to be accessible outside of D.T
require_once( plugin_dir_path( __DIR__ ) . 'campaign-functions/porch-api.php' );
require_once( plugin_dir_path( __DIR__ ) . 'shortcodes/ongoing.php' );
require_once( plugin_dir_path( __DIR__ ) . 'shortcodes/generic.php' );
require_once( plugin_dir_path( __DIR__ ) . 'shortcodes/prayer-timer.php' );
require_once( plugin_dir_path( __DIR__ ) . 'parts/components-enqueue.php' );


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
    }
    if ( class_exists( 'DT_Campaign_Settings' ) ){
        $email_address = DT_Campaign_Settings::get( 'email_address' );
        if ( !empty( $email_address ) ){
            return $email_address;
        }
    }
    if ( empty( $email ) || strpos( $email, 'wordpress' ) !== false ){
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
    if ( class_exists( 'DT_Campaign_Settings' ) ){
        $name = DT_Campaign_Settings::get( 'email_name' );
        if ( !empty( $name ) ){
            return $name;
        }
    }
    if ( empty( $name ) && class_exists( 'DT_Porch_Settings' ) ){
        $campaign_name = DT_Porch_Settings::get_field_translation( 'title' );
        if ( !empty( $campaign_name ) ){
            $name = $campaign_name;
        }
    }
    return $name;
}, 200 );

function dt_campaigns_is_p4m_news_enabled(){
    //maybe make this a setting too.
    return apply_filters( 'dt_campaigns_is_p4m_news_enabled', true );
}