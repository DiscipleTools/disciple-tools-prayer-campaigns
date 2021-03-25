<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Campaign_Progress extends DT_Module_Base
{
    public $module = "campaigns_24hour_prayer";
    public $post_type = 'campaigns';

    public $magic = false;
    public $parts = false;
    public $root = "campaign_app"; // define the root of the url {yoursite}/root/type/key/action
    public $type = 'progress'; // define the type


    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        parent::__construct();
        if ( !self::check_enabled_and_prerequisites() ){
            return;
        }
        // register tiles if on details page
        add_filter( 'dt_campaign_types', [ $this, 'dt_campaign_types' ], 20, 1 );
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 30, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 30, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'tile_scripts' ], 100 );

        // register type
        $this->magic = new DT_Magic_URL( $this->root );
        add_filter( 'dt_magic_url_register_types', [ $this, 'register_type' ], 10, 1 );

        // register REST and REST access
        add_filter( 'dt_allow_rest_access', [ $this, 'authorize_url' ], 10, 1 );
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );

        // stop processing for non magic urls
        $url = dt_get_url_path();
        if ( strpos( $url, $this->root . '/' . $this->type ) === false ) {
            return;
        }

        // fail to blank if not valid url
        $this->parts = $this->magic->parse_url_parts();
        if ( ! $this->parts ){
            // @note this returns a blank page for bad url, instead of redirecting to login
            add_filter( 'dt_templates_for_urls', function ( $template_for_url ) {
                $url = dt_get_url_path();
                $template_for_url[ $url ] = 'template-blank.php';
                return $template_for_url;
            }, 199, 1 );
            add_filter( 'dt_blank_access', function(){ return true;
            } );
            add_filter( 'dt_allow_non_login_access', function(){ return true;
            }, 100, 1 );
            return;
        }

        // fail if does not match type
        if ( $this->type !== $this->parts['type'] ){
            return;
        }

        // load if valid url
        add_action( 'dt_blank_head', [ $this, 'form_head' ] );
        add_action( 'dt_blank_footer', [ $this, 'form_footer' ] );
        if ( $this->magic->is_valid_key_url( $this->type ) && '' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'form_body' ] );
        }
        else if ( 'access_account' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'access_account_body' ] );
        }
        else {
            // fail if no valid action url found
            return;
        }

        // load page elements
        add_action( 'wp_print_scripts', [ $this, 'print_scripts' ], 1500 );
        add_action( 'wp_print_styles', [ $this, 'print_styles' ], 1500 );

        // register url and access
        add_filter( 'dt_templates_for_urls', [ $this, 'register_url' ], 199, 1 );
        add_filter( 'dt_blank_access', [ $this, '_has_access' ] );
        add_filter( 'dt_allow_non_login_access', function(){ return true;
        }, 100, 1 );
    }

    public function fail_register( $template_for_url ) {
        $url = dt_get_url_path();
        $template_for_url[ $url ] = 'template-blank.php';
        return $template_for_url;
    }

    public function dt_details_additional_tiles( $tiles, $post_type = "" ){
        if ( $post_type === 'campaigns' && ! isset( $tiles["progress"] ) ){
            $tiles["progress"] = [ "label" => __( "Progress", 'disciple-tools-campaigns' ) ];
        }
        return $tiles;
    }

    public function dt_details_additional_section( $section, $post_type ) {
        // test if campaigns post type and campaigns_app_module enabled
        if ( $post_type === $this->post_type ) {

            if ( 'progress' === $section ) {
                $post_id = get_the_ID();
                $record = DT_Posts::get_post( $post_type, $post_id );
                $coverage_percentage = DT_Campaigns_Base::query_coverage_percentage( $post_id );
                $number_of_time_slots = DT_Campaigns_Base::query_coverage_total_time_slots( $post_id );

                $description = "Campaign Description";
                if ( isset( $record["description"] ) && !empty( $record["description"] ) ){
                    $description = $record["description"];
                }

                if ( isset( $record['type']['key'] ) && '24hour' === $record['type']['key'] ) {
                    if ( isset( $record['public_key'] )) {
                        $key = $record['public_key'];
                    } else {
                        $key = DT_Subscriptions_Base::instance()->create_unique_key();
                        update_post_meta( get_the_ID(), 'public_key', $key );
                    }
                    $link = trailingslashit( site_url() ) . $this->root . '/' . $this->type . '/' . $key;
                    ?>
                    <p style="overflow-wrap: anywhere">Embed link: <?php echo esc_url( $link ) ?></p>
                    <hr>
                    <br>
                    <h3><?php echo esc_html( $description ); ?></h3>
                    <div class="progress-bar-container" style="border: 1px solid black" >
                        <div class="progress-bar" data-percent="<?php echo esc_html( $coverage_percentage ); ?>" style="background: dodgerblue; width:0; height:20px"></div>
                    </div>
                    We have covered <strong><?php echo esc_html( $coverage_percentage ); ?>%</strong> of the <?php echo esc_html( $number_of_time_slots ); ?> time slots needed to cover the region in 24 hour prayer.

                    <script>
                        jQuery.each( jQuery('.progress-bar'), function(){
                            jQuery(this).animate({
                                width: ( jQuery(this).data('percent') || 0 ) + '%'
                            })
                        })
                    </script>

                    <?php
                } // end if 24hour prayer
            } // end if apps section
        } // end if campaigns and enabled
    }

    public function dt_campaign_types( $types ) {
        $types['progress'] = [
            'label' => __( 'Progress', 'disciple_tools' ),
            'description' => __( 'Cover a region with 24h prayer.', 'disciple_tools' ),
            "visibility" => __( "Collaborators", 'disciple_tools' ),
            'color' => "#4CAF50",
            'hidden' => true
        ];
        return $types;
    }

    public function tile_scripts(){
        if ( is_singular( "campaigns" ) ){
            $magic = new DT_Magic_URL( 'campaigns_app' );
            $types = $magic->list_types();
            $campaigns = $types['campaigns'] ?? [];
            $campaigns['new_key'] = $magic->create_unique_key();

            wp_localize_script( // add object to campaigns-post-type.js
                'dt_campaigns', 'campaigns_campaigns_module', [
                    'campaigns' => $campaigns,
                ]
            );
        }
    }

    public function register_type( array $types ) : array {
        if ( ! isset( $types[$this->root] ) ) {
            $types[$this->root] = [];
        }
        $types[$this->root][$this->type] = [
            'name' => 'Subscriptions',
            'root' => $this->root,
            'type' => $this->type,
            'meta_key' => 'public_key', // coaching-magic_c_key
            'actions' => [
                '' => 'Manage',
                'access_account' => 'Access Account',
            ],
            'post_type' => $this->post_type,
        ];
        return $types;
    }

    public function register_url( $template_for_url ){
        $parts = $this->parts;

        // test 1 : correct url root and type
        if ( ! $parts ){ // parts returns false
            return $template_for_url;
        }

        // test 2 : only base url requested
        if ( empty( $parts['public_key'] ) ){ // no public key present
            $template_for_url[ $parts['root'] . '/'. $parts['type'] ] = 'template-blank.php';
            return $template_for_url;
        }

        // test 3 : no specific action requested
        if ( empty( $parts['action'] ) ){ // only root public key requested
            $template_for_url[ $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] ] = 'template-blank.php';
            return $template_for_url;
        }

        // test 4 : valid action requested
        $actions = $this->magic->list_actions( $parts['type'] );
        if ( isset( $actions[ $parts['action'] ] ) ){
            $template_for_url[ $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] . '/' . $parts['action'] ] = 'template-blank.php';
        }

        return $template_for_url;
    }

    public function _has_access() : bool {
        $parts = $this->parts;

        // test 1 : correct url root and type
        if ( $parts ){ // parts returns false
            return true;
        }

        return false;
    }

    public function print_scripts(){
        // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php
        $allowed_js = [
            'jquery',
            'lodash',
            'moment',
            'datepicker',
            'site-js',
            'shared-functions',
            'mapbox-gl',
            'mapbox-cookie',
            'mapbox-search-widget',
            'google-search-widget',
            'jquery-cookie',
        ];

        global $wp_scripts;

        if ( isset( $wp_scripts ) ){
            foreach ( $wp_scripts->queue as $key => $item ){
                if ( ! in_array( $item, $allowed_js ) ){
                    unset( $wp_scripts->queue[$key] );
                }
            }
        }
        unset( $wp_scripts->registered['mapbox-search-widget']->extra['group'] );
    }

    public function print_styles(){
        // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php
        $allowed_css = [
            'foundation-css',
            'jquery-ui-site-css',
            'site-css',
            'datepicker-css',
            'mapbox-gl-css'
        ];

        global $wp_styles;
        if ( isset( $wp_styles ) ) {
            foreach ($wp_styles->queue as $key => $item) {
                if ( !in_array( $item, $allowed_css )) {
                    unset( $wp_styles->queue[$key] );
                }
            }
        }
    }

    public function form_head(){
        wp_head(); // styles controlled by wp_print_styles and wp_print_scripts actions
        $this->campaigns_styles_header();
        $this->campaigns_javascript_header();
    }

    public function form_footer(){
        wp_footer(); // styles controlled by wp_print_styles and wp_print_scripts actions
    }

    public function campaigns_styles_header(){
        ?>
        <style>
            body {
                background-color: white;
            }

            #content {
                max-width:100%;
            }

            #wrapper {
                max-width:1000px;
                margin:0 auto;
                padding: .5em;
                background-color: white;
            }

            /* Chrome, Safari, Edge, Opera */
            input::-webkit-outer-spin-button,
            input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            /* Firefox */
            input[type=number] {
                -moz-appearance: textfield;
            }
            select::-ms-expand {
                display: none;
            }

            /* size specific style section */
            @media screen and (max-width: 991px) {
                /* start of large tablet styles */

            }
            @media screen and (max-width: 767px) {
                /* start of medium tablet styles */

            }
            @media screen and (max-width: 479px) {
                /* start of phone styles */
                body {
                    background-color: white;
                }
            }

            .day-cell {
                max-width: 25%;
                float:left;
                text-align: center;
                border: 1px solid grey;
                font-size:1.2em;
                padding: 10px 5px 5px 5px;
            }
            .day-cell:hover {
                background: lightblue;
                border: 1px solid darkslategrey;
                cursor:pointer;
            }
            .progress-bar {
                height:10px;
            }
            .selected-hour {
                max-width: 100%;
                padding-top: 10px;
                border: 1px solid grey;
                font-size:1.2em;
            }
            .no-selection {
                max-width: 100%;
                padding-top: 10px;
                border: 1px solid grey;
                font-size:1.2em;
            }
            .remove-selection {
                float: right;
                color: red;
                cursor:pointer;
            }
            #email {
                display:none;
            }
        </style>
        <?php
    }

    public function campaigns_javascript_header(){
        $post = DT_Posts::get_post( 'campaigns', $this->parts['post_id'], true, false );
        if ( is_wp_error( $post ) ) {
            return $post;
        }
        $grid_id = 1;
        if ( isset( $post['location_grid'] ) && ! empty( $post['location_grid'] ) ) {
            $grid_id = $post['location_grid'][0]['id'];
        }
        ?>
        <script>
            let postObject = [<?php echo json_encode([
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'campaign_id' => $post['ID'],
                'campaign_grid_id' => $grid_id,
                'translations' => []
            ]) ?>][0]

            jQuery(document).ready(function($){
                console.log("here");
            })
        </script>
        <?php
        return true;
    }

    public function form_body(){

        $post_id = $this->parts['post_id'];
        $record = DT_Posts::get_post( "campaigns", $post_id, true, false );
        if ( is_wp_error( $record ) ){
            return;
        }
        $coverage_percentage = DT_Campaigns_Base::query_coverage_percentage( $post_id );
        $number_of_time_slots = DT_Campaigns_Base::query_coverage_total_time_slots( $post_id );

        $description = "Campaign Description";
        if ( isset( $record["description"] ) && !empty( $record["description"] ) ){
            $description = $record["description"];
        }

        if ( isset( $record['type']['key'] ) && '24hour' === $record['type']['key'] ) {
            ?>

            <div id="wrapper">
            <h3><?php echo esc_html( $description ); ?></h3>
            <div class="progress-bar-container" style="border: 1px solid black" >
                <div class="progress-bar" data-percent="<?php echo esc_html( $coverage_percentage ); ?>" style="background: dodgerblue; width:0; height:20px"></div>
            </div>
            We have covered <strong><?php echo esc_html( $coverage_percentage ); ?>%</strong> of the <?php echo esc_html( $number_of_time_slots ); ?> time slots needed to cover the region in 24 hour prayer.

            </div> <!-- form wrapper -->
            <script>
                jQuery.each( jQuery('.progress-bar'), function(){
                    jQuery(this).animate({
                        width: ( jQuery(this).data('percent') || 0 ) + '%'
                    })
                })
            </script>

            <?php
        }
    }


    /**
     * Open default restrictions for access to registered endpoints
     * @param $authorized
     * @return bool
     */
    public function authorize_url( $authorized ){
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $this->root . '/v1/'.$this->type ) !== false ) {
            $authorized = true;
        }
        return $authorized;
    }

    /**
     * Register REST Endpoints
     * @link https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
     */
    public function add_api_routes() {
        $namespace = $this->root . '/v1';
    }




}
