<?php


add_filter( 'dt_register_prayer_campaign_porch', 'dt_register_generic_porch', 10, 1 );

if ( !function_exists( 'dt_register_generic_porch' ) ) {
    function dt_register_generic_porch( $porches ) {
        $porch = new DT_Generic_Porch_Loader();

        $porches[$porch->id] = $porch->get_porch_details();

        return $porches;
    }
}

if ( class_exists( 'DT_Generic_Porch_Loader' ) ) {
    return;
}

/**
 * A light weight factory class that provides all of the functionality needed for the porch
 */
class DT_Generic_Porch_Loader implements IDT_Porch_Loader {

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

    public function load_admin(): IDT_Porch_Admin_Menu {
        $this->load_functions();

        require_once __DIR__ . '/admin/dt-generic-porch-admin-menu.php';

        return new DT_Generic_Porch_Admin_Menu();
    }

    private function load_functions() {
        /* The functions that are needed in here (recursive and the other one) are more of a campaigns level functions */
        /* TODO move them up into the plugin level functions file */
        require_once __DIR__ . '/site/functions.php';
    }

    public function load_porch() {
        require_once __DIR__ . '/dt-generic-porch.php';

        DT_Generic_Porch::instance();
    }

}
