<?php

add_filter( 'script_loader_tag', function ( $tag, $handle, $src ){
    if ( str_contains( $handle, 'campaign_component' ) || str_contains( $handle, 'dt_subscription_js' ) ){
        $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>'; //phpcs:ignore
    }
    return $tag;
}, 10, 3 );

function dt_campaigns_register_scripts( $atts = [] ){
    $plugin_dir_path = DT_Prayer_Campaigns::get_dir_path();
    $plugin_dir_url = DT_Prayer_Campaigns::get_url_path();

    wp_register_script( 'luxon', 'https://cdn.jsdelivr.net/npm/luxon@2.3.1/build/global/luxon.min.js', false, '2.3.1', true );
    if ( !wp_script_is( 'dt_campaign_core', 'registered' ) ){
        wp_enqueue_script( 'dt_campaign_core', $plugin_dir_url . 'parts/campaign-core.js', [
            'jquery',
            'luxon'
        ], filemtime( $plugin_dir_path . 'parts/campaign-core.js' ), true );
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
                'remote' => ( $atts['rest_url'] ?? get_rest_url() ) !== get_rest_url(),
                'home' => home_url(),
                'plugin_url' => $plugin_dir_url,
                'translations' => [
                    'Detected time zone' => __( 'Detected time zone', 'disciple-tools-prayer-campaigns' ),
                    'Choose a timezone' => __( 'Choose a timezone', 'disciple-tools-prayer-campaigns' ),
                    'days' => __( 'days', 'disciple-tools-prayer-campaigns' ),
                    'hours' => __( 'hours', 'disciple-tools-prayer-campaigns' ),
                    'minutes' => __( 'minutes', 'disciple-tools-prayer-campaigns' ),
                    'seconds' => __( 'seconds', 'disciple-tools-prayer-campaigns' ),
                    'Campaign Begins In' => __( 'Campaign Starts In', 'disciple-tools-prayer-campaigns' ),
                    'Campaign Ends In' => __( 'Campaign Ends In', 'disciple-tools-prayer-campaigns' ),
                    'Campaign Ended' => __( 'Campaign Ended', 'disciple-tools-prayer-campaigns' ),
                    'time_slot_label' => _x( '%1$s for %2$s minutes.', 'Monday 5pm for 15 minutes', 'disciple-tools-prayer-campaigns' ),
                    'How often?' => __( 'How often?', 'disciple-tools-prayer-campaigns' ),
                    'I will pray for' => __( 'I will pray for', 'disciple-tools-prayer-campaigns' ),
                    'At what time?' => __( 'At what time?', 'disciple-tools-prayer-campaigns' ),
                    'Selected Times' => __( 'Selected Times', 'disciple-tools-prayer-campaigns' ),
                    'Contact Info' => __( 'Contact Info', 'disciple-tools-prayer-campaigns' ),
                    'Name' => __( 'Name', 'disciple-tools-prayer-campaigns' ),
                    'Email' => __( 'Email', 'disciple-tools-prayer-campaigns' ),
                    'Start Here' => __( 'Start Here', 'disciple-tools-prayer-campaigns' ),
                    'Continue here' => __( 'Continue here', 'disciple-tools-prayer-campaigns' ),
                    'On which week day?' => __( 'On which week day?', 'disciple-tools-prayer-campaigns' ),
                    'Select a Date' => __( 'Select a Date', 'disciple-tools-prayer-campaigns' ),
                    'Select a Time' => __( 'Select a Time', 'disciple-tools-prayer-campaigns' ),
                    'Select a Time for %s' => __( 'Select a Time for %s', 'disciple-tools-prayer-campaigns' ),
                    'Close' => __( 'Close', 'disciple-tools-prayer-campaigns' ),
                    'prayer times' => __( 'prayer times', 'disciple-tools-prayer-campaigns' ),
                    'Starting on %s' => _x( 'Starting on %s', 'Starting on October 4', 'disciple-tools-prayer-campaigns' ),
                    'Ending on %s' => _x( 'Ending on %s', 'Ending on October 4', 'disciple-tools-prayer-campaigns' ),
                    'Review' => __( 'Review', 'disciple-tools-prayer-campaigns' ),
                    'Submit' => __( 'Submit', 'disciple-tools-prayer-campaigns' ),
                    'Verify' => __( 'Verify', 'disciple-tools-prayer-campaigns' ),
                    'Percentage covered in prayer' => __( 'Percentage covered in prayer', 'disciple-tools-prayer-campaigns' ),
                    '%s committed' => __( '%s committed', 'disciple-tools-prayer-campaigns' ),
                    'Please enter a valid name or email address' => __( 'Please enter a valid name or email address', 'disciple-tools-prayer-campaigns' ),
                    'Next' => __( 'Next', 'disciple-tools-prayer-campaigns' ),
                    'No prayer times selected' => __( 'No prayer times selected', 'disciple-tools-prayer-campaigns' ),
                    'Daily' => __( 'Daily', 'disciple-tools-prayer-campaigns' ),
                    'Weekly' => __( 'Weekly', 'disciple-tools-prayer-campaigns' ),
                    'Monthly' => __( 'Monthly', 'disciple-tools-prayer-campaigns' ),
                    'Pick Dates and Times' => __( 'Pick Dates and Times', 'disciple-tools-prayer-campaigns' ),
                    'up to %s months' => _x( 'up to %s months', 'up to 3 months', 'disciple-tools-prayer-campaigns' ),
                    'A confirmation code hase been sent to %s.' => __( 'A confirmation code hase been sent to %s.', 'disciple-tools-prayer-campaigns' ),
                    'Please enter the code below in the next 10 minutes to confirm your email address.' => __( 'Please enter the code below in the next 10 minutes to confirm your email address.', 'disciple-tools-prayer-campaigns' ),
                    '%s Minutes' => __( '%s Minutes', 'disciple-tools-prayer-campaigns' ),
                    '%s Hours' => __( '%s Hours', 'disciple-tools-prayer-campaigns' ),
                    '%1$s at %2$s for %3$s' => __( '%1$s at %2$s for %3$s', 'disciple-tools-prayer-campaigns' ),
                    'for %s minutes' => __( 'for %s minutes', 'disciple-tools-prayer-campaigns' ),
                    'Every %s' => __( 'Every %s', 'disciple-tools-prayer-campaigns' ),
                    'Confirmation Code' => __( 'Confirmation Code', 'disciple-tools-prayer-campaigns' ),
                    'My Prayer Times' => __( 'My Prayer Times', 'disciple-tools-prayer-campaigns' ),
                    'Prayer Time Selected' => __( 'Prayer Time Selected', 'disciple-tools-prayer-campaigns' ),
                    'Select a Day' => __( 'Select a Day', 'disciple-tools-prayer-campaigns' ),
                    'Renews on %s' => __( 'Renews on %s', 'disciple-tools-prayer-campaigns' ),
                ]
            ]
        );

        wp_enqueue_script( 'campaign_component_css', $plugin_dir_url . 'parts/campaign-component-css.js', [], filemtime( $plugin_dir_path . 'parts/campaign-component-css.js' ), false );
        wp_enqueue_script( 'campaign_components', $plugin_dir_url . 'parts/campaign-components.js', [ 'campaign_component_css' ], filemtime( $plugin_dir_path . 'parts/campaign-components.js' ), true );
        wp_enqueue_script( 'campaign_component_sign_up', $plugin_dir_url . 'parts/campaign-sign-up.js', [ 'campaign_component_css' ], filemtime( $plugin_dir_path . 'parts/campaign-sign-up.js' ), true );
        wp_enqueue_style( 'toastify-js-css', 'https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css', [], '1.12.0' );
        wp_enqueue_script( 'toastify-js', 'https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js', [ 'jquery' ], '1.12.0' );
    }
}
