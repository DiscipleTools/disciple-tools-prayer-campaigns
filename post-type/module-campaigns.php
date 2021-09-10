<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class DT_Subscriptions_Base
 * Load the core post type hooks into the Disciple Tools system
 */
class DT_Campaigns_Base {

    /**
     * Define post type variables
     * @var string
     */
    public $post_type = "campaigns";
    public $module = "campaigns_base";
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
        add_filter( "dt_front_page", [ $this, "dt_front_page" ] );

        //setup tiles and fields
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 20, 2 );

        // hooks
        add_filter( "dt_post_create_fields", [ $this, "dt_post_create_fields" ], 10, 2 );

        //list
        add_filter( "dt_user_list_filters", [ $this, "dt_user_list_filters" ], 10, 2 );
        add_filter( "dt_filter_access_permissions", [ $this, "dt_filter_access_permissions" ], 20, 2 );

    }

    public function after_setup_theme(){
        if ( class_exists( 'Disciple_Tools_Post_Type_Template' )) {
            new Disciple_Tools_Post_Type_Template( $this->post_type, $this->single_name, $this->plural_name );
        }
    }

    public function dt_front_page(){
        return site_url( '/campaigns' );
    }

    /**
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/roles-permissions.md#rolesd
     */
    public function dt_set_roles_and_permissions( $expected_roles ){

        $expected_roles["campaigns_admin"] = [
            "label" => __( 'Campaigns Admin', 'disciple_tools' ),
            "description" => __( 'Campaigns admin can administrate the prayer campaigns and subscriptions section', 'disciple_tools' ),
            "permissions" => [
                'access_disciple_tools' => true,
                'access_'.$this->post_type => true,
                'create_'.$this->post_type => true,
                'update_any_'.$this->post_type => true,
                'view_any_'.$this->post_type => true,
            ]
        ];

        if ( isset( $expected_roles["administrator"] ) ){
            $expected_roles["administrator"]["permissions"]['access_' . $this->post_type ] = true;
            $expected_roles["administrator"]["permissions"]['create_' . $this->post_type] = true;
            $expected_roles["administrator"]["permissions"]['view_any_'.$this->post_type ] = true;
            $expected_roles["administrator"]["permissions"]['update_any_'.$this->post_type ] = true;
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
                'name'        => __( 'Status', 'disciple_tools' ),
                'description' => _x( 'Set the current status.', 'field description', 'disciple_tools' ),
                'type'        => 'key_select',
                'default'     => [
                    'pre_signup'   => [
                        'label' => __( 'Pre Sign-Up', 'disciple_tools' ),
                        'description' => _x( 'Getting Ready', 'field description', 'disciple_tools' ),
                        'color' => "orange"
                    ],
                    'active'   => [
                        'label' => __( 'Active', 'disciple_tools' ),
                        'description' => _x( 'Is active.', 'field description', 'disciple_tools' ),
                        'color' => "#4CAF50"
                    ],
                    'inactive' => [
                        'label' => __( 'Inactive', 'disciple_tools' ),
                        'description' => _x( 'No longer active.', 'field description', 'disciple_tools' ),
                        'color' => "#F43636"
                    ],
                ],
                'tile'     => 'status',
                'icon' => get_template_directory_uri() . '/dt-assets/images/status.svg',
                "default_color" => "#366184",
                "show_in_table" => 10,
                "select_cannot_be_empty" => true,
            ];
            $fields['type'] = [
                'name'        => __( 'Campaign Type', 'disciple_tools' ),
                'description' => _x( 'Set the current type.', 'field description', 'disciple_tools' ),
                'type'        => 'key_select',
                'default'     => apply_filters( 'dt_campaign_types', [] ),
                'tile'     => 'status',
//                'icon' => get_template_directory_uri() . '/dt-assets/images/status.svg',
                "default_color" => "#F43636",
                "show_in_table" => 15,
                "in_create_form" => false
            ];
            // end basic framework fields


            $fields["languages"] = [
                'name' => __( 'Subscriber Preferred Language', 'disciple_tools' ),
                'description' => __( 'Subscriber preferred language', 'disciple_tools' ),
                'type' => 'key_select',
                "tile" => "details",
                'default' => dt_get_option( "dt_working_languages" ) ?: [ 'en' ],
                'icon' => get_template_directory_uri() . "/dt-assets/images/languages.svg",
            ];
            $fields["peoplegroups"] = [
                "name" => __( 'People Groups', 'disciple_tools' ),
                'description' => _x( 'The people groups connected to this record.', 'Optional Documentation', 'disciple_tools' ),
                "type" => "connection",
                "post_type" => "peoplegroups",
                "tile" => "details",
                "p2p_direction" => "to",
                "p2p_key" => $this->post_type."_to_peoplegroups"
            ];

            $fields['start_date'] = [
                'name'        => __( 'Start Date', 'disciple_tools' ),
                'required' => true,
                'description' => '',
                'type'        => 'date',
                'default'     => time(),
                'tile' => 'campaign_setup',
                'icon' => get_template_directory_uri() . '/dt-assets/images/date-start.svg',
                "in_create_form" => true,
                "show_in_table" => 101
            ];
            $fields['end_date'] = [
                'name'        => __( 'End Date', 'disciple_tools' ),
                'required' => true,
                'description' => '',
                'type'        => 'date',
                'default'     => '',
                'tile' => 'campaign_setup',
                'icon' => get_template_directory_uri() . '/dt-assets/images/date-end.svg',
                "in_create_form" => true,
                "show_in_table" => 102
            ];
            $fields["last_modified"]["show_in_table"] = false;
            $fields["favorite"]["show_in_table"] = false;

            $timezones = [];
            $tzlist = DateTimeZone::listIdentifiers( DateTimeZone::ALL );
            foreach ( $tzlist as $tz ){
                $timezones[$tz] = [
                    "label" => $tz
                ];
            }
            $fields["campaign_timezone"] = [
                "name" => __( "Campaign Time Zone", 'disciple_tools' ),
                'required' => true,
                "in_create_form" => true,
                "default" => $timezones,
                "type" => "key_select",
                "tile" => "campaign_setup",
            ];

            $fields["min_time_duration"] = [
                "name" => __( "Prayer Time Duration", 'disciple_tools' ),
                "type" => "key_select",
                "default" => [
                    "15" => [ "label" => "15 Minutes", "default" => true ], //keep as first item
                    "10" => [ "label" => "10 Minutes" ],
                    "5" => [ "label" => "5 Minutes" ],
                ],
                "tile" => "campaign_setup"
            ];

            $fields["duration_options"] = [
                "name" => __( "Duration options", 'disciple-tools-prayer-campaigns' ),
                "type" => "key_select",
                "default" => [
                    "5" => [ "label" => __( "5 minutes", 'disciple-tools-prayer-campaigns' ) ],
                    "10" => [ "label" => __( "10 minutes", 'disciple-tools-prayer-campaigns' ) ],
                    "15" => [ "label" => __( "15 minutes", 'disciple-tools-prayer-campaigns' ) ],
                    "30" => [ "label" => __( "30 minutes", 'disciple-tools-prayer-campaigns' ) ],
                    "60" => [ "label" => __( "1 hour", 'disciple-tools-prayer-campaigns' ) ],
                ]
            ];

            $fields["strings_translations"] = [
                "name" => "String Translations",
                "type" => "array",
                $default = [
                    "en_US" => [
                        "title" => "Campaign Description"
                    ]
                ],
                "hidden" => true
            ];

            $key_name = 'public_key';
            if ( method_exists( "DT_Magic_URL", "get_public_key_meta_key" ) ){
                $key_name = DT_Magic_URL::get_public_key_meta_key( "campaign_app", "24hour" );
            }
            $fields[$key_name] = [
                'name'   => __( 'Private Key', 'disciple_tools' ),
                'description' => __( 'Private key for subscriber access', 'disciple_tools' ),
                'type'   => 'hash',
                'default' => dt_create_unique_key(),
                'hidden' => true,
                "customizable" => false,
            ];

            /**
             * location elements
             */
            $fields['location_grid'] = [
                'name'        => __( 'Locations', 'disciple_tools' ),
                'description' => _x( 'The general location where this contact is located.', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'location',
                'mapbox'    => false,
                "customizable" => false,
                "in_create_form" => true,
                "tile" => "campaign_setup",
                "icon" => get_template_directory_uri() . "/dt-assets/images/location.svg",
            ];
            $fields['location_grid_meta'] = [
                'name'        => __( 'Locations', 'disciple_tools' ), //system string does not need translation
                'description' => _x( 'The general location where this contact is located.', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'location_meta',
                "tile"      => "campaign_setup",
                'mapbox'    => false,
                'hidden' => true,
                "icon" => get_template_directory_uri() . "/dt-assets/images/location.svg",
            ];
            $fields["contact_address"] = [
                "name" => __( 'Address', 'disciple_tools' ),
                "icon" => get_template_directory_uri() . "/dt-assets/images/house.svg",
                "type" => "communication_channel",
                "tile" => "",
                'mapbox'    => false,
                "customizable" => false
            ];
            if ( DT_Mapbox_API::get_key() ){
                $fields["contact_address"]["custom_display"] = true;
                $fields["contact_address"]["mapbox"] = true;
                unset( $fields["contact_address"]["tile"] );
                $fields["location_grid"]["mapbox"] = true;
                $fields["location_grid_meta"]["mapbox"] = true;
                $fields["location_grid"]["hidden"] = true;
                $fields["location_grid_meta"]["hidden"] = false;
            }

            $fields["subscriptions"] = [
                "name" => __( 'Subscriptions', 'disciple_tools' ),
                'description' => _x( 'The contacts who are members of this group.', 'Optional Documentation', 'disciple_tools' ),
                "type" => "connection",
                "post_type" => "subscriptions",
                "p2p_direction" => "from",
                "customizable" => false,
                'hidden' => true,
                "p2p_key" => 'campaigns_to_subscriptions',
                'icon' => get_template_directory_uri() . "/dt-assets/images/nametag.svg",
            ];


        }

        if ( $post_type === "subscriptions" ){
            $fields['campaigns'] = [
                "name" => $this->plural_name,
                "description" => '',
                "type" => "connection",
                "post_type" => $this->post_type,
                "p2p_direction" => "to",
                "p2p_key" => 'campaigns_to_subscriptions',
                "tile" => "",
                'icon' => get_template_directory_uri() . "/dt-assets/images/group-type.svg",
                'create-icon' => get_template_directory_uri() . "/dt-assets/images/add-group.svg",
                "show_in_table" => 35,
                "customizable" => false,
            ];
        }

        return $fields;
    }

    /**
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/field-and-tiles.md
     */
    public function dt_details_additional_tiles( $tiles, $post_type = "" ){
        if ( $post_type === $this->post_type ){
            $tiles["campaign_setup"] = [ "label" => __( "Campaign Setup", 'disciple_tools' ) ];
            $tiles["commitments"] = [ "label" => __( "Commitments", 'disciple_tools' ) ];
        }
        return $tiles;
    }

    /**
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/field-and-tiles.md#add-custom-content
     */
    public function dt_details_additional_section( $section, $post_type ){

        if ( $post_type === $this->post_type && $section === "commitments" ){
            $subscribers_count = DT_Subscriptions::get_subscribers_count( get_the_ID() );
            $coverage_count = $this->query_coverage_percentage( get_the_ID() );
            $scheduled_commitments = $this->query_scheduled_count( get_the_ID() );
            $past_commitments = $this->query_past_count( get_the_ID() );
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
                <button class="button hollow" id="campaign_coverage_stats">Stats</button>
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
                 makeRequest( 'GET', 'subscribers', { campaign_id: '<?php echo get_the_ID() ?>' }, 'campaigns/v1')
                .done(function(data){
                    let content = `<ol>`
                    if ( data ) {
                        jQuery.each(data, function(i,v){
                            content += `<li>
                                <a href="/subscriptions/${window.lodash.escape(v.ID)}">
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
                window.makeRequest( 'GET', 'coverage-stats', { campaign_id: '<?php echo get_the_ID() ?>' }, 'campaigns/v1')
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

            /* campaign coverage */
            $('#campaign_coverage_chart').on('click', function(e){
                $('#modal-full-title').empty().html(`<h2>Coverage Chart</h2><span style="font-size:.7em;">Cells contain subscriber count per block of time.</span><hr>`)

                let container = $('#modal-full-content')
                container.empty().html(`
                <span class="loading-spinner active"></span>
                `)

                makeRequest( 'GET', 'coverage', { campaign_id: '<?php echo get_the_ID() ?>' }, 'campaigns/v1')
                .done(function(data){
                    console.log(data)
                    let content = `<style>#cover-table td:hover {border: 1px solid darkslateblue;}</style><div class="table-scroll"><table id="cover-table" class="center">`
                    /* top row */
                    jQuery.each(data, function(i,v){
                        content += `<tr><th></th>`
                        let c = 0
                        jQuery.each(v.hours, function(ii,vv){
                            if ( c >= 20 ){
                                 content += `<th></th>`
                                c = 0
                            } else {
                                c++
                            }
                            content += `<th>${vv.formatted}</th>`
                        })
                        content += `</tr>`
                        return false // looping only once for the column titles
                    })
                    /* table body */
                    jQuery.each(data, function(i,v){
                        content += `<tr><th style="white-space:nowrap">${v.formatted}</th>`
                        let c = 0
                        jQuery.each(v.hours, function(ii,vv){
                            if ( c >= 20 ){
                                 content += `<th style="white-space:nowrap">${v.formatted}</th>`
                                c = 0
                            } else {
                                c++
                            }
                            if ( vv.subscribers > 0 ){
                                    content += `<td style="background-color:lightblue;">${vv.subscribers}</td>`
                                } else {
                                    content += `<td>${vv.subscribers}</td>`
                                }
                        })

                        content += `</tr>`
                    })
                    content += `</table></div>`

                    container.empty().html(content)

                })


                $('#modal-full').foundation('open')
            })

        })
        </script>


        <?php }

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
        $subscribers = $wpdb->get_results( $wpdb->prepare( "
            SELECT p.post_title as name, p.ID,
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
            if ( $sub["commitments"] !== "0"){
                $times = Disciple_Tools_Reports::get( $sub["ID"], 'post_id' );
                foreach ( $times as $time ){
                    $day = gmdate( "Y-m-d", $time["time_begin"] );
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
            $sub["unique_days_covered"] = sizeof( $unique_days );
        }
        return [
            "unique_days_covered" => $unique_days_covered,
        ];
    }

    public function query_scheduled_count( $campaign_post_id ){
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare(  "SELECT COUNT(r.post_id) as count
            FROM (SELECT p2p_to as post_id
            FROM $wpdb->p2p
            WHERE p2p_type = 'campaigns_to_subscriptions' AND p2p_from = %s) as t1
            LEFT JOIN $wpdb->dt_reports r ON t1.post_id=r.post_id
            WHERE r.post_id IS NOT NULL
            AND r.time_begin > UNIX_TIMESTAMP();", $campaign_post_id
        ) );
    }

    public function query_past_count( $campaign_post_id ){
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

    public static function query_coverage_percentage( $campaign_post_id ) {
        $percent = 0;
        $times_list = DT_Time_Utilities::campaign_times_list( $campaign_post_id );
        $record = DT_Posts::get_post( "campaigns", $campaign_post_id );
        $min_time_duration = 15;
        if ( isset( $record["min_time_duration"]["key"] ) ){
            $min_time_duration = $record["min_time_duration"]["key"];
        }

        $day_count = 0;
        $blocks_covered = 0;
        if ( ! empty( $times_list ) ) {
            foreach ( $times_list as $day ){
                $day_count++;
                $blocks_covered += $day['blocks_covered'];
            }

            $blocks = $day_count * ( 24 * 60 ) / $min_time_duration; // number of blocks of x minutes for a 24 hour period
            $percent = $blocks_covered / $blocks * 100;
        }
        return round( $percent, 1 );
    }

    public static function query_coverage_levels_progress( $campaign_post_id ) {
        $times_list = DT_Time_Utilities::campaign_times_list( $campaign_post_id );
        $record = DT_Posts::get_post( "campaigns", $campaign_post_id, true, false );
        $min_time_duration = 15;
        if ( isset( $record["min_time_duration"]["key"] ) ){
            $min_time_duration = $record["min_time_duration"]["key"];
        }

        $day_count = 0;
        $blocks_covered = [];
        $res = [];
        $highest_number = 1;
        if ( ! empty( $times_list ) ) {
            foreach ( $times_list as $day ){
                $day_count++;
                foreach ( $day["hours"] as $hour ){
                    if ( $hour["subscribers"] > 0 ){
                        $highest_number = max( $hour["subscribers"], $highest_number );
                        if ( !isset( $blocks_covered[$hour["subscribers"]] ) ){
                            $blocks_covered[$hour["subscribers"]] = 0;
                        }
                        $blocks_covered[$hour["subscribers"]]++;
                    }
                }
            }
            for ( $i = 1; $i <= $highest_number; $i++ ){
                $res[] = [
                    "key" => $i,
                    "blocks_covered" => 0,
                    "percent" => 0
                ];
            }

            foreach ( $blocks_covered as $number_of_prayers => $times_covered ){
                foreach ($res as &$r ){
                    if ( $r["key"] <= $number_of_prayers ){
                        $r["blocks_covered"] += $times_covered;
                    }
                }
            }
            $total_blocks = $day_count * ( 24 * 60 ) / $min_time_duration; // number of blocks of x minutes for a 24 hour period
            foreach ($res as &$r ){
                $r["percent"] = round( $r["blocks_covered"] / $total_blocks * 100, 2 );
            }
        }
        return $res;
    }

    /**
     * Get the number of time slots the campaign will cover
     * @param $campaign_post_id
     * @return int
     */
    public static function query_coverage_total_time_slots( $campaign_post_id ){
        $campaign = DT_Posts::get_post( "campaigns", $campaign_post_id, true, false );
        $min_time_duration = 15;
        if ( isset( $campaign["min_time_duration"]["key"] ) ){
            $min_time_duration = $campaign["min_time_duration"]["key"];
        }
        if ( isset( $campaign["start_date"]["timestamp"], $campaign["end_date"]["timestamp"] ) ){
            $duration_in_seconds = (int) $campaign["end_date"]["timestamp"] - (int) $campaign["start_date"]["timestamp"];
            $duration_in_seconds += 86400; // end of last day.
            $number_of_time_slots = $duration_in_seconds / ( $min_time_duration * 60 );
            return $number_of_time_slots;
        }
        return 0;
    }

    // filter at the start of post creation
    public function dt_post_create_fields( $fields, $post_type ){
        if ( $post_type === $this->post_type ) {
            if ( !isset( $fields["status"] ) ) {
                $fields["status"] = "active";
            }
            if ( !isset( $fields["type"] ) ){
                $fields["type"] = "24hour";
            }
            $key_name = 'public_key';
            if ( method_exists( "DT_Magic_URL", "get_public_key_meta_key" ) ){
                $key_name = DT_Magic_URL::get_public_key_meta_key( "campaign_app", $fields["type"] );
            }
            if ( !isset( $fields[$key_name] ) ) {
                $fields[$key_name] = dt_create_unique_key();
            }
            if ( !isset( $fields["min_time_duration"] ) ){
                $fields["min_time_duration"] = "15";
            }
        }
        return $fields;
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

            $counts = self::get_all_status_types();
            $active_counts = [];
            $update_needed = 0;
            $status_counts = [];
            $total_all = 0;
            foreach ( $counts as $count ){
                $total_all += $count["count"];
                dt_increment( $status_counts[$count["status"]], $count["count"] );
                if ( $count["status"] === "active" ){
                    dt_increment( $active_counts[$count["status"]], $count["count"] );
                }
            }
            $filters["tabs"][] = [
                "key" => "all",
                "label" => _x( "All", 'List Filters', 'disciple_tools' ),
                "count" => $total_all,
                "order" => 10
            ];
            // add assigned to me filters
            $filters["filters"][] = [
                'ID' => 'all',
                'tab' => 'all',
                'name' => _x( "All", 'List Filters', 'disciple_tools' ),
                'query' => [
                    'sort' => '-post_date'
                ],
                "count" => $total_all
            ];

            foreach ( $fields["status"]["default"] as $status_key => $status_value ) {
                if ( isset( $status_counts[$status_key] ) ){
                    $filters["filters"][] = [
                        "ID" => 'all_' . $status_key,
                        "tab" => 'all',
                        "name" => $status_value["label"],
                        "query" => [
                            'status' => [ $status_key ],
                            'sort' => '-post_date'
                        ],
                        "count" => $status_counts[$status_key]
                    ];
                    if ( $status_key === "active" ){
                        if ( $update_needed > 0 ){
                            $filters["filters"][] = [
                                "ID" => 'all_update_needed',
                                "tab" => 'all',
                                "name" => $fields["requires_update"]["name"],
                                "query" => [
                                    'status' => [ 'active' ],
                                    'requires_update' => [ true ],
                                ],
                                "count" => $update_needed,
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
}
