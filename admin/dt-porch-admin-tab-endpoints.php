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
                        return dt_has_permissions( [ 'administrator', 'view_any_subscriptions' ] );
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
            $campaign_field_settings = apply_filters( 'dt_custom_fields_settings', Disciple_Tools_Post_Type_Template::get_base_post_type_fields(), 'campaigns' );

            // Specify general fields to be ignored.
            $ignored_fields = [
                'ID',
                'name',
                'type',
                'title',
                'permalink',
                'post_type',
                'post_author',
                'post_author_display_name',
                'last_modified',
                'post_date',
                'campaign_url'
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
                    }
                }
            }

            // Final sanity check to ensure no magic keys have fallen through the cracks.
            $fields_to_unset = [];
            foreach ( $fields as $key => $value ) {
                if ( strpos( $key, '_magic_key' ) !== false ) {
                    $fields_to_unset[] = $key;
                }
            }

            foreach ( $fields_to_unset as $field_key ) {
                unset( $fields[ $field_key ] );
            }

            // Create cloned campaign post.
            $new_campaign = DT_Posts::create_post( 'campaigns', $fields, true, false );
            if ( !is_wp_error( $new_campaign ) ) {

                // If required, set as selected campaign.
                $default_campaign = get_option( 'dt_campaign_selected_campaign', false );
                if ( empty( $default_campaign ) ){
                    update_option( 'dt_campaign_selected_campaign', $new_campaign['ID'] );
                }

                /**
                 * Clone Prayer Fuel Metadata
                 */

                // Identify campaign length and subsequent prayer fuel days count.
                $campaign_length = DT_Campaign_Fuel::total_days_in_campaign( $campaign['ID'] );
                $prayer_fuel_days_count = ( $campaign_length > 0 ) ? $campaign_length : 1000;

                $prayer_fuel_days = [];
                for ( $i = 0; $i < $prayer_fuel_days_count; $i++ ) {
                    $prayer_fuel_days[] = $i + 1;
                }
                $prayer_fuel_days_string = implode( ', ', $prayer_fuel_days );

                // List prayer fuel posts currently associated with primary campaign.
                global $wpdb;
                $prayer_fuel_posts = $wpdb->get_results( $wpdb->prepare( "
                    SELECT DISTINCT ID, post_title, CAST( pm.meta_value as unsigned ) as day FROM $wpdb->posts p
                    JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_id AND pm.meta_key = 'day' )
                    JOIN $wpdb->postmeta pm2 ON ( p.ID = pm2.post_id AND pm2.meta_key = 'linked_campaign' AND pm2.meta_value = %d)
                    WHERE p.post_type = %s
                    AND p.post_status IN ( 'draft', 'publish', 'future' )
                    AND pm.meta_value IN ( %1s )
                ", [ $campaign['ID'], CAMPAIGN_LANDING_POST_TYPE, $prayer_fuel_days_string ] ), ARRAY_A );

                // Associated prayer fuel post detected? Build prayer fuel insert sql.
                if ( !empty( $prayer_fuel_posts ) ) {
                    $prayer_fuel_sql = [];
                    foreach ( $prayer_fuel_posts as $prayer_fuel_post ) {
                        $prayer_fuel_sql[] = '(' . $prayer_fuel_post['ID'] . ", 'linked_campaign', ". $new_campaign['ID'] . ')';
                    }

                    // phpcs:disable
                    $wpdb->get_results( $wpdb->prepare( "
                        INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
                        VALUES ". implode( ', ', $prayer_fuel_sql ) ." ON DUPLICATE KEY UPDATE post_id = VALUES(post_id), meta_key = VALUES(meta_key), meta_value = VALUES(meta_value)",
                        [] ), ARRAY_A );
                    // phpcs:enable
                }

                $response['success'] = true;
                $response['campaign_id'] = $new_campaign['ID'];
            } else {
                $response['success'] = false;
                $response['msg'] = $new_campaign->get_error_message();
            }
        } else {
            $response['success'] = false;
            $response['msg'] = 'Invalid parameters';
        }

        return $response;
    }
}
new Porch_Admin_Endpoints();
