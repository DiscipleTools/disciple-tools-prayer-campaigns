<?php

add_filter( 'script_loader_tag', function ( $tag, $handle, $src ){
    if ( str_contains( $handle, 'campaign_component' ) || str_contains( $handle, 'dt_subscription_js' ) ){
        $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>'; //phpcs:ignore
    }
    return $tag;
}, 10, 3 );

function dt_campaigns_register_scripts( $atts, $campaign_id ){
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
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'magic_link_parts' => [
                    'root' => $atts['root'],
                    'type' => $atts['type'],
                    'public_key' => $atts['public_key'],
                    'meta_key' => $atts['meta_key'],
                    'post_id' => $atts['post_id'],
                    'lang' => $atts['lang'] ?? 'en_US',
                    'campaign_id' => $campaign_id,
                ],
                'rest_url' => get_rest_url(),
                'remote' => ( $atts['rest_url'] ?? get_rest_url() ) !== get_rest_url(),
                'home' => class_exists( 'DT_Campaign_Landing_Settings' ) ? DT_Campaign_Landing_Settings::get_landing_page_url( $campaign_id ) : home_url(),
                'campaign_root' => class_exists( 'DT_Campaign_Landing_Settings' ) ? DT_Campaign_Landing_Settings::get_landing_root_url() : '',
                'plugin_url' => $plugin_dir_url,
                'dt_campaigns_is_prayer_tools_news_enabled' => dt_campaigns_is_prayer_tools_news_enabled(),
                'locale' => determine_locale(),
                'translations' => [
                    'Detected time zone' => __( 'Detected time zone', 'disciple-tools-prayer-campaigns' ),
                    'Select a timezone' => __( 'Select a timezone', 'disciple-tools-prayer-campaigns' ),
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
                    'My Prayer Commitments' => __( 'My Prayer Commitments', 'disciple-tools-prayer-campaigns' ),
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
                    'prayer commitments' => __( 'prayer commitments', 'disciple-tools-prayer-campaigns' ),
                    'Starting on %s' => _x( 'Starting on %s', 'Starting on October 4', 'disciple-tools-prayer-campaigns' ),
                    'Ending on %s' => _x( 'Ending on %s', 'Ending on October 4', 'disciple-tools-prayer-campaigns' ),
                    'Review' => __( 'Review', 'disciple-tools-prayer-campaigns' ),
                    'Submit' => __( 'Submit', 'disciple-tools-prayer-campaigns' ),
                    'Verify' => __( 'Verify', 'disciple-tools-prayer-campaigns' ),
                    'Goal: %s hours of prayer every day' => __( 'Goal: %s hours of prayer every day', 'disciple-tools-prayer-campaigns' ),
                    'Goal: 24/7 coverage' => __( 'Goal: 24/7 coverage', 'disciple-tools-prayer-campaigns' ),
                    'Please enter a valid name or email address' => __( 'Please enter a valid name or email address', 'disciple-tools-prayer-campaigns' ),
                    'Next' => __( 'Next', 'disciple-tools-prayer-campaigns' ),
                    'No prayer times selected' => __( 'No prayer times selected', 'disciple-tools-prayer-campaigns' ),
                    'Daily' => __( 'Daily', 'disciple-tools-prayer-campaigns' ),
                    'Weekly' => __( 'Weekly', 'disciple-tools-prayer-campaigns' ),
                    'Monthly' => __( 'Monthly', 'disciple-tools-prayer-campaigns' ),
                    'Pick Dates and Times' => __( 'Pick Dates and Times', 'disciple-tools-prayer-campaigns' ),
                    '%s Minutes' => __( '%s Minutes', 'disciple-tools-prayer-campaigns' ),
                    '%s Hour' => __( '%s Hour', 'disciple-tools-prayer-campaigns' ),
                    '%s Hours' => __( '%s Hours', 'disciple-tools-prayer-campaigns' ),
                    '%1$s at %2$s for %3$s' => __( '%1$s at %2$s for %3$s', 'disciple-tools-prayer-campaigns' ),
                    'for %s minutes' => __( 'for %s minutes', 'disciple-tools-prayer-campaigns' ),
                    'Every %s' => __( 'Every %s', 'disciple-tools-prayer-campaigns' ),
                    'Prayer Time Selected' => __( 'Prayer Time Selected', 'disciple-tools-prayer-campaigns' ),
                    'Prayer Time Removed' => __( 'Prayer Time Removed', 'disciple-tools-prayer-campaigns' ),
                    'Select a Day' => __( 'Select a Day', 'disciple-tools-prayer-campaigns' ),
                    'Renews on %s' => __( 'Renews on %s', 'disciple-tools-prayer-campaigns' ),
                    'Ends on %s' => __( 'Ends on %s', 'disciple-tools-prayer-campaigns' ),
                    'Success' => __( 'Success', 'disciple-tools-prayer-campaigns' ),
                    'Your registration was successful.' => __( 'Your registration was successful.', 'disciple-tools-prayer-campaigns' ),
                    'Check your email for additional details and to manage your account.' => __( 'Check your email for additional details and to manage your account.', 'disciple-tools-prayer-campaigns' ),
                    'Ok' => __( 'Ok', 'disciple-tools-prayer-campaigns' ),
                    'Access Account' => __( 'Access Account', 'disciple-tools-prayer-campaigns' ),
                    'cancel_warning_paragraph' => __( 'Need to cancel? We get it! But wait! If your prayer commitment is scheduled to start in less than 48-hours, please ask a friend to cover it for you. That will keep the prayer chain from being broken AND will give someone the joy of fighting for the lost! Thanks!', 'disciple-tools-prayer-campaigns' ),
                    'Account Settings' => __( 'Account Settings', 'disciple-tools-prayer-campaigns' ),
                    'Timezone' => __( 'Timezone', 'disciple-tools-prayer-campaigns' ),
                    'Receive prayer time notifications' => __( 'Receive prayer time notifications', 'disciple-tools-prayer-campaigns' ),
                    'Auto extend prayer times' => __( 'Auto extend prayer times', 'disciple-tools-prayer-campaigns' ),
                    'Advanced Settings' => __( 'Advanced Settings', 'disciple-tools-prayer-campaigns' ),
                    'show' => __( 'show', 'disciple-tools-prayer-campaigns' ),
                    'Delete this account and all the scheduled prayer times' => __( 'Delete this account and all the scheduled prayer times', 'disciple-tools-prayer-campaigns' ),
                    'So sorry. Something went wrong. Please, contact us to help you through it, or just try again.' => __( 'So sorry. Something went wrong. Please, contact us to help you through it, or just try again.', 'disciple-tools-prayer-campaigns' ),
                    'Your account has been deleted!' => __( 'Your account has been deleted!', 'disciple-tools-prayer-campaigns' ),
                    'Thank you for praying with us.' => __( 'Thank you for praying with us.', 'disciple-tools-prayer-campaigns' ),
                    'Language' => __( 'Language', 'disciple-tools-prayer-campaigns' ),
                    'Delete' => __( 'Delete', 'disciple-tools-prayer-campaigns' ),
                    'See Prayer Fuel' => __( 'See Prayer Fuel', 'disciple-tools-prayer-campaigns' ),
                    'change time' => __( 'change time', 'disciple-tools-prayer-campaigns' ),
                    'Stop Praying' => __( 'Stop Praying', 'disciple-tools-prayer-campaigns' ),
                    'See prayer times' => __( 'See prayer times', 'disciple-tools-prayer-campaigns' ),
                    'Your future prayer times will be canceled.' => __( 'Your future prayer times will be canceled.', 'disciple-tools-prayer-campaigns' ),
                    'extend' => __( 'extend', 'disciple-tools-prayer-campaigns' ),
                    'Extend Prayer Times' => __( 'Extend Prayer Times', 'disciple-tools-prayer-campaigns' ),
                    'Really delete this prayer time?' => __( 'Really delete this prayer time?', 'disciple-tools-prayer-campaigns' ),
                    'Delete Prayer Time' => __( 'Delete Prayer Time', 'disciple-tools-prayer-campaigns' ),
                    'Mondays' => __( 'Mondays', 'disciple-tools-prayer-campaigns' ),
                    'Tuesdays' => __( 'Tuesdays', 'disciple-tools-prayer-campaigns' ),
                    'Wednesdays' => __( 'Wednesdays', 'disciple-tools-prayer-campaigns' ),
                    'Thursdays' => __( 'Thursdays', 'disciple-tools-prayer-campaigns' ),
                    'Fridays' => __( 'Fridays', 'disciple-tools-prayer-campaigns' ),
                    'Saturdays' => __( 'Saturdays', 'disciple-tools-prayer-campaigns' ),
                    'Sundays' => __( 'Sundays', 'disciple-tools-prayer-campaigns' ),
                    'Hours' => __( 'Hours', 'disciple-tools-prayer-campaigns' ),
                    'Hour' => __( 'Hour', 'disciple-tools-prayer-campaigns' ),
                    'Minutes' => __( 'Minutes', 'disciple-tools-prayer-campaigns' ),
                    'Change Prayer Time' => __( 'Change Prayer Time', 'disciple-tools-prayer-campaigns' ),
                    'Your current prayer time is %s' => __( 'Your current prayer time is %s', 'disciple-tools-prayer-campaigns' ),
                    'Select a new time:' => __( 'Select a new time:', 'disciple-tools-prayer-campaigns' ),
                    'Select a time' => __( 'Select a time', 'disciple-tools-prayer-campaigns' ),
                    'Renew Prayer Times' => __( 'Renew Prayer Times', 'disciple-tools-prayer-campaigns' ),
                    'renew' => __( 'renew', 'disciple-tools-prayer-campaigns' ),
                    'Until %s' => _x( 'Until %s', 'Extend prayer times until Feb 22', 'disciple-tools-prayer-campaigns' ),
                    'For %s months' => _x( 'For %s months', 'Extend prayer times for 12 months', 'disciple-tools-prayer-campaigns' ),
                    'Receive news from Prayer.Tools about upcoming prayer campaigns and occasional communication from GospelAmbition.org' => __( 'Receive news from Prayer.Tools about upcoming prayer campaigns and occasional communication from GospelAmbition.org', 'disciple-tools-prayer-campaigns' ),
                    'modals' => [
                        'edit' => [
                            'modal_title' => __( 'Text Translations', 'disciple-tools-prayer-campaigns' ),
                            'edit_original_string' => __( 'Original String', 'disciple-tools-prayer-campaigns' ),
                            'edit_all_languages' => __( 'Custom Value For All Languages', 'disciple-tools-prayer-campaigns' ),
                            'edit_selected_language' => __( 'Translation For Currently Selected Language', 'disciple-tools-prayer-campaigns' ),
                            'edit_btn_close' => __( 'Close', 'disciple-tools-prayer-campaigns' ),
                            'edit_btn_update' => __( 'Update', 'disciple-tools-prayer-campaigns' )
                        ]
                    ],
                    'Almost there! Finish signing up by activating your account.' => __( 'Almost there! Finish signing up by activating your account.', 'disciple-tools-prayer-campaigns' ),
                    'Click the "Activate Account" button in the email sent to: %s' => __( 'Click the "Activate Account" button in the email sent to: %s', 'disciple-tools-prayer-campaigns' ),
                    'It will look like this:' => __( 'It will look like this:', 'disciple-tools-prayer-campaigns' ),
                    'Pending - Verification Needed' => __( 'Pending - Verification Needed', 'disciple-tools-prayer-campaigns' ),
                    'Back to sign-up' => __( 'Back to sign-up', 'disciple-tools-prayer-campaigns' ),
                    'So sorry. Something went wrong. You can:' => __( 'So sorry. Something went wrong. You can:', 'disciple-tools-prayer-campaigns' ),
                    'Try Again' => __( 'Try Again', 'disciple-tools-prayer-campaigns' ),
                    'Contact Us' => __( 'Contact Us', 'disciple-tools-prayer-campaigns' ),
                    'Please check your email to activate your account before adding more prayer times.' => __( 'Please check your email to activate your account before adding more prayer times.', 'disciple-tools-prayer-campaigns' ),
                    'Time not covered' => __( 'Time not covered', 'disciple-tools-prayer-campaigns' ),
                    '# of people covering this time' => __( '# of people covering this time', 'disciple-tools-prayer-campaigns' ),
                    'Minute' => __( 'Minute', 'disciple-tools-prayer-campaigns' ),
                    'Selected Commitment' => __( 'Selected Commitment', 'disciple-tools-prayer-campaigns' ),
                    'Add' => __( 'Add', 'disciple-tools-prayer-campaigns' ),
                ]
            ]
        );

        wp_enqueue_script( 'campaign_component_css', $plugin_dir_url . 'parts/campaign-component-css.js', [], filemtime( $plugin_dir_path . 'parts/campaign-component-css.js' ), false );
        wp_enqueue_script( 'campaign_components', $plugin_dir_url . 'parts/campaign-components.js', [ 'campaign_component_css' ], filemtime( $plugin_dir_path . 'parts/campaign-components.js' ), true );
        wp_enqueue_script( 'campaign_component_sign_up', $plugin_dir_url . 'parts/campaign-sign-up.js', [ 'campaign_component_css' ], filemtime( $plugin_dir_path . 'parts/campaign-sign-up.js' ), true );
        wp_enqueue_style( 'toastify-js-css', 'https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css', [], '1.12.0' );
        wp_enqueue_script( 'toastify-js', 'https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js', [ 'jquery' ], '1.12.0', true );
    }
}
