<?php

class DT_Ongoing_Porch_Loader extends DT_Generic_Porch_Loader {

    public $id = 'ongoing-porch';

    public function __construct() {
        parent::__construct( __DIR__ );

        $this->label = __( 'Ongoing Porch', 'disciple-tools-prayer-campaign' );
    }
}
( new DT_Ongoing_Porch_Loader() )->register_porch();
