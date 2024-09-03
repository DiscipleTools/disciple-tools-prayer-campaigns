<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Prayer_Campaign_Migration_0009
 */
class DT_Prayer_Campaign_Migration_0010 extends DT_Prayer_Campaign_Migration {
    /**
     * @throws \Exception  Got error when creating table $name.
     */
    public function up() {

        $active_campaign = get_option( 'dt_campaign_selected_campaign' );
        if ( empty( $active_campaign ) ){
            return;
        }

        $porch_selector = DT_Porch_Selector::instance();
        $porch_selector->load_selected_porch();

        $porch_settings = get_option( 'dt_campaign_porch_settings' );
        if ( empty( $porch_settings ) ){
            return;
        }
        $global_campaign_settings = get_option( 'dt_prayer_campaign_settings' );
        $campaign = DT_Posts::get_post( 'campaigns', (int) $active_campaign, false, false );

        $campaign_update = [];
        if ( !empty( $global_campaign_settings['email_address'] ) ){
            $campaign_update['from_email'] = $global_campaign_settings['email_address'];
        }
        if ( !empty( $global_campaign_settings['email_name'] ) ){
            $campaign_update['from_name'] = $global_campaign_settings['email_name'];
        }
        if ( !empty( $global_campaign_settings['email_logo'] ) ){
            $campaign_update['email_logo'] = $global_campaign_settings['email_logo'];
        }

        if ( !empty( $global_campaign_settings['selected_porch'] ) ){
            $campaign_update['porch_type'] = $global_campaign_settings['selected_porch'];
        }

        if ( !empty( $campaign['campaign_strings'] ) ){
            if ( isset( $campaign['campaign_strings']['default']['signup_content'] ) && !empty( $campaign['campaign_strings']['default']['signup_content'] ) ){
                $campaign_update['signup_content'] = $campaign['campaign_strings']['default']['signup_content'];
            }
            if ( isset( $campaign['campaign_strings']['default']['reminder_content'] ) && !empty( $campaign['campaign_strings']['default']['reminder_content'] ) ){
                $campaign_update['reminder_content'] = $campaign['campaign_strings']['default']['reminder_content'];
            }
            foreach ( $campaign['campaign_strings'] as $language => $strings ){
                if ( $language === 'default' ){
                    continue;
                }
                if ( isset( $strings['signup_content'] ) && !empty( $strings['signup_content'] ) ){
                    DT_Campaign_Languages::save_translation( $campaign['ID'], 'signup_content', $language, $strings['signup_content'] );
                }
                if ( isset( $strings['reminder_content'] ) && !empty( $strings['reminder_content'] ) ){
                    DT_Campaign_Languages::save_translation( $campaign['ID'], 'reminder_content', $language, $strings['reminder_content'] );
                }
            }
        }

        DT_Posts::get_post_settings( 'campaigns', false );
        $campaign_field_settings = DT_Posts::get_post_field_settings( 'campaigns', false );
        //porch settings
        foreach ( ( $porch_settings ?? [] ) as $porch_setting_key => $porch_setting ){
            if ( $porch_setting_key === 'title' ){
                $porch_setting_key = 'name';
            }
            if ( isset( $campaign_field_settings[$porch_setting_key] ) && !empty( $porch_setting ) ){
                $campaign_update[$porch_setting_key] = $porch_setting;
            }
        }

        $name = $campaign_update['name'] ?? $campaign['name'];

        //set campaign_url
        $campaign_update['campaign_url'] = str_replace( ' ', '-', strtolower( trim( $name ) ) );

        $language_settings = get_option( 'dt_campaign_languages' );
        $campaign_update['enabled_languages'] = [ 'values' => [ [ 'value' => 'en_US' ] ] ];
        foreach ( ( $language_settings ?? [] ) as $language_code => $language_setting ){
            if ( $language_setting['enabled'] === true ){
                $campaign_update['enabled_languages']['values'][] = [ 'value' => $language_code ];
            }
        }

        if ( !empty( $campaign_update ) ){
            $update = DT_Posts::update_post( 'campaigns', (int) $active_campaign, $campaign_update, true, false );
        }

        $porch_string_translations = get_option( 'dt_campaign_porch_translations' );
        foreach ( $porch_string_translations ?? [] as $porch_string_key => $porch_string_translation ){
            foreach ( $porch_string_translation as $language => $translation ){
                if ( !empty( $translation ) ){
                    DT_Campaign_Languages::save_translation( $campaign['ID'], $porch_string_key, $language, $translation );
                }
            }
        }

        global $wpdb;
        $wpdb->query( "
            UPDATE $wpdb->postmeta
            SET meta_value = 'generic-porch'
            WHERE meta_key = 'porch_type'
            AND meta_value = 'ongoing-porch'
            OR meta_value = '24hour-porch'
        " );
    }

    /**
     * @throws \Exception  Got error when dropping table $name.
     */
    public function down() {
    }

    /**
     * Test function
     */
    public function test() {
    }
}
