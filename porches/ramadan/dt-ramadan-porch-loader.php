<?php

class DT_Ramadan_Porch_Loader extends DT_Generic_Porch_Loader {

    public $id = 'ramadan-porch';

    public function __construct() {
        parent::__construct( __DIR__ );

        $this->label = __( 'Ramadan Landing Page', 'disciple-tools-prayer-campaigns' );
        add_filter( 'dt_campaigns_wizard_types', array( $this, 'wizard_types' ) );

        require_once( __DIR__ . '/settings.php' );
    }

    public function wizard_types( $wizard_types ) {
        $wizard_types[$this->id] = [
            'porch' => $this->id,
            'label' => '24/7 Ramadan Template',
        ];

        return $wizard_types;
    }
}
( new DT_Ramadan_Porch_Loader() )->register_porch();
