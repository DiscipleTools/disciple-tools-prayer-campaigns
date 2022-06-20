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
        $this->defaults = [
            /* specific */
            'country_name' => [
                'label' => 'Location Name',
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
            ],
            /* specific */
            'people_name' => [
                'label' => 'People Name',
                'value' => '',
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
            ],
            /* translations */
            'title' => [
                'label' => 'Campaign/Site Title',
                'value' => get_bloginfo( 'name' ),
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
            ],
            /* translations */
            'goal' => [
                'label' => 'Goal',
                'default' => __( "We want to cover the country of %s with continuous 24/7 prayer during the entire 30 days of Ramadan.", 'dt-campaign-generic-porch' ),
                'value' => "",
                'type' => 'text',
                'translations' => [],
                'tab' => 'translations',
            ],
            /* translations */
            'what_content' => [
                'label' => 'What is Ramadan Content',
                'default' =>__( 'Ramadan is one of the five requirements (or pillars) of Islam. During each of its 30 days, Muslims are obligated to fast from dawn until sunset. During this time they are supposed to abstain from food, drinking liquids, smoking, and sexual relations.
    In %1$s, women typically spend the afternoons preparing a big meal. At sunset, families often gather to break the fast. Traditionally the families break the fast with a drink of water, then three dried date fruits, and a multi-course meal. After watching the new Ramadan TV series, men (and some women) go out to coffee shops where they drink coffee, and smoke with friends until late into the night.
    Though many %2$s have stopped fasting in recent years, and lots of %2$s are turned off by the hypocrisy, increased crime rates, and rudeness that is pervasive through the month, lots of %2$s become more serious about religion during this time. Many attend the evening prayer services and do the other ritual prayers. Some even read the entire Quran (about a tenth the length of the Bible). This sincere seeking makes it a strategic time for us to pray for them.', 'dt-campaign-generic-porch' ),
                'value' => '',
                'type' => 'textarea',
                'translations' => [],
                'tab' => 'translations',
            ],
        ];
    }
}
