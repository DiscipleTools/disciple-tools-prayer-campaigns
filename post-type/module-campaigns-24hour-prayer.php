<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Campaign_24Hour_Prayer extends DT_Module_Base {
    public $module = "campaigns_24hour_prayer";
    public $post_type = 'campaigns';
    public $magic_link_root = "campaign_app";
    public $magic_link_type = "24hour";

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        $module_enabled = dt_is_module_enabled( "subscriptions_management", true );
        if ( !$module_enabled ){
            return;
        }
        parent::__construct();
        // register tiles if on details page
        add_filter( 'dt_campaign_types', [ $this, 'dt_campaign_types' ], 20, 1 );
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 30, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 30, 2 );

        new DT_Prayer_Campaign_24_Hour_Magic_Link();
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }


    public function dt_details_additional_tiles( $tiles, $post_type = "" ){
        if ( $post_type === 'campaigns' && ! isset( $tiles["apps"] ) ){
            $tiles["apps"] = [ "label" => __( "Campaign Subscription Form Links", 'disciple-tools-campaigns' ) ];
        }
        return $tiles;
    }

    public function dt_details_additional_section( $section, $post_type ) {
        // test if campaigns post type and campaigns_app_module enabled
        if ( $post_type === $this->post_type ) {

            if ( 'status' === $section ){
                $record = DT_Posts::get_post( $post_type, get_the_ID() );

                if ( isset( $record['type']['key'] ) && '24hour' === $record['type']['key'] ){
                    $key_name = 'public_key';
                    if ( method_exists( "DT_Magic_URL", "get_public_key_meta_key" ) ){
                        $key_name = DT_Magic_URL::get_public_key_meta_key( "campaign_app", "24hour" );
                    }
                    if ( isset( $record[$key_name] ) ){
                        $key = $record[$key_name];
                    } else {
                        $key = dt_create_unique_key();
                        update_post_meta( get_the_ID(), $key_name, $key );
                    }
                    $link = trailingslashit( site_url() ) . $this->magic_link_root . '/' . $this->magic_link_type . '/' . $key;
                    ?>
                    <div class="cell small-12 medium-4">
                        <div class="section-subheader">
                            <?php esc_html_e( 'Magic Link', 'disciple_tools' ); ?>
                        </div>
                        <a class="button hollow small" target="_blank" href="<?php echo esc_html( $link ); ?>"><?php esc_html_e( 'Open Link', 'disciple_tools' ); ?></a>
                    </div>
                    <?php
                }
            }

            if ( 'apps' === $section ) {
                $record = DT_Posts::get_post( $post_type, get_the_ID() );

                if ( isset( $record['type']['key'] ) && '24hour' === $record['type']['key'] ) {
                    $key_name = 'public_key';
                    if ( method_exists( "DT_Magic_URL", "get_public_key_meta_key" ) ){
                        $key_name = DT_Magic_URL::get_public_key_meta_key( "campaign_app", "24hour" );
                    }
                    if ( isset( $record[$key_name] )) {
                        $key = $record[$key_name];
                    } else {
                        $key = dt_create_unique_key();
                        update_post_meta( get_the_ID(), $key_name, $key );
                    }
                    if ( !isset( $record["start_date"]["timestamp"], $record["end_date"]["timestamp"] ) ){
                        ?>
                        <p>A Start Date and End Date are required for the 24 hour campaign</p>
                        <?php
                        return;
                    }
                    $link = trailingslashit( site_url() ) . $this->magic_link_root . '/' . $this->magic_link_type . '/' . $key;
                    $shortcode = '[dt_campaign root="' . esc_html( $this->magic_link_root ) . '" type="' . esc_html( $this->magic_link_type ) . '" public_key="' . esc_html( $key ) . '" meta_key="' . esc_html( $key_name ) . '" post_id="' . esc_html( get_the_ID() ) . '" rest_url="' . esc_html( rest_url() ) . '"]';
                    ?>
                    <div class="cell">
                        <div class="section-subheader">
                            <?php esc_html_e( '24hour Circles Prayer', 'disciple_tools' ); ?>
                        </div>
                        <a class="button hollow small" onclick="copyToClipboard('<?php echo esc_html( $shortcode ) ?>')"><?php esc_html_e( 'Copy Shortcode', 'disciple_tools' ); ?></a>
                        <a class="button hollow small" onclick="copyToClipboard('<?php echo esc_url( $link ) ?>')"><?php esc_html_e( 'Copy Link', 'disciple_tools' ); ?></a>
                        <a class="button hollow small" onclick="open_app('<?php echo esc_url( $link ) ?>')"><?php esc_html_e( 'Open', 'disciple_tools' ); ?></a>
                        <div class="section-subheader">
                            <?php esc_html_e( 'Shortcode', 'disciple_tools' ); ?>
                        </div>
                        <pre><?php echo esc_html( $shortcode ) ?></pre>
                    </div>

                    <script>
                        const copyToClipboard = str => {
                            const el = document.createElement('textarea');
                            el.value = str;
                            el.setAttribute('readonly', '');
                            el.style.position = 'absolute';
                            el.style.left = '-9999px';
                            document.body.appendChild(el);
                            const selected =
                                document.getSelection().rangeCount > 0
                                    ? document.getSelection().getRangeAt(0)
                                    : false;
                            el.select();
                            document.execCommand('copy');
                            document.body.removeChild(el);
                            if (selected) {
                                document.getSelection().removeAllRanges();
                                document.getSelection().addRange(selected);
                            }
                            alert('Copied')
                        };

                        function open_app(){
                            jQuery('#modal-large-content').empty().html(`
                            <div class="iframe_container">
                                <span id="campaign-spinner" class="loading-spinner active"></span>
                                <iframe id="campaign-iframe" src="<?php echo esc_url( $link ) ?>" width="100%" height="${window.innerHeight -150}px" style="border:none;">Your browser does not support iframes</iframe>
                            </div>
                            <style>
                            .iframe_container {
                                position: relative;
                            }
                            .iframe_container .loading-spinner {
                                position: absolute;
                                top: 10%;
                                left: 50%;
                            }
                            .iframe_container iframe {
                                background: transparent;
                                z-index: 1;
                            }
                            </style>
                            `)
                            jQuery('#campaign-iframe').on('load', function() {
                                document.getElementById('campaign-spinner').style.display='none';
                            });
                            jQuery('#modal-large').foundation('open')
                        }
                    </script>
                    <?php
                } // end if 24hour prayer
            } // end if apps section
        } // end if campaigns and enabled
    }

    /**
     * Register REST Endpoints
     * @link https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
     */
    public function add_api_routes() {
        $namespace = $this->magic_link_root . '/v1';
        register_rest_route(
            $namespace, '/'.$this->magic_link_type, [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'create_subscription' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'.$this->magic_link_type . '/access_account', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'access_account' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'.$this->magic_link_type . '/campaign_info', [
                [
                    'methods'  => "GET",
                    'callback' => [ $this, 'campaign_info' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->magic_link_root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );

    }

    public function verify_magic_link_and_get_post_id( $meta_key, $public_key ){
        $magic = new DT_Magic_URL( $this->magic_link_root );
        $parts = $magic->parse_wp_rest_url_parts( $public_key );
        if ( !$parts || $this->magic_link_type != $parts["type"] ){
            return false;
        }
        if ( isset( $parts["post_id"] ) && !empty( $parts["post_id"] ) ){
            return (int) $parts["post_id"];
        }
        return false;
    }

    public function create_subscription( WP_REST_Request $request ) {
        $params = $request->get_params();

        $post_id = $this->verify_magic_link_and_get_post_id( $params['parts']['meta_key'], $params['parts']['public_key'] );
        if ( !$post_id || !isset( $params["campaign_id"] ) || $post_id !== (int) $params["campaign_id"] ){
            return new WP_Error( __METHOD__, "Missing post record", [ 'status' => 400 ] );
        }

        // create
        if ( ! isset( $params['email'] ) || empty( $params['email'] ) ) {
            return new WP_Error( __METHOD__, "Missing email", [ 'status' => 400 ] );
        }
        if ( ! isset( $params['selected_times'] ) || empty( $params['selected_times'] ) ) {
            return new WP_Error( __METHOD__, "Missing times and locations", [ 'status' => 400 ] );
        }
        if ( ! isset( $params['timezone'] ) || empty( $params['timezone'] ) ) {
            return new WP_Error( __METHOD__, "Missing timezone", [ 'status' => 400 ] );
        }

        $params = dt_recursive_sanitize_array( $params );
        $email = $params['email'];
        $title = $params['name'];
        if ( empty( $title ) ) {
            $title = $email;
        }

        $receive_prayer_time_notifications = isset( $params["receive_prayer_time_notifications"] ) && !empty( $params["receive_prayer_time_notifications"] );

        $existing_posts = DT_Posts::list_posts( "subscriptions", [
            "campaigns" => [ $params["campaign_id"] ],
            "contact_email" => [ $email ]
        ], false );

        if ( (int) $existing_posts["total"] === 1 ){
            $subscriber_id = $existing_posts["posts"][0]["ID"];
            $added_times = DT_Subscriptions::add_subscriber_times( $params["campaign_id"], $subscriber_id, $params['selected_times'] );
            if ( is_wp_error( $added_times ) ){
                return $added_times;
            }
        } else {
            $subscriber_id = DT_Subscriptions::create_subscriber( $params["campaign_id"], $email, $title, $params['selected_times'], [
                "receive_prayer_time_notifications" => $receive_prayer_time_notifications,
                "timezone" => $params["timezone"]
            ]);
            if ( is_wp_error( $subscriber_id ) ){
                return new WP_Error( __METHOD__, "Could not create record", [ 'status' => 400 ] );
            }
        }

        $email_sent = DT_Prayer_Campaigns_Send_Email::send_registration( $subscriber_id, $params["campaign_id"] );
        if ( !$email_sent ){
            return new WP_Error( __METHOD__, "Could not sent email confirmation", [ 'status' => 400 ] );
        }

        return true;
    }

    public function access_account( WP_REST_Request $request ) {
        $params = $request->get_params();

        $post_id = $this->verify_magic_link_and_get_post_id( $params['parts']['meta_key'], $params['parts']['public_key'] );
        if ( !$post_id || !isset( $params["campaign_id"] ) || $post_id !== $params["campaign_id"] ){
            return new WP_Error( __METHOD__, "Missing post record", [ 'status' => 400 ] );
        }

        // @todo insert email reset link
        $params = dt_recursive_sanitize_array( $params );
        if ( ! isset( $params['email'], $params['campaign_id'] ) ) {
            return new WP_Error( __METHOD__, "Missing required parameter.", [ 'status' => 400 ] );
        }

        DT_Prayer_Campaigns_Send_Email::send_account_access( $params['campaign_id'], $params['email'] );

        return $params;
    }

    public function campaign_info( WP_REST_Request $request ){
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );
        $magic = new DT_Magic_URL( $this->magic_link_root );
        $parts = $magic->parse_wp_rest_url_parts( $params["parts"]["public_key"] );

        $post_id = $parts['post_id'];
        $record = DT_Posts::get_post( "campaigns", $post_id, true, false );
        if ( is_wp_error( $record ) ){
            return;
        }
        $coverage_levels = DT_Campaigns_Base::query_coverage_levels_progress( $post_id );
        $number_of_time_slots = DT_Campaigns_Base::query_coverage_total_time_slots( $post_id );

        $coverage_percentage = $coverage_levels[0]["percent"];
        $second_level = isset( $coverage_levels[1]["percent"] ) ? $coverage_levels[1]["percent"] : "";

        $description = "Campaign Description";
        if ( isset( $record["description"] ) && !empty( $record["description"] ) ){
            $description = $record["description"];
        }
        $grid_id = 1;
        if ( isset( $record['location_grid'] ) && ! empty( $record['location_grid'] ) ) {
            $grid_id = $record['location_grid'][0]['id'];
        }
        $current_commitments = DT_Time_Utilities::subscribed_times_list( $post_id );

        return [
            "description" => $description,
            "coverage_levels" => $coverage_levels,
            "number_of_time_slots" => $number_of_time_slots,
            "coverage_percentage" => $coverage_percentage,
            'campaign_id' => $post_id,
            'campaign_grid_id' => $grid_id,
            'translations' => [],
            'start_timestamp' => (int) DT_Time_Utilities::start_of_campaign_with_timezone( $post_id ),
            'end_timestamp' => (int) DT_Time_Utilities::end_of_campaign_with_timezone( $post_id ) + 86400,
            'current_commitments' => $current_commitments,
            'slot_length' => 15,
            'second_level' => $second_level
        ];

    }

    public function dt_campaign_types( $types ) {
        $types['24hour'] = [
            'label' => __( '24hr Prayer Calendar', 'disciple_tools' ),
            'description' => __( 'Cover a region with 24h prayer.', 'disciple_tools' ),
            "visibility" => __( "Collaborators", 'disciple_tools' ),
            'color' => "#4CAF50",
        ];
        return $types;
    }
}


class DT_Prayer_Campaign_24_Hour_Magic_Link extends DT_Magic_Url_Base {

    public $module = "campaigns_24hour_prayer";
    public $post_type = 'campaigns';
    public $page_title = "Sign up to pray";

    public $magic = false;
    public $parts = false;
    public $root = "campaign_app"; // define the root of the url {yoursite}/root/type/key/action
    public $type = '24hour'; // define the type
    public $type_name = "Campaigns";
    public $type_actions = [
        '' => "Manage",
        'access_account' => 'Access Account',
        'shortcode' => "Shortcode View"
    ];

    public function __construct(){
        parent::__construct();
        if ( !$this->check_parts_match()){
            return;
        }

        if ( 'shortcode' === $this->parts['action'] ){
            add_action( 'wp_enqueue_scripts', function (){
                dt_24hour_campaign_register_scripts([
                    "root" => $this->root,
                    "type" => $this->type,
                    "public_key" => $this->parts["public_key"],
                    "meta_key" => $this->parts["meta_key"],
                    "post_id" => $this->parts["post_id"],
                    "rest_url" => rest_url()

                ]);
            }, 100 );
            add_action( 'dt_blank_body', function (){
                dt_24hour_campaign_body();
            } );
        } else {
            if ( '' === $this->parts['action'] ) {
                add_action( 'dt_blank_body', [ $this, 'page_body' ] );
            } else if ( 'access_account' === $this->parts['action'] ) {
                add_action( 'dt_blank_body', [ $this, 'access_account_body' ] );
            } else {
                return; // fail if no valid action url found
            }

            add_action( 'dt_blank_head', [ $this, 'page_head' ] );
            add_action( 'dt_blank_footer', [ $this, 'page_footer' ] );
            add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );
        }

        // add dt_campaign_core to allowed scripts
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js[] = 'dt_campaign_core';
        $allowed_js[] = 'dt_campaign';
        return $allowed_js;
    }

    public function wp_enqueue_scripts(){
        if ( is_singular( "campaigns" ) ){
            $magic = new DT_Magic_URL( 'campaigns_app' );
            $types = $magic->list_types();
            $campaigns = $types['campaigns'] ?? [];
            $campaigns['new_key'] = dt_create_unique_key();

            wp_localize_script( // add object to campaigns-post-type.js
                'dt_campaigns', 'campaigns_campaigns_module', [
                    'campaigns' => $campaigns,
                ]
            );
        }


        wp_enqueue_script( 'dt_campaign_core', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'campaign_core.js', [
            'jquery',
            'lodash'
        ], filemtime( plugin_dir_path( __FILE__ ) . 'campaign_core.js' ), true );

        // Localize script with array data
        wp_localize_script(
            'dt_campaign_core', 'campaign_objects', [
                'translations' => [
                    "Sample API Call" => __( "Sample API Call" )
                ]
            ]
        );
    }

    public function page_head(){
        wp_head(); // styles controlled by wp_print_styles and wp_print_scripts actions
        $this->campaigns_styles_header();
    }

    public function page_footer(){
        $this->campaigns_javascript_header();
        wp_footer(); // styles controlled by wp_print_styles and wp_print_scripts actions
    }

    public function campaigns_styles_header(){
        ?>
        <style>
            body {
                background-color: white;
            }
            #wrapper {
                max-width:1000px;
                margin:0 auto;
                padding: .5em;
                background-color: white;
            }


            .day-cell {
                /*flex-basis: 14%;*/
                text-align: center;
                flex-grow: 0;

            }
            .display-day-cell {
                height: 40px;
            }
            .disabled-calendar-day {
                width:40px;
                height:40px;
                vertical-align: top;
                padding-top:10px;
                color: grey;
            }
            .calendar {
                display: flex;
                flex-wrap: wrap;
                width: 300px;
                margin: auto
            }
            .month-title {
                text-align: center;
                margin-bottom: 0;
            }
            .week-day {
                height: 20px;
                width:40px;
                color: grey;
            }
            #calendar-content h3 {
                margin-bottom: 0;
            }

            #list-day-times td, th {
                padding: 2px 6px
            }

            #email {
                display:none;
            }

            .day-in-select-calendar {
                color: black;
                display: inline-block;
                height: 40px;
                width: 40px;
                line-height: 0;
                vertical-align: middle;
                text-align: center;
                padding-top: 18px;
            }
            .selected-day {
                background-color: dodgerblue;
                color: white;
                border-radius: 50%;
                border: 2px solid;
            }

            .calendar-month-title {
                display: block;
                padding:0 10px;
            }
            .small-view .calendar-month-title {
                /*display: inline-block;*/
            }

            #modal-calendar.small-view {
                display: flex;
                width: 250px;
                flex-wrap: wrap;
                margin: auto;
            }

            .small-view .calendar {
                width: 180px;
            }
            .small-view .month-title {
                width: 50px;
                padding: 0 10px;
                overflow: hidden;
                flex-grow: 1;
            }
            .small-view .calendar .week-day {
                height: 20px;
                width: 25px;
            }
            .small-view .calendar .day-in-select-calendar {
                height: 25px;
                width: 25px;
            }
            .small-view .calendar .disabled-calendar-day {
                width: 25px;
                height: 25px;
                padding-top: 5px;
                font-size: 11px;
            }

            .small-view .day-in-select-calendar {
                height: 25px;
                width: 25px;
                padding-top: 11px;
                font-size:8px;
            }

            .confirm-view {
                display: none;
            }
            .success-confirmation-section {
                display: none;
            }

        </style>
        <?php
    }

    public function page_body(){
        $link = trailingslashit( site_url() ) . $this->parts['root'] . '/' . $this->parts['type'] . '/' . $this->parts['public_key'] . '/access_account';
        $post_id = $this->parts['post_id'];
        $record = DT_Posts::get_post( "campaigns", $post_id, true, false );
        if ( is_wp_error( $record ) ){
            return;
        }
        $coverage_levels = DT_Campaigns_Base::query_coverage_levels_progress( $post_id );
        $number_of_time_slots = DT_Campaigns_Base::query_coverage_total_time_slots( $post_id );

        $coverage_percentage = $coverage_levels[0]["percent"];
        $second_level = isset( $coverage_levels[1]["percent"] ) ? $coverage_levels[1]["percent"] : "";

        $description = "Campaign Description";
        if ( isset( $record["description"] ) && !empty( $record["description"] ) ){
            $description = $record["description"];
        }

        if ( isset( $record['type']['key'] ) && '24hour' === $record['type']['key'] ) {
            $text2 = "covered";
            $text2 = "+" . $second_level . "%";
            ?>

            <div id="wrapper">
                <div class="center">
                    <h2 ><?php echo esc_html( $description ); ?></h2>
                    <?php if ( $coverage_levels[0]["blocks_covered"] === $number_of_time_slots ) :
                        ?>
                        All of the <?php echo esc_html( $number_of_time_slots ); ?> time slots are covered in once prayer once. Help us cover them twice!
                    <?php else : ?>
                        At <strong><?php echo esc_html( $coverage_percentage ); ?>%</strong> of the <?php echo esc_html( $number_of_time_slots ); ?> time slots needed to cover the region in 24 hour prayer.
                    <?php endif; ?>
                </div>
                <div id="main-progress" class="center">
                    <progress-ring stroke="10" radius="80" font="18"
                                   progress="<?php echo esc_html( $coverage_percentage ); ?>"
                                   progress2="<?php echo esc_html( $second_level ); ?>"
                                   text="<?php echo esc_html( $coverage_percentage ); ?>%"
                                   text2="<?php echo esc_html( $text2 ); ?>">
                    </progress-ring>
                </div>
                <div class="center">
                    <button class="button" data-open="select-times-modal" id="open-select-times-button" style="margin-top: 10px">
                        Choose Prayer Times
                    </button>
                </div>

                <div id="calendar-content"></div>

                <p class="center" style="margin-top: 10px">
                    <?php esc_html_e( 'Showing times for:', 'disciple_tools' ); ?><a href="javascript:void(0)" data-open="timezone-changer" class="timezone-current"></a>
                </p>
                <div class="center">
                    <div class="" style=""><a href="<?php echo esc_url( $link ) ?>">Already have prayer times?</a></div>
                </div>

                <div class="reveal" id="view-times-modal" data-reveal data-close-on-click="true">
                    <h3 id="list-modal-title"></h3>

                    <div id="list-day-times">
                        <table>
                            <thead>
                            <tr>
                                <th></th>
                                <th>:00</th>
                                <th>:15</th>
                                <th>:30</th>
                                <th>:45</th>
                            </tr>
                            </thead>
                            <tbody id="day-times-table-body">

                            </tbody>
                        </table>
                    </div>

                    <button class="close-button" data-close aria-label="Close modal" type="button">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <div class="center">
                        <button class="button" data-close aria-label="Close reveal" type="button">
                            <?php echo esc_html__( 'Close', 'disciple_tools' )?>
                        </button>
                    </div>
                </div>


                <div class="reveal" id="select-times-modal" data-reveal data-close-on-click="false" data-multiple-opened="true">

                    <div id="modal-calendar">Modal Calendar</div>
                    <p id="calendar-select-all-selected" class="select-view center" style="margin-top: 10px">
                        <strong>All days selected.</strong>
                        <a id="unselect-all-calendar-days">unselect all</a>
                    </p>
                    <p id="calendar-select-help" class="select-view center" style="margin-top: 10px; display: none">
                        <span id="calendar-select-help-text" style="font-weight: bold"></span>
                        <a id="select-all-calendar-days">select all</a>
                    </p>

                    <label class="select-view" >
                        <strong>Prayer Time</strong>
                        <select id="daily_time_select" class="select-view">
                            <option>Daily Time</option>
                        </select>
                    </label>
                    <label class="select-view">
                        <strong>For</strong>
                        <select id="prayer-time-duration-select" class="select-view">
                            <option value="15">15 Minutes</option>
                            <option value="30">30 Minutes</option>
                            <option value="45">45 Minutes</option>
                            <option value="60">1 Hour</option>
                            <option value="90">1 Hour 30 Minutes</option>
                            <option value="120">2 Hours</option>
                        </select>
                    </label>
                    <p class="select-view">
                        <strong>
                            <?php esc_html_e( 'Using timezone for:', 'disciple_tools' ); ?></strong>
                        <a href="javascript:void(0)" data-open="timezone-changer" class="timezone-current"></a>
                    </p>

                    <div id="confirmation-section" class="confirm-view" style="margin-top:15px">
                        <p>
                            You are signing up to praying for
                            <span id="time-duration-confirmation" style="font-weight: bold" ></span>
                            on each selected day at <span id="time-confirmation" style="font-weight: bold"></span>
                            in <span class="timezone-current"></span> timezone.
                        </p>

                        <!--                        <div class="center"><h2 id="contact-information">--><?php //echo esc_html( "Contact Information" ); ?><!--</h2></div>-->
                        <div class="grid-x">
                            <div class="cell">
                        <span id="name-error" class="form-error">
                            <?php echo esc_html( "You're name is required." ); ?>
                        </span>
                                <label for="name"><?php esc_html_e( 'Contact Name', 'disciple_tools' ); ?><br>
                                    <input type="text" name="name" id="name" placeholder="Name" required/>
                                </label>
                            </div>
                            <div class="cell">
                        <span id="email-error" class="form-error">
                            <?php esc_html_e( "You're email is required.", 'disciple_tools' ); ?>
                        </span>
                                <label for="email"><?php esc_html_e( 'Contact Email', 'disciple_tools' ); ?><br>
                                    <input type="email" name="email" id="email" placeholder="Email" />
                                    <input type="email" name="e2" id="e2" placeholder="Email" required />
                                </label>
                            </div>
                        </div>


                        <div class="grid-x grid-padding-x grid-padding-y">
                            <div class="cell center">
                                <label for="receive_prayer_time_notifications">
                                    <input type="checkbox" id="receive_prayer_time_notifications" name="receive_prayer_time_notifications" checked />
                                    <?php esc_html_e( 'Receive Prayer Time Notifications (email verification needed).', 'disciple_tools' ); ?>
                                </label>
                            </div>
                            <div class="cell center">
                                <span id="selection-error" class="form-error">
                                    <?php esc_html_e( 'You must select at least one time slot above.', 'disciple_tools' ); ?>
                                </span>
                                <button class="button loader" id="submit-form"><?php esc_html_e( 'Submit Your Prayer Commitment', 'disciple_tools' ); ?> <span class="loading-spinner"></span></button>
                            </div>
                        </div>
                    </div>

                    <div class="success-confirmation-section">
                        <div class="cell center">
                            <h2>Sent! Check your email.</h2>
                            <p>
                                Click on the link included in the email to <strong>verify</strong> your commitment and receive prayer time notifications!
                            </p>
                            <p>
                                In the email is a link to <strong>manage</strong> your prayer times.
                            </p>
                        </div>
                    </div>


                    <div class="center">
                        <button class="button button-cancel clear select-view" data-close aria-label="Close reveal" type="button">
                            <?php echo esc_html__( 'Cancel', 'disciple_tools' )?>
                        </button>

                        <button class="button select-view" type="button" id="confirm-daily-time" disabled>
                            <?php echo esc_html__( 'Next', 'disciple_tools' )?>
                        </button>
                    </div>
                    <button class="button button-cancel clear confirm-view" id="back-to-select" aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'back', 'disciple_tools' )?>
                    </button>

                    <button class="close-button" data-close aria-label="Close modal" type="button">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <div class="center">
                        <button class="button success-confirmation-section" data-close aria-label="Close reveal" type="button">
                            <?php echo esc_html__( 'ok', 'disciple_tools' )?>
                        </button>
                    </div>
                </div>
                <!-- Reveal Modal Timezone Changer-->
                <div id="timezone-changer" class="reveal tiny" data-reveal>
                    <h2>Change your timezone:</h2>
                    <select id="timezone-select">
                        <?php
                        $selected_tz = 'America/Denver';
                        if ( !empty( $selected_tz ) ){
                            ?>
                            <option id="selected-time-zone" value="<?php echo esc_html( $selected_tz ) ?>" selected><?php echo esc_html( $selected_tz ) ?></option>
                            <option disabled>----</option>
                            <?php
                        }
                        $tzlist = DateTimeZone::listIdentifiers( DateTimeZone::ALL );
                        foreach ( $tzlist as $tz ){
                            ?><option value="<?php echo esc_html( $tz ) ?>"><?php echo esc_html( $tz ) ?></option><?php
                        }
                        ?>
                    </select>
                    <button class="button button-cancel clear" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Cancel', 'disciple_tools' )?>
                    </button>
                    <button class="button" type="button" id="confirm-timezone" data-close>
                        <?php echo esc_html__( 'Select', 'disciple_tools' )?>
                    </button>

                    <button class="close-button" data-close aria-label="Close modal" type="button">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

            </div> <!-- form wrapper -->

            <?php
        }
    }

    public function campaigns_javascript_header(){
        $post = DT_Posts::get_post( 'campaigns', $this->parts['post_id'], true, false );
        if ( is_wp_error( $post ) ) {
            return $post;
        }
        $grid_id = 1;
        if ( isset( $post['location_grid'] ) && ! empty( $post['location_grid'] ) ) {
            $grid_id = $post['location_grid'][0]['id'];
        }
        $current_commitments = DT_Time_Utilities::subscribed_times_list( $this->parts['post_id'] );
        ?>
        <script>
            let calendar_subscribe_object = [<?php echo json_encode([
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'campaign_id' => $post['ID'],
                'campaign_grid_id' => $grid_id,
                'translations' => [],
                'start_timestamp' => (int) DT_Time_Utilities::start_of_campaign_with_timezone( $post["ID"] ),
                'end_timestamp' => (int) DT_Time_Utilities::end_of_campaign_with_timezone( $post["ID"] ) + 86400,
                'current_commitments' => $current_commitments,
                'slot_length' => 15
            ]) ?>][0]

            let current_time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'
            let selected_calendar_color = 'green'

            const number_of_days = ( calendar_subscribe_object.end_timestamp - calendar_subscribe_object.start_timestamp ) / ( 24*3600)
            let time_slot_coverage = {}

            jQuery(document).ready(function($){
                let days = window.campaign_scripts.calculate_day_times()
                let update_timezone = function (){
                    $('.timezone-current').html(current_time_zone)
                    $('#selected-time-zone').val(current_time_zone).text(current_time_zone)
                }
                update_timezone()
                //change timezone
                $('#confirm-timezone').on('click', function (){
                    current_time_zone = $("#timezone-select").val()
                    update_timezone()
                    days = window.campaign_scripts.calculate_day_times(current_time_zone)
                    draw_calendar()
                    draw_modal_calendar()
                })

                let headers = `
                    <div class="day-cell week-day">Su</div>
                    <div class="day-cell week-day">Mo</div>
                    <div class="day-cell week-day">Tu</div>
                    <div class="day-cell week-day">We</div>
                    <div class="day-cell week-day">Th</div>
                    <div class="day-cell week-day">Fr</div>
                    <div class="day-cell week-day">Sa</div>
                    `

                //display main calendar
                let draw_calendar = ( id = 'calendar-content') => {
                    let content = $(`#${window.lodash.escape(id)}`)
                    content.empty()
                    let last_month = "";
                    let list = ``
                    days.forEach(day=>{
                        if ( day.month !== last_month ){
                            if ( last_month ){
                                //add extra days at the month end
                                let day_number = new Date(window.campaign_scripts.day_start(day.key, current_time_zone) * 1000).getDay()
                                for ( let i = 1; i <= 7-day_number; i++ ){
                                    list +=  `<div class="day-cell disabled-calendar-day">${window.lodash.escape(i)}</div>`
                                }
                                list += `</div>`
                            }

                            list += `<h3 class="month-title">${window.lodash.escape(day.month)}</h3><div class="calendar">`
                            if( !last_month ){
                                list += headers
                            }

                            //add extra days at the month start
                            let day_number = new Date(window.campaign_scripts.day_start(day.key, current_time_zone) * 1000).getDay()
                            let start_of_week = new Date ( ( day.key - day_number * 86400 ) * 1000 )
                            for ( let i = 0; i < day_number; i++ ){
                                list +=  `<div class="day-cell disabled-calendar-day">${window.lodash.escape(start_of_week.getDate()+i)}</div>`
                            }
                            last_month = day.month
                        }
                        if ( day.disabled ){
                            list += `<div class="day-cell disabled-calendar-day">
                                ${day.day}
                            </div>`
                        } else {
                            list +=`
                                <div class="display-day-cell" data-day=${window.lodash.escape(day.key)}>
                                    <progress-ring stroke="3" radius="20" progress="${window.lodash.escape(day.percent)}" text="${window.lodash.escape(day.day)}"></progress-ring>
                                </div>
                            `
                        }
                    })
                    list += `</div>`

                    content.html(`<div class="" id="selection-grid-wrapper">${list}</div>`)
                }
                draw_calendar()

                //day times coverage modal
                $('.display-day-cell').on( 'click', function (){
                    let day_timestamp = $(this).data('day')
                    $('#view-times-modal').foundation('open')
                    let list_title = jQuery('#list-modal-title')
                    let day=days.find(k=>k.key===day_timestamp)
                    list_title.empty().html(`<h2 class="section_title">${window.lodash.escape(day.formatted)}</h2>`)
                    let day_times_content = $('#day-times-table-body')
                    let times_html = ``
                    let row_index = 0
                    day.slots.forEach(slot=>{
                        let background_color = 'white'
                        if ( slot.subscribers > 0) {
                            background_color = 'lightblue'
                        } if ( slot.subscribers > 1) {
                            background_color = 'mediumseagreen'
                        }
                        if ( row_index === 0 ){
                            times_html += `<tr><td>${window.lodash.escape(slot.formatted)}</td>`
                        }
                        times_html +=`<td style="background-color:${background_color}">
                            ${window.lodash.escape(slot.subscribers)} <i class="fi-torsos"></i>
                        </td>`
                        if ( times_html === 3 ){
                            times_html += `</tr>`
                        }
                        row_index = row_index === 3 ? 0 : row_index + 1;
                    })
                    day_times_content.empty().html(`<div class="grid-x"> ${times_html} </div>`)
                })

                //draw progress circles
                window.customElements.define('progress-ring', ProgressRing);

                //dawn calendar in date select modal
                let modal_calendar = $('#modal-calendar')
                let now = new Date().getTime()/1000
                let draw_modal_calendar = ()=> {
                    let last_month = "";
                    modal_calendar.empty()
                    let list = ''
                    days.forEach(day => {
                        if (day.month!==last_month) {
                            if (last_month) {
                                //add extra days at the month end
                                let day_number = new Date(window.campaign_scripts.day_start(day.key, current_time_zone) * 1000).getDay()
                                for (let i = 1; i <= 7 - day_number; i++) {
                                    list += `<div class="day-cell disabled-calendar-day">${window.lodash.escape(i)}</div>`
                                }
                                list += `</div>`
                            }

                            list += `<h3 class="month-title">${window.lodash.escape(day.month)}</h3><div class="calendar">`
                            if (!last_month) {
                                list += headers
                            }

                            //add extra days at the month start
                            let day_number = new Date(window.campaign_scripts.day_start(day.key, current_time_zone) * 1000).getDay()
                            let start_of_week = new Date((day.key - day_number * 86400) * 1000)
                            for (let i = 0; i < day_number; i++) {
                                list += `<div class="day-cell disabled-calendar-day">${window.lodash.escape(start_of_week.getDate() + i)}</div>`
                            }
                            last_month = day.month
                        }
                        let disabled = (day.key + (24 * 3600)) < now;
                        list += `
                        <div class="day-cell ${disabled ? 'disabled-calendar-day':'selected-day day-in-select-calendar'}" data-day="${window.lodash.escape(day.key)}">
                            ${window.lodash.escape(day.day)}
                        </div>
                    `
                    })
                    modal_calendar.html(list)
                }
                draw_modal_calendar()

                //build time select input
                let time_select = $('#daily_time_select')

                let cal_select_all_div = $('#calendar-select-all-selected')
                let cal_select_help_text = $('#calendar-select-help')
                $(document).on('click', '.day-in-select-calendar', function (){
                    $(this).toggleClass('selected-day')
                    show_help_text()
                })
                $('#open-select-times-button').on('click', function (){
                    show_help_text()
                })

                $('#unselect-all-calendar-days').on( "click", function (){
                    $('.selected-day').toggleClass('selected-day')
                    show_help_text()
                })
                $('#select-all-calendar-days').on( "click", function (){
                    $('.day-in-select-calendar').addClass('selected-day')
                    show_help_text()
                })

                time_select.on( "change", function (){
                    disable_button( $('.selected-day').length )
                })

                let disable_button = ( num_selected ) => {
                    let select_val = $('#daily_time_select').val()
                    $('#confirm-daily-time').prop('disabled', num_selected === 0 || select_val === "false" )
                }
                let show_help_text = () =>{
                    let selected_days = $('.selected-day')
                    let number_of_days_selected = selected_days.length
                    if ( number_of_days_selected === days.length ){
                        cal_select_all_div.show()
                        cal_select_help_text.hide()
                    } else {
                        cal_select_all_div.hide()
                        cal_select_help_text.show()
                    }
                    $('#calendar-select-help-text').html(`${window.lodash.escape(number_of_days_selected)} of ${window.lodash.escape(days.length)} days selected`)
                    disable_button( number_of_days_selected )
                    let coverage = {}
                    let already_selected = parseInt(time_select.val())
                    selected_days.each((index, val)=> {
                        let day = $(val).data('day')
                        for ( const key in calendar_subscribe_object.current_commitments ){
                            if (!calendar_subscribe_object.current_commitments.hasOwnProperty(key)) {
                                continue;
                            }
                            if ( key >= day && key < day + 24 * 3600 ){
                                let mod_time = key % (24 * 60 * 60)
                                let time_formatted = '';
                                if ( window.campaign_scripts.processing_save[mod_time] ){
                                    time_formatted = window.campaign_scripts.processing_save[mod_time]
                                } else {
                                    time_formatted = window.campaign_scripts.timestamp_to_time( parseInt(key), current_time_zone )
                                    window.campaign_scripts.processing_save[mod_time] = time_formatted
                                }
                                if ( !coverage[time_formatted]){
                                    coverage[time_formatted] = [];
                                }
                                coverage[time_formatted].push(calendar_subscribe_object.current_commitments[key]);
                            }
                        }
                    })
                    let select_html = `<option value="false">Select a time</option>`

                    let key = 0;
                    let start_of_today = new Date()
                    start_of_today.setHours(0,0,0,0)
                    let start_time_stamp = start_of_today.getTime()/1000
                    while ( key < 24 * 3600 ){
                        let time_formatted = window.campaign_scripts.timestamp_to_time(start_time_stamp+key)
                        let text = ''
                        let covered = coverage[time_formatted] ? coverage[time_formatted].length === number_of_days_selected : false;
                        let fully_covered = window.campaign_scripts.time_slot_coverage[time_formatted] ? window.campaign_scripts.time_slot_coverage[time_formatted] === number_of_days : false;
                        let level_covered = coverage[time_formatted] ? Math.min(...coverage[time_formatted]) : 0
                        if ( fully_covered && level_covered === 1 ){
                            text = "(fully covered once)"
                        } else if ( fully_covered && level_covered > 1  ){
                            text = `(fully covered ${level_covered} times)`
                        } else if ( covered ){
                            text = "(covered for selected days)"
                        } else if ( coverage[time_formatted] && coverage[time_formatted].length > 0 ){
                            text = `(${coverage[time_formatted].length} out of ${number_of_days_selected} selected days covered)`
                        }
                        select_html += `<option value="${window.lodash.escape(key)}" ${already_selected===key ? "selected" : ''}>
                            ${window.lodash.escape(time_formatted)} ${ window.lodash.escape(text) }
                        </option>`
                        key += calendar_subscribe_object.slot_length * 60
                    }
                    time_select.empty();
                    time_select.html(select_html)
                }

                $('#confirm-daily-time').on("click", function (){
                    // $('#confirm-times-modal').foundation('open');
                    $('#modal-calendar').toggleClass('small-view')
                    $('.select-view').hide()
                    $('.confirm-view').show()
                    $('#time-confirmation').html( $('#daily_time_select option:selected').text())
                    $('#time-duration-confirmation').html($('#prayer-time-duration-select option:selected').text())
                })

                $('#back-to-select').on('click', function (){
                    $('#modal-calendar').toggleClass('small-view')
                    $('.select-view').toggle()
                    $('.confirm-view').toggle()
                    show_help_text()
                })

                $('#submit-form').on('click', function (){
                    let submit_button = jQuery('#submit-form')
                    submit_button.addClass('loading')
                    submit_button.prop('disabled', true)

                    let honey = jQuery('#email').val()
                    if ( honey ) {
                        jQuery('#next_1').html('Shame, shame, shame. We know your name ... ROBOT!').prop('disabled', true )
                        window.spinner.hide()
                        return;
                    }

                    let name_input = jQuery('#name')
                    let name = name_input.val()
                    if ( ! name ) {
                        jQuery('#name-error').show()
                        submit_button.removeClass('loading')
                        name_input.focus(function(){
                            jQuery('#name-error').hide()
                        })
                        submit_button.prop('disabled', false)
                        return;
                    }

                    let email_input = jQuery('#e2')
                    let email = email_input.val()
                    if ( ! email ) {
                        jQuery('#email-error').show()
                        submit_button.removeClass('loading')
                        email_input.focus(function(){
                            jQuery('#email-error').hide()
                        })
                        submit_button.prop('disabled', false)
                        return;
                    }

                    let selected_daily_time = time_select.val()
                    let selected_times = []
                    $('.selected-day').each((index, val)=>{
                        let day = $(val).data('day')
                        let time = parseInt(day) + parseInt(selected_daily_time)
                        let now = new Date().getTime()/1000
                        if ( time > now && time >= calendar_subscribe_object['start_timestamp'] ){
                            let duration = parseInt($('#prayer-time-duration-select').val())
                            selected_times.push({time: time, duration: duration})
                        }
                    })

                    let receive_prayer_time_notifications = $('#receive_prayer_time_notifications').is(':checked')

                    let data = {
                        name: name,
                        email: email,
                        selected_times: selected_times,
                        campaign_id: calendar_subscribe_object.campaign_id,
                        timezone: current_time_zone,
                        receive_prayer_time_notifications,
                        parts: calendar_subscribe_object.parts
                    }
                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify(data),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: calendar_subscribe_object.root + calendar_subscribe_object.parts.root + '/v1/' + calendar_subscribe_object.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', calendar_subscribe_object.nonce )
                        }
                    })
                    .done(function(){
                        submit_button.removeClass('loading')
                        $('#modal-calendar').hide()
                        $('.select-view').hide()
                        $('.confirm-view').hide()
                        $(`.success-confirmation-section`).show()
                    })
                    .fail(function(e) {
                        console.log(e)
                        $('#selection-error').empty().html(`<div class="cell center">
                        So sorry. Something went wrong. Please, contact us to help you through it, or just try again.<br>
                        <a href="${window.lodash.escape(window.location.href)}">Try Again</a>
                        </div>`).show()
                        $('#error').html(e)
                        submit_button.removeClass('loading')
                    })
                })
            })

        </script>
        <?php
        return true;
    }

    public function access_account_body(){
        ?>
        <style>
            #email {
                display:none;
            }
        </style>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="center"><h2><?php esc_html_e( "We'll Email You a Link to Your Account", 'disciple_tools' ); ?></h2></div>
            <div class="grid-x ">
                <div class="cell">
                    <span id="email-error" class="form-error">
                        <?php esc_html_e( "You're email is required.", 'disciple_tools' ); ?>
                    </span>
                    <label for="email"><?php esc_html_e( 'Email', 'disciple_tools' ); ?><br>
                        <input type="email" name="email" id="email" placeholder="Email" />
                        <input type="email" name="e2" id="e2" placeholder="Email" required />
                    </label>
                </div>
                <div class="cell center">
                    <button class="button large" id="send-link-form"><?php esc_html_e( 'Send me a link to my prayer times', 'disciple_tools' ); ?></button>
                </div>
            </div>
        </div> <!-- form wrapper -->
        <script>
            jQuery(document).ready(function($){

                jQuery('#send-link-form').on('click', function(){
                    let spinner = jQuery('.loading-spinner')
                    spinner.addClass('active')

                    let submit_button = jQuery('#send-link-form')
                    submit_button.prop('disabled', true)

                    let honey = jQuery('#email').val()
                    if ( honey ) {
                        jQuery('#next_1').html('Shame, shame, shame. We know your name ... ROBOT!').prop('disabled', true )
                        window.spinner.hide()
                        return;
                    }

                    let email_input = jQuery('#e2')
                    let email = email_input.val()
                    if ( ! email ) {
                        jQuery('#email-error').show()
                        spinner.hide()
                        email_input.focus(function(){
                            jQuery('#email-error').hide()
                        })
                        submit_button.prop('disabled', false)
                        return;
                    }

                    let data = {
                        'email': email,
                        'campaign_id': calendar_subscribe_object.campaign_id,
                        'parts': calendar_subscribe_object.parts
                    }

                    let wrapper = jQuery('#wrapper')
                    wrapper.empty().html(`<div class="grid-x"><div class="cell center"><span class="loading-spinner active"></span></div></div>`)

                    let alert = `
                    <div class="grid-x">
                        <div class="cell center">
                            <h2><?php esc_html_e( 'Sent! Check your email.', 'disciple_tools' ); ?>
                                <br><br>
                                <?php esc_html_e( "If your address is in our system, you'll get an email with a link to your prayer commitments.", 'disciple_tools' ); ?>
                            </h2>
                        </div>
                    </div>
                    `
                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify(data),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: calendar_subscribe_object.root + calendar_subscribe_object.parts.root + '/v1/' + calendar_subscribe_object.parts.type + '/access_account',
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', calendar_subscribe_object.nonce )
                        }
                    })
                    .done(function(data){
                        console.log(data)
                        wrapper.empty().html(alert)
                        spinner.removeClass('active')
                    })
                    .fail(function(e) {
                        console.log(e)
                        $('#error').html(e)
                        spinner.removeClass('active')
                    })
                })
            })
        </script>
        <?php
    }
}
