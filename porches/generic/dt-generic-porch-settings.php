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
                'section' => DT_Generic_Porch_Translation_Sections::HERO,
            ],
            'subtitle' => [
                'label' => 'Subtitle',
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::HERO,
            ],
            'vision_title_plain' => [
                'label' => 'Vision Title (uncolored part)',
                'default' => __( "Our", 'dt-campaign-generic-porch' ),
                'value' => "",
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::VISION,
            ],
            'vision_title_colored' => [
                'label' => 'Vision Title (colored part)',
                'default' => __( "Vision", 'dt-campaign-generic-porch' ),
                'value' => "",
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::VISION,
            ],
            'vision' => [
                'label' => 'Vision',
                'default' => __( "We want to cover the country of %s with continuous 24/7 prayer.", 'dt-campaign-generic-porch' ),
                'value' => "",
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::VISION,
            ],
            'pray_section_title' => [
                'label' => 'Prayer section Title',
                'default' => __( 'Extraordinary Prayer', 'dt-campaign-generic-porch' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
            ],
            'pray_section_text' => [
                'label' => 'Prayer section Text',
                'default' => __( 'Every disciple making movement in history has happened in the context of extraordinary prayer.', 'dt-campaign-generic-porch' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
            ],
            'movement_section_title' => [
                'label' => 'Movement section Title',
                'default' => __( 'Movement Focused', 'dt-campaign-generic-porch' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
            ],
            'movement_section_text' => [
                'label' => 'Movement section Text',
                'default' => __( 'Join us in asking, seeking, and knocking for streams of disciples and churches to be made.', 'dt-campaign-generic-porch' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
            ],
            'time_section_title' => [
                'label' => 'Time section Title',
                'default' => __( '24/7 for 30 Days', 'dt-campaign-generic-porch' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
            ],
            'time_section_text' => [
                'label' => 'Time section Text',
                'default' => __( 'Choose a 15-minute (or more!) time slot that you can pray during each day. Invite someone else to sign up too.', 'dt-campaign-generic-porch' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
            ],
            'what_content' => [
                'label' => 'What is 24/7 Prayer Content',
                'value' => '',
                'type' => 'textarea',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::WHAT,
            ],
            'prayer_fuel_title_plain' => [
                'label' => 'Prayer Fuel Title (uncolored part)',
                'default' => __( '15-Minute', 'dt-campaign-generic-porch' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::FUEL,
            ],
            'prayer_fuel_title_colored' => [
                'label' => 'Prayer Fuel Title (colored part)',
                'default' => __( 'Prayer Fuel', 'dt-campaign-generic-porch' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::FUEL,
            ],
            'prayer_fuel_description' => [
                'label' => 'Prayer Fuel Description',
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::FUEL,
            ],
            'todays_fuel_title' => [
                'label' => 'Todays Prayer Fuel Title',
                'default' => __( "Today's Prayer Fuel", 'dt-campaign-generic-porch' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::FUEL,
            ],
            'all_fuel_title' => [
                'label' => 'All Prayer Fuel Title',
                'default' => __( "All Days", 'dt-campaign-generic-porch' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::FUEL,
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

/**
 * Replacement for proper enum types in pre PHP8
 */
class DT_Generic_Porch_Translation_Sections {
    public const HERO = "Hero";
    public const VISION = "Vision";
    public const THREE = "Three Sections";
    public const WHAT = "What";
    public const FUEL = "Prayer Fuel";
}
