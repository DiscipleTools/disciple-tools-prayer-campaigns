<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Manages the selection of a porch
 */
class DT_Porch_Selector {

    public $selected_porch_id;

    private static $instance;
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->selected_porch_id = ( new DT_Campaign_Settings() )->get( 'selected_porch' );
    }

    public function load_selected_porch() {
        if ( $this->has_selected_porch() ) {
            $this->get_selected_porch_loader()->load_porch();
        }
    }

    public function has_selected_porch() {
        return $this->selected_porch_id && !empty( $this->selected_porch_id ) && $this->porch_exists( $this->selected_porch_id );
    }

    public function porch_exists( $porch_id ) {
        $porches = $this->get_porch_loaders();

        return array_key_exists( $porch_id, $porches );
    }

    public function get_porch_loaders() {
        return apply_filters( 'dt_register_prayer_campaign_porch', [] );
    }

    public function get_selected_porch_id() {
        return $this->selected_porch_id;
    }

    public function set_selected_porch_id( $new_selected_porch_id ) {
        $this->selected_porch_id = $new_selected_porch_id;
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
