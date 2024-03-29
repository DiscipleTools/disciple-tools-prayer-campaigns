<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Campaign_Porch_Home extends DT_Magic_Url_Base
{
    public $magic = false;
    public $parts = false;
    public $page_title = PORCH_TITLE;
    public $root = PORCH_ROOT;
    public $type = PORCH_TYPE;
    public static $token = PORCH_TOKEN;

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        parent::__construct();

        $url = dt_get_url_path();
        $url = substr( $url, 0, strrpos( $url, '?' ) !== false ? strrpos( $url, '?' ) : strlen( $url ) ); //discard url parameters
        if ( empty( $url ) && ! dt_is_rest() ) {

            // register url and access
            add_action( 'template_redirect', [ $this, 'theme_redirect' ] );
            add_filter( 'dt_blank_access', function (){ return true;
            }, 100, 1 ); // allows non-logged in visit
            add_filter( 'dt_allow_non_login_access', function (){ return true;
            }, 100, 1 );
            add_filter( 'dt_override_header_meta', function (){ return true;
            }, 100, 1 );

            // header content
            add_filter( 'dt_blank_title', [ $this, 'page_tab_title' ] ); // adds basic title to browser tab
            add_action( 'wp_print_scripts', [ $this, 'print_scripts' ], 1500 ); // authorizes scripts
            add_action( 'wp_print_styles', [ $this, 'print_styles' ], 1500 ); // authorizes styles


            // page content
            add_action( 'dt_blank_head', [ $this, '_header' ] );
            add_action( 'dt_blank_footer', [ $this, '_footer' ] );
            add_action( 'dt_blank_body', [ $this, 'body' ] ); // body for no post key

            add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
            add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );

            add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 99 );
            require_once( 'enqueue.php' );
            add_filter( 'language_attributes', [ $this, 'dt_custom_dir_attr' ] );
        }

        if ( dt_is_rest() ) {
            require_once( 'rest.php' );
            add_filter( 'dt_allow_rest_access', [ $this, 'authorize_url' ], 10, 1 );
        }
    }
    public function wp_enqueue_scripts(){
        require_once( 'landing-enqueue.php' );
        DT_Generic_Porch_Landing_Enqueue::load_scripts();
    }

    public function dt_custom_dir_attr( $lang ){
        return dt_campaign_custom_dir_attr( $lang );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        return array_merge( [ 'jquery', 'lodash', 'lodash-core' ], DT_Generic_Porch_Landing_Enqueue::load_allowed_scripts() );
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        return [
            'porch-style-css',
            'bootstrap',
            'main-styles',
            'font-awesome',
            'line-icons',
            'animate-css',
            'menu_sideslide',
            'responsive',
            'p4m-colors',
        ];
    }

    public function header_javascript(){
        require_once( 'header.php' );
    }

    public function footer_javascript(){
        require_once( 'footer.php' );
    }

    public function body(){
        DT_Generic_Porch::instance()->require_once( 'top-section.php' );
        DT_Generic_Porch::instance()->require_once( 'home-body.php' );
    }
}
DT_Campaign_Porch_Home::instance();
