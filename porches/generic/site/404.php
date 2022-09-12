<?php

/* Catch any routes beginning with PORCH_LANDING_ROOT that don't match the registered urls */

class DT_Generic_Porch_404 extends DT_Magic_Url_Base {
    public $page_title = '404';
    public $root = PORCH_LANDING_ROOT;

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
        $length = strlen( $this->root );
        if ( substr( $url, 0, $length ) !== $this->root ) {
            return;
        }

        /* check existing routes for a url with the root in */
        $templates_for_urls = apply_filters( 'dt_templates_for_urls', [] );

        $root_url_registered = false;
        foreach ( $templates_for_urls as $url => $template ) {
            if ( substr( $url, 0, $length ) !== $this->root ) {
                continue;
            }
            $root_url_registered = true;
        }

        if ( $root_url_registered ) {
            return;
        }

        /* if there are no urls with the root in, then another magic link didn't run */
        /* therefore we are in a 404 zone and can show the 404 */

        /* register this url with the 404 template */
        add_filter( 'dt_templates_for_urls', [ $this, 'register_url' ] );
        add_filter( 'dt_blank_access', function() {
            return true;
        });
        add_action( 'dt_blank_head', [ $this, '_header' ] );
        add_action( 'dt_blank_footer', [ $this, '_footer' ] );
        add_action( 'dt_blank_body', [ $this, 'body' ] );

        require_once( 'landing-enqueue.php' );

        require_once( 'enqueue.php' );

        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 99 );
        add_filter( 'language_attributes', [ $this, 'dt_custom_dir_attr' ] );
    }

    public function dt_custom_dir_attr( $lang ){
        return dt_campaign_custom_dir_attr( $lang );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        return DT_Generic_Porch_Landing_Enqueue::load_allowed_scripts();
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        return DT_Generic_Porch_Landing_Enqueue::load_allowed_styles();
    }

    public function wp_enqueue_scripts() {
        DT_Generic_Porch_Landing_Enqueue::load_scripts();
    }

    public function register_url( $templates_for_urls ) {
        $url = dt_get_url_path();

        $templates_for_urls[$url] = 'template-blank.php';

        return $templates_for_urls;
    }

    public function body() {
        DT_Generic_Porch::instance()->require_once( 'top-section.php' );
        require_once( '404-body.php' );
    }

    public function footer_javascript(){
        require_once( 'footer.php' );
    }

    public function header_javascript(){
        require_once( 'header.php' );
    }
}

new DT_Generic_Porch_404();
