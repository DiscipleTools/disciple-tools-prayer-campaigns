<?php


function dt_campaign_list_languages(){
    $languages_manager = new DT_Campaign_Languages();

    return $languages_manager->get_enabled_languages();
}

function dt_campaign_list_all_languages() {
    $languages_manager = new DT_Campaign_Languages();

    return $languages_manager->get();
}

/**
 * What is the direction of the language
 *
 * @param string $lang
 *
 * @return string
 */
function dt_campaign_language_direction( string $lang ) {
    return ( new DT_Campaign_Languages() )->get_language_direction( $lang );
}

function dt_campaign_set_translation( $lang ){
    if ( $lang !== 'en_US' ){
        add_filter( 'determine_locale', function ( $locale ) use ( $lang ){
            if ( !empty( $lang ) ){
                return $lang;
            }
            return $locale;
        }, 1000, 1 );
        dt_campaign_reload_text_domain();
    }
}

function dt_campaign_custom_dir_attr( $lang ){
    if ( is_admin() ) {
        return $lang;
    }

    $lang = dt_campaign_get_current_lang();

    $dir = dt_campaign_language_direction( $lang );
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
