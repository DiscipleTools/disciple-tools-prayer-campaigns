<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Generic_Porch_Landing_Tab_Starter_Content
 */
class DT_Porch_Admin_Tab_Email_Settings extends DT_Porch_Admin_Tab_Base {
    public $key = 'campaign_email';
    public $title = 'Email Settings';

    public function __construct() {
        parent::__construct( $this->key );
        add_action( 'dt_prayer_campaigns_tab_content', [ $this, 'dt_prayer_campaigns_tab_content' ], 10, 2 );
        add_filter( 'prayer_campaign_tabs', [ $this, 'prayer_campaign_tabs' ], 10, 1 );
    }
    public function prayer_campaign_tabs( $tabs ) {
        $tabs[ $this->key ] = $this->title;
        return $tabs;
    }

    public function dt_prayer_campaigns_tab_content( $tab, $campaign_id ){
        if ( $tab !== $this->key || empty( $campaign_id ) ){
            return;
        }
        $this->content( true );
    }

    public function body_content() {
        $this->main_column();
    }
}
new DT_Porch_Admin_Tab_Email_Settings();