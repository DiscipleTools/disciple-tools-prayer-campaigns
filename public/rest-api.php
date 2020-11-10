<?php
/**
 * Rest API example class
 */
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

class DT_Prayer_Endpoints
{
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        add_filter( 'dt_allow_rest_access',[$this, 'authorize_url'], 10, 1  );
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }

    public function authorize_url( $authorized ){
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'dt-prayer/v1/create' ) !== false ) {
            $authorized = true;
        }
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'dt-prayer/v1/update' ) !== false ) {
            $authorized = true;
        }
        return $authorized;
    }

    //See https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
    public function add_api_routes() {
        $namespace = 'dt-prayer/v1';

        register_rest_route(
            $namespace, '/create', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'create' ],
                ],
            ]
        );
        register_rest_route(
            $namespace, '/update', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'update' ],
                ],
            ]
        );


    }

    public function create( WP_REST_Request $request ) {
        $params = $request->get_params();

        // @todo honey pot test


        $user = wp_get_current_user();
        $user->add_cap('create_contacts');

        $new_id = DT_Posts::create_post( 'contacts', $params, true );
        if ( isset( $new_id['ID'] ) ){
            try {
                $hash = hash('sha256', bin2hex( random_bytes( 64 ) ) );
            } catch( Exception $exception ) {
                $hash = hash('sha256', bin2hex( rand( 0, 1234567891234567890 ) . microtime() ) );
            }
            update_post_meta( $new_id['ID'], 'contact_public_key', $hash );

            return $hash;
        }
        return false;
    }

    // @link /dt-posts/dt-posts-endpoints.php:405
    public function update( WP_REST_Request $request ){
        $params = $request->get_params();
        return $params; // @todo


        $fields = $request->get_json_params() ?? $request->get_body_params();
        $url_params = $request->get_url_params();
        $get_params = $request->get_query_params();
        $silent = isset( $get_params["silent"] ) && $get_params["silent"] === "true";
        return DT_Posts::update_post( $url_params["post_type"], $url_params["id"], $fields, $silent );
    }
}
DT_Prayer_Endpoints::instance();
