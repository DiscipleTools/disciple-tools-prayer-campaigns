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
     * @param int|array|null $campaign campaign id, an already loaded campaign, or null for the current campaign
     *
     * @return DateTimeZone
     */
    public static function campaign_timezone( $campaign = null ): DateTimeZone {
        if ( !is_array( $campaign ) ) {
            $campaign = DT_Campaign_Landing_Settings::get_campaign( $campaign );
        }
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
     * @param int|array|null $campaign campaign id, an already loaded campaign, or null for the current campaign
     * @param string $format
     *
     * @return string
     */
    public static function today_in_campaign_timezone( $campaign = null, string $format = 'Y-m-d' ): string {
        return wp_date( $format, time(), self::campaign_timezone( $campaign ) );
    }

    /**
     * Get the start (midnight in the campaign's timezone) of a day of the campaign.
     * Calendar days are counted rather than seconds so daylight saving changes don't shift the date.
     *
     * @param int $day
     * @param int|array|null $campaign campaign id, an already loaded campaign, or null for the current campaign
     *
     * @return DateTimeImmutable
     */
    public static function datetime_of_campaign_day( int $day, $campaign = null ): DateTimeImmutable {
        if ( !is_array( $campaign ) ) {
            $campaign = DT_Campaign_Landing_Settings::get_campaign( $campaign );
        }
        $timezone = self::campaign_timezone( $campaign );
        $start = !empty( $campaign['start_date']['formatted'] ) ? $campaign['start_date']['formatted'] : 'today';
        $date = ( new DateTimeImmutable( $start, $timezone ) )->setTime( 0, 0 );

        $frequency = $campaign['prayer_fuel_frequency']['key'] ?? 'daily';
        if ( $frequency === 'monthly' ) {
            return $date->modify( "first day of +{$day} month" );
        }

        $interval_days = $frequency === 'weekly' ? 7 : 1;
        return $date->modify( '+' . ( ( $day - 1 ) * $interval_days ) . ' days' );
    }

    /**
     * Format the date of a campaign day in the campaign's timezone
     *
     * @param int $day
     * @param string $format
     * @param int|array|null $campaign campaign id, an already loaded campaign, or null for the current campaign
     *
     * @return string
     */
    public static function formatted_date_of_campaign_day( int $day, string $format = 'Y/m/d', $campaign = null ): string {
        return self::datetime_of_campaign_day( $day, $campaign )->format( $format );
    }

    /**
     * Get the mysql date string (UTC) for the start of the day of the campaign
     *
     * @param int $day
     * @param int|array|null $campaign campaign id, an already loaded campaign, or null for the current campaign
     *
     * @return string
     */
    public static function date_of_campaign_day( int $day, $campaign = null ) {
        return gmdate( 'Y-m-d H:i:s', self::datetime_of_campaign_day( $day, $campaign )->getTimestamp() );
    }
}
