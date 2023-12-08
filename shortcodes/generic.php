<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function dt_generic_percentage_shortcode( $attrs ) {
    echo dt_campaign_percentage(//phpcs:ignore
        $attrs
    );
}
add_shortcode( 'dt-generic-campaign-percentage', 'dt_generic_percentage_shortcode' );

function dt_generic_calendar_shortcode( $attrs ) {
    echo dt_ongoing_campaign_calendar(//phpcs:ignore
        $attrs
    );
}
add_shortcode( 'dt-generic-campaign-calendar', 'dt_generic_calendar_shortcode' );

function dt_generic_signup_shortcode( $attrs ) {
    echo dt_ongoing_campaign_signup(//phpcs:ignore
        $attrs
    );
}
add_shortcode( 'dt-generic-campaign-signup', 'dt_generic_signup_shortcode' );
