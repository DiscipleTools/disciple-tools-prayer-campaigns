<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$lang = dt_campaign_get_current_lang();

$porch_fields = DT_Porch_Settings::settings();
$campaign_fields = DT_Campaign_Settings::get_campaign();
global $allowedtags;

if ( empty( $campaign_fields ) ): ?>
    <div class="container">
        <p style="margin:auto">Choose a campaign in settings <a href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns' ) );?>">here</a></p>
    </div>
    <?php die();
endif;


$campaign_has_end_date = !empty( $campaign_fields['end_date']['timestamp'] );
$campaign_root = 'campaign_app';
$campaign_type = 'ongoing';
$key_name = 'public_key';
$key = '';
if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
    $key_name = DT_Magic_URL::get_public_key_meta_key( $campaign_root, $campaign_type );
}
if ( isset( $campaign_fields[$key_name] ) ){
    $key = $campaign_fields[$key_name];
}
$atts = [
    'root' => $campaign_root,
    'type' => $campaign_type,
    'public_key' => $key,
    'meta_key' => $key_name,
    'post_id' => (int) $campaign_fields['ID'],
    'rest_url' => rest_url(),
    'lang' => $lang
];
$dt_campaign_selected_campaign_magic_link_settings = $atts;
$dt_campaign_selected_campaign_magic_link_settings['color'] = PORCH_COLOR_SCHEME_HEX;
if ( $dt_campaign_selected_campaign_magic_link_settings['color'] === 'preset' ){
    $dt_campaign_selected_campaign_magic_link_settings['color'] = '#4676fa';
}
?>

<!-- Vision -->
<section id="campaign-vision" class="section">
    <div class="container">
        <div class="section-header row">
            <div class="col-sm-12 <?php echo esc_html( $campaign_has_end_date ? 'col-md-8' : 'col-md-12' ); ?>">
                <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s">
                    <?php echo esc_html( dt_split_sentence( DT_Porch_Settings::get_field_translation( 'vision_title' ), 1, 2 ) ) ?> <span><?php echo esc_html( dt_split_sentence( DT_Porch_Settings::get_field_translation( 'vision_title' ), 2, 2 ) ) ?></span>
                </h2>
                <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                <div style="padding: 2em">
                <p class="section-subtitle wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s" style="padding:2em">
                    <?php echo nl2br( wp_kses( DT_Porch_Settings::get_field_translation( 'vision' ), $allowedtags ) ); ?>
                </p>
                </div>
            </div>
            <?php if ( $campaign_has_end_date ): ?>
                <div class="col-sm-12 col-md-4">
                    <?php dt_generic_percentage_shortcode( $dt_campaign_selected_campaign_magic_link_settings ); ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="row">
            <div class="col-md-4 col-sm-6">
                <div class="item-boxes wow fadeInDown" data-wow-delay="0.2s">
                    <div class="icon">
                        <img class="<?php echo !empty( $porch_fields['pray_section_icon']['value'] ) ? '' : 'color-img'?>" style="height: 40px; margin-top:10px" src="<?php echo esc_html( DT_Porch_Settings::get_field_translation( 'pray_section_icon' ) ) ?>" alt="Praying hands icon"/>
                    </div>
                    <h4><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'pray_section_title' ) ) ?></h4>
                    <p><?php echo wp_kses( DT_Porch_Settings::get_field_translation( 'pray_section_text' ), $allowedtags ) ?></p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="item-boxes wow fadeInDown" data-wow-delay="0.4s">
                    <div class="icon">
                        <img class="<?php echo !empty( $porch_fields['movement_section_icon']['value'] ) ? '' : 'color-img'?>" style="height: 40px; margin-top:10px" src="<?php echo esc_html( DT_Porch_Settings::get_field_translation( 'movement_section_icon' ) ) ?>" alt="Movement icon"/>
                    </div>
                    <h4><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'movement_section_title' ) ) ?></h4>
                    <p><?php echo wp_kses( DT_Porch_Settings::get_field_translation( 'movement_section_text' ), $allowedtags ) ?></p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="item-boxes wow fadeInDown" data-wow-delay="0.6s">
                    <div class="icon">
                        <img class="<?php echo !empty( $porch_fields['time_section_icon']['value'] ) ? '' : 'color-img'?>" style="height: 40px; margin-top:10px" src="<?php echo esc_html( DT_Porch_Settings::get_field_translation( 'time_section_icon' ) ) ?>" alt="Clock icon"/>
                    </div>
                    <h4><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'time_section_title' ) ) ?></h4>
                    <p><?php echo wp_kses( DT_Porch_Settings::get_field_translation( 'time_section_text' ), $allowedtags ) ?></p>
                </div>
            </div>
        </div>
        <div class="row" style="justify-content: center">
            <a href="#sign-up" class="btn btn-common"><?php esc_html_e( 'Sign Up to Pray', 'disciple-tools-prayer-campaigns' ); ?></a>
        </div>

    </div>
</section>
<!-- Services Section End -->


<!-- COUNTER ROW -->
<div id="days_until" class="counters section" data-stellar-background-ratio="0.5" >
    <div class="overlay"></div>
    <div class="container">
        <div id="counter_row" class="row">
            <div class="col-sm-12">
                <div class="wow fadeInUp" data-wow-delay=".3s">
                    <div class="facts-item">
                        <div class="fact-count">
                            <counter-row
                                end_time="<?php echo esc_html( $campaign_fields['end_date']['timestamp'] ?? null ); ?>"
                                start_time="<?php echo esc_html( $campaign_fields['start_date']['timestamp'] ); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row" style="margin-top: 30px">
            <div class="col-sm-12 col-md-8 what-content-text">
                <?php
                    echo wp_kses( DT_Porch_Settings::get_field_translation( 'what_content' ), $allowedtags );
                ?>
            </div>
            <div class="col-sm-12 col-md-4">
                <?php dt_generic_calendar_shortcode( $dt_campaign_selected_campaign_magic_link_settings ); ?>
            </div>
        </div>
    </div>
</div>

<!-- SIGN UP TO PRAY -->
<section id="features" class="section" data-stellar-background-ratio="0.2">
    <div id="sign-up" name="sign-up" class="container">
        <div class="section-header">
            <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'Sign Up to Pray', 'disciple-tools-prayer-campaigns' ); ?></h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
        </div>
        <div class="row">
            <?php dt_generic_signup_shortcode( $dt_campaign_selected_campaign_magic_link_settings ); ?>
        </div>
    </div>
</section>
<!-- Features Section End -->

<!-- COUNTER ROW -->
<div class="counters section" data-stellar-background-ratio="0.5" >
    <div class="overlay"></div>
    <div class="container">
        <div class="row">
            <?php
            $days_in_campaign = DT_Campaign_Settings::total_days_in_campaign();
            $minutes_committed = DT_Campaigns_Base::get_minutes_prayed_and_scheduled( $campaign_fields['ID'] );
            $time_committed = DT_Time_Utilities::display_minutes_in_time( $minutes_committed );

            $size = $campaign_has_end_date ? 'col-sm-6 col-md-4 col-lg-4' : 'col-sm-6 col-md-6 col-lg-6';
            ?>
            <div class="<?php echo esc_html( $size ); ?>">
                <div class="wow fadeInUp" data-wow-delay=".2s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-calendar-full"></i>
                        </div>
                        <div class="fact-count">

                            <h3><?php echo esc_html( $time_committed ); ?></h3>
                            <h4><?php esc_html_e( 'Time Committed', 'disciple-tools-prayer-campaigns' ) ?></h4>

                        </div>
                    </div>
                </div>
            </div>
            <?php $subscribers_count =DT_Subscriptions::get_subscribers_count( $campaign_fields['ID'] ); ?>

            <div class="<?php echo esc_html( $size ); ?>">
                <div class="wow fadeInUp" data-wow-delay=".6s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-user"></i>
                        </div>
                        <div class="fact-count">
                            <h3><?php echo esc_html( $subscribers_count ?? 0 ) ?></h3>
                            <h4><?php esc_html_e( 'Prayer Warriors', 'disciple-tools-prayer-campaigns' ); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ( $campaign_has_end_date ) : ?>
            <div class="<?php echo esc_html( $size ); ?>">
                <div class="wow fadeInUp" data-wow-delay=".8s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-heart"></i>
                        </div>
                        <div class="fact-count">

                                <h3><?php echo $days_in_campaign !== -1 ? esc_html( $days_in_campaign * 24 * 4 ) : '2880' ?></h3>
                                <h4><?php esc_html_e( 'Prayer Commitments Needed', 'disciple-tools-prayer-campaigns' ); ?></h4>

                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Counter Section End -->

<!-- Blog Section -->
<section id="blog" class="section">
    <!-- Container Starts -->
    <div class="container">
        <div class="section-header">
            <h2 class="section-title split-color wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( dt_split_sentence( DT_Porch_Settings::get_field_translation( 'prayer_fuel_title' ), 1, 2 ) ) ?> <span><?php echo esc_html( dt_split_sentence( DT_Porch_Settings::get_field_translation( 'prayer_fuel_title' ), 2, 2 ) ) ?></span> </h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
            <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'prayer_fuel_description' ) ); ?></p>
            <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s"><a href="<?php echo esc_html( site_url( '/prayer/list' ) ); ?>" class="btn btn-common btn-rm"><?php esc_html_e( 'View All', 'disciple-tools-prayer-campaigns' ); ?></a></p>
        </div>
    </div>
</section>
<!-- blog Section End -->
