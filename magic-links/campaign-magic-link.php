<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class DT_Prayer_Campaign_Magic_Link extends DT_Magic_Url_Base {

    public $module = 'campaigns_ongoing_prayer';
    public $post_type = 'campaigns';
    public $page_title = 'Landing Page';

    public $magic = false;
    public $parts = false;
    public $root = 'campaign_app';
    public $type = ''; // define the type
    public $type_name = 'Campaigns';

    public $show_app_tile = false; // enables addition to "app" tile sharing features


    public function __construct(){
        parent::__construct();

        $url = dt_get_url_path( true );
        if ( dt_is_rest() || empty( $url ) ){
            return;
        }
        $url_parts = explode( '/', $url );
        //find post by name
        $post = get_page_by_path( $url_parts[0], ARRAY_A, 'campaigns' );
        if ( !$post ) {
            return;
        }
        define( 'CAMPAIGN_ID', $post['ID'] );
        $record = DT_Posts::get_post( 'campaigns', $post['ID'] );
        $this->root = $record['name'];
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
        add_action( 'template_redirect', [ $this, 'theme_redirect' ] );

        add_filter( 'dt_blank_access', function (){ return true;
        }, 100, 1 ); // allows non-logged in visit

        add_filter( 'dt_blank_title', [ $this, 'page_tab_title' ] ); // adds basic title to browser tab
        add_action( 'wp_print_scripts', [ $this, 'print_scripts' ], 1500 ); // authorizes scripts
        add_action( 'wp_print_styles', [ $this, 'print_styles' ], 1500 ); // authorizes styles

        add_action( 'dt_blank_body', [ $this, 'dt_blank_body' ], 10 );
        add_action( 'dt_blank_head', [ $this, '_header' ] );
        add_action( 'dt_blank_footer', [ $this, '_footer' ] );

        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );

        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 99 );
        require_once( DT_Prayer_Campaigns::get_dir_path() . 'porches/generic/site/enqueue.php' );
        add_filter( 'language_attributes', [ $this, 'dt_custom_dir_attr' ] );
    }


    public function dt_blank_body(){
        DT_Generic_Porch::instance()->require_once( 'top-section.php' );
        DT_Generic_Porch::instance()->require_once( 'home-body.php' );
    }
    public function header_javascript(){
        require_once( DT_Prayer_Campaigns::get_dir_path() . 'porches/generic/site/header.php' );
    }

    public function footer_javascript(){
        require_once( DT_Prayer_Campaigns::get_dir_path() . 'porches/generic/site/footer.php' );
    }

    public function dt_custom_dir_attr( $lang ){
        return dt_campaign_custom_dir_attr( $lang );
    }
    public function wp_enqueue_scripts(){
        require_once( DT_Prayer_Campaigns::get_dir_path() . 'porches/generic/site/landing-enqueue.php' );
        DT_Generic_Porch_Landing_Enqueue::load_scripts();
    }
    // add dt_campaign_core to allowed scripts
    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        return array_merge( [ 'jquery', 'lodash', 'lodash-core' ], DT_Generic_Porch_Landing_Enqueue::load_allowed_scripts() );

    }
    // add dt_campaign_core to allowed scripts
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
    public function add_api_routes(){
        $namespace = $this->root . '/v1';

    }


}
new DT_Prayer_Campaign_Magic_Link();
