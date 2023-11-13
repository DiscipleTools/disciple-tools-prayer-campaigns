<?php
/**
 * Post Type Template
 */

if ( !defined( 'ABSPATH' ) ){
    exit;
} // Exit if accessed directly.


/**
 * DT_Campaign_Prayer_Fuel_Post_Type Class
 * All functionality pertaining to project update post types in DT_Campaign_Prayer_Fuel_Post_Type.
 *
 * @package  Disciple_Tools
 * @since    0.1.0
 */
class DT_Campaign_Prayer_Fuel_Post_Type
{

    public $post_type;
    public $singular;
    public $plural;
    public $args;
    public $taxonomies;
    private $language_settings;
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Disciple_Tools_Prayer_Post_Type constructor.
     *
     * @param array $args
     * @param array $taxonomies
     */
    public function __construct( $args = [], $taxonomies = [] ){
        $this->post_type = PORCH_LANDING_POST_TYPE;
        $this->singular = PORCH_LANDING_POST_TYPE_SINGLE;
        $this->plural = PORCH_LANDING_POST_TYPE_PLURAL;
        $this->args = $args;
        $this->taxonomies = $taxonomies;
        $this->language_settings = new DT_Campaign_Languages();

        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'transition_post_status', [ $this, 'transition_post' ], 10, 3 );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
        add_action( 'save_post', [ $this, 'save_post' ], 10, 3 );
        add_action( 'dt_post_updated', [ $this, 'update_post' ], 10, 5 );

        if ( is_admin() && isset( $_GET['post_type'] ) && PORCH_LANDING_POST_TYPE === $_GET['post_type'] ){
            add_action( 'pre_get_posts', [ $this, 'dt_landing_order_by_date' ] );
            add_filter( 'manage_'.$this->post_type.'_posts_columns', [ $this, 'set_custom_edit_columns' ] );
            add_action( 'manage_'.$this->post_type.'_posts_custom_column', [ $this, 'custom_column' ], 10, 2 );
            add_action( 'admin_head', [ $this, 'admin_head' ] );
        }
        global $pagenow;
        if ( is_admin() && $pagenow === 'post.php' && isset( $_GET['action'] ) && $_GET['action'] === 'edit' ){
            add_action( 'admin_head', [ $this, 'admin_head' ] );
        }

        add_filter( 'post_type_link', [ $this, 'post_type_link' ], 20, 3 );

    } // End __construct()

    public function add_meta_box( $post_type ) {
        if ( PORCH_LANDING_POST_TYPE === $post_type ) {
            add_meta_box( PORCH_LANDING_POST_TYPE . '_custom_permalink', PORCH_LANDING_POST_TYPE_SINGLE . ' Url', [ $this, 'meta_box_custom_permalink' ], PORCH_LANDING_POST_TYPE, 'side', 'default' );
            add_meta_box( PORCH_LANDING_POST_TYPE . '_page_language', 'Post Language', [ $this, 'meta_box_page_language' ], PORCH_LANDING_POST_TYPE, 'side', 'core' );
            add_meta_box( PORCH_LANDING_POST_TYPE . '_campaign_day', 'Campaign Day', [ $this, 'meta_box_campaign_day' ], PORCH_LANDING_POST_TYPE, 'side', 'core' );
        }
    }

    public static function get_prayer_fuel_permalink( $post ){
        $public_key = get_post_meta( $post->ID, PORCH_LANDING_META_KEY, true );
        $linked_campaign = get_post_meta( $post->ID, 'linked_campaign', true );
        $campaign_url = get_post_meta( $linked_campaign, 'campaign_url', true );
        $lang = get_post_meta( $post->ID, 'post_language', true );
        return trailingslashit( site_url() ) . ( $campaign_url ?? PORCH_LANDING_ROOT ) . '/' . PORCH_LANDING_TYPE . '/' . $public_key . '?lang=' . $lang;
    }

    public function meta_box_custom_permalink( $post ) {
        $link = self::get_prayer_fuel_permalink( $post );
        echo '<a href="' . esc_url( $link ) . '">'. esc_url( $link ) .'</a>';
    }

    public function meta_box_page_language( $post ) {
        $lang = get_post_meta( $post->ID, 'post_language', true );

        if ( empty( $lang ) ) {
            $lang = isset( $_GET['post_language'] ) ? sanitize_text_field( wp_unslash( $_GET['post_language'] ) ) : null;
        }

        $langs = $this->language_settings->get_enabled_languages();
        if ( empty( $lang ) ){
            $lang = 'en_US';
        }
        ?>

        <?php wp_nonce_field( 'landing-language-selector', 'landing-language-selector' ); ?>

        <select class="dt-magic-link-language-selector" name="dt-landing-language-selector">
            <?php foreach ( $langs as $code => $language ) : ?>
                <option value="<?php echo esc_html( $code ); ?>" <?php selected( $lang == $code ) ?>>
                    <?php echo esc_html( $language['flag'] ); ?> <?php echo esc_html( $language['native_name'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function meta_box_campaign_day( $post ) {
        $campaign_day = get_post_meta( $post->ID, 'day', true );

        $latest_campaign_day = DT_Campaign_Prayer_Post_Importer::instance()->find_latest_prayer_fuel_day();
        $day = isset( $_GET['day'] ) ? sanitize_text_field( wp_unslash( $_GET['day'] ) ) : $latest_campaign_day + 1;

        $value = !empty( $campaign_day ) ? $campaign_day : $day;

        $date = DT_Campaign_Fuel::date_of_campaign_day( (int) $value );
        ?>

        <?php wp_nonce_field( 'landing-day-selector', 'landing-day-selector' ) ?>
        <input
            id="dt-landing-day-selector"
            name="dt-landing-day-selector"
            type="number"
            min="1"
            value="<?php echo esc_html( $value ) ?>"
            data-day="<?php echo esc_html( $value ) ?>"
        >

        <p id="dt-landing-date-display" data-date="<?php echo esc_html( $date ) ?>">
            <?php echo esc_html( gmdate( 'Y/m/d', strtotime( $date ) ) ) ?>
        </p>
        <p>
            Or give a specific date that you want this post to be published on.
        </p>

        <input
            id="dt-landing-date-selector"
            name="dt-landing-date-selector"
            type="date"
        >

        <?php
    }

    public function post_type_link( $link, $post, $leavename ){
        if ( $post->post_type === PORCH_LANDING_POST_TYPE ){
            return self::get_prayer_fuel_permalink( $post );
        }
        return $link;
    }

    public function admin_head(){
        ?>
        <script>
            jQuery(document).ready(function($) {
                setTimeout(function (){
                    $('.edit-post-fullscreen-mode-close').attr('href', '<?php echo esc_url_raw( admin_url( 'admin.php?page=dt_prayer_campaigns&tab=dt_prayer_fuel' ) ) ?>')
                }, 2000)
            })
        </script>
        <?php
    }

    public function save_post( $id, $post, $update ){

        $post_submission = dt_recursive_sanitize_array( wp_unslash( $_POST ) );

        if ( isset( $_POST['landing-language-selector'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['landing-language-selector'] ) ), 'landing-language-selector' ) ) {
            if ( isset( $_POST['dt-landing-language-selector'] ) ){
                update_post_meta( $post_submission['ID'], 'post_language', $post_submission['dt-landing-language-selector'] );
            }
        }

        if ( isset( $_POST['landing-day-selector'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['landing-day-selector'] ) ), 'landing-day-selector' ) ) {

            if ( !empty( $_POST['dt-landing-date-selector'] ) ) {
                update_post_meta( $id, 'fixed', true );
                $post_date = $post_submission['dt-landing-date-selector'];
                $day = DT_Campaign_Fuel::what_day_in_campaign( $post_date );
            } else if ( isset( $_POST['dt-landing-day-selector'] ) ){
                $day = $post_submission['dt-landing-day-selector'];
            }
            update_post_meta( $id, 'day', $day );
        }

        if ( $post->post_type === PORCH_LANDING_POST_TYPE ) {
            $day = get_post_meta( $id, 'day', true );
            $lang = get_post_meta( $id, 'post_language', true );

            if ( empty( $day ) && isset( $_GET['day'] ) && !empty( $_GET['day'] ) ){
                $day = sanitize_key( wp_unslash( $_GET['day'] ) );
                update_post_meta( $id, 'day', $day );
            }
            if ( empty( $lang ) && isset( $_GET['post_language'] ) && !empty( $_GET['post_language'] ) ){
                $lang = sanitize_text_field( wp_unslash( $_GET['post_language'] ) );
                update_post_meta( $id, 'post_language', $lang );
            }

            if ( !empty( $day ) ) {
                update_post_meta( $id, PORCH_LANDING_META_KEY, $day );
            }

            $campaign = DT_Campaign_Landing_Settings::get_campaign();
            if ( isset( $campaign['ID'] ) && $update === false ){
                update_post_meta( $id, 'linked_campaign', $campaign['ID'] );
            }
        }
    }

    public function update_post( $post_type, $post_id, $initial_fields, $existing_post, $post ) {

        if ( $post_type === 'campaigns' ) {
            if ( array_key_exists( 'start_date', $initial_fields ) ) {
                if ( $post['start_date']['timestamp'] !== $existing_post['start_date']['timestamp'] ) {
                    global $wpdb;

                    $old_start_date = $existing_post['start_date']['formatted'];
                    $new_start_date = $post['start_date']['formatted'];

                    $diff_in_start_days = DT_Campaign_Fuel::diff_days_between_dates( $old_start_date, $new_start_date );

                    $result = $wpdb->query( $wpdb->prepare( "
                        UPDATE $wpdb->postmeta as pm
                        JOIN $wpdb->posts as p
                        ON ( p.ID = pm.post_id AND p.post_type = %s )
                        JOIN $wpdb->postmeta as pm2
                        ON ( p.ID = pm2.post_id AND pm2.meta_key = 'fixed' AND pm2.meta_value = '1' )
                        JOIN $wpdb->postmeta as pm3
                        ON ( p.ID = pm3.post_id AND pm3.meta_key = %s )
                        SET pm.meta_value = pm.meta_value - %1s,
                        pm3.meta_value = pm3.meta_value - %1s
                        WHERE pm.meta_key = 'day'
                    ", PORCH_LANDING_POST_TYPE, PORCH_LANDING_META_KEY, $diff_in_start_days, $diff_in_start_days  ) );
                }
            }
        }
    }

    /**
     * Register the post type.
     *
     * @return void
     */
    public function register_post_type() {
        register_post_type($this->post_type, /* (http://codex.wordpress.org/Function_Reference/register_post_type) */
            // let's now add all the options for this post type
            array(
                'labels' => array(
                    'name' => 'Landing Posts', /* This is the Title of the Group */
                    'singular_name' => $this->singular, /* This is the individual type */
                    'all_items' => 'All '.$this->plural, /* the all items menu item */
                    'add_new' => 'Add New', /* The add new menu item */
                    'add_new_item' => 'Add New '.$this->singular, /* Add New Display Title */
                    'edit' => 'Edit', /* Edit Dialog */
                    'edit_item' => 'Edit '.$this->singular, /* Edit Display Title */
                    'new_item' => 'New '.$this->singular, /* New Display Title */
                    'view_item' => 'View '.$this->singular, /* View Display Title */
                    'search_items' => 'Search '.$this->plural, /* Search Custom Type Title */
                    'not_found' => 'Nothing found in the Database.', /* This displays if there are no entries yet */
                    'not_found_in_trash' => 'Nothing found in Trash', /* This displays if there is nothing in the trash */
                    'parent_item_colon' => ''
                ), /* end of arrays */
                'description' => $this->singular, /* Custom Type Description */
                'public' => true,
                'publicly_queryable' => true,
                'exclude_from_search' => true,
                'show_ui' => true,
                'query_var' => true,
                'show_in_nav_menus' => true,
                'menu_position' => 5, /* this is what order you want it to appear in on the left hand side menu */
                'menu_icon' => 'dashicons-book', /* the icon for the custom post type menu. uses built-in dashicons (CSS class name) */
                'rewrite' => true, /* you can specify its url slug */
                'has_archive' => true, /* you can rename the slug here */
                'capabilities' => [
                    'create_posts'        => 'create_'.$this->post_type,
                    'edit_post'           => 'edit_'.$this->post_type, // needed for bulk edit
                    'read_post'           => 'read_'.$this->post_type,
                    'delete_post'         => 'delete_'.$this->post_type, // delete individual post
                    'delete_others_posts' => 'delete_others_'.$this->post_type.'s',
                    'delete_posts'        => 'delete_'.$this->post_type.'s', // bulk delete posts
                    'edit_posts'          => 'edit'.$this->post_type.'s', //menu link in WP Admin
                    'edit_others_posts'   => 'edit_others_'.$this->post_type.'s',
                    'publish_posts'       => 'publish_'.$this->post_type.'s',
                    'read_private_posts'  => 'read_private_'.$this->post_type.'s',
                ],
                'capability_type' => $this->post_type,
                'hierarchical' => true,
                'show_in_rest' => true,
                'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' )
            ) /* end of options */
        ); /* end of register post type */
    } // End register_post_type()


    /**
     * Order post list by date by default
     * @param $query
     * @return void
     */
    public function dt_landing_order_by_date( $query ){
        if ( !is_admin() ){
            return;
        }

        require_once( ABSPATH . 'wp-admin/includes/screen.php' );

        $screen = get_current_screen();

        if ( !$screen ) {
            return;
        }

        if ( 'edit' == $screen->base
            && 'landing' == $screen->post_type
            && !isset( $_GET['orderby'] ) ){
            $query->set( 'orderby', 'date' );
            $query->set( 'order', 'ASC' );
        }
    }



    public function transition_post( $new_status, $old_status, $post ) {
        if ( ( 'publish' == $new_status || 'future' == $new_status ) && $post->post_type == PORCH_LANDING_POST_TYPE ) {

            $post_id = $post->ID;
            $day = get_post_meta( $post_id, 'day', true );
            $lang = get_post_meta( $post_id, 'post_language', true );

            $slug = !empty( $day ) ? $day : trim( strtolower( $post->post_title ) );
            $slug = str_replace( ' ', '-', $slug );
            $slug = str_replace( '"', '', $slug );
            $slug = str_replace( '&', '', $slug );
            $slug = str_replace( "'", '', $slug );
            $slug = str_replace( ',', '', $slug );
            $slug = str_replace( ':', '', $slug );
            $slug = str_replace( ';', '', $slug );
            $slug = str_replace( '.', '', $slug );
            $slug = str_replace( '/', '', $slug );
            $slug = urlencode( $slug );

            $current_public_key = get_post_meta( $post_id, PORCH_LANDING_META_KEY, true );
            if ( $slug !== $current_public_key ) {
                update_post_meta( $post_id, PORCH_LANDING_META_KEY, $slug );
                global $wpdb;
                $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET guid = %s WHERE ID = %s;", trailingslashit( site_url() ) . PORCH_LANDING_ROOT . '/' . PORCH_LANDING_TYPE . '/' . $slug . ( !empty( $lang ) ? '&lang=' . $lang : '' ), $post_id ) );
            }
        }
    }

    // Add the custom columns to the book post type:
    public function set_custom_edit_columns( $columns ){
        unset( $columns['author'] );
        $columns['url'] = 'URL';
        $columns['language'] = 'Language';
        $columns['last_modified'] = 'Last Modified';

        return $columns;
    }



    // Add the data to the custom columns for the book post type:
    public function custom_column( $column, $post_id ) {
        switch ( $column ) {
            case 'url' :
                $public_key = get_post_meta( $post_id, PORCH_LANDING_META_KEY, true );
                $language = get_post_meta( $post_id, 'post_language', true );
                if ( empty( $language ) ){
                    $language = 'en_US';
                }
                $url = trailingslashit( site_url() ) . PORCH_LANDING_ROOT . '/' . PORCH_LANDING_TYPE . '/' . $public_key . '?lang=' . $language;
                echo '<a href="' . esc_url( $url ) . '">'. esc_html( $url ) .'</a>';
                break;
            case 'language' :
                $language = get_post_meta( $post_id, 'post_language', true );
                if ( empty( $language ) ){
                    $language = 'en_US';
                }
                /* This is waiting for the language PR to be merged in before it can use that functionality */
                $languages = dt_get_available_languages( true );
                if ( !isset( $languages[$language]['flag'] ) ){
                    echo esc_html( $language );
                } else {
                    echo esc_html( $languages[$language]['flag'] );
                }
                break;
            case 'last_modified':
                $post = get_post( $post_id, ARRAY_A );
                if ( $post['post_modified'] !== $post['post_date'] ){
                    echo esc_html( $post['post_modified'] );
                }
        }
    }

    /**
     * Get the days posts
     *
     * @param int $day
     *
     * @return WP_Query
     */
    public function get_days_posts( int $day ) {

        $campaign = DT_Campaign_Landing_Settings::get_campaign();

        $lang = dt_campaign_get_current_lang();

        // query for getting posts in the selected language.
        $lang_query = [
            [
                'key'     => 'post_language',
                'value'   => $lang,
                'compare' => '=',
            ],
        ];
        if ( $lang === 'en_US' ){
            $lang_query[] = [
                'key'     => 'post_language',
                'compare' => 'NOT EXISTS',
            ];
            $lang_query['relation'] = 'OR';
        }

        $meta_query = [
            'relation' => 'AND',
            [
                'key' => 'day',
                'value' => $day,
                'compare' => '=',
            ],
            [
                'key' => 'linked_campaign',
                'value' => $campaign['ID'],
                'compare' => '=',
            ],
            $lang_query,
        ];

        //get latest published post
        $today = new WP_Query( [
            'post_type' => PORCH_LANDING_POST_TYPE,
            'post_status' => [ 'publish', 'future' ] ,
            'posts_per_page' => -1,
            'orderby' => 'post_date',
            'order' => 'DESC',
            'meta_query' => $meta_query,
        ] );

        // if no post in the selected language, get post in english.
        if ( empty( $today->posts ) ){

            $args = array(
                'post_type' => PORCH_LANDING_POST_TYPE,
                'post_status' => [ 'publish', 'future' ],
                'posts_per_page' => 1,
                'orderby' => 'post_date',
                'order' => 'DESC',
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => 'linked_campaign',
                        'value' => $campaign['ID'],
                        'compare' => '=',
                        'type' => 'numeric',
                    ],
                    [
                        'key' => 'day',
                        'value' => $day,
                    ],
                    [
                        'relation' => 'OR',
                        [
                            'key'     => 'post_language',
                            'value'   => 'en_US',
                            'compare' => '=',
                        ],
                        [
                            'key'     => 'post_language',
                            'compare' => 'NOT EXISTS',
                        ],
                    ]
                ]
            );
            $today = new WP_Query( $args );
        }

        return $today;

    }

    public function get_most_recent_post(){
        $campaign = DT_Campaign_Landing_Settings::get_campaign();

        $lang = dt_campaign_get_current_lang();

        // query for getting posts in the selected language.
        $lang_query = [
            [
                'key'     => 'post_language',
                'value'   => $lang,
                'compare' => '=',
            ],
        ];
        if ( $lang === 'en_US' ){
            $lang_query[] = [
                'key'     => 'post_language',
                'compare' => 'NOT EXISTS',
            ];
            $lang_query['relation'] = 'OR';
        }

        $meta_query = [
            'relation' => 'AND',
            'day_clause' => [
                'key' => 'day',
                'type' => 'numeric',
            ],
            [
                'key' => 'linked_campaign',
                'value' => $campaign['ID'],
                'compare' => '=',
            ],
            $lang_query,
        ];

        //get latest published post
        $today = new WP_Query( [
            'post_type' => PORCH_LANDING_POST_TYPE,
            'post_status' => [ 'publish', 'future' ] ,
            'posts_per_page' => 1,
            'orderby' => 'day_clause',
            'order' => 'DESC',
            'meta_query' => $meta_query,
        ] );

        // if no post in the selected language, get post in english.
        if ( empty( $today->posts ) ){

            $args = array(
                'post_type' => PORCH_LANDING_POST_TYPE,
                'post_status' => [ 'publish', 'future' ],
                'posts_per_page' => 1,
                'orderby' => 'post_date',
                'order' => 'DESC',
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => 'linked_campaign',
                        'value' => $campaign['ID'],
                        'compare' => '=',
                        'type' => 'numeric',
                    ],
                    [
                        'relation' => 'OR',
                        [
                            'key'     => 'post_language',
                            'value'   => 'en_US',
                            'compare' => '=',
                        ],
                        [
                            'key'     => 'post_language',
                            'compare' => 'NOT EXISTS',
                        ],
                    ]
                ]
            );
            $today = new WP_Query( $args );
        }

        return $today;


    }
} // End Class
DT_Campaign_Prayer_Fuel_Post_Type::instance();
