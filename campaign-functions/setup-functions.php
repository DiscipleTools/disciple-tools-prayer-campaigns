<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
add_filter( 'cron_schedules', 'dt_prayer_campaign_cron_schedules' );

function dt_prayer_campaign_cron_schedules( $schedules ){
    if ( !isset( $schedules["15min"] ) ){
        $schedules["15min"] = array(
            'interval' => 15 * 60,
            'display' => __( 'Once every 15 minutes' )
        );
    }
    return $schedules;
}

//need to be accessible outside of D.T
require_once( plugin_dir_path( __DIR__ ) . 'shortcodes/24hour.php' );
// require_once( plugin_dir_path( __DIR__ ) . 'shortcodes/ongoing.php' ); // This was erroring for me as the ongoing.php file doesn't exist yet. so commented out for now
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
