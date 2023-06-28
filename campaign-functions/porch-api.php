<?php

class Porch_Campaigns_API {
    public function __construct(){
        add_action( 'rest_api_init', [ $this, 'rest_api_init' ] );
    }

    public function rest_api_init(){
        $namespace = 'campaign_app/v1';
        register_rest_route(
            $namespace, '/24hour-router', [
                [
                    'methods'  => [ 'POST', 'GET' ],
                    'callback' => [ $this, 'dt_campaign_router_endpoint' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
    }

    public function dt_campaign_router_endpoint( WP_REST_Request $request ){
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );
        if ( isset( $params['parts']['post_id'] ) ){
            $remote = get_transient( 'dt_magic_link_remote_' . $params['parts']['post_id'] );
            $url = $remote . $params['parts']['root'] . '/v1/' .  $params['parts']['type'] . '/' . $params['url'];
            if ( !empty( $remote ) ){
                if ( $request->get_method() === 'GET' ){
                    $fetch = wp_remote_get( $url, [ 'body' => $params ] );
                    if ( !is_wp_error( $fetch ) ){
                        return json_decode( wp_remote_retrieve_body( $fetch ) );
                    }
                }
                if ( $request->get_method() === 'POST' ){
                    $fetch = wp_remote_post( $url, [ 'body' => $params ] );
                    if ( !is_wp_error( $fetch ) ){
                        return json_decode( wp_remote_retrieve_body( $fetch ) );
                    }
                }
            }
        }
        return false;
    }
}
new Porch_Campaigns_API();