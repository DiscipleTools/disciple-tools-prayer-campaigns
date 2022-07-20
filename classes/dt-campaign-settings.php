<?php

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Encapsulates getting and setting of campaign settings
 */
class DT_Campaign_Settings {
    private $settings_key = 'dt_prayer_campaign_settings';

    private function get_all() {
        return get_option( $this->settings_key, [] );
    }

    public function get( string $name ) {
        $settings = $this->get_all();

        if ( isset( $settings[$name] ) ) {
            return $settings[$name];
        }

        return null;
    }

    public function update( string $name, $value ) {
        $settings = $this->get_all();

        if ( !$value || empty( $value ) ) {
            unset( $settings[$name] );
        } else {
            $new_setting = [];

            $new_setting[$name] = $value;

            $settings = array_merge( $settings, $new_setting );
        }

        update_option( $this->settings_key, $settings );
    }

    public static function get_campaign() {

        $selected_campaign = get_option( 'dt_campaign_selected_campaign', false );

        if ( empty( $selected_campaign ) ) {
            return [];
        }

        $campaign = DT_Posts::get_post( 'campaigns', (int) $selected_campaign, true, false );
        if ( is_wp_error( $campaign ) ) {
            return [];
        }

        return $campaign;
    }

    /**
     * Get the day number that a certain date is in the campaign
     *
     * @param string $date
     *
     * @return int
     */
    public static function what_day_in_campaign( string $date ) {
        $campaign = self::get_campaign();
        $campaign_start_date = $campaign["start_date"]["formatted"];

        $given = new DateTime( $date );
        $campaign_start = new DateTime( $campaign_start_date );

        $diff = intval( $campaign_start->diff( $given )->format( "%r%a" ) );

        /* If the date given is the same as the start, then this is day 1 not day 0 */
        /* If the date given is before the start, then this is a negative day */
        if ( $diff >= 0 ) {
            return $diff + 1;
        } else {
            return $diff;
        }
    }

    /**
     * How long is the campaign. -1 if is ongoing
     *
     * @return int
     */
    public static function campaign_length() {
        $campaign = self::get_campaign();

        if ( !isset( $campaign["end_date"] ) ) {
            return -1;
        }

        $campaign_start_date = $campaign["start_date"]["formatted"];
        $campaign_end_date = $campaign["end_date"]["formatted"];

        $campaign_start = new DateTime( $campaign_start_date );
        $campaign_end = new DateTime( $campaign_end_date );

        $diff = intval( $campaign_start->diff( $campaign_end )->format( "%r%a" ) );

        /* If the date given is the same as the start, then this is day 1 not day 0 */
        /* If the date given is before the start, then this is a negative day */
        return $diff;
    }

    /**
     * Get the mysql date string for the day of the campaign
     */
    public static function date_of_campaign_day( int $day ) {
        $campaign = self::get_campaign();
        $campaign_start_date = $campaign["start_date"]["formatted"];

        return gmdate( "Y-m-d H:i:s", strtotime( $campaign_start_date . " +" . $day - 1 . " days" ) );
    }
}
