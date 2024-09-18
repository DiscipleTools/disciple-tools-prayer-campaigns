<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter( 'dt_campaign_porch_settings', function ( $settings, $porch_type = null ){
    if ( $porch_type === 'ramadan-porch' ){
        $settings['what_content']['default'] = __( 'Ramadan is one of the five requirements (or pillars) of Islam. During each of its 30 days, Muslims are obligated to fast from dawn until sunset. During this time they are supposed to abstain from food, drinking liquids, smoking, and sexual relations.

Women typically spend the afternoons preparing a big meal. At sunset, families often gather to break the fast. Traditionally the families break the fast with a drink of water, then three dried date fruits, and a multi-course meal. After watching the new Ramadan TV series, men (and some women) go out to coffee shops where they drink coffee, and smoke with friends until late into the night.

Though many have stopped fasting in recent years, and are turned off by the hypocrisy, increased crime rates, and rudeness that is pervasive through the month, others have become more serious about religion during this time. Many attend the evening prayer services and do the other ritual prayers. Some even read the entire Quran (about a tenth the length of the Bible). This sincere seeking makes it a strategic time for us to pray for them.', 'disciple-tools-prayer-campaigns' );
    }
    return $settings;
}, 20, 2 );


add_action('dt_campaigns_before_prayer_fuel', function ( $posts, $todays_campaign_day ){
    $link = site_url( '/prayer/stats' );
    if ( class_exists( 'DT_Campaign_Settings' ) ){
        $current_selected_porch = DT_Campaign_Settings::get( 'selected_porch' );
        if ( $current_selected_porch !== 'ramadan-porch' ){
            return;
        }
    } else {
        $campaign_fields = DT_Campaign_Landing_Settings::get_campaign();
        if ( !isset( $campaign_fields['porch_type']['key'] ) || $campaign_fields['porch_type']['key'] !== 'ramadan-porch' ){
            return;
        }
        $link = DT_Campaign_Landing_Settings::get_landing_root_url() . '/stats';
    }
    if ( empty( $posts ) ){ ?>
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 blog-item">
            <div class="blog-item-wrapper wow fadeInUp" data-wow-delay="0.3s">
                <div class="blog-item-text">
                    <div class="section-header">
                        <h2 class="section-title">No Prayer Fuel Today</h2>
                    </div>
                    <p>Thank you for joining over 6,000 people in praying during this Ramadan!</p>

                    <p>As you pray today look back over the past month and continue praying what God has put on your heart.</p>
                    <p>Pray that God will continue working powerfully in this place.</p>

                    <p>Before you leave check out the end of campaign stats page and
                        consider sharing with us about your prayer time (E.g. testimonies, insights, blessings, etc.)</p>
                    <div style="display: flex; justify-content: center">
                        <a class="btn btn-common btn-rm" href="<?php echo esc_html( $link ); ?>">View stats</a>
                        <a class="btn btn-common btn-rm" href="<?php echo esc_html( $link . '#share' ); ?>">Share your experience</a>
                    </div>
                </div>
            </div>
        </div>

    <?php }
}, 10, 2);

add_action( 'dt_campaigns_after_prayer_fuel', function ( $position, $posts = [], $todays_campaign_day = 0 ){
    if ( $position !== 'after_record_prayed' || $todays_campaign_day != 31 || empty( $posts ) ){
        return;
    }
    $link = site_url( '/prayer/stats' );
    if ( class_exists( 'DT_Campaign_Settings' ) ){
        $current_selected_porch = DT_Campaign_Settings::get( 'selected_porch' );
        if ( $current_selected_porch !== 'ramadan-porch' ){
            return;
        }
    } else {
        $campaign_fields = DT_Campaign_Landing_Settings::get_campaign();
        if ( $campaign_fields['porch_type']['key'] !== 'ramadan-porch' ){
            return;
        }
        $link = DT_Campaign_Landing_Settings::get_landing_root_url() . '/stats';
    }

    ?>
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 blog-item">
        <div class="blog-item-wrapper wow fadeInUp" data-wow-delay="0.3s">
            <div class="blog-item-text">
                <div class="section-header">
                    <h2 class="section-title">Thank you</h2>
                </div>
                <p>Thank you for joining over 6,000 people in praying during this Ramadan!</p>

                <p>Please continue praying for this place, especially all that He has put on your heart over the past month.</p>
                <p>Pray that God will continue working powerfully in this place.</p>

                <p>Before you leave check out the end of campaign stats page and
                    consider sharing with us about your prayer time (E.g. testimonies, insights, blessings, etc.)</p>
                <div style="display: flex; justify-content: center">
                    <a class="btn btn-common btn-rm" href="<?php echo esc_html( $link ); ?>">View stats</a>
                    <a class="btn btn-common btn-rm" href="<?php echo esc_html( $link . '#share' ); ?>">Share your experience</a>
                </div>
            </div>
        </div>
    </div>
    <?php
}, 10, 3);