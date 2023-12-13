<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( class_exists( 'DT_Generic_Porch_Loader' ) ) {
    return;
}

/**
 * A light weight factory class that provides all of the functionality needed for the porch
 */
class DT_Generic_Porch_Loader implements IDT_Porch_Loader {

    /**
     * Child porches must overwrite this with their own unique porch id
     */
    public $id = 'generic-porch';
    /**
     * Child porches must overwrite this in their own constructor
     */
    public $label;
    private $porch_dir;

    /**
     * Child porches need to provide the directory path for themselves
     * so that the generic porch can find any files that it might need to
     * override it's own.
     */
    public function __construct( $porch_dir = '' ) {
        $this->label = 'Generic Landing Page';
        $this->porch_dir = $porch_dir;

        require_once __DIR__ . '/dt-generic-porch.php';
        add_filter( 'dt_campaigns_wizard_types', array( $this, 'dt_campaigns_wizard_types' ), 10, 1 );
    }

    public function dt_campaigns_wizard_types( $wizard_types ) {
        $default_wizards = [
            'generic' => [
                'porch' => 'generic-porch',
                'label' => '24/7 Campaign with a start and optional end date'
            ],
        ];
        return array_merge( $default_wizards, $wizard_types );
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
        $this->load_porch_settings();

        $plugin_dir = plugin_dir_path( __FILE__ );

        $admin_menu_class = $this->require_admin();
        return new $admin_menu_class( $plugin_dir );
    }

    public function require_admin(): string {
        require_once __DIR__ . '/admin/dt-generic-porch-admin-menu.php';
        return DT_Generic_Porch_Admin_Menu::class;
    }

    private function load_functions() {
        require_once __DIR__ . '/site/functions.php';
    }

    public function load_porch() {
        $this->load_porch_settings();

        DT_Generic_Porch::instance( $this->porch_dir );
    }

    /**
     * To control the translation strings that the porch uses, and
     * any other themes/colours that this porch wants to provide
     * extend or overload this function.
     */
    public function load_porch_settings() {
        require_once __DIR__ . '/dt-generic-porch-settings.php';

        new DT_Generic_Porch_Settings();
    }

    public function register_porch() {
        add_filter( 'dt_register_prayer_campaign_porch', array( $this, 'dt_register_porch' ), 10, 1 );
    }

    public function dt_register_porch( $porches ) {
        $porches[$this->id] = $this->get_porch_details();

        return $porches;
    }
}
( new DT_Generic_Porch_Loader() )->register_porch();
