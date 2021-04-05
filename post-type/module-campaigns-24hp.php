<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Campaign_24hp extends DT_Module_Base
{
    public $module = "campaigns_24hour_prayer";
    public $post_type = 'campaigns';

    public $magic = false;
    public $parts = false;
    public $root = "campaign_app"; // define the root of the url {yoursite}/root/type/key/action
    public $type = '24hp'; // define the type


    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        parent::__construct();
        if ( !self::check_enabled_and_prerequisites() ){
            return;
        }
        // register tiles if on details page
        add_filter( 'dt_campaign_types', [ $this, 'dt_campaign_types' ], 20, 1 );
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 30, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 30, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'tile_scripts' ], 100 );

        // register type
        $this->magic = new DT_Magic_URL( $this->root );
        add_filter( 'dt_magic_url_register_types', [ $this, 'register_type' ], 10, 1 );

        // register REST and REST access
        add_filter( 'dt_allow_rest_access', [ $this, 'authorize_url' ], 10, 1 );
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );

        // stop processing for non magic urls
        $url = dt_get_url_path();
        if ( strpos( $url, $this->root . '/' . $this->type ) === false ) {
            return;
        }

        // fail to blank if not valid url
        $this->parts = $this->magic->parse_url_parts();
        if ( ! $this->parts ){
            // @note this returns a blank page for bad url, instead of redirecting to login
            add_filter( 'dt_templates_for_urls', function ( $template_for_url ) {
                $url = dt_get_url_path();
                $template_for_url[ $url ] = 'template-blank.php';
                return $template_for_url;
            }, 199, 1 );
            add_filter( 'dt_blank_access', function(){ return true;
            } );
            add_filter( 'dt_allow_non_login_access', function(){ return true;
            }, 100, 1 );
            return;
        }

        // fail if does not match type
        if ( $this->type !== $this->parts['type'] ){
            return;
        }

        // load if valid url
        add_action( 'dt_blank_head', [ $this, 'form_head' ] );
        add_action( 'dt_blank_footer', [ $this, 'form_footer' ] );
        if ( $this->magic->is_valid_key_url( $this->type ) && '' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'form_body' ] );
        }
        else if ( 'access_account' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'access_account_body' ] );
        }
        else {
            // fail if no valid action url found
            return;
        }

        // load page elements
        add_action( 'wp_print_scripts', [ $this, 'print_scripts' ], 1500 );
        add_action( 'wp_print_styles', [ $this, 'print_styles' ], 1500 );

        // register url and access
        add_filter( 'dt_templates_for_urls', [ $this, 'register_url' ], 199, 1 );
        add_filter( 'dt_blank_access', [ $this, '_has_access' ] );
        add_filter( 'dt_allow_non_login_access', function(){ return true;
        }, 100, 1 );
    }

    public function fail_register( $template_for_url ) {
        $url = dt_get_url_path();
        $template_for_url[ $url ] = 'template-blank.php';
        return $template_for_url;
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

            if ( 'apps' === $section ) {
                $record = DT_Posts::get_post( $post_type, get_the_ID() );

                if ( isset( $record['type']['key'] ) && '24hp' === $record['type']['key'] ) {
                    if ( isset( $record['public_key'] )) {
                        $key = $record['public_key'];
                    } else {
                        $key = DT_Subscriptions_Base::instance()->create_unique_key();
                        update_post_meta( get_the_ID(), 'public_key', $key );
                    }
                    if ( !isset( $record["start_date"]["timestamp"], $record["end_date"]["timestamp"] ) ){
                        ?>
                        <p>A Start Date and End Date are required for the 24 hour campaign</p>
                        <?php
                        return;
                    }
                    $link = trailingslashit( site_url() ) . $this->root . '/' . $this->type . '/' . $key;
                    ?>
                    <div class="cell">
                        <div class="section-subheader">
                            <?php esc_html_e( '24hour Circles Prayer', 'disciple_tools' ); ?>
                        </div>
                        <a class="button hollow small" onclick="copyToClipboard('<?php echo esc_url( $link ) ?>')"><?php esc_html_e( 'Copy Link', 'disciple_tools' ); ?></a>
                        <a class="button hollow small" onclick="open_app('<?php echo esc_url( $link ) ?>')"><?php esc_html_e( 'Open', 'disciple_tools' ); ?></a>
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
                } // end if 24hp prayer
            } // end if apps section
        } // end if campaigns and enabled
    }


    /**
     * Open default restrictions for access to registered endpoints
     * @param $authorized
     * @return bool
     */
    public function authorize_url( $authorized ){
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $this->root . '/v1/'.$this->type ) !== false ) {
            $authorized = true;
        }
        return $authorized;
    }

    /**
     * Register REST Endpoints
     * @link https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
     */
    public function add_api_routes() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/'.$this->type, [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'create_subscription' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'.$this->type . '/access_account', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'access_account' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
    }

    public function create_subscription( WP_REST_Request $request ) {
        $params = $request->get_params();

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

        $user = wp_get_current_user();
        $user->add_cap( 'create_subscriptions' );

        $hash = dt_create_unique_key();

        $receive_prayer_time_notifications = isset( $params["receive_prayer_time_notifications"] ) && !empty( $params["receive_prayer_time_notifications"] );

        $fields = [
            'title' => $title,
            "contact_email" => [
                [ "value" => $email ],
            ],
            'campaigns' => [
                "values" => [
                    [ "value" => $params['campaign_id'] ],
                ],
            ],
            'timezone' => $params["timezone"],
            'public_key' => $hash,
            'receive_prayer_time_notifications' => $receive_prayer_time_notifications,
        ];

        // create post

        $new_id = DT_Posts::create_post( 'subscriptions', $fields, true );
        if ( is_wp_error( $new_id ) ) {
            return $new_id;
        }

        $campaign = DT_Posts::get_post( 'campaigns', $params['campaign_id'], true, false );
        if ( is_wp_error( $campaign ) ){
            return new WP_Error( __METHOD__, "Sorry, Something went wrong", [ 'status' => 400 ] );
        }
        $campaign_grid_id = isset( $campaign["location_grid"][0]['id'] ) ? $campaign["location_grid"][0]['id'] : null;

        // log reports
        foreach ( $params['selected_times'] as $time ){
            if ( !isset( $time["time"] ) ){
                continue;
            }
            $location_id = isset( $time['grid_id'] ) ? $time['grid_id'] : $campaign_grid_id;
            $args = [
                'parent_id' => $params['campaign_id'],
                'post_id' => $new_id['ID'],
                'post_type' => 'subscriptions',
                'type' => $this->root,
                'subtype' => $this->type,
                'payload' => null,
                'value' => 0,
                'lng' => null,
                'lat' => null,
                'level' => null,
                'label' => null,
                'grid_id' => $location_id,
                'time_begin' => $time['time'],
                'time_end' => $time['time'] + 900,
            ];

            $grid_row = Disciple_Tools_Mapping_Queries::get_by_grid_id( $location_id );
            if ( ! empty( $grid_row ) ){
                $full_name = Disciple_Tools_Mapping_Queries::get_full_name_by_grid_id( $location_id );
                $args['lng'] = $grid_row['longitude'];
                $args['lat'] = $grid_row['latitude'];
                $args['level'] = $grid_row['level_name'];
                $args['label'] = $full_name;
            }
            Disciple_Tools_Reports::insert( $args );
        }

        $email_sent = DT_Prayer_Campaigns_Send_Email::send_registration( $new_id['ID'] );

        if ( !$email_sent ){
            return new WP_Error( __METHOD__, "Could not sent email confirmation", [ 'status' => 400 ] );
        }

        return $hash;
    }

    public function access_account( WP_REST_Request $request ) {
        $params = $request->get_params();

        // @todo insert email reset link
        $params = dt_recursive_sanitize_array( $params );
        if ( ! isset( $params['email'], $params['campaign_id'] ) ) {
            return new WP_Error( __METHOD__, "Missing required parameter.", [ 'status' => 400 ] );
        }

        DT_Prayer_Campaigns_Send_Email::send_account_access( $params['campaign_id'], $params['email'] );

        return $params;
    }


    public function dt_campaign_types( $types ) {
        $types['24hp'] = [
            'label' => __( '24hr Prayer Calendar (v2)', 'disciple_tools' ),
            'description' => __( 'Cover a region with 24h prayer.', 'disciple_tools' ),
            "visibility" => __( "Collaborators", 'disciple_tools' ),
            'color' => "#4CAF50",
        ];
        return $types;
    }

    public function tile_scripts(){
        if ( is_singular( "campaigns" ) ){
            $magic = new DT_Magic_URL( 'campaigns_app' );
            $types = $magic->list_types();
            $campaigns = $types['campaigns'] ?? [];
            $campaigns['new_key'] = $magic->create_unique_key();

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

    public function register_type( array $types ) : array {
        if ( ! isset( $types[$this->root] ) ) {
            $types[$this->root] = [];
        }
        $types[$this->root][$this->type] = [
            'name' => 'Subscriptions',
            'root' => $this->root,
            'type' => $this->type,
            'meta_key' => 'public_key', // coaching-magic_c_key
            'actions' => [
                '' => 'Manage',
                'access_account' => 'Access Account',
            ],
            'post_type' => $this->post_type,
        ];
        return $types;
    }

    public function register_url( $template_for_url ){
        $parts = $this->parts;

        // test 1 : correct url root and type
        if ( ! $parts ){ // parts returns false
            return $template_for_url;
        }

        // test 2 : only base url requested
        if ( empty( $parts['public_key'] ) ){ // no public key present
            $template_for_url[ $parts['root'] . '/'. $parts['type'] ] = 'template-blank.php';
            return $template_for_url;
        }

        // test 3 : no specific action requested
        if ( empty( $parts['action'] ) ){ // only root public key requested
            $template_for_url[ $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] ] = 'template-blank.php';
            return $template_for_url;
        }

        // test 4 : valid action requested
        $actions = $this->magic->list_actions( $parts['type'] );
        if ( isset( $actions[ $parts['action'] ] ) ){
            $template_for_url[ $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] . '/' . $parts['action'] ] = 'template-blank.php';
        }

        return $template_for_url;
    }

    public function _has_access() : bool {
        $parts = $this->parts;

        // test 1 : correct url root and type
        if ( $parts ){ // parts returns false
            return true;
        }

        return false;
    }

    public function print_scripts(){
        // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php
        $allowed_js = [
            'jquery',
            'jquery-core',
            'lodash',
            'lodash-core',
            'site-js',
            'shared-functions',
            'dt_campaign_core'
        ];

        global $wp_scripts;

        if ( isset( $wp_scripts ) ){
            foreach ( $wp_scripts->queue as $key => $item ){
                if ( ! in_array( $item, $allowed_js ) ){
                    unset( $wp_scripts->queue[$key] );
                }
            }
        }
        if ( isset( $wp_scripts ) ){
            foreach ( $wp_scripts->registered as $key => $item ){
                if ( ! in_array( $key, $allowed_js ) ){
                    unset( $wp_scripts->registered[$key] );
                }
            }
        }
        unset( $wp_scripts->registered['mapbox-search-widget']->extra['group'] );
    }

    public function print_styles(){
        // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php
        $allowed_css = [
            'foundation-css',
            'site-css',
        ];

        global $wp_styles;
        if ( isset( $wp_styles ) ) {
            foreach ($wp_styles->queue as $key => $item) {
                if ( !in_array( $item, $allowed_css )) {
                    unset( $wp_styles->queue[$key] );
                }
            }
        }
    }

    public function form_head(){
        wp_head(); // styles controlled by wp_print_styles and wp_print_scripts actions
        $this->campaigns_styles_header();
    }

    public function form_footer(){
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

    public function form_body(){

        $post_id = $this->parts['post_id'];
        $record = DT_Posts::get_post( "campaigns", $post_id, true, false );
        if ( is_wp_error( $record ) ){
            return;
        }
        $coverage_percentage = DT_Campaigns_Base::query_coverage_percentage( $post_id );
        $number_of_time_slots = DT_Campaigns_Base::query_coverage_total_time_slots( $post_id );

        $description = "Campaign Description";
        if ( isset( $record["description"] ) && !empty( $record["description"] ) ){
            $description = $record["description"];
        }

        if ( isset( $record['type']['key'] ) && '24hp' === $record['type']['key'] ) {
            ?>

            <div id="wrapper">
                <div class="center">
                    <h2 ><?php echo esc_html( $description ); ?></h2>

                    At <strong><?php echo esc_html( $coverage_percentage ); ?>%</strong> of the <?php echo esc_html( $number_of_time_slots ); ?> time slots needed to cover the region in 24 hour prayer.
                </div>
                <div id="main-progress" class="center">
                    <progress-ring stroke="10" radius="100" font="20" progress="<?php echo esc_html( $coverage_percentage ); ?>" text="<?php echo esc_html( $coverage_percentage ); ?>% Covered"></progress-ring>
                </div>

                <!--                <div class="center">-->
                <!--                    <h3>Calendar</h3>-->
                <!--                </div>-->
                <div id="calendar-content"></div>

                <div class="center">
                    <button class="button" data-open="select-times-modal" id="open-select-times-button" style="margin-top: 10px">Sign up to Pray</button>
                </div>

                <div class="reveal" id="select-times-modal" data-reveal data-close-on-click="false" data-multiple-opened="true">

                    <div id="modal-calendar">Modal Calendar</div>
                    <p id="calendar-select-all-selected" class="select-view" style="margin-top: 10px">All days selected.
                        <a id="unselect-all-calendar-days">unselect all</a>
                    </p>
                    <p id="calendar-select-help" class="select-view" style="margin-top: 10px; display: none">
                        <span id="calendar-select-help-text"></span>
                        <a id="select-all-calendar-days">select all</a>
                    </p>

                    <select id="daily_time_select" class="select-view">
                        <option>Daily Time</option>
                    </select>



                    <div id="confirmation-section" class="confirm-view" style="margin-top:15px">
                        <!--                        //@todo timezone-->
                        <p>Your are signing up to praying on each selected day at <span id="time-confirmation" style="font-weight: bold"></span></p>

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


                    <button class="button button-cancel clear select-view" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Cancel', 'disciple_tools' )?>
                    </button>

                    <button class="button button-cancel clear confirm-view" id="back-to-select" aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'back', 'disciple_tools' )?>
                    </button>
                    <button class="button select-view" type="button" id="confirm-daily-time" disabled>
                        <?php echo esc_html__( 'Next', 'disciple_tools' )?>
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
                'start_timestamp' => DT_Time_Utilities::start_of_campaign_with_timezone( $post["ID"] ),
                'end_timestamp' => DT_Time_Utilities::end_of_campaign_with_timezone( $post["ID"] ) + 86400,
                'current_commitments' => $current_commitments,
                'slot_length' => 15
            ]) ?>][0]

            let current_time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'
            let selected_calendar_color = 'green'


            const number_of_days = ( calendar_subscribe_object.end_timestamp - calendar_subscribe_object.start_timestamp ) / ( 24*3600)
            let time_slot_coverage = {}



            jQuery(document).ready(function($){
                let days = window.campaign_scripts.calculate_day_times()

                let draw_calendar = ( id = 'calendar-content') => {
                    let content = $(`#${id}`)
                    content.empty()
                    let list = ''
                    let now = new Date().getTime()/1000
                    let last_month = "";
                    days.forEach(day=>{
                        if ( day.month !== last_month ){
                            last_month = day.month
                            list += `<h3 style="display: block; padding:0 10px">${day.month}</h3>`
                        }
                        if ( day.disabled ){
                            list += `<div style="display: inline-block; width:40px" class="center">
                                ${day.day}
                            </div>`
                        } else {
                            list +=`
                                <div style="display: inline-block">
                                    <progress-ring stroke="3" radius="20" progress="${day.percent}" text="${day.day}"></progress-ring>
                                </div>
                            `
                        }
                    })

                    content.html(`<div class="" id="selection-grid-wrapper">${list}</div>`)
                }
                draw_calendar()

                //draw progress circles
                window.customElements.define('progress-ring', ProgressRing);

                //dawn calendar in date select modal
                let modal_calendar = $('#modal-calendar')
                modal_calendar.empty()
                let list = ''
                let now = new Date().getTime()/1000
                let last_month = "";
                days.forEach(day=>{
                    let disabled = ( day.key + ( 24*3600 ) ) > now ?  '' : "disabled-day";
                    if ( day.month !== last_month ){
                        last_month = day.month
                        list += `<h3 class="calendar-month-title" style="">${window.lodash.escape(day.month)}</h3>`
                    }
                    list +=`
                        <div class="selected-day day-in-select-calendar" data-day="${window.lodash.escape(day.key)}">
                            ${window.lodash.escape(day.day)}
                        </div>
                    `
                })
                modal_calendar.html(list)

                //build time select input
                let time_select = $('#daily_time_select')
                time_select.empty();
                time_select.html(window.campaign_scripts.get_time_select_html())


                let cal_select_all_div = $('#calendar-select-all-selected')
                let cal_select_help_text = $('#calendar-select-help')
                $(document).on('click', '.day-in-select-calendar', function (){
                    $(this).toggleClass('selected-day')
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
                    let number_of_days_selected = $('.selected-day').length
                    if ( number_of_days_selected === days.length ){
                        cal_select_all_div.show()
                        cal_select_help_text.hide()
                    } else {
                        cal_select_all_div.hide()
                        cal_select_help_text.show()
                    }
                    $('#calendar-select-help-text').html(`${number_of_days_selected} of ${days.length} days selected`)
                    disable_button( number_of_days_selected )
                }

                $('#confirm-daily-time').on("click", function (){
                    // $('#confirm-times-modal').foundation('open');
                    $('#modal-calendar').toggleClass('small-view')
                    $('.select-view').hide()
                    $('.confirm-view').show()
                    $('#time-confirmation').html( $('#daily_time_select option:selected').text())
                })

                $('#back-to-select').on('click', function (){
                    $('#modal-calendar').toggleClass('small-view')
                    $('.select-view').toggle()
                    $('.confirm-view').toggle()
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
                        selected_times.push({ time })
                    })

                    let receive_prayer_time_notifications = $('#receive_prayer_time_notifications').is(':checked')

                    let data = {
                        name: name,
                        email: email,
                        selected_times: selected_times,
                        campaign_id: calendar_subscribe_object.campaign_id,
                        timezone: current_time_zone,
                        receive_prayer_time_notifications
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
                        <a href="${window.location.href}">Try Again</a>
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

}
