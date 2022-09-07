<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Manages settings for each campaign by it's id rather than all campaigns
 */
class DT_Campaign_Specific_Settings {

    private DT_Campaign_Settings $settings_manager;

    public function __construct() {
        $this->settings_manager = new DT_Campaign_Settings();
    }

    /**
     * Gets all of the settings for the campaign $campaign_id
     *
     * @param int $campaign_id
     *
     * @return array
     */
    private function get_all( string $campaign_id ): array {
        $campaign_key = $this->campaign_key( $campaign_id );
        $serialized_settings = $this->settings_manager->get( $campaign_key );
        $campaign_settings = unserialize( $serialized_settings );

        return $campaign_settings;
    }

    /**
     * Get a setting by name from the campaign's settings
     *
     * @param int $campaign_id
     * @param string $name
     *
     * @return mixed
     */
    public function get( int $campaign_id, string $name ): string {
        $campaign_settings = $this->get_all( $campaign_id );

        if ( isset( $campaign_settings[$name] ) ) {
            return $campaign_settings[$name];
        }

        return null;
    }

    /**
     * Update a setting
     *
     * @param int $campaign_id
     * @param string $name
     * @param mixed $value
     */
    public function update( int $campaign_id, string $name, mixed $value ) {
        $campaign_settings = $this->get_all( $campaign_id );
        $campaign_key = $this->campaign_key( $campaign_id );

        if ( !$value || empty( $value ) ) {
            unset( $campaign_settings[$name] );
        } else {
            $campaign_settings[$name] = $value;
        }

        $serialized_settings = serialize( $campaign_settings );

        $this->settings_manager->update( $campaign_key, $serialized_settings );
    }

    /**
     * Creates a settings key with the $campaign_id
     *
     * @param string $campaign_id
     *
     * @return string
     */
    private function campaign_key( int $campaign_id ) {
        return '__campaign_setting_' . strval( $campaign_id );
    }
}
