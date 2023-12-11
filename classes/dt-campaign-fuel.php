<?php

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Encapsulates getting and setting of campaign settings
 */
class DT_Campaign_Fuel {

    /**
     * Get the day number that a certain date is in the campaign
     *
     * @param string $date
     *
     * @return int
     */
    public static function what_day_in_campaign( string $date, $campaign_id = null ) {
        $campaign = DT_Campaign_Landing_Settings::get_campaign( $campaign_id );
        $campaign_start_date = $campaign['start_date']['formatted'];


        $diff = self::diff_days_between_dates( $campaign_start_date, $date );

        /* If the date given is the same as the start, then this is day 1 not day 0 */
        /* If the date given is before the start, then this is a negative day */
        if ( $diff >= 0 ) {
            return $diff + 1;
        } else {
            return $diff;
        }
    }

    public static function diff_days_between_dates( string $start, string $end ) {
        $end_date = new DateTime( $end );
        $start_date = new DateTime( $start );

        return intval( $start_date->diff( $end_date )->format( '%r%a' ) );
    }

    /**
     * Return the total number of days in the campaign.  If no start/end date, return -1.
     *
     * @return int
     */
    public static function total_days_in_campaign(): int {
        $campaign = DT_Campaign_Landing_Settings::get_campaign();

        if ( !isset( $campaign['end_date'] ) ) {
            return -1;
        }

        $campaign_start_date = $campaign['start_date']['timestamp'];
        $campaign_end_date = $campaign['end_date']['timestamp'] + DAY_IN_SECONDS; //end of day.

        return round( ( $campaign_end_date - $campaign_start_date ) / DAY_IN_SECONDS );

    }

    /**
     * Get the mysql date string for the day of the campaign
     */
    public static function date_of_campaign_day( int $day ) {
        $porch_fields = DT_Porch_Settings::settings();
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
        $campaign_start_time = $campaign['start_date']['timestamp'];

        $internal = DAY_IN_SECONDS;
        if ( $porch_fields['prayer_fuel_frequency']['value'] === 'weekly' ) {
            $internal = DAY_IN_SECONDS * 7;
        } elseif ( $porch_fields['prayer_fuel_frequency']['value'] === 'monthly' ) {
            return gmdate( 'Y-m-d', strtotime( "first day of +{$day} month", $campaign_start_time + ( $day - 1 ) ) );
        }

        return gmdate( 'Y-m-d H:i:s', $campaign_start_time + ( $day - 1 ) * $internal );
    }
}
