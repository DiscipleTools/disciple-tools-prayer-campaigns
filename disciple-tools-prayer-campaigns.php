<?php
/**
 * Plugin Name: Disciple.Tools - Prayer Campaigns
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools-prayer-campaigns
 * Description: Add a prayer subscriptions module to Disciple.Tools that allows for non-users to subscribe to pray for specific locations at specific times, supporting both time and geographic prayer saturation for your project.
 * Text Domain: disciple-tools-prayer-campaigns
 * Domain Path: /languages
 * Version: 4.1.0
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools-prayer-campaigns
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.6.1
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @version 0.1 Initializing plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

add_filter( 'dt_plugins', function ( $plugins ){
    $plugin_data = get_file_data( __FILE__, [ 'Version' => 'Version', 'Plugin Name' => 'Plugin Name' ], false );
    $plugins['disciple-tools-prayer-campaigns'] = [
        'plugin_url' => trailingslashit( plugin_dir_url( __FILE__ ) ),
        'version' => $plugin_data['Version'] ?? null,
        'name' => $plugin_data['Plugin Name'] ?? null,
    ];
    return $plugins;
});

/**
 * Gets the instance of the `DT_Prayer_Campaigns` class.
 *
 * @since  0.1
 * @access public
 * @return object|bool
 */
function dt_prayer_campaigns() {
    $dt_prayer_campaigns_required_dt_theme_version = '1.43.0';
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;

    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = class_exists( 'Disciple_Tools' );
    if ( $is_theme_dt && version_compare( $version, $dt_prayer_campaigns_required_dt_theme_version, '<' ) ) {
        add_action( 'admin_notices', 'dt_prayer_campaigns_hook_admin_notice' );
        add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );
        return false;
    }
    if ( !$is_theme_dt ){
        return false;
    }
    /**
     * Load useful function from the theme
     */
    if ( !defined( 'DT_FUNCTIONS_READY' ) ){
        require_once get_template_directory() . '/dt-core/global-functions.php';
    }

    return DT_Prayer_Campaigns::instance();

}
add_action( 'after_setup_theme', 'dt_prayer_campaigns', 20 );
require_once( 'campaign-functions/setup-functions.php' );

/**
 * Fires after WordPress has finished loading but before any headers are sent.
 *
 */
add_action( 'after_setup_theme', function() : void {
    require_once __DIR__ . '/porches/interfaces/dt-porch-loader-interface.php';
    require_once __DIR__ . '/porches/generic/dt-generic-porch-loader.php';
    require_once __DIR__ . '/porches/ramadan/dt-ramadan-porch-loader.php';
} );

/**
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class DT_Prayer_Campaigns {

    public $plugin_dir_path = null;
    public $plugin_dir_url = null;

    private static $instance = null;
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            $i = new DT_Prayer_Campaigns();
            self::$instance = $i;
        }
        return self::$instance;
    }
    public static function get_url_path(){
        return trailingslashit( plugin_dir_url( __FILE__ ) );
    }
    public static function get_dir_path(){
        return trailingslashit( plugin_dir_path( __FILE__ ) );
    }

    private function __construct() {
        $this->plugin_dir_path = trailingslashit( plugin_dir_path( __FILE__ ) );
        $this->plugin_dir_url = trailingslashit( plugin_dir_url( __FILE__ ) );
        $this->define_porch_constants();

        require_once( 'campaign-functions/utils.php' );
        require_once( 'campaign-functions/time-utilities.php' );
        require_once( 'campaign-functions/send-email-utility.php' );
        require_once( 'campaign-functions/cron-schedule.php' );

        require_once( 'post-type/loader.php' );
        require_once( 'classes/dt-campaign-fuel.php' );
        require_once( 'classes/dt-campaign-global-settings.php' );
        require_once( 'classes/dt-porch-settings.php' );
        require_once( 'classes/dt-campaign-languages.php' );
        require_once( 'classes/dt-porch-theme.php' );
        require_once( 'classes/dt-porch-admin-tab-base.php' );
        require_once( 'classes/dt-porch-selector.php' );

        require_once( 'porches/loader.php' );
        require_once( 'classes/dt-campaign-landing-settings.php' );

        require_once( 'magic-links/campaign-magic-link.php' );
        require_once( 'magic-links/ongoing/ongoing.php' );
        require_once( 'magic-links/subscription-management/subscription-management.php' );
        require_once( 'magic-links/campaign-resend-email/magic-link-post-type.php' );

        if ( is_admin() ) {
//            require_once( __DIR__ . '/admin/config-required-plugins.php' );
            require_once __DIR__ . '/admin/dt-prayer-campaigns.php';
            require_once __DIR__ . '/admin/admin-menu-and-tabs.php'; // adds starter admin page and section for plugin
            require_once __DIR__ . '/admin/admin-media-tab.php';
            DT_Prayer_Campaigns_Menu::instance();
        }

        $lang = dt_campaign_get_current_lang();
        dt_campaign_add_lang_to_cookie( $lang );
        dt_campaign_set_translation( $lang );


        if ( is_admin() ) { // adds links to the plugin description area in the plugin admin list.
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 );
        }

        if ( !is_admin() ) {
            require_once( plugin_dir_path( __FILE__ ) . '/parts/components.php' );
        }
    }

    private function define_porch_constants() {
        if ( ! defined( 'CAMPAIGN_LANDING_ROOT' ) ) {
            define( 'CAMPAIGN_LANDING_ROOT', 'prayer' ); // Alphanumeric key. Use underscores not hyphens. No special characters.
        }
        if ( ! defined( 'CAMPAIGN_LANDING_TYPE' ) ) {
            define( 'CAMPAIGN_LANDING_TYPE', 'fuel' ); // Alphanumeric key. Use underscores not hyphens. No special characters. Must be less than 20 characters
        }
        if ( ! defined( 'CAMPAIGN_LANDING_META_KEY' ) ) {
            define( 'CAMPAIGN_LANDING_META_KEY', CAMPAIGN_LANDING_ROOT . '_' . CAMPAIGN_LANDING_TYPE . '_magic_key' ); // Alphanumeric key. Use underscores not hyphens. No special characters. Must be less than 20 characters
        }
        if ( ! defined( 'CAMPAIGN_LANDING_POST_TYPE' ) ) {
            define( 'CAMPAIGN_LANDING_POST_TYPE', 'landing' ); // Alphanumeric key. Use underscores not hyphens. No special characters. Must be less than 20 characters
        }
        if ( ! defined( 'CAMPAIGN_LANDING_POST_TYPE_SINGLE' ) ) {
            define( 'CAMPAIGN_LANDING_POST_TYPE_SINGLE', 'Prayer Fuel' ); // Alphanumeric key. Use underscores not hyphens. No special characters. Must be less than 20 characters
        }
        if ( ! defined( 'CAMPAIGN_LANDING_POST_TYPE_PLURAL' ) ) {
            define( 'CAMPAIGN_LANDING_POST_TYPE_PLURAL', 'Prayer Fuel' ); // Alphanumeric key. Use underscores not hyphens. No special characters. Must be less than 20 characters
        }
    }

    /**
     * Filters the array of row meta for each/specific plugin in the Plugins list table.
     * Appends additional links below each/specific plugin on the plugins page.
     */
    public function plugin_description_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
        if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
            $links_array[] = '<a href="https://disciple.tools/plugins/prayer-campaigns/">Disciple.Tools Prayer Campaigns</a>';
        }

        return $links_array;
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function activation() {
        // add elements here that need to fire on activation
    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function deactivation() {
        // add functions here that need to happen on deactivation
        delete_option( 'dismissed-disciple-tools-prayer-campaigns' );
    }

    /**
     * Loads the translation files.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function i18n() {
        $domain = 'disciple-tools-prayer-campaigns';
        load_plugin_textdomain( $domain, false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ). 'languages' );
    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @since  0.1
     * @access public
     * @return string
     */
    public function __toString() {
        return 'disciple-tools-prayer-campaigns';
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
        _doing_it_wrong( 'dt_prayer_campaigns::' . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
}


/**
 * Run migrations after theme is loaded
 */
add_action( 'init', function (){
    try {
        require_once( plugin_dir_path( __FILE__ ) . '/admin/class-migration-engine.php' );
        DT_Prayer_Campaigns_Migration_Engine::migrate( DT_Prayer_Campaigns_Migration_Engine::$migration_number );
    } catch ( Throwable $e ) {
        new WP_Error( 'migration_error', 'Migration engine failed to migrate.' );
    }
    DT_Prayer_Campaigns_Migration_Engine::display_migration_and_lock();
} );

// Register activation hook.
register_activation_hook( __FILE__, [ 'DT_Prayer_Campaigns', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'DT_Prayer_Campaigns', 'deactivation' ] );


if ( ! function_exists( 'dt_prayer_campaigns_hook_admin_notice' ) ) {
    function dt_prayer_campaigns_hook_admin_notice() {
        global $dt_prayer_campaigns_required_dt_theme_version;
        $wp_theme = wp_get_theme();
        $current_version = $wp_theme->version;
        $message = "'Disciple.Tools - Subscriptions' plugin requires 'Disciple.Tools' theme to work. Please activate 'Disciple.Tools' theme or make sure it is latest version.";
        if ( $wp_theme->get_template() === 'disciple-tools-theme' ){
            $message .= ' ' . sprintf( esc_html( 'Current Disciple.Tools version: %1$s, required version: %2$s' ), esc_html( $current_version ), esc_html( $dt_prayer_campaigns_required_dt_theme_version ) );
        }
        // Check if it's been dismissed...
        if ( ! get_option( 'dismissed-disciple-tools-prayer-campaigns', false ) ) { ?>
            <div class="notice notice-error notice-disciple-tools-prayer-campaigns is-dismissible" data-notice="disciple-tools-prayer-campaigns">
                <p><?php echo esc_html( $message );?></p>
            </div>
            <script>
                jQuery(function($) {
                    $( document ).on( 'click', '.notice-disciple-tools-prayer-campaigns .notice-dismiss', function () {
                        $.ajax( ajaxurl, {
                            type: 'POST',
                            data: {
                                action: 'dismissed_notice_handler',
                                type: 'disciple-tools-prayer-campaigns',
                                security: '<?php echo esc_html( wp_create_nonce( 'wp_rest_dismiss' ) ) ?>'
                            }
                        })
                    });
                });
            </script>
        <?php }
    }
}

add_action( 'plugins_loaded', function (){
    /**
     * Plugin Releases and updates
     */
    $disable = isset( $_POST['wppusher'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( !$disable && is_admin() ){
        require_once( __DIR__ . '/admin/plugin-update-checker/plugin-update-checker.php' );


        if ( class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
            $hosted_json = 'https://raw.githubusercontent.com/DiscipleTools/disciple-tools-prayer-campaigns/master/version-control.json';

            PucFactory::buildUpdateChecker(
                $hosted_json,
                __FILE__,
                'disciple-tools-prayer-campaigns' // change this token
            );
        }
    }
});

/**
 * AJAX handler to store the state of dismissible notices.
 */
if ( ! function_exists( 'dt_hook_ajax_notice_handler' ) ){
    function dt_hook_ajax_notice_handler(){
        check_ajax_referer( 'wp_rest_dismiss', 'security' );
        if ( isset( $_POST['type'] ) ){
            $type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
            update_option( 'dismissed-' . $type, true );
        }
    }
}
