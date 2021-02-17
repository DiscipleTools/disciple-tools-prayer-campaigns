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

    $modules["prayers_base"] = [
        "name" => "Prayer Subscription",
        "enabled" => true,
        "locked" => true,
        "prerequisites" => [ "contacts_base" ],
        "post_type" => "prayers",
        "description" => "Prayer subscription base"
    ];
    $modules["prayers_management"] = [
        "name" => "Prayer Subscription",
        "enabled" => true,
        "locked" => true,
        "prerequisites" => [ "prayers_base" ],
        "post_type" => "prayers",
        "description" => "Prayer Subscription Management Form"
    ];

    return $modules;
}, 20, 1 );

require_once 'module-base.php';
DT_Prayers_Base::instance();

require_once 'module-subscription.php';
DT_Prayer_Subscription_Management::instance();
