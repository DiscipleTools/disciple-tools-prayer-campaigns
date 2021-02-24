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

    $modules["subscription_base"] = [
        "name" => "Subscription",
        "enabled" => true,
        "locked" => true,
        "prerequisites" => [ "contacts_base" ],
        "post_type" => "subscription",
        "description" => "Subscription base"
    ];
    $modules["subscription_management"] = [
        "name" => "Subscription Management",
        "enabled" => true,
        "locked" => true,
        "prerequisites" => [ "subscription_base" ],
        "post_type" => "subscription",
        "description" => "Subscription Management"
    ];

    return $modules;
}, 20, 1 );

require_once 'module-base.php';
DT_Subscription_Base::instance();

require_once 'module-management.php';
DT_Subscription_Management::instance();
