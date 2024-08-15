<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DT_Prayer_Subscription_Management_Magic_Link extends DT_Magic_Url_Base {

    public $post_type = 'subscriptions';
    public $page_title = 'My Prayer Commitments';

    public $magic = false;
    public $parts = [];
    public $root = 'subscriptions_app'; // define the root of the url {yoursite}/root/type/key/action
    public $type = 'manage'; // define the type
    public $type_name = 'Subscriptions';
    public $type_actions = [
        '' => 'Manage',
        'download_calendar' => 'Download Calendar',
        'activate' => 'Manage',
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
        $this->page_title = __( 'My Prayer Commitments', 'disciple-tools-prayer-campaigns' );

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
        DT_Porch_Selector::instance();

    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js = [
            'jquery',
        ];
        $allowed_js[] = 'dt_campaign_core';
        $allowed_js[] = 'luxon';
        $allowed_js[] = 'dt_subscription_js';
        $allowed_js[] = 'campaign_css';
        $allowed_js[] = 'campaign_components';
        $allowed_js[] = 'campaign_component_sign_up';
        $allowed_js[] = 'campaign_components';
        $allowed_js[] = 'campaign_component_css';
        $allowed_js[] = 'toastify-js';
        return $allowed_js;
    }
    public function dt_magic_url_base_allowed_css( $allowed_js ) {
        $allowed_js = [];
        $allowed_js[] = 'dt_subscription_css';
        $allowed_js[] = 'toastify-js-css';
        return $allowed_js;
    }

    public function wp_enqueue_scripts(){
        $post = DT_Posts::get_post( 'subscriptions', $this->parts['post_id'], true, false );
        if ( is_wp_error( $post ) ) {
            return $post;
        }
        if ( isset( $_GET['campaign'] ) ){
            $campaign_id = sanitize_key( wp_unslash( $_GET['campaign'] ) );
        }
        if ( empty( $campaign_id ) ){
            $campaign_id = $post['campaigns'][0]['ID'];
        }
        dt_campaigns_register_scripts( $this->parts, $campaign_id );


        wp_enqueue_style( 'dt_subscription_css', DT_Prayer_Campaigns::instance()->plugin_dir_url . 'magic-links/subscription-management/subscription-management.css', [], filemtime( DT_Prayer_Campaigns::instance()->plugin_dir_path . 'magic-links/subscription-management/subscription-management.css' ) );
        wp_enqueue_script( 'dt_subscription_js', DT_Prayer_Campaigns::instance()->plugin_dir_url . 'magic-links/subscription-management/subscription-management.js', [ 'jquery', 'dt_campaign_core' ], filemtime( DT_Prayer_Campaigns::instance()->plugin_dir_path . 'magic-links/subscription-management/subscription-management.js' ), true );

        $lang = $post['lang'] ?: 'en_US';

        wp_localize_script(
            'dt_subscription_js', 'subscription_page_data', [
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'name' => get_the_title( $this->parts['post_id'] ),
                'campaign_id' => (int) $campaign_id,
                'languages' => DT_Campaign_Languages::get_enabled_languages( $campaign_id ),
                'current_language' => $lang
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
        $campaign_url = DT_Campaign_Landing_Settings::get_landing_page_url( $campaign_id );
        $calendar_timezone = $post['timezone'];
        $calendar_dtstamp = gmdate( 'Ymd' ).'T'. gmdate( 'His' ) . 'Z';

        $calendar_description = '';
        if ( isset( $campaign['campaign_strings'][$locale]['reminder_content'] ) ){
            $calendar_description = $campaign['campaign_strings'][$locale]['reminder_content'];
        } elseif ( isset( $campaign['campaign_strings']['en_US']['reminder_content'] ) ){
            $calendar_description = $campaign['campaign_strings']['en_US']['reminder_content'];
        }

        $my_commitments_reports = DT_Subscriptions::get_subscriber_prayer_times( $campaign_id, $post_id );
        $my_recurring_signups = DT_Subscriptions::get_recurring_signups( $post_id, $campaign_id );

        $my_commitments = [];

        // Package $my_commitments accordingly, based on overall commitment type.
        foreach ( $my_commitments_reports as $commitments_report ) {
            if ( $commitments_report['type'] === 'selected_time' ) {

                $dt_start = new DateTime();
                $dt_start->setTimezone( new DateTimeZone( $calendar_timezone ) );
                $dt_start->setTimestamp( $commitments_report['time_begin'] );
                $calendar_format = $dt_start->format( 'Ymd' ) . 'T' . $dt_start->format( 'His' );

                $dt_end = new DateTime();
                $dt_end->setTimezone( new DateTimeZone( $calendar_timezone ) );
                $dt_end->setTimestamp( $commitments_report['time_end'] );
                $calendar_format_end = $dt_end->format( 'Ymd' ) . 'T' . $dt_end->format( 'His' );

                $my_commitments[] = [
                    'time_begin' => $calendar_format,
                    'time_end' => $calendar_format_end,
                    'time_duration' => self::get_clean_duration( $commitments_report['time_end'], $commitments_report['time_begin'] ),
                    'location' => $commitments_report['label'] ?? '',
                ];
            }
        }

        foreach ( $my_recurring_signups as $recurring_signup ) {
            $type = ( $recurring_signup['type'] === 'daily' ) ? 'DAILY' : 'WEEKLY';
            $time_end = $recurring_signup['first'] + ( $recurring_signup['duration'] * 60 );

            $dt_start = new DateTime();
            // Set a non-default timezone if needed
            $dt_start->setTimezone( new DateTimeZone( $calendar_timezone ) );
            $dt_start->setTimestamp( $recurring_signup['first'] );
            $calendar_format = $dt_start->format( 'Ymd' ) . 'T' . $dt_start->format( 'His' );

            $dt_end = new DateTime();
            $dt_end->setTimezone( new DateTimeZone( $calendar_timezone ) );
            $dt_end->setTimestamp( $time_end );
            $calendar_format_end = $dt_end->format( 'Ymd' ) . 'T' . $dt_end->format( 'His' );

            $my_commitments[] = [
                'time_begin' => $calendar_format,
                'time_end' => $calendar_format_end,
                'rrule' => 'FREQ='. $type .';UNTIL=' . gmdate( 'Ymd', $recurring_signup['last'] ) . 'T'. gmdate( 'His', $recurring_signup['last'] ) . 'Z',
            ];
        }

        $content = "BEGIN:VCALENDAR\r\n";
        $content .= "VERSION:2.0\r\n";
        $content .= "PRODID:-//disciple.tools\r\n";
        $content .= "CALSCALE:GREGORIAN\r\n";
        $content .= 'TZID:' . esc_html( $calendar_timezone ) . "\r\n";
        $content .= 'X-WR-TIMEZONE:' . esc_html( $calendar_timezone ) . "\r\n";

        foreach ( $my_commitments as $mc ) {
            $calendar_uid = md5( uniqid( mt_rand(), true ) ) . '@disciple.tools';

            $content .= "BEGIN:VEVENT\r\n";
            $content .= 'TZID:' . esc_html( $calendar_timezone ) . "\r\n";
            $content .= 'UID:' . esc_html( $calendar_uid ) . "\r\n";
            $content .= 'DTSTAMP:' . esc_html( $calendar_dtstamp ) . "\r\n";
            $content .= 'SUMMARY:' . esc_html( $calendar_title ) . "\r\n";
            $content .= 'DTSTART;TZID=' . esc_html( $calendar_timezone ) . ':' . esc_html( $mc['time_begin'] ) . "\r\n";
            $content .= 'DTEND;TZID=' . esc_html( $calendar_timezone ) . ':' . esc_html( $mc['time_end'] ) . "\r\n";

            if ( isset( $mc['rrule'] ) ) {
                $content .= 'RRULE:' . esc_html( $mc['rrule'] ) . "\r\n";
            }

            $content .= 'DESCRIPTION:' . esc_html( $calendar_description ) . "\r\n";
            $content .= 'LOCATION:' . esc_html( $campaign_url . '/list' ) . "\r\n";
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
        dt_campaign_set_translation( $post['lang'] );
        if ( !isset( $post['campaigns'][0]['ID'] ) ){
            return false;
        }
        if ( isset( $_GET['campaign'] ) ){
            $campaign_id = sanitize_key( wp_unslash( $_GET['campaign'] ) );
        }
        if ( empty( $campaign_id ) ){
            $campaign_id = $post['campaigns'][0]['ID'];
        }

        //cannot manage a subscription that has no campaign
        if ( empty( $campaign_id ) ){
            $this->error_body();
            exit;
        }


        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        $title = DT_Porch_Settings::get_field_translation( 'name', $post['lang'] ?? null, $campaign_id );
        if ( is_wp_error( $campaign ) ) {
            return $campaign;
        }

        $color = DT_Campaign_Landing_Settings::get_campaign_color( $campaign_id );
        $campaign_url = DT_Campaign_Landing_Settings::get_landing_page_url( $campaign_id );

        $url_path = dt_get_url_path();
        $account_verified = strpos( $url_path, 'verified' ) !== false;

        ?>
        <style>
            :root {
                --cp-color: <?php echo esc_html( $color ) ?>;
                --cp-color-dark: color-mix(in srgb, var(--cp-color), #000 10%);
                --cp-color-light: color-mix(in srgb, var(--cp-color), #fff 70%);
            }
            .nav-bar {
                justify-content: center;
                display: flex;
                background-color: var(--cp-color);
                color: white;
            }
            .nav-bar h3 {
                margin: 0;
            }
            .nav-bar button {
                background-color: transparent;
                border: none;
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
            .verified-section {
                background-color: #1eb858;
                color: white;
                text-align: center;
                padding: 20px;
                margin: 1rem;
                border-radius: 10px;

            }
        </style>
        <!-- header -->
        <div class="nav-bar" style="">
            <div style="padding:10px">
                <h3><?php echo esc_html( $title ); ?></h3>
            </div>
            <div style="display: flex;">
                <button class="active" data-show="prayer-times"><?php esc_html_e( 'Prayer Commitments', 'disciple-tools-prayer-campaigns' ); ?></button>
                <button data-show="profile"><?php esc_html_e( 'Account', 'disciple-tools-prayer-campaigns' ); ?></button>
            </div>
        </div>

        <?php if ( $account_verified ) : ?>
            <div class="verified-section">
                <h1><?php esc_html_e( 'Account activated!', 'disciple-tools-prayer-campaigns' ); ?></h1>
                <h3><?php esc_html_e( 'Here you can manage your prayer commitments and account settings.', 'disciple-tools-prayer-campaigns' ); ?></h3>
            </div>
        <?php endif; ?>

        <div id="wrapper">
            <!-- links -->
            <div style="margin: 10px; text-align: center">
                <?php esc_html_e( 'Links', 'disciple-tools-prayer-campaigns' ); ?>:
                <a href="<?php echo esc_url( $campaign_url ) ?>"><?php esc_html_e( 'Home', 'disciple-tools-prayer-campaigns' ); ?></a>
                <a href="<?php echo esc_url( $campaign_url ) ?>/list"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'prayer_fuel_name' ) ) ?></a>
                <a href="<?php echo esc_url( $campaign_url ) ?>/stats"> <?php esc_html_e( 'Stats', 'disciple-tools-prayer-campaigns' ); ?></a>
            </div>

            <div id="prayer-times" class="display-panel" style="display: block">

                <div style="display: flex; gap: 20px; justify-content: space-around; flex-wrap: wrap-reverse; flex-direction: row">
                    <!-- my times -->
                    <div>
                        <h2><?php esc_html_e( 'My Prayer Commitments', 'disciple-tools-prayer-campaigns' ); ?></h2>
                        <campaign-subscriptions></campaign-subscriptions>
                    </div>

                    <!-- calendar -->
                    <div>
                        <div style="display: flex; align-items: center; gap: 10px">
                            <h2>
                                <?php esc_html_e( 'My Calendar', 'disciple-tools-prayer-campaigns' ); ?>
                            </h2>
                            <a class="clear-button" target="_blank" href="<?php echo esc_attr( self::get_download_url() ); ?>">
                                <?php esc_html_e( 'Download Calendar', 'disciple-tools-prayer-campaigns' ); ?>
                            </a>
                        </div>
                        <my-calendar>

                        </my-calendar>
                    </div>
                </div>

                <div style="background-color: white; margin: 150px 50px 50px 50px">
                    <h2 style="text-align: center"><?php esc_html_e( 'Sign up for more prayer', 'disciple-tools-prayer-campaigns' ); ?></h2>
                    <campaign-sign-up
                        already_signed_up="true"
                    ></campaign-sign-up>
                </div>

                <!-- Extra setting depending on the campaign type -->
                <div>
                    <?php do_action( 'dt_subscription_management_extra' ) ?>
                </div>
            </div>
            <div id="profile" class="display-panel" style="display: none">
                  <cp-profile></cp-profile>
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
            $namespace, '/'. $this->type . '/update-profile', [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'update_profile' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
    }

    public function update_profile( WP_REST_Request $request ){
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );
        $new_values = $params['updates'];
        $updates = [];
        $subscriber_id = $params['parts']['post_id'];
        $subscriber = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
        if ( isset( $new_values['name'] ) ){
            $updates['name'] = $new_values['name'];
        }
        if ( isset( $new_values['email'] ) ){
            $existing_email = $subscriber['contact_email'][0];
            $updates['contact_email'] = [ [ 'key' => $existing_email['key'], 'value' => $new_values['email'] ] ];
        }
        if ( isset( $new_values['timezone'] ) ){
            $updates['timezone'] = $new_values['timezone'];
        }
        if ( isset( $new_values['language'] ) ){
            $updates['lang'] = $new_values['language'];
        }
        if ( isset( $new_values['receive_prayer_time_notifications'] ) ){
            $updates['receive_prayer_time_notifications'] = !empty( $new_values['receive_prayer_time_notifications'] );
        }
        $updated = DT_Posts::update_post( 'subscriptions', $subscriber_id, $updates, true, false );
        return true;
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
        $action = $params['action'];
        $campaign_id = $params['campaign_id'] ?? null;

        // manage
        $post_id = $params['parts']['post_id']; //has been verified in verify_rest_endpoint_permissions_on_post()
        if ( ! $post_id ){
            return new WP_Error( __METHOD__, 'Missing post record', [ 'status' => 400 ] );
        }

        switch ( $action ) {
            case 'get':
                return DT_Subscriptions::get_subscriber_prayer_times( $campaign_id, $post_id );
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
        return $this->delete_subscription( $post_id, $params['report_id'] );
    }

    private function add_subscriptions( $subscriber_id, $params ){
        $post = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
        if ( !isset( $post['campaigns'][0]['ID'] ) ){
            return false;
        }
        $campaign_id = $post['campaigns'][0]['ID'];

        foreach ( $params['selected_times'] as $time ){
            if ( !isset( $time['time'] ) ){
                continue;
            }
            $new_report = DT_Subscriptions::add_subscriber_time( $campaign_id, $subscriber_id, $time['time'], $time['duration'], $time['grid_id'] ?? null, 0 );
            if ( !$new_report ){
                return new WP_Error( __METHOD__, 'Sorry, Something went wrong', [ 'status' => 400 ] );
            }
        }
        DT_Subscriptions::save_recurring_signups( $subscriber_id, $campaign_id, $params['recurring_signups'] ?? [] );

        //Send an email with new subscriptions
        DT_Prayer_Campaigns_Send_Email::send_registration( $subscriber_id, $campaign_id, $params['recurring_signups'] ?? [] );

        do_action( 'subscriptions_added', $campaign_id, $subscriber_id );
        return DT_Subscriptions::get_subscriber_prayer_times( $campaign_id, $subscriber_id );
    }

    private function change_times( $post_id, $params ){
        $post = DT_Posts::get_post( 'subscriptions', $post_id, true, false );
        if ( !isset( $post['campaigns'][0]['ID'] ) ){
            return false;
        }
        if ( !isset( $params['offset'] ) ){
            return false;
        }
        if ( !isset( $params['time'] ) ){
            return false;
        }

        global $wpdb;
        $wpdb->query( $wpdb->prepare( "
            UPDATE $wpdb->dt_reports
            SET time_begin = time_begin + %d, time_end = time_end + %d
            WHERE post_type = 'subscriptions'
            AND type = 'campaign_app'
            AND post_id = %d
            AND value = %d
            AND time_begin >= %d
        ", (int) $params['offset'], (int) $params['offset'], (int) $post_id, (int) $params['report_id'], time() ) );

        $report = Disciple_Tools_Reports::get( $params['report_id'], 'id' );
        $report['payload'] = maybe_unserialize( $report['payload'] );
        $report['payload']['time'] = $params['time'];
        $report['time_begin'] += (int) $params['offset'];
        $report['time_end'] += (int) $params['offset'];
        Disciple_Tools_Reports::update( $report );

        return true;
    }
    private function delete_times( $subscriber_id, $params ){
        $post = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
        $campaign_id = $post['campaigns'][0]['ID'] ?? null;
        if ( $campaign_id ){
            $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
            $start_date = $campaign['start_date']['timestamp'] ?? null;
            if ( time() > $start_date - 3 * DAY_IN_SECONDS && time() < $start_date + 3 * DAY_IN_SECONDS ){
                $base_user = dt_get_base_user();
                DT_Posts::add_post_comment( 'subscriptions', $subscriber_id, "@[$base_user->display_name]($base_user->ID) Reoccurring prayer time deleted", 'comment', [], false, false );
            }
        }
        if ( !isset( $post['campaigns'][0]['ID'] ) ){
            return false;
        }
        foreach ( $params['report_ids'] as $id ){
            $this->delete_subscription( $post['ID'], $id );
        }
        do_action( 'subscriptions_removed', $campaign_id, $subscriber_id );
        return DT_Subscriptions::get_subscriber_prayer_times( $campaign_id, $subscriber_id );
    }

    public static function campaign_info( WP_REST_Request $request ){
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
        $my_commitments = DT_Subscriptions::get_subscriber_prayer_times( $campaign_id, $subscriber_id );

        $my_recurring_signups = DT_Subscriptions::get_recurring_signups( $subscriber_id, $campaign_id );


        return [
            'campaign_id' => (int) $campaign_id,
            'start_timestamp' => $start,
            'end_timestamp' => $end,
            'slot_length' => (int) $min_time_duration,
            'current_commitments' => $current_commitments,
            'minutes_committed' => (int) $minutes_committed,
            'time_committed' => DT_Time_Utilities::display_minutes_in_time( $minutes_committed ),
            'enabled_frequencies' => $campaign['enabled_frequencies'] ?? [ 'daily', 'pick' ],
            'subscriber_info' => [
                'my_commitments' => $my_commitments,
                'my_recurring_signups' => $my_recurring_signups,
                'timezone' => $subscriber['timezone'] ?? 'America/Chicago',
                'receive_prayer_time_notifications' => $subscriber['receive_prayer_time_notifications'] ?? false,
                'email' => $subscriber['contact_email'][0]['value'] ?? '',
                'name' => $subscriber['name'] ?? '',
            ]
        ];
    }
}
new DT_Prayer_Subscription_Management_Magic_Link();
