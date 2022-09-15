<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

class DT_Campaign_Prayer_Fuel_Menu {

    public $token = 'dt_prayer_fuel';
    public $title = 'Prayer Fuel';
    private static $_instance = null;

    /**
     * DT_Prayer_Campaigns_Menu Instance
     *
     * Ensures only one instance of DT_Prayer_Campaigns_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return DT_Campaign_Prayer_Fuel_Menu instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( !current_user_can( 'wp_api_allowed_user' ) ) { // manage dt is a permission that is specific to Disciple.Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        $campaign = DT_Campaign_Settings::get_campaign();



        ?>

        <div class="wrap">
            <?php if ( empty( $campaign ) ): ?>

                <?php DT_Porch_Admin_Tab_Base::message_box( 'Prayer Fuel', 'Select a campaign to be able to view the prayer fuel in this table' ); ?>

            <?php else : ?>

                <h1 class="wp-heading-inline">Prayer Fuel</h1>

                <a href="post-new.php?post_type=landing" class="page-title-action">Add New</a>
                <hr class="wp-header-end">

                <?php

                $table = new DT_Campaign_Prayer_Fuel_Day_List();

                $table->prepare_items();

                $table->display();

                ?>

            <?php endif; ?>

        </div>

        <?php
    }
}

DT_Campaign_Prayer_Fuel_Menu::instance();
