<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Manages the selection of a porch
 */
class DT_Porch_Selector {

    private static $instance;
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action( 'after_setup_theme', [ $this, 'dt_after_all_porches_have_loaded' ], 150 );
    }

    public function load_selected_porch() {
        if ( $this->has_selected_porch() ) {
            $this->get_selected_porch_loader()->load_porch();
        }
    }

    public function has_selected_porch() {
        //all campaigns have a landing page now.
        return true;
    }

    public function dt_after_all_porches_have_loaded() {

        $plugin_dir = DT_Prayer_Campaigns::get_dir_path();
        if ( $this->has_selected_porch() ) {
            require_once trailingslashit( $plugin_dir ) . 'porches/prayer-fuel-post-type.php';
        }
        $this->load_selected_porch();
    }

    public function porch_exists( $porch_id ) {
        $porches = $this->get_porch_loaders();

        return array_key_exists( $porch_id, $porches );
    }

    public function get_porch_loaders() {
        return apply_filters( 'dt_register_prayer_campaign_porch', [] );
    }

    public function get_selected_porch_id() {
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
        if ( isset( $campaign['default_language'] ) && $campaign['default_language'] !== 'en_US' ){
            $lang = dt_campaign_get_current_lang( '' );
            if ( empty( $lang ) ){
                dt_campaign_add_lang_to_cookie( $campaign['default_language'] );
                dt_campaign_set_translation( $campaign['default_language'] );
                DT_Posts::get_post_field_settings( 'campaigns', false );
            }
        }
        $porches = $this->get_porch_loaders();
        if ( isset( $campaign['porch_type']['key'] ) && array_key_exists( $campaign['porch_type']['key'], $porches ) ){
            return $campaign['porch_type']['key'];
        } else {
            return 'generic-porch';
        }
    }

    /**
     * @return IDT_Porch_Loader
     */
    public function get_selected_porch_loader() {
        $porches = $this->get_porch_loaders();

        $selected_porch_id = $this->get_selected_porch_id();

        return $porches[$selected_porch_id]['class'];
    }
}
