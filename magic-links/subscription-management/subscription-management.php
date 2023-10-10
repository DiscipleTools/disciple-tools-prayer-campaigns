<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DT_Prayer_Subscription_Management_Magic_Link extends DT_Magic_Url_Base {

    public $post_type = 'subscriptions';
    public $page_title = 'My Prayer Times';

    public $magic = false;
    public $parts = [];
    public $root = 'subscriptions_app'; // define the root of the url {yoursite}/root/type/key/action
    public $type = 'manage'; // define the type
    public $type_name = 'Subscriptions';
    public $type_actions = [
        '' => 'Manage',
        'download_calendar' => 'Download Calendar',
    ];

    public function __construct(){
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
        parent::__construct();
        if ( !$this->check_parts_match() ){
            return;
        }
        $post = DT_Posts::get_post( 'subscriptions', $this->parts['post_id'], true, false );
        if ( is_wp_error( $post ) || empty( $post['campaigns'] ) ){
            return;
        }
        if ( isset( $post['lang'] ) && $post['lang'] !== 'en_US' ){
            $lang_code = $post['lang'];
            add_filter( 'determine_locale', function ( $locale ) use ( $lang_code ){
                if ( !empty( $lang_code ) ){
                    return $lang_code;
                }
                return $locale;
            } );
        }
        $this->page_title = __( 'My Prayer Times', 'disciple-tools-prayer-campaigns' );

        // load if valid url
        if ( 'download_calendar' === $this->parts['action'] ) {
            $download_content = $this->generate_calendar_download_content( $this->parts['post_id'] );
            if ( !empty( $download_content ) ) {
                header( 'Content-type: text/calendar; charset=utf-8' );
                header( 'Content-Disposition: inline; filename=calendar.ics' );
                echo esc_html( $download_content );
                die();
            }
            return;
        } else if ( '' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'manage_body' ] );
        } else {
            return; // fail if no valid action url found
        }

        // load page elements
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js = [
            'jquery',
            'jquery-ui',
            'lodash',
            'lodash-core',
            'shared-functions',
            'moment',
            'datepicker',
            'site-js',
        ];
        $allowed_js[] = 'dt_campaign_core';
        $allowed_js[] = 'luxon';
        $allowed_js[] = 'dt_subscription_js';
        $allowed_js[] = 'campaign_css';
        $allowed_js[] = 'campaign_components';
        $allowed_js[] = 'campaign_sign_up_component';
        $allowed_js[] = 'campaign_components';
        $allowed_js[] = 'campaign_css_component';
        $allowed_js[] = 'toastify-js';
        return $allowed_js;
    }
    public function dt_magic_url_base_allowed_css( $allowed_js ) {
        $allowed_js[] = 'dt_subscription_css';
        $allowed_js[] = 'toastify-js-css';
        return $allowed_js;
    }

    public function wp_enqueue_scripts(){
        dt_campaigns_register_scripts( $this->parts );

        $post = DT_Posts::get_post( 'subscriptions', $this->parts['post_id'], true, false );
        if ( is_wp_error( $post ) ) {
            return $post;
        }
        $campaign_id = $post['campaigns'][0]['ID'];

        wp_enqueue_style( 'dt_subscription_css', DT_Prayer_Campaigns::instance()->plugin_dir_url . 'magic-links/subscription-management/subscription-management.css', [], filemtime( DT_Prayer_Campaigns::instance()->plugin_dir_path . 'magic-links/subscription-management/subscription-management.css' ) );
        wp_enqueue_script( 'dt_subscription_js', DT_Prayer_Campaigns::instance()->plugin_dir_url . 'magic-links/subscription-management/subscription-management.js', [ 'jquery', 'dt_campaign_core' ], filemtime( DT_Prayer_Campaigns::instance()->plugin_dir_path . 'magic-links/subscription-management/subscription-management.js' ), true );


        wp_localize_script(
            'dt_subscription_js', 'subscription_page_data', [
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'name' => get_the_title( $this->parts['post_id'] ),
                'campaign_id' => $campaign_id,
            ]
        );
    }


    public static function get_clean_duration( $start_time, $end_time ) {
        $time_duration = ( $start_time - $end_time ) / 60;

        switch ( true ) {
            case $time_duration < 60:
                $time_duration .= ' minutes';
                break;
            case $time_duration === 60:
                $time_duration = $time_duration / 60 . ' hour';
                break;
            case $time_duration < 60:
                $time_duration = $time_duration . ' hours';
                break;
            case $time_duration > 60:
                $time_duration = $time_duration / 60 . ' hours';
        }
        return $time_duration;
    }

    public static function get_timezone_offset( $timezone ) {
        $dt_now = new DateTime();
        $dt_now->setTimezone( new DateTimeZone( esc_html( $timezone ) ) );
        $timezone_offset = sprintf( '%+03d', $dt_now->getOffset() / 3600 );
        return $timezone_offset;
    }

    public function get_download_url() {
        $download_url = trailingslashit( $this->parts['public_key'] ) .'download_calendar';
        return $download_url;
    }

    public static function generate_calendar_download_content( $post_id ) {
        // Get post data
        $post = DT_Posts::get_post( 'subscriptions', $post_id, true, false );
        if ( is_wp_error( $post ) ) {
            return $post;
        }
        $campaign_id = $post['campaigns'][0]['ID'];
        $locale = $post['lang'] ?: 'en_US';

        //get summary from campaign strings
        $calendar_title = $post['campaigns'][0]['post_title'];
        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        $calendar_timezone = $post['timezone'];
        $calendar_dtstamp = gmdate( 'Ymd' ).'T'. gmdate( 'His' ) . 'Z';
        $calendar_description = '';
        if ( isset( $campaign['campaign_strings'][$locale]['reminder_content'] ) ){
            $calendar_description = $campaign['campaign_strings'][$locale]['reminder_content'];
        } elseif ( isset( $campaign['campaign_strings']['en_US']['reminder_content'] ) ){
            $calendar_description = $campaign['campaign_strings']['en_US']['reminder_content'];
        }
        $calendar_timezone_offset = self::get_timezone_offset( esc_html( $calendar_timezone ) );

        $my_commitments_reports = self::get_subscriptions( $post_id );
        $my_commitments = [];

        foreach ( $my_commitments_reports as $commitments_report ){
            $commitments_report['time_begin'] = $commitments_report['time_begin'] + $calendar_timezone_offset * 3600;
            $commitments_report['time_end'] = $commitments_report['time_end'] + $calendar_timezone_offset * 3600;

            $my_commitments[] = [
                'time_begin' => gmdate( 'Ymd', $commitments_report['time_begin'] ) . 'T'. gmdate( 'His', $commitments_report['time_begin'] ),
                'time_end' => gmdate( 'Ymd', $commitments_report['time_end'] ) . 'T'. gmdate( 'His', $commitments_report['time_end'] ),
                'time_duration' => self::get_clean_duration( $commitments_report['time_end'], $commitments_report['time_begin'] ),
                'location' => $commitments_report['label'],
            ];
        }

        $content = "BEGIN:VCALENDAR\r\n";
        $content .= "VERSION:2.0\r\n";
        $content .= "PRODID:-//disciple.tools\r\n";
        $content .= "CALSCALE:GREGORIAN\r\n";
        $content .= "BEGIN:VTIMEZONE\r\n";
        $content .= 'TZID:' . esc_html( $calendar_timezone ) . "\r\n";
        $content .= "BEGIN:STANDARD\r\n";
        $content .= 'TZNAME:' . esc_html( $calendar_timezone_offset ) . "\r\n";
        $content .= 'TZOFFSETFROM:' . esc_html( $calendar_timezone_offset ) . "00\r\n";
        $content .= 'TZOFFSETTO:' . esc_html( $calendar_timezone_offset ) . "00\r\n";
        $content .= "DTSTART:19700101T000000\r\n";
        $content .= "END:STANDARD\r\n";
        $content .= "END:VTIMEZONE\r\n";

        foreach ( $my_commitments as $mc ) {
            $calendar_uid = md5( uniqid( mt_rand(), true ) ) . '@disciple.tools';

            $content .= "BEGIN:VEVENT\r\n";
            $content .= 'UID:' . esc_html( $calendar_uid ) . "\r\n";
            $content .= 'DTSTAMP:' . esc_html( $calendar_dtstamp ) . "\r\n";
            $content .= 'SUMMARY:' . esc_html( $calendar_title ) . "\r\n";
            $content .= 'DTSTART:' . esc_html( $mc['time_begin'] ) . "\r\n";
            $content .= 'DTEND:' . esc_html( $mc['time_end'] ) . "\r\n";
            $content .= 'DESCRIPTION:' . esc_html( $calendar_description ) . "\r\n";
            $content .= 'LOCATION:' . esc_html( get_site_url( null, '/prayer/list' ) ) . "\r\n";
            $content .= "STATUS:CONFIRMED\r\n";
            $content .= "SEQUENCE:3\r\n";
            $content .= "BEGIN:VALARM\r\n";
            $content .= "TRIGGER:-PT10M\r\n";
            $content .= "ACTION:DISPLAY\r\n";
            $content .= "END:VALARM\r\n";
            $content .= "END:VEVENT\r\n";
        }

        $content .= "END:VCALENDAR\r\n";

        return $content;
    }

    public function manage_body(){
        $post = DT_Posts::get_post( 'subscriptions', $this->parts['post_id'], true, false );
        if ( !isset( $post['campaigns'][0]['ID'] ) ){
            return false;
        }
        $campaign_id = $post['campaigns'][0]['ID'];
        //cannot manage a subscription that has no campaign
        if ( empty( $campaign_id ) ){
            $this->error_body();
            exit;
        }


        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        if ( is_wp_error( $campaign ) ) {
            return $campaign;
        }

        $current_selected_porch = DT_Campaign_Settings::get( 'selected_porch' );
        $color = PORCH_COLOR_SCHEME_HEX;
        if ( $color === 'preset' ){
            $color = '#4676fa';
        }
        $notifications = isset( $post['receive_prayer_time_notifications'] ) && !empty( $post['receive_prayer_time_notifications'] );

        ?>
        <style>
            :root {
                --cp-color: <?php echo esc_html( $color ) ?>;
                --cp-color-dark: color-mix(in srgb, var(--cp-color), #000 10%);
                --cp-color-light: color-mix(in srgb, var(--cp-color), #fff 70%);
            }
            .nav-bar {
                display: flex;
                justify-content: center;
                background-color: var(--cp-color);
                color: white;
            }
            .nav-bar h1 {
                margin: 0;
            }
            .nav-bar button {
                background-color: transparent;
                /*border: 1px solid white;*/
                border-radius: 0px;
                color: white;
                padding: 10px;
            }
            .nav-bar button:hover {
                background-color: var(--cp-color-dark);
                color: white;
            }
            .nav-bar button.active {
                background-color: var(--cp-color-dark);
                color: white
            }
        </style>
        <!-- header -->
        <div class="nav-bar" style="">
            <div style="padding:10px">
                <h1><?php echo esc_html( $post['name'] ); ?></h1>
            </div>
            <div style="display: flex;">
                <button class="active" data-show="prayer-times">Prayer Times</button>
                <button data-show="profile">Profile</button>
            </div>
        </div>
        <div id="wrapper">
            <div id="prayer-times" class="display-panel">

                <div style="display: flex; gap: 20px; justify-content: space-around; flex-wrap: wrap-reverse; flex-direction: row">
                    <!-- my times -->
                    <div>
                        <h2>My Prayer Times</h2>
                        <campaign-subscriptions></campaign-subscriptions>
                    </div>

                    <!-- calendar -->
                    <div>
                        <h2 style="display: inline">
                            My Calendar
                        </h2>
                        <a class="clear-button" style="margin-top: 10px" target="_blank" href="<?php echo esc_attr( self::get_download_url() ); ?>">
                            <?php esc_html_e( 'Download Calendar', 'disciple-tools-prayer-campaigns' ); ?>
                        </a>
                        <my-calendar>

                        </my-calendar>

                    </div>
                </div>

                <!-- Reveal Modal Timezone Changer-->
                <div id="timezone-changer" class="reveal tiny" data-reveal>
                    <h2><?php esc_html_e( 'Change your timezone:', 'disciple-tools-prayer-campaigns' ); ?></h2>
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
                        <?php echo esc_html__( 'Cancel', 'disciple-tools-prayer-campaigns' )?>
                    </button>
                    <button class="button" type="button" id="confirm-timezone" data-close>
                        <?php echo esc_html__( 'Select', 'disciple-tools-prayer-campaigns' )?>
                    </button>

                    <button class="close-button" data-close aria-label="Close modal" type="button">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Reveal Modal View Times-->
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
                            <?php echo esc_html__( 'Close', 'disciple-tools-prayer-campaigns' )?>
                        </button>
                    </div>
                </div>

                <!-- Reveal Modal Delete Time-->
                <div class="reveal" id="delete-time-modal" data-reveal data-close-on-click="true">
                    <h3 id="delete-time-modal-title"><?php esc_html_e( 'Delete Time', 'disciple-tools-prayer-campaigns' ); ?></h3>

                    <p id="delete-time-modal-text"></p>
                    <p id="delete-time-extra-warning">
                        <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/broken.svg' ) ?>"/>
                        <?php esc_html_e( 'Need to cancel? We get it! But wait!
    If your prayer commitment is scheduled to start in less than 48-hours, please ask a friend to cover it for you.
    That will keep the prayer chain from being broken AND will give someone the joy of fighting for the lost! Thanks!', 'disciple-tools-prayer-campaigns' ); ?>
                    </p>

                    <button class='button button-cancel clear' data-close aria-label='Close reveal' type='button'>
                        <?php echo esc_html__( 'Cancel', 'disciple-tools-prayer-campaigns' ) ?>
                    </button>
                    <button class="button loader alert" type="button" id="confirm-delete-my-time-modal">
                        <?php echo esc_html__( 'Delete', 'disciple-tools-prayer-campaigns' ) ?>
                    </button>

                    <button class="close-button" data-close aria-label="Close modal" type="button">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <?php do_action( 'campaign_management_signup_controls', $current_selected_porch ); ?>


                <div style="background-color: white; margin: 150px 50px 50px 50px">
                    <h2 style="text-align: center">Sign up for more prayer</h2>
                    <campaign-sign-up style="padding:30px 0"
                        already_signed_up="true"
                    ></campaign-sign-up>
                </div>

                <hr>

                <!-- Extra setting depending on the campaign type -->
                <div>
                    <?php do_action( 'dt_subscription_management_extra' ) ?>
                </div>
            </div>
            <div id="profile" class="display-panel" style="display: none">


              <div style="margin-top: 50px">
                <hr>
                <h2><?php esc_html_e( 'Profile Settings', 'disciple-tools-prayer-campaigns' ); ?></h2>

                <!-- Name -->
                <div>
                    <label for="name"><?php esc_html_e( 'Name', 'disciple-tools-prayer-campaigns' ); ?></label>
                    <input type="text" id="name" name="name" value="<?php echo esc_html( $post['name'] ); ?>">
                </div>

                <!-- Email -->
                <div>
                    <label for="email"><?php esc_html_e( 'Email', 'disciple-tools-prayer-campaigns' ); ?></label>
                    <input type="email" id="email" name="email" value="<?php echo esc_html( $post['contact_email'][0]['value'] ); ?>">
                </div>

                <!-- Notifications -->
                <div><?php esc_html_e( 'Receive prayer time notifications', 'disciple-tools-prayer-campaigns' ); ?> <span class="notifications_allowed_spinner loading-spinner"></span>
                    <select name="allow_notifications" id="allow_notifications">
                        <option <?php selected( $notifications ) ?> value="allowed"><?php esc_html_e( 'Notifications allowed', 'disciple-tools-prayer-campaigns' ); ?> ✅</option>
                        <option <?php selected( !$notifications ) ?> value="disallowed"><?php esc_html_e( 'Notifications not allowed', 'disciple-tools-prayer-campaigns' ); ?> ❌</option>
                    </select>
                </div>
                <div class="danger-zone">
                    <h2 style="margin: 0 10px 0 0"><?php esc_html_e( 'Advanced Settings', 'disciple-tools-prayer-campaigns' ); ?></h2>
                    <button class="chevron" onclick="toggle_danger();">
                        <img class="dt-white-icon" src="<?php echo esc_html( get_template_directory_uri() ); ?>/dt-assets/images/chevron_down.svg">
                    </button>
                </div>
                <div class="danger-zone-content collapsed">
                    <label style="margin-right: 10px">
                        <?php esc_html_e( 'Delete this profile and all the scheduled prayer times?', 'disciple-tools-prayer-campaigns' ); ?>
                    </label>
                    <button class="button alert" data-open="delete-profile-modal"><?php esc_html_e( 'Delete', 'disciple-tools-prayer-campaigns' ); ?></button>
                    <!-- Reveal Modal Daily time slot-->
                    <div id="delete-profile-modal" class="reveal tiny" data-reveal>
                        <h2><?php esc_html_e( 'Are you sure you want to delete your profile?', 'disciple-tools-prayer-campaigns' ); ?></h2>
                        <p>
                            <?php esc_html_e( 'This can not be undone.', 'disciple-tools-prayer-campaigns' ); ?>
                        </p>
                        <p id='delete-time-extra-warning'>
                            <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/broken.svg' ) ?>"/>
                            <?php esc_html_e( 'Need to cancel? We get it! But wait!
If your prayer commitment is scheduled to start in less than 48-hours, please ask a friend to cover it for you.
That will keep the prayer chain from being broken AND will give someone the joy of fighting for the lost! Thanks!', 'disciple-tools-prayer-campaigns' ); ?>
                        </p>
                        <p id="delete-account-errors"></p>


                        <button class="button button-cancel clear" data-close aria-label="Close reveal" type="button">
                            <?php echo esc_html__( 'Cancel', 'disciple-tools-prayer-campaigns' )?>
                        </button>
                        <button class="button loader alert" type="button" id="confirm-delete-profile">
                            <?php echo esc_html__( 'Delete', 'disciple-tools-prayer-campaigns' )?>
                        </button>

                        <button class="close-button" data-close aria-label="Close modal" type="button">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            </div>
            </div>
        </div>
        <?php
    }

    public function error_body(){
        ?>
        <div class="center" style="margin-top:50px">
            <h2 class=""><?php esc_html_e( 'This subscription has ended or is not configured correctly.', 'disciple-tools-prayer-campaigns' ); ?></h2>
        </div>
        <?php
    }

    public function add_api_routes() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/'.$this->type, [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'manage_profile' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'.$this->type . '/campaign_info', [
                [
                    'methods'  => 'GET',
                    'callback' => [ $this, 'campaign_info' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'.$this->type . '/delete_profile', [
                [
                    'methods'  => 'DELETE',
                    'callback' => [ $this, 'delete_profile' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'.$this->type . '/allow-notifications', [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'allow_notifications' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
    }

    public function delete_profile( WP_REST_Request $request ){
        $params = $request->get_params();

        if ( ! isset( $params['parts'], $params['parts']['meta_key'], $params['parts']['public_key'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }
        $params = dt_recursive_sanitize_array( $params );

        $post_id = $params['parts']['post_id']; //has been verified in verify_rest_endpoint_permissions_on_post()
        if ( ! $post_id ){
            return new WP_Error( __METHOD__, 'Missing post record', [ 'status' => 400 ] );
        }
        global $wpdb;

        //remove connection
        $a = $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts p SET post_title = 'Deleted Subscription', post_name = 'Deleted Subscription' WHERE p.ID = %s", $post_id ) );
        $a = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->dt_activity_log WHERE object_id = %s and action != 'add_subscription' and action !='delete_subscription'", $post_id ) );
        //create activity for connection removed on the campaign
//        DT_Posts::update_post( 'subscriptions', $post_id, [ 'campaigns' => [ 'values' => [], 'force_values' => true ] ], true, false );
        $a = $wpdb->query( $wpdb->prepare( "DELETE pm FROM $wpdb->postmeta pm WHERE pm.post_id = %s", $post_id ) );
        $a = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->dt_reports WHERE post_id = %s AND time_begin > %d", $post_id, time() ) );
        DT_Posts::update_post( 'subscriptions', $post_id, [ 'status' => 'inactive' ], true, false );

        $base_user = dt_get_base_user();
        DT_Posts::add_post_comment( 'subscriptions', $post_id, "@[$base_user->display_name]($base_user->ID) A user has deleted their profile and future prayer times.", 'comment', [], false, false );

        return true;
    }

    public function manage_profile( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['parts'], $params['parts']['meta_key'], $params['parts']['public_key'], $params['action'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        $params = dt_recursive_sanitize_array( $params );
        $action = sanitize_text_field( wp_unslash( $params['action'] ) );

        // manage
        $post_id = $params['parts']['post_id']; //has been verified in verify_rest_endpoint_permissions_on_post()
        if ( ! $post_id ){
            return new WP_Error( __METHOD__, 'Missing post record', [ 'status' => 400 ] );
        }

        switch ( $action ) {
            case 'get':
                return self::get_subscriptions( $post_id );
            case 'delete':
                return $this->delete_subscription_endpoint( $post_id, $params );
            case 'add':
                return $this->add_subscriptions( $post_id, $params );
            case 'change_times':
                return $this->change_times( $post_id, $params );
            case 'delete_times':
                return $this->delete_times( $post_id, $params );
            case 'delete_recurring_signup':
                return DT_Subscriptions::delete_recurring_signup( $post_id, $params['report_id'] ?? null );
            case 'update_recurring_signup':
                return DT_Subscriptions::update_recurring_signup( $post_id, $params['report_id'] ?? null, $params );
            default:
                return new WP_Error( __METHOD__, 'Missing valid action', [ 'status' => 400 ] );
        }
    }

    public static function get_subscriptions( $post_id ) {
        global $wpdb;
        $subs  = $wpdb->get_results( $wpdb->prepare( "
            SELECT * FROM $wpdb->dt_reports
            WHERE post_id = %s
            AND post_type = 'subscriptions'
            AND type = 'campaign_app'
        ", $post_id ), ARRAY_A );

        return $subs;
    }

    private function delete_subscription( $post_id, $report_id ){
        $sub = Disciple_Tools_Reports::get( $report_id, 'id' );
        if ( empty( $sub ) ){
            return new WP_Error( __METHOD__, 'Missing subscription record', [ 'status' => 400 ] );
        }

        if ( $sub['time_begin'] < time() ){
            return new WP_Error( __METHOD__, 'Cannot delete a commitment that is the past', [ 'status' => 400 ] );
        }
        $time_in_mins = ( $sub['time_end'] - $sub['time_begin'] ) / 60;
        $label = 'Commitment deleted: ' . gmdate( 'F d, Y @ H:i a', $sub['time_begin'] ) . ' UTC for ' . $time_in_mins . ' minutes';
        Disciple_Tools_Reports::delete( $report_id );
        dt_activity_insert([
            'action' => 'delete_subscription',
            'object_type' => $this->post_type,
            'object_subtype' => 'report',
            'object_note' => $label,
            'object_id' => $post_id
        ] );
        $subscription = DT_Posts::get_post( 'subscriptions', $post_id, true, false );
        $campaign_id = $subscription['campaigns'][0]['ID'] ?? null;
        if ( $campaign_id ){
            $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
            $start_date = $campaign['start_date']['timestamp'] ?? null;
            if ( $sub['time_begin'] < $start_date + 2 * DAY_IN_SECONDS ){
                $base_user = dt_get_base_user();
                DT_Posts::add_post_comment( 'subscriptions', $post_id, "@[$base_user->display_name]($base_user->ID) A prayer time close to the campaign start has been deleted", 'comment', [], false, false );
            }
        }
        do_action( 'subscriptions_removed', $campaign_id, $post_id );
        return true;
    }

    private function delete_subscription_endpoint( $post_id, $params ) {
        $this->delete_subscription( $post_id, $params['report_id'] );
        return self::get_subscriptions( $post_id );
    }

    private function add_subscriptions( $post_id, $params ){
        $post = DT_Posts::get_post( 'subscriptions', $post_id, true, false );
        if ( !isset( $post['campaigns'][0]['ID'] ) ){
            return false;
        }
        $campaign_id = $post['campaigns'][0]['ID'];

        foreach ( $params['selected_times'] as $time ){
            if ( !isset( $time['time'] ) ){
                continue;
            }
            $new_report = DT_Subscriptions::add_subscriber_time( $campaign_id, $post_id, $time['time'], $time['duration'], $time['grid_id'] ?? null, true );
            if ( !$new_report ){
                return new WP_Error( __METHOD__, 'Sorry, Something went wrong', [ 'status' => 400 ] );
            }
        }
        DT_Subscriptions::save_recurring_signups( $post_id, $campaign_id, $params['recurring_signups'] ?? [] );

        //Send an email with new subscriptions
        DT_Prayer_Campaigns_Send_Email::send_registration( $post_id, $campaign_id );

        do_action( 'subscriptions_added', $campaign_id, $post_id );
        return self::get_subscriptions( $params['parts']['post_id'] );
    }

    private function change_times( $post_id, $params ){
        $post = DT_Posts::get_post( 'subscriptions', $post_id, true, false );
        if ( !isset( $post['campaigns'][0]['ID'] ) ){
            return false;
        }
        if ( !isset( $params['offset'] ) ){
            return false;
        }

        $ids = dt_array_to_sql( $params['report_ids'] );
        global $wpdb;
        //phpcs:disable.
        //Cannot pass in array in prepare
        $wpdb->query( $wpdb->prepare( "
            UPDATE $wpdb->dt_reports
            SET time_begin = time_begin + %d, time_end = time_end + %d
            WHERE id IN ($ids)
        ", (int) $params["offset"], (int) $params["offset"] ) );
        //phpcs:enable

        return self::get_subscriptions( $params['parts']['post_id'] );
    }
    private function delete_times( $post_id, $params ){
        $post = DT_Posts::get_post( 'subscriptions', $post_id, true, false );
        $campaign_id = $post['campaigns'][0]['ID'] ?? null;
        if ( $campaign_id ){
            $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
            $start_date = $campaign['start_date']['timestamp'] ?? null;
            if ( time() > $start_date - 3 * DAY_IN_SECONDS && time() < $start_date + 3 * DAY_IN_SECONDS ){
                $base_user = dt_get_base_user();
                DT_Posts::add_post_comment( 'subscriptions', $post_id, "@[$base_user->display_name]($base_user->ID) Reoccurring prayer time deleted", 'comment', [], false, false );
            }
        }
        if ( !isset( $post['campaigns'][0]['ID'] ) ){
            return false;
        }
        foreach ( $params['report_ids'] as $id ){
            $this->delete_subscription( $post['ID'], $id );
        }
        do_action( 'subscriptions_removed', $campaign_id, $post_id );
        return self::get_subscriptions( $params['parts']['post_id'] );
    }


    public function allow_notifications( WP_REST_Request $request ){
        $params = $request->get_params();

        if ( ! isset( $params['parts'], $params['parts']['meta_key'], $params['parts']['public_key'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }
        $params = dt_recursive_sanitize_array( $params );

        $post_id = $params['parts']['post_id']; //has been verified in verify_rest_endpoint_permissions_on_post()
        if ( ! $post_id ){
            return new WP_Error( __METHOD__, 'Missing post record', [ 'status' => 400 ] );
        }

        return update_post_meta( $post_id, 'receive_prayer_time_notifications', !empty( $params['allowed'] ) );

    }

    public function campaign_info( WP_REST_Request $request ){
        //@todo this is a duplicate
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );
        $subscriber_id = $params['parts']['post_id']; //has been verified in verify_rest_endpoint_permissions_on_post()

        $campaign_id = $params['campaign_id'] ?? null;
        if ( empty( $campaign_id ) ){
            return new WP_Error( __METHOD__, 'Missing campaign id', [ 'status' => 400 ] );
        }
        $subscriber = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
        if ( is_wp_error( $subscriber ) ){
            return;
        }
        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );

        $minutes_committed = DT_Campaigns_Base::get_minutes_prayed_and_scheduled( $campaign_id );
        $current_commitments = DT_Time_Utilities::get_current_commitments( $campaign_id, 13 );
        $start = (int) DT_Time_Utilities::start_of_campaign_with_timezone( $campaign_id );
        $end = $campaign['end_date']['timestamp'] ?? null;
        if ( $end ){
            $end = (int) DT_Time_Utilities::end_of_campaign_with_timezone( $campaign_id, 3, $start );
        }
        $min_time_duration = DT_Time_Utilities::campaign_min_prayer_duration( $campaign_id );

        $lang = dt_campaign_get_current_lang(); //todo remove?
        dt_campaign_set_translation( $lang );


        //subscriber info
        $my_commitments_reports = self::get_subscriptions( $subscriber_id );
        $my_commitments = [];
        foreach ( $my_commitments_reports as $commitments_report ){
            $my_commitments[] = [
                'time_begin' => $commitments_report['time_begin'],
                'time_end' => $commitments_report['time_end'],
                'report_id' => $commitments_report['id'],
            ];
        }
        $my_recurring_signups = DT_Subscriptions::get_recurring_signups( $subscriber_id, $campaign_id );


        return [
            'campaign_id' => $campaign_id,
            'start_timestamp' => $start,
            'end_timestamp' => $end,
            'slot_length' => (int) $min_time_duration,
//            'status' => $record['status']['key'],
            'current_commitments' => $current_commitments,
            'minutes_committed' => $minutes_committed,
            'time_committed' => DT_Time_Utilities::display_minutes_in_time( $minutes_committed ),
            'enabled_frequencies' => $record['enabled_frequencies'] ?? [ 'daily', 'pick' ],
            'subscriber_info' => [
                'my_commitments' => $my_commitments,
                'my_recurring_signups' => $my_recurring_signups,
                'timezone' => $post['timezone'] ?? 'America/Chicago',
            ]
        ];
    }
}
new DT_Prayer_Subscription_Management_Magic_Link();
