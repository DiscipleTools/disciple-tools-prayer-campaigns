<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Test that DT_Module_Base has loaded
 */
if ( ! class_exists( 'DT_Module_Base' ) ) {
    dt_write_log( 'Disciple.Tools System not loaded. Cannot load custom post type.' );
    return;
}

/**
 * Add any modules required or added for the post type
 */
add_filter( 'dt_post_type_modules', function( $modules ){

    $modules['subscriptions_management'] = [
        'name' => 'Subscriptions Management',
        'enabled' => true,
        'locked' => true,
        'prerequisites' => [],
        'post_type' => 'subscriptions',
        'description' => 'Subscriptions Management'
    ];

    if ( isset( $modules['contacts_base'] ) ){
        $modules['contacts_base']['locked'] = false;
    }
    if ( isset( $modules['groups_base'] ) ){
        $modules['groups_base']['locked'] = false;
    }

    return $modules;
}, 20, 1 );


/*
 * Subscriptions post type
 */
require_once 'subscriptions.php';
DT_Subscriptions_Base::instance();

/**
 * Campaigns Post Type
 */
require_once 'campaigns.php';
DT_Campaigns_Base::instance();

/*
 * 247 Ongoing campaign type module
 */
require_once 'module-campaigns-ongoing-prayer.php';
DT_Campaign_Ongoing_Prayer::instance();

/*
 * Subscriptions API
 */

require_once 'dt-subscriptions.php';


