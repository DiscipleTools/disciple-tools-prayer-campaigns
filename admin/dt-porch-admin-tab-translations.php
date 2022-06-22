<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

class DT_Porch_Admin_Tab_Translations extends DT_Porch_Admin_Tab_Base {

    public $title = 'Content';

    public $key = 'translations';

    public function __construct( string $porch_dir ) {
        parent::__construct( $this->key, $porch_dir );
    }
}
