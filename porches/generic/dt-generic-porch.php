<?php


add_filter( 'dt_register_prayer_campaign_porch', 'dt_register_generic_porch', 10, 1 );

if ( !function_exists( 'dt_register_generic_porch' ) ) {
    function dt_register_generic_porch( $porches ) {
        $porch = new DT_Generic_Porch();

        $porches[$porch->id] = $porch->get_porch_details();

        return $porches;
    }
}

if ( class_exists( 'DT_Generic_Porch' ) ) {
    return;
}

/**
 * A light weight factory class that provides all of the functionality needed for the porch
 */
class DT_Generic_Porch {

    public $id = 'generic-porch';
    public $label;

    public function __construct() {
        $this->label = __( 'Generic Porch', 'disciple-tools-prayer-campaign' );
    }

    public function get_porch_details() {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'class' => $this,
        ];
    }

}
