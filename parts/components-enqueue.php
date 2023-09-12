<?php

add_filter( 'script_loader_tag', function ( $tag, $handle, $src ){
    if ( str_contains( $handle, 'component' ) ){
        $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>'; //phpcs:ignore
    }
    return $tag;
}, 10, 3 );

function dt_campaigns_register_scripts( $atts = [] ){
    $plugin_dir_path = DT_Prayer_Campaigns::get_dir_path();
    $plugin_dir_url = DT_Prayer_Campaigns::get_url_path();

    wp_register_script( 'luxon', 'https://cdn.jsdelivr.net/npm/luxon@2.3.1/build/global/luxon.min.js', false, '2.3.1', true );
    if ( !wp_script_is( 'dt_campaign_core', 'registered' ) ){
        wp_enqueue_script( 'dt_campaign_core', $plugin_dir_url . 'post-type/campaign_core.js', [
            'jquery',
            'lodash',
            'luxon'
        ], filemtime( $plugin_dir_path . 'post-type/campaign_core.js' ), true );
    }
    wp_localize_script(
        'dt_campaign_core', 'campaign_objects', [
            'magic_link_parts' => [
                'root' => $atts['root'],
                'type' => $atts['type'],
                'public_key' => $atts['public_key'],
                'meta_key' => $atts['meta_key'],
                'post_id' => $atts['post_id'],
                'lang' => $atts['lang'] ?? 'en_US'
            ],
            'rest_url' => get_rest_url(),
            'remote' => $atts['rest_url'] !== get_rest_url(),
            'home' => home_url(),
            'plugin_url' => $plugin_dir_url,
            'translations' => [
                'Detected Time Zone' => __( 'Detected Time Zone', 'disciple-tools-prayer-campaigns' ),
                'Choose a timezone' => __( 'Choose a timezone', 'disciple-tools-prayer-campaigns' ),
                'days' => __( 'days', 'disciple-tools-prayer-campaigns' ),
                'hours' => __( 'hours', 'disciple-tools-prayer-campaigns' ),
                'minutes' => __( 'minutes', 'disciple-tools-prayer-campaigns' ),
                'seconds' => __( 'seconds', 'disciple-tools-prayer-campaigns' ),
                'Campaign Begins In' => __( 'Campaign Starts In', 'disciple-tools-prayer-campaigns' ),
                'Campaign Ends In' => __( 'Campaign Ends In', 'disciple-tools-prayer-campaigns' ),
                'Campaign Ended' => __( 'Campaign Ended', 'disciple-tools-prayer-campaigns' ),
                'time_slot_label' => _x( '%1$s for %2$s minutes.', 'Monday 5pm for 15 minutes', 'disciple-tools-prayer-campaigns' ),
            ]
        ]
    );

    wp_enqueue_script( 'campaign_css_component', $plugin_dir_url . 'parts/campaign-component-css.js', [], filemtime( $plugin_dir_path . 'parts/campaign-component-css.js' ), false );
    wp_enqueue_script( 'campaign_components', $plugin_dir_url . 'parts/campaign-components.js', [ 'campaign_css_component' ], filemtime( $plugin_dir_path . 'parts/campaign-components.js' ), true );

    wp_enqueue_script( 'campaign_sign_up_component', $plugin_dir_url . 'parts/campaign-sign-up.js', [ 'campaign_css_component' ], filemtime( $plugin_dir_path . 'parts/campaign-sign-up.js' ), true );
}
