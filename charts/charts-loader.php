<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Prayer_Subscription_Charts
{
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct(){

        require_once( 'one-page-chart-template.php' );
        new DT_Prayer_Subscription_Chart_Template();

        /**
         * @todo add other charts like the pattern above here
         */

    } // End __construct
}
DT_Prayer_Subscription_Charts::instance();
