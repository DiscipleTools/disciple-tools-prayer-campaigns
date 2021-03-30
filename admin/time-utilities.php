<?php

class DT_Time_Utilities {

    public static function subscribed_times_list( $post_id ){
        $record = DT_Posts::get_post( 'campaigns', $post_id, true, false );

        // process start and end of campaign
        if ( !isset( $record['start_date'] )) {
            $start = strtotime( gmdate( 'Y-m-d', time() ) );
        } else {
            $start = strtotime( gmdate( 'Y-m-d', $record['start_date']['timestamp'] ) ); // perfects the stamp to day beginning
        }
        $record['start_date'] = $start;

        if ( !isset( $record['end_date'] )) {
            $end = strtotime( '+30 days' );
        } else {
            $end = strtotime( gmdate( 'Y-m-d', $record['end_date']['timestamp'] ) ) + 86399; // end of selected day (-1 second)
        }
        $record['end_date'] = $end;

        $current_times_list = self::get_current_commitments( $record['ID'], (int) $start, (int) $end );
        return $current_times_list;
    }


    public static function campaign_times_list( $post_id ) {
        $data = [];

        $record = DT_Posts::get_post( 'campaigns', $post_id, true, false );

        // process start and end of campaign
        if ( !isset( $record['start_date'] )) {
            $start = time();
        } else {
            $start = strtotime( gmdate( 'Y-m-d', $record['start_date']['timestamp'] ) ); // perfects the stamp to day beginning
        }
        $record['start_date'] = $start;

        if ( !isset( $record['end_date'] )) {
            $end = strtotime( '+30 days' );
        } else {
            $end = strtotime( gmdate( 'Y-m-d', $record['end_date']['timestamp'] ) ) + 86399; // end of selected day (-1 second)
        }
        $record['end_date'] = $end;

        $current_times_list = self::get_current_commitments( $record['ID'], (int) $start, (int) $end );

        // build time list array
        while ($start <= $end) {

            $time_begin = $start;
            $end_of_day = $start + 86400;

            while ( $time_begin < $end_of_day ) {
                if ( !isset( $data[$start] )) {
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
                    'formatted' => gmdate( 'H:i', $time_begin ),
                    'subscribers' => $current_times_list[$time_begin] ?? 0,
                ];

                $time_begin = $time_begin + 900; // add 15 minutes
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
                $percent = $covered / 96 * 100; // 96 is based on 15 minute blocks for 24 hours. different block size will require different math
                $data[$index]['percent'] = $percent;
                $data[$index]['blocks_covered'] = $covered;
            }
        }

        return $data;
    }

    public static function get_current_commitments( $campaign_post_id, int $campaign_start_date, int $campaign_end_date ) {
        global $wpdb;
        $commitments = $wpdb->get_results($wpdb->prepare( "
            SELECT time_begin, COUNT(id) as count
                FROM $wpdb->dt_reports
                WHERE post_type = 'subscriptions'
                AND parent_id = %s
                AND time_begin >= %d
                AND time_begin <= %d
                GROUP BY time_begin
            ", $campaign_post_id, $campaign_start_date, $campaign_end_date
        ), ARRAY_A );

        $times_list = [];

        if ( ! empty( $commitments ) ){
            foreach ( $commitments as $commitment ) {
                $times_list[$commitment['time_begin']] = $commitment['count'];
            }
        }

        return $times_list;
    }

    public static function start_of_campaign_with_timezone( $post_id ){
        $post = DT_Posts::get_post( 'campaigns', $post_id, true, false );
        if ( isset( $post["campaign_timezone"]["key"] ) ){
            if ( isset( $post["start_date"]["timestamp"] ) && !empty( $post["start_date"]["timestamp"] ) ){
                $dt_now = new DateTime();
                // Set a non-default timezone if needed
                $dt_now->setTimezone( new DateTimeZone( $post["campaign_timezone"]["key"] ) );
                $dt_now->setTimestamp( $post["start_date"]["timestamp"] );

                $start_date = clone $dt_now;
                $start_date->modify( 'today' );
                return $start_date->getTimestamp();
            }
        }
        return isset( $post["start_date"]["timestamp"] ) ? $post["start_date"]["timestamp"] : time();
    }
    public static function end_of_campaign_with_timezone( $post_id ){
        $post = DT_Posts::get_post( 'campaigns', $post_id, true, false );
        if ( isset( $post["campaign_timezone"]["key"] ) ){
            if ( isset( $post["end_date"]["timestamp"] ) && !empty( $post["end_date"]["timestamp"] ) ){
                $dt_now = new DateTime();
                // Set a non-default timezone if needed
                $dt_now->setTimezone( new DateTimeZone( $post["campaign_timezone"]["key"] ) );
                $dt_now->setTimestamp( $post["end_date"]["timestamp"] );

                $end_date = clone $dt_now;
                $end_date->modify( 'today' );
                return $end_date->getTimestamp();
            }
        }
        return isset( $post["end_date"]["timestamp"] ) ? $post["end_date"]["timestamp"] : time();
    }

}
