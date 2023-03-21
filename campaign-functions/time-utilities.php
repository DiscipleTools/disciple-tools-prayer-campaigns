<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DT_Time_Utilities {

    //Get each day with each time chuck counts
    public static function campaign_times_list( $post_id, $month_limit = null ) {
        $data = [];

        $record = DT_Posts::get_post( 'campaigns', $post_id, true, false );

        $min_time_duration = self::campaign_min_prayer_duration( $post_id );
        $start_with_tz = self::start_of_campaign_with_timezone( $post_id );
        $end = self::end_of_campaign_with_timezone( $post_id, $month_limit, $start_with_tz );

        $start = strtotime( gmdate( 'Y-m-d', $start_with_tz ) );

        $current_times_list = self::get_current_commitments( $record['ID'], $month_limit );

        // build time list array
        while ( $start <= $end ) {

            $time_begin = $start;
            $end_of_day = $start + DAY_IN_SECONDS;

            while ( $time_begin < $end_of_day ) {
                if ( !isset( $data[$start] ) ){
                    $data[$start] = [
                        'key' => $start,
                        'formatted' => gmdate( 'F d', $start ),
                        'percent' => 0,
                        'blocks_covered' => 0,
                        'hours' => [],
                        'time_slot_count' => 0,
                    ];
                }

                $outside_of_campaign = $time_begin < $start_with_tz || $time_begin > $end;


                if ( !$outside_of_campaign ){
                    $data[$start]['time_slot_count']++;
                }
                $data[$start]['hours'][] = [
                    'key' => $time_begin,
                    'formatted' => gmdate( 'H:i', $time_begin - $start ),
                    'subscribers' => $current_times_list[$time_begin] ?? 0,
                    'outside_of_campaign' => $time_begin < $start_with_tz || $time_begin > $end
                ];


                $time_begin = $time_begin + $min_time_duration * 60; // add x minutes
            } // end while

            $start = $start + DAY_IN_SECONDS; // add a day
        } // end while

        foreach ( $data as $index => $day ) {
            $covered = 0;
            foreach ( $day['hours'] as $time ) {
                if ( $time['subscribers'] > 0 ){
                    $covered++;
                }
            }

            if ( $covered > 0 ){
                $percent = $covered / $day['time_slot_count'] * 100; // number of x minute blocks for 24 hours. different block size will require different math
                $data[$index]['percent'] = $percent;
                $data[$index]['blocks_covered'] = $covered;
            }
        }

        return $data;
    }

    public static function get_current_commitments( $campaign_post_id, $month_limit = null ) {

        $min_time_duration = self::campaign_min_prayer_duration( $campaign_post_id );
        $start = self::start_of_campaign_with_timezone( $campaign_post_id );
        $end = self::end_of_campaign_with_timezone( $campaign_post_id, $month_limit, $start );

        global $wpdb;
        $commitments = $wpdb->get_results($wpdb->prepare( "
            SELECT time_begin, time_end, COUNT(id) as count
                FROM $wpdb->dt_reports
                WHERE post_type = 'subscriptions'
                AND parent_id = %s
                AND time_begin >= %d
                AND time_begin <= %d
                GROUP BY time_begin, time_end
            ", $campaign_post_id, $start - DAY_IN_SECONDS, $end
        ), ARRAY_A );

        $times_list = [];


        if ( !empty( $commitments ) ){
            foreach ( $commitments as $commitment ){
                // split into x min increments
                for ( $i = 0; $i < $commitment['time_end'] - $commitment['time_begin']; $i += $min_time_duration *60 ){
                    $time = $commitment['time_begin'] + $i;
                    if ( !isset( $times_list[$time] ) ){
                        $times_list[$time] = 0;
                    }
                    $times_list[$time] += $commitment['count'];
                }
            }
        }

        return $times_list;

    }

    public static function campaign_min_prayer_duration( $post_id ){
        $post = DT_Posts::get_post( 'campaigns', $post_id, true, false );
        $min_time_duration = 15;
        if ( isset( $post['min_time_duration']['key'] ) ){
            $min_time_duration = $post['min_time_duration']['key'];
        }
        return $min_time_duration;
    }

    public static function start_of_campaign_with_timezone( $post_id ){
        $post = DT_Posts::get_post( 'campaigns', $post_id, true, false );
        if ( isset( $post['campaign_timezone']['key'] ) ){
            if ( isset( $post['start_date']['timestamp'] ) && !empty( $post['start_date']['timestamp'] ) ){
                $dt_now = new DateTime();
                // Set a non-default timezone if needed
                $dt_now->setTimezone( new DateTimeZone( $post['campaign_timezone']['key'] ) );
                $dt_now->setTimestamp( strtotime( $post['start_date']['formatted'] ) );
                $off = $dt_now->getOffset();
                return $dt_now->getTimestamp() - $off;
            }
        }
        return isset( $post['start_date']['formatted'] ) ? strtotime( $post['start_date']['formatted'] ) : time();
    }

    public static function end_of_campaign_with_timezone( $post_id, $month_limit = null, $start = null ){
        $post = DT_Posts::get_post( 'campaigns', $post_id, true, false );
        $end = isset( $post['end_date']['timestamp'] ) ? $post['end_date']['timestamp'] : null;
        if ( $start && $start < time() ){
            $start = time();
        }
        if ( $end && $start && $month_limit && $end > ( $start + $month_limit * MONTH_IN_SECONDS ) ){
            $end = $start + $month_limit * MONTH_IN_SECONDS;
        }
        if ( !$end && $start ){
            $end = $start + ( $month_limit ?? 2 ) * MONTH_IN_SECONDS;
        }

        if ( isset( $post['campaign_timezone']['key'] ) ){
            if ( $end ){
                $dt_now = new DateTime();
                // Set a non-default timezone if needed
                $dt_now->setTimezone( new DateTimeZone( $post['campaign_timezone']['key'] ) );
                $dt_now->setTimestamp( $end );
                $off = $dt_now->getOffset();
                $end = $dt_now->getTimestamp() - $off;
            }
        }

        //if we want to restrict the data returned and the end date is after the limit
        return $end + 86399; // end of selected day (-1 second);
    }

    public static function get_slot_duration_options(){
        return [
            '5' => [ 'label' => __( '5 minutes', 'disciple-tools-prayer-campaigns' ) ],
            '10' => [ 'label' => __( '10 minutes', 'disciple-tools-prayer-campaigns' ) ],
            '15' => [ 'label' => __( '15 minutes', 'disciple-tools-prayer-campaigns' ) ],
            '30' => [ 'label' => __( '30 minutes', 'disciple-tools-prayer-campaigns' ) ],
            '60' => [ 'label' => __( '1 hour', 'disciple-tools-prayer-campaigns' ) ],
        ];
    }

    public static function display_minutes_in_time( $time_committed ){
        $days_committed = round( fmod( $time_committed / 60 / 24, 365 ), 2 );
        $years_committed = floor( $time_committed / 60 / 24 / 365 );
        $string = '';
        if ( !empty( $years_committed ) ){
            $string .= $years_committed . ' ' .( $years_committed > 1 ? __( 'years', 'disciple-tools-prayer-campaigns' ) : __( 'year', 'disciple-tools-prayer-campaigns' ) );
            $string  .= ' ';
        }
        $string .= $days_committed . ' ' . ( (int) $days_committed === 1 ? __( 'day', 'disciple-tools-prayer-campaigns' ) : __( 'days', 'disciple-tools-prayer-campaigns' ) );
        return $string;
    }

}
