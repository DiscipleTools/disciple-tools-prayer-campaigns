<?php

/**
 * Adds the porch specific settings to the porch settings array
 */
class DT_Generic_Porch_Settings {

    private $defaults = [];

    public function __construct() {
        $this->load_defaults();
        add_filter( 'dt_campaign_porch_settings', [ $this, 'dt_prayer_campaigns_porch_settings' ], 10, 1 );
        add_filter( 'dt_campaign_porch_theme_options', [ $this, 'dt_generic_porch_themes' ], 10, 1 );
    }

    public function dt_prayer_campaigns_porch_settings( $settings ) {
        return array_merge( $this->defaults, $settings );
    }

    public function dt_generic_porch_themes( $theme_options ) {
        $theme_options['pink'] = [
            'color' => '#FF55AA',
        ];

        return $theme_options;
    }

    private function load_defaults() {

        $current_campaign = DT_Campaign_Settings::get_campaign();

        $campaign_name = isset( $current_campaign["name"] ) ? $current_campaign["name"] : "";

        $this->defaults = [
            'campaign_name' => [
                'label' => 'Campaign Name',
                'value' => $campaign_name,
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
            ],
            'title' => [
                'label' => 'Campaign/Site Title',
                'value' => get_bloginfo( 'name' ),
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
            ],
            'subtitle' => [
                'label' => 'Subtitle',
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
            ],
            'goal' => [
                'label' => 'Goal',
                'default' => __( "We want to cover the country of %s with continuous 24/7 prayer.", 'dt-campaign-generic-porch' ),
                'value' => "",
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
            ],
            'what_content' => [
                'label' => 'What is 24/7 Prayer Content',
                'value' => '',
                'type' => 'textarea',
                'translations' => [],
                'tab' => 'translations',
            ],
            'prayer_fuel_description' => [
                'label' => 'Prayer Fuel Description',
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
            ],
            'country_name' => [
                'label' => 'Location Name',
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
            ],
            'people_name' => [
                'label' => 'People Name',
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
            ],
        ];
    }
}
