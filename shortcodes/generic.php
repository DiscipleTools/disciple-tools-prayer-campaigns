<?php

function dt_generic_percentage_shortcode( $attrs ) {
    if ( $attrs["type"] === "24hour" ) {
        $dt_campaign_selected_campaign_magic_link_settings["section"] = "percentage";
        echo dt_24hour_campaign_shortcode(
            $dt_campaign_selected_campaign_magic_link_settings
        );
    } else if ( $attrs["type"] === "ongoing" ) {
        return;
    }
}
add_shortcode( 'dt-generic-campaign-percentage', 'dt_generic_percentage_shortcode' );

function dt_generic_calendar_shortcode( $attrs ) {
    if ( $attrs["type"] === "24hour" ) {
        $attrs["section"] = "calendar";
        echo dt_24hour_campaign_shortcode( $attrs );
    } else if ( $attrs["type"] === "ongoing" ) {
        echo dt_ongoing_campaign_calendar( $attrs );
    }
}
add_shortcode( 'dt-generic-campaign-calendar', 'dt_generic_calendar_shortcode' );

function dt_generic_signup_shortcode( $attrs ) {
    if ( $attrs["type"] === "24hour" ) {
        $attrs["section"] = "sign_up";
        echo dt_24hour_campaign_shortcode( $attrs );
    } elseif ( $attrs["type"] === "ongoing" ) {
        echo dt_ongoing_campaign_signup( $attrs );
    }
}
add_shortcode( 'dt-generic-campaign-signup', 'dt_generic_signup_shortcode' );
