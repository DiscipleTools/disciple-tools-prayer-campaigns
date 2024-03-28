<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Generic_Porch_Landing_Tab_Starter_Content
 */
class DT_Porch_Admin_Tab_Email_Settings extends DT_Porch_Admin_Tab_Base {
    public $key = 'campaign_email';
    public $title = 'Email Settings';

    public function __construct( string $porch_dir ) {
        parent::__construct( $this->key, $porch_dir );
    }

    public function body_content() {

        $this->main_column();
    }

    public function content() {

        ?>
        <style>
            .metabox-table input {
                /*width: 100%;*/
            }
            .metabox-table select {
                width: 100%;
            }
            .metabox-table textarea {
                width: 100%;
                height: 100px;
            }
        </style>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column(); ?>

                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
