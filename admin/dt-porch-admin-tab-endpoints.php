<?php

class Porch_Admin_Endpoints {
    public function __construct(){
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }

    public function add_api_routes() {
        $namespace = 'admin/v1';
        register_rest_route(
            $namespace, '/clone_campaign', [
                [
                    'methods'  => [ 'POST' ],
                    'callback' => [ $this, 'dt_campaign_clone_endpoint' ],
                    'permission_callback' => function( WP_REST_Request $request ) {

                        // TODO: Investigate why defaults to zero for current user!?
                        // return dt_has_permissions( [ 'administrator', 'view_any_subscriptions' ] );

                        return true;
                    }
                ],
            ]
        );
    }

    public function dt_campaign_clone_endpoint( WP_REST_Request $request ){
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );

        $response = [
            'success' => false
        ];

        if ( isset( $params['new_name'], $params['campaign_id'] ) ) {
            $new_name = $params['new_name'];
            $campaign_id = $params['campaign_id'];
            $campaign = DT_Campaign_Landing_Settings::get_campaign( $campaign_id );
            $campaign_field_settings = DT_Posts::get_post_field_settings( 'campaigns' );

            // Specify general fields to be ignored.
            $ignored_fields = [
                'ID',
                'name',
                'title',
                'permalink',
                'post_type',
                'post_author',
                'post_author_display_name',
                'last_modified',
                'post_date'
            ];

            // Capture magic link meta keys to also be ignored.
            $magic_link_keys = apply_filters( 'dt_magic_url_register_types', [] );
            foreach ( $magic_link_keys as $ml_type_key => $ml_type ) {
                foreach ( $ml_type as $key => $type ) {
                    if ( !empty( $type['meta_key'] ) && !in_array( $type['meta_key'], $ignored_fields ) ) {
                        $ignored_fields[] = $type['meta_key'];
                    }
                }
            }

            // Create cloned fields base array.
            $fields = [
                'name' => $new_name
            ];

            // Iterate over campaign fields, extracting available values accordingly.
            foreach ( $campaign as $key => $value ) {

                // Ignore specified fields.
                if ( !in_array( $key, $ignored_fields ) ) {

                    // Process accordingly, based on field settings detection and type.
                    if ( isset( $campaign_field_settings[ $key ] ) ) {
                        $field_setting = $campaign_field_settings[ $key ];
                        switch ( $field_setting['type'] ) {
                            case 'icon':
                            case 'text':
                            case 'textarea':
                                $fields[ $key ] = $value;
                                break;
                            case 'date':
                                $timestamp = $value['timestamp'];
                                if ( !empty( $timestamp ) ) {
                                    $fields[ $key ] = $timestamp;
                                }
                                break;
                            case 'key_select':
                                $key_select_value = $value['key'];
                                if ( !empty( $key_select_value ) ) {
                                    $fields[ $key ] = $key_select_value;
                                }
                                break;
                            case 'tags':
                            case 'multi_select':
                                $multi_select_values = $value;
                                if ( !empty( $multi_select_values ) ) {
                                    $values = [];
                                    foreach ( $multi_select_values as $multi_value ) {
                                        $values[] = [
                                            'value' => $multi_value
                                        ];
                                    }
                                    $fields[ $key ] = [
                                        'values' => $values
                                    ];
                                }
                                break;
                        }
                    } elseif ( !is_array( $value ) ) { // Otherwise capture, only if it's a text based value.
                        $fields[ $key ] = $value;
                    }
                }
            }

            // Create cloned campaign post.
            $new_campaign = DT_Posts::create_post( 'campaigns', $fields, true, false );
            if ( !is_wp_error( $new_campaign ) ) {

                // If required, set as selected campaign.
                $default_campaign = get_option( 'dt_campaign_selected_campaign', false );
                if ( empty( $default_campaign ) ){
                    update_option( 'dt_campaign_selected_campaign', $new_campaign['ID'] );
                }

                $response['success'] = true;
                $response['campaign_id'] = $new_campaign['ID'];
            }
        }

        return $response;
    }
}
new Porch_Admin_Endpoints();
