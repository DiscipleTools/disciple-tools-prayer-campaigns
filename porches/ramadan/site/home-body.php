<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$lang = dt_campaign_get_current_lang();

$porch_fields = DT_Porch_Settings::settings();
$campaign_fields = DT_Campaign_Landing_Settings::get_campaign();
$root_url = DT_Campaign_Landing_Settings::get_landing_root_url();

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

<!-- MODALS -->
<div id="edit_modal_div"></div>
<!-- MODALS -->

<!-- Vision -->
<section id="campaign-vision" class="section">
    <div class="container">
        <div class="section-header row">
            <div class="col-sm-12 <?php echo esc_html( $campaign_has_end_date ? 'col-md-8' : 'col-md-12' ); ?>">
                <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php display_translated_field( 'vision_title', 'btn-common', true ); ?></h2>
                <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                <div style="padding: 1em">
                    <p class="section-subtitle wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php display_translated_field( 'vision' ); ?></p>
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
                    <h4 data-wow-duration="1000ms" data-wow-delay="0.3s"><?php display_translated_field( 'pray_section_title' ); ?></h4>
                    <p data-wow-duration="1000ms" data-wow-delay="0.3s"><?php display_translated_field( 'pray_section_text' ); ?></p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="item-boxes wow fadeInDown" data-wow-delay="0.4s">
                    <div class="icon">
                        <img class="<?php echo !empty( $porch_fields['movement_section_icon']['value'] ) ? '' : 'color-img'?>" style="height: 40px; margin-top:10px" src="<?php echo esc_html( DT_Porch_Settings::get_field_translation( 'movement_section_icon' ) ) ?>" alt="Movement icon"/>
                    </div>
                    <h4 data-wow-duration="1000ms" data-wow-delay="0.3s"><?php display_translated_field( 'movement_section_title' ); ?></h4>
                    <p data-wow-duration="1000ms" data-wow-delay="0.3s"><?php display_translated_field( 'movement_section_text' ); ?></p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="item-boxes wow fadeInDown" data-wow-delay="0.6s">
                    <div class="icon">
                        <img class="<?php echo !empty( $porch_fields['time_section_icon']['value'] ) ? '' : 'color-img'?>" style="height: 40px; margin-top:10px" src="<?php echo esc_html( DT_Porch_Settings::get_field_translation( 'time_section_icon' ) ) ?>" alt="Clock icon"/>
                    </div>
                    <h4 data-wow-duration="1000ms" data-wow-delay="0.3s"><?php display_translated_field( 'time_section_title' ); ?></h4>
                    <p data-wow-duration="1000ms" data-wow-delay="0.3s"><?php display_translated_field( 'time_section_text' ); ?></p>
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
                                starting_label="<?php echo esc_html__( 'Ramadan Begins ...', 'disciple-tools-prayer-campaigns' ); ?>"
                                ending_label="<?php echo esc_html__( 'Ramadan Ends ...', 'disciple-tools-prayer-campaigns' ); ?>"
                                is_finished="<?php echo esc_html__( 'Ramadan Is Finished', 'disciple-tools-prayer-campaigns' ); ?>"
                                end_time="<?php echo esc_html( $campaign_fields['end_date']['timestamp'] ); ?>"
                                start_time="<?php echo esc_html( $campaign_fields['start_date']['timestamp'] ); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row" style="margin-top: 30px">
            <div class="col-sm-12 col-md-7 col-lg-8 what-content-text" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php display_translated_field( 'what_content', 'btn-border' ); ?></div>
            <div class="col-sm-12 col-md-5 col-lg-4">
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
            <?php $days_in_campaign = DT_Campaign_Fuel::total_days_in_campaign();?>
            <div class="col-sm-6 col-md-4 col-lg-4">
                <div class="wow fadeInUp" data-wow-delay=".2s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-calendar-full"></i>
                        </div>
                        <div class="fact-count">

                            <?php if ( $campaign_has_end_date ): ?>

                                <h3><span class="counter"><?php echo $days_in_campaign !== -1 ? esc_html( $days_in_campaign ) : '30' ?></span></h3>
                                <h4><?php esc_html_e( 'Days', 'disciple-tools-prayer-campaigns' ); ?></h4>

                            <?php else : ?>

                                <h3><?php esc_html_e( 'Next', 'disciple-tools-prayer-campaigns' ) ?></h3>
                                <h4><?php esc_html_e( 'Month', 'disciple-tools-prayer-campaigns' ); ?></h4>

                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
            <?php $hours_next_month = DT_Campaigns_Base::number_of_hours_next_month() ?>
            <?php $more_hours_needed = $hours_next_month - ceil( DT_Campaigns_Base::query_scheduled_minutes_next_month( $campaign_fields['ID'] ) / 60 ) ?>
            <?php /* 96 daily prayer warriors would be needed to cover the campaign... Do I use that number? */ ?>
            <div class="col-sm-6 col-md-4 col-lg-4">
                <div class="wow fadeInUp" data-wow-delay=".6s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-user"></i>
                        </div>
                        <div class="fact-count">

                            <?php if ( $campaign_has_end_date ): ?>

                                <h3><?php echo $days_in_campaign !== -1 ? esc_html( $days_in_campaign * 24 ) : '720' ?></h3>
                                <h4><?php esc_html_e( 'Hours of Prayer', 'disciple-tools-prayer-campaigns' ); ?></h4>

                            <?php else : ?>

                                <h3><?php echo esc_html( $hours_next_month ) ?></h3>
                                <h4><?php esc_html_e( 'Hours', 'disciple-tools-prayer-campaigns' ); ?></h4>

                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-4">
                <div class="wow fadeInUp" data-wow-delay=".8s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-heart"></i>
                        </div>
                        <div class="fact-count">

                            <?php if ( $campaign_has_end_date ): ?>

                                <h3><?php echo $days_in_campaign !== -1 ? esc_html( $days_in_campaign * 24 * 4 ) : '2880' ?></h3>
                                <h4><?php esc_html_e( 'Prayer Commitments Needed', 'disciple-tools-prayer-campaigns' ); ?></h4>

                            <?php else : ?>

                                <h3><?php echo esc_html( $more_hours_needed ) ?></h3>
                                <h4><?php esc_html_e( 'Hours Remaining', 'disciple-tools-prayer-campaigns' ); ?></h4>

                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Counter Section End -->

<!-- Blog Section -->
<section id="blog" class="section">
    <!-- Container Starts -->
    <div class="container">
        <div class="section-header">
            <h2 class="section-title split-color wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php display_translated_field( 'prayer_fuel_title', 'btn-common', true ); ?></h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
            <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php display_translated_field( 'prayer_fuel_description' ); ?></p>
            <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s">
                <a href="<?php echo esc_html( $root_url . '/list' ); ?>" class="btn btn-common btn-rm"><?php esc_html_e( 'View All', 'disciple-tools-prayer-campaigns' ); ?></a>
            </p>
        </div>
    </div>
</section>
<!-- blog Section End -->
