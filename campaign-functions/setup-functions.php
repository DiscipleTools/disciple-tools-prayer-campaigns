<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
//remove_filter( 'the_content', [ $GLOBALS['wp_embed'], 'autoembed' ], 8 ); needed for video players

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
    if ( empty( $email ) || strpos( $email, 'wordpress' ) !== false ){
        $domain = parse_url( home_url() )['host'];
        $email = 'no-reply@' . $domain;
    }
    return $email;
}, 50 );
add_filter( 'wp_mail_from_name', function ( $name ) {
    if ( 'WordPress' === $name || empty( $name ) ){
        $name = get_bloginfo( 'name' );
    }
    return $name;
}, 50, 1 );


function dt_campaigns_is_prayer_tools_news_enabled(){
    //maybe make this a setting too.
    return apply_filters( 'dt_campaigns_is_p4m_news_enabled', defined( 'WP_DEBUG' ) ? !WP_DEBUG : true );
}

/**
 * Handle export via admin-post.php to avoid direct file access.
 */
add_action( 'admin_post_dt_pc_export_fuel', function(){
    if ( ! ( current_user_can( 'manage_dt' ) || current_user_can( 'edit_landings' ) ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'disciple-tools-prayer-campaigns' ) );
    }

    if ( ! isset( $_POST['export_from_file_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['export_from_file_nonce'] ) ), 'export_from_file' ) ){
        wp_die( esc_html__( 'Invalid request.', 'disciple-tools-prayer-campaigns' ) );
    }

    $lang = null;
    if ( isset( $_POST['language'] ) ){
        $lang = sanitize_text_field( wp_unslash( $_POST['language'] ) );
    }
    $linked_campaign = null;
    if ( isset( $_POST['linked_campaign'])){
        $linked_campaign = sanitize_text_field( wp_unslash( $_POST['linked_campaign'] ) );
    }
    $campaign_name = null;
    if ( isset( $_POST['campaign_name'])){
        $campaign_name = sanitize_text_field( wp_unslash( $_POST['campaign_name'] ) );
    }

    if ( ! function_exists( 'export_fuel' ) ){
        require_once __DIR__ . '/../admin/export.php';
    }

    export_fuel( $lang, $linked_campaign, $campaign_name );
    exit;
} );