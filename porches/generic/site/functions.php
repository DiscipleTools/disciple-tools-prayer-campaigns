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


function display_translated_field( $field_key, $edit_btn_class = 'btn-common', $split_text = false ) {
    $field_translation = DT_Porch_Settings::get_field_translation( $field_key );
    if ( !empty( $field_translation ) ) {
        global $allowedtags;

        // Display translated text, splitting string accordingly, based on flag.
        if ( $split_text ) {
            echo esc_html( dt_split_sentence( $field_translation, 1, 2 ) ) ?> <span><?php echo esc_html( dt_split_sentence( $field_translation, 2, 2 ) );
        } else {
            echo nl2br( wp_kses( $field_translation, $allowedtags ) );
        }

        // Display edit button, if user is currently logged in.
        if ( is_user_logged_in() ) {

            // Capture existing values for processing further down stream.
            $settings = DT_Porch_Settings::settings();
            $lang_default = $settings[$field_key]['default'] ?? '';
            $lang_all = $settings[$field_key]['value'] ?? '';
            $lang_selected = $settings[$field_key]['translations'][dt_campaign_get_current_lang()] ?? '';
            ?>
            <button class="btn edit-btn <?php echo esc_attr( $edit_btn_class ) ?>"
                    style="font-size: 10px; padding: 5px 15px;"
                    data-field_key="<?php echo esc_attr( $field_key ) ?>"
                    data-split_text="<?php echo esc_attr( ( $split_text ? 'true' : 'false' ) ) ?>"
                    data-lang_default="<?php echo esc_attr( $lang_default ) ?>"
                    data-lang_all="<?php echo esc_attr( $lang_all ) ?>"
                    data-lang_selected="<?php echo esc_attr( $lang_selected ) ?>">
                    <?php esc_html_e( 'Edit', 'disciple-tools-prayer-campaigns' ); ?>
            </button>
            <?php
        }
    }
}
