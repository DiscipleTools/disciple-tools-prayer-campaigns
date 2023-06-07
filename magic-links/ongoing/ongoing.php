<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class DT_Prayer_Campaign_Ongoing_Magic_Link extends DT_Magic_Url_Base {

    public $module = 'campaigns_ongoing_prayer';
    public $post_type = 'campaigns';
    public $page_title = 'Ongoing Coverage';

    public $magic = false;
    public $parts = false;
    public $root = 'campaign_app';
    public $type = 'ongoing'; // define the type
    public $type_name = 'Campaigns';
    public $type_actions = [
        '' => 'Manage',
        'access_account' => 'Access Account',
        'shortcode' => 'Shortcode View'
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
                    'methods' => 'GET',
                    'callback' => [ $this, 'campaign_info' ],
                    'permission_callback' => function ( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'.$this->type, [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'create_subscription' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );

        register_rest_route(
            $namespace, '/'. $this->type . '/verify', [
                [
                    'methods' => 'POST',
                    'callback' => [ $this, 'verify_email_with_code' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ]
            ]
        );
    }

    public function verify_email_with_code( WP_REST_Request $request ){
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );
        $post_id = $params['parts']['post_id']; //has been verified in verify_rest_endpoint_permissions_on_post()

        if ( !$post_id || !isset( $params['campaign_id'] ) || (int) $post_id !== (int) $params['campaign_id'] ){
            return new WP_Error( __METHOD__, 'Missing post record', [ 'status' => 400 ] );
        }
        // create
        if ( ! isset( $params['email'] ) || empty( $params['email'] ) ) {
            return new WP_Error( __METHOD__, 'Missing email', [ 'status' => 400 ] );
        }
        $email = sanitize_email( $params['email'] );

        //generate_verify_code
        $six_digit_code = random_int( 100000, 999999 );

        set_transient( 'campaign_verify_' . $email, $six_digit_code, 10 * MINUTE_IN_SECONDS );

        $sent = DT_Prayer_Campaigns_Send_Email::send_verification( $email, $six_digit_code );
        return $sent; // true on success
    }

    public function campaign_info( WP_REST_Request $request ){
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );
        $post_id = $params['parts']['post_id']; //has been verified in verify_rest_endpoint_permissions_on_post()


        $record = DT_Posts::get_post( 'campaigns', $post_id, true, false );
        if ( is_wp_error( $record ) ){
            return;
        }


        $current_commitments = DT_Time_Utilities::get_current_commitments( $post_id, 3 );
        $start = (int) DT_Time_Utilities::start_of_campaign_with_timezone( $post_id );
        $min_time_duration = DT_Time_Utilities::campaign_min_prayer_duration( $post_id );
        $field_settings = DT_Posts::get_post_field_settings( 'campaigns' );

        return [
            'current_commitments' => $current_commitments,
            'start_timestamp' => $start,
            'end_timestamp' => (int) DT_Time_Utilities::end_of_campaign_with_timezone( $post_id, 3, time() ),
            'slot_length' => (int) $min_time_duration,
            'duration_options' => $field_settings['duration_options']['default'],
            'status' => $record['status']['key'],
            'campaign_id' => $post_id,
        ];
    }

    public function create_subscription( WP_REST_Request $request ) {
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );
        $post_id = $params['parts']['post_id']; //has been verified in verify_rest_endpoint_permissions_on_post()

        if ( empty( $post_id ) ){
            return new WP_Error( __METHOD__, 'Missing post record', [ 'status' => 400 ] );
        }

        // create
        if ( ! isset( $params['email'] ) || empty( $params['email'] ) ) {
            return new WP_Error( __METHOD__, 'Missing email', [ 'status' => 400 ] );
        }
        if ( ! isset( $params['selected_times'] ) ) {
            return new WP_Error( __METHOD__, 'Missing times and locations', [ 'status' => 400 ] );
        }
        if ( ! isset( $params['timezone'] ) || empty( $params['timezone'] ) ) {
            return new WP_Error( __METHOD__, 'Missing timezone', [ 'status' => 400 ] );
        }
        if ( ! isset( $params['code'] ) || empty( $params['code'] ) ) {
            return new WP_Error( __METHOD__, 'Missing code', [ 'status' => 400 ] );
        }
        $code = $params['code'];
        $email = sanitize_email( $params['email'] );
        $code_to_match = get_transient( 'campaign_verify_' . $email );
        if ( $code !== $code_to_match ) {
            return new WP_Error( __METHOD__, 'Invalid code', [ 'status' => 401 ] );
        }

        $email = $params['email'];
        $title = $params['name'];
        if ( empty( $title ) ) {
            $title = $email;
        }
        if ( isset( $params['p4m_news'] ) && !empty( $params['p4m_news'] ) ){
            p4m_subscribe_to_news( $params['email'], $title );
        }

        $existing_posts = DT_Posts::list_posts( 'subscriptions', [
            'campaigns' => [ $post_id ],
            'contact_email' => [ $email ]
        ], false );

        if ( (int) $existing_posts['total'] === 1 ){
            $subscriber_id = $existing_posts['posts'][0]['ID'];
            $added_times = DT_Subscriptions::add_subscriber_times( $post_id, $subscriber_id, $params['selected_times'] );
            if ( is_wp_error( $added_times ) ){
                return $added_times;
            }
        } else {
            $lang = 'en_US';
            if ( isset( $params['parts']['lang'] ) ){
                $lang = $params['parts']['lang'];
            }
            $subscriber_id = DT_Subscriptions::create_subscriber( $post_id, $email, $title, $params['selected_times'], [
                'receive_prayer_time_notifications' => true,
                'timezone' => $params['timezone'],
                'lang' => $lang,
            ]);
            if ( is_wp_error( $subscriber_id ) ){
                return new WP_Error( __METHOD__, 'Could not create record', [ 'status' => 400 ] );
            }
        }

        $email_sent = null;
        if ( !empty( $params['selected_times'] ) ){
            $email_sent = DT_Prayer_Campaigns_Send_Email::send_registration( $subscriber_id, $post_id );
        }

        if ( !$email_sent ){
            return new WP_Error( __METHOD__, 'Could not send email confirmation', [ 'status' => 400 ] );
        }

        return true;
    }

}
new DT_Prayer_Campaign_Ongoing_Magic_Link();
