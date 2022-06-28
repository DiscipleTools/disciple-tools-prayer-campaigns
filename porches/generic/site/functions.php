<?php


function dt_campaign_list_languages(){
    $languages_manager = new DT_Campaign_Languages();

    return $languages_manager->get_enabled_languages();
}

function dt_campaign_set_translation( $lang ){
    if ( $lang !== "en_US" ){
        add_filter( 'determine_locale', function ( $locale ) use ( $lang ){
            if ( !empty( $lang ) ){
                return $lang;
            }
            return $locale;
        }, 1000, 1 );
        load_plugin_textdomain( 'disciple-tools-prayer-campaigns', false, trailingslashit( dirname( plugin_basename( __FILE__ ), 2 ) ). 'support/languages' );
    }
}

function dt_campaign_custom_dir_attr( $lang ){
    if ( is_admin() ) {
        return $lang;
    }

    $lang = dt_campaign_get_current_lang();

    $translations = dt_campaign_list_languages();
    $dir = "ltr";
    if ( isset( $translations[$lang]["dir"] ) && $translations[$lang]['dir'] === 'rtl' ){
        $dir = "rtl";
    }

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
function custom_wpkses_post_tags( $tags, $context ){
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
}
add_filter( 'wp_kses_allowed_html', 'custom_wpkses_post_tags', 10, 2 );
