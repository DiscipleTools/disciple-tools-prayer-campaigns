<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.


class P4_Ramadan_Porch_Landing extends DT_Magic_Url_Base
{
    public $page_title = PORCH_LANDING_POST_TYPE_SINGLE;
    public $root = PORCH_LANDING_ROOT;
    public $type = PORCH_LANDING_TYPE;
    public $post_type = PORCH_LANDING_POST_TYPE;
    public $meta_key = PORCH_LANDING_META_KEY;

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        parent::__construct();

        /**
         * tests if other URL
         */
        $url = dt_get_url_path();
        $length = strlen( $this->root . '/' . $this->type );
        if ( substr( $url, 0, $length ) !== $this->root . '/' . $this->type ) {
            return;
        }
        /**
         * tests magic link parts are registered and have valid elements
         */
        if ( !$this->check_parts_match( false ) ){
            return;
        }

        // load if valid url
        add_action( 'dt_blank_body', [ $this, 'body' ] ); // body for no post key

        require_once( 'landing-enqueue.php' );
        require_once( 'enqueue.php' );
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 99 );
        add_filter( 'language_attributes', [ $this, 'dt_custom_dir_attr' ] );
    }

    public function dt_custom_dir_attr( $lang ){
        return ramadan_custom_dir_attr( $lang );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        return P4_Ramadan_Porch_Landing_Enqueue::load_allowed_scripts();
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        return P4_Ramadan_Porch_Landing_Enqueue::load_allowed_styles();
    }

    public function wp_enqueue_scripts() {
        P4_Ramadan_Porch_Landing_Enqueue::load_scripts();
    }

    public function body(){
        require_once( 'top-section.php' );
        require_once( 'landing-body.php' );
    }

    public function footer_javascript(){
        require_once( 'footer.php' );
    }

    public function header_javascript(){
        require_once( 'header.php' );
    }
}
P4_Ramadan_Porch_Landing::instance();
