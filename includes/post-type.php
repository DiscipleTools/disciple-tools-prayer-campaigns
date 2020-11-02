<?php

class DT_Starter_Post_Type {

    // Setup post type naming
    public $post_type = 'starter_post_type';
    public $single = 'Starter';
    public $plural = 'Starters';

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {

        add_action( 'after_setup_theme', [ $this, 'after_setup_theme' ], 100 );
        add_action( 'p2p_init', [ $this, 'p2p_init' ] );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 10, 2 );
        add_action( 'dt_modal_help_text', [ $this, 'modal_help_text' ], 10 );

        add_filter( 'dt_registered_post_types', [ $this, 'register_post_type' ], 10, 2 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
        add_filter( 'dt_details_additional_section_ids', [ $this, 'dt_details_additional_section_ids' ], 10, 2 );
        add_action( 'post_connection_removed', [ $this, "post_connection_removed" ], 10, 4 );
        add_action( 'post_connection_added', [ $this, "post_connection_added" ], 10, 4 );
        add_filter( 'dt_user_list_filters', [ $this, "dt_user_list_filters" ], 10, 2 );
        add_filter( 'dt_get_post_fields_filter', [ $this, "dt_get_post_fields_filter" ], 10, 2 );

    }

    public function after_setup_theme(){
        if ( class_exists( 'Disciple_Tools_Post_Type_Template' )) {
            new Disciple_Tools_Post_Type_Template( $this->post_type, ucwords( $this->single ), ucwords( $this->plural ) );
        }
    }

    public function register_post_type( $post_types ) {
        $post_types[] = $this->post_type;
        return $post_types;
    }

    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === $this->post_type ){
            $fields["contact_count"] = [
                'name' => "Contact Count",
                'type' => 'text',
                'default' => 0,
                'show_in_table' => false
            ];
            $fields["group_count"] = [
                'name' => "Group Count",
                'type' => 'text',
                'default' => 0,
                'show_in_table' => false
            ];
            $fields["location_grid"] = [
                'name' => "Locations",
                'type' => 'location',
                'default' => [],
                'show_in_table' => true
            ];
            $fields["location_grid_meta"] = [
                'name' => "Locations",
                'type' => 'location_meta',
                'default' => [],
                'show_in_table' => false,
                'silent' => true,
            ];
            $fields["status"] = [
                'name' => "Status",
                'type' => 'key_select',
                'default' => [
                    'new'   => [
                        "label" => _x( 'New', 'Status label', 'disciple_tools' ),
                        "description" => _x( "This record is new.", "Status field description", 'disciple_tools' ),
                        "color" => "#F43636",
                    ],
                    'active'   => [
                        "label" => _x( 'Active', 'Status label', 'disciple_tools' ),
                        "description" => _x( "This record is active.", "Status field description", 'disciple_tools' ),
                        "color" => "#F43636",
                    ],
                    'inactive' => [
                        "label" => _x( 'Inactive', 'Status label', 'disciple_tools' ),
                        "description" => _x( "This record is inactive.", "Status field description", 'disciple_tools' ),
                        "color" => "#FF9800",
                    ],
                ],
                'show_in_table' => true
            ];
            $fields["start_date"] = [
                'name' => "Start Date",
                'type' => 'date',
                'default' => '',
                'show_in_table' => true
            ];
            $fields['parents'] = [
                'name' => "Parents",
                'type' => 'connection',
                "post_type" => $this->post_type,
                "p2p_direction" => "from",
                "p2p_key" => $this->post_type . '_to_' . $this->post_type,
            ];
            $fields['children'] = [
                'name' => "Children",
                'type' => 'connection',
                "post_type" => $this->post_type,
                "p2p_direction" => "to",
                "p2p_key" => $this->post_type . '_to_' . $this->post_type,
            ];
            $fields['contacts'] = [
                'name' => "Contacts",
                'type' => 'connection',
                "post_type" => 'contacts',
                "p2p_direction" => "from",
                "p2p_key" => $this->post_type . "_to_contacts",
            ];
            $fields['groups'] = [
                'name' => "Groups",
                'type' => 'connection',
                "post_type" => 'groups',
                "p2p_direction" => "from",
                "p2p_key" => $this->post_type . "_to_groups",
            ];
            $fields["people_groups"] = [
                "name" => __( 'People Groups', 'disciple_tools' ),
                'description' => _x( 'The people groups represented by this group.', 'Optional Documentation', 'disciple_tools' ),
                "type" => "connection",
                "post_type" => "peoplegroups",
                "p2p_direction" => "from",
                "p2p_key" => $this->post_type . "_to_peoplegroups"
            ];

        }
        if ( $post_type === 'groups' ){
            $fields[$this->post_type] = [
                'name' => $this->plural,
                'type' => 'connection',
                "post_type" => $this->post_type,
                "p2p_direction" => "to",
                "p2p_key" => $this->post_type . "_to_groups",
            ];
        }
        if ( $post_type === 'contacts' ){
            $fields[ $this->post_type ] = [
                'name' => $this->plural,
                'type' => 'connection',
                "post_type" => $this->post_type,
                "p2p_direction" => "to",
                "p2p_key" => $this->post_type . "_to_contacts",
            ];
        }

        return $fields;
    }

    public function p2p_init(){
        p2p_register_connection_type([
            'name' => $this->post_type . '_to_contacts',
            'from' => $this->post_type,
            'to' => 'contacts'
        ]);
        p2p_register_connection_type([
            'name' => $this->post_type . '_to_groups',
            'from' => $this->post_type,
            'to' => 'groups'
        ]);

        p2p_register_connection_type([
            'name' => $this->post_type . '_to_' . $this->post_type,
            'from' => $this->post_type,
            'to' => $this->post_type
        ]);
        p2p_register_connection_type([
            'name' => $this->post_type . '_to_peoplegroups',
            'from' => $this->post_type,
            'to' => 'peoplegroups'
        ]);
    }

    public function dt_details_additional_section_ids( $sections, $post_type = "" ){
        if ( $post_type === $this->post_type){
            $sections[] = 'connections';
            $sections[] = 'locations';
        }
        if ( $post_type === 'contacts' || $post_type === 'groups' ){
            $sections[] = $this->post_type;
        }
        return $sections;
    }

    public function dt_details_additional_section( $section, $post_type ){

        if ( $section === "details" && $post_type === $this->post_type ){
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>
            <div class="grid-x grid-padding-x">
                <div class="cell medium-6">
                    <?php render_field_for_display( 'status', $post_settings["fields"], $dt_post ); ?>
                </div>
                <div class="cell medium-6">
                    <?php render_field_for_display( 'start_date', $post_settings["fields"], $dt_post ); ?>
                </div>
            </div>
            <?php
        }

        if ($section === "locations" && $post_type === $this->post_type ){
            $post_type = get_post_type();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );

            ?>

            <label class="section-header">
                <?php esc_html_e( 'Locations', 'disciple_tools' )?> <a class="button clear" id="new-mapbox-search"><?php esc_html_e( "add", 'disciple_tools' ) ?></a>
            </label>

            <?php /* If Mapbox Upgrade */ if ( DT_Mapbox_API::get_key() ) : ?>

                <div id="mapbox-wrapper"></div>

                <?php if ( isset( $dt_post['location_grid_meta'] ) ) : ?>

                    <!-- reveal -->
                    <div class="reveal" id="map-reveal" data-reveal>
                        <div id="map-reveal-content"><!-- load content here --><div class="loader">Loading...</div></div>
                        <button class="close-button" data-close aria-label="Close modal" type="button">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

            <?php /* No Mapbox Upgrade */ else : ?>

                <?php render_field_for_display( 'location_grid', $post_settings["fields"], $dt_post ); ?>

            <?php endif; ?>


        <?php }

        // Connections tile on Streams details page
        if ($section === "connections" && $post_type === $this->post_type){
            $post_type = get_post_type();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <h3 class="section-header">
                <?php esc_html_e( 'Connections', 'disciple_tools' )?>
                <button class="help-button float-right" data-section="connections-help-text">
                    <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                </button>
                <button class="section-chevron chevron_down">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>"/>
                </button>
                <button class="section-chevron chevron_up">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>"/>
                </button>
            </h3>
            <div class="section-body">

                <?php render_field_for_display( 'contacts', $post_settings["fields"], $dt_post ) ?>

                <?php render_field_for_display( 'groups', $post_settings["fields"], $dt_post ) ?>

                <?php render_field_for_display( 'people_groups', $post_settings["fields"], $dt_post ) ?>

                <?php render_field_for_display( 'parents', $post_settings["fields"], $dt_post ) ?>

                <?php render_field_for_display( 'children', $post_settings["fields"], $dt_post ) ?>
            </div>
        <?php }



        // Streams tile on contacts details page
        if ($section == $this->post_type && $post_type === "contacts"){
            $post_type = get_post_type();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <label class="section-header">
                <?php echo esc_html( $this->plural )?>
                <button class="help-button float-right" data-section="<?php echo esc_attr( $this->post_type ) ?>-help-text">
                    <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                </button>
                <button class="section-chevron chevron_down">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>"/>
                </button>
                <button class="section-chevron chevron_up">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>"/>
                </button>
            </label>
            <div class="section-body">

                <?php render_field_for_display( $this->post_type, $post_settings["fields"], $dt_post ) ?>
            </div>


        <?php }

        // Streams tile on groups details page
        if ($section == $this->post_type && $post_type === "groups"){
            $post_type = get_post_type();
            $post_settings = apply_filters( "dt_get_post_type_settings", [], $post_type );
            $dt_post = DT_Posts::get_post( $post_type, get_the_ID() );
            ?>

            <label class="section-header">
                <?php echo esc_html( $this->plural )?>
                <button class="help-button float-right" data-section="<?php echo esc_attr( $this->post_type ) ?>-help-text">
                    <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                </button>
                <button class="section-chevron chevron_down">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>"/>
                </button>
                <button class="section-chevron chevron_up">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>"/>
                </button>
            </label>
            <div class="section-body">

                <?php render_field_for_display( $this->post_type, $post_settings["fields"], $dt_post ) ?>

            </div>

        <?php }

    }

    public function modal_help_text() {
        if ( is_singular( $this->post_type ) ) {
            ?>
            <div class="help-section" id="connections-help-text" style="display: none">
                <h3><?php echo esc_html_x( "Connections", 'Optional Documentation', 'disciple_tools' ) ?></h3>
                <p><?php echo esc_html_x( "These are connections to this record.", 'Optional Documentation', 'disciple_tools' ) ?></p>
            </div>
            <?php
        }
        if ( is_singular( "contacts" ) ) {
            ?>
            <div class="help-section" id="<?php echo esc_attr( $this->post_type ) ?>-help-text" style="display: none">
                <h3><?php echo esc_html( $this->plural ) ?></h3>
                <p><?php echo esc_html_x( "Connect this contact.", 'Optional Documentation', 'disciple_tools' ) ?></p>
            </div>
            <?php
        }
        if ( is_singular( "groups" ) ) {
            ?>
            <div class="help-section" id="<?php echo esc_attr( $this->post_type ) ?>-help-text" style="display: none">
                <h3><?php echo esc_html( $this->plural ) ?></h3>
                <p><?php echo esc_html_x( "Connect this group.", 'Optional Documentation', 'disciple_tools' ) ?></p>
            </div>
            <?php
        }
    }

    private function update_event_counts( $id, $action = "added", $type = 'contacts' ){
        $current_post = get_post( $id );
        if ( $type === 'contacts' ){
            $args = [
                'connected_type'   => $this->post_type  . "_to_contacts",
                'connected_direction' => 'from',
                'connected_items'  => $current_post,
                'nopaging'         => true,
                'suppress_filters' => false,
            ];
            $contacts = get_posts( $args );
            $contact_count = get_post_meta( $id, 'contact_count', true );
            if ( sizeof( $contacts ) > intval( $contact_count ) ){
                update_post_meta( $id, 'contact_count', sizeof( $contacts ) );
            } elseif ( $action === "removed" ){
                update_post_meta( $id, 'contact_count', intval( $contact_count ) - 1 );
            }
        }
        if ( $type === 'groups' ){
            $args = [
                'connected_type'   => $this->post_type  . "_to_groups",
                'connected_direction' => 'from',
                'connected_items'  => $current_post,
                'nopaging'         => true,
                'suppress_filters' => false,
            ];
            $groups = get_posts( $args );
            $group_count = get_post_meta( $id, 'group_count', true );
            if ( sizeof( $groups ) > intval( $group_count ) ){
                update_post_meta( $id, 'group_count', sizeof( $groups ) );
            } elseif ( $action === "removed" ){
                update_post_meta( $id, 'group_count', intval( $group_count ) - 1 );
            }
        }

    }
    public function post_connection_added( $post_type, $post_id, $post_key, $value ){
        if ( $post_type === $this->post_type && ( $post_key === "contacts" || $post_key === "groups" ) ){
            $this->update_event_counts( $post_id, 'added', $post_key );
        } elseif ( ( $post_type === "contacts" || $post_type === "groups" ) && $post_key === $this->post_type ) {
            $this->update_event_counts( $value, 'added', $post_type );
        }
    }
    public function post_connection_removed( $post_type, $post_id, $post_key, $value ){
        if ( $post_type === $this->post_type && ( $post_key === "contacts" || $post_key === "groups" ) ){
            $this->update_event_counts( $post_id, 'removed', $post_key );
        } elseif ( ( $post_type === "contacts" || $post_type === "groups" ) && $post_key === $this->post_type ) {
            $this->update_event_counts( $value, 'removed', $post_type );
        }
    }

    public static function dt_user_list_filters( $filters, $post_type ) {
        if ( $post_type === self::instance()->post_type ) {
            $filters["tabs"][] = [
                "key" => "all_" . self::instance()->post_type,
                "label" => _x( "All", 'List Filters', 'disciple_tools' ),
                "order" => 10
            ];
            // add assigned to me filters
            $filters["filters"][] = [
                'ID' => 'all_' . self::instance()->post_type,
                'tab' => 'all_' . self::instance()->post_type,
                'name' => _x( "All", 'List Filters', 'disciple_tools' ),
                'query' => [],
            ];
            $filters["filters"][] = [
                'ID' => 'all_active',
                'tab' => 'all_' . self::instance()->post_type,
                'name' => _x( "Active", 'List Filters', 'disciple_tools' ),
                'query' => [ "status" => [ "active" ] ],
            ];
            $filters["filters"][] = [
                'ID' => 'all_inactive',
                'tab' => 'all_' . self::instance()->post_type,
                'name' => _x( "Inactive", 'List Filters', 'disciple_tools' ),
                'query' => [ "status" => [ "inactive" ] ],
            ];

        }
        return $filters;
    }

    public function dt_get_post_fields_filter( $fields, $post_type ) {
        if ( $post_type === $this->post_type ){
            $fields = apply_filters( $this->post_type . '_fields_post_filter', $fields );
        }
        return $fields;
    }
}
DT_Starter_Post_Type::instance();
