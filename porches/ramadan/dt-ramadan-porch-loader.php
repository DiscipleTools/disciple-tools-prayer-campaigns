<?php

class Pray4movement_Ramadan_Porch_Loader extends DT_Generic_Porch_Loader {

    public $id = 'ramadan-porch';

    public function __construct() {
        parent::__construct( __DIR__ );

        $this->label = __( 'Ramadan Landing Page', 'disciple-tools-prayer-campaign' );
        add_filter( 'dt_campaigns_wizard_types', array( $this, 'wizard_types' ) );

        require_once( __DIR__ . '/settings.php' );
    }

    public function wizard_types( $wizard_types ) {
        $wizard_types[$this->id] = [
            'campaign_type' => '24hour',
            'porch' => $this->id,
            'label' => 'Ramadan Campaign',
        ];

        return $wizard_types;
    }
}
( new Pray4movement_Ramadan_Porch_Loader() )->register_porch();
