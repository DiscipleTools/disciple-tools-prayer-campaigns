<?php
/**
 *Plugin Name: Disciple.Tools - Prayer Campaigns
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools-prayer-campaigns
 * Description: Add a prayer subscriptions module to Disciple.Tools that allows for non-users to subscribe to pray for specific locations at specific times, supporting both time and geographic prayer saturation for your project.
 * Text Domain: disciple-tools-prayer-campaigns
 * Domain Path: /languages
 * Version:  1.9.0
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

/**
 * Gets the instance of the `DT_Prayer_Campaigns` class.
 *
 * @since  0.1
 * @access public
 * @return object|bool
 */
function dt_prayer_campaigns() {
    $dt_prayer_campaigns_required_dt_theme_version = '1.8.1';
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;

    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = class_exists( "Disciple_Tools" );
    if ( $is_theme_dt && version_compare( $version, $dt_prayer_campaigns_required_dt_theme_version, "<" ) ) {
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
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class DT_Prayer_Campaigns {

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private $selected_campaign;

    private function __construct() {

        require_once( 'campaign-functions/time-utilities.php' );
        require_once( 'campaign-functions/send-email-utility.php' );
        require_once( 'campaign-functions/cron-schedule.php' );

        require_once( 'post-type/loader.php' );

        require_once( 'porches/loader.php' );

        require_once( 'magic-links/24hour/24hour.php' );
        require_once( 'magic-links/ongoing/ongoing.php' );
        require_once( 'magic-links/campaign-resend-email/magic-link-post-type.php' );

        if ( is_admin() ) {
            require_once __DIR__ . '/admin/dt-prayer-campaigns.php';
            require_once __DIR__ . '/admin/admin-menu-and-tabs.php'; // adds starter admin page and section for plugin
        }

        $this->i18n();

        /**
         * Plugin Releases and updates
         */
        if ( is_admin() ){
            if ( ! class_exists( 'Puc_v4_Factory' ) ) {
                require( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
            }

            $hosted_json = "https://raw.githubusercontent.com/DiscipleTools/disciple-tools-prayer-campaigns/master/version-control.json";

            Puc_v4_Factory::buildUpdateChecker(
                $hosted_json,
                __FILE__,
                'disciple-tools-prayer-campaigns' // change this token
            );
        }

        if ( is_admin() ) { // adds links to the plugin description area in the plugin admin list.
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 );
        }

        try {
            require_once( plugin_dir_path( __FILE__ ) . '/admin/class-migration-engine.php' );
            DT_Prayer_Campaigns_Migration_Engine::migrate( DT_Prayer_Campaigns_Migration_Engine::$migration_number );
        } catch ( Throwable $e ) {
            new WP_Error( 'migration_error', 'Migration engine failed to migrate.' );
        }
        DT_Prayer_Campaigns_Migration_Engine::display_migration_and_lock();
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

    public function get_campaign() {

        $this->selected_campaign = get_option( 'pray4ramadan_selected_campaign', false );

        if ( empty( $this->selected_campaign ) ) {
            return [];
        }

        $campaign = DT_Posts::get_post( 'campaigns', (int) $this->selected_campaign, true, false );
        if ( is_wp_error( $campaign ) ) {
            return [];
        }

        return $campaign;
    }

    /**
     * This function needs some work to be split into:
     *
     * prayer campaign specific stuff, like social links, etc.
     * porch specific stuff
     * translation stuff
     *
     * It might need another home as well
     */
    public function porch_fields() {
        $defaults = [
            'theme_color' => [
                'label' => 'Theme Color',
                'value' => 'preset',
                'type' => 'theme_select',
            ],
            'custom_theme_color' => [
                'label' => 'Custom Theme Color',
                'value' => '',
                'type' => 'text'
            ],
            'country_name' => [
                'label' => 'Location Name',
                'value' => '',
                'type' => 'text',
                'translations' => [],
            ],
            'people_name' => [
                'label' => 'People Name',
                'value' => '',
                'type' => 'text',
                'translations' => [],
            ],
            'title' => [
                'label' => 'Campaign/Site Title',
                'value' => get_bloginfo( 'name' ),
                'type' => 'text',
                'translations' => [],
            ],
            'logo_url' => [
                'label' => 'Logo Image URL',
                'value' => '',
                'type' => 'text',
            ],
            'logo_link_url' => [
                'label' => 'Logo Link to URL',
                'value' => '',
                'type' => 'text',
            ],
            'header_background_url' => [
                'label' => 'Header Background URL',
                'value' => trailingslashit( plugin_dir_url( __FILE__ ) ) . 'site/img/stencil-header.png',
                'type' => 'text',
            ],
            'what_image' => [
                'label' => 'What is Ramadan Image',
                'value' => '',
                'type' => 'text',
                'enabled' => false
            ],
            'show_prayer_timer' => [
                'label' => 'Show Prayer Timer',
                'default' => 'Yes',
                'value' => 'yes',
                'type' => 'prayer_timer_toggle',
            ],
            "email" => [
                "label" => "Address to send sign-up and notification emails from",
                "value" => "no-reply@" . parse_url( home_url() )["host"],
                'type' => 'text'
            ],
            'facebook' => [
                'label' => 'Facebook Url',
                'value' => '',
                'type' => 'text',
            ],
            'instagram' => [
                'label' => 'Instagram Url',
                'value' => '',
                'type' => 'text',
            ],
            'twitter' => [
                'label' => 'Twitter Url',
                'value' => '',
                'type' => 'text',
            ],

            //strings
            'what_content' => [
                'label' => 'What is Ramadan Content',
                'default' =>__( 'Ramadan is one of the five requirements (or pillars) of Islam. During each of its 30 days, Muslims are obligated to fast from dawn until sunset. During this time they are supposed to abstain from food, drinking liquids, smoking, and sexual relations.

 In %1$s, women typically spend the afternoons preparing a big meal. At sunset, families often gather to break the fast. Traditionally the families break the fast with a drink of water, then three dried date fruits, and a multi-course meal. After watching the new Ramadan TV series, men (and some women) go out to coffee shops where they drink coffee, and smoke with friends until late into the night.

 Though many %2$s have stopped fasting in recent years, and lots of %2$s are turned off by the hypocrisy, increased crime rates, and rudeness that is pervasive through the month, lots of %2$s become more serious about religion during this time. Many attend the evening prayer services and do the other ritual prayers. Some even read the entire Quran (about a tenth the length of the Bible). This sincere seeking makes it a strategic time for us to pray for them.', 'pray4ramadan-porch' ),
                'value' => '',
                'type' => 'textarea',
                'translations' => [],
            ],
            'goal' => [
                'label' => 'Goal',
                'default' => __( "We want to cover the country of %s with continuous 24/7 prayer during the entire 30 days of Ramadan.", 'pray4ramadan-porch' ),
                'value' => "",
                'type' => 'text',
                'translations' => [],
            ],
            'google_analytics' => [
                'label' => 'Google Analytics',
                'default' => get_site_option( "p4r_porch_google_analytics" ),
                'value' => '',
                'type' => 'textarea',
            ],
            'default_language' => [
                'label' => 'Default Language',
                'default' => 'en_US',
                'value' => '',
                'type' => 'default_language_select',
            ],
            'power' => [
                'label' => "Night of power",
                'type' => "array",
                'value' => [
                    "start" => "",
                    "start_time" => 0,
                    "end" => "",
                    "end_time" => 100000,
                ],
            ],
            'stats-p4m' => [
                'label' => 'Show Pray4Movement sign-up on ' . esc_html( home_url( '/prayer/stats' ) ),
                'default' => 'Yes',
                'value' => 'yes',
                'type' => 'prayer_timer_toggle',
            ]
        ];

        $defaults = apply_filters( 'p4r_porch_fields', $defaults );

        $saved_fields = get_option( 'p4r_porch_fields', [] );

        $lang = dt_ramadan_get_current_lang();
        $people_name = get_field_translation( $saved_fields["people_name"] ?? [], $lang );
        $country_name = get_field_translation( $saved_fields["country_name"] ?? [], $lang );

        $defaults["goal"]["default"] = sprintf( $defaults["goal"]["default"], !empty( $country_name ) ? $country_name : "COUNTRY" );

        $defaults["what_content"]["default"] = sprintf(
            $defaults["what_content"]["default"],
            !empty( $country_name ) ? $country_name : "COUNTRY",
            !empty( $people_name ) ? $people_name : "PEOPLE"
        );

        if ( !isset( $saved_fields["power"] ) ){
            $selected_campaign = get_option( 'pray4ramadan_selected_campaign', false );
            $start = get_post_meta( $selected_campaign, 'start_date', true );
            $end = get_post_meta( $selected_campaign, 'end_date', true );
            if ( !empty( $selected_campaign ) && !empty( $start ) && !empty( $end ) ){
                $saved_fields["power"] = $defaults["power"];
                $saved_fields["power"]["value"] = [
                    "start" => dt_format_date( $start + DAY_IN_SECONDS * 26 ),
                    "start_time" => 19 * HOUR_IN_SECONDS,
                    "end" => dt_format_date( $start + DAY_IN_SECONDS * 27 ),
                    "end_time" => (int) ( 4.5 * HOUR_IN_SECONDS ),
                    "enabled" => true
                ];
                update_option( 'p4r_porch_fields', $saved_fields );
            }
        }

        return $this->recursive_parse_args( $saved_fields, $defaults );
    }

    /**
     * This function may be better off somewhere else, but putting it here for now
     */
    public function recursive_parse_args( $args, $defaults ) {
        $new_args = (array) $defaults;

        foreach ( $args ?: [] as $key => $value ) {
            if ( is_array( $value ) && isset( $new_args[ $key ] ) ) {
                $new_args[ $key ] = $this->recursive_parse_args( $value, $new_args[ $key ] );
            }
            elseif ( $key !== "default" ){
                $new_args[ $key ] = $value;
            }
        }

        return $new_args;
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
        _doing_it_wrong( "dt_prayer_campaigns::" . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
}


// Register activation hook.
register_activation_hook( __FILE__, [ 'DT_Prayer_Campaigns', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'DT_Prayer_Campaigns', 'deactivation' ] );


if ( ! function_exists( 'dt_prayer_campaigns_hook_admin_notice' ) ) {
    function dt_prayer_campaigns_hook_admin_notice() {
        global $dt_prayer_campaigns_required_dt_theme_version;
        $wp_theme = wp_get_theme();
        $current_version = $wp_theme->version;
        $message = "'Disciple.Tools - Subscriptions' plugin requires 'Disciple.Tools' theme to work. Please activate 'Disciple.Tools' theme or make sure it is latest version.";
        if ( $wp_theme->get_template() === "disciple-tools-theme" ){
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

/**
 * AJAX handler to store the state of dismissible notices.
 */
if ( ! function_exists( "dt_hook_ajax_notice_handler" ) ){
    function dt_hook_ajax_notice_handler(){
        check_ajax_referer( 'wp_rest_dismiss', 'security' );
        if ( isset( $_POST["type"] ) ){
            $type = sanitize_text_field( wp_unslash( $_POST["type"] ) );
            update_option( 'dismissed-' . $type, true );
        }
    }
}

