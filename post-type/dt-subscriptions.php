<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Subscriptions {

    public static function create_subscriber( $campaign_id, $email, $title, $times, $args = [] ) {
        $args = wp_parse_args(
            $args,
            [
                'receive_prayer_time_notifications' => false,
                "timezone" => ""
            ]
        );

        $user = wp_get_current_user();
        $user->add_cap( 'create_subscriptions' );

        $hash = dt_create_unique_key();


        $key_name = 'public_key';
        if ( method_exists( "DT_Magic_URL", "get_public_key_meta_key" ) ){
            $key_name = DT_Magic_URL::get_public_key_meta_key( "subscriptions_app", "manage" );
        }
        $fields = [
            'title' => $title,
            "contact_email" => [
                [ "value" => $email ],
            ],
            'campaigns' => [
                "values" => [
                    [ "value" => $campaign_id ],
                ],
            ],
            'timezone' => $args["timezone"],
            $key_name => $hash,
            'receive_prayer_time_notifications' => $args["receive_prayer_time_notifications"],
        ];


        // create post
        $new_subscriber = DT_Posts::create_post( 'subscriptions', $fields, true );
        if ( is_wp_error( $new_subscriber ) ) {
            return $new_subscriber;
        }

        $added_reports = self::add_subscriber_times( $campaign_id, $new_subscriber["ID"], $times );
        if ( is_wp_error( $added_reports ) ){
            return $added_reports;
        }
        return $new_subscriber["ID"];
    }


    /**
     * @param $campaign_id
     * @param $subscription_id
     * @param $times
     * @return bool|WP_Error
     */
    public static function add_subscriber_times( $campaign_id, $subscription_id, $times){
        foreach ( $times as $time ){
            if ( !isset( $time["time"] ) ){
                continue;
            }
            $new_report = self::add_subscriber_time( $campaign_id, $subscription_id, $time["time"], $time["duration"], $time['grid_id'] );
            if ( !$new_report ){
                return new WP_Error( __METHOD__, "Sorry, Something went wrong", [ 'status' => 400 ] );
            }
        }
        return true;
    }


    /**
     * @param $campaign_id
     * @param $subscription_id
     * @param $time
     * @param $duration
     * @param null $location_id
     * @return false|int|WP_Error
     */
    public static function add_subscriber_time( $campaign_id, $subscription_id, $time, $duration, $location_id = null ){

        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        if ( is_wp_error( $campaign ) ){
            return new WP_Error( __METHOD__, "Sorry, Something went wrong", [ 'status' => 400 ] );
        }

        if ( empty( $location_id ) && isset( $campaign["location_grid"][0]['id'] ) ){
            $location_id = $campaign["location_grid"][0]['id'];
        }

        $duration_mins = 15;
        if ( is_numeric( $duration ) ){
            $duration_mins = $duration;
        }
        $args = [
            'parent_id' => $campaign_id,
            'post_id' => $subscription_id,
            'post_type' => 'subscriptions',
            'type' => 'campaign_app',
            'subtype' => $campaign["type"]["key"],
            'payload' => null,
            'value' => 0,
            'lng' => null,
            'lat' => null,
            'level' => null,
            'label' => null,
            'grid_id' => $location_id,
            'time_begin' => $time,
            'time_end' => $time + $duration_mins * 60,
        ];

        $grid_row = Disciple_Tools_Mapping_Queries::get_by_grid_id( $location_id );
        if ( ! empty( $grid_row ) ){
            $full_name = Disciple_Tools_Mapping_Queries::get_full_name_by_grid_id( $location_id );
            $args['lng'] = $grid_row['longitude'];
            $args['lat'] = $grid_row['latitude'];
            $args['level'] = $grid_row['level_name'];
            $args['label'] = $full_name;
        }
        $new_report = Disciple_Tools_Reports::insert( $args );

        $label = "Commitment added: " . gmdate( 'F d, Y @ H:i a', $args['time_begin'] ) . ' UTC for ' . $duration_mins . ' minutes';
        dt_activity_insert([
            'action' => 'add_subscription',
            'object_type' => $args["post_type"], // If this could be contacts/groups, that would be best
            'object_subtype' => 'report',
            'object_note' => $label,
            'object_id' => $args["post_id"]
        ] );

        return $new_report;

    }


}
