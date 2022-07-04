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

        require_once __DIR__ . '/dt-generic-porch.php';
    }

    public function get_porch_details() {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'class' => $this,
        ];
    }

    public function load_admin(): IDT_Porch_Admin_Menu {
        require_once __DIR__ . '/admin/dt-generic-porch-admin-menu.php';

        $this->load_functions();
        $this->load_porch_settings();

        $plugin_dir = plugin_dir_path( __FILE__ );

        return new DT_Generic_Porch_Admin_Menu( $plugin_dir );
    }

    private function load_functions() {
        require_once __DIR__ . '/site/functions.php';
    }

    public function load_porch() {
        $this->load_porch_settings();

        DT_Generic_Porch::instance();
    }

    public function load_porch_settings() {
        require_once __DIR__ . '/dt-generic-porch-settings.php';

        new DT_Generic_Porch_Settings();

        DT_Generic_Porch::define_porch_constants();
    }

}
