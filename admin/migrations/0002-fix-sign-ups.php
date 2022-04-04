<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Prayer_Campaign_Migration_0001
 */
class DT_Prayer_Campaign_Migration_0002 extends DT_Prayer_Campaign_Migration {
    /**
     * @throws \Exception  Got error when creating table $name.
     */
    public function up() {
        global $wpdb;
        $wpdb->query( "UPDATE $wpdb->dt_reports r
            set r.value = 1
            WHERE r.post_type = 'subscriptions'
            AND r.subtype = '24hour1'
            AND r.timestamp > 1648774861
            AND r.timestamp < 1648861261
        ");
        $test = "";
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
