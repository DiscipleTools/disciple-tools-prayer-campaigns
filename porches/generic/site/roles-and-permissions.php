<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.


/**
 * P4_Ramadan_Porch_Landing_Roles Class
 *
 * @package  Disciple_Tools
 * @since    0.1.0
 */
class P4_Ramadan_Porch_Landing_Roles
{
    // needs to match post-type.php
    public $post_type = PORCH_LANDING_POST_TYPE;

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_filter( 'dt_set_roles_and_permissions', [ $this, 'dt_set_roles_and_permissions' ], 50, 1 );
        add_filter( 'dt_allow_rest_access', [ $this, 'dt_allow_rest_access' ] ); // allows access
        add_filter( 'allowed_wp_v2_paths', [ $this, 'dt_porch_template_allowed_wp_v2_paths' ], 10, 1 );
    }

    public function dt_set_roles_and_permissions( $expected_roles ){
        $landing_page_permissions = [
            // landing page access
            'create_'.$this->post_type => true,
            'edit_'.$this->post_type => true,
            'read_'.$this->post_type => true,
            'delete_'.$this->post_type => true,
            'delete_others_'.$this->post_type.'s',
            'delete_'.$this->post_type.'s' => true,
            'edit'.$this->post_type.'s' => true,
            'edit_others_'.$this->post_type.'s' => true,
            'publish_'.$this->post_type.'s' => true,
            'read_private_'.$this->post_type.'s' => true,

            // rest access for blocks editor
            'wp_api_allowed_user' => true,
            'edit_files' => true,
            'upload_files' => true,
            // wp-admin dashboard access
            'read' => true,
        ];
        if ( !isset( $expected_roles["porch_admin"] ) ){
            $expected_roles["porch_admin"] = [
                "label" => 'Porch Admin',
                "description" => "Administrates porch public pages",
                "permissions" => $landing_page_permissions
            ];
        }
        $expected_roles["administrator"]["permissions"] = wp_parse_args( $expected_roles["administrator"]["permissions"], $landing_page_permissions );
        return $expected_roles;
    }

    public function dt_allow_rest_access( $authorized ) {
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'wp/v2' ) !== false && user_can( get_current_user_id(), 'wp_api_allowed_user' ) ) {
            $authorized = true;
        }
        return $authorized;
    }

    public function dt_porch_template_allowed_wp_v2_paths( $allowed_wp_v2_paths ) {
        if ( user_can( get_current_user_id(), 'wp_api_allowed_user' ) ) {

            $allowed_wp_v2_paths[] = '/wp/v2/'.PORCH_LANDING_POST_TYPE;
            $allowed_wp_v2_paths[] = '/wp/v2/'.PORCH_LANDING_POST_TYPE.'/(?P<id>[\d]+)';
            $allowed_wp_v2_paths[] = '/wp/v2/'.PORCH_LANDING_POST_TYPE.'/(?P<parent>[\d]+)/revisions';
            $allowed_wp_v2_paths[] = '/wp/v2/'.PORCH_LANDING_POST_TYPE.'/(?P<parent>[\d]+)/revisions/(?P<id>[\d]+)';
            $allowed_wp_v2_paths[] = '/wp/v2/'.PORCH_LANDING_POST_TYPE.'/(?P<id>[\d]+)/autosaves';
            $allowed_wp_v2_paths[] = '/wp/v2/'.PORCH_LANDING_POST_TYPE.'/(?P<parent>[\d]+)/autosaves/(?P<id>[\d]+)';

            $allowed_wp_v2_paths[] = '/wp/v2/types';
            $allowed_wp_v2_paths[] = '/wp/v2/types/(?P<type>[\w-]+)';

            $allowed_wp_v2_paths[] = '/wp/v2/blocks';
            $allowed_wp_v2_paths[] = '/wp/v2/blocks/(?P<id>[\d]+)';
            $allowed_wp_v2_paths[] = '/wp/v2/blocks/(?P<parent>[\d]+)/revisions';
            $allowed_wp_v2_paths[] = '/wp/v2/blocks/(?P<parent>[\d]+)/revisions/(?P<id>[\d]+)';
            $allowed_wp_v2_paths[] = '/wp/v2/blocks/(?P<id>[\d]+)/autosaves';
            $allowed_wp_v2_paths[] = '/wp/v2/blocks/(?P<parent>[\d]+)/autosaves/(?P<id>[\d]+)';
            $allowed_wp_v2_paths[] = '/wp/v2/block-directory/search';

            $allowed_wp_v2_paths[] = '/wp/v2/media/(?P<id>[\d]+)';
            $allowed_wp_v2_paths[] = '/wp/v2/media/(?P<id>[\d]+)/post-process';
            $allowed_wp_v2_paths[] = '/wp/v2/media/(?P<id>[\d]+)/edit';

            $allowed_wp_v2_paths[] = '/wp/v2/taxonomies';
            $allowed_wp_v2_paths[] = '/wp/v2/taxonomies/(?P<taxonomy>[\w-]+)';

            $allowed_wp_v2_paths[] = '/wp/v2/themes';
            $allowed_wp_v2_paths[] = '/wp/v2/themes/(?P<stylesheet>[\w-]+)';

            $allowed_wp_v2_paths[] = '/wp/v2/templates';
            $allowed_wp_v2_paths[] = '/wp/v2/templates/(?P<id>[\/\w-]+)';
            $allowed_wp_v2_paths[] = '/wp/v2/templates/(?P<parent>[\d]+)/revisions';
            $allowed_wp_v2_paths[] = '/wp/v2/templates/(?P<parent>[\d]+)/revisions/(?P<id>[\d]+)';
            $allowed_wp_v2_paths[] = '/wp/v2/templates/(?P<id>[\d]+)/autosaves';
            $allowed_wp_v2_paths[] = '/wp/v2/templates/(?P<parent>[\d]+)/autosaves/(?P<id>[\d]+)';

            $allowed_wp_v2_paths[] = '/wp/v2/users/me';
            $allowed_wp_v2_paths[] = '/wp/v2/users/me?_locale=user';
            $allowed_wp_v2_paths[] = '/wp/v2/users';

        }
        return $allowed_wp_v2_paths;
    }

} // End Class
P4_Ramadan_Porch_Landing_Roles::instance();
