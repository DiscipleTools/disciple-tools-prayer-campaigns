<?php
/**
 * Rest API example class
 */
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

class DT_Prayer_Endpoints
{
    public $permissions = [ 'view_any_contacts', 'view_project_metrics' ];

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }

    public function has_permission(){
        $pass = false;
        foreach ( $this->permissions as $permission ){
            if ( current_user_can( $permission ) ){
                $pass = true;
            }
        }
        return $pass;
    }


    //See https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
    public function add_api_routes() {
        $namespace = 'dt_prayer/v1';

        register_rest_route(
            'dt-public/v1', '/prayer/create', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'private_endpoint' ],
                ],
            ]
        );
    }


    public function private_endpoint( WP_REST_Request $request ) {
        $params = $request->get_params();

        $user = wp_get_current_user();
        $user->add_cap('create_contacts');

        $new_id = DT_Posts::create_post( 'contacts', $params );
        if ( isset( $new_id['ID'] ) ){
            try {
                $hash = hash('sha256', bin2hex( random_bytes( 64 ) ) );
            } catch( Exception $exception ) {
                $hash = hash('sha256', bin2hex( rand(0, 1234567891234567890 ) . microtime() ) );
            }
            update_post_meta( $new_id['ID'], 'contact_public_key', $hash );

            return $hash;
        }
        return false;
    }
}
DT_Prayer_Endpoints::instance();
