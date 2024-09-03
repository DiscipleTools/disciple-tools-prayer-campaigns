<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.


/**
 * Class Disciple_Tools_Resend_Email_Magic_Link
 */
class Disciple_Tools_Resend_Email_Magic_Link extends DT_Magic_Url_Base {

    public $magic = false;
    public $parts = false;
    public $page_title = 'Resend Subscription Link';
    public $page_description = 'Post Type - Magic Links.';
    public $root = 'campaign_app';
    public $type = 'resend';
    public $post_type = 'campaigns';
    private $meta_key = '';
    public $show_bulk_send = false;
    public $show_app_tile = true;

    private static $_instance = null;
    public $meta = []; // Allows for instance specific data.

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {

        /**
         * Specify metadata structure, specific to the processing of current
         * magic link type.
         *
         * - meta:              Magic link plugin related data.
         *      - app_type:     Flag indicating type to be processed by magic link plugin.
         *      - post_type     Magic link type post type.
         *      - contacts_only:    Boolean flag indicating how magic link type user assignments are to be handled within magic link plugin.
         *                          If True, lookup field to be provided within plugin for contacts only searching.
         *                          If false, Dropdown option to be provided for user, team or group selection.
         *      - fields:       List of fields to be displayed within magic link frontend form.
         */
        $this->meta = [
            'app_type'      => 'magic_link',
            'post_type'     => $this->post_type,
            'contacts_only' => true,
            'fields'        => [
                [
                    'id'    => 'name',
                    'label' => 'Name'
                ]
            ]
        ];

        $this->meta_key = $this->root . '_' . $this->type . '_magic_key';
        parent::__construct();

        /**
         * post type and module section
         */
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 30, 2 );
        add_filter( 'dt_settings_apps_list', [ $this, 'dt_settings_apps_list' ], 10, 1 );
        add_action( 'rest_api_init', [ $this, 'add_endpoints' ] );


        /**
         * tests if other URL
         */
        $url = dt_get_url_path();
        if ( strpos( $url, $this->root . '/' . $this->type ) === false ) {
            return;
        }
        /**
         * tests magic link parts are registered and have valid elements
         */
        if ( !$this->check_parts_match() ){
            return;
        }

        // load if valid url
        add_action( 'dt_blank_body', [ $this, 'body' ] ); // body for no post key
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );
    }

    public function wp_enqueue_scripts(){
        wp_enqueue_script( 'magic_link_scripts', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'magic-link.js', [
            'jquery',
            'lodash',
        ], filemtime( plugin_dir_path( __FILE__ ) . 'magic-link.js' ), true );
        wp_localize_script(
            'magic_link_scripts', 'jsObject', [
                'map_key' => DT_Mapbox_API::get_key(),
                'rest_base' => esc_url( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'translations' => [],
                'rest_namespace' => $this->root . '/v1/' . $this->type,
            ]
        );
        wp_enqueue_style( 'magic_link_css', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'magic-link.css', [], filemtime( plugin_dir_path( __FILE__ ) . 'magic-link.css' ) );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js[] = 'magic_link_scripts';
        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        $allowed_css[] = 'magic_link_css';
        return $allowed_css;
    }

    public function dt_details_additional_section( $section, $post_type ) {
        // test if campaigns post type and campaigns_app_module enabled
        if ( $post_type === $this->post_type ) {
            if ( 'apps' === $section ) {
                $link = DT_Magic_URL::get_link_url_for_post( $post_type, get_the_ID(), $this->root, $this->type )
                ?>
                <a class="button hollow" style="display: block" href="<?php echo esc_html( $link ); ?>" target="_blank">Retrieve Prayer Time Management Link</a>
                <?php
            }
        }
    }

    /**
     * Builds magic link type settings payload:
     * - key:               Unique magic link type key; which is usually composed of root, type and _magic_key suffix.
     * - url_base:          URL path information to map with parent magic link type.
     * - label:             Magic link type name.
     * - description:       Magic link type description.
     * - settings_display:  Boolean flag which determines if magic link type is to be listed within frontend user profile settings.
     *
     * @param array $apps_list
     *
     * @return mixed
     */
    public function dt_settings_apps_list( $apps_list ) {
        $apps_list[ $this->meta_key ] = [
            'key'              => $this->meta_key,
            'url_base'         => $this->root . '/' . $this->type,
            'label'            => $this->page_title,
            'description'      => $this->page_description,
            'settings_display' => false
        ];

        return $apps_list;
    }

    public function body(){
        ?>
        <div id="magic-link-wrapper">
            <div class="grid-x">
                <div class="cell center">
                    <h2 id="title"><?php esc_html_e( 'Retrieve Prayer Time Management Link', 'disciple-tools-prayer-campaigns' ); ?></h2>
                </div>
            </div>
            <hr>
            <div id="content">

<!--                <h3>Form</h3>-->
                <form id="form-content" onSubmit="submit_form();return false;">

                    <p><?php esc_html_e( 'Enter you email address and receive a link to your prayer times.', 'disciple-tools-prayer-campaigns' ); ?></p>
                    <label>
                        <strong><?php esc_html_e( 'Email', 'disciple-tools-prayer-campaigns' ); ?></strong>
                        <input type="email" id="email" style="display: none">
                        <input type="email" id="email-2" required>
                    </label>

                    <button type="submit" class="button loader" id="submit-form"><?php esc_html_e( 'Email My Access Link', 'disciple-tools-prayer-campaigns' ); ?></button>
                </form>
                <div id="form-confirm" style="display: none">
                    <?php esc_html_e( 'Please check your email address now for your access link.', 'disciple-tools-prayer-campaigns' ); ?>
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * Register REST Endpoints
     * @link https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
     */
    public function add_endpoints() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/' . $this->type, [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'access_account' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
    }

    public function access_account( WP_REST_Request $request ) {
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );

        $post_id = $params['parts']['post_id']; //has been verified in verify_rest_endpoint_permissions_on_post()

        if ( empty( $params['email'] ) ){
            return new WP_Error( __METHOD__, 'Missing required parameter.', [ 'status' => 400 ] );
        }

        DT_Prayer_Campaigns_Send_Email::send_account_access( $post_id, $params['email'] );

        return $params;
    }
}
Disciple_Tools_Resend_Email_Magic_Link::instance();
