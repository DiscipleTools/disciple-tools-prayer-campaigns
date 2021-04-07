<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Test that DT_Module_Base has loaded
 */
if ( ! class_exists( 'DT_Module_Base' ) ) {
    dt_write_log( 'Disciple Tools System not loaded. Cannot load custom post type.' );
    return;
}

/**
 * Add any modules required or added for the post type
 */
add_filter( 'dt_post_type_modules', function( $modules ){

    $modules["subscriptions_base"] = [
        "name" => "Subscriptions",
        "enabled" => true,
        "locked" => true,
        "prerequisites" => [],
        "post_type" => "subscriptions",
        "description" => "Subscriptions base"
    ];
    $modules["subscriptions_management"] = [
        "name" => "Subscriptions Management",
        "enabled" => true,
        "locked" => true,
        "prerequisites" => [ "subscriptions_base" ],
        "post_type" => "subscriptions",
        "description" => "Subscriptions Management"
    ];

    $modules["campaigns_base"] = [
        "name" => "Campaigns",
        "enabled" => true,
        "locked" => true,
        "prerequisites" => [],
        "post_type" => "campaigns",
        "description" => "Campaigns base"
    ];
    $modules["campaigns_24hour_prayer"] = [
        "name" => "Campaigns - 24Hour Prayer",
        "enabled" => true,
        "locked" => false,
        "prerequisites" => [ "campaigns_base" ],
        "post_type" => "campaigns",
        "description" => "Campaigns - 24Hour Prayer"
    ];

    if ( isset( $modules["contacts_base"] ) ){
        $modules["contacts_base"]["locked"] = false;
    }
    if ( isset( $modules["groups_base"] ) ){
        $modules["groups_base"]["locked"] = false;
    }

    return $modules;
}, 20, 1 );

require_once 'module-subscriptions-base.php';
DT_Subscriptions_Base::instance();

require_once 'module-subscriptions-management.php';
DT_Subscriptions_Management::instance();

require_once 'module-campaigns.php';
DT_Campaigns_Base::instance();

require_once 'module-campaigns-24hour-prayer.php';
DT_Campaign_24Hour_Prayer::instance();

require_once 'module-campaigns-progress.php';
DT_Campaign_Progress::instance();
