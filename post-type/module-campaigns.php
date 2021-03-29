<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class DT_Subscriptions_Base
 * Load the core post type hooks into the Disciple Tools system
 */
class DT_Campaigns_Base extends DT_Module_Base {

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
        parent::__construct();
        if ( !self::check_enabled_and_prerequisites() ){
            return;
        }

        //setup post type
        add_action( 'after_setup_theme', [ $this, 'after_setup_theme' ], 100 );
        add_filter( 'dt_set_roles_and_permissions', [ $this, 'dt_set_roles_and_permissions' ], 20, 1 ); //after contacts

        //setup tiles and fields
        add_action( 'p2p_init', [ $this, 'p2p_init' ] );
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 20, 2 );
        add_filter( 'desktop_navbar_menu_options', [ $this, 'desktop_navbar_menu_options' ], 1000, 1 );
//        add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );

        // hooks
//        add_action( "post_connection_removed", [ $this, "post_connection_removed" ], 10, 4 );
//        add_action( "post_connection_added", [ $this, "post_connection_added" ], 10, 4 );
//        add_action( "dt_comment_created", [ $this, "dt_comment_created" ], 10, 4 );
        add_filter( "dt_post_update_fields", [ $this, "dt_post_update_fields" ], 10, 3 );
        add_filter( "dt_post_create_fields", [ $this, "dt_post_create_fields" ], 10, 2 );
        add_action( "dt_post_created", [ $this, "dt_post_created" ], 10, 3 );

        //list
        add_filter( "dt_user_list_filters", [ $this, "dt_user_list_filters" ], 10, 2 );
        add_filter( "dt_filter_access_permissions", [ $this, "dt_filter_access_permissions" ], 20, 2 );

    }

    public function after_setup_theme(){
        if ( class_exists( 'Disciple_Tools_Post_Type_Template' )) {
            new Disciple_Tools_Post_Type_Template( $this->post_type, $this->single_name, $this->plural_name );
        }
    }

    /**
     * Modifies the top tabs to add campaigns under the drop down
     * @param $tabs
     * @return mixed
     */
    public function desktop_navbar_menu_options( $tabs ) {
        if ( isset( $tabs[$this->post_type] ) ) {
            unset( $tabs['subscriptions'] );
        }
        if ( $tabs['campaigns'] ) {
            $tabs['campaigns']['submenu']['campaigns'] = $tabs['campaigns'];
            $tabs['campaigns']['submenu']['subscriptions'] = [
                "link" => site_url( "/subscriptions/" ),
                "label" => __( 'Subscriptions', 'disciple_tools' ),
                'icon' => '',
                'hidden' => false,
            ];
        }
        return $tabs;
    }

    /**
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/roles-permissions.md#rolesd
     */
    public function dt_set_roles_and_permissions( $expected_roles ){

        $expected_roles["subscriptions_admin"] = [
            "label" => __( 'Subscriptions Admin', 'disciple_tools' ),
            "description" => __( 'Subscriptions admin can administrate the prayer subscriptions section', 'disciple_tools' ),
            "permissions" => [
                'view_any_'.$this->post_type => true,
                'dt_all_admin_' . $this->post_type => true,
            ]
        ];

        if ( !isset( $expected_roles["dt_admin"] ) ){
            $expected_roles["dt_admin"] = [
                "label" => __( 'Disciple.Tools Admin', 'disciple-tools-training' ),
                "description" => "All D.T permissions",
                "permissions" => []
            ];
        }
        if ( !isset( $expected_roles["administrator"] ) ){
            $expected_roles["administrator"] = [
                "label" => __( 'Administrator', 'disciple-tools-training' ),
                "description" => "All D.T permissions plus the ability to manage plugins.",
                "permissions" => []
            ];
        }

        if ( isset( $expected_roles["administrator"] ) ){
            $expected_roles["administrator"]["permissions"]['view_any_'.$this->post_type ] = true;
            $expected_roles["administrator"]["permissions"]['update_any_'.$this->post_type ] = true;
            $expected_roles["dt_admin"]["permissions"][ 'dt_all_admin_' . $this->post_type] = true;
        }

        return $expected_roles;
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
            $fields['tags'] = [
                'name'        => __( 'Tags', 'disciple_tools' ),
                'description' => _x( 'A useful way to group related items.', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'multi_select',
                'default'     => [],
                'tile'        => 'other',
                'custom_display' => true,
                "customizable" => false,
            ];
            $fields["follow"] = [
                'name'        => __( 'Follow', 'disciple_tools' ),
                'type'        => 'multi_select',
                'default'     => [],
                'section'     => 'misc',
                'hidden'      => true,
                "customizable" => false,
            ];
            $fields["unfollow"] = [
                'name'        => __( 'Un-Follow', 'disciple_tools' ),
                'type'        => 'multi_select',
                'default'     => [],
                'hidden'      => true,
                "customizable" => false,
            ];
            $fields['tasks'] = [
                'name' => __( 'Tasks', 'disciple_tools' ),
                'type' => 'post_user_meta',
                "customizable" => false,
            ];
            $fields['status'] = [
                'name'        => __( 'Status', 'disciple_tools' ),
                'description' => _x( 'Set the current status.', 'field description', 'disciple_tools' ),
                'type'        => 'key_select',
                'default'     => [
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
            ];
            $fields['type'] = [
                'name'        => __( 'Campaign Type', 'disciple_tools' ),
                'description' => _x( 'Set the current type.', 'field description', 'disciple_tools' ),
                'type'        => 'key_select',
                'default'     => apply_filters( 'dt_campaign_types', [] ),
                'tile'     => 'status',
//                'icon' => get_template_directory_uri() . '/dt-assets/images/status.svg',
                "default_color" => "#F43636",
                "show_in_table" => 10,
                "in_create_form" => false
            ];
//            $fields['assigned_to'] = [
//                'name'        => __( 'Assigned To', 'disciple_tools' ),
//                'description' => __( "Select the main person who is responsible for reporting on this record.", 'disciple_tools' ),
//                'type'        => 'user_select',
//                'default'     => '',
//                'tile' => 'status',
//                'icon' => get_template_directory_uri() . '/dt-assets/images/assigned-to.svg',
//                "show_in_table" => 16,
//                'custom_display' => true,
//            ];
//            $fields["requires_update"] = [
//                'name'        => __( 'Requires Update', 'disciple_tools' ),
//                'description' => '',
//                'type'        => 'boolean',
//                'default'     => false,
//            ];
            // end basic framework fields


            $fields['description'] = [
                'name'   => __( 'Public campaign description', 'disciple_tools' ),
                'description' => __( 'General description about the campaign', 'disciple_tools' ),
                'type'   => 'text',
                "tile" => "details",
                'default' => '',
                "customizable" => true,
                "in_create_form" => true,
                'icon' => get_template_directory_uri() . '/dt-assets/images/sign-post.svg',
            ];
            $fields["languages"] = [
                'name' => __( 'Subscriber Preferred Language', 'disciple_tools' ),
                'description' => __( 'Subscriber preferred language', 'disciple_tools' ),
                'type' => 'key_select',
                "tile" => "details",
                "in_create_form" => true,
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
                'description' => '',
                'type'        => 'date',
                'default'     => time(),
                'tile' => 'time',
                'icon' => get_template_directory_uri() . '/dt-assets/images/date-start.svg',
                "in_create_form" => true,
            ];
            $fields['end_date'] = [
                'name'        => __( 'End Date', 'disciple_tools' ),
                'description' => '',
                'type'        => 'date',
                'default'     => '',
                'tile' => 'time',
                'icon' => get_template_directory_uri() . '/dt-assets/images/date-end.svg',
                "in_create_form" => true,
            ];


            $fields['public_key'] = [
                'name'   => __( 'Private Key', 'disciple_tools' ),
                'description' => __( 'Private key for subscriber access', 'disciple_tools' ),
                'type'   => 'hash',
                'default' => DT_Subscriptions_Base::instance()->create_unique_key(),
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
                "tile" => "",
                "icon" => get_template_directory_uri() . "/dt-assets/images/location.svg",
            ];
            $fields['location_grid_meta'] = [
                'name'        => __( 'Locations', 'disciple_tools' ), //system string does not need translation
                'description' => _x( 'The general location where this contact is located.', 'Optional Documentation', 'disciple_tools' ),
                'type'        => 'location_meta',
                "tile"      => "locations",
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

            $fields["prayer_content_message"] = [
                "name" => __( "Prayer Content message", 'disciple_tools' ),
                "type" => "text",
                "tile" => "details"
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
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/fields.md#declaring-connection-fields
     */
    public function p2p_init(){

        p2p_register_connection_type(
            [
                'name'           => "campaigns_to_subscriptions",
                'from'           => 'campaigns',
                'to'             => 'subscriptions',
                'admin_box' => [
                    'show' => false,
                ],
                'title'          => [
                    'from' => __( 'Campaigns', 'disciple_tools' ),
                    'to'   => 'Subscriptions',
                ]
            ]
        );
        p2p_register_connection_type(
            [
                'name'        => $this->post_type."_to_peoplegroups",
                'from'        => $this->post_type,
                'to'          => 'peoplegroups',
                'title'       => [
                    'from' => __( 'People Groups', 'disciple_tools' ),
                    'to'   => $this->plural_name,
                ]
            ]
        );
    }

    /**
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/field-and-tiles.md
     */
    public function dt_details_additional_tiles( $tiles, $post_type = "" ){
        if ( $post_type === $this->post_type ){
            $tiles["location"] = [ "label" => __( "Geo Focus", 'disciple_tools' ) ];
            $tiles["time"] = [ "label" => __( "Time Range", 'disciple_tools' ) ];
            $tiles["commitments"] = [ "label" => __( "Commitments", 'disciple_tools' ) ];
            $tiles["other"] = [ "label" => __( "Other", 'disciple_tools' ) ];
        }
        return $tiles;
    }

    /**
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/field-and-tiles.md#add-custom-content
     */
    public function dt_details_additional_section( $section, $post_type ){

        if ( $post_type === $this->post_type && $section === "commitments" ){
            $subscribers_count = $this->query_subscriber_count( get_the_ID() );
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
                 makeRequest( 'GET', '/subscribers', { campaign_id: '<?php echo get_the_ID() ?>' }, 'campaigns/v1')
                .done(function(data){
                    let content = `<ol>`
                    if ( data ) {
                        jQuery.each(data, function(i,v){
                            content += `<li><a href="/subscriptions/${window.lodash.escape(v.ID)}">${window.lodash.escape(v.name)} (${window.lodash.escape(v.commitments)})</a></li>`
                        })
                    } else {
                        content += `<div class="cell">No subscribers found</div>`
                    }
                    content += `</ol>`
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

                makeRequest( 'GET', '/coverage', { campaign_id: '<?php echo get_the_ID() ?>' }, 'campaigns/v1')
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

        if ( $post_type === $this->post_type && $section === "location" ){
            $fields = DT_Posts::get_post_field_settings( $post_type );
            ?>
            <div class="cell small-12">
                <div class="section-subheader">
                    <img src="<?php echo esc_url( get_template_directory_uri() . "/dt-assets/images/location.svg" ) ?>" /> <?php echo esc_html( $fields['location_grid']['name'] ) ?>
                </div>
                <div class="dt_location_grid" data-id="location_grid">
                    <var id="location_grid-result-container" class="result-container"></var>
                    <div id="location_grid_t" name="form-location_grid" class="scrollable-typeahead typeahead-margin-when-active">
                        <div class="typeahead__container">
                            <div class="typeahead__field">
                                    <span class="typeahead__query">
                                        <input class="js-typeahead-location_grid input-height"
                                               data-field="location_grid"
                                               data-field_type="location"
                                               name="location_grid[query]"
                                               placeholder="<?php echo esc_html( sprintf( _x( "Search %s", "Search 'something'", 'disciple_tools' ), $fields['location_grid']['name'] ) )?>"
                                               autocomplete="off" />
                                    </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php }

        if ( $post_type === $this->post_type && $section === "other" ) :
            $fields = DT_Posts::get_post_field_settings( $post_type );
            ?>
            <div class="section-subheader">
                <?php echo esc_html( $fields["tags"]["name"] ) ?>
            </div>
            <div class="tags">
                <var id="tags-result-container" class="result-container"></var>
                <div id="tags_t" name="form-tags" class="scrollable-typeahead typeahead-margin-when-active">
                    <div class="typeahead__container">
                        <div class="typeahead__field">
                            <span class="typeahead__query">
                                <input class="js-typeahead-tags input-height"
                                       name="tags[query]"
                                       placeholder="<?php echo esc_html( sprintf( _x( "Search %s", "Search 'something'", 'disciple_tools' ), $fields["tags"]['name'] ) )?>"
                                       autocomplete="off">
                            </span>
                            <span class="typeahead__button">
                                <button type="button" data-open="create-tag-modal" class="create-new-tag typeahead__image_button input-height">
                                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/tag-add.svg' ) ?>"/>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif;

    }

    public function add_api_routes() {
        $namespace = 'campaigns/v1';
        register_rest_route(
            $namespace, '/subscribers', [
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
            $namespace, '/coverage', [
                [
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => [ $this, 'coverage_endpoint' ],
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
        global $wpdb;
        $campaign_post_id = sanitize_text_field( wp_unslash( $params['campaign_id'] ) );
        return $wpdb->get_results( $wpdb->prepare( "
            SELECT p.post_title as name, p.ID,
                   (SELECT COUNT(r.post_id)
                   FROM $wpdb->dt_reports r
                   WHERE r.post_type = 'subscriptions'
                     AND r.parent_id = %s
                     AND r.post_id = p.ID) as commitments
            FROM $wpdb->p2p p2
            LEFT JOIN $wpdb->posts p ON p.ID=p2.p2p_to
            WHERE p2p_type = 'campaigns_to_subscriptions'
            AND p2p_from = %s", $campaign_post_id, $campaign_post_id
        ), ARRAY_A );

    }

    public function coverage_endpoint( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( ! isset( $params['campaign_id'] ) ) {
            return new WP_Error( __METHOD__, 'Required parameter not set' );
        }
        $campaign_post_id = sanitize_text_field( wp_unslash( $params['campaign_id'] ) );
        return DT_Time_Utilities::campaign_times_list( $campaign_post_id );

    }

    public function query_subscriber_count( $campaign_post_id ){
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( "SELECT count(DISTINCT p2p_to) as count FROM $wpdb->p2p WHERE p2p_type = 'campaigns_to_subscriptions' AND p2p_from = %s", $campaign_post_id ) );
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

        $day_count = 0;
        $blocks_covered = 0;
        if ( ! empty( $times_list ) ) {
            foreach ( $times_list as $day ){
                $day_count++;
                $blocks_covered += $day['blocks_covered'];
            }

            $blocks = $day_count * 96; // 96 blocks of 15 minutes for a 24 hour period
            $percent = $blocks_covered / $blocks * 100;
        }
        return round( $percent, 1 );
    }

    /**
     * Get the number of time slots the campaign will cover
     * @param $campaign_post_id
     * @return int
     */
    public static function query_coverage_total_time_slots( $campaign_post_id ){
        $campaign = DT_Posts::get_post( "campaigns", $campaign_post_id, true, false );
        if ( isset( $campaign["start_date"]["timestamp"], $campaign["end_date"]["timestamp"] ) ){
            $duration_in_seconds = (int) $campaign["end_date"]["timestamp"] - (int) $campaign["start_date"]["timestamp"];
            $number_of_time_slots = $duration_in_seconds / ( 15 * 60 );
            return $number_of_time_slots;
        }
        return 0;
    }

    //filter when a comment is created
    public function dt_comment_created( $post_type, $post_id, $comment_id, $type ){
        // action when comment is created
    }

    //filter at the start of post update
    public function dt_post_update_fields( $fields, $post_type, $post_id ){
        // filter the fields when being updated
        return $fields;
    }

    // filter at the start of post creation
    public function dt_post_create_fields( $fields, $post_type ){
        if ( $post_type === $this->post_type ) {
            if ( !isset( $fields["status"] ) ) {
                $fields["status"] = "active";
            }
            if ( !isset( $fields['public_key'] ) ) {
                $fields['public_key'] = $this->create_unique_key();
            }
            if ( !isset( $fields["type"] ) ){
                $fields["type"] = "24hour";
            }
        }
        return $fields;
    }

    public function create_unique_key() : string {
        try {
            $hash = hash( 'sha256', bin2hex( random_bytes( 256 ) ) );
        } catch ( Exception $exception ) {
            $hash = hash( 'sha256', bin2hex( rand( 0, 1234567891234567890 ) . microtime() ) );
        }
        return $hash;
    }

    //action when a post has been created
    public function dt_post_created( $post_type, $post_id, $initial_fields ){

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
