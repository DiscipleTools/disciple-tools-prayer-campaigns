<?php

class DT_Time_Utilities {
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


//    public static function times_lists() : array {
//        if ( wp_cache_get(__METHOD__) ){
//            return wp_cache_get(__METHOD__);
//        }
//        $data = [
//            'hours' => [], // hours from 00 to 23
//            'hour' => [], // key: hour+minute blocks (15 min increments), formatted: hour:block
//            'hour_list' => [], // hour+block array
//            'quarter_hour' => [], // key: hour+minute blocks (15 min increments), formatted: hour:block
//            'quarter_hour_list' => [], // hour+block array
//            'half_hour' => [], // key: hour+minute blocks (30 min increments), formatted: hour:block
//            'half_hour_list' => [], // hour+block array
//            'months' => [], // list of months 01 - 12
//            'month_days' => [], // key: month+day, formatted: month - day, formatted_dm: day month order for eu
//            'month_days_list' => [], // month+day
//            'quarter_hour_year' => [], // keys for a year. month+day+hour+block
//            'quarter_hour_year_list' => [],
//            'half_hour_year' => [], // keys for a year. month+day+hour+block
//            'half_hour_year_list' => [],
//        ];
//
//        $data['hours'] = [
//            '00',
//            '01',
//            '02',
//            '03',
//            '04',
//            '05',
//            '06',
//            '07',
//            '08',
//            '09',
//            '10',
//            '11',
//            '12',
//            '13',
//            '14',
//            '15',
//            '16',
//            '17',
//            '18',
//            '19',
//            '20',
//            '21',
//            '22',
//            '23',
//            ];
//
//        $data['hour'] = [];
//        $data['hour_list'] = [];
//        $data['quarter_hour'] = [];
//        $data['quarter_hour_list'] = [];
//        $data['half_hour'] = [];
//        $data['half_hour_list'] = [];
//        foreach( $data['hours'] as $hour ){
//            if ( '00' !== $hour ) {
//                $hour_trimmed = ltrim($hour, '0');
//            } else {
//                $hour_trimmed = $hour;
//            }
//            $data['quarter_hour'][$hour . '00'] = [
//                'key' => $hour . '00',
//                'formatted' => $hour_trimmed . ':00',
//            ];
//            $data['quarter_hour_list'][] = $hour . '00';
//            $data['quarter_hour'][$hour . '15'] = [
//                'key' => $hour . '15',
//                'formatted' => $hour_trimmed . ':15',
//            ];
//            $data['quarter_hour_list'][] = $hour . '15';
//            $data['quarter_hour'][$hour . '30'] = [
//                'key' => $hour . '30',
//                'formatted' => $hour_trimmed . ':30',
//            ];
//            $data['quarter_hour_list'][] = $hour . '30';
//            $data['quarter_hour'][$hour . '45'] = [
//                'key' => $hour . '45',
//                'formatted' => $hour_trimmed . ':45',
//            ];
//            $data['quarter_hour_list'][] = $hour . '45';
//
//            $data['half_hour'][$hour . '00'] = [
//                'key' => $hour . '00',
//                'formatted' => $hour_trimmed . ':00',
//            ];
//            $data['half_hour_list'][] = $hour . '00';
//            $data['half_hour'][$hour . '30'] = [
//                'key' => $hour . '30',
//                'formatted' => $hour_trimmed . ':30',
//            ];
//            $data['half_hour_list'][] = $hour . '30';
//
//            $data['hour'][$hour . '00'] = [
//                'key' => $hour . '00',
//                'formatted' => $hour_trimmed . ':00',
//            ];
//            $data['hour_list'][] = $hour . '00';
//        }
//
//        $data['months'] = [
//            '01',
//            '02',
//            '03',
//            '04',
//            '05',
//            '06',
//            '07',
//            '08',
//            '09',
//            '10',
//            '11',
//            '12',
//        ];
//
//        $data['month_days'] = [];
//        $data['month_days_list'] = [];
//        foreach( $data['months'] as $month ) {
//            $data['month_days_list'][] = $month . '01';
//            $data['month_days_list'][] = $month . '02';
//            $data['month_days_list'][] = $month . '03';
//            $data['month_days_list'][] = $month . '04';
//            $data['month_days_list'][] = $month . '05';
//            $data['month_days_list'][] = $month . '06';
//            $data['month_days_list'][] = $month . '07';
//            $data['month_days_list'][] = $month . '08';
//            $data['month_days_list'][] = $month . '09';
//            $data['month_days_list'][] = $month . '10';
//            $data['month_days_list'][] = $month . '11';
//            $data['month_days_list'][] = $month . '12';
//            $data['month_days_list'][] = $month . '13';
//            $data['month_days_list'][] = $month . '14';
//            $data['month_days_list'][] = $month . '15';
//            $data['month_days_list'][] = $month . '16';
//            $data['month_days_list'][] = $month . '17';
//            $data['month_days_list'][] = $month . '18';
//            $data['month_days_list'][] = $month . '19';
//            $data['month_days_list'][] = $month . '20';
//            $data['month_days_list'][] = $month . '21';
//            $data['month_days_list'][] = $month . '22';
//            $data['month_days_list'][] = $month . '23';
//            $data['month_days_list'][] = $month . '24';
//            $data['month_days_list'][] = $month . '25';
//            $data['month_days_list'][] = $month . '26';
//            $data['month_days_list'][] = $month . '27';
//            $data['month_days_list'][] = $month . '28';
//            if ( '02' !== $month ){
//                $data['month_days_list'][] = $month . '29';
//                $data['month_days_list'][] = $month . '30';
//                if ( in_array( $month, ['01', '03', '05', '07', '08', '10', '12']) ) {
//                    $data['month_days_list'][] = $month . '31';
//                }
//            }
//
//            $data['month_days'][$month . '01'] =[
//                'key' => $month . '01',
//                'formatted' => ltrim( $month, '0') . '-1',
//                'formatted_dm' => '1-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '02'] =[
//                'key' => $month . '02',
//                'formatted' => ltrim( $month, '0') . '-2',
//                'formatted_dm' => '2-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '03'] =[
//                'key' => $month . '03',
//                'formatted' => ltrim( $month, '0') . '-3',
//                'formatted_dm' => '3-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '04'] =[
//                'key' => $month . '04',
//                'formatted' => ltrim( $month, '0') . '-4',
//                'formatted_dm' => '4-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '05'] =[
//                'key' => $month . '05',
//                'formatted' => ltrim( $month, '0') . '-5',
//                'formatted_dm' => '5-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '06'] =[
//                'key' => $month . '06',
//                'formatted' => ltrim( $month, '0') . '-6',
//                'formatted_dm' => '6-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '07'] =[
//                'key' => $month . '07',
//                'formatted' => ltrim( $month, '0') . '-7',
//                'formatted_dm' => '7-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '08'] =[
//                'key' => $month . '08',
//                'formatted' => ltrim( $month, '0') . '-8',
//                'formatted_dm' => '8-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '09'] =[
//                'key' => $month . '09',
//                'formatted' => ltrim( $month, '0') . '-9',
//                'formatted_dm' => '9-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '10'] =[
//                'key' => $month . '10',
//                'formatted' => ltrim( $month, '0') . '-10',
//                'formatted_dm' => '10-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '11'] =[
//                'key' => $month . '11',
//                'formatted' => ltrim( $month, '0') . '-11',
//                'formatted_dm' => '11-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '12'] =[
//                'key' => $month . '12',
//                'formatted' => ltrim( $month, '0') . '-12',
//                'formatted_dm' => '12-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '13'] =[
//                'key' => $month . '13',
//                'formatted' => ltrim( $month, '0') . '-13',
//                'formatted_dm' => '13-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '14'] =[
//                'key' => $month . '14',
//                'formatted' => ltrim( $month, '0') . '-14',
//                'formatted_dm' => '14-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '15'] =[
//                'key' => $month . '15',
//                'formatted' => ltrim( $month, '0') . '-15',
//                'formatted_dm' => '15-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '16'] =[
//                'key' => $month . '16',
//                'formatted' => ltrim( $month, '0') . '-16',
//                'formatted_dm' => '16-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '17'] =[
//                'key' => $month . '17',
//                'formatted' => ltrim( $month, '0') . '-17',
//                'formatted_dm' => '17-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '18'] =[
//                'key' => $month . '18',
//                'formatted' => ltrim( $month, '0') . '-18',
//                'formatted_dm' => '18-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '19'] =[
//                'key' => $month . '19',
//                'formatted' => ltrim( $month, '0') . '-19',
//                'formatted_dm' => '19-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '20'] =[
//                'key' => $month . '20',
//                'formatted' => ltrim( $month, '0') . '-20',
//                'formatted_dm' => '20-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '21'] =[
//                'key' => $month . '21',
//                'formatted' => ltrim( $month, '0') . '-21',
//                'formatted_dm' => '21-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '22'] =[
//                'key' => $month . '22',
//                'formatted' => ltrim( $month, '0') . '-22',
//                'formatted_dm' => '22-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '23'] =[
//                'key' => $month . '23',
//                'formatted' => ltrim( $month, '0') . '-23',
//                'formatted_dm' => '1-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '24'] =[
//                'key' => $month . '24',
//                'formatted' => ltrim( $month, '0') . '-24',
//                'formatted_dm' => '24-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '25'] =[
//                'key' => $month . '25',
//                'formatted' => ltrim( $month, '0') . '-25',
//                'formatted_dm' => '25-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '26'] =[
//                'key' => $month . '26',
//                'formatted' => ltrim( $month, '0') . '-26',
//                'formatted_dm' => '26-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '27'] =[
//                'key' => $month . '27',
//                'formatted' => ltrim( $month, '0') . '-27',
//                'formatted_dm' => '27-' . ltrim( $month, '0'),
//            ];
//            $data['month_days'][$month . '28'] =[
//                'key' => $month . '28',
//                'formatted' => ltrim( $month, '0') . '-28',
//                'formatted_dm' => '28-' . ltrim( $month, '0'),
//            ];
//
//            if ( '02' !== $month ){
//                $data['month_days'][$month . '29'] =[
//                    'key' => $month . '29',
//                    'formatted' => ltrim( $month, '0') . '-29',
//                    'formatted_dm' => '29-' . ltrim( $month, '0'),
//                ];
//                $data['month_days'][$month . '30'] =[
//                    'key' => $month . '30',
//                    'formatted' => ltrim( $month, '0') . '-30',
//                    'formatted_dm' => '30-' . ltrim( $month, '0'),
//                ];
//                if ( in_array( $month, ['01', '03', '05', '07', '08', '10', '12']) ) {
//                    $data['month_days'][$month . '31'] =[
//                        'key' => $month . '31',
//                        'formatted' => ltrim( $month, '0') . '-31',
//                        'formatted_dm' => '31-' . ltrim( $month, '0'),
//                    ];
//                }
//            }
//
//        }
//
//        $data['hour_year'] = [];
//        $data['hour_year_list'] = [];
//        $data['quarter_hour_year'] = [];
//        $data['quarter_hour_year_list'] = [];
//        $data['half_hour_year'] = [];
//        $data['half_hour_year_list'] = [];
//        foreach( $data['month_days'] as $month_key => $month_day ){
//            foreach ( $data['quarter_hour'] as $quarter_hour_key => $quarter_hour ) {
//                $data['quarter_hour_year_list'][] = $month_key . $quarter_hour_key;
//                $data['quarter_hour_year'][$month_key . $quarter_hour_key] = [
//                    'key' => $month_key . $quarter_hour_key,
//                    'formatted' => $month_day['formatted'] . ' ' . $quarter_hour['formatted'],
//                    'formatted_dm' => $month_day['formatted_dm'] . ' ' . $quarter_hour['formatted'],
//                ];
//            }
//            foreach ( $data['half_hour'] as $half_hour_key => $half_hour ) {
//                $data['half_hour_year_list'][] = $month_key . $half_hour_key;
//                $data['half_hour_year'][$month_key . $half_hour_key] = [
//                    'key' => $month_key . $half_hour_key,
//                    'formatted' => $month_day['formatted'] . ' ' . $half_hour['formatted'],
//                    'formatted_dm' => $month_day['formatted_dm'] . ' ' . $half_hour['formatted'],
//                ];
//            }
//            foreach ( $data['hour'] as $hour_key => $hour ) {
//                $data['hour_year_list'][] = $month_key . $hour_key;
//                $data['hour_year'][$month_key . $hour_key] = [
//                    'key' => $month_key . $hour_key,
//                    'formatted' => $month_day['formatted'] . ' ' . $hour['formatted'],
//                    'formatted_dm' => $month_day['formatted_dm'] . ' ' . $hour['formatted'],
//                ];
//            }
//        }
//
//        $data['days_in_each_month'] = [
//            '01' => '31',
//            '02' => '28', // if leap year add one
//            '03' => '31',
//            '04' => '30',
//            '05' => '31',
//            '06' => '30',
//            '07' => '31',
//            '08' => '31',
//            '09' => '30',
//            '10' => '31',
//            '11' => '30',
//            '12' => '31',
//        ];
//
////        wp_cache_set(__METHOD__, $data );
//
//        return $data;
//    }
//
//    public static function quarter_hours_for_month( string $month = null ) {
//        if ( empty( $month ) ) {
//            $month = gmdate('m');
//        }
//        if ( ! is_numeric( $month ) ) {
//            return new WP_Error(__METHOD__, 'Must be numeric');
//        }
//        if ( strlen( $month ) !== 2 ) {
//            return new WP_Error(__METHOD__, 'Must two digit month');
//        }
//
//        $time_lists = self::times_lists();
//
//        $dates = [];
//        foreach( $time_lists['quarter_hour_year'] as $key => $value ) {
//            if ( $month === substr( $key, 0, 2 ) ) {
//                $dates[$key] = $value;
//            }
//        }
//
//        return $dates;
//    }
//
//    public static function next_year_nested(){
//        $data = [];
//        $time_list = self::times_lists();
//
//        $date = gmdate('Ymd');
//        $next_year = gmdate('Ymd', strtotime('+30 days') );
//
//        $i = 0;
//        while( $date < $next_year ) {
//
//            $data[$date] = [];
//            foreach( $time_list['quarter_hour'] as $key => $time ) {
//                $data[$date][] = [
//                    'key' => $date . $key,
//                    'formatted' => $time['formatted']
//                ];
//            }
//
//            $i++;
//            $string = '+' . $i . ' days';
//            $date = gmdate('Ymd', strtotime($string) );
//        }
//
////        dt_write_log($data);
//
//        return $data;
//    }
//
//    public static function campaign_schedule( $post_id ) {
//        $data = [];
//        $time_list = self::times_lists();
//
//        $record = DT_Posts::get_post( 'campaigns', $post_id, true, false );
//
//        if ( ! isset( $record['start_date'] ) ){
//            $start = strtotime('-30 days');
//        } else {
//            $start = $record['start_date']['timestamp'];
//        }
//
//        if (! isset( $record['end_date'] ) ) {
//            $end = time();
//        } else {
//            $end = $record['end_date']['timestamp'];
//        }
//
//        $date_start = gmdate('Ymd', $start );
//        $date_end = gmdate('Ymd', $end );
//
//        $i = 0;
//        while( $date_start <= $date_end ) {
//
//            foreach( $time_list['quarter_hour'] as $key => $time ) {
//                if ( ! isset( $data[$date_start] ) ){
//                    $data[$date_start] = [
//                        'key' => $date_start,
//                        'formatted' => gmdate('F d', strtotime( substr( $date_start, 0, 4) . '-' . substr( $date_start, 4, 2) . '-' . substr( $date_start, -2, 2)  ) ),
//                        'percent' => rand(0, 100),
//                        'hours' => []
//                    ];
//                }
//
//                $data[$date_start]['hours'][] = [
//                    'key' => $date_start . $key,
//                    'formatted' => $time['formatted'],
//                    'subscribers' => rand(0,2),
//                ];
//            }
//
//            $i++;
//            $string = '+' . $i . ' days';
//            $date_start = gmdate('Ymd', strtotime(gmdate( 'Y-m-d', $start ) . ' ' . $string) );
//        }
//
//
//        return $data;
//    }



}
