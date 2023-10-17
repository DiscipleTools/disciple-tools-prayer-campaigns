<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Prayer_Campaign_Migration_0006
 */
class DT_Prayer_Campaign_Migration_0006 extends DT_Prayer_Campaign_Migration {
    /**
     * @throws \Exception  Got error when creating table $name.
     */
    public function up() {
        // Grab the campaign settings
        $dt_settings = get_site_option( 'dt_campaign_porch_settings' );

        // Isolate the google analytics option if it exists
        $ga_option = empty( $dt_settings['google_analytics'] ) ? false : $dt_settings['google_analytics'];

        // If the site was previously using a google analytics script, grab the id and replace the script with the id
        if ( ! empty( $ga_option ) ) {
            $pattern = '/<script async src="https:\/\/www\.googletagmanager\.com\/gtag\/js\?id=([^"]+)">/';

            if (preg_match($pattern, $ga_option, $matches)) {
                // The value of 'id' will be captured in $matches[1]
                $dt_settings['google_analytics'] = $matches[1];
                update_site_option( 'dt_campaign_porch_settings', $dt_settings );
            } else {
                // If an ID wasn't found, remove whatever was there
                $dt_settings['google_analytics'] = '';
                update_site_option( 'dt_campaign_porch_settings', $dt_settings );
            }
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


