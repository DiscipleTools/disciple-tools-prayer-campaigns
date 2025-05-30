<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class DT_Subscriptions_Base
 * Load the core post type hooks into the Disciple.Tools system
 */
class DT_Campaigns_Base {

    /**
     * Define post type variables
     * @var string
     */
    public $post_type = 'campaigns';
    public $module = 'campaigns_base';
    public $single_name = 'Campaign';
    public $plural_name = 'Campaigns';
    public static function post_type(){
        return 'campaigns';
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
//        add_filter( 'dt_front_page', [ $this, 'dt_front_page' ] );

        //setup tiles and fields
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 20, 2 );
        add_filter( 'dt_post_update_fields', [ $this, 'dt_post_update_fields' ], 20, 3 );

        // hooks
        add_filter( 'dt_post_create_fields', [ $this, 'dt_post_create_fields' ], 10, 2 );
        add_action( 'post_connection_added', [ $this, 'post_connection_added' ], 10, 4 );
        add_action( 'dt_post_created', [ $this, 'dt_post_created' ], 10, 3 );

        //list
        add_filter( 'dt_user_list_filters', [ $this, 'dt_user_list_filters' ], 150, 2 );
        add_filter( 'dt_filter_access_permissions', [ $this, 'dt_filter_access_permissions' ], 20, 2 );
        add_filter( 'dt_comments_additional_sections', [ $this, 'dt_comments_additional_sections' ], 50, 2 );

        add_filter( 'dt_nav', [ $this, 'dt_nav' ], 10, 1 );

        if ( !wp_next_scheduled( 'dt_prayer_campaigns_daily_cron' ) ) {
            wp_schedule_event( time(), 'daily', 'dt_prayer_campaigns_daily_cron' );
        }
        add_action( 'dt_prayer_campaigns_daily_cron', [ $this, 'dt_prayer_campaigns_daily_cron' ] );
    }

    public function after_setup_theme(){
        if ( class_exists( 'Disciple_Tools_Post_Type_Template' ) ) {
            new Disciple_Tools_Post_Type_Template( $this->post_type, $this->single_name, $this->plural_name );
        }
    }

    public function dt_front_page(){
        return admin_url( 'admin.php?page=dt_prayer_campaigns' );
    }

    public function dt_nav( $nav ){
        if ( isset( $nav['admin']['settings']['submenu']['admin'] ) ){
            $nav['admin']['settings']['submenu']['admin']['hidden'] = !current_user_can( 'manage_dt' ) && !current_user_can( 'edit_' . CAMPAIGN_LANDING_POST_TYPE );
        }
        return $nav;
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
        if ( !isset( $expected_roles['campaigns_creator'] ) ){
            $expected_roles['campaigns_creator'] = [
                'label' => 'Campaign Creator',
                'description' => 'Create and edit prayer campaigns',
                'permissions' => [
                    'access_disciple_tools' => true,
                ]
            ];
        }
        $campaigns_permissions = [
            'access_'.$this->post_type => true,
            'create_'.$this->post_type => true,

        ];
        $landing_page_permissions = [
            // landing page access
            'create_' . CAMPAIGN_LANDING_POST_TYPE => true,
            'edit_' . CAMPAIGN_LANDING_POST_TYPE => true,
            'read_' . CAMPAIGN_LANDING_POST_TYPE => true,
            'delete_' . CAMPAIGN_LANDING_POST_TYPE => true,
            'delete_' . CAMPAIGN_LANDING_POST_TYPE . 's' => true,
            'edit' . CAMPAIGN_LANDING_POST_TYPE . 's' => true,
            'publish_' . CAMPAIGN_LANDING_POST_TYPE . 's' => true,
            'read_private_' . CAMPAIGN_LANDING_POST_TYPE . 's' => true,
            'edit_posts' => true, // list patterns and all creating landing posts
            'publish_posts' => true, //create new patterns
            'edit_published_posts' => true, //edit synced posts and create un-synced posts
            // rest access for blocks editor
            'wp_api_allowed_user' => true,
            'edit_files' => true,
            'upload_files' => true,
            // wp-admin dashboard access
            'read' => true,
        ];

        //all campaigns permissions
        $all_campaigns_admin_permissions = [
            'update_any_'.$this->post_type => true,
            'view_any_'.$this->post_type => true,
            'delete_others_' . CAMPAIGN_LANDING_POST_TYPE . 's' => true,
            'edit_others_' . CAMPAIGN_LANDING_POST_TYPE . 's' => true,
        ];

        //campaign creator permissions
        $expected_roles['campaigns_creator']['permissions'] = array_merge( $expected_roles['campaigns_creator']['permissions'], $campaigns_permissions );
        $expected_roles['campaigns_creator']['permissions'] = array_merge( $expected_roles['campaigns_creator']['permissions'], $landing_page_permissions );

        //campaign admin permissions
        $expected_roles['campaigns_admin']['permissions'] = array_merge( $expected_roles['campaigns_admin']['permissions'], $campaigns_permissions );
        $expected_roles['campaigns_admin']['permissions'] = array_merge( $expected_roles['campaigns_admin']['permissions'], $landing_page_permissions );
        $expected_roles['campaigns_admin']['permissions'] = array_merge( $expected_roles['campaigns_admin']['permissions'], $all_campaigns_admin_permissions );

        if ( isset( $expected_roles['administrator'] ) ){
            $expected_roles['administrator']['permissions']['access_' . $this->post_type ] = true;
            $expected_roles['administrator']['permissions']['create_' . $this->post_type] = true;
            $expected_roles['administrator']['permissions']['view_any_'.$this->post_type ] = true;
            $expected_roles['administrator']['permissions']['update_any_'.$this->post_type ] = true;
            $expected_roles['administrator']['permissions'] = array_merge( $expected_roles['administrator']['permissions'], $landing_page_permissions );
            $expected_roles['administrator']['permissions'] = array_merge( $expected_roles['administrator']['permissions'], $all_campaigns_admin_permissions );
        }

        return $expected_roles;
    }

    /**
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/fields.md
     */
    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === $this->post_type ){
            $fields['status'] = [
                'name'        => 'Status',
                'description' => 'Set the current status.',
                'type'        => 'key_select',
                'default'     => [
                    'active'   => [
                        'label' => 'Active',
                        'description' => 'Is active.',
                        'color' => '#4CAF50'
                    ],
                    'inactive' => [
                        'label' => 'Inactive',
                        'description' => 'No longer active.',
                        'color' => '#F43636'
                    ],
                ],
                'tile'     => 'status',
                'icon' => get_template_directory_uri() . '/dt-assets/images/status.svg',
                'default_color' => '#366184',
                'show_in_table' => 10,
                'select_cannot_be_empty' => true,
            ];
            // end basic framework fields

            $fields['assigned_user'] = [
                'name' => 'Assigned Users',
                'description' => 'Users responsible for managing the campaign.',
                'type' => 'connection',
                'post_type' => 'contacts',
                'tile' => 'status',
                'p2p_direction' => 'from',
                'p2p_key' => $this->post_type.'_to_contacts',
                'icon' => get_template_directory_uri() . '/dt-assets/images/assigned-to.svg',
            ];

            $fields['name']['tile'] = 'status';
            $fields['name']['description'] = 'The name of your campaign. Shown on your landing page and in email signatures.';

            $fields['campaign_url'] = [
                'name' => 'Campaign URL',
                'description' => 'The URL of the campaign landing page. It must be unique.',
                'type' => 'text',
                'tile' => 'campaign_setup',
                'icon' => get_template_directory_uri() . '/dt-assets/images/link.svg',
                'show_in_table' => 20,
                'settings_tab' => 'campaign_landing',
                'settings_section' => 'Campaign',
            ];


//            $fields['languages'] = [
//                'name' => 'Subscriber Preferred Language',
//                'description' => 'Subscriber preferred language',
//                'type' => 'key_select',
//                'tile' => 'details',
//                'default' => dt_get_option( 'dt_working_languages' ) ?: [ 'en' ],
//                'icon' => get_template_directory_uri() . '/dt-assets/images/languages.svg',
//            ];
//            $fields['peoplegroups'] = [
//                'name' => 'People Groups',
//                'description' => 'The people groups connected to this record.',
//                'type' => 'connection',
//                'post_type' => 'peoplegroups',
//                'tile' => 'details',
//                'p2p_direction' => 'to',
//                'p2p_key' => $this->post_type.'_to_peoplegroups'
//            ];

            $porches = apply_filters( 'dt_register_prayer_campaign_porch', [] );
            $fields['porch_type'] = [
                'name' => 'Landing Page Type',
                'type' => 'key_select',
                'default' => [],
                'tile' => 'campaign_setup',
                'in_create_form' => true,
                'select_cannot_be_empty' => true,
                'settings_tab' => 'campaign_landing',
                'settings_section' => 'Campaign',
                'description' => 'The type of landing page to use for this campaign. Types will have different layouts and content.'
            ];
            foreach ( $porches as $porch ){
                $fields['porch_type']['default'][$porch['id']] = [ 'label' => $porch['label'], 'default' => $porch['id'] === 'generic-porch' ];
            }

            $fields['start_date'] = [
                'name'        => 'Start Date',
                'required' => true,
                'description' => 'On what day will your campaign start?',
                'type'        => 'date',
                'default'     => time(),
                'tile' => 'campaign_setup',
                'settings_tab' => 'campaign_landing',
                'settings_section' => 'Campaign',
                'icon' => get_template_directory_uri() . '/dt-assets/images/date-start.svg',
                'in_create_form' => true,
                'show_in_table' => 101
            ];
            $fields['end_date'] = [
                'name'        => 'End Date (optional)',
                'description' => 'When will be the last day of your campaign?',
                'type'        => 'date',
                'default'     => '',
                'tile' => 'campaign_setup',
                'settings_tab' => 'campaign_landing',
                'settings_section' => 'Campaign',
                'icon' => get_template_directory_uri() . '/dt-assets/images/date-end.svg',
                'in_create_form' => true,
                'show_in_table' => 102
            ];
            $fields['last_modified']['show_in_table'] = false;
            $fields['favorite']['show_in_table'] = false;
            $fields['name']['translations'] = [];

            $fields['campaign_goal'] = [
                'name' => 'Campaign Goal',
                'type' => 'key_select',
                'tile' => 'status',
                'description' => 'The prayer goal of the campaign. See https://prayer.tools/docs/campaign-goals/',
                'default' => [
                    '247coverage' => [
                        'label' => '24/7 daily coverage',
                    ],
                    'quantity' => [
                        'label' => 'Custom # of hours every day (default 24)',
                        'description' => 'Get to x Hours of prayer every day.'
                    ]
                ],
                'settings_tab' => 'campaign_landing',
                'settings_section' => 'Campaign',
                'select_cannot_be_empty' => true,
            ];
            $fields['goal_quantity'] = [
                'name' => 'Goal Quantity',
                'description' => 'The number of hours of prayer a day you want to reach',
                'type' => 'number',
                'tile' => 'status',
                'default' => 24,
                'settings_tab' => 'campaign_landing',
                'settings_section' => 'Campaign',
                'select_cannot_be_empty' => true,
            ];

            $timezones = [];
            $tzlist = DateTimeZone::listIdentifiers( DateTimeZone::ALL );
            foreach ( $tzlist as $tz ){
                $timezones[$tz] = [
                    'label' => $tz
                ];
            }
            $fields['campaign_timezone'] = [
                'name' => 'Campaign Time Zone',
                'description' => 'The time zone for the campaign. This will affect exactly when the campaign starts and ends.',
                'required' => false,
                'in_create_form' => true,
                'default' => $timezones,
                'type' => 'key_select',
                'tile' => 'campaign_setup',
                'settings_tab' => 'campaign_landing',
                'settings_section' => 'Campaign',
            ];

            $fields['min_time_duration'] = [
                'name' => 'Minimum Prayer Time Duration',
                'description' => 'The minimum duration a user can select for a prayer slot.',
                'type' => 'key_select',
                'default' => [
                    '60' => [ 'label' => '1 Hour' ],
                    '30' => [ 'label' => '30 Minutes' ], //keep as first item
                    '15' => [ 'label' => '15 Minutes', 'default' => true ], //keep as first item
                    '10' => [ 'label' => '10 Minutes' ],
                    '5' => [ 'label' => '5 Minutes' ],
                ],
                'tile' => 'campaign_setup',
                'settings_tab' => 'campaign_landing',
                'settings_section' => 'Campaign',
                'select_cannot_be_empty' => true,
            ];

            $fields['enabled_frequencies'] = [
                'name' => 'Sign up Frequencies',
                'description' => 'Let the user choose between these during sign up. Default: daily and pick days.',
                'type' => 'multi_select',
                'default' => [
                    'daily' => [ 'label' => 'Daily' ],
                    'weekly' => [ 'label' => 'Weekly' ],
//                    'monthly' => [ 'label' => 'Monthly' ],
                    'pick' => [ 'label' => 'Pick Days' ],
                ],
                'tile' => 'campaign_setup',
                'settings_tab' => 'campaign_landing',
                'settings_section' => 'Campaign',
            ];

            $fields['weekly_signup_length'] = [
                'name' => 'Weekly Signup Length (in days)',
                'description' => 'The number of days a user can sign up for at one time with a weekly signup. Max 365 days',
                'type' => 'text',
                'default' => 180,
                'tile' => 'campaign_setup',
                'settings_tab' => 'campaign_landing',
                'settings_section' => 'Campaign',
            ];

            $fields['daily_signup_length'] = [
                'name' => 'Daily Signup Length (in days)',
                'description' => 'The number of days a user can sign up for at one time with a daily signup. Max 365 days',
                'type' => 'text',
                'default' => 90,
                'tile' => 'campaign_setup',
                'settings_tab' => 'campaign_landing',
                'settings_section' => 'Campaign',
            ];

//            $fields['duration_options'] = [
//                'name' => 'Duration options',
//                'type' => 'key_select',
//                'default' => DT_Time_Utilities::get_slot_duration_options(),
//                'settings_section' => 'Campaign',
//                'settings_tab' => 'campaign_landing',
//            ];
            $fields['enabled_languages'] = [
                'name' => 'Enabled Languages',
                'type' => 'tags',
                'default' => [],
                'tile' => 'campaign_landing',
            ];

            /**
             * location elements
             */
            $fields['location_grid'] = [
                'name'        => 'Locations',
                'description' => 'The general location where this contact is located.',
                'type'        => 'location',
                'mapbox'    => false,
                'customizable' => false,
                'in_create_form' => true,
                'tile' => 'campaign_setup',
                'icon' => get_template_directory_uri() . '/dt-assets/images/location.svg',
            ];
            $fields['location_grid_meta'] = [
                'name'        => 'Locations', //system string does not need translation
                'description' => 'The general location where this contact is located.',
                'type'        => 'location_meta',
                'tile'      => 'campaign_setup',
                'mapbox'    => false,
                'hidden' => true,
                'icon' => get_template_directory_uri() . '/dt-assets/images/location.svg',
            ];
            $fields['contact_address'] = [
                'name' => 'Address',
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

            $fields['subscriptions'] = [
                'name' => 'Subscriptions',
                'description' => 'The contacts who are members of this group.',
                'type' => 'connection',
                'post_type' => 'subscriptions',
                'p2p_direction' => 'from',
                'customizable' => false,
                'hidden' => true,
                'p2p_key' => 'campaigns_to_subscriptions',
                'icon' => get_template_directory_uri() . '/dt-assets/images/nametag.svg',
            ];

            if ( isset( $fields['tags'] ) ){
                $fields['tags']['tile'] = 'details';
            }
        }

        if ( $post_type === 'subscriptions' ){
            $fields['campaigns'] = [
                'name' => $this->plural_name,
                'description' => '',
                'type' => 'connection',
                'post_type' => $this->post_type,
                'p2p_direction' => 'to',
                'p2p_key' => 'campaigns_to_subscriptions',
                'tile' => '',
                'icon' => get_template_directory_uri() . '/dt-assets/images/group-type.svg',
                'create-icon' => get_template_directory_uri() . '/dt-assets/images/add-group.svg',
                'show_in_table' => 35,
                'customizable' => false,
            ];
        }

        return $fields;
    }

    public function dt_post_update_fields( $fields, $post_type, $post_id ){
        global $wpdb;
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
            //make sure the campaign_url is unique
            if ( isset( $fields['campaign_url'] ) ) {
                $campaign_url = DT_Campaign_Landing_Settings::format_landing_page_url( $fields['campaign_url'] );
                $url_exists = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM $wpdb->postmeta WHERE meta_key = 'campaign_url' AND meta_value = %s", $campaign_url ) );
                if ( !empty( $url_exists ) ){
                    $campaign_url = dt_create_field_key( $campaign_url, true );
                    $campaign_url = str_replace( '/', '', $campaign_url );
                }
                $fields['campaign_url'] = $campaign_url;
            }
        }
        return $fields;
    }

    public function post_connection_added( $post_type, $post_id, $post_key, $value ){
        if ( $post_type === 'campaigns' ){
            if ( $post_key === 'assigned_user' ){
                $user_id = get_post_meta( $value, 'corresponds_to_user', true );
                if ( $user_id ){
                    DT_Posts::add_shared( $post_type, $post_id, $user_id, null, true, false, true );
                }
            }
        }
    }

    public function dt_comments_additional_sections( $sections, $post_type ){
        if ( $post_type === 'campaigns' || $post_type === 'subscriptions' ){
            $sections[] = [
                'key' => 'stories',
                'label' => 'Stories',
                'selected_by_default' => true
            ];
            $sections[] = [
                'key' => 'contact_us',
                'label' => 'Contact US',
                'selected_by_default' => true
            ];
        }
        return $sections;
    }

    /**
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/field-and-tiles.md
     */
    public function dt_details_additional_tiles( $tiles, $post_type = '' ){
        if ( $post_type === $this->post_type ){
            $tiles['campaign_setup'] = [ 'label' => 'Campaign Setup' ];
            $tiles['commitments'] = [ 'label' => 'Commitments' ];
            if ( !isset( $tiles['campaign_communication'] ) ){
                $tiles['campaign_communication'] = [
                    'label' => __( 'Campaign Communication', 'disciple-tools-prayer-campaigns' ),
                    'description' => '',
                ];
            }
        }
        return $tiles;
    }

    /**
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/field-and-tiles.md#add-custom-content
     */
    public function dt_details_additional_section( $section, $post_type ){

        if ( $post_type === $this->post_type && $section === 'commitments' ){
            $subscribers_count = DT_Subscriptions::get_subscribers_count( get_the_ID() );
            $coverage_count = $this->query_coverage_percentage( get_the_ID() );
            $scheduled_commitments = $this->query_scheduled_count( get_the_ID() );
            $past_commitments = $this->query_past_count( get_the_ID() );
            $scheduled_hours = $this->query_scheduled_minutes( get_the_ID() ) / 60;
            $hours_prayed = $this->query_minutes_prayed( get_the_ID() ) / 60;
            ?>
            <div class="cell small-12">
            <div class="grid-x">
            <div class="cell small-6 ">
                <div class="section-subheader">
                    Subscribers
                </div>
                <div>
                    <span style="font-size:2rem;"><a href="javascript:void(0)" id="campaign_subscriber_list"><?php echo esc_html( $subscribers_count ) ?></a></span>
                </div>
            </div>
            <div class="cell small-6 ">
                <div class="section-subheader">
                    Coverage
                </div>
                <div>
                    <span style="font-size:2rem;"><a href="javascript:void(0)" id="campaign_coverage_chart"><?php echo esc_html( $coverage_count ) ?>%</a></span>
                </div>
            </div>
            <div class="cell small-6 ">
                <div class="section-subheader">
                    Scheduled Events
                </div>
                <div>
                    <span style="font-size:2rem;"><?php echo esc_html( $scheduled_commitments ) ?></span>
                </div>
            </div>
            <div class="cell small-6 ">
                <div class="section-subheader">
                    Past Events
                </div>
                <div>
                    <span style="font-size:2rem;"><?php echo esc_html( $past_commitments ) ?></span>
                </div>
            </div>
            <div class="cell small-6 ">
                <div class="section-subheader">
                    Scheduled Hours
                </div>
                <div>
                    <span style="font-size:2rem;"><?php echo esc_html( $scheduled_hours ) ?></span>
                </div>
            </div>
            <div class="cell small-6 ">
                <div class="section-subheader">
                    Hours Prayed
                </div>
                <div>
                    <span style="font-size:2rem;"><?php echo esc_html( $hours_prayed ) ?></span>
                </div>
            </div>
            <div class="cell small-6 ">
                <div class="section-subheader">
                    Hours from group reports
                </div>
                <div>
                    <span style="font-size:2rem;"><?php echo esc_html( self::query_extra_minutes( get_the_ID() ) / 60 ) ?></span>
                </div>
            </div>
            <div class="cell small-12 ">
                <button class="button hollow" id="campaign_coverage_stats">Stats</button>
                <button class="button hollow" id="campaign_coverage_timeline">Timeline</button>
                <button class="button hollow" id="campaign_group_reports">Group Reports</button>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($){
            /* subscribers */
            $('#campaign_subscriber_list').on('click', function(e){
                $('#modal-small-title').empty().html(`<h2>Subscriber List</h2><hr>`)

                let container = $('#modal-small-content')
                container.empty().html(`
                <span class="loading-spinner active"></span>
                `)
                 makeRequest( 'GET', 'subscribers', { campaign_id: window.detailsSettings.post_id }, 'campaigns/v1')
                .done(function(data){
                    let content = `<ol>`
                    if ( data ) {
                        jQuery.each(data, function(i,v){
                            content += `<li>
                                <a href="${window.SHAREDFUNCTIONS.escapeHTML(window.wpApiShare.site_url)}/subscriptions/${window.lodash.escape(v.ID)}">
                                    ${window.lodash.escape(v.name)}
                                </a>
                                 (${window.lodash.escape(v.commitments)})
                                 ${parseInt(v.verified)>0 ? 'verified ' : '' }
                            </li>`
                        })
                    } else {
                        content += `<div class="cell">No subscribers found</div>`
                    }
                    content += `</ol>`
                    container.empty().html(content)
                })

                $('#modal-small').foundation('open')
            })

            $('#campaign_coverage_stats').on('click', function(e){
                $('#modal-small-title').empty().html(`<h2>Stats</h2><hr>`)

                let container = $('#modal-small-content')
                container.empty().html(`
                    <span class="loading-spinner active"></span>
                `)
                window.makeRequest( 'GET', 'coverage-stats', { campaign_id: window.detailsSettings.post_id }, 'campaigns/v1')
                .done(function(data){
                    let content = `<ul>`
                    if ( data ) {
                        jQuery.each(data.unique_days_covered, function(i,v){
                            content += `<li>
                                ${window.lodash.escape(v)} subscribers covered ${window.lodash.escape(i)} days
                            </li>`
                        })
                    } else {
                        content += `<div class="cell">No subscribers found</div>`
                    }
                    content += `</ul>`
                    container.empty().html(content)
                })

                $('#modal-small').foundation('open')
            })

            $('#campaign_coverage_timeline').on('click', function(e){
                $('#modal-small-title').empty().html(`<h2>Timeline</h2><hr>`)

                let container = $('#modal-small-content')
                container.empty().html(`
                    <span class="loading-spinner active"></span>
                `)
                window.makeRequest( 'GET', 'timeline', { campaign_id: window.detailsSettings.post_id }, 'campaigns/v1')
                .done(function(data){
                    let time_slots = {'00:00':null, '00:15':null, '00:30':null, '00:45':null, '01:00':null, '01:15':null, '01:30':null, '01:45':null, '02:00':null, '02:15':null, '02:30':null, '02:45':null, '03:00':null, '03:15':null, '03:30':null, '03:45':null, '04:00':null, '04:15':null, '04:30':null, '04:45':null, '05:00':null, '05:15':null, '05:30':null, '05:45':null, '06:00':null, '06:15':null, '06:30':null, '06:45':null, '07:00':null, '07:15':null, '07:30':null, '07:45':null, '08:00':null, '08:15':null, '08:30':null, '08:45':null, '09:00':null, '09:15':null, '09:30':null, '09:45':null, '10:00':null, '10:15':null, '10:30':null, '10:45':null, '11:00':null, '11:15':null, '11:30':null, '11:45':null, '12:00':null, '12:15':null, '12:30':null, '12:45':null, '13:00':null, '13:15':null, '13:30':null, '13:45':null, '14:00':null, '14:15':null, '14:30':null, '14:45':null, '15:00':null, '15:15':null, '15:30':null, '15:45':null, '16:00':null, '16:15':null, '16:30':null, '16:45':null, '17:00':null, '17:15':null, '17:30':null, '17:45':null, '18:00':null, '18:15':null, '18:30':null, '18:45':null, '19:00':null, '19:15':null, '19:30':null, '19:45':null, '20:00':null, '20:15':null, '20:30':null, '20:45':null, '21:00':null, '21:15':null, '21:30':null, '21:45':null, '22:00':null, '22:15':null, '22:30':null, '22:45':null, '23:00':null, '23:15':null, '23:30':null, '23:45':null};
                    let content = `<ul>`
                    if ( data ) {
                        jQuery.each(data, function(i,v){
                            // Split longer prayer subscriptions into more time slots
                            if (v.minutes == 30) {
                                // Calculate 15 mins after time_slot_begin time
                                var time_subslot_begin = new Date(new Date("1970/01/01 " + v.time_slot_begin).getTime() + 15 * 60000).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
                                data.push(
                                    {
                                        "name":v.name,
                                        "post_id":v.post_id,
                                        "time_slot_begin":time_subslot_begin,
                                        "time_slot_end":v.time_slot_end,
                                        "minutes":"15",
                                        "all_subscriptions":v.all_subscriptions,
                                        "verified_subscriptions":v.verified_subscriptions
                                    }
                                )

                                // Adjust first half of the timeslot
                                data[i].minutes = 15
                                data[i].time_slot_end = time_subslot_begin
                            }

                            if (v.minutes == 45) {
                                // Calculate 15 mins after time_slot_begin time
                                var time_subslot_1_begin = new Date(new Date("1970/01/01 " + v.time_slot_begin).getTime() + 15 * 60000).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
                                var time_subslot_2_begin = new Date(new Date("1970/01/01 " + v.time_slot_begin).getTime() + 30 * 60000).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });

                                data.push(
                                    {
                                        "name":v.name,
                                        "post_id":v.post_id,
                                        "time_slot_begin":time_subslot_1_begin,
                                        "time_slot_end":time_subslot_2_begin,
                                        "minutes":"15",
                                        "all_subscriptions":v.all_subscriptions,
                                        "verified_subscriptions":v.verified_subscriptions
                                    },
                                    {
                                        "name":v.name,
                                        "post_id":v.post_id,
                                        "time_slot_begin":time_subslot_2_begin,
                                        "time_slot_end":v.time_slot_end,
                                        "minutes":"15",
                                        "all_subscriptions":v.all_subscriptions,
                                        "verified_subscriptions":v.verified_subscriptions
                                    }
                                )

                                // Adjust first half of the timeslot
                                data[i].minutes = "15"
                                data[i].time_slot_end = time_subslot_1_begin
                            }
                        })

                        // Write the timeline
                        jQuery.each(time_slots, function(i,v){
                            var has_subscribers = false
                            content += `<li><b>${window.lodash.escape(i)}</b> - `
                            jQuery.each(data, function(ii,vv){
                                if (vv.time_slot_begin == i) {
                                    has_subscribers = true
                                    content += `<a href="/subscriptions/${window.lodash.escape(vv.post_id)}/">${window.lodash.escape(vv.name)}</a> (${window.lodash.escape(vv.all_subscriptions)} slots), `
                                }
                            })
                            if (!has_subscribers){
                                content += '(0 slots)'
                            }
                            content += '</li>'
                        })
                        // Remove trailing commas, if any
                        content = content.replaceAll(', </li>', '</li>')
                        content += `</ul>`
                    } else {
                        content += `<div class="cell">No subscribers found</div></ul>`
                    }
                    container.empty().html(content)
                })

                $('#modal-small').foundation('open')
            })

            /* campaign coverage */
            $('#campaign_coverage_chart').on('click', function(e){
                $('#modal-full-title').empty().html(`
                    <h2>Coverage Chart</h2>
                    <span style="font-size:.7em;">Cells contain subscriber count per block of time. Times shown in UTC (GMT+0) time.</span>

                <hr>`)

                let container = $('#modal-full-content')
                container.empty().html(`
                <span class="loading-spinner active"></span>
                `)

                makeRequest( 'GET', 'coverage', { campaign_id: window.detailsSettings.post_id }, 'campaigns/v1')
                .done(function(data){
                    let content = `<style>#cover-table td:hover {border: 1px solid darkslateblue;}</style><div class="table-scroll"><table id="cover-table" class="center">`
                    /* top row */
                    content += `<tr><th></th>`
                    let column_count = 0
                    jQuery.each(data[Object.keys(data)[1]].hours, function(i,time_slot){
                        if ( column_count >= 20 ){
                             content += `<th></th>`
                            column_count = 0
                        } else {
                            column_count++
                        }
                        content += `<th>${window.lodash.escape(time_slot.formatted)}</th>`
                    })
                    content += `</tr>`

                    /* table body */
                    jQuery.each(data, function(i,day){
                        content += `<tr><th style="white-space:nowrap">${window.lodash.escape(day.formatted)}</th>`
                        let column_count = 0
                        jQuery.each(day.hours, function(ii,time_slot){
                            if ( column_count >= 20 ){
                                 content += `<th style="white-space:nowrap">${window.lodash.escape(day.formatted)}</th>`
                                column_count = 0
                            } else {
                                column_count++
                            }
                            if ( time_slot.outside_of_campaign ){
                                content += `<td style="background-color:grey;">-</td>`
                            } else if ( time_slot.subscribers > 0 ){
                                content += `<td style="background-color:lightblue;">${window.lodash.escape(time_slot.subscribers)}</td>`
                            } else {
                                content += `<td>${window.lodash.escape(time_slot.subscribers)}</td>`
                            }
                        })

                        content += `</tr>`
                    })
                    content += `</table></div>`

                    container.empty().html(content)

                })


                $('#modal-full').foundation('open')
            })

            $('#campaign_group_reports').on('click', function(e){
                $('#modal-small-title').empty().html(`<h2>Group Reports</h2><hr>`)

                let container = $('#modal-small-content')
                container.empty().html(`
                    <span class="loading-spinner active"></span>
                `)
                window.makeRequest( 'GET', 'group-reports', { campaign_id: window.detailsSettings.post_id }, 'campaigns/v1')
                .done(function(data){
                    let content = `<table style="max-height:600px; overflow-y: scroll">
                        <tr style="text-align: left">
                            <th>Date</th>
                            <th>Group Size</th>
                            <th>Minutes Added</th>
                        </tr>`
                    if ( data ) {
                        const slot_length = parseInt( window.detailsSettings?.post_fields?.min_time_duration?.key || 15 )
                        jQuery.each(data, function(i,v){
                            content += `<tr>
                                <td>${window.lodash.escape(window.SHAREDFUNCTIONS.formatDate(v.timestamp))}</td>
                                <td>${window.lodash.escape(v.count)}</td>
                                <td>${window.lodash.escape( (parseInt( v.count ) - 1) * slot_length )}</td>
                            </tr>`
                        })
                    } else {
                        content += `<div class="cell">No group reports found</div>`
                    }
                    content += `</ul>`
                    container.empty().html(content)
                })

                $('#modal-small').foundation('open')
            })

        })
        </script>

        <?php }
        if ( 'campaign_communication' === $section ){
            $campaign_id = get_the_ID();
            $prayer_time_reminder_emails_sent = DT_Subscriptions::get_number_of_notification_emails_sent( $campaign_id );
            ?>
            <div class="cell small-12 medium-4">
                <div class="section-subheader">
                    <?php esc_html_e( 'Magic Link', 'disciple-tools-prayer-campaigns' ); ?>
                </div>
                <p>Prayer Time Reminder Emails sent: <?php echo esc_html( $prayer_time_reminder_emails_sent ); ?></p>
            </div>
            <?php
        }

        if ( $post_type === $this->post_type ) {
            $record = DT_Posts::get_post( $post_type, get_the_ID() );
            if ( $section === 'status' ){
                $link = DT_Magic_URL::get_link_url_for_post( $post_type, $record['ID'], 'campaign_app', 'ongoing' );
                ?>
                <div class="cell small-12 medium-4">
                    <div class="section-subheader">
                        <?php esc_html_e( 'Shortcodes', 'disciple-tools-prayer-campaigns' ); ?>
                    </div>
                    <a class="button hollow small" target="_blank" href="<?php echo esc_html( $link ); ?>"><?php esc_html_e( 'View Components', 'disciple-tools-prayer-campaigns' ); ?></a>
                    <a class="button hollow small" target="_blank" href="<?php echo esc_html( DT_Campaign_Landing_Settings::get_landing_page_url( $record['ID'] ) ); ?>"><?php esc_html_e( 'View Landing Page', 'disciple-tools-prayer-campaigns' ); ?></a>
                </div>
                <?php
            }
        }
    }

    public function add_api_routes() {
        $namespace = 'campaigns/v1';
        register_rest_route(
            $namespace, 'subscribers', [
                [
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => [ $this, 'subscribers_endpoint' ],
                    'permission_callback' => function( WP_REST_Request $request ) {
                        return dt_has_permissions( [ 'view_any_subscriptions' ] );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, 'coverage', [
                [
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => [ $this, 'coverage_endpoint' ],
                    'permission_callback' => function( WP_REST_Request $request ) {
                        return dt_has_permissions( [ 'view_any_subscriptions' ] );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, 'coverage-stats', [
                [
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => [ $this, 'coverage_stats_endpoint' ],
                    'permission_callback' => function( WP_REST_Request $request ) {
                        return dt_has_permissions( [ 'view_any_subscriptions' ] );
                    },
                ],
            ]
        );

        register_rest_route(
            $namespace, 'timeline', [
                [
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => [ $this, 'timeline_endpoint' ],
                    'permission_callback' => function( WP_REST_Request $request ) {
                        return dt_has_permissions( [ 'view_any_subscriptions' ] );
                    },
                ],
            ]
        );

        register_rest_route(
            $namespace, 'group-reports', [
                [
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => [ $this, 'group_reports_endpoint' ],
                    'permission_callback' => function( WP_REST_Request $request ) {
                        return dt_has_permissions( [ 'view_any_subscriptions' ] );
                    },
                ],
            ]
        );
    }



    public function subscribers_endpoint( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( ! isset( $params['campaign_id'] ) ) {
            return new WP_Error( __METHOD__, 'Required parameter not set' );
        }
        $campaign_post_id = sanitize_text_field( wp_unslash( $params['campaign_id'] ) );
        return DT_Subscriptions::get_subscribers( $campaign_post_id );
    }

    public function coverage_endpoint( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( ! isset( $params['campaign_id'] ) ) {
            return new WP_Error( __METHOD__, 'Required parameter not set' );
        }
        $campaign_post_id = sanitize_text_field( wp_unslash( $params['campaign_id'] ) );
        return DT_Time_Utilities::campaign_times_list( $campaign_post_id );
    }

    public function coverage_stats_endpoint( WP_REST_Request $request ){
        $params = $request->get_params();
        if ( ! isset( $params['campaign_id'] ) ) {
            return new WP_Error( __METHOD__, 'Required parameter not set' );
        }
        global $wpdb;
        $campaign_post_id = sanitize_text_field( wp_unslash( $params['campaign_id'] ) );
        $subscribers = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.post_title as name, p.ID,
                (SELECT COUNT(r.post_id)
                    FROM $wpdb->dt_reports r
                    WHERE r.post_type = 'subscriptions'
                    AND r.parent_id = %s
                    AND r.post_id = p.ID) as commitments,
                (SELECT COUNT(r.post_id)
                    FROM $wpdb->dt_reports r
                    WHERE r.post_type = 'subscriptions'
                    AND r.parent_id = %s
                    AND r.value = 1
                    AND r.post_id = p.ID) as verified
            FROM $wpdb->p2p p2
            LEFT JOIN $wpdb->posts p ON p.ID=p2.p2p_to
            WHERE p2p_type = 'campaigns_to_subscriptions'
            AND p2p_from = %s", $campaign_post_id, $campaign_post_id, $campaign_post_id, $campaign_post_id
        ), ARRAY_A );
        $unique_days_covered = [];
        foreach ( $subscribers as &$sub ){
            $unique_days = [];
            if ( $sub['commitments'] !== '0' ){
                $times = Disciple_Tools_Reports::get( $sub['ID'], 'post_id' );
                foreach ( $times as $time ){
                    $day = gmdate( 'Y-m-d', $time['time_begin'] );
                    if ( !in_array( $day, $unique_days, true ) ){
                        $unique_days[] = $day;
                    }
                }
            }
            $number_of_days_covered = sizeof( $unique_days );
            if ( !isset( $unique_days_covered[$number_of_days_covered] ) ){
                $unique_days_covered[$number_of_days_covered] =0;
            }
            $unique_days_covered[$number_of_days_covered]++;
            $sub['unique_days_covered'] = sizeof( $unique_days );
        }
        return [
            'unique_days_covered' => $unique_days_covered,
        ];
    }

    public function timeline_endpoint( WP_REST_Request $request ) {
        $params = $request->get_params();
        $post = DT_Posts::get_post( 'campaigns', $params['campaign_id'], true, false );
        if ( ! isset( $post['campaign_timezone']['label'] ) ) {
            $time_zone = 'America/Chicago';
        } else {
            $time_zone = $post['campaign_timezone']['label'];
        }

        if ( ! isset( $params['campaign_id'] ) ) {
            return new WP_Error( __METHOD__, 'Required parameter not set' );
        }
        global $wpdb;
        $time_format = '%H:%i';

        // Get time zone offset
        $tz_offset_info = new DateTime( 'now', new DateTimeZone( $time_zone ) );
        $tz_offset = $tz_offset_info->format( 'P' );

        $campaign_post_id = sanitize_text_field( wp_unslash( $params['campaign_id'] ) );
        $timeline_slots = $wpdb->get_results( $wpdb->prepare(
            "SELECT
            p.post_title AS name,
            r.post_id,
            DATE_FORMAT( CONVERT_TZ( FROM_UNIXTIME( r.time_begin ), 'SYSTEM', %s ), %s ) AS time_slot_begin,
            DATE_FORMAT( CONVERT_TZ( FROM_UNIXTIME( r.time_end ), 'SYSTEM', %s ), %s ) AS time_slot_end,
            SUM( ( r.time_end - r.time_begin ) / 60 ) AS minutes
            SUM( r.value ) AS verified_subscriptions,
            COUNT( r.value ) AS all_subscriptions
        FROM $wpdb->dt_reports r
        INNER JOIN $wpdb->posts p ON p.ID = r.post_id
        WHERE r.post_type = 'subscriptions'
        AND r.parent_id = %s
        GROUP BY time_slot_begin, time_slot_end;
        ", $tz_offset, $time_format, $tz_offset, $time_format, $campaign_post_id ), ARRAY_A);
        return $timeline_slots;
    }

    public function group_reports_endpoint( WP_REST_Request $request ){
        $params = $request->get_params();

        if ( ! isset( $params['campaign_id'] ) ) {
            return new WP_Error( __METHOD__, 'Required parameter not set' );
        }

        global $wpdb;
        $values = $wpdb->get_results( $wpdb->prepare( "SELECT
            r.value as count, r.timestamp as timestamp
            FROM $wpdb->dt_reports r
            WHERE r.parent_id = %s AND r.post_type = 'campaigns' AND r.type = 'fuel'
            ;", $params['campaign_id']
        ), ARRAY_A );

        return $values;
    }

    public static function query_scheduled_count( $campaign_post_id ){
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(  "SELECT COUNT(r.post_id) as count
            FROM (SELECT p2p_to as post_id
            FROM $wpdb->p2p
            WHERE p2p_type = 'campaigns_to_subscriptions' AND p2p_from = %s) as t1
            LEFT JOIN $wpdb->dt_reports r ON t1.post_id=r.post_id
            WHERE r.post_id IS NOT NULL
            AND r.time_begin > UNIX_TIMESTAMP();", $campaign_post_id
        ) );
    }

    public static function query_total_events_count( $campaign_post_id ){
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(  "SELECT COUNT(r.post_id) as count
            FROM (SELECT p2p_to as post_id
            FROM $wpdb->p2p
            WHERE p2p_type = 'campaigns_to_subscriptions' AND p2p_from = %s) as t1
            LEFT JOIN $wpdb->dt_reports r ON t1.post_id=r.post_id
            WHERE r.post_id IS NOT NULL", $campaign_post_id
        ) );
    }

    public static function query_past_count( $campaign_post_id ){
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(r.post_id) as count
            FROM (SELECT p2p_to as post_id
            FROM $wpdb->p2p
            WHERE p2p_type = 'campaigns_to_subscriptions' AND p2p_from = %s) as t1
            LEFT JOIN $wpdb->dt_reports r ON t1.post_id=r.post_id
            WHERE r.post_id IS NOT NULL
            AND r.time_begin <= UNIX_TIMESTAMP();", $campaign_post_id
        ) );
    }

    public static function query_scheduled_minutes( $campaign_post_id ){
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( "SELECT
            SUM( ( time_end - time_begin ) / 60 ) AS minutes
            FROM $wpdb->dt_reports
            WHERE parent_id = %s AND post_type = 'subscriptions' AND type = 'campaign_app'
        ", $campaign_post_id
        ) );
    }

    public static function query_extra_minutes( $campaign_post_id ){
        $prayer_time_duration = DT_Time_Utilities::campaign_min_prayer_duration( $campaign_post_id );

        global $wpdb;
        $extra_people = $wpdb->get_var( $wpdb->prepare( "SELECT
            SUM( r.value - 1 ) AS extra_people
            FROM $wpdb->dt_reports r
            WHERE r.parent_id = %s AND r.post_type = 'campaigns' AND r.type = 'fuel'
            AND r.value > 1
            ;", $campaign_post_id
        ) );
        return $extra_people * $prayer_time_duration;
    }

    public static function time_prayed_as_a_group( $campaign_post_id ){
        $prayer_time_duration = DT_Time_Utilities::campaign_min_prayer_duration( $campaign_post_id );

        global $wpdb;
        $extra_people = $wpdb->get_var( $wpdb->prepare( "SELECT
            SUM( r.value ) AS extra_people
            FROM $wpdb->dt_reports r
            WHERE r.parent_id = %s AND r.post_type = 'campaigns' AND r.type = 'fuel'
            AND r.value > 1
            ;", $campaign_post_id
        ) );
        return $extra_people * $prayer_time_duration;
    }

    public static function get_minutes_prayed_and_scheduled( $campaign_id ){
        $scheduled = self::query_scheduled_minutes( $campaign_id );
        $extra = self::query_extra_minutes( $campaign_id );
        $total = $scheduled + $extra;
        return empty( $total ) ? 0 : $total;
    }

    public static function number_of_hours_next_month() {
        $days_next_month = self::number_of_days_next_month();
        return $days_next_month * 24;
    }

    public static function number_of_days_next_month() {
        return intval( gmdate( 'd', strtotime( 'last day of +1 month' ) ) );
    }

    public static function query_scheduled_minutes_next_month( $campaign_post_id ) {
        $time_begin = strtotime( gmdate( 'M d', strtotime( 'first day of +1 month' ) ) . ' 00:00' );
        $time_end = strtotime( gmdate( 'M d', strtotime( 'first day of +2 month' ) ) . ' 00:00' );
        global $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT
                SUM( ( r.time_end - r.time_begin ) / 60 ) AS minutes
                FROM (
                        SELECT p2p_to as post_id
                        FROM $wpdb->p2p
                        WHERE p2p_type = 'campaigns_to_subscriptions' AND p2p_from = %s
                    ) as t1
                LEFT JOIN $wpdb->dt_reports r ON t1.post_id=r.post_id
                WHERE r.post_id IS NOT NULL
                AND r.time_begin > %d
                AND r.time_end < %d;",
                $campaign_post_id, $time_begin, $time_end
            )
        );
    }

    public static function query_minutes_prayed( $campaign_post_id ){
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( "SELECT
            SUM( ( r.time_end - r.time_begin ) / 60 ) AS minutes
            FROM (SELECT p2p_to as post_id
            FROM $wpdb->p2p
            WHERE p2p_type = 'campaigns_to_subscriptions' AND p2p_from = %s) as t1
            LEFT JOIN $wpdb->dt_reports r ON t1.post_id=r.post_id
            WHERE r.post_id IS NOT NULL
            AND r.type = 'campaign_app'
            AND r.post_type = 'subscriptions'
            AND r.time_end <= UNIX_TIMESTAMP();", $campaign_post_id
        ) );
    }

    public static function query_coverage_percentage( $campaign_post_id, $month_limit = 2 ) {
        $percent = 0;
        $campaign = DT_Posts::get_post( 'campaigns', $campaign_post_id, true, false );
        $campaign_goal = Campaign_Utils::get_campaign_goal( $campaign );
        $times_list = DT_Time_Utilities::campaign_times_list( $campaign_post_id, $month_limit );
        $campaign_goal_quantity = Campaign_Utils::get_campaign_goal_quantity( $campaign );
        $min_time_duration = DT_Time_Utilities::campaign_min_prayer_duration( $campaign_post_id );
        $number_of_slots_needed_to_meet_goal = $campaign_goal_quantity * 60 / $min_time_duration;

        $blocks_covered = 0;
        $blocks = 0;
        $blocks_expected = 0;
        $covered_each_day = [];

        if ( ! empty( $times_list ) ) {
            foreach ( $times_list as $day ){
                if ( $campaign_goal === 'quantity' ){
                    //expect the goal or the max amount of slots in the day
                    $blocks_expected += $number_of_slots_needed_to_meet_goal;
                    $blocks_covered += min( $day['prayer_times'], $number_of_slots_needed_to_meet_goal );
                    $covered_each_day[] = $day['prayer_times'];
                } else {
                    $blocks_covered += $day['blocks_covered'];
                }
                $blocks += $day['time_slot_count'];
            }

            if ( $campaign_goal === 'quantity' ){
                $percent = $blocks ? ( $blocks_covered / $blocks_expected * 100 ) : 0;
                //allow for over 100% if the goal is met
                $lowest_day_percent = min( $covered_each_day ) / $number_of_slots_needed_to_meet_goal * 100;
                $percent = max( $percent, $lowest_day_percent );
            } else {
                $percent = $blocks ? ( $blocks_covered / $blocks * 100 ) : 0;
            }
        }
        return round( $percent, 2 );
    }

    public static function query_coverage_levels_progress( $campaign_post_id, $month_limit = 2 ) {
        $times_list = DT_Time_Utilities::campaign_times_list( $campaign_post_id, $month_limit );

        $blocks = 0;
        $blocks_covered = [];
        $res = [];
        $highest_number = 1;
        if ( ! empty( $times_list ) ) {
            foreach ( $times_list as $day ){
                $blocks += $day['time_slot_count'];
                foreach ( $day['hours'] as $hour ){
                    if ( $hour['subscribers'] > 0 && !$hour['outside_of_campaign'] ){
                        $highest_number = max( $hour['subscribers'], $highest_number );
                        if ( !isset( $blocks_covered[$hour['subscribers']] ) ){
                            $blocks_covered[$hour['subscribers']] = 0;
                        }
                        $blocks_covered[$hour['subscribers']]++;
                    }
                }
            }
            for ( $i = 1; $i <= $highest_number; $i++ ){
                $res[] = [
                    'key' => $i,
                    'blocks_covered' => 0,
                    'percent' => 0
                ];
            }

            foreach ( $blocks_covered as $number_of_prayers => $times_covered ){
                foreach ( $res as &$r ){
                    if ( $r['key'] <= $number_of_prayers ){
                        $r['blocks_covered'] += $times_covered;
                    }
                }
            }
            foreach ( $res as &$r ){
                $r['percent'] = round( $r['blocks_covered'] / $blocks * 100, 2 );
            }
        }
        return $res;
    }

    /**
     * Get the number of time slots the campaign will cover
     * @param int $campaign_post_id
     * @return int
     */
    public static function query_coverage_total_time_slots( $campaign_post_id ){
        $campaign = DT_Posts::get_post( 'campaigns', $campaign_post_id, true, false );
        $min_time_duration = 15;
        if ( isset( $campaign['min_time_duration']['key'] ) ){
            $min_time_duration = $campaign['min_time_duration']['key'];
        }
        if ( isset( $campaign['start_date']['timestamp'], $campaign['end_date']['timestamp'] ) ){
            $duration_in_seconds = (int) $campaign['end_date']['timestamp'] - (int) $campaign['start_date']['timestamp'];
            $duration_in_seconds += DAY_IN_SECONDS; // end of last day.
            $number_of_time_slots = $duration_in_seconds / ( $min_time_duration * 60 );
            return $number_of_time_slots;
        }
        return 0;
    }

    //generate a random theme color for the porch
    public static function random_color() {
        return sprintf( '#%06X', mt_rand( 0, 0xFFFFFF ) );
    }

    // get a theme color for the porch
    public static function color_from_pallet() {
        /**
         * Colors:
         * Espresso Brown #4B3621
         * Midnight Blue #002147
         * Deep Emerald #014421
         * Rich Burgundy #800020
         * Navy Indigo #1A1A40
         * Dark Teal #014D4E
         * Charcoal Gray #36454F
         * Deep Purple #2E0854
         * Forest Green #014421
         * Maroon #800000
         * Sapphire Blue #0F52BA
         * Oxidized Copper #6F4E37
         * Obsidian Black #0C0C0C
         * Royal Purple #3F00FF
         * Dark Olive #556B2F
         * Mahogany #4A0100
         * Deep Cyan #008B8B
         * Twighlight Blue #0C2340
         * Plum #8E4585
         * Gunmetal Gray #2a3439
         */
        $color_options = [ '#4B3621', '#002147', '#014421', '#800020', '#1A1A40', '#014D4E', '#36454F', '#2E0854', '#014421', '#800000', '#0F52BA', '#6F4E37', '#0C0C0C', '#3F00FF', '#556B2F', '#4A0100', '#008B8B', '#0C2340', '#8E4585', '#2a3439' ];
        return $color_options[ array_rand( $color_options ) ];
    }

    // filter at the start of post creation
    public function dt_post_create_fields( $fields, $post_type ){
        global $wpdb;
        if ( $post_type === $this->post_type ) {
            if ( !isset( $fields['status'] ) ) {
                $fields['status'] = 'active';
            }
            if ( !isset( $fields['porch_type'] ) ){
                $fields['porch_type'] = 'generic-porch';
            }
            if ( !isset( $fields['custom_theme_color'] ) ) {
                $fields['custom_theme_color'] = self::color_from_pallet();
            }
            if ( !isset( $fields['campaign_url'] ) ) {
                $fields['campaign_url'] = DT_Campaign_Landing_Settings::format_landing_page_url( $fields['name'] );
            }
            $url_exists = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) FROM $wpdb->postmeta WHERE meta_key = 'campaign_url' AND meta_value = %s", $fields['campaign_url'] ) );
            if ( !empty( $url_exists ) ){
                $fields['campaign_url'] = dt_create_field_key( $fields['campaign_url'], true );
            }
            if ( !isset( $fields['min_time_duration'] ) ){
                $fields['min_time_duration'] = '15';
            }
            $key_name = 'public_key';
            if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
                $key_name = DT_Magic_URL::get_public_key_meta_key( 'campaign_app', 'ongoing' );
            }
            if ( !isset( $fields[$key_name] ) ) {
                $fields[$key_name] = dt_create_unique_key();
            }
            //campaign goal
            if ( !isset( $fields['campaign_goal'] ) ){
                $fields['campaign_goal'] = '247coverage';
            }
            //signup frequency
            if ( !isset( $fields['enabled_frequencies'] ) ){
                $fields['enabled_frequencies'] = [ 'values' => [ [ 'value' => 'daily' ], [ 'value' => 'pick' ] ] ];
            }
        }
        return $fields;
    }

    public function dt_post_created( $post_type, $post_id, $initial_fields ){
        if ( $post_type === $this->post_type ){
            $porch_settings = DT_Porch_Settings::settings( null, null, null, $post_id );
            if ( !isset( $initial_fields['header_background_url'] ) && !empty( $porch_settings['header_background_url']['options'] ) ){
                shuffle( $porch_settings['header_background_url']['options'] );
                $update = [
                    'header_background_url' => $porch_settings['header_background_url']['options'][0]
                ];
                DT_Posts::update_post( $post_type, $post_id, $update );
            }
        }
    }

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

            $counts = self::get_all_status_types();
            $active_counts = [];
            $update_needed = 0;
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
                    if ( $status_key === 'active' ){
                        if ( $update_needed > 0 ){
                            $filters['filters'][] = [
                                'ID' => 'all_update_needed',
                                'tab' => 'all',
                                'name' => $fields['requires_update']['name'],
                                'query' => [
                                    'status' => [ 'active' ],
                                    'requires_update' => [ true ],
                                ],
                                'count' => $update_needed,
                                'subfilter' => true
                            ];
                        }
                    }
                }
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


    public static function campaign_stats( $post_id, $min_time_duration = 15 ){
        $coverage_levels = self::query_coverage_levels_progress( $post_id );
        $number_of_time_slots = self::query_coverage_total_time_slots( $post_id );
        $coverage_percentage = $coverage_levels[0]['percent'];
        $minutes_committed = self::get_minutes_prayed_and_scheduled( $post_id );

        return [
            'campaign_progress' => $coverage_percentage,
            'minutes_committed' => $minutes_committed,
            'number_of_time_slots' => $number_of_time_slots,
        ];
    }


    public function dt_prayer_campaigns_daily_cron(){
        self::send_campaign_info();
        self::close_campaigns();
    }

    /**
     * Report to P4M the status of the campaigns
     * @return array|false|WP_Error
     */
    public static function send_campaign_info(){
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ){
            return false;
        }
        $url = apply_filters( 'p4m_report_url', 'https://prayer.tools/wp-json/dt-public/campaigns/report' );
        if ( empty( $url ) ){
            return false;
        }

        $p4m_participation = apply_filters( 'p4m_participation', DT_Campaign_Global_Settings::get( 'p4m_participation', true ) );
        $default_campaign_id = get_option( 'dt_campaign_selected_campaign', false );

        $campaigns = DT_Posts::list_posts( 'campaigns', [ 'tags' => [ '-campaign-ended' ] ], false );
        $campaigns_to_send = [];
        $site_url = get_site_url( null, '', 'https' );
        $site_url = str_replace( 'http://', 'https://', $site_url );
        $site_hash = hash( 'sha256', $site_url );

        global $wpdb;

        foreach ( $campaigns['posts'] as $campaign ){
            if ( !isset( $campaign['start_date']['timestamp'] ) ){
                continue;
            }

            //get languages that have prayer fuel
            $language_counts = $wpdb->get_results( $wpdb->prepare( "
                SELECT pm.meta_value, count(pm.meta_value) as count
                FROM $wpdb->posts p
                LEFT JOIN $wpdb->postmeta pm ON ( pm.post_ID = p.ID and pm.meta_key = 'post_language' )
                INNER JOIN $wpdb->postmeta pm2 ON ( pm2.post_ID = p.ID and pm2.meta_key = 'linked_campaign' AND pm2.meta_value = %s )
                WHERE p.post_type = 'landing'
                AND ( p.post_status = 'publish' OR p.post_status = 'future' )
                GROUP BY pm.meta_value
            ", $campaign['ID'] ?? '0' ), ARRAY_A );
            $languages = [];
            foreach ( $language_counts as $lang ){
                if ( $lang['meta_value'] === null ){
                    $lang['meta_value'] = 'en_US';
                }
                if ( !in_array( $lang['meta_value'], $languages, true ) ){
                    $languages[] = $lang['meta_value'];
                }
            }
            $pray_fuel = array_map( function ( $a ){
                return [ 'value' => $a ];
            }, $languages );

            $min_time_duration = 15;
            if ( isset( $record['min_time_duration']['key'] ) ){
                $min_time_duration = $record['min_time_duration']['key'];
            }
            $coverage_levels = self::query_coverage_levels_progress( $campaign['ID'] );
            $mins_scheduled = self::query_scheduled_minutes( $campaign['ID'] );
            $mins_extra = self::query_extra_minutes( $campaign['ID'] );
            $time_lots_covered = $mins_scheduled / $min_time_duration;

            $is_current_campaign = (int) $campaign['ID'] === (int) $default_campaign_id;

            $focus = [];
            if ( isset( $campaign['porch_type']['key'] ) && $campaign['porch_type']['key'] === 'ramadan-porch' ){
                $focus[] = [ 'value' => 'ramadan' ];
            }

            $location_grid = [];
            foreach ( $campaign['location_grid'] ?? [] as $grid ){
                $location_grid[] = [ 'grid_id' => $grid['id'] ];
            }

            $campaign_url = $is_current_campaign ? $site_url : DT_Campaign_Landing_Settings::get_landing_page_url( $campaign['ID'] );
            $campaign_url = str_replace( 'http://', 'https://', $campaign_url );

            $data = [
                'p4m_participation' => $p4m_participation ? 'approval' : 'not_shown',
                'name' => $campaign['name'],
                'campaign_name' => $campaign['name'],
//                'status' => $campaign['status']['key'],
                'start_date' => $campaign['start_date']['timestamp'],
                'end_date' => isset( $campaign['end_date']['timestamp'] ) ? $campaign['end_date']['timestamp'] : null,
                'unique_id' => $site_hash . '_' . $campaign['ID'],
                'campaign_link' => $campaign_url,
                'campaign_links' => [
                    'values' => [
                        [ 'value' => $campaign_url, 'type' => 'default' ],
                    ]
                ],
                'campaign_progress' => $coverage_levels[0]['percent'],
                //'campaign_type' => $campaign['type']['key'],
                'focus' => empty( $focus ) ? [] : [ 'values' => $focus ],
                'minutes_committed' => $mins_scheduled + $mins_extra,
                'subscriber_count' => DT_Subscriptions::get_subscribers_count( $campaign['ID'] ),
                'slot_length' => (int) $min_time_duration,
                'number_of_time_slots' => self::query_coverage_total_time_slots( $campaign['ID'] ),
                'time_slots_covered' => $time_lots_covered,
                'location_grid_meta' => empty( $location_grid ) ? [] : [ 'values' => $location_grid ],
                'prayer_fuel_languages' => [ 'values' => $pray_fuel ],
            ];

            $campaigns_to_send[] = apply_filters( 'p4m_campaigns_to_send', $data, $campaign );
        }


        if ( !empty( $campaigns_to_send ) ){
            return wp_remote_post( $url, [ 'body' => [ 'campaigns' => $campaigns_to_send ] ] );
        }
        return true;
    }

    public static function close_campaigns(){
        $campaigns = DT_Posts::list_posts( 'campaigns', [ 'tags' => [ '-campaign-ended' ] ], false );
        foreach ( $campaigns['posts'] as $campaign ){
            if ( isset( $campaign['end_date']['timestamp'] ) && ( $campaign['end_date']['timestamp'] + DAY_IN_SECONDS ) < time() ){
                $close = [
                    'tags' => [ 'values' => [ [ 'value' => 'campaign-ended' ] ] ],
                    'status' => 'inactive'
                ];
                DT_Posts::update_post( 'campaigns', $campaign['ID'], $close, true, false );

                //if the campaign is linked to the current porch and if it ended recently, send an email to all subscribers
                if ( $campaign['end_date']['timestamp'] > time() - MONTH_IN_SECONDS ){
                    foreach ( $campaign['subscriptions'] as $subscription ){
                        wp_queue()->push( new End_Of_Campaign_Email_Job( $subscription['ID'], $campaign['ID'] ) );

                    }
                }
            }
        }
    }


    public static function schedule_campaign_sync_job(){
        wp_queue()->push( new P4M_Sync_Campaigns(), 0, 'campaign_sync' );
    }
}
use WP_Queue\Job;
class P4M_Sync_Campaigns extends Job {
    public function __construct(){
    }

    /**
     * Handle job logic.
     */
    public function handle(){
        if ( class_exists( 'DT_Campaigns_Base' ) ){
            DT_Campaigns_Base::send_campaign_info();
        }
    }
}
