<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

class DT_Porch_Admin_Tab_Translations extends DT_Porch_Admin_Tab_Base {

    public $title = 'Text & Translations';

    public $key = 'campaign_landing_strings';

    public function __construct( string $porch_dir ) {
        parent::__construct( $this->key, $porch_dir );
        add_action( 'dt_prayer_campaigns_tab_content', [ $this, 'dt_prayer_campaigns_tab_content' ], 10, 2 );
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