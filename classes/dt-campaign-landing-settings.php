<?php

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Encapsulates getting and setting of campaign settings
 */
class DT_Campaign_Landing_Settings {

    public function __construct(){
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );

        add_filter( 'dt_campaign_porch_theme_options', [ $this, 'dt_generic_porch_themes' ], 10, 1 );
    }

    public function dt_generic_porch_themes( $theme_options ) {
        $theme_options['pink'] = [
            'color' => '#FF55AA',
        ];

        return $theme_options;
    }

    public static function determine_campaign_via_url( $expected_pages = [] ){
        $url = dt_get_url_path( true );
        $url_parts = explode( '/', $url );
        $current_page = '';
        if ( empty( $url_parts[0] ) || in_array( $url_parts[0], $expected_pages, true ) ){
            $selected_campaign_id = get_option( 'dt_campaign_selected_campaign', false );
            if ( empty( $selected_campaign_id ) ) {
                return false;
            }
            if ( !defined( 'CAMPAIGN_ID' ) ){
                define( 'CAMPAIGN_ID', $selected_campaign_id );
            }
            if ( !defined( 'LANDING_URL' ) ){
                define( 'LANDING_URL', '' );
            }
            $current_page = $url_parts[0];
        } else if ( !isset( $url_parts[1] ) || in_array( $url_parts[1], $expected_pages, true ) ){
            //find post by name
            global $wpdb;
            $selected_campaign_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'campaign_url' AND meta_value = %s", $url_parts[0] ) );
            if ( empty( $selected_campaign_id ) ) {
                return;
            }
            if ( !defined( 'CAMPAIGN_ID' ) ){
                define( 'CAMPAIGN_ID', $selected_campaign_id );
            }
            if ( !defined( 'LANDING_URL' ) ){
                define( 'LANDING_URL', $url_parts[0] );
            }
            $root = $url_parts[0];
            $current_page = isset( $url_parts[1] ) ? $url_parts[1] : '';
        } else {
            return false;
        }
        return [
            'current_page' => $current_page,
            'root' => $root ?? '',
            'campaign_id' => $selected_campaign_id,
        ];
    }

    public static function get_landing_root_url(){
        if ( defined( 'LANDING_URL' ) ){
            return site_url( LANDING_URL );
        } else {
            return site_url();
        }
    }

    public static function get_landing_page_url( $campaign_id = null ){
        $campaign = self::get_campaign( $campaign_id );
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
            'hidden' => true,
        ];
        $tiles['campaign_email'] = [
            'label' => __( 'Email Settings', 'disciple-tools-prayer-campaigns' ),
            'hidden' => true,
        ];
        $tiles['campaign_prayer_fuel'] = [
            'hidden' => true,
            'label' => __( 'Prayer Fuel Settings', 'disciple-tools-prayer-campaigns' ),
        ];
        $tiles['campaign_landing_strings'] = [
            'hidden' => true,
            'label' => __( 'Landing Page Strings', 'disciple-tools-prayer-campaigns' ),
        ];


        return $tiles;
    }



    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type !== 'campaigns' ){
            return $fields;
        }
        $sections = [
            'settings' => 'Sign Up and Notification Email Settings',
            'email_content' => 'Email Content',
        ];


        /**
         * Email settings
         */
        $fields['from_email'] = [
            'name' => __( 'The email address campaign emails will be sent from:', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'description' => __( 'The email address that will be used as the "from" address for emails sent from this campaign.', 'disciple-tools-prayer-campaigns' ),
            'tile' => 'campaign_email',
            'campaign_section' => $sections['settings'],
            'default' => DT_Prayer_Campaigns_Send_Email::default_email_address(),
        ];
        $fields['from_name'] = [
            'name' => __( 'From Name', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'description' => __( 'The name that will be used as the "from" name for emails sent from this campaign.', 'disciple-tools-prayer-campaigns' ),
            'tile' => 'campaign_email',
            'campaign_section' => $sections['settings'],
            'default' => ''
        ];
        $fields['reply_to_email'] = [
            'name' => __( 'Reply To Email', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'description' => __( 'The email address that will be used as the "reply to" address for emails sent from this campaign.', 'disciple-tools-prayer-campaigns' ),
            'tile' => 'campaign_email',
            'campaign_section' => $sections['settings'],
        ];
        $fields['email_logo'] = [
            'name' => __( 'Email Logo', 'disciple-tools-prayer-campaigns' ),
            'type' => 'icon',
            'tile' => 'campaign_email',
            'description' => __( 'The logo that will be used in emails sent from this campaign.', 'disciple-tools-prayer-campaigns' ),
            'campaign_section' => $sections['settings'],
            'default' => Campaigns_Email_Template::get_email_logo_url()
        ];
        //reminder_content_disable_fuel
        $fields['reminder_content_disable_fuel'] = [
            'name' => __( 'Disable Prayer Email Fuel Link', 'disciple-tools-prayer-campaigns' ),
            'type' => 'key_select',
            'default' => [
                'yes' => [ 'label' => __( 'Yes', 'disciple-tools-prayer-campaigns' ) ],
                'no' => [ 'label' => __( 'No', 'disciple-tools-prayer-campaigns' ) ],
            ],
            'tile' => 'campaign_email',
            'description' => __( 'Whether or not to disable prayer fuel emails.', 'disciple-tools-prayer-campaigns' ),
            'campaign_section' => $sections['settings'],
        ];

        //reminder_content
        $fields['reminder_content'] = [
            'name' => __( 'Reminder Email Content', 'disciple-tools-prayer-campaigns' ),
            'type' => 'textarea',
            'tile' => 'campaign_email',
            'description' => __( 'The content that will be used in the reminder email.', 'disciple-tools-prayer-campaigns' ),
            'default' => '',
            'translations' => [],
            'campaign_section' => 'Email Content',
        ];
        //signup content
        $fields['signup_content'] = [
            'name' => __( 'Signup Email Content', 'disciple-tools-prayer-campaigns' ),
            'type' => 'textarea',
            'tile' => 'campaign_email',
            'description' => __( 'The content that will be used in the signup email.', 'disciple-tools-prayer-campaigns' ),
            'default' => '',
            'translations' => [],
            'campaign_section' => 'Email Content',
        ];


        /**
         * Landing page settings
         */
        $theme_manager = new DT_Porch_Theme();
        $available_themes = $theme_manager->get_available_theme_names( DT_Prayer_Campaigns::get_dir_path() . 'porches/generic/site/css/colors' );
        $themes = [];
        foreach ( $available_themes as $theme_key => $theme ){
            $themes[$theme_key] = [
                'label' => $theme,
            ];
        }
        $fields['theme_color'] = [
            'name' => __( 'Theme Color', 'disciple-tools-prayer-campaigns' ),
            'type' => 'key_select',
            'tile' => 'campaign_landing',
            'description' => __( 'The color that will be used as the theme color for the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
            'default' => $themes,
            'campaign_tab' => 'settings'
        ];
        $fields['custom_theme_color'] = [
            'name' => __( 'Custom Theme Color', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'tile' => 'campaign_landing',
            'description' => __( 'The color that will be used as the custom theme color for the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
        ];
        $fields['logo_url'] = [
            'name' => __( 'Custom Logo URL', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'tile' => 'campaign_landing',
            'description' => __( 'The logo that will be used as the logo for the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
        ];
        $fields['logo_link_url'] = [
            'name' => __( 'Logo Link URL', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'tile' => 'campaign_landing',
            'description' => __( 'Where the logo will link to', 'disciple-tools-prayer-campaigns' ),
        ];
        //header_background_url
        $fields['header_background_url'] = [
            'name' => __( 'Header Background URL', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'tile' => 'campaign_landing',
            'description' => __( 'The URL that will be used as the background image for the header on the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
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
            'name' => __( 'Footer Facebook Link', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'tile' => 'campaign_landing',
            'description' => __( 'The URL that will be used as the Facebook link on the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
            'campaign_tab' => 'settings'
        ];
        //instagram
        $fields['instagram'] = [
            'name' => __( 'Footer Instagram Link', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'tile' => 'campaign_landing',
            'description' => __( 'The URL that will be used as the Instagram link on the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
        ];

        $fields['google_analytics'] = [
            'name' => __( 'Google Analytics ID (optional)', 'disciple-tools-prayer-campaigns' ),
            'type' => 'text',
            'tile' => 'campaign_landing',
            'description' => __( 'Google Analytics ID (optional)', 'disciple-tools-prayer-campaigns' ),
        ];

        /**
         * Prayer fuel settings
         */
        //show_prayer_timer
        $fields['show_prayer_timer'] = [
            'name' => __( 'Show Prayer Timer', 'disciple-tools-prayer-campaigns' ),
            'type' => 'key_select',
            'tile' => 'campaign_landing',
            'description' => __( 'Whether or not to show the prayer timer on the campaign landing page.', 'disciple-tools-prayer-campaigns' ),
            'default' => [
                'yes' => [ 'label' => __( 'Yes', 'disciple-tools-prayer-campaigns' ) ],
                'no' => [ 'label' => __( 'No', 'disciple-tools-prayer-campaigns' ) ],
            ]
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

    public static function get_campaign_id( $search_my_campaigns = false ){
        if ( isset( $_GET['campaign'] ) ){
            return sanitize_key( wp_unslash( $_GET['campaign'] ) );
        }
        if ( defined( 'CAMPAIGN_ID' ) ) {
            return CAMPAIGN_ID;
        }

        if ( $search_my_campaigns || is_admin() ) {
            $campaigns = DT_Posts::list_posts( 'campaigns', [] );
            if ( !empty( $campaigns['posts'] ) && isset( $campaigns['posts'][0]['ID'] ) ){
                return $campaigns['posts'][0]['ID'];
            }
        }

        $default_campaign = get_option( 'dt_campaign_selected_campaign', false );
        if ( !empty( $default_campaign ) ){
            return $default_campaign;
        }

        return null;
    }

    public static function get_campaign( $selected_campaign = null, $search_my_campaigns = false ) {
        if ( $selected_campaign === null ) {
            $selected_campaign = self::get_campaign_id( $search_my_campaigns );
        }
//        if ( !defined( 'CAMPAIGN_ID' ) && !empty( $selected_campaign ) ) {
//            define( 'CAMPAIGN_ID', $selected_campaign );
//        }
        if ( empty( $selected_campaign ) ) {
            return [];
        }

        $campaign = DT_Posts::get_post( 'campaigns', (int) $selected_campaign, true, false );
        if ( is_wp_error( $campaign ) ) {
            return [];
        }

//        wp_cache_set( 'dt_selected_campaign_' . $selected_campaign, $campaign );
        return $campaign;
    }

}
new DT_Campaign_Landing_Settings();
