<?php

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Encapsulates getting and setting of campaign settings
 */
class DT_Campaign_Landing_Settings {

    public function __construct(){
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
    }

    public static function get_landing_root_url(){
        if ( defined( 'LANDING_URL' ) ){
            return site_url( LANDING_URL );
        } else {
            return site_url();
        }
    }

    public static function get_landing_page_url( $campaign_id ){
        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id );
        $url = $campaign['campaign_url'];
        if ( empty( $url ) ){
            $url = str_replace( ' ', '-', strtolower( trim( $campaign['name'] ) ) );
            DT_Posts::update_post( 'campaigns', $campaign_id, [
                'campaign_url' => $url,
            ] );
        }
        return site_url( $url );
    }

    public function dt_details_additional_tiles( $tiles, $post_type ){
        if ( $post_type !== 'campaigns' ){
            return $tiles;
        }

        $tiles['campaign_landing'] = [
            'label' => __( 'Landing Page Settings', 'disciple-tools-prayer-campaigns' ),
        ];

        return $tiles;
    }



    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type !== 'campaigns' ){
            return $fields;
        }

        /**
         * Email settings
         */
        $fields['from_email'] = [
            'name' => __( 'From Email', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'description' => __( 'The email address that will be used as the "from" address for emails sent from this campaign.', 'disciple-tools-prayer-campaigns' ),
            'tile' => 'campaign_landing',
        ];
        $fields['from_name'] = [
            'name' => __( 'From Name', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'description' => __( 'The name that will be used as the "from" name for emails sent from this campaign.', 'disciple-tools-prayer-campaigns' ),
            'tile' => 'campaign_landing',
        ];
        $fields['reply_to_email'] = [
            'name' => __( 'Reply To Email', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'description' => __( 'The email address that will be used as the "reply to" address for emails sent from this campaign.', 'disciple-tools-prayer-campaigns' ),
            'tile' => 'campaign_landing',
        ];
        $fields['email_logo'] = [
            'name' => __( 'Email Logo', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'tile' => 'campaign_landing',
            'description' => __( 'The logo that will be used in emails sent from this campaign.', 'disciple-tools-prayer-campaigns' ),
        ];
        //reminder_content_disable_fuel
        $fields['reminder_content_disable_fuel'] = [
            'name' => __( 'Disable Prayer Fuel', 'disciple-tools-prayer-campaigns' ),
            'type' => 'boolean',
            'tile' => 'campaign_landing',
            'description' => __( 'Whether or not to disable prayer fuel emails.', 'disciple-tools-prayer-campaigns' ),
        ];


        /**
         * Landing page settings
         */
        $fields['theme_color'] = [
            'name' => __( 'Theme Color', 'disciple-tools-prayer-campaigns' ),
            'type' => 'key_select',
            'tile' => 'campaign_landing',
            'description' => __( 'The color that will be used as the theme color for the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
            'default' => [
                'preset' => [ 'label' => __( 'Preset', 'disciple-tools-prayer-campaigns' ) ],
                'forestgreen' => [ 'label' => __( 'Forest Green', 'disciple-tools-prayer-campaigns' ) ],
                'green' => [ 'label' => __( 'Green', 'disciple-tools-prayer-campaigns' ) ],
            ]
        ];
        $fields['custom_theme_color'] = [
            'name' => __( 'Custom Theme Color', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'tile' => 'campaign_landing',
            'description' => __( 'The color that will be used as the custom theme color for the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
        ];
        $fields['logo_url'] = [
            'name' => __( 'Logo URL', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'tile' => 'campaign_landing',
            'description' => __( 'The logo that will be used as the logo for the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
        ];
        $fields['logo_link_url'] = [
            'name' => __( 'Logo Link URL', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'tile' => 'campaign_landing',
            'description' => __( 'The URL that will be used as the link for the logo on the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
        ];
        //header_background_url
        $fields['header_background_url'] = [
            'name' => __( 'Header Background URL', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'tile' => 'campaign_landing',
            'description' => __( 'The URL that will be used as the background image for the header on the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
        ];
        //what_image
        $fields['what_image'] = [
            'name' => __( 'What Image', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'tile' => 'campaign_landing',
            'description' => __( 'The URL that will be used as the image for the "What is a Prayer Campaign?" section on the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
        ];
        $languages_manager = new DT_Campaign_Languages();
        $langs = $languages_manager->get_enabled_languages();
        $lang_options = [];
        foreach ( $langs as $lang_code => $lang ){
            $lang_options[$lang_code] = [
                'label' => $lang['label'],
            ];
        }
        //default_language
        $fields['default_language'] = [
            'name' => __( 'Default Language', 'disciple-tools-prayer-campaigns' ),
            'type' => 'key_select',
            'tile' => 'campaign_landing',
            'description' => __( 'The default language that will be used for the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
            'default' => $lang_options,
        ];
        //facebook
        $fields['facebook'] = [
            'name' => __( 'Facebook', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'tile' => 'campaign_landing',
            'description' => __( 'The URL that will be used as the Facebook link on the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
        ];
        //instagram
        $fields['instagram'] = [
            'name' => __( 'Instagram', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'tile' => 'campaign_landing',
            'description' => __( 'The URL that will be used as the Instagram link on the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
        ];

        /**
         * Prayer fuel settings
         */
        //show_prayer_timer
        $fields['show_prayer_timer'] = [
            'name' => __( 'Show Prayer Timer', 'disciple-tools-prayer-campaigns' ),
            'type' => 'checkbox',
            'tile' => 'campaign_landing',
            'description' => __( 'Whether or not to show the prayer timer on the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
        ];
        //prayer_fuel_frequency
        $fields['prayer_fuel_frequency'] = [
            'name' => __( 'Prayer Fuel Frequency', 'disciple-tools-prayer-campaigns' ),
            'type' => 'key_select',
            'tile' => 'campaign_landing',
            'default' => [
                'daily' => [ 'label' => __( 'Daily', 'disciple-tools-prayer-campaigns' ) ],
                'weekly' => [ 'label' => __( 'Weekly', 'disciple-tools-prayer-campaigns' ) ],
                'monthly' => [ 'label' => __( 'Monthly', 'disciple-tools-prayer-campaigns' ) ],
            ],
            'description' => __( 'The frequency that prayer fuel emails will be sent to participants.', 'disciple-tools-prayer-campaigns' ),
        ];


        return $fields;

    }

}
new DT_Campaign_Landing_Settings();