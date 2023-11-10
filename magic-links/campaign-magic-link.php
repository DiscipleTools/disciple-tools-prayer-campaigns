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

    public $pages = [ 'list', 'fuel', 'stats' ];
    public $current_page = '';

    public function __construct(){
        $url = dt_get_url_path( true );
        if ( dt_is_rest() ){
            add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
//            return;
        }
        $url_parts = explode( '/', $url );
        if ( empty( $url_parts[0] ) || in_array( $url_parts[0], $this->pages, true ) ){
            $selected_campaign_id = get_option( 'dt_campaign_selected_campaign', false );
            define( 'CAMPAIGN_ID', $selected_campaign_id );
            define( 'LANDING_URL', '' );
            $this->current_page = $url_parts[0];
        } else if ( !isset( $url_parts[1] ) || in_array( $url_parts[1], $this->pages, true ) ){
            //find post by name
            global $wpdb;
            $selected_campaign_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'campaign_url' AND meta_value = %s", $url_parts[0] ) );
            if ( empty( $selected_campaign_id ) ) {
                return;
            }
            define( 'CAMPAIGN_ID', $selected_campaign_id );
            define( 'LANDING_URL', $url_parts[0] );
            $this->root = $url_parts[0];
            $this->current_page = isset( $url_parts[1] ) ? $url_parts[1] : '';
        } else {
            return;
        }
        parent::__construct();

        $record = DT_Posts::get_post( 'campaigns', $selected_campaign_id );

        add_action( 'template_redirect', [ $this, 'theme_redirect' ] );
        add_filter( 'dt_templates_for_urls', [ $this, 'register_url' ], 199, 1 ); // registers url as valid once tests are passed

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


        if ( $this->current_page === 'stats' ){
            require_once DT_Prayer_Campaigns::get_dir_path() . 'porches/generic/site/stats.php';
        }
    }

    public function register_url( $template_for_url ){
        $url = dt_get_url_path( true );
        $url_parts = explode( '/', $url );
        $template_for_url[join( '/', $url_parts )] = 'template-blank.php';
        return $template_for_url;
    }


    public function dt_blank_body(){
        DT_Generic_Porch::instance()->require_once( 'top-section.php' );

        if ( $this->current_page === '' ){
            DT_Generic_Porch::instance()->require_once( 'home-body.php' );
        } elseif ( $this->current_page === 'list' ){
            DT_Generic_Porch::instance()->require_once( 'archive-body.php' );
            DT_Generic_Porch::instance()->require_once( 'post-list-body.php' );
        } elseif ( $this->current_page === 'fuel' ){
            DT_Generic_Porch::instance()->require_once( 'landing-body.php' );
            DT_Generic_Porch::instance()->require_once( 'post-list-body.php' );
        }
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
        register_rest_route(
            $namespace, 'group-count', [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'record_group_count' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
    }
    public function record_group_count( WP_REST_Request $request ){
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );
        if ( !isset( $params['number'] ) ){
            return false;
        }
        $campaign = DT_Campaign_Settings::get_campaign( $params['campaign_id'] );
        if ( empty( $campaign ) ){
            return false;
        }
        $campaign_id = $campaign['ID'];
        $args = [
            'parent_id' => $campaign_id,
            'post_id' => 0,
            'post_type' => 'campaigns',
            'type' => 'fuel',
            'subtype' => $campaign['type']['key'],
            'payload' => null,
            'value' => $params['number'],
        ];
        Disciple_Tools_Reports::insert( $args, true, false );

        return $params['number'];
    }


}
new DT_Prayer_Campaign_Magic_Link();
