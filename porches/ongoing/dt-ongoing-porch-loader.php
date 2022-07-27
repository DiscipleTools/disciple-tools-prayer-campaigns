<?php

add_filter( 'dt_register_prayer_campaign_porch', 'dt_register_ongoing_porch' );

if ( !function_exists( 'dt_register_ongoing_porch' ) ) {
    function dt_register_ongoing_porch( $porches ) {
        $porch = new DT_Ongoing_Porch_Loader();
        $porches[$porch->id] = $porch->get_porch_details();
        return $porches;
    }
}

class DT_Ongoing_Porch_Loader extends DT_Generic_Porch_Loader {

    public $id = 'ongoing-porch';

    public function __construct() {
        parent::__construct( __DIR__ );

        $this->label = __( 'Ongoing Porch', 'disciple-tools-prayer-campaign' );
    }
}
