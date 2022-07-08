<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Generic_Porch_Landing_Tab_Starter_Content
 */
class DT_Porch_Admin_Tab_Starter_Content {
    public $key = 'starter-content';
    public $title = 'Install Prayer Content';
    public $porch_dir;

    public function __construct( $porch_dir ) {
        $this->porch_dir = $porch_dir;

        require_once( trailingslashit( __DIR__ ) . '../classes/dt-campaign-porch-starter-content.php' );
    }

    public function content() {

        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php
                        DT_Porch_Admin_Tab_Base::box_campaign();

                        $fields = DT_Campaign_Settings::get_campaign();
                        if ( ! empty( $fields ) ) {
                            $this->main_column();
                        }

                        ?>

                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function main_column() {

        $this->starter_content_box();
        $this->upload_prayer_content_box();

    }

    public function starter_content_box() {
        global $wpdb;
        if ( isset( $_POST['install_content_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['install_content_nonce'] ) ), 'install_content' ) ) {
            $language = null;
            if ( isset( $_POST["install_content_language"] ) ){
                $language = sanitize_text_field( wp_unslash( $_POST["install_content_language"] ) );
            }
            $from_translation = null;
            if ( isset( $_POST["install_content_language_english"] ) ){
                $language = sanitize_text_field( wp_unslash( $_POST["install_content_language_english"] ) );
                $from_translation = 'en_US';
            }
            if ( $language ){
                $default_people_name = isset( $_POST[ $language . "_people_plural_masculine"] ) ? sanitize_text_field( wp_unslash( $_POST[ $language . "_people_plural_masculine"] ) ) : null;
                $args = [
                    "location_name" => isset( $_POST[ $language . "_location_name"] ) ? sanitize_text_field( wp_unslash( $_POST[ $language . "_location_name"] ) ) : null,
                    "people_singular_masculine" => isset( $_POST[ $language . "_people_singular_masculine"] ) ? sanitize_text_field( wp_unslash( $_POST[ $language . "_people_singular_masculine"] ) ) : $default_people_name,
                    "people_singular_feminine" => isset( $_POST[ $language . "_people_singular_feminine"] ) ? sanitize_text_field( wp_unslash( $_POST[ $language . "_people_singular_feminine"] ) ) : $default_people_name,
                    "people_plural_masculine" => $default_people_name,
                    "people_plural_feminine" => isset( $_POST[ $language . "_people_plural_feminine"] ) ? sanitize_text_field( wp_unslash( $_POST[ $language . "_people_plural_feminine"] ) ) : $default_people_name,
                ];

                DT_Campaign_Porch_Starter_Content::load_content( $language, $args, $from_translation );
            }

            if ( isset( $_POST["delete_duplicates"] ) ){
                $wpdb->query( "
                    DELETE t1 FROM $wpdb->posts t1
                    INNER JOIN $wpdb->posts t2
                    LEFT JOIN $wpdb->postmeta t1m ON ( t1m.post_ID = t1.ID and t1m.meta_key = 'post_language')
                    LEFT JOIN $wpdb->postmeta t2m ON ( t2m.post_ID = t2.ID and t2m.meta_key = 'post_language')
                    WHERE t1.ID > t2.ID
                    AND t1.post_title = t2.post_title
                    AND ( t1m.meta_value = t2m.meta_value OR ( t1m.meta_value IS NULL AND t2m.meta_value IS NULL ) )
                    AND t1.post_type = 'landing' AND t2.post_type = 'landing'
                    AND ( t1.post_status = 'publish' OR t1.post_status = 'future' )
                    AND ( t2.post_status = 'publish' OR t2.post_status = 'future' )
                ");
            }

            if ( isset( $_POST["delete_posts"] ) ){
                $language = sanitize_text_field( wp_unslash( $_POST["delete_posts"] ) );
                $wpdb->query( $wpdb->prepare( "
                    DELETE t1 FROM $wpdb->posts t1
                    LEFT JOIN $wpdb->postmeta t1m ON ( t1m.post_ID = t1.ID and t1m.meta_key = 'post_language')
                    WHERE t1m.meta_value = %s
                    AND ( t1.post_status = 'publish' OR t1.post_status = 'future' )
                    AND t1.post_type = 'landing'
                ", $language ) );
                if ( $language === "en_US" ){
                    $wpdb->query( "
                        DELETE t1 FROM $wpdb->posts t1
                        LEFT JOIN $wpdb->postmeta t1m ON ( t1m.post_ID = t1.ID and t1m.meta_key = 'post_language')
                        WHERE t1m.meta_value IS NULL
                        AND ( t1.post_status = 'publish' OR t1.post_status = 'future' )
                        AND t1.post_type = 'landing'
                    " );
                }
            }
        }
        $languages = dt_campaign_list_languages();
        $fields = DT_Porch_Settings::settings();

        $installed_langs_query = $wpdb->get_results( "
            SELECT pm.meta_value, count(*) as count
            FROM $wpdb->posts p
            LEFT JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_id AND meta_key = 'post_language' )
            WHERE post_type = 'landing' and ( post_status = 'publish' or post_status = 'future')
            GROUP BY meta_value
        ", ARRAY_A);
        $installed_langs = [];
        foreach ( $installed_langs_query as $result ){
            if ( $result["meta_value"] === null ){
                $result["meta_value"] = 'en_US';
            }
            if ( !isset( $installed_langs[$result["meta_value"]] ) ){
                $installed_langs[$result["meta_value"]] = 0;
            }
            $installed_langs[$result["meta_value"]] += $result["count"];
        }

        ?>
        <form method="post">
            <?php wp_nonce_field( 'install_content', 'install_content_nonce' ) ?>
            <!-- Box -->
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Install Starter 24/7 Content</th>
                </tr>
                </thead>
                <tbody>
                    <tr><td>
                            <p>See Prayer Fuel instructions <a href="https://github.com/Pray4Movement/pray4ramadan-porch/wiki/Prayer-fuel" target="_blank">here</a> </p>
                    <table>
                        <thead>
                        <tr>
                            <th>Language</th>
                            <th>Installed Posts</th>
                            <th>Location Name</th>
<!--                            <th>People Singular Masculine</th>-->
<!--                            <th>People Singular Feminine</th>-->
                            <th>People Plural Masculine</th>
                            <th>People Plural Feminine</th>
                            <th>Install</th>
                            <th>Install in English</th>
                            <th>Delete Posts</th>
                            <th>Duplicates</th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php foreach ( $languages as $language_key => $language ) :
                            $people_name = DT_Porch_Settings::get_field_translation( "people_name", $language_key );
                            $country_name = DT_Porch_Settings::get_field_translation( "country_name", $language_key );
                            $already_installed = ( $installed_langs[$language_key] ?? 0 ) > 0;
                            $available_in_language = $language["prayer_fuel"] ?? false;
                            $delete_enabled = ( $installed_langs[$language_key] ?? 0 ) > 1
                            ?>
                            <tr>
                                <td><?php echo esc_html( $language["flag"] ); ?> <?php echo esc_html( $language['english_name'] ); ?></td>
                                <td><?php echo esc_html( $installed_langs[$language_key] ?? 0 ); ?></td>
                                <td><input style="width:150px" name="<?php echo esc_html( $language_key ); ?>_location_name" value="<?php echo esc_html( $country_name ); ?>"></td>
<!--                                <td><input style="width:150px" name="--><?php //echo esc_html( $language_key ); ?><!--_people_singular_masculine" value="--><?php //echo esc_html( $people_name ); ?><!--"></td>-->
<!--                                <td><input style="width:150px" name="--><?php //echo esc_html( $language_key ); ?><!--_people_singular_feminine" value="--><?php //echo esc_html( $people_name ); ?><!--"></td>-->
                                <td><input style="width:150px" name="<?php echo esc_html( $language_key ); ?>_people_plural_masculine" value="" autocomplete="off"></td>
                                <td><input style="width:150px" name="<?php echo esc_html( $language_key ); ?>_people_plural_feminine" value="" autocomplete="off"></td>
                                <td>
                                    <button type="submit" name="install_content_language" class="button" value="<?php echo esc_html( $language_key ); ?>" <?php disabled( $already_installed || !$available_in_language ) ?>>
                                        Install 24/7 Starter Content
                                    </button>
                                </td>
                                <td>
                                    <button type="submit" name="install_content_language_english" class="button" value="<?php echo esc_html( $language_key ); ?>" <?php disabled( $already_installed ) ?>>
                                        Install Content in English
                                    </button>
                                </td>
                                <td>
                                    <button type="button" onclick="jQuery('#confirm-delete-<?php echo esc_html( $language_key ); ?>').show()" class="button" <?php disabled( !$delete_enabled ) ?>>
                                        Delete all <?php echo esc_html( $language["flag"] ); ?>
                                    </button>
                                    <div id="confirm-delete-<?php echo esc_html( $language_key ); ?>" style="display: none">
                                        Are you sure? <button type="submit" class="button button-primary" name="delete_posts" value="<?php echo esc_html( $language_key ); ?>">Yes</button>
                                        <button type="button" class="button button-secondary" onclick="jQuery('#confirm-delete-<?php echo esc_html( $language_key ); ?>').hide()">No</button>
                                    </div>

                                </td>
                                <td>
                                    <?php if ( ( $installed_langs[$language_key] ?? 0 ) > 60 ) :?>
                                        <button type="submit" name="delete_duplicates" value="<?php echo esc_html( $language_key ); ?>">Delete Duplicates?</button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </td></tr>
                </tbody>
            </table>
            <br>
            <!-- End Box -->
        </form>
        <?php
    }

    public function upload_prayer_content_box() {
        /* Note: the url must have the import query param in it to trigger the admin system to require the correct files
        for the import to work */

        $all_good_to_go = false;
        $message = "";
        $tmp_file = "";
        if ( isset( $_POST['install_from_file_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['install_from_file_nonce'] ) ), 'install_from_file' ) ) {

            $max_file_size = 1024 * 1024 * 5;

            $append_date = isset( $_POST['append_date'] ) ? sanitize_text_field( wp_unslash( $_POST['append_date'] ) ) : "";

            if ( empty( $append_date ) ) {
                $append_date = dt_find_latest_prayer_fuel_date();
            }

            $file = isset( $_FILES['file'] ) ? dt_recursive_sanitize_array( wp_unslash( $_FILES['file'] ) ) : '';

            $tmp_file = $file["tmp_name"];

            if ( in_array( $file["type"], [ "application/xml", "text/xml" ], true ) ) {
                $all_good_to_go = true;
            } else {
                $message = "Please upload an xml file";
            }
            if ( $all_good_to_go && $file["size"] < $max_file_size ) {
                $all_good_to_go = true;
            } else {
                $message = "File size is too large.";
            }
        }

        ?>


        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'install_from_file', 'install_from_file_nonce' ) ?>
        <!-- Box -->
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Install Prayer Fuel Posts</th>
                </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <!-- When the date changes the import date needs to change globally -->
                            <input name="append_date" type="date" />
                        </td>
                        <td>
                            What date should the imported posts be scheduled to start at:
                            <!-- Put in something here to do the query for when it is going to append it to -->
                            <!-- If a date isn't used it will use that date, or otherwise the date that is given. -->
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input name="file" type="file" accept="application/xml text/xml" required>
                            <?php if ( $message !== "" ): ?>

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
                            <?php ( new WP_Import() )->import( $tmp_file ); ?>
                        </td>
                    </tr>

                    <?php endif; ?>

                   <tr>
                        <td>
                            <button class="button">Upload</button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </form>


        <?php
    }
}
