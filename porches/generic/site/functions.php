<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }



function dt_campaign_custom_dir_attr( $lang ){
    if ( is_admin() ) {
        return $lang;
    }

    $lang = dt_campaign_get_current_lang();

    $dir = DT_Campaign_Languages::get_language_direction( $lang );
    $dir_attr = 'dir="' . $dir . '"';

    return 'lang="' . $lang .'" ' .$dir_attr;
}


/**
 * Add iFrame to allowed wp_kses_post tags
 *
 * @param array  $tags Allowed tags, attributes, and/or entities.
 * @param string $context Context to judge allowed tags by. Allowed values are 'post'.
 *
 * @return array
 */

add_filter( 'wp_kses_allowed_html', function ( $tags, $context ){
    if ( 'post' === $context ){
        $tags['iframe'] = [
            'src' => true,
            'height' => true,
            'width' => true,
            'frameborder' => true,
            'allowfullscreen' => true,
        ];
    }

    return $tags;
}, 10, 2 );



function display_translated_field( $field_key, $section_tag, $section_id, $section_class, $edit_btn_class, $allowed_tags, $split_text = false ) {
    $field_translation = DT_Porch_Settings::get_field_translation( $field_key );
    if ( !empty( $field_translation ) ) {
        ?>
        <<?php echo esc_attr( $section_tag ) ?> id="<?php echo esc_attr( $section_id ) ?>" class="<?php echo esc_attr( $section_class ) ?> wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s">

        <?php

        // Display translated text, splitting string accordingly, based on flag.
        if ( $split_text ) {
            echo esc_html( dt_split_sentence( $field_translation, 1, 2 ) ) ?> <span><?php echo esc_html( dt_split_sentence( $field_translation, 2, 2 ) );
        } else {
            echo esc_html( nl2br( wp_kses( $field_translation, $allowed_tags ) ) );
        }

        // Display edit button, if user is currently logged in.
        if ( is_user_logged_in() ) {
            ?>
            <button class="btn edit-btn <?php echo esc_attr( $edit_btn_class ) ?>" style="font-size: 10px; padding: 5px 15px;" data-field_key="<?php echo esc_attr( $field_key ) ?>" data-section_id="<?php echo esc_attr( $section_id ) ?>"  data-split_text="<?php echo esc_attr( ( $split_text ? 'true' : 'false' ) ) ?>"><?php esc_html_e( 'Edit', 'disciple-tools-prayer-campaigns' ); ?></button>
            <?php
        }
        ?>
        </<?php echo esc_attr( $section_tag ) ?>>
        <?php

        // Capture existing values for processing further down stream.
        $settings = DT_Porch_Settings::settings();
        $lang_default = $settings[$field_key]['default'] ?? '';
        $lang_all = $settings[$field_key]['value'] ?? '';
        $lang_selected = $settings[$field_key]['translations'][dt_campaign_get_current_lang()] ?? '';
        ?>
        <input id="<?php echo esc_attr( $section_id ) ?>_lang_default" type="hidden" value="<?php echo esc_attr( $lang_default ) ?>"/>
        <input id="<?php echo esc_attr( $section_id ) ?>_lang_all" type="hidden" value="<?php echo esc_attr( $lang_all ) ?>"/>
        <input id="<?php echo esc_attr( $section_id ) ?>_lang_selected" type="hidden" value="<?php echo esc_attr( $lang_selected ) ?>"/>
        <?php
    }
}