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
    public static function what_day_in_campaign( string $date, $campaign_id = null, $frequency = null ) {
        $campaign = DT_Campaign_Landing_Settings::get_campaign( $campaign_id );
        $campaign_start_date = $campaign['start_date']['formatted'];

        if ( $frequency === null ){
            $frequency = 'daily';
            if ( isset( $campaign['prayer_fuel_frequency']['key'] ) ) {
                $frequency = $campaign['prayer_fuel_frequency']['key'];
            }
        }

        $diff = self::diff_days_between_dates( $campaign_start_date, $date );

        if ( $frequency === 'weekly' ) {
            $diff = floor( $diff / 7 );
        } elseif ( $frequency === 'monthly' ) {
            $diff = floor( $diff / 30 );
        }


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
    public static function total_days_in_campaign( $selected_campaign = null ): int {
        $campaign = DT_Campaign_Landing_Settings::get_campaign( $selected_campaign );

        if ( !isset( $campaign['end_date'] ) ) {
            return -1;
        }

        $campaign_start_date = $campaign['start_date']['timestamp'];
        $campaign_end_date = $campaign['end_date']['timestamp'] + DAY_IN_SECONDS; //end of day.

        return round( ( $campaign_end_date - $campaign_start_date ) / DAY_IN_SECONDS );
    }

    /**
     * Get the timezone of the campaign, falling back to UTC
     *
     * @param int|null $campaign_id
     *
     * @return DateTimeZone
     */
    public static function campaign_timezone( $campaign_id = null ): DateTimeZone {
        $campaign = DT_Campaign_Landing_Settings::get_campaign( $campaign_id );
        $timezone = $campaign['campaign_timezone']['key'] ?? 'UTC';
        try {
            return new DateTimeZone( $timezone );
        } catch ( Exception $e ) {
            return new DateTimeZone( 'UTC' );
        }
    }

    /**
     * Get today's date in the campaign's timezone
     *
     * @param int|null $campaign_id
     * @param string $format
     *
     * @return string
     */
    public static function today_in_campaign_timezone( $campaign_id = null, string $format = 'Y-m-d' ): string {
        return wp_date( $format, time(), self::campaign_timezone( $campaign_id ) );
    }

    /**
     * Format the timestamp of a campaign day in the campaign's timezone.
     * date_of_campaign_day() returns the UTC time of midnight in the campaign timezone,
     * so formatting it in UTC shows the previous day for timezones ahead of UTC.
     *
     * @param int $day
     * @param string $format
     *
     * @return string
     */
    public static function formatted_date_of_campaign_day( int $day, string $format = 'Y/m/d' ): string {
        return wp_date( $format, strtotime( self::date_of_campaign_day( $day ) ), self::campaign_timezone() );
    }

    /**
     * Get the mysql date string (UTC) for the start of the day of the campaign
     */
    public static function date_of_campaign_day( int $day ) {
        $porch_fields = DT_Porch_Settings::settings();
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
        $campaign_start_time = DT_Time_Utilities::start_of_campaign_with_timezone( $campaign['ID'] );

        $internal = DAY_IN_SECONDS;
        if ( $porch_fields['prayer_fuel_frequency']['value'] === 'weekly' ) {
            $internal = DAY_IN_SECONDS * 7;
        } elseif ( $porch_fields['prayer_fuel_frequency']['value'] === 'monthly' ) {
            return gmdate( 'Y-m-d', strtotime( "first day of +{$day} month", $campaign_start_time + ( $day - 1 ) ) );
        }

        return gmdate( 'Y-m-d H:i:s', $campaign_start_time + ( $day - 1 ) * $internal );
    }
}
