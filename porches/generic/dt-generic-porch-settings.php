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
            'vision_title' => [
                'label' => 'Vision Title',
                'default' => __( "Our Vision", 'disciple-tools-prayer-campaigns' ),
                'value' => "",
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::VISION,
            ],
            'vision' => [
                'label' => 'Vision',
                'default' => __( "We want to cover these 30 days with continuous 24/7 prayer.", 'disciple-tools-prayer-campaigns' ),
                'value' => "",
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::VISION,
            ],
            'pray_section_title' => [
                'label' => 'Prayer section Title',
                'default' => __( 'Extraordinary Prayer', 'disciple-tools-prayer-campaigns' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
            ],
            'pray_section_text' => [
                'label' => 'Prayer section Text',
                'default' => __( 'Every disciple making movement in history has happened in the context of extraordinary prayer.', 'disciple-tools-prayer-campaigns' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
            ],
            'movement_section_title' => [
                'label' => 'Movement section Title',
                'default' => __( 'Movement Focused', 'disciple-tools-prayer-campaigns' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
            ],
            'movement_section_text' => [
                'label' => 'Movement section Text',
                'default' => __( 'Join us in asking, seeking, and knocking for streams of disciples and churches to be made.', 'disciple-tools-prayer-campaigns' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
            ],
            'time_section_title' => [
                'label' => 'Time section Title',
                'default' => __( '24/7 for 30 Days', 'disciple-tools-prayer-campaigns' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
            ],
            'time_section_text' => [
                'label' => 'Time section Text',
                'default' => __( 'Choose a 15-minute (or more!) time slot that you can pray during each day. Invite someone else to sign up too.', 'disciple-tools-prayer-campaigns' ),
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
            'prayer_fuel_name' => [
                'label' => 'Prayer Fuel Name',
                'default' => __( 'Prayer Fuel', 'disciple-tools-prayer-campaigns' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::FUEL,
            ],
            'prayer_fuel_title' => [
                'label' => 'Prayer Fuel Title',
                'default' => __( '15-Minute Prayer Fuel', 'disciple-tools-prayer-campaigns' ),
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
                'default' => __( "Today's Prayer Fuel", 'disciple-tools-prayer-campaigns' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::FUEL,
            ],
            'all_fuel_title' => [
                'label' => 'All Prayer Fuel Title',
                'default' => __( "All Days", 'disciple-tools-prayer-campaigns' ),
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
    const HERO = "Hero";
    const VISION = "Vision";
    const THREE = "Three Sections";
    const WHAT = "What";
    const FUEL = "Prayer Fuel";
}
