<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DT_Ongoing_Porch_Loader extends DT_Generic_Porch_Loader {

    public $id = 'ongoing-porch';

    public function __construct() {
        parent::__construct( __DIR__ );

        $this->label = 'Ongoing Landing Page';
        add_filter( 'dt_campaigns_wizard_types', array( $this, 'wizard_types' ) );
    }

    public function wizard_types( $wizard_types ) {
        $ongoing_wizard = [
            'campaign_type' => 'ongoing',
            'porch' => $this->id,
            'label' => '24/7 Ongoing Campaign',
        ];
        $wizard_types[$this->id] = $ongoing_wizard;

        return $wizard_types;
    }

    public function load_porch_settings() {
        parent::load_porch_settings();

        require_once( __DIR__ . '/dt-ongoing-porch-settings.php' );
    }

}
//( new DT_Ongoing_Porch_Loader() )->register_porch();
