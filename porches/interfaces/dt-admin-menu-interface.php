<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

interface IDT_Porch_Admin_Menu {

    public function __construct( string $porch_dir );

    public function tab_headers( string $link );

    public function tab_content();

    public function get_porch_dir();
}
