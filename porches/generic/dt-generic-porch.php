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
class DT_Generic_Porch implements DT_Porch_Interface {

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

    public function load_admin(): DT_Porch_Admin_Menu_Interface {
        $this->load_porch();

        require_once __DIR__ . '/admin/admin-menu-and-tabs.php';

        return new DT_Ramadan_Porch_Landing_Menu();
    }

    public function load_porch() {
        require_once __DIR__ . '/site/functions.php';
    }

}
