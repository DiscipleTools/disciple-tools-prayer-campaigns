<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DT_Generic_Porch {

    private $child_porch_dir;
    private static $instance = null;
    public static function instance( $child_porch_dir = '' ) {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self( $child_porch_dir );
        }
        return self::$instance;
    }

    private function __construct( $child_porch_dir ) {
        $this->child_porch_dir = $child_porch_dir;

        require_once __DIR__ . '/site/functions.php';
        $fields = DT_Porch_Settings::settings();
        if ( ! defined( 'PORCH_TITLE' ) ) {
            $title = $fields['title']['value'] ?? '24/7 Prayer';
            define( 'PORCH_TITLE', $title ); // Used in tabs and titles, avoid special characters. Spaces are okay.
        }

        // Set Default Language
        $default_language = 'en_US';

        if ( isset( $fields['default_language'] ) && ! empty( $fields['default_language']['value'] ) ) {
            $default_language = $fields['default_language']['value'];
        }

        if ( ! defined( 'PORCH_DEFAULT_LANGUAGE' ) ) {
            define( 'PORCH_DEFAULT_LANGUAGE', $default_language );
        }

        $theme_manager = new DT_Porch_Theme();
        $theme = $theme_manager->get_default_theme();
        if ( isset( $fields['theme_color']['value'] ) && ! empty( $fields['theme_color']['value'] ) && ! defined( 'PORCH_COLOR_SCHEME' ) ) {
            $theme_name = $fields['theme_color']['value'];
            $theme = $theme_manager->get_theme( $theme_name );
        }

        if ( isset( $fields['custom_theme_color']['value'] ) && ! empty( $fields['custom_theme_color']['value'] ) ) {
            $theme = [];
            $theme['name'] = 'custom';
            $theme['color'] = $fields['custom_theme_color']['value'];
        }
        if ( !defined( 'PORCH_COLOR_SCHEME' ) ) {
            define( 'PORCH_COLOR_SCHEME', $theme['name'] );
        }
        if ( !defined( 'PORCH_COLOR_SCHEME_HEX' ) ) {
            define( 'PORCH_COLOR_SCHEME_HEX', $theme['color'] );
        }

        // MICROSITE Magic Links
        require_once( 'site/home.php' );
        require_once( 'site/archive.php' );
        require_once( 'site/stats.php' );
        require_once( 'site/landing.php' );
        require_once( 'site/rest.php' );

        /* TODO: Porch emails need setting up like the ramadan porch in this file... */
        /* require_once( 'admin/campaigns-config.php' ); */

//        if ( is_admin() ){
//            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 ); // admin plugin page description
//        }

//        $this->i18n();
    }

    /**
     * Loads the translation files.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function i18n() {
        $domain = 'dt-generic-porch';
        dt_campaign_reload_text_domain();
    }

    /**
     * Conditionally require a file based on whether we are using a child porch or not
     *
     * @param string $file_name
     */
    public function require_once( $file_name ) {
        $file_path = trailingslashit( $this->child_porch_dir ) . "site/$file_name";

        if ( !empty( $this->child_porch_dir ) && file_exists( $file_path ) ) {
            require_once( $file_path );
        } else {
            require_once( __DIR__ . "/site/$file_name" );
        }
    }

    /**
     * Return the directory that the generic porch assets can be found in
     */
    public static function assets_dir() {
        return trailingslashit( plugin_dir_url( __File__ ) ) . 'site/';
    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @since  0.1
     * @access public
     * @return string
     */
    public function __toString() {
        return 'dt-generic-porch';
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @param string $method
     * @param array $args
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ) {
        _doing_it_wrong( 'dt-generic-porch::' . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
}
