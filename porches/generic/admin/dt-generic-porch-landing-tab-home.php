<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

class DT_Generic_Porch_Landing_Tab_Home {
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
                        <!-- Main Column -->

                        <?php $this->box_campaign() ?>

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function box_campaign() {

        if ( isset( $_POST['install_campaign_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['install_campaign_nonce'] ) ), 'install_campaign_nonce' )
            && isset( $_POST['selected_campaign'] )
        ) {
            $campaign_id = sanitize_text_field( wp_unslash( $_POST['selected_campaign'] ) );
            update_option( 'pray4ramadan_selected_campaign', $campaign_id );
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
                                <option value="none"></option>
                                <?php foreach ( $campaigns["posts"] as $campaign ) :?>
                                    <option value="<?php echo esc_html( $campaign["ID"] ) ?>"
                                        <?php selected( (int) $campaign["ID"] === (int) $fields['ID'] ) ?>
                                    >
                                        <?php echo esc_html( $campaign["name"] ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="button" type="submit">Update</button>
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

    private function translation_cell( $langs, $key, $field ){
        ?>
        <button class="button small expand_translations">
            <?php
            $number_of_translations = 0;
            foreach ( $langs as $lang => $val ){
                if ( !empty( $field["translations"][$val['language']] ) ){
                    $number_of_translations++;
                }
            }
            ?>
            <img style="height: 15px; vertical-align: middle" src="<?php echo esc_html( get_template_directory_uri() . "/dt-assets/images/languages.svg" ); ?>">
            (<?php echo esc_html( $number_of_translations ); ?>)
        </button>
        <div class="translation_container hide">
            <table style="width:100%">
                <?php foreach ( $langs as $lang => $val ) : ?>
                    <tr>
                        <td><label for="field_key_<?php echo esc_html( $key )?>_translation-<?php echo esc_html( $val['language'] )?>"><?php echo esc_html( $val['native_name'] )?></label></td>
                        <?php if ( $field["type"] === "textarea" ) :?>
                            <td><textarea name="field_key_<?php echo esc_html( $key )?>_translation-<?php echo esc_html( $val['language'] )?>"><?php echo wp_kses_post( $field["translations"][$val['language']] ?? "" );?></textarea></td>
                        <?php else : ?>
                            <td><input name="field_key_<?php echo esc_html( $key )?>_translation-<?php echo esc_html( $val['language'] )?>" type="text" value="<?php echo esc_html( $field["translations"][$val['language']] ?? "" );?>"/></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php
    }

    public function main_column() {
        global $allowed_tags;
        $allowed_tags['script'] = array(
            'async' => array(),
            'src' => array()
        );
        $fields = DT_Porch_Settings::porch_fields();
        $langs = dt_ramadan_list_languages();
        $dir = scandir( plugin_dir_path( __DIR__ ) . 'site/css/colors' );
        $list = [];
        foreach ( $dir as $file ) {
            if ( substr( $file, -4, 4 ) === '.css' ){
                $f = explode( '.', $file );
                $l_key = $f[0];
                $label = ucwords( $l_key );
                $list[$l_key] = $label;
            }
        }

        if ( isset( $_POST['ramadan_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ramadan_settings_nonce'] ) ), 'ramadan_settings' ) ) {

            if ( isset( $_POST['list'] ) ) {
                $saved_fields = $fields;

                $post_fields = dt_recursive_sanitize_array( $_POST );
                $post_list = dt_recursive_sanitize_array( $_POST['list'] );
                foreach ( $post_list as $field_key => $value ){
                    if ( isset( $saved_fields[$field_key]["type"], $_POST['list'][$field_key] ) && $saved_fields[$field_key]["type"] === "textarea" ){ // if textarea
                        $post_list[$field_key] = wp_kses( wp_unslash( $_POST['list'][$field_key] ), $allowed_tags );
                    }
                }

                foreach ( $post_list as $key => $value ) {
                    if ( ! isset( $saved_fields[$key] ) ) {
                        $saved_fields[$key] = [];
                    }
                    $saved_fields[$key]['value'] = $value;
                }

                foreach ( $fields as $field_key => $field ){
                    if ( isset( $field["translations"] ) ){
                        foreach ( $langs as $lang_code => $lang_values ){
                            if ( isset( $_POST["field_key_" . $field_key . "_translation-" . $lang_code] ) ){
                                $saved_fields[$field_key]["translations"][$lang_code] = wp_kses( wp_unslash( $_POST["field_key_" . $field_key . "_translation-" . $lang_code] ), $allowed_tags );
                            }
                        }
                    }
                }

                $fields = DT_Prayer_Campaigns::instance()->recursive_parse_args( $saved_fields, $fields );

                update_option( 'DT_Prayer_Campaigns::instance()->porch_fields', $fields );
            }

            if ( isset( $_POST['reset_values'] ) ) {
                update_option( 'DT_Prayer_Campaigns::instance()->porch_fields', [] );
                $fields = DT_Porch_Settings::porch_fields();
            }
            if ( isset( $_POST['night_of_power'] ) ){
                $post_fields = dt_recursive_sanitize_array( $_POST );
                $fields["power"]["value"]["start"] = $post_fields["start"];
                $fields["power"]["value"]["start_time"] = $post_fields["start_time"];
                $fields["power"]["value"]["end"] = $post_fields["end"];
                $fields["power"]["value"]["end_time"] = $post_fields["end_time"];
                update_option( 'DT_Prayer_Campaigns::instance()->porch_fields', $fields );
            }
        }
        ?>
        <form method="post" class="metabox-table">
            <?php wp_nonce_field( 'ramadan_settings', 'ramadan_settings_nonce' ) ?>
            <!-- Box -->
            <table class="widefat striped">
                <thead>
                <tr>
                    <th style="width:20%">Home Page Details</th>
                    <th style="width:50%"></th>
<!--                    <th ><span style="float:right;"><button type="submit" name="reset_values" value='delete'>Reset all to default</button></span></th>-->
                </tr>
                </thead>
                <tbody>
                    <?php foreach ( $fields as $key => $field ) :
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
                                        self::translation_cell( $langs, $key, $field );
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
                                        self::translation_cell( $langs, $key, $field );
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
                                        <option value="yes" selected="selected"><?php echo esc_html( __( 'Yes', 'pray4ramadan-porch' ) ); ?></option>
                                        <option value="no"><?php echo esc_html( __( 'No', 'pray4ramadan-porch' ) ); ?></option>
                                    <?php else : ?>
                                        <option value="yes"><?php echo esc_html( __( 'Yes', 'pray4ramadan-porch' ) ); ?></option>
                                        <option value="no" selected="selected"><?php echo esc_html( __( 'No', 'pray4ramadan-porch' ) ); ?></option>
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
                                        <?php foreach ( $langs as $lang ) : ?>
                                            <option value="<?php echo esc_attr( $lang['language'] ); ?>"><?php echo esc_html( $lang['native_name'] ); ?></option>
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
                                            <option value="<?php echo esc_attr( $field['value'] ) ?>"><?php echo esc_html( $list[$field['value']] ) ?? ''?></option>
                                            <option disabled>-----</option>
                                            <?php
                                        }
                                        foreach ( $list as $list_key => $list_label ) {
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
                            <button class="button" type="submit">Update</button>
                        </td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            <br>
            <!-- End Box -->
        </form>

        <form method="post" class="metabox-table">
            <?php wp_nonce_field( 'ramadan_settings', 'ramadan_settings_nonce' );
            $power_fields = $fields["power"]["value"];
            $start_time = (int) $power_fields["start_time"];
            $end_time = (int) $power_fields["end_time"];

            $campaign_fields = DT_Campaign_Settings::get_campaign();
            $timezone = "America/Chicago";
            if ( isset( $campaign_fields["campaign_timezone"]["key"] ) ){
                $timezone = $campaign_fields["campaign_timezone"]["key"];
            }

            ?>
            <!-- Box -->
            <table class="widefat striped">
                <thead>
                <tr>
                    <th style="width:20%">Night of Power</th>
                    <th style="width:50%"></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Link</td>
                    <td><a href="<?php echo esc_html( home_url( '/prayer/power' ) ); ?>"><?php echo esc_html( home_url( '/prayer/power' ) ); ?></a></td>
                </tr>
<!--                <tr>-->
<!--                    <td>Enabled</td>-->
<!--                    <td><input type="checkbox" --><?php //checked( $power_fields["enabled"] ); ?><!-- style="width:fit-content"></td>-->
<!--                </tr>-->
                <tr>
                    <td>
                        Start time
                    </td>
                    <td>
                        <input type="date" name="start"
                               value="<?php echo esc_html( $power_fields["start"] ); ?>"><br>
                        <select name="start_time">
                            <?php
                            $int = 0;
                            while ( $int < DAY_IN_SECONDS ): ?>
                                <option value="<?php echo esc_html( $int ); ?>" <?php selected( $int === $start_time ) ?>> <?php echo esc_html( gmdate( 'h:i a', $int ) ) ?></option>
                                <?php
                                $int += 30 * MINUTE_IN_SECONDS;
                            endwhile; ?>
                        </select>
                        <br>
                        Suggestion: Night of Power 7pm
                        <br>
                        Set times according to <?php echo esc_html( $timezone ); ?> timezone
                    </td>
                </tr>
                <tr>
                    <td>End time</td>
                    <td>
                        <input type="date" name="end" value="<?php echo esc_html( $power_fields["end"] ); ?>">
                        <select name="end_time">
                            <?php
                            $int = 0;
                            while ( $int < DAY_IN_SECONDS ): ?>
                                <option value="<?php echo esc_html( $int ); ?>" <?php selected( $int === $end_time ) ?>> <?php echo esc_html( gmdate( 'h:i a', $int ) ) ?></option>
                                <?php
                                $int += 30 * MINUTE_IN_SECONDS;
                            endwhile; ?>
                        </select>
                        <br>
                        Suggestion: Night of Power 4am
                        <br>
                        Set times according to <?php echo esc_html( $timezone ); ?> timezone
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <button class="button" type="submit" name="night_of_power">Update</button>
                    </td>
                    <td></td>
                </tr>
                </tbody>
            </table>
            <br>
            <!-- End Box -->
        </form>
        <?php
    }

}