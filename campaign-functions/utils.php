<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }


class Campaign_Utils {
    public static function get_campaign_goal_quantity( $campaign ) {
        $goal = 24;
        if ( !empty( $campaign['goal_quantity'] ) && is_numeric( $campaign['goal_quantity'] ) ){
            $goal = (int) $campaign['goal_quantity'];
        }
        return $goal;
    }
    public static function get_campaign_goal( $campaign ) {
        return isset( $campaign['campaign_goal']['key'] ) ? $campaign['campaign_goal']['key'] : '247coverage';
    }
    public static function prayer_commitments_needed( $campaign ) {
        $days_in_campaign = DT_Campaign_Fuel::total_days_in_campaign( $campaign['ID'] );
        if ( $days_in_campaign === -1 ){
            return '';
        }
        $min_time_duration = DT_Time_Utilities::campaign_min_prayer_duration( $campaign['ID'] );
        $campaign_goal = self::get_campaign_goal( $campaign );
        if ( $campaign_goal === 'quantity' ){
            $goal_hours = self::get_campaign_goal_quantity( $campaign );
            $prayer_commitments_needed = $days_in_campaign * $goal_hours * 60 / $min_time_duration;
        } else {
            $prayer_commitments_needed = $days_in_campaign * 24 * 60 / $min_time_duration;
        }
        return $prayer_commitments_needed;
    }
}


/**
 * Merges the $args and $defaults array recursively through sub arrays
 *
 * Both arrays are expected to have the same shape
 *
 * @param array $args
 * @param array $defaults
 *
 * @return array
 */
if ( !function_exists( 'recursive_parse_args' ) ){
    function recursive_parse_args( $args, $defaults ) {
        $new_args = (array) $defaults;

        foreach ( $args ?: [] as $key => $value ) {
            if ( is_array( $value ) && isset( $new_args[ $key ] ) ) {
                $new_args[ $key ] = recursive_parse_args( $value, $new_args[ $key ] );
            }
            elseif ( $key !== 'default' && isset( $new_args[$key] ) ){
                $new_args[ $key ] = $value;
            }
        }

        return $new_args;
    }
}

/**
 * Returns merged values stored in $settings into the $defaults array with the $value_key
 * as the key to store the $settings values in.
 *
 * @param array $settings contains the values to merge in
 * @param array $defaults e.g. contains the information for some fields
 * @param string $value_key stores the contents of $settings at this key in the $defaults array
 *
 * @return array
 */
function dt_merge_settings( $settings, $defaults, $value_key = 'value' ) {
    $new_settings = (array) $defaults;

    foreach ( $settings as $key => $value ) {
        if ( isset( $defaults[$key] ) ) {
            $new_settings[$key][$value_key] = $value;
        }
    }

    return $new_settings;
}

/**
 * Returns filtered array of settings whose keys are found in the defaults array
 *
 * @param array $settings
 * @param array $defaults
 *
 * @return array
 */
function dt_validate_settings( $settings, $defaults ) {
    $keep_settings_in_defaults = function( $value, $key ) use ( $defaults ) {
        return isset( $defaults[$key] );
    };

    return array_filter( $settings, $keep_settings_in_defaults, ARRAY_FILTER_USE_BOTH );
}

/**
 * Return the code of the language that the campaign is currently being viewed in.
 *
 * This could come from the GET request or be stored in the browser's cookies
 *
 * @return string
 */
function dt_campaign_get_current_lang( $lang = 'en_US' ): string {
    if ( defined( 'PORCH_DEFAULT_LANGUAGE' ) ){
        $lang = PORCH_DEFAULT_LANGUAGE;
    }
    if ( isset( $_GET['lang'] ) && !empty( $_GET['lang'] ) ){
        $lang = sanitize_text_field( wp_unslash( $_GET['lang'] ) );
    } elseif ( isset( $_COOKIE['dt-magic-link-lang'] ) && !empty( $_COOKIE['dt-magic-link-lang'] ) ){
        $lang = sanitize_text_field( wp_unslash( $_COOKIE['dt-magic-link-lang'] ) );
    }
    return $lang;
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
function dt_campaign_reload_text_domain(){
    load_plugin_textdomain( 'disciple-tools-prayer-campaigns', false, trailingslashit( dirname( plugin_basename( __FILE__ ), 2 ) ). 'languages' );
}

/**
 * Set the magic link lang in the cookie
 *
 * @param string $lang
 */
function dt_campaign_add_lang_to_cookie( string $lang ) {
    setcookie( 'dt-magic-link-lang', $lang, 0, '/' );
    $_COOKIE['dt-magic-link-lang'] = $lang;
}

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
 * Split a sentence of $words into $parts and return the $part that you want.
 *
 * @param string $words The sentence or string of words
 * @param int $part The part of the split sentence to be returned
 * @param int $parts How many parts to split the sentence into
 *
 * @return string
 */
function dt_split_sentence( string $words, int $part, int $parts ) {
    $seperator = ' ';
    $split_words = explode( $seperator, $words );

    /* split the array into two halves with the smaller half last */
    $part_length = floor( count( $split_words ) / $parts );

    if ( $part === $parts ) {
        return implode( $seperator, array_slice( $split_words, ( $part - 1 ) * $part_length ) );
    } else {
        return implode( $seperator, array_slice( $split_words, ( $part - 1 ) * $part_length, $part_length ) );
    }
}

/**
 * Added to dummy out missing function as it was deprecated at PHP7 and is depended on by rss-importer plugin
 */
if ( !function_exists( 'set_magic_quotes_runtime' ) ) {
    function set_magic_quotes_runtime( $f = null ) {
        return true;
    }
}

function campaigns_get_frequency_duration_days( $campaign ) {
    $frequencies = [
        'daily' => 90,
        'weekly' => 180,
    ];
    if ( isset( $campaign['daily_signup_length'] ) && is_numeric( $campaign['daily_signup_length'] ) ) {
        $frequencies['daily'] = min( (int) $campaign['daily_signup_length'], 365 );
    }
    if ( isset( $campaign['weekly_signup_length'] ) && is_numeric( $campaign['weekly_signup_length'] ) ) {
        $frequencies['weekly'] = min( (int) $campaign['weekly_signup_length'], 365 );
    }
    return $frequencies;
}

function dt_get_next_ramadan_start_date() {
    $ramadan_start_dates = [
        '2023-03-22',
        '2024-03-10',
        '2025-03-01',
        '2026-02-18',
        '2027-02-08',
        '2028-01-28',
        '2029-01-16',
        '2030-01-05',
        '2030-12-26',
        '2031-12-15',
        '2032-12-04',
    ];

    $current_time = time();

    foreach ( $ramadan_start_dates as $start_date ) {
        if ( strtotime( $start_date ) < $current_time ) {
            continue;
        }
        return $start_date;
    }
}
function dt_get_next_ramadan_end_date(){
    $start = dt_get_next_ramadan_start_date();
    return gmdate( 'Y-m-d', strtotime( $start . ' +29 days' ) );
}
/*
https://www.qppstudio.net/global-holidays-observances/start-of-ramadan.htm

2022 Start of Ramadan (Umm al-Qura) Saturday Apr 2
2023 Start of Ramadan (Umm al-Qura) Thursday Mar 23
2024 Start of Ramadan (Umm al-Qura) Monday Mar 11
2025 Start of Ramadan (Umm al-Qura) Saturday Mar 1
2026 Start of Ramadan (Umm al-Qura) Wednesday Feb 18
2027 Start of Ramadan (Umm al-Qura) Monday Feb 8
2028 Start of Ramadan (Umm al-Qura) Friday Jan 28
2029 Start of Ramadan (Umm al-Qura) Tuesday Jan 16
2030 Start of Ramadan (Umm al-Qura) Saturday Jan 5
2030 Start of Ramadan (Umm al-Qura) Thursday Dec 26
2031 Start of Ramadan (Umm al-Qura) Monday Dec 15
2032 Start of Ramadan (Umm al-Qura) Saturday Dec 4
*/

if ( !function_exists( 'dt_cached_api_call' ) ){
    function p4m_cached_api_call( $url, $type = 'GET', $args = [], $duration = HOUR_IN_SECONDS, $use_cache = true ){
        $data = get_site_transient( 'dt_cached_' . esc_url( $url ) );
        if ( !$use_cache || empty( $data ) ){
            if ( $type === 'GET' ){
                $response = wp_remote_get( $url, $args );
            } else {
                $response = wp_remote_post( $url, $args );
            }
            if ( is_wp_error( $response ) || ( isset( $response['response']['code'] ) && $response['response']['code'] !== 200 ) ){
                return false;
            }
            $data = wp_remote_retrieve_body( $response );

            set_site_transient( 'dt_cached_' . esc_url( $url ), $data, $duration );
        }
        return json_decode( $data, true );
    }
}


function p4m_subscribe_to_news( $email, $name = '', $source = 'p4m_campaign_signup' ){
    if ( !dt_campaigns_is_prayer_tools_news_enabled() ) {
        return;
    }

    $lists = [ 'list_23', 'list_29' ]; //P4M News, P4M Campaign subscriber
    $tags = [];

    if ( class_exists( 'DT_Porch_Selector' ) ){
        $selected_porch = DT_Porch_Selector::instance()->get_selected_porch_id();
        if ( $selected_porch === 'ramadan-porch' ){
            $lists[] = 'list_31'; //Ramadan Campaign subscriber
            $lists[] = 'list_43'; //Ramadan 2026 Campaign subscriber
            $tags[] = [ 'value' => 'p4m_ramadan_subscriber' ];
        }
    }
    $campaign_name = '';
    if ( class_exists( 'DT_Porch_Settings' ) ){
        $porch_fields = DT_Porch_Settings::settings();
        if ( isset( $porch_fields['name']['value'] ) ){
            $campaign_name = $porch_fields['name']['value'];
        }
    }
    if ( empty( $campaign_name ) ){
        $campaign_name = get_the_title();
    }

    wp_remote_post( 'https://prayer.tools/wp-json/go-webform/optin', [
        'body' => [
            'email' => $email,
            'name' => $name,
            'source' => $source,
            'lists' => $lists,
            'tags' => [ 'values' => $tags ],
            'named_tags' => [ 'values' => [ [ 'value' => $campaign_name, 'type' => 'p4m_campaign_name' ] ] ],
        ]
    ] );
}

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
        if ( is_user_logged_in() && is_user_member_of_blog() ) {

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
