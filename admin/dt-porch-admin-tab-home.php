<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

class DT_Porch_Admin_Tab_Home extends DT_Porch_Admin_Tab_Base {

    public $title = 'Porch Settings';

    public $key = 'settings';

    public function __construct( string $porch_dir ) {
        parent::__construct( $this->key, $porch_dir );
    }
}
