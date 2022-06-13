<?php
/**
 * Post Type Template
 */

if ( !defined( 'ABSPATH' ) ){
    exit;
} // Exit if accessed directly.


/**
 * P4_Ramadan_Porch_Landing_Post_Type Class
 * All functionality pertaining to project update post types in P4_Ramadan_Porch_Landing_Post_Type.
 *
 * @package  Disciple_Tools
 * @since    0.1.0
 */
class P4_Ramadan_Porch_Landing_Post_Type
{

    public $post_type;
    public $singular;
    public $plural;
    public $args;
    public $taxonomies;
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

        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'transition_post_status', [ $this, 'transition_post' ], 10, 3 );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
        add_action( 'save_post', [ $this, 'save_post' ] );

        if ( is_admin() && isset( $_GET['post_type'] ) && PORCH_LANDING_POST_TYPE === $_GET['post_type'] ){
            add_action( 'pre_get_posts', [ $this, 'dt_landing_order_by_date' ] );
            add_filter( 'manage_'.$this->post_type.'_posts_columns', [ $this, 'set_custom_edit_columns' ] );
            add_action( 'manage_'.$this->post_type.'_posts_custom_column', [ $this, 'custom_column' ], 10, 2 );
        }

    } // End __construct()

    public function add_meta_box( $post_type ) {
        if ( PORCH_LANDING_POST_TYPE === $post_type ) {
            add_meta_box( PORCH_LANDING_POST_TYPE . '_custom_permalink', PORCH_LANDING_POST_TYPE_SINGLE . ' Url', [ $this, 'meta_box_custom_permalink' ], PORCH_LANDING_POST_TYPE, 'side', 'default' );
            add_meta_box( PORCH_LANDING_POST_TYPE . '_page_language', 'Post Language', [ $this, 'meta_box_page_language' ], PORCH_LANDING_POST_TYPE, 'side', 'default' );
        }
    }

    public function meta_box_custom_permalink( $post ) {
        $public_key = get_post_meta( $post->ID, PORCH_LANDING_META_KEY, true );
        echo '<a href="' . esc_url( trailingslashit( site_url() ) . PORCH_LANDING_ROOT . '/' . PORCH_LANDING_TYPE . '/' . $public_key ) . '">'. esc_url( trailingslashit( site_url() ) . PORCH_LANDING_ROOT . '/' . PORCH_LANDING_TYPE . '/' . $public_key ) .'</a>';
    }

    public function meta_box_page_language( $post ) {
        $lang = get_post_meta( $post->ID, 'post_language', true );
        $langs = dt_ramadan_list_languages();
        if ( empty( $lang ) ){
            $lang = 'en_US';
        }
        ?>
        <select class="dt-magic-link-language-selector" name="dt-landing-language-selector">
            <?php foreach ( $langs as $code => $language ) : ?>
                <option value="<?php echo esc_html( $code ); ?>" <?php selected( $lang === $code ) ?>>
                    <?php echo esc_html( $language["flag"] ); ?> <?php echo esc_html( $language["native_name"] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function save_post( $id ){
        if ( isset( $_POST["dt-landing-language-selector"] ) ){
            $post_submission = dt_recursive_sanitize_array( $_POST );
            update_post_meta( $post_submission["ID"], 'post_language', $post_submission["dt-landing-language-selector"] );
        }
    }


    /**
     * Register the post type.
     *
     * @access public
     * @return void
     */
    public function register_post_type() {
        register_post_type($this->post_type, /* (http://codex.wordpress.org/Function_Reference/register_post_type) */
            // let's now add all the options for this post type
            array(
                'labels' => array(
                    'name' => $this->plural, /* This is the Title of the Group */
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

        $screen = get_current_screen();
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
            $slug = trim( strtolower( $post->post_title ) );
            $slug = str_replace( ' ', '-', $slug );
            $slug = str_replace( '"', '', $slug );
            $slug = str_replace( '&', '', $slug );
            $slug = str_replace( "'", '', $slug );
            $slug = str_replace( ",", '', $slug );
            $slug = str_replace( ":", '', $slug );
            $slug = str_replace( ";", '', $slug );
            $slug = str_replace( ".", '', $slug );
            $slug = str_replace( "/", '', $slug );
            $slug = urlencode( $slug );

            $current_public_key = get_post_meta( $post_id, PORCH_LANDING_META_KEY, true );
            if ( $slug !== $current_public_key ) {
                update_post_meta( $post_id, PORCH_LANDING_META_KEY, $slug );
                global $wpdb;
                $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET guid = %s WHERE ID = %s;", trailingslashit( site_url() ) . PORCH_LANDING_ROOT . '/' . PORCH_LANDING_TYPE . '/' . $slug, $post_id ) );
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
                    $language = "en_US";
                }
                $url = trailingslashit( site_url() ) . PORCH_LANDING_ROOT . '/' . PORCH_LANDING_TYPE . '/' . $public_key . '?lang=' . $language;
                echo '<a href="' . esc_url( $url ) . '">'. esc_html( $url ) .'</a>';
                break;
            case 'language' :
                $language = get_post_meta( $post_id, 'post_language', true );
                if ( empty( $language ) ){
                    $language = "en_US";
                }
                $languages = dt_ramadan_list_languages();
                if ( !isset( $languages[$language]["flag"] ) ){
                    echo esc_html( $language );
                } else {
                    echo esc_html( $languages[$language]["flag"] );
                }
                break;
            case 'last_modified':
                $post = get_post( $post_id, ARRAY_A );
                if ( $post['post_modified'] !== $post['post_date'] ){
                    echo esc_html( $post['post_modified'] );
                }
        }
    }
} // End Class
P4_Ramadan_Porch_Landing_Post_Type::instance();
