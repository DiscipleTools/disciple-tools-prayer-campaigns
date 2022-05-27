<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class DT_Prayer_Campaign_Ongoing_Magic_Link extends DT_Magic_Url_Base {

    public $module = "campaigns_ongoing_prayer";
    public $post_type = 'campaigns';
    public $page_title = "Sign up to pray";

    public $magic = false;
    public $parts = false;
    public $root = "campaign_app";
    public $type = 'ongoing'; // define the type
    public $type_name = "Campaigns";
    public $type_actions = [
        '' => "Manage",
        'access_account' => 'Access Account',
        'shortcode' => "Shortcode View"
    ];
    public $show_app_tile = true;

    public function __construct(){
        parent::__construct();
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
        if ( !$this->check_parts_match() ){
            return;
        }

        add_action( 'dt_blank_body', [ $this, 'dt_blank_body' ], 10 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        require_once( 'shortcode-display.php' );
    }

    public function dt_blank_body(){
        $disp = new DT_Campaigns_Ongoing_Shortcode_Display( $this->parts );
        $disp->body();
    }

    // add dt_campaign_core to allowed scripts
    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js[] = 'dt_campaign_core';
        $allowed_js[] = 'dt_campaign';
        $allowed_js[] = 'luxon';
        return $allowed_js;
    }
    // add dt_campaign_core to allowed scripts
    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        $allowed_css[] = 'dt_campaign_style';
        return $allowed_css;
    }
    public function add_api_routes(){
        $namespace = $this->root . '/v1';

        register_rest_route(
            $namespace, '/' . $this->type . '/campaign_info', [
                [
                    'methods' => "GET",
                    'callback' => [ $this, 'campaign_info' ],
                    'permission_callback' => function ( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
    }
    public function campaign_info( WP_REST_Request $request ){
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );
        $post_id = $params["parts"]["post_id"]; //has been verified in verify_rest_endpoint_permissions_on_post()


        $record = DT_Posts::get_post( "campaigns", $post_id, true, false );
        if ( is_wp_error( $record ) ){
            return;
        }


        //@todo limit
//        $coverage_levels = DT_Campaigns_Base::query_coverage_levels_progress( $post_id, 2 );

        $current_commitments = DT_Time_Utilities::get_current_commitments( $post_id, 2 );
        $start = (int) DT_Time_Utilities::start_of_campaign_with_timezone( $post_id );
        return [
//            "coverage_levels" => $coverage_levels,
            "current_commitments" => $current_commitments,
            'start_timestamp' => $start,
            'end_timestamp' => (int) DT_Time_Utilities::end_of_campaign_with_timezone( $post_id, 2, $start ),
        ];
    }

}
new DT_Prayer_Campaign_Ongoing_Magic_Link();
