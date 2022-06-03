<?php

class DT_Time_Utilities {

    //Get each day with each time chuck counts
    public static function campaign_times_list( $post_id, $month_limit = null ) {
        $data = [];

        $record = DT_Posts::get_post( 'campaigns', $post_id, true, false );

        $min_time_duration = self::campaign_min_prayer_duration( $post_id );
        $start = self::start_of_campaign_with_timezone( $post_id );
        $end = self::end_of_campaign_with_timezone( $post_id, $month_limit, $start );

        $current_times_list = self::get_current_commitments( $record['ID'], $month_limit );

        // build time list array
        while ( $start <= $end ) {

            $time_begin = $start;
            $end_of_day = $start + 86400;

            while ( $time_begin < $end_of_day ) {
                if ( !isset( $data[$start] ) ) {
                    $data[$start] = [
                        'key' => $start,
                        'formatted' => gmdate( 'F d', $start ),
                        'percent' => 0,
                        'blocks_covered' => 0,
                        'hours' => []
                    ];
                }

                $data[$start]['hours'][] = [
                    'key' => $time_begin,
                    'formatted' => gmdate( 'H:i', $time_begin - $start ),
                    'subscribers' => $current_times_list[$time_begin] ?? 0,
                ];

                $time_begin = $time_begin + $min_time_duration * 60; // add x minutes
            } // end while

            $start = $start + 86400; // add a day
        } // end while

        foreach ( $data as $index => $value ) {
            $covered = 0;
            foreach ( $value['hours'] as $time ) {
                if ( $time['subscribers'] > 0 ){
                    $covered++;
                }
            }

            if ( $covered > 0 ){
                $percent = $covered / ( ( 24 * 60 ) / $min_time_duration ) * 100; // number of x minute blocks for 24 hours. different block size will require different math
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
            ", $campaign_post_id, $start - 86400, $end
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
                    $times_list[$time] += $commitment["count"];
                }
            }
        }

        return $times_list;

    }

    public static function campaign_min_prayer_duration( $post_id ){
        $post = DT_Posts::get_post( 'campaigns', $post_id, true, false );
        $min_time_duration = 15;
        if ( isset( $post["min_time_duration"]["key"] ) ){
            $min_time_duration = $post["min_time_duration"]["key"];
        }
        return $min_time_duration;
    }

    public static function start_of_campaign_with_timezone( $post_id ){
        $post = DT_Posts::get_post( 'campaigns', $post_id, true, false );
        if ( isset( $post["campaign_timezone"]["key"] ) ){
            if ( isset( $post["start_date"]["timestamp"] ) && !empty( $post["start_date"]["timestamp"] ) ){
                $dt_now = new DateTime();
                // Set a non-default timezone if needed
                $dt_now->setTimezone( new DateTimeZone( $post["campaign_timezone"]["key"] ) );
                $dt_now->setTimestamp( $post["start_date"]["timestamp"] );
                $off = $dt_now->getOffset();
                return $dt_now->getTimestamp() - $off;
            }
        }
        return isset( $post["start_date"]["timestamp"] ) ? $post["start_date"]["timestamp"] : time();
    }

    public static function end_of_campaign_with_timezone( $post_id, $month_limit = null, $start = null ){
        $post = DT_Posts::get_post( 'campaigns', $post_id, true, false );
        $end = isset( $post["end_date"]["timestamp"] ) ? $post["end_date"]["timestamp"] : null;
        if ( $start && $start < time() ){
            $start = time();
        }
        if ( $end && $start && $month_limit && $end > ( $start + $month_limit * MONTH_IN_SECONDS ) ){
            $end = $start + $month_limit * MONTH_IN_SECONDS;
        }
        if ( !$end && $start ){
            $end = $start + ( $month_limit ?? 2 ) * MONTH_IN_SECONDS;
        }

        if ( isset( $post["campaign_timezone"]["key"] ) ){
            if ( $end ){
                $dt_now = new DateTime();
                // Set a non-default timezone if needed
                $dt_now->setTimezone( new DateTimeZone( $post["campaign_timezone"]["key"] ) );
                $dt_now->setTimestamp( $end );
                $off = $dt_now->getOffset();
                $end = $dt_now->getTimestamp() - $off;
            }
        }

        //if we want to restrict the data returned and the end date is after the limit
        return $end + 86399; // end of selected day (-1 second);
    }

}
