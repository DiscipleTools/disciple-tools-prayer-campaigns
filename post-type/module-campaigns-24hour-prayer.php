<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Campaign_24Hour_Prayer extends DT_Module_Base {
    public $module = 'campaigns_24hour_prayer';
    public $post_type = 'campaigns';
    public $magic_link_root = 'campaign_app';
    public $magic_link_type = '24hour';

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        $module_enabled = dt_is_module_enabled( 'subscriptions_management', true );
        if ( !$module_enabled ){
            return;
        }
        parent::__construct();
        // register tiles if on details page
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 30, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 30, 2 );
        add_filter( 'dt_post_update_fields', [ $this, 'dt_post_update_fields' ], 20, 3 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );

        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }

    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === $this->post_type ){
            $fields['type']['default']['24hour'] = [
                'label' => __( '24/7 Fixed Dates Campaign', 'disciple-tools-prayer-campaigns' ),
                'description' => __( 'Cover a region with 24h prayer.', 'disciple-tools-prayer-campaigns' ),
                'visibility' => __( 'Collaborators', 'disciple-tools-prayer-campaigns' ),
                'color' => '#4CAF50',
            ];
        }
        $key_name = 'public_key';
        if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
            $key_name = DT_Magic_URL::get_public_key_meta_key( 'campaign_app', $this->magic_link_type );
        }
        $fields[$key_name] = [
            'name'   => 'Private Key',
            'description' => 'Private key for subscriber access',
            'type'   => 'hash',
            'default' => dt_create_unique_key(),
            'hidden' => true,
            'customizable' => false,
        ];
        return $fields;
    }


    public function dt_details_additional_tiles( $tiles, $post_type = '' ){
        if ( $post_type === 'campaigns' && !isset( $tiles['campaign_strings'] ) ){
            $tiles['campaign_strings'] = [
                'label' => __( 'Campaign Strings', 'disciple-tools-prayer-campaigns' ),
                'description' => "In this section: set Translation strings for custom elements. \r\n\r\n Translate core campaign strings here: https://poeditor.com/join/project?hash=yik32Z3OEf \r\n\r\n Setup instructions: https://disciple.tools/user-docs/prayer/24-7-prayer-campaign-setup/",
                'display_for' => [
                    'type' => [ $this->magic_link_type ],
                ]
            ];
        }
        if ( $post_type === 'campaigns' && ! isset( $tiles['campaign_communication'] ) ){
            $tiles['campaign_communication'] = [
                'label' => __( 'Campaign Communication', 'disciple-tools-prayer-campaigns' ),
                'description' => '',
                'display_for' => [
                    'type' => [ $this->magic_link_type ],
                ]
            ];
        }
        return $tiles;
    }

    public function dt_post_update_fields( $fields, $post_type, $post_id ){
        if ( $post_type === 'campaigns' ){
            foreach ( $fields as $field_key => $field_value ){
                if ( strpos( $field_key, 'hack-campaign_strings' ) === 0 ){
                    $temp = explode( '--', str_replace( 'hack-campaign_strings-', '', $field_key ) );
                    $string_key = $temp[0];
                    $lang_code = $temp[1];
                    $campaign_strings = get_post_meta( $post_id, 'campaign_strings', true ) ?? [];
                    if ( empty( $campaign_strings ) ){
                        $campaign_strings = [];
                    }
                    $campaign_strings[$lang_code]["$string_key"] = $field_value;
                    update_post_meta( $post_id, 'campaign_strings', $campaign_strings );
                    unset( $fields[$field_key] );
                }
            }
        }
        return $fields;
    }

    public function dt_details_additional_section( $section, $post_type ) {
        // test if campaigns post type and campaigns_app_module enabled
        if ( $post_type === $this->post_type ) {
            $record = DT_Posts::get_post( $post_type, get_the_ID() );
            if ( !isset( $record['type']['key'] ) || '24hour' !== $record['type']['key'] ){
                return;
            }
            $key_name = 'public_key';
            if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
                $key_name = DT_Magic_URL::get_public_key_meta_key( 'campaign_app', '24hour' );
            }
            if ( isset( $record[$key_name] ) ) {
                $key = $record[$key_name];
            } else {
                $key = dt_create_unique_key();
                update_post_meta( get_the_ID(), $key_name, $key );
            }

            if ( 'status' === $section ){
                $link = trailingslashit( site_url() ) . $this->magic_link_root . '/' . $this->magic_link_type . '/' . $key;
                ?>
                <div class="cell small-12 medium-4">
                    <div class="section-subheader">
                        <?php esc_html_e( 'Magic Link', 'disciple-tools-prayer-campaigns' ); ?>
                    </div>
                    <a class="button hollow small" target="_blank" href="<?php echo esc_html( $link ); ?>"><?php esc_html_e( 'View Components', 'disciple-tools-prayer-campaigns' ); ?></a>
                </div>
                <?php
            }

            if ( 'campaign_strings' === $section ){

                $post = DT_Posts::get_post( $post_type, GET_THE_ID() );
                $strings = $post['campaign_strings'] ?? [];
                $plugin_root = trailingslashit( dirname( __FILE__, 2 ) );
                $installed_translations = get_available_languages( $plugin_root . 'languages' );
                array_unshift( $installed_translations, 'en_US' );
                $dt_languages = dt_get_available_languages( true );
                ?>
                <ul class="tabs" data-tabs id="language-strings-tabs" style="display: block">
                    <?php
                    foreach ( $installed_translations as $index => $translation_key ) :
                        $code = str_replace( 'disciple-tools-prayer-campaigns-', '', $translation_key );
                        $label = isset( $dt_languages[$code] ) ? $dt_languages[$code]['native_name'] : ( $code === 'en_US' ? 'English' : $code );
                        ?>
                        <li class="tabs-title <?php echo esc_html( $code === 'en_US' ? 'is-active' : '' ); ?>"><a href="#lang-<?php echo esc_html( $code ); ?>" data-tabs-target="lang-<?php echo esc_html( $code ); ?>"><?php echo esc_html( $label ); ?></a></li>
                    <?php endforeach; ?>
                </ul>

                <div class="tabs-content" data-tabs-content="language-strings-tabs">
                    <?php
                    foreach ( $installed_translations as $index => $translation_key ) :
                        $code = str_replace( 'disciple-tools-prayer-campaigns-', '', $translation_key );
                        ?>
                        <div class="tabs-panel <?php echo esc_html( $code === 'en_US' ? 'is-active' : '' ); ?>" id="lang-<?php echo esc_html( $code ); ?>">
                            <label class="section-subheader" >
                                Extra content in sign up email
                                <input id="hack-campaign_strings-signup_content--<?php echo esc_html( $code ); ?>" type="text" class="text-input" value="<?php echo esc_html( $strings[$code]['signup_content'] ?? '' ); ?>">
                            </label>
                            <label class="section-subheader">
                                Extra content in prayer time reminder email
                                <input id="hack-campaign_strings-reminder_content--<?php echo esc_html( $code ); ?>" type="text" class="text-input" value="<?php echo esc_html( $strings[$code]['reminder_content'] ?? '' ); ?>">
                            </label>

                            <?php
                            if ( !isset( $record['start_date']['timestamp'], $record['end_date']['timestamp'] ) ) :
                                ?><p>A Start Date and End Date are required for the 24 hour campaign</p><?php
                                return;
                            endif;

                            $link = trailingslashit( site_url() ) . $this->magic_link_root . '/' . $this->magic_link_type . '/' . $key . '?lang=' . $code;
                            $shortcode = '[dt_campaign root="' . esc_html( $this->magic_link_root ) . '" type="' . esc_html( $this->magic_link_type ) . '" public_key="' . esc_html( $key ) . '" meta_key="' . esc_html( $key_name ) . '" post_id="' . esc_html( get_the_ID() ) . '" rest_url="' . esc_html( rest_url() ) . '" lang="' . esc_html( $code ) . '"]';
                            ?>

                            <div class="section-subheader">
                                <?php esc_html_e( 'Magic Links', 'disciple-tools-prayer-campaigns' ); ?>
                            </div>
                            <a class="button hollow small" onclick="copyToClipboard('<?php echo esc_html( $shortcode ) ?>')"><?php esc_html_e( 'Copy Shortcode', 'disciple-tools-prayer-campaigns' ); ?></a>
                            <a class="button hollow small" onclick="copyToClipboard('<?php echo esc_url( $link ) ?>')"><?php esc_html_e( 'Copy Link', 'disciple-tools-prayer-campaigns' ); ?></a>
                            <a class="button hollow small" target="_blank" href="<?php echo esc_html( $link ); ?>"><?php esc_html_e( 'Open Link', 'disciple-tools-prayer-campaigns' ); ?></a>

                        </div>
                    <?php endforeach; ?>
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
                </script>
                <?php
            }

            if ( 'campaign_communication' === $section ){
                $campaign_id = get_the_ID();
                $prayer_time_reminder_emails_sent = DT_Subscriptions::get_number_of_notification_emails_sent( $campaign_id );
                $to_send = DT_Posts::list_posts( 'subscriptions', [ 'tags' => [ 'pre-signup' ], 'campaigns' => [ $campaign_id ] ] );

                ?>
                <div class="cell small-12 medium-4">
                    <div class="section-subheader">
                        <?php esc_html_e( 'Magic Link', 'disciple-tools-prayer-campaigns' ); ?>
                    </div>
                    <p>Prayer Time Reminder Emails sent: <?php echo esc_html( $prayer_time_reminder_emails_sent ); ?></p>
                    <div class="section-subheader">
                        Email to pre-signup subscribers
                    </div>
                    <p>(those who signed up with no time slots)</p>
                    <p>Emails to send: <span id="emails_to_send_count"><?php echo esc_html( $to_send['total'] ); ?></span>
                    <?php if ( $to_send['total'] > 0 ) :
                        $disabled = $record['status']['key'] !== 'active'
                        ?>
                        <button id="send_email_to_pre_subscribers" class="button hollow small loader" style="margin: 0 10px" <?php disabled( $disabled ) ?>>Send now</button>
                        <?php if ( $disabled ) :?>
                            <br><span>Change campaign status to <strong>active</strong> to send emails (and refresh)</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    </p>
                    <p>
                        <?php
                        $prayer_campaign_email = get_option( 'dt_prayer_campaign_email' );
                        if ( empty( $prayer_campaign_email ) ){
                            $prayer_campaign_email = self::default_email_address();
                        }
                        ?>
                        Prayer Campaign Emails are sent from <?php echo esc_html( $prayer_campaign_email ); ?>. <a target="_blank"
                            href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns' ) ); ?>">Configure</a>
                    </p>
                </div>
                <script>
                jQuery(document).ready(function($) {
                    $('#send_email_to_pre_subscribers').on("click", function (){
                        $(this).addClass('loading')
                        window.makeRequest( 'GET', '24hour/send-pre-signup-email', { campaign_id: '<?php echo get_the_ID() ?>' }, 'campaign_app/v1')
                        .done(function(data){
                            if( data.emails_sent ){
                                $('#emails_to_send_count').html('0')
                                $('#send_email_to_pre_subscribers').hide()
                                $(this).removeClass('loading')
                            }
                        })
                    })
                })
                </script>
                <?php
            }
            ?>
            <?php
        } // end if campaigns and enabled
    }
    private function default_email_address(): string {
        $default_addr = apply_filters( 'wp_mail_from', '' );
        if ( empty( $default_addr ) ) {
            // Get the site domain and get rid of www.
            $sitename = wp_parse_url( network_home_url(), PHP_URL_HOST );
            if ( 'www.' === substr( $sitename, 0, 4 ) ) {
                $sitename = substr( $sitename, 4 );
            }
            $default_addr = 'wordpress@' . $sitename;
        }
        return $default_addr;
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
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->magic_link_root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'.$this->magic_link_type . '/access_account', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'access_account' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->magic_link_root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'.$this->magic_link_type . '/campaign_info', [
                [
                    'methods'  => 'GET',
                    'callback' => [ $this, 'campaign_info' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->magic_link_root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        /**
         * route for sending pre sign up email
         */
        register_rest_route(
            $namespace, '/'.$this->magic_link_type . '/send-pre-signup-email', [
                [
                    'methods'  => 'GET',
                    'callback' => [ $this, 'send_pre_sign_up_email' ],
                    'permission_callback' => function ( WP_REST_Request $request ){
                        $params = $request->get_params();
                        if ( !isset( $params['campaign_id'] ) ){
                            return false;
                        }
                        return DT_Posts::can_update( 'campaigns', $params['campaign_id'] );
                    }
                ],
            ]
        );
    }

    public function send_pre_sign_up_email( WP_REST_Request $request ){
        $params = $request->get_params();
        if ( !isset( $params['campaign_id'] ) ){
             new WP_Error( __METHOD__, 'Missing campaign id', [ 'status' => 400 ] );
        }
        $campaign_post_id = sanitize_text_field( wp_unslash( $params['campaign_id'] ) );
        return DT_Prayer_Campaigns_Send_Email::send_pre_sign_up_email( $campaign_post_id );

    }

    public function create_subscription( WP_REST_Request $request ) {
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
        if ( ! isset( $params['selected_times'] ) ) {
            return new WP_Error( __METHOD__, 'Missing times and locations', [ 'status' => 400 ] );
        }
        if ( ! isset( $params['timezone'] ) || empty( $params['timezone'] ) ) {
            return new WP_Error( __METHOD__, 'Missing timezone', [ 'status' => 400 ] );
        }

        $email = $params['email'];
        $title = $params['name'];
        if ( empty( $title ) ) {
            $title = $email;
        }

        $receive_prayer_time_notifications = isset( $params['receive_prayer_time_notifications'] ) && !empty( $params['receive_prayer_time_notifications'] );

        $existing_posts = DT_Posts::list_posts( 'subscriptions', [
            'campaigns' => [ $params['campaign_id'] ],
            'contact_email' => [ $email ]
        ], false );

        if ( (int) $existing_posts['total'] === 1 ){
            $subscriber_id = $existing_posts['posts'][0]['ID'];
            $added_times = DT_Subscriptions::add_subscriber_times( $params['campaign_id'], $subscriber_id, $params['selected_times'] );
            if ( is_wp_error( $added_times ) ){
                return $added_times;
            }
        } else {
            $lang = 'en_US';
            if ( isset( $params['parts']['lang'] ) ){
                $lang = $params['parts']['lang'];
            }
            $subscriber_id = DT_Subscriptions::create_subscriber( $params['campaign_id'], $email, $title, $params['selected_times'], [
                'receive_prayer_time_notifications' => $receive_prayer_time_notifications,
                'timezone' => $params['timezone'],
                'lang' => $lang,
            ]);
            if ( is_wp_error( $subscriber_id ) ){
                return new WP_Error( __METHOD__, 'Could not create record', [ 'status' => 400 ] );
            }
        }

        if ( !empty( $params['selected_times'] ) ){
            $email_sent = DT_Prayer_Campaigns_Send_Email::send_registration( $subscriber_id, $params['campaign_id'] );
        } else {
            $email_sent = DT_Prayer_Campaigns_Send_Email::send_pre_registration( $subscriber_id, $params['campaign_id'] );
        }

        if ( !$email_sent ){
            return new WP_Error( __METHOD__, 'Could not send email confirmation', [ 'status' => 400 ] );
        }

        return true;
    }

    public function access_account( WP_REST_Request $request ) {
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );

        $post_id = $params['parts']['post_id']; //has been verified in verify_rest_endpoint_permissions_on_post()

        if ( !$post_id || !isset( $params['campaign_id'] ) || $post_id !== $params['campaign_id'] ){
            return new WP_Error( __METHOD__, 'Missing post record', [ 'status' => 400 ] );
        }

        // @todo insert email reset link
        if ( ! isset( $params['email'], $params['campaign_id'] ) ) {
            return new WP_Error( __METHOD__, 'Missing required parameter.', [ 'status' => 400 ] );
        }

        DT_Prayer_Campaigns_Send_Email::send_account_access( $params['campaign_id'], $params['email'] );

        return $params;
    }

    public function campaign_info( WP_REST_Request $request ){
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );
        $post_id = $params['parts']['post_id']; //has been verified in verify_rest_endpoint_permissions_on_post()


        $record = DT_Posts::get_post( 'campaigns', $post_id, true, false );
        if ( is_wp_error( $record ) ){
            return;
        }
        $coverage_levels = DT_Campaigns_Base::query_coverage_levels_progress( $post_id );
        $number_of_time_slots = DT_Campaigns_Base::query_coverage_total_time_slots( $post_id );

        $coverage_percentage = $coverage_levels[0]['percent'];
        $second_level = isset( $coverage_levels[1]['percent'] ) ? $coverage_levels[1]['percent'] : '';

        $min_time_duration = 15;
        if ( isset( $record['min_time_duration']['key'] ) ){
            $min_time_duration = $record['min_time_duration']['key'];
        }
        $minutes_committed = 0;
        foreach ( $coverage_levels as $level ){
            $minutes_committed += $level['blocks_covered'] * $min_time_duration;
        }

        $locale = $params['parts']['lang'] ?: 'en_US';
        $grid_id = 1;
        if ( isset( $record['location_grid'] ) && ! empty( $record['location_grid'] ) ) {
            $grid_id = $record['location_grid'][0]['id'];
        }
        $current_commitments = DT_Time_Utilities::get_current_commitments( $post_id );
        $min_time_duration = DT_Time_Utilities::campaign_min_prayer_duration( $post_id );
        $field_settings = DT_Posts::get_post_field_settings( 'campaigns' );

        $return = [
            'coverage_levels' => $coverage_levels,
            'number_of_time_slots' => $number_of_time_slots,
            'coverage_percentage' => $coverage_percentage,
            'campaign_id' => $post_id,
            'campaign_grid_id' => $grid_id,
            'translations' => [],
            'start_timestamp' => (int) DT_Time_Utilities::start_of_campaign_with_timezone( $post_id ),
            'end_timestamp' => (int) DT_Time_Utilities::end_of_campaign_with_timezone( $post_id ) + 86400,
            'current_commitments' => $current_commitments,
            'slot_length' => (int) $min_time_duration,
            'second_level' => $second_level,
            'duration_options' => $field_settings['duration_options']['default'],
            'status' => $record['status']['key'],
            'minutes_committed' => $minutes_committed,
            'prayers_count' => sizeof( $record['subscriptions'] ?? [] ),
        ];
        return apply_filters( 'prayer_campaign_info_response', $return );
    }

}


