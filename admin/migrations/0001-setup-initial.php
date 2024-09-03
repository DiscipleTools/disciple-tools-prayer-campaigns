<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Prayer_Campaign_Migration_0001
 */
class DT_Prayer_Campaign_Migration_0001 extends DT_Prayer_Campaign_Migration {
    /**
     * @throws \Exception  Got error when creating table $name.
     */
    public function up() {
        $to_setup = get_option( 'p4m_porch_type_to_set_up' );
        if ( !empty( $to_setup ) ){
            require_once( DT_Prayer_Campaigns::instance()->plugin_dir_path . 'admin/dt-porch-admin-tab-general-settings.php' );
            $site_name = get_bloginfo( 'name' );
            $campaign_details = get_option( 'pt_campaign' );
            DT_Prayer_Campaigns_Campaigns::setup_wizard_for_type( $to_setup, $site_name, $campaign_details );
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
