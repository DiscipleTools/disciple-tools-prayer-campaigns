<?php

class DT_Generic_Porch {

    private static $instance = null;
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
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
            $theme["name"] = 'custom';
            $theme["color"] = $fields['custom_theme_color']['value'];
        }
        define( 'PORCH_COLOR_SCHEME', $theme["name"] );
        define( 'PORCH_COLOR_SCHEME_HEX', $theme["color"] );

        // POST TYPE and ACCESS
        require_once( 'site/roles-and-permissions.php' );
        require_once( 'site/landing-post-type.php' );

        // MICROSITE Magic Links
        require_once( 'site/home.php' );
        require_once( 'site/archive.php' );
        require_once( 'site/stats.php' );
        require_once( 'site/landing.php' );
        require_once( 'site/rest.php' );

        /* TODO: Porch emails need setting up like the ramadan porch in this file... */
        /* require_once( 'admin/campaigns-config.php' ); */

        if ( is_admin() ){
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 ); // admin plugin page description
        }

        $this->i18n();
    }

    public static function define_porch_constants() {
        if ( ! defined( 'PORCH_ROOT' ) ) {
            define( 'PORCH_ROOT', 'porch_app' ); // Alphanumeric key. Use underscores not hyphens. No special characters.
        }
        if ( ! defined( 'PORCH_TYPE' ) ) {
            define( 'PORCH_TYPE', '5' ); // Alphanumeric key. Use underscores not hyphens. No special characters.
        }
        if ( ! defined( 'PORCH_TOKEN' ) ) {
            define( 'PORCH_TOKEN', 'porch_app_5' ); // Alphanumeric key. Use underscores not hyphens. No special characters. Must be less than 20 characters
        }
        if ( ! defined( 'PORCH_LANDING_ROOT' ) ) {
            define( 'PORCH_LANDING_ROOT', 'prayer' ); // Alphanumeric key. Use underscores not hyphens. No special characters.
        }
        if ( ! defined( 'PORCH_LANDING_TYPE' ) ) {
            define( 'PORCH_LANDING_TYPE', 'fuel' ); // Alphanumeric key. Use underscores not hyphens. No special characters. Must be less than 20 characters
        }
        if ( ! defined( 'PORCH_LANDING_META_KEY' ) ) {
            define( 'PORCH_LANDING_META_KEY', PORCH_LANDING_ROOT . '_' . PORCH_LANDING_TYPE . '_magic_key' ); // Alphanumeric key. Use underscores not hyphens. No special characters. Must be less than 20 characters
        }
        if ( ! defined( 'PORCH_LANDING_POST_TYPE' ) ) {
            define( 'PORCH_LANDING_POST_TYPE', 'landing' ); // Alphanumeric key. Use underscores not hyphens. No special characters. Must be less than 20 characters
        }
        if ( ! defined( 'PORCH_LANDING_POST_TYPE_SINGLE' ) ) {
            define( 'PORCH_LANDING_POST_TYPE_SINGLE', 'Porch' ); // Alphanumeric key. Use underscores not hyphens. No special characters. Must be less than 20 characters
        }
        if ( ! defined( 'PORCH_LANDING_POST_TYPE_PLURAL' ) ) {
            define( 'PORCH_LANDING_POST_TYPE_PLURAL', 'Porch' ); // Alphanumeric key. Use underscores not hyphens. No special characters. Must be less than 20 characters
        }
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
        load_plugin_textdomain( $domain, false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ). 'support/languages' );
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
        _doing_it_wrong( "dt-generic-porch::" . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
}
