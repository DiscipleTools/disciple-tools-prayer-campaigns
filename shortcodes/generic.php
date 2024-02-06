<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function dt_generic_percentage_shortcode( $attrs ) {
    ob_start();
    echo dt_campaign_percentage(//phpcs:ignore
        $attrs
    );
    return ob_get_clean();
}
add_shortcode( 'dt-generic-campaign-percentage', 'dt_generic_percentage_shortcode' );

function dt_generic_calendar_shortcode( $attrs ) {
    ob_start();
    echo dt_ongoing_campaign_calendar(//phpcs:ignore
        $attrs
    );
    return ob_get_clean();
}
add_shortcode( 'dt-generic-campaign-calendar', 'dt_generic_calendar_shortcode' );

function dt_generic_signup_shortcode( $attrs ) {
    ob_start();
    echo dt_ongoing_campaign_signup(//phpcs:ignore
        $attrs
    );
    return ob_get_clean();
}
add_shortcode( 'dt-generic-campaign-signup', 'dt_generic_signup_shortcode' );
