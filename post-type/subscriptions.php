<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class DT_Subscriptions_Base
 * Load the core post type hooks into the Disciple.Tools system
 */
class DT_Subscriptions_Base {

    /**
     * Define post type variables
     * @var string
     */
    public $post_type = 'subscriptions';
    public $module = 'subscriptions_base';
    public $single_name = 'Subscription';
    public $plural_name = 'Subscriptions';
    public static function post_type(){
        return 'subscriptions';
    }

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {

        //setup post type
        add_action( 'after_setup_theme', [ $this, 'after_setup_theme' ], 100 );
        add_filter( 'dt_set_roles_and_permissions', [ $this, 'dt_set_roles_and_permissions' ], 20, 1 ); //after contacts
        add_filter( 'desktop_navbar_menu_options', [ $this, 'desktop_navbar_menu_options' ], 1000, 1 );

        //setup tiles and fields
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 20, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 20, 2 );

//        add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );

        // hooks
        add_action( 'post_connection_added', [ $this, 'post_connection_added' ], 10, 4 );
        add_action( 'post_connection_removed', [ $this, 'post_connection_removed' ], 10, 4 );
//        add_action( "dt_comment_created", [ $this, "dt_comment_created" ], 10, 4 );
        add_filter( 'dt_post_update_fields', [ $this, 'dt_post_update_fields' ], 10, 3 );
        add_filter( 'dt_post_create_fields', [ $this, 'dt_post_create_fields' ], 10, 2 );
        add_action( 'dt_post_created', [ $this, 'dt_post_created' ], 10, 3 );

        //list
        add_filter( 'dt_user_list_filters', [ $this, 'dt_user_list_filters' ], 150, 2 );
        add_filter( 'dt_filter_access_permissions', [ $this, 'dt_filter_access_permissions' ], 20, 2 );

        add_action( 'rest_api_init', [ $this, 'rest_api_init' ] );

    }

    public function after_setup_theme(){
        if ( class_exists( 'Disciple_Tools_Post_Type_Template' ) ) {
            new Disciple_Tools_Post_Type_Template( $this->post_type, $this->single_name, $this->plural_name );
        }
    }


    /**
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/roles-permissions.md#rolesd
     */
    public function dt_set_roles_and_permissions( $expected_roles ){

        if ( !isset( $expected_roles['campaigns_admin'] ) ){
            $expected_roles['campaigns_admin'] = [
                'label' => 'Campaigns Admin',
                'description' => 'Campaigns admin can administrate the prayer campaigns and subscriptions section',
                'permissions' => [
                    'access_disciple_tools' => true,
                ]
            ];
        }
        $subscriptions_permissions = [
            'access_'.$this->post_type => true,
            'create_'.$this->post_type => true,
            'update_any_'.$this->post_type => true,
            'view_any_'.$this->post_type => true,
        ];
        $expected_roles['campaigns_admin']['permissions'] = array_merge( $expected_roles['campaigns_admin']['permissions'], $subscriptions_permissions );

        if ( isset( $expected_roles['administrator'] ) ){
            $expected_roles['administrator']['permissions']['access_' . $this->post_type ] = true;
            $expected_roles['administrator']['permissions']['create_' . $this->post_type] = true;
            $expected_roles['administrator']['permissions']['view_any_'.$this->post_type ] = true;
            $expected_roles['administrator']['permissions']['update_any_'.$this->post_type ] = true;
        }

        return $expected_roles;
    }

    /**
     * Modifies the top tabs to add subscriptions under the campaigns drop down
     * @param $tabs
     * @return mixed
     */
    public function desktop_navbar_menu_options( $tabs ) {
        if ( isset( $tabs[$this->post_type] ) ) {
            unset( $tabs['subscriptions'] );
        }
        if ( isset( $tabs['campaigns'] ) ){
            $tabs['campaigns']['submenu']['campaigns'] = $tabs['campaigns'];
            $tabs['campaigns']['submenu']['subscriptions'] = [
                'link' => site_url( '/subscriptions/' ),
                'label' => __( 'Subscriptions', 'disciple-tools-prayer-campaigns' ),
                'icon' => '',
                'hidden' => false,
            ];
        }
        return $tabs;
    }

    /**
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/fields.md
     */
    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === $this->post_type ){
            /**
             * Basic framework fields used by post-type base
             * recommended to leave these alone
             */


            $fields['status'] = [
                'name'        => __( 'Status', 'disciple-tools-prayer-campaigns' ),
                'description' => _x( 'Set the current status.', 'field description', 'disciple-tools-prayer-campaigns' ),
                'type'        => 'key_select',
                'default'     => [
                    'inactive' => [
                        'label' => __( 'Inactive', 'disciple-tools-prayer-campaigns' ),
                        'description' => _x( 'No longer active.', 'field description', 'disciple-tools-prayer-campaigns' ),
                        'color' => '#F43636'
                    ],
                    'active'   => [
                        'label' => __( 'Active', 'disciple-tools-prayer-campaigns' ),
                        'description' => _x( 'Is active.', 'field description', 'disciple-tools-prayer-campaigns' ),
                        'color' => '#4CAF50'
                    ],
                ],
                'order' => 1,
                'tile'     => '',
                'icon' => get_template_directory_uri() . '/dt-assets/images/status.svg',
                'default_color' => '#366184',
                'show_in_table' => 10,
            ];


            $fields['contact_email'] = [
                'name' => __( 'Email', 'disciple-tools-prayer-campaigns' ),
                'icon' => get_template_directory_uri() . '/dt-assets/images/email.svg',
                'type' => 'communication_channel',
                'tile' => 'details',
                'customizable' => false,
                'in_create_form' => true,
                'show_in_table' => 20
            ];
            $fields['contact_phone'] = [
                'name' => __( 'Phone', 'disciple-tools-prayer-campaigns' ),
                'description' => __( 'Subscriber phone number', 'disciple-tools-prayer-campaigns' ),
                'icon' => get_template_directory_uri() . '/dt-assets/images/phone.svg',
                'type' => 'communication_channel',
                'tile' => 'details',
                'customizable' => false,
                'in_create_form' => false,
            ];
            $fields['languages'] = [
                'name' => __( 'Languages', 'disciple-tools-prayer-campaigns' ),
                'description' => __( 'Subscriber preferred language', 'disciple-tools-prayer-campaigns' ),
                'type' => 'key_select',
                'tile' => 'details',
                'in_create_form' => true,
                'default' => dt_get_option( 'dt_working_languages' ) ?: [ 'en' ],
                'icon' => get_template_directory_uri() . '/dt-assets/images/languages.svg',
            ];

            $key_name = 'public_key';
            if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
                $key_name = DT_Magic_URL::get_public_key_meta_key( 'subscriptions_app', 'manage' );
            }
            $fields[$key_name] = [
                'name'   => __( 'Private Key', 'disciple-tools-prayer-campaigns' ),
                'description' => __( 'Private key for subscriber access', 'disciple-tools-prayer-campaigns' ),
                'type'   => 'hash',
                'hidden' => true,
                'customizable' => false,
            ];
            $fields['subscriber_last_modified'] = [
                'name'   => __( 'Last Modified by Subscriber', 'disciple-tools-prayer-campaigns' ),
                'description' => '',
                'type' => 'time',
                'default' => '',
                'hidden' => true,
                'customizable' => false,
            ];



            /**
             * location elements
             */
            $fields['location_grid'] = [
                'name'        => __( 'Locations', 'disciple-tools-prayer-campaigns' ),
                'description' => _x( 'The general location where this contact is located.', 'Optional Documentation', 'disciple-tools-prayer-campaigns' ),
                'type'        => 'location',
                'mapbox'    => false,
                'customizable' => false,
                'in_create_form' => true,
                'tile' => 'details',
                'icon' => get_template_directory_uri() . '/dt-assets/images/location.svg',
            ];
            $fields['location_grid_meta'] = [
                'name'        => __( 'Locations', 'disciple-tools-prayer-campaigns' ), //system string does not need translation
                'description' => _x( 'The general location where this contact is located.', 'Optional Documentation', 'disciple-tools-prayer-campaigns' ),
                'type'        => 'location_meta',
                'tile'      => 'details',
                'mapbox'    => false,
                'hidden' => true,
                'icon' => get_template_directory_uri() . '/dt-assets/images/location.svg?v=2',
            ];
            $fields['contact_address'] = [
                'name' => __( 'Address', 'disciple-tools-prayer-campaigns' ),
                'icon' => get_template_directory_uri() . '/dt-assets/images/house.svg',
                'type' => 'communication_channel',
                'tile' => '',
                'mapbox'    => false,
                'customizable' => false
            ];
            if ( DT_Mapbox_API::get_key() ){
                $fields['contact_address']['custom_display'] = true;
                $fields['contact_address']['mapbox'] = true;
                unset( $fields['contact_address']['tile'] );
                $fields['location_grid']['mapbox'] = true;
                $fields['location_grid_meta']['mapbox'] = true;
                $fields['location_grid']['hidden'] = true;
                $fields['location_grid_meta']['hidden'] = false;
            }
            // end locations

            $fields['contacts'] = [
                'name' => __( 'Contact', 'disciple-tools-prayer-campaigns' ),
                'description' => _x( 'The contacts who are members of this group.', 'Optional Documentation', 'disciple-tools-prayer-campaigns' ),
                'type' => 'connection',
                'post_type' => 'contacts',
                'p2p_direction' => 'from',
                'tile' => 'other',
                'customizable' => false,
                'p2p_key' => $this->post_type.'_to_contacts',
            ];

            $fields['timezone'] = [
                'name' => __( 'Timezone', 'disciple-tools-prayer-campaigns' ),
                'type' => 'text',
                'hidden' => true
            ];
            $fields['lang'] = [
                'name' => __( 'Language', 'disciple-tools-prayer-campaigns' ),
                'type' => 'text',
                'hidden' => true
            ];
            $fields['receive_prayer_time_notifications'] = [
                'name' => __( 'Receive Prayer Time Notifications', 'disciple-tools-prayer-campaigns' ),
                'type' => 'boolean',
                'tile' => 'details',
                'default' => false,
                'hidden' => false,
                'show_in_table' => 50,
            ];
            $fields['last_modified']['show_in_table'] = false;
        }

        if ( $post_type === 'contacts' && current_user_can( 'view_all_'.$this->post_type ) ){
            $fields[$this->post_type] = [
                'name' => $this->plural_name,
                'description' => '',
                'type' => 'connection',
                'post_type' => $this->post_type,
                'p2p_direction' => 'from',
                'p2p_key' => $this->post_type.'_to_contacts',
                'tile' => 'other',
                'icon' => get_template_directory_uri() . '/dt-assets/images/group-type.svg',
                'create-icon' => get_template_directory_uri() . '/dt-assets/images/add-group.svg',
                'show_in_table' => 35,
                'customizable' => false,
            ];
        }

        return $fields;
    }


    /**
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/field-and-tiles.md
     */
    public function dt_details_additional_tiles( $tiles, $post_type = '' ){
        if ( $post_type === $this->post_type ){
            $tiles['commitments'] = [ 'label' => __( 'Commitments', 'disciple-tools-subscriptions' ) ];
            $tiles['other'] = [ 'label' => __( 'Other', 'disciple-tools-prayer-campaigns' ) ];
        }

        return $tiles;
    }

    private function get_subscription_link( $post_type ){
        $record = DT_Posts::get_post( $post_type, get_the_ID() );

        $key_name = 'public_key';
        if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
            $key_name = DT_Magic_URL::get_public_key_meta_key( 'subscriptions_app', 'manage' );
        }
        if ( isset( $record[$key_name] ) ) {
            $key = $record[$key_name];
        } else {

            $key = dt_create_unique_key();
            update_post_meta( get_the_ID(), $key_name, $key );
        }
        return trailingslashit( site_url() ) . 'subscriptions_app/manage/' . $key;
    }

    /**
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/field-and-tiles.md#add-custom-content
     */
    public function dt_details_additional_section( $section, $post_type ){
        /* STATUS */
        if ( $post_type === $this->post_type && $section === 'status' ){
            $record = DT_Posts::get_post( $post_type, get_the_ID() );
            $fields = DT_Posts::get_post_field_settings( $post_type );

            $link = $this->get_subscription_link( $post_type );

            ?>
            <div class="cell small-12 medium-4">
                <div class="cell small-12 medium-4">
                    <?php render_field_for_display( 'status', $fields, $record, true ); ?>
                </div>
            </div>
            <div class="cell small-12 medium-4">
                <div class="cell small-12 medium-4">
                    <?php render_field_for_display( 'campaigns', $fields, $record, true ); ?>
                </div>
            </div>
            <div class="cell small-12 medium-4">
                <div class="section-subheader">
                    Subscriptions Management
                </div>
                <div class="grid-x">
                    <div class="cell small-6" style="padding:2px;">
                        <a class="button expanded hollow" onclick="copyToClipboard('<?php echo esc_url( $link ) ?>')">Copy Link</a>
                    </div>
                    <div class="cell small-6" style="padding:2px;">
                        <a class="button expanded hollow" target="_blank" href="<?php echo esc_url( $link ) ?>">Open</a>
                    </div>
                </div>
            </div>
            <script>
                /* @todo move somewhere more global */
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
        <?php }

        /* SUBSCRIPTIONS */

        if ( $post_type === $this->post_type && $section === 'commitments' ){
            $subscriber_id = get_the_ID();
            $subscriber = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, true );
            $subs = Disciple_Tools_Reports::get( $subscriber_id, 'post_id' );
            usort($subs, function( $a, $b ) {
                return $a['time_begin'] <=> $b['time_begin'];
            });
            $email_verified = false;
            foreach ( $subs ?? [] as $sub ){
                if ( !empty( $sub['value'] ) ){
                    $email_verified = true;
                }
            }
            $timezone = !empty( $subscriber['timezone'] ) ? $subscriber['timezone'] : 'America/Chicago';
            $tz = new DateTimeZone( $timezone );
            $notifications = isset( $subscriber['receive_prayer_time_notifications'] ) && !empty( $subscriber['receive_prayer_time_notifications'] );
            $link = $this->get_subscription_link( $post_type );
            ?>
            <p>Conformation Email Sent:
                <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/verified.svg' ) ?>"/>
                <button class="loader button hollow tiny" type="button" id="resend_confirmation_email">Resend confirmation email</button>
                <span id="confirmation_email_sent" style="display: none">Conformation Email Sent</span>
            </p>
            <p>
                Email address verified:
                <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/' . ( $email_verified ? 'verified.svg' : 'invalid.svg' ) ) ?>"/>
                <a class="button hollow tiny" target="_blank" href="<?php echo esc_url( $link ) ?>">manually verify</a>
            </p>
            <p>Notifications allowed:
                <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/' . ( $notifications ? 'verified.svg' : 'invalid.svg' ) ) ?>"/>
            </p>
            <p>
                Showing times according to timezone: <strong><?php echo esc_html( $timezone ); ?></strong><br>
                Subscriber language: <strong><?php echo esc_html( $subscriber['lang'] ?? 'en_US' ); ?></strong>
            </p>
            <?php
            if ( ! empty( $subs ) ){
                foreach ( $subs as $sub ){
                    if ( $sub['type'] !== 'campaign_app' ){
                        continue;
                    }
                    if ( !isset( $sub['time_begin'] ) ){
                        continue;
                    }
                    $style = '';
                    if ( time() > $sub['time_begin'] ){
                        $style = 'text-decoration:line-through;';
                    }
                    $begin_date = new DateTime( '@'.$sub['time_begin'] );
                    $end_date = new DateTime( '@'.$sub['time_end'] );
                    $begin_date->setTimezone( $tz );
                    $end_date->setTimezone( $tz );
                    $payload = maybe_unserialize( $sub['payload'] );
                    ?>
                    <div>
                        <span style="<?php echo esc_html( $style ); ?>">
                            <?php echo esc_html( sprintf(
                                '%1$s from %2$s to %3$s',
                                $begin_date->format( 'F d, Y' ),
                                $begin_date->format( 'H:i a' ),
                                $end_date->format( 'H:i a' )
                            ) );
                            if ( !empty( $sub['label'] ) ) : ?>
                                for <?php echo esc_html( $sub['label'] ) ?>
                            <?php endif;
                            if ( !empty( $sub['value'] ) ) : ?>
                                <img title="<?php esc_html_e( 'Verified', 'disciple-tools-prayer-campaigns' ); ?>" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/verified.svg' ) ?>"/>
                            <?php endif;
                            if ( isset( $payload['prayer_time_reminder_sent'] ) || strpos( $payload ?? '', 'prayer_time_reminder_sent' ) !== false ) : ?>
                                <img title="<?php esc_html_e( 'Email Sent', 'disciple-tools-prayer-campaigns' ); ?>" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/email.svg' ) ?>"/>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div>
                    No Subscriptions
                </div>
                <?php
            }

            ?>
                <script type="application/javascript">
                    $('#resend_confirmation_email').on("click", function (){
                        $(this).addClass('loading')
                        $.ajax({
                            type: 'POST',
                            contentType: 'application/json; charset=utf-8',
                            dataType: 'json',
                            url: window.wpApiShare.site_url + "/wp-json/dt-subscriptions/v1/" + window.detailsSettings.post_id + '/confirmation-email',
                            beforeSend: (xhr) => {
                                xhr.setRequestHeader("X-WP-Nonce", wpApiShare.nonce);
                            },
                        }).then(()=>{
                            $(this).removeClass('loading')
                            $('#confirmation_email_sent').show()
                        })
                    })

                </script>
            <?php
        }
    }

    public function rest_api_init(){
        $namespace = 'dt-subscriptions/v1';
        $arg_schemas = [
            'id' => [
                'description' => 'The id of the post',
                'type' => 'integer',
                'required' => true,
                'validate_callback' => [ 'Disciple_Tools_Posts_Endpoints', 'prefix_validate_args_static' ]
            ],
        ];
        register_rest_route( $namespace, '/(?P<id>\d+)/confirmation-email', [
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'send_confirmation_email' ],
                'args' => [
                        'id' => $arg_schemas['id'],
                    ],
                'permission_callback' => function( WP_REST_Request $request ){
                    $url_params = $request->get_url_params();
                    return DT_Posts::can_update( 'subscriptions', $url_params['id'] ?? null );
                },
            ],
        ] );
    }

    public function send_confirmation_email( WP_REST_Request $request ){
        $url_params = $request->get_url_params();
        $subscription_id = $url_params['id'];
        $subscription = DT_Posts::get_post( 'subscriptions', $subscription_id );
        if ( !isset( $subscription['campaigns'][0]['ID'] ) ){
            return new WP_Error( __METHOD__, 'Missing parameters.' );
        }
        $campaign = $subscription['campaigns'][0]['ID'];

        return DT_Prayer_Campaigns_Send_Email::send_registration( $subscription_id, $campaign );
    }



    public function post_connection_added( $post_type, $post_id, $field_key, $value ){
        // placeholder for future
    }

    //action when a post connection is removed during create or update
    public function post_connection_removed( $post_type, $post_id, $field_key, $value ){
        // placeholder for future
    }

    //filter when a comment is created
    public function dt_comment_created( $post_type, $post_id, $comment_id, $type ){
        // placeholder for future
    }

    //filter at the start of post update
    public function dt_post_update_fields( $fields, $post_type, $post_id ){
        if ( $post_type === $this->post_type ){
            if ( ! isset( $fields['subscriber_last_modified'] ) ) {
                $fields['subscriber_last_modified'] = time();
            }
        }
        return $fields;
    }

    // filter at the start of post creation
    public function dt_post_create_fields( $fields, $post_type ){
        if ( $post_type === $this->post_type ) {
            if ( !isset( $fields['status'] ) ) {
                $fields['status'] = 'active';
            }
            $key_name = 'public_key';
            if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
                $key_name = DT_Magic_URL::get_public_key_meta_key( 'subscriptions_app', 'manage' );
            }
            if ( !isset( $fields[$key_name] ) ) {
                $fields[$key_name] = dt_create_unique_key();
            }
            if ( ! isset( $fields['subscriber_last_modified'] ) ) {
                $fields['subscriber_last_modified'] = time();
            }
        }
        return $fields;
    }

    //action when a post has been created
    public function dt_post_created( $post_type, $post_id, $initial_fields ){
        if ( $post_type === $this->post_type ){
            do_action( "dt_'.$this->post_type.'_created", $post_id, $initial_fields );
//            $post_array = DT_Posts::get_post( $this->post_type, $post_id, true, false );
//            if ( isset( $post_array["assigned_to"] )) {
//                if ( $post_array["assigned_to"]["id"] ) {
//                    DT_Posts::add_shared( $this->post_type, $post_id, $post_array["assigned_to"]["id"], null, false, false, false );
//                }
//            }
        }
    }

    //list page filters function
    private static function get_all_status_types(){
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare( "
                SELECT pm.meta_value as status, count(pm.post_id) as count
                FROM $wpdb->postmeta pm
                JOIN $wpdb->posts a ON( a.ID = pm.post_id AND a.post_type = %s and a.post_status = 'publish' )
                WHERE pm.meta_key = 'status'
                GROUP BY pm.meta_value;
            ", self::post_type() ), ARRAY_A );

        return $results;
    }

    //build list page filters
    public static function dt_user_list_filters( $filters, $post_type ){
        if ( $post_type === self::post_type() && current_user_can( 'view_any_'.self::post_type() ) ){

            $fields = DT_Posts::get_post_field_settings( $post_type );

            $campaigns = DT_Posts::list_posts( 'campaigns', [] );

            $counts = self::get_all_status_types();
            $active_counts = [];
            $status_counts = [];
            $total_all = 0;
            foreach ( $counts as $count ){
                $total_all += $count['count'];
                dt_increment( $status_counts[$count['status']], $count['count'] );
                if ( $count['status'] === 'active' ){
                    dt_increment( $active_counts[$count['status']], $count['count'] );
                }
            }

            //add count to default all tab
            foreach ( $filters['tabs'] as &$filter_tab ){
                if ( $filter_tab['key'] === 'all' ){
                    $filter_tab['count'] = $total_all;
                }
            }
            //add count to default all filter
            foreach ( $filters['filters'] as &$filter_item ){
                if ( $filter_item['ID'] === 'all' ){
                    $filter_item['count'] = $total_all;
                }
            }

            foreach ( $fields['status']['default'] as $status_key => $status_value ) {
                if ( isset( $status_counts[$status_key] ) ){
                    $filters['filters'][] = [
                        'ID' => 'all_' . $status_key,
                        'tab' => 'all',
                        'name' => $status_value['label'],
                        'query' => [
                            'status' => [ $status_key ],
                            'sort' => '-post_date'
                        ],
                        'count' => $status_counts[$status_key]
                    ];
                }
            }

            $filters['filters'][] = [
                'ID' => 'by_campaign',
                'tab' => 'all',
                'name' => 'By Campaign',
                'query' => [
                    'sort' => 'campaigns'
                ],
                'count' => $total_all,

            ];
            foreach ( $campaigns['posts'] as $campaign ){
                $filters['filters'][] = [
                    'ID' => 'campaign_' . $campaign['ID'],
                    'tab' => 'all',
                    'name' => $campaign['name'],
                    'query' => [
                        'campaigns' => [ $campaign['ID'] ],
                    ],
                    'count' => '',
                    'subfilter' => true
                ];
            }
        }
        return $filters;
    }

    // access permission
    public static function dt_filter_access_permissions( $permissions, $post_type ){
        if ( $post_type === self::post_type() ){
            if ( DT_Posts::can_view_all( $post_type ) ){
                $permissions = [];
            }
        }
        return $permissions;
    }
}


