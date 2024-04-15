<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Adds the porch specific settings to the porch settings array
 */
class DT_Generic_Porch_Strings {

    private $defaults = [];

    public function __construct(){
//        add_filter( 'dt_campaign_porch_default_settings', [ $this, 'dt_campaign_porch_default_settings' ], 10, 1 );

        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
    }

    public function dt_campaign_porch_default_settings( $defaults ) {
        $defaults = $this->load_defaults();
        return array_merge( $defaults, $defaults );
    }

    private function load_defaults() {

//        $current_campaign = DT_Campaign_Landing_Settings::get_campaign();
//
//        $campaign_name = isset( $current_campaign['name'] ) ? $current_campaign['name'] : '';

        return [
//            'title' => [
//                'label' => 'Campaign/Site Title',
//                'value' => get_bloginfo( 'name' ),
//                'type' => 'text',
//                'translations' => [],
//                'tab' => 'translations',
//                'section' => DT_Generic_Porch_Translation_Sections::HERO,
//            ],
            'subtitle' => [
                'label' => 'Subtitle',
                'default' => __( 'Strategic prayer for a Disciple Making Movement', 'disciple-tools-prayer-campaigns' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::HERO,
            ],
            'vision_title' => [
                'label' => 'Vision Title',
                'default' => __( 'Our Vision', 'disciple-tools-prayer-campaigns' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::VISION,
            ],
            'vision' => [
                'label' => 'Vision',
                'default' => __( 'We want to cover this region with continuous 24/7 prayer.', 'disciple-tools-prayer-campaigns' ),
                'value' => '',
                'type' => 'textarea',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::VISION,
            ],
            'pray_section_icon' => [
                'label' => 'Prayer section Icon URL',
                'default' => DT_Generic_Porch::assets_dir() . 'img/pray.svg',
                'value' => '',
                'type' => 'icon',
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
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
                'type' => 'textarea',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
            ],
            'movement_section_icon' => [
                'label' => 'Movement section Icon URL',
                'default' => DT_Generic_Porch::assets_dir() . 'img/movement.svg',
                'value' => '',
                'type' => 'icon',
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
                'type' => 'textarea',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
            ],
            'time_section_icon' => [
                'label' => 'Time section Icon',
                'default' => DT_Generic_Porch::assets_dir() . 'img/24_7.svg',
                'value' => '',
                'type' => 'icon',
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
            ],
            'time_section_title' => [
                'label' => 'Time section Title',
                'default' => __( '24/7 Every Day', 'disciple-tools-prayer-campaigns' ),
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
                'type' => 'textarea',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::THREE,
            ],
            'what_content' => [
                'label' => 'Prayer Content Message next to calendar',
                'default' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
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
                'default' => __( 'Prayer Fuel', 'disciple-tools-prayer-campaigns' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::FUEL,
            ],
            'prayer_fuel_description' => [
                'label' => 'Prayer Fuel Description',
                'default' => __( 'Use these resources to help pray specifically each day.', 'disciple-tools-prayer-campaigns' ),
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
                'default' => __( 'All Days', 'disciple-tools-prayer-campaigns' ),
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::FUEL,
            ],
            'scheduled_prayer_fuel_title' => [
                'label' => 'Scheduled Prayer Fuel Title',
                'default' => __( 'Scheduled Prayer Fuel', 'disciple-tools-prayer-campaigns' ),
                'value' => '',
                'type' => 'text',
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::FUEL,
            ],
            'scheduled_prayer_fuel_description' => [
                'label' => 'Scheduled Prayer Fuel Description',
                'default' => __( 'All Scheduled Prayer Fuel in all available languages. Add and edit the prayer fuel below', 'disciple-tools-prayer-campaigns' ),
                'value' => '',
                'type' => 'text',
                'tab' => 'translations',
                'section' => DT_Generic_Porch_Translation_Sections::FUEL,
            ],
            'country_name' => [
                'label' => 'Location Name',
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'hidden' => true,
            ],
            'people_name' => [
                'label' => 'People Name',
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'hidden' => true,
            ],
            'campaign_name' => [
                'label' => 'Campaign Name',
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
                'hidden' => true,
            ],
            'footer_content' => [
                'label' => 'Extra Footer Content',
                'default' => '',
                'value' => '',
                'type' => 'textarea',
                'translations' => [],
                'tab' => 'translations',
            ]
        ];
    }

    public function dt_custom_fields_settings( $fields ){
        $string_fields = $this->load_defaults();

        $valid_types = [
            'text',
            'textarea',
            'icon'
        ];

        foreach ( $string_fields as $key => $field ){
            $fields[$key] = [
                'name' => $field['label'],
                'tile' => 'campaign_landing_strings',
                'type' => isset( $field['type'] ) && in_array( $field['type'], $valid_types ) ? $field['type'] : 'text',
                'default' => isset( $field['default'] ) ? $field['default'] : '',
                'description' => $field['placeholder'] ?? '',
                'campaign_tab' => isset( $field['tab'] ) ? $field['tab'] : 'translations',
                'campaign_section' => isset( $field['section'] ) ? $field['section'] : '',
                'hidden' => !empty( $field['hidden'] ),
            ];
            if ( $field['type'] === 'text' || $field['type'] === 'textarea' ){
                $fields[$key]['translations'] = [];
            }
        }

        return $fields;
    }
}

/**
 * Replacement for proper enum types in pre PHP8
 */
class DT_Generic_Porch_Translation_Sections {
    const HERO = 'Hero';
    const VISION = 'Vision';
    const THREE = 'Three Sections';
    const WHAT = 'What';
    const FUEL = 'Prayer Fuel';
}
