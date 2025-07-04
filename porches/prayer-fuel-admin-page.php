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

    public function __construct(){
        add_action( 'dt_prayer_campaigns_tab_content', [ $this, 'dt_prayer_campaigns_tab_content' ], 10, 2 );
        add_filter( 'prayer_campaign_tabs', [ $this, 'prayer_campaign_tabs' ], 21, 1 );
    }
    public function prayer_campaign_tabs( $tabs ) {
        $tabs[ $this->token ] = $this->title;
        return $tabs;
    }
    public function dt_prayer_campaigns_tab_content( $tab, $campaign_id ){
        if ( $tab !== $this->token || empty( $campaign_id ) ){
            return;
        }
        $this->content_body2( $campaign_id );
    }

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content_body2( $campaign_id ) {

        if ( !current_user_can( 'wp_api_allowed_user' ) ) { // manage dt is a permission that is specific to Disciple.Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        $campaign = DT_Campaign_Landing_Settings::get_campaign( $campaign_id );

        // For ongoing campaigns (no end date), redirect to current page if no paged parameter
        if ( !isset( $campaign['end_date']['timestamp'] ) && !isset( $_GET['paged'] ) ) {
            $start_date = $campaign['start_date']['timestamp'];
            $days_since_start_date = ( strtotime( 'now' ) - $start_date ) / ( 60 * 60 * 24 );
            $page = max( 1, round( $days_since_start_date / 50 ) );
            
            $redirect_url = add_query_arg( 'paged', $page );
            wp_safe_redirect( $redirect_url );
            exit;
        }
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
