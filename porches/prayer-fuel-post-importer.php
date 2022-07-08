<?php

add_filter( 'wp_import_posts', 'my_custom_post_args' );
/**
* Some examples.
*/
function my_custom_post_args( $posts ) {
    // Copy the post. Recommended
    $counter = 1;

    $new_posts = $posts;

    foreach ( $new_posts as $i => $post ) {
        $new_posts[$i]["post_title"] = "Day $counter";
        $counter++;
    }

    /*
        So we can change the title, dates for publishing, day meta key
        absolutely anything that we want to do.

        So then the code part of it would be to get the latest post in the db
        and start adjusting these posts, publish dates to go be published after these ones.

        If the posts coming in have the day meta tag, then at least we can connect translated
        posts together with that

        How does doing translations of posts work with this.

        Try installing one of the post translator plugins and see how that deals with linking posts
        and what happens when you export those  .
    */
    return $new_posts;
}

function dt_find_latest_prayer_fuel_date() {
    /* get the latest prayer fuel with the imported meta tag */
    $latest_prayer_fuel = null;

    if ( !$latest_prayer_fuel ) {
        $today_timestamp = date_timestamp_get( new DateTime() );
        $campaign = DT_Campaign_Settings::get_campaign();
        $campaign_start = $campaign["start_date"]["timestamp"];

        if ( $today_timestamp > $campaign_start ) {
            return $today_timestamp;
        } else {
            return $campaign_start;
        }
    } else {
        /* else return the date of the next day after it */
        return "";
    }
}
