<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

define( 'WXR_VERSION', '1.2' );

/**
 * Class DT_Generic_Porch_Landing_Tab_Starter_Content
 */
class DT_Porch_Admin_Tab_Starter_Content {
    public $key = 'starter-content';
    public $title = 'Install Prayer Fuel';
    public $porch_dir;

    public function __construct( $porch_dir ) {
        $this->porch_dir = $porch_dir;
    }

    public function content() {

        ?>
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

    public function main_column() {

        $fields = DT_Campaign_Landing_Settings::get_campaign();

        if ( !empty( $fields ) ) {
            do_action( 'dt_prayer_campaigns_admin_install_fuel' );
            $this->upload_prayer_content_box();
            $this->export_prayer_content_box();
        } else {
            DT_Porch_Admin_Tab_Base::message_box( 'Upload Prayer Fuel', 'You need to select a campaign to start importing prayer fuel' );
        }

    }

    private function get_post_language_start_date() {
        $importer = DT_Campaign_Prayer_Post_Importer::instance();

        $post_start_date = $importer->start_posting_from_date();

        return $post_start_date;
    }

    public function upload_prayer_content_box() {
        /* Note: the url must have the import query param in it to trigger the admin system to require the correct files
        for the import to work */
        $prayer_post_importer = DT_Campaign_Prayer_Post_Importer::instance();
        $post_start_date = $this->get_post_language_start_date();

        $all_good_to_go = false;
        $message = '';
        $tmp_file_name = '';
        $invalidurl = false;
        $feeds = null;
        $has_installed_importer_plugin = true;
        if ( !class_exists( 'WP_Import' ) ) {
            $has_installed_importer_plugin = false;
        }

        if ( isset( $_POST['install_from_file_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['install_from_file_nonce'] ) ), 'install_from_file' ) ) {

            $max_file_size = 1024 * 1024 * 5; // 5MB

            if ( isset( $_POST['append_date'] ) ) {
                $prayer_post_importer->set_append_date( sanitize_text_field( wp_unslash( $_POST['append_date'] ) ) );
            }


            if ( !empty( $_FILES['file']['tmp_name'] ) ) {
                $file = !empty( $_FILES['file']['tmp_name'] ) ? dt_recursive_sanitize_array( wp_unslash( $_FILES['file'] ) ) : null;

                $tmp_file_name = $file['tmp_name'];

                if ( in_array( $file['type'], [ 'application/xml', 'text/xml' ], true ) ) {
                    $all_good_to_go = true;
                } else {
                    $message = 'Please upload an xml file';
                }
                if ( $all_good_to_go && $file['size'] < $max_file_size ) {
                    $all_good_to_go = true;
                } else {
                    $message = 'File size is too large.';
                }
            }

            if ( isset( $_POST['rss-feed-url'] ) ) {
                $rss_feed_url = sanitize_text_field( wp_unslash( $_POST['rss-feed-url'] ) );

                libxml_use_internal_errors( true );
                $feeds = simplexml_load_file( $rss_feed_url );

                $errors = libxml_get_errors();


                if ( !empty( $feeds ) ) {
                    $all_good_to_go = true;
                    /* write the data to a file */
                    $tmp_file = tmpfile();

                    $data = $feeds->asXML();

                    fwrite( $tmp_file, $data );

                    $tmp_file_name = stream_get_meta_data( $tmp_file )['uri'];
                } else {
                    $invalidurl = true;
                }
            }

            if ( $all_good_to_go ) {
                ob_start();

                ( new WP_Import() )->import( $tmp_file_name );

                $import_output = ob_get_contents();
                ob_end_clean();

                $post_start_date = $this->get_post_language_start_date();

            }

            if ( isset( $tmp_file ) ) {
                fclose( $tmp_file );
            }
        }

        ?>


        <div class='wrap'>
            <div id='poststuff'>
                <div id='post-body' class='metabox-holder columns-2'>
                    <div id='post-body-content'>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'install_from_file', 'install_from_file_nonce' ) ?>
            <!-- Box -->
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Import Prayer Fuel Posts</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            Documentation
                        </td>
                        <td>
                            <a href='https://pray4movement.org/docs/exporting-and-importing/' target='_blank'>Click here to
                                view documentation</a>
                        </td>
                    </tr>

                    <?php if ( !$has_installed_importer_plugin ): ?>
                        <tr>
                            <td>
                                <p>
                                    In order to import prayer fuel you need to install/activate the Wordpress Installer plugin
                                </p>
                                <a class="button" href="plugins.php?page=tgmpa-install-plugins">Click here to fix this</a>
                            </td>
                            <td></td>
                        </tr>

                    <?php else : ?>

                        <tr>
                            <td>
                                <input id="install_from_file_append_date" name="append_date" type="date" value="<?php echo esc_html( gmdate( 'Y-m-d', strtotime( $post_start_date ) ) ) ?>" />
                                <p id="install_from_file_append_date_text">
                                    Posts will automatically be scheduled to start from the date
                                    <strong><?php echo esc_html( gmdate( 'd M Y', strtotime( $post_start_date ) ) ) ?></strong>
                                </p>
                            </td>
                            <td>
                                What date should the imported posts be scheduled to start at:

                                <p>
                                    If the campaign has had posts imported previously, then they will be appended to the last scheduled post.
                                    If no posts have been imported yet, then it will either schedule the posts to start today or the first day of the campaign, whichever comes last.
                                </p>
                            </td>
                        </tr>
<!--                        <tr>-->
<!--                            <td>-->
<!--                                <input id="rss-feed-url" name="rss-feed-url" type="text" placeholder="RSS Feed URL">-->
<!--                                --><?php //if ( $invalidurl ): ?>
<!---->
<!--                                    <p>-->
<!--                                        The url is invalid-->
<!--                                    </p>-->
<!---->
<!--                                --><?php //endif; ?>
<!---->
<!--                                --><?php //if ( $feeds && empty( $feeds ) ): ?>
<!---->
<!--                                    <p>-->
<!--                                        The feed is empty-->
<!--                                    </p>-->
<!---->
<!--                                --><?php //endif; ?>
<!--                            </td>-->
<!--                            <td>-->
<!--                                <label for="rss-reed-url">RSS Feed URL</label>-->
<!--                            </td>-->
<!--                        </tr>-->
                        <tr>
                            <td>
                                <input name="file" type="file" accept="application/xml text/xml">
                                <?php if ( $message !== '' ): ?>

                                    <p>
                                        <?php echo esc_html( $message ) ?>
                                    </p>

                                <?php endif; ?>
                            </td>
                            <td>
                                The xml file of prayer posts to import.
                            </td>
                        </tr>

                        <?php if ( $all_good_to_go ): ?>

                        <tr>
                            <td>
                                <?php //phpcs:ignore ?>
                                <?php echo $import_output ?>
                            </td>
                        </tr>

                        <?php endif; ?>

                        <tr>
                            <td>
                                <button class="button">Upload</button>
                            </td>
                            <td></td>
                        </tr>

                    <?php endif; ?>

                </tbody>
            </table>
        </form>

                    </div>
                </div>
            </div>
        </div>

        <?php
    }


    public function export_prayer_content_box(){
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
        $languages = DT_Campaign_Languages::get_enabled_languages( $campaign['ID'] );


        global $wpdb;
        $installed_langs_query = $wpdb->get_results( $wpdb->prepare("
            SELECT pm.meta_value, count(*) as count
            FROM $wpdb->posts p
            LEFT JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_id AND meta_key = 'post_language' )
            INNER JOIN $wpdb->postmeta pm2 ON ( p.ID = pm2.post_id AND pm2.meta_key = 'linked_campaign' AND pm2.meta_value = %d )
            WHERE post_type = 'landing' and ( post_status = 'publish' or post_status = 'future')
            GROUP BY pm.meta_value
        ", $campaign['ID'] ), ARRAY_A );
        $installed_langs = [];
        foreach ( $installed_langs_query as $result ){
            if ( $result['meta_value'] === null ){
                $result['meta_value'] = 'en_US';
            }
            if ( !isset( $installed_langs[$result['meta_value']] ) ){
                $installed_langs[$result['meta_value']] = 0;
            }
            $installed_langs[$result['meta_value']] += $result['count'];
        }

        ?>
        <div class='wrap'>
            <div id='poststuff'>
                <div id='post-body' class='metabox-holder columns-2'>
                    <div id='post-body-content'>

        <form method = 'post' action="<?php echo esc_html( plugin_dir_url( __FILE__ ) . 'export.php' ); ?>">
            <?php wp_nonce_field( 'export_from_file', 'export_from_file_nonce' ) ?>
            <input type="hidden" name="linked_campaign" value="<?php echo esc_html( $campaign['ID'] ); ?>">
            <input type="hidden" name="campaign_name" value="<?php echo esc_html( $campaign['title'] ); ?>">



            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Export Prayer Fuel Posts</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        Documentation: <a href='https://pray4movement.org/docs/exporting-and-importing/' target='_blank'>Click
                            here to view documentation</a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table class="">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Installed Posts</th>
                                <th>Export</th>
                            </tr>
                            </thead>
                            <tbody>

                            <?php foreach ( $languages as $code => $language ):
                                ?>

                                <tr>
                                    <td><?php echo esc_html( $language['flag'] ) ?> <?php echo esc_html( $language['english_name'] ) ?></td>

                                    <td><?php echo esc_html( $installed_langs[$code] ?? 0 ); ?></td>
                                    <td>
                                        <button class="button export-prayer-fuel" name="language" value="<?php echo esc_html( $code ) ?>" <?php disabled( ( $installed_langs[$code] ?? 0 ) === 0 ) ?>>
                                            Export prayer fuel in <?php echo esc_html( $language['flag'] ) ?>
                                        </button>
                                    </td>

                                </tr>

                            <?php endforeach; ?>
                            </tbody>
                        </table>

                    </td>
                </tr>
                </tbody>
            </table>
        </form>

                    </div>
                </div>
            </div>
        </div>

        <br>
        <!-- End Box -->
        <?php
    }


}
