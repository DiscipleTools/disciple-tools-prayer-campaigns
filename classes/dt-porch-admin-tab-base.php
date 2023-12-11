<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Base class to create a porch admin tab
 */
class DT_Porch_Admin_Tab_Base {

    private $theme_file_dir;
    private string $tab;

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

                        <?php $this->body_content() ?>

                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function body_content() {
        $this->main_column();
    }

    public static function translation_cell( $langs, $key, $field, $form_name ){
        ?>
        <button class="button small expand_translations" data-form_name="<?php echo esc_html( $form_name ) ?>">
            <?php
            $number_of_translations = 0;
            foreach ( $langs as $code => $val ){
                if ( !empty( $field['translations'][$code] ) ){
                    $number_of_translations++;
                }
            }
            ?>
            <img style="height: 15px; vertical-align: middle" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/languages.svg' ); ?>">
            (<?php echo esc_html( $number_of_translations ); ?>)
        </button>
        <div class="translation_container hide">
            <table style="width:100%">
                <?php foreach ( $langs as $code => $val ) : ?>
                    <tr>
                        <td><label for="field_key_<?php echo esc_html( $key )?>_translation-<?php echo esc_html( $code )?>"><?php echo esc_html( $val['native_name'] )?></label></td>
                        <?php if ( isset( $field['type'] ) && $field['type'] === 'textarea' ) :?>
                            <td><textarea name="field_key_<?php echo esc_html( $key )?>_translation-<?php echo esc_html( $code )?>"><?php echo wp_kses_post( $field['translations'][$code] ?? '' );?></textarea></td>
                        <?php else : ?>
                            <td><input name="field_key_<?php echo esc_html( $key )?>_translation-<?php echo esc_html( $code )?>" type="text" value="<?php echo esc_html( $field['translations'][$code] ?? '' );?>"/></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php
    }

    public function main_column() {
        $allowed_tags = $this->get_allowed_tags();

        $campaign = DT_Campaign_Landing_Settings::get_campaign( null, true );
        $langs = DT_Campaign_Languages::get_enabled_languages( $campaign['ID'] );
        $campaign_settings = DT_Porch_Settings::settings();

        if ( isset( $_POST['generic_porch_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['generic_porch_settings_nonce'] ) ), 'generic_porch_settings' ) ) {

            if ( isset( $_POST['list'] ) ) {
                $post_list = dt_recursive_sanitize_array( $_POST['list'] );
                $allowed_tags = $this->get_allowed_tags();

                //Keep line breaks by using wp_kses to sanitize.
                foreach ( $post_list as $field_key => $value ){
                    if ( isset( $campaign_settings[$field_key]['type'], $_POST['list'][$field_key] ) && $campaign_settings[$field_key]['type'] === 'textarea' ){
                        $post_list[$field_key] = wp_kses( wp_unslash( $_POST['list'][$field_key] ), $allowed_tags );
                    }
                }
                DT_Porch_Settings::update_values( $post_list );

                $new_translations = $this->get_new_translations( $_POST );
                DT_Porch_Settings::update_translations( $campaign['ID'], $new_translations );
                DT_Posts::get_post( 'campaigns', $campaign['ID'], false );
            }
            //refresh the campaign
            $campaign = DT_Posts::get_post( 'campaigns', $campaign['ID'], false, true );
            $campaign_settings = DT_Porch_Settings::settings( null, null, false );
        }

        foreach ( DT_Porch_Settings::sections( $this->tab ) as $section ): ?>
            <?php if ( empty( $section ) ) {
                $section_name = 'Other';
            } else {
                $section_name = $section;
            } ?>

            <form method="post" class="metabox-table" name="<?php echo esc_html( $section ) ?>">
                <?php wp_nonce_field( 'generic_porch_settings', 'generic_porch_settings_nonce' ) ?>
                <!-- Box -->
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <th style="width:20%"><?php echo esc_html( $section_name ) ?> Content</th>
                        <th style="width:50%"></th>
                        <th style="width:30%"></th>
                    </tr>
                    </thead>
                    <tbody>

                        <?php foreach ( $campaign_settings as $key => $field ) :
                            if ( ( $field['tile'] ?? '' ) !== $this->tab || ( $field['campaign_section'] ?? '' ) !== $section ){
                                continue;
                            }
                            if ( isset( $field['enabled'] ) && $field['enabled'] === false ){
                                continue;
                            }
                            if ( $key === 'default_language' ) : ?>
                                <?php $enabled_languages = DT_Campaign_Languages::get_enabled_languages( $campaign['ID'] );
                                ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html( $field['name'] ); ?>
                                    </td>
                                    <td>
                                        <select name="list[<?php echo esc_html( $key ); ?>]">
                                            <?php
                                            $selected_option = isset( $campaign[$key] ) ? $campaign[$key] : '';
                                            if ( !empty( $selected_option ) && isset( $enabled_languages[$selected_option] ) ) {
                                                ?>
                                                <option value="<?php echo esc_attr( $selected_option ) ?>"><?php echo esc_html( $enabled_languages[$selected_option]['native_name'] ) ?></option>
                                                <option disabled>-----</option>
                                                <?php
                                            }
                                            foreach ( $enabled_languages as $list_key => $value ) {
                                                ?>
                                                <option value="<?php echo esc_attr( $list_key ) ?>"><?php echo esc_attr( $value['native_name'] ) ?></option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                </tr>
                            <?php elseif ( !isset( $field['type'] ) || in_array( $field['type'], [ 'text', 'color' ] ) ) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html( $field['name'] ); ?>
                                    </td>
                                    <td>
                                        <?php if ( !empty( $field['default'] ) && isset( $field['translations'] ) ) : ?>
                                            <h3>Default translated text:</h3>
                                            <?php echo nl2br( esc_html( $field['default'] ) ); ?>
                                            <br><br>
                                        <?php elseif ( !empty( $field['default'] ) ): ?>
                                            <strong>Default: </strong>
                                            <?php echo nl2br( esc_html( $field['default'] ) ); ?>
                                            <br>
                                        <?php endif;
                                        if ( isset( $field['translations'] ) ) : ?>
                                            <p>Set a custom text for all languages (below) or click the
                                                <img style="height: 15px; vertical-align: middle" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/languages.svg' ); ?>">
                                                button to set a value for each language:</p>
                                        <?php endif; ?>
                                        <input style="width: 100%" type="<?php echo esc_html( $field['type'] ); ?>" name="list[<?php echo esc_html( $key ); ?>]" id="<?php echo esc_html( $key ); ?>" value="<?php echo esc_html( $campaign[$key] ?? '' ); ?>" placeholder="<?php echo esc_html( $field['description'] ?? $field['name'] ); ?>"/>
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <?php if ( isset( $field['translations'] ) ){
                                            self::translation_cell( $langs, $key, $field, $section_name );
                                        } ?>
                                    </td>
                                </tr>
                            <?php elseif ( 'textarea' === $field['type'] ) : ?>
                                <?php self::textarea( $langs, $key, $field, $section_name, $allowed_tags, $campaign[$key] ?? '' ) ?>

                            <?php elseif ( 'icon' === $field['type'] ) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html( $field['name'] ); ?>
                                    </td>
                                    <td>
                                    <?php if ( !empty( $field['default'] ) ) : ?>
                                        <h3>Default icon:</h3>
                                        <img class="color-img" style="height: 40px; margin-top:10px"  src="<?php echo esc_html( $field['default'] ); ?>" />
                                        <br><br>
                                    <?php endif; ?>
                                    <input style="width: 100%" type="text" name="list[<?php echo esc_html( $key ); ?>]" id="<?php echo esc_html( $key ); ?>" value="<?php echo esc_html( $campaign[$key] ?? '' ); ?>" placeholder="<?php echo esc_html( $field['description'] ?? $field['name'] ); ?>"/>
                                </td>
                                <td style="vertical-align: middle;">
                                    <?php if ( isset( $field['translations'] ) ){
                                        self::translation_cell( $langs, $key, $field, $section_name );
                                    } ?>
                                </td>
                                </tr>


                            <?php elseif ( 'key_select' === $field['type'] ) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html( $field['name'] ); ?>
                                    </td>
                                    <td>
                                        <select name="list[<?php echo esc_html( $key ); ?>]">
                                            <?php
                                            $selected_option = isset( $campaign[$key]['key'] ) ? $campaign[$key]['key'] : '';
                                            if ( !empty( $selected_option ) && isset( $field['default'][$selected_option] ) ) {
                                                ?>
                                                <option value="<?php echo esc_attr( $selected_option ) ?>"><?php echo esc_html( $field['default'][$selected_option]['label'] ) ?? ''?></option>
                                                <option disabled>-----</option>
                                                <?php
                                            }
                                            foreach ( $field['default'] as $list_key => $value ) {
                                                ?>
                                                <option value="<?php echo esc_attr( $list_key ) ?>"><?php echo esc_attr( $value['label'] ) ?></option>
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

    /**
     * Display a field text area with translation as a row in a table
     *
     * @param array $langs The languages available for translation code => array( of details )
     * @param string $key The field key name
     * @param array $field Contains label: field label, default: default text, translations: array( $lang_code => $translation ),
     * @param string $form_name Name of the form to update when the translation modal is closed
     * @param array|string $allowed_tags The html tags allowed in the translation strings
     */
    public static function textarea( $langs, $key, $field, $form_name, $allowed_tags = [], $value = '' ) {
        ?>

        <tr>
            <td>
                <?php echo esc_html( $field['name'] ); ?>
            </td>
            <td>
                <?php if ( !empty( $field['default'] ) && isset( $field['translations'] ) ) : ?>
                    <h3>Default translated text:</h3>
                    <?php echo nl2br( esc_html( $field['default'] ) ); ?>
                    <br><br>
                <?php endif;

                if ( isset( $field['translations'] ) ) : ?>
                    <p>Set a custom text for all languages (below) or click the
                        <img style="height: 15px; vertical-align: middle" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/languages.svg' ); ?>">
                        button to set a value for each language:</p>
                <?php endif; ?>
                <textarea
                    name="list[<?php echo esc_html( $key ); ?>]"
                    id="<?php echo esc_html( $key ); ?>"
                    placeholder="<?php echo esc_html( $field['description'] ?? $field['name'] ); ?>"
                ><?php echo wp_kses( $value ?? '', $allowed_tags ); ?></textarea>
            </td>
            <td style="vertical-align: middle;">
                <?php if ( isset( $field['translations'] ) ){
                    self::translation_cell( $langs, $key, $field, $form_name );
                } ?>
            </td>
        </tr>

        <?php

    }

    public static function message_box( string $title, string $content ) {
        ?>

        <div class="metabox-table">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <td><?php echo esc_html( $title ) ?></td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo esc_html( $content ) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php
    }
    private function get_new_translations( $post ) {
        $campaign_id = DT_Campaign_Landing_Settings::get_campaign_id();
        $fields = DT_Posts::get_post_field_settings( 'campaigns' );
        $allowed_tags = $this->get_allowed_tags();
        $langs = DT_Campaign_Languages::get_enabled_languages( $campaign_id );

        $new_translations = [];
        foreach ( $fields as $field_key => $field ){
            if ( isset( $field['translations'] ) ){
                foreach ( $langs as $lang_code => $lang_values ){
                    if ( isset( $post['field_key_' . $field_key . '_translation-' . $lang_code] ) ){
                        $new_translations[$field_key][$lang_code] = wp_kses( wp_unslash( $post['field_key_' . $field_key . '_translation-' . $lang_code] ), $allowed_tags );
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
            'src' => array(),
        );
        $allowed_tags['a'] = [ 'href' => [], 'title' => [], 'target' => [], 'rel' => [] ];
        $allowed_tags['br'] = [];

        return $allowed_tags;
    }
}
