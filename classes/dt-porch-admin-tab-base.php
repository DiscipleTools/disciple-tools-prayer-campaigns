<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Base class to create a porch admin tab
 */
class DT_Porch_Admin_Tab_Base {

    private static $no_campaign_key = "none";

    private $theme_file_dir;

    public function __construct( string $settings_key, string $porch_dir ) {
        $this->tab = $settings_key;

        $this->theme_file_dir = $porch_dir . 'site/css/colors';
    }

    public function content() {
        ?>
        <style>
            .metabox-table input {
                width: 100%;
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

                        <?php self::box_campaign() ?>

                        <?php $this->main_column() ?>

                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public static function box_campaign() {

        if ( isset( $_POST['install_campaign_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['install_campaign_nonce'] ) ), 'install_campaign_nonce' )
            && isset( $_POST['selected_campaign'] )
        ) {
            $campaign_id = sanitize_text_field( wp_unslash( $_POST['selected_campaign'] ) );
            update_option( 'dt_campaign_selected_campaign', $campaign_id === self::$no_campaign_key ? null : $campaign_id );
        }
        $fields = DT_Campaign_Settings::get_campaign();
        if ( empty( $fields ) ) {
            $fields = [ 'ID' => 0 ];
        }

        $campaigns = DT_Posts::list_posts( "campaigns", [ "status" => [ "active", "pre_signup", "inactive" ] ] );
        if ( is_wp_error( $campaigns ) ){
            $campaigns = [ "posts" => [] ];
        }

        ?>
        <style>
            .metabox-table select {
                width: 100%;
            }
        </style>
        <form method="post" class="metabox-table">
            <?php wp_nonce_field( 'install_campaign_nonce', 'install_campaign_nonce' ) ?>
            <!-- Box -->
            <table class="widefat striped">
                <thead>
                <tr>
                    <th style="width:20%">Link Campaign</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            Select Campaign
                        </td>
                        <td>
                            <select name="selected_campaign">
                                <option value=<?php echo esc_html( self::$no_campaign_key ) ?>></option>
                                <?php foreach ( $campaigns["posts"] as $campaign ) :?>
                                    <option value="<?php echo esc_html( $campaign["ID"] ) ?>"
                                        <?php selected( (int) $campaign["ID"] === (int) $fields['ID'] ) ?>
                                    >
                                        <?php echo esc_html( $campaign["name"] ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="button float-right" type="submit">Update</button>
                        </td>
                    </tr>
                    <?php if ( ! empty( $fields['ID'] ) ) : ?>
                        <?php foreach ( $fields as $key => $value ) :
                            if ( in_array( $key, [ 'start_date', 'end_date', 'status' ] ) ) :
                                ?>
                                <tr>
                                    <td><?php echo esc_attr( ucwords( str_replace( '_', ' ', $key ) ) ) ?></td>
                                    <td><?php echo esc_html( ( is_array( $value ) ) ? $value['formatted'] ?? $value['label'] : $value ); ?></td>
                                </tr>
                            <?php endif;
                        endforeach; ?>
                        <tr>
                            <td>Edit Campaign Details</td>
                            <td>
                                <a href="<?php echo esc_html( site_url() . "/campaigns/" . $fields['ID'] ); ?>" target="_blank">Edit Campaign</a>
                            </td>
                        </tr>
                        <tr>
                            <td>See subscribers</td>
                            <td>
                                <a href="<?php echo esc_html( site_url() . "/subscriptions/" ); ?>" target="_blank">See Subscribers</a>
                            </td>
                        </tr>
                        <tr>
                            <td>Export Campaign Subscribers</td>
                            <td><button type="submit" name="download_csv">Download CSV</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <br>
            <!-- End Box -->
        </form>
        <?php
    }

    private function translation_cell( $langs, $key, $field, $form_name ){
        ?>
        <button class="button small expand_translations" data-form_name="<?php echo esc_html( $form_name ) ?>">
            <?php
            $number_of_translations = 0;
            foreach ( $langs as $code => $val ){
                if ( !empty( $field["translations"][$code] ) ){
                    $number_of_translations++;
                }
            }
            ?>
            <img style="height: 15px; vertical-align: middle" src="<?php echo esc_html( get_template_directory_uri() . "/dt-assets/images/languages.svg" ); ?>">
            (<?php echo esc_html( $number_of_translations ); ?>
        </button>
        <div class="translation_container hide">
            <table style="width:100%">
                <?php foreach ( $langs as $code => $val ) : ?>
                    <tr>
                        <td><label for="field_key_<?php echo esc_html( $key )?>_translation-<?php echo esc_html( $code )?>"><?php echo esc_html( $val["native_name"] )?></label></td>
                        <?php if ( $field["type"] === "textarea" ) :?>
                            <td><textarea name="field_key_<?php echo esc_html( $key )?>_translation-<?php echo esc_html( $code )?>"><?php echo wp_kses_post( $field["translations"][$code] ?? "" );?></textarea></td>
                        <?php else : ?>
                            <td><input name="field_key_<?php echo esc_html( $key )?>_translation-<?php echo esc_html( $code )?>" type="text" value="<?php echo esc_html( $field["translations"][$code] ?? "" );?>"/></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php
    }

    public function main_column() {
        $langs = dt_campaign_list_languages();
        $allowed_tags = $this->get_allowed_tags();
        $sections = DT_Porch_Settings::sections( $this->tab );

        $site_colors = $this->get_site_colors();

        if ( isset( $_POST['generic_porch_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['generic_porch_settings_nonce'] ) ), 'generic_porch_settings' ) ) {

            if ( isset( $_POST['list'] ) ) {
                $post_list = dt_recursive_sanitize_array( $_POST['list'] );
                $new_settings = $this->sanitize_settings( $post_list );
                $new_translations = $this->get_new_translations( $_POST );

                DT_Porch_Settings::update_translations( $new_translations );
                DT_Porch_Settings::update_values( $new_settings );
            }

            if ( isset( $_POST['reset_values'] ) ) {
                DT_Porch_Settings::reset();
            }
        }
        ?>
        <?php foreach ( DT_Porch_Settings::sections( $this->tab ) as $section ): ?>

            <form method="post" class="metabox-table" name="<?php echo esc_html( $section ) ?>">
                <?php wp_nonce_field( 'generic_porch_settings', 'generic_porch_settings_nonce' ) ?>
                <!-- Box -->
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <th style="width:20%"><?php echo esc_html( $section === "" ? "Other" : $section ) ?> Content</th>
                        <th style="width:50%"></th>
                        <th style="width:30%"></th>
                    </tr>
                    </thead>
                    <tbody>

                        <?php foreach ( DT_Porch_Settings::settings( $this->tab, $section ) as $key => $field ) :
                            if ( isset( $field["enabled"] ) && $field["enabled"] === false ){
                                continue;
                            }
                            if ( !isset( $field['type'] ) || 'text' === $field['type'] ) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html( $field['label'] ); ?>
                                    </td>
                                    <td>
                                        <?php if ( !empty( $field["default"] ) && isset( $field["translations"] ) ) {
                                            echo '<h3>Default translated text:</h3>';
                                            echo nl2br( esc_html( $field["default"] ) );
                                            echo '<br><br>';
                                        }
                                        if ( isset( $field["translations"] ) ){
                                            echo '<p>Click the <img style="height: 15px; vertical-align: middle" src="' . esc_html( get_template_directory_uri() . "/dt-assets/images/languages.svg" ) . '"> button to set a value for each language. Custom text for all languages: </p>';
                                        }
                                        ?>
                                        <input type="text" name="list[<?php echo esc_html( $key ); ?>]" id="<?php echo esc_html( $key ); ?>" value="<?php echo esc_html( $field['value'] ); ?>" placeholder="<?php echo esc_html( $field['label'] ); ?>"/>
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <?php if ( isset( $field["translations"] ) ){
                                            self::translation_cell( $langs, $key, $field, $section );
                                        } ?>
                                    </td>
                                </tr>
                            <?php elseif ( 'textarea' === $field['type'] ) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html( $field['label'] ); ?>
                                    </td>
                                    <td>
                                        <?php if ( !empty( $field["default"] ) && isset( $field["translations"] ) ) {
                                            echo '<h3>Default translated text:</h3>';
                                            echo nl2br( esc_html( $field["default"] ) );
                                            echo '<br><br>';
                                        }
                                        if ( isset( $field["translations"] ) ){
                                            echo '<p>Click the <img style="height: 15px; vertical-align: middle" src="' . esc_html( get_template_directory_uri() . "/dt-assets/images/languages.svg" ) . '"> button to set a value for each language. Custom text for all languages:</p>';
                                        } ?>
                                        <textarea name="list[<?php echo esc_html( $key ); ?>]" id="<?php echo esc_html( $key ); ?>" placeholder="<?php echo esc_html( $field['label'] ); ?>"><?php echo wp_kses( $field['value'], $allowed_tags ); ?></textarea>
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <?php if ( isset( $field["translations"] ) ){
                                            self::translation_cell( $langs, $key, $field, $section );
                                        } ?>
                                    </td>
                                </tr>
                            <?php elseif ( 'prayer_timer_toggle' === $field['type'] ) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html( $field['label'] ); ?>
                                    </td>
                                    <td>
                                        <select name="list[<?php echo esc_html( $key ); ?>]">
                                        <?php if ( $field['value'] === 'yes' || ! isset( $field['value'] ) || empty( $field['value'] ) ) : ?>
                                            <option value="yes" selected="selected"><?php echo esc_html( __( 'Yes', 'disciple-tools-prayer-campaigns' ) ); ?></option>
                                            <option value="no"><?php echo esc_html( __( 'No', 'disciple-tools-prayer-campaigns' ) ); ?></option>
                                        <?php else : ?>
                                            <option value="yes"><?php echo esc_html( __( 'Yes', 'disciple-tools-prayer-campaigns' ) ); ?></option>
                                            <option value="no" selected="selected"><?php echo esc_html( __( 'No', 'disciple-tools-prayer-campaigns' ) ); ?></option>
                                        <?php endif; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php elseif ( 'default_language_select' === $field['type'] ) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html( $field['label'] ); ?>
                                    </td>
                                    <td>
                                        <select name="list[<?php echo esc_html( $key ); ?>]">
                                            <?php if ( isset( $field['value'] ) && ! empty( $field['value'] ) ) : ?>
                                                <?php
                                                    $default_translation_label = $langs[ $field['value'] ]['native_name'];
                                                ?>
                                                <option value="<?php echo esc_html( $field['value'] ); ?>" selected="selected"><?php echo esc_html( $default_translation_label ); ?></option>
                                                <option disabled>-----</option>
                                            <?php endif; ?>
                                            <?php foreach ( $langs as $code => $lang ) : ?>
                                                <option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $lang['native_name'] ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php elseif ( 'theme_select' === $field['type'] ) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html( $field['label'] ); ?>
                                    </td>
                                    <td>
                                        <select name="list[<?php echo esc_html( $key ); ?>]">
                                            <?php
                                            if ( isset( $field['value'] ) && ! empty( $field['value'] ) ) {
                                                ?>
                                                <option value="<?php echo esc_attr( $field['value'] ) ?>"><?php echo esc_html( $site_colors[$field['value']] ) ?? ''?></option>
                                                <option disabled>-----</option>
                                                <?php
                                            }
                                            foreach ( $site_colors as $list_key => $list_label ) {
                                                ?>
                                                <option value="<?php echo esc_attr( $list_key ) ?>"><?php echo esc_attr( $list_label ) ?></option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <tr>
                            <td colspan="2">
                                <button class="button float-right" type="submit">Update</button>
                            </td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
                <br>
            </form>

            <?php endforeach; ?>

            <?php dt_display_translation_dialog() ?>

        <?php
    }

    private function get_site_colors() {
        $theme_manager = new DT_Porch_Theme();

        $available_themes = $theme_manager->get_available_theme_names( $this->theme_file_dir );

        return $available_themes;
    }

    private function sanitize_settings( $post_list ) {
        $fields = DT_Porch_Settings::fields();
        $allowed_tags = $this->get_allowed_tags();

        /* make any text area stuff safe */
        foreach ( $post_list as $field_key => $value ){
            if ( isset( $fields[$field_key]["type"], $post_list[$field_key] ) && $fields[$field_key]["type"] === "textarea" ){ // if textarea
                $post_list[$field_key] = wp_kses( wp_unslash( $post_list[$field_key] ), $allowed_tags );
            }
        }

        return $post_list;
    }

    private function get_new_translations( $post ) {
        $fields = DT_Porch_Settings::fields();
        $allowed_tags = $this->get_allowed_tags();
        $langs = dt_campaign_list_languages();

        $new_translations = [];
        foreach ( $fields as $field_key => $field ){
            if ( isset( $field["translations"] ) ){
                foreach ( $langs as $lang_code => $lang_values ){
                    if ( isset( $post["field_key_" . $field_key . "_translation-" . $lang_code] ) ){
                        $new_translations[$field_key][$lang_code] = wp_kses( wp_unslash( $post["field_key_" . $field_key . "_translation-" . $lang_code] ), $allowed_tags );
                    }
                }
            }
        }

        return $new_translations;
    }

    private function get_allowed_tags() {
        global $allowed_tags;
        $allowed_tags['script'] = array(
            'async' => array(),
            'src' => array()
        );

        return $allowed_tags;
    }
}
