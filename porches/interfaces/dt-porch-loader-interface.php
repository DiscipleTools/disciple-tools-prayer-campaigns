<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

interface IDT_Porch_Loader {

    public function get_porch_details();

    public function load_admin(): IDT_Porch_Admin_Menu;

    /**
     * This must return the ::class of the porch_admin_menu file that implements IDT_Porch_Admin_Menu
     */
    public function require_admin(): string;

    public function load_porch();

    public function register_porch();
}
