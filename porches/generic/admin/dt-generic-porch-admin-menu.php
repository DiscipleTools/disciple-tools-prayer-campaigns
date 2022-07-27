<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Generic_Porch_Admin_Menu
 */
class DT_Generic_Porch_Admin_Menu implements IDT_Porch_Admin_Menu {

    public $token = 'dt_porch_generic';
    public $title = 'Settings';

    private $porch_dir;

    public function __construct( string $porch_dir ) {
        if ( ! is_admin() ) {
            return;
        }

        $this->porch_dir = $porch_dir;

        add_action( "admin_menu", array( $this, "register_menu" ) );

    }

    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {}

    /**
     * Builds porch tab links
     * @since 0.1
     */
    public function tab_headers( string $link ) {

        $tab = $this->get_tab();

        ?>

        <!-- Put tab header links here -->

        <?php
    }

    public function tab_content() {

        $tab = $this->get_tab();

        switch ( $tab ){
            default:
                break;
        }
    }

    public function get_porch_dir() {
        return $this->porch_dir;
    }

    private function get_tab(): string {
        if ( isset( $_GET["tab"] ) ) {
            return sanitize_key( wp_unslash( $_GET["tab"] ) );
        }

        return "";
    }
}
