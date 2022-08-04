<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Prayer_Campaign_Migration_0001
 */
class DT_Prayer_Campaign_Migration_0004 extends DT_Prayer_Campaign_Migration {
    /**
     * @throws \Exception  Got error when creating table $name.
     */
    public function up() {
        $selected_campaign_id = get_option( "pray4ramadan_selected_campaign" );
        $selected_campaign = DT_Campaign_Settings::get_campaign( $selected_campaign_id );

        if ( !$selected_campaign["start_date"]["formatted"] ) {
            return;
        }

        $prayer_fuel_posts = new WP_Query( [
            'post_type' => PORCH_LANDING_POST_TYPE,
            'post_status' => [ 'publish', 'future' ] ,
            'posts_per_page' => -1,
            'orderby' => 'post_date',
            'order' => 'DESC',
        ] );

        foreach ( $prayer_fuel_posts->posts as $post ) {
            $day_in_campaign = DT_Campaign_Settings::diff_days_between_dates( $selected_campaign["start_date"]["formatted"], $post->post_date );
            if ( $day_in_campaign >= 0 ) {
                ++$day_in_campaign;
            }

            add_post_meta( $post->ID, "day", $day_in_campaign );
            add_post_meta( $post->ID, PORCH_LANDING_META_KEY, $day_in_campaign );
        }
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


