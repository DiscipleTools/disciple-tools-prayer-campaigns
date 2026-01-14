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
$dt_campaign_selected_campaign_magic_link_settings['color'] = CAMPAIGN_LANDING_COLOR_SCHEME_HEX;
if ( $dt_campaign_selected_campaign_magic_link_settings['color'] === 'preset' ){
    $dt_campaign_selected_campaign_magic_link_settings['color'] = '#4676fa';
}
$video_url = DT_Porch_Settings::get_field_translation( 'promo_video_url', $lang, $campaign_fields['ID'] ) ?? '';

?>

<!-- MODALS -->
<div id="edit_modal_div"></div>
<!-- MODALS -->

<!-- Why Pray -->
<section id="why-pray" class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php display_translated_field( 'vision_title', 'btn-common', true ); ?></h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
            <p class="section-subtitle wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php display_translated_field( 'vision' ); ?></p>
        </div>
        <div class="row">
            <div class="col-md-4 col-sm-6">
                <div class="item-boxes wow fadeInDown" data-wow-delay="0.2s">
                    <div class="icon">
                        <img class="<?php echo !empty( $porch_fields['pray_section_icon']['value'] ) ? '' : 'color-img'?>" style="height: 40px; margin-top:10px" src="<?php echo esc_html( DT_Porch_Settings::get_field_translation( 'pray_section_icon' ) ) ?>" alt="Praying hands icon"/>
                    </div>
                    <h4><?php display_translated_field( 'pray_section_title' ); ?></h4>
                    <p><?php display_translated_field( 'pray_section_text' ); ?></p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="item-boxes wow fadeInDown" data-wow-delay="0.4s">
                    <div class="icon">
                        <img class="<?php echo !empty( $porch_fields['movement_section_icon']['value'] ) ? '' : 'color-img'?>" style="height: 40px; margin-top:10px" src="<?php echo esc_html( DT_Porch_Settings::get_field_translation( 'movement_section_icon' ) ) ?>" alt="Movement icon"/>
                    </div>
                    <h4><?php display_translated_field( 'movement_section_title' ); ?></h4>
                    <p><?php display_translated_field( 'movement_section_text' ); ?></p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="item-boxes wow fadeInDown" data-wow-delay="0.6s">
                    <div class="icon">
                        <img class="<?php echo !empty( $porch_fields['time_section_icon']['value'] ) ? '' : 'color-img'?>" style="height: 40px; margin-top:10px" src="<?php echo esc_html( DT_Porch_Settings::get_field_translation( 'time_section_icon' ) ) ?>" alt="Clock icon"/>
                    </div>
                    <h4><?php display_translated_field( 'time_section_title' ); ?></h4>
                    <p><?php display_translated_field( 'time_section_text' ); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Why Pray Section End -->

<!-- How It Works -->
<div id="how-it-works" class="counters section" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="section-header">
            <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s" style="color: #fff;"><?php esc_html_e( 'How It Works', 'disciple-tools-prayer-campaigns' ); ?></h2>
        </div>
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="wow fadeInUp" data-wow-delay=".2s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-pencil"></i>
                        </div>
                        <div class="fact-count">
                            <strong><?php esc_html_e( 'Sign up to pray', 'disciple-tools-prayer-campaigns' ); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="wow fadeInUp" data-wow-delay=".4s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-envelope"></i>
                        </div>
                        <div class="fact-count">
                            <strong><?php esc_html_e( 'Receive email reminders', 'disciple-tools-prayer-campaigns' ); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="wow fadeInUp" data-wow-delay=".6s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-clock"></i>
                        </div>
                        <div class="fact-count">
                            <strong><?php esc_html_e( 'Spend 15 minutes with guided prayers', 'disciple-tools-prayer-campaigns' ); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="wow fadeInUp" data-wow-delay=".8s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-heart"></i>
                        </div>
                        <div class="fact-count">
                            <strong><?php esc_html_e( 'Encounter God as you pray for the nations', 'disciple-tools-prayer-campaigns' ); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- How It Works Section End -->

<!-- About Ramadan -->
<section id="about-ramadan" class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'About Ramadan', 'disciple-tools-prayer-campaigns' ); ?></h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
        </div>
        <div class="row">
            <div class="col-sm-12"><?php display_translated_field( 'what_content' ); ?></div>
        </div>
    </div>
</section>
<!-- About Ramadan Section End -->

<!-- Campaign Info - Shared PHP Variables -->
<?php
$start_date = isset( $campaign_fields['start_date']['timestamp'] ) ? gmdate( 'F j, Y', $campaign_fields['start_date']['timestamp'] ) : '';
$start_date_short = isset( $campaign_fields['start_date']['timestamp'] ) ? gmdate( 'M j', $campaign_fields['start_date']['timestamp'] ) : '';
$end_date = isset( $campaign_fields['end_date']['timestamp'] ) ? gmdate( 'F j, Y', $campaign_fields['end_date']['timestamp'] ) : '';
$end_date_short = isset( $campaign_fields['end_date']['timestamp'] ) ? gmdate( 'M j, Y', $campaign_fields['end_date']['timestamp'] ) : '';
$minutes_committed = DT_Campaigns_Base::get_minutes_prayed_and_scheduled( $campaign_fields['ID'] );
$time_committed = DT_Time_Utilities::display_minutes_in_time( $minutes_committed );
$subscribers_count = DT_Subscriptions::get_subscribers_count( $campaign_fields['ID'] );

// Calculate days until start
$now = time();
$start_timestamp = $campaign_fields['start_date']['timestamp'] ?? 0;
$end_timestamp = $campaign_fields['end_date']['timestamp'] ?? 0;
$days_until_start = $start_timestamp > $now ? ceil( ( $start_timestamp - $now ) / DAY_IN_SECONDS ) : 0;
$days_until_end = $end_timestamp > $now ? ceil( ( $end_timestamp - $now ) / DAY_IN_SECONDS ) : 0;
$campaign_started = $now >= $start_timestamp;
$campaign_ended = $now >= $end_timestamp;

// Calculate progress percentage
$coverage_progress = 0;
if ( $campaign_has_end_date && function_exists( 'DT_Campaigns_Base::query_coverage_percentage' ) ) {
    $coverage_progress = DT_Campaigns_Base::query_coverage_percentage( $campaign_fields['ID'] );
}
?>

<!-- Campaign Info -->
<div id="campaign-info" class="counters section" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row" style="justify-content: space-between;">
            <!-- Left Column: Info Stack -->
            <div class="col-sm-12 col-md-7 col-lg-6">
                <!-- Countdown Block -->
                <div style="margin-bottom: 30px;">
                    <h4 style="color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 2px; font-size: 0.9em; margin-bottom: 10px;">
                        <?php if ( $campaign_ended ): ?>
                            <?php esc_html_e( 'Campaign Complete', 'disciple-tools-prayer-campaigns' ); ?>
                        <?php elseif ( $campaign_started ): ?>
                            <?php esc_html_e( 'Days Remaining', 'disciple-tools-prayer-campaigns' ); ?>
                        <?php else : ?>
                            <?php esc_html_e( 'Ramadan Begins In', 'disciple-tools-prayer-campaigns' ); ?>
                        <?php endif; ?>
                    </h4>
                    <div style="color: #fff; font-size: 3.5em; font-weight: bold; line-height: 1;">
                        <?php if ( $campaign_ended ): ?>
                            ✓
                        <?php elseif ( $campaign_started ): ?>
                            <?php echo esc_html( $days_until_end ); ?> <span style="font-size: 0.4em; font-weight: normal;"><?php esc_html_e( 'days', 'disciple-tools-prayer-campaigns' ); ?></span>
                        <?php else : ?>
                            <?php echo esc_html( $days_until_start ); ?> <span style="font-size: 0.4em; font-weight: normal;"><?php esc_html_e( 'days', 'disciple-tools-prayer-campaigns' ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="color: rgba(255,255,255,0.8); margin-top: 10px;">
                        <strong><?php echo esc_html( $start_date ); ?></strong> — <strong><?php echo esc_html( $end_date ); ?></strong>
                    </div>
                </div>
                <!-- Progress Block -->
                <?php if ( $campaign_has_end_date ): ?>
                <div style="margin-bottom: 30px;">
                    <h4 style="color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 2px; font-size: 0.9em; margin-bottom: 15px;">
                        <?php esc_html_e( 'Prayer Coverage', 'disciple-tools-prayer-campaigns' ); ?>
                    </h4>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="flex: 1; background: rgba(255,255,255,0.2); border-radius: 10px; height: 16px; overflow: hidden;">
                            <div style="background: #fff; height: 100%; width: <?php echo esc_attr( min( $coverage_progress, 100 ) ); ?>%; border-radius: 10px;"></div>
                        </div>
                        <div style="color: #fff; font-size: 1.5em; font-weight: bold; min-width: 60px;"><?php echo esc_html( $coverage_progress ); ?>%</div>
                    </div>
                    <div style="color: rgba(255,255,255,0.7); font-size: 0.9em; margin-top: 8px;">
                        <?php
                        $campaign_goal = Campaign_Utils::get_campaign_goal( $campaign_fields );
                        if ( $campaign_goal === 'quantity' ) {
                            $goal_hours = Campaign_Utils::get_campaign_goal_quantity( $campaign_fields );
                            /* translators: %d is the number of hours per day */
                            printf( esc_html__( 'Goal: %d hours/day coverage', 'disciple-tools-prayer-campaigns' ), absint( $goal_hours ) );
                        } else {
                            esc_html_e( 'Goal: 24/7 coverage', 'disciple-tools-prayer-campaigns' );
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                <!-- Inline Stats (Strategy A style) -->
                <?php
                // Use 2 for plural (matches display_minutes_in_time logic where only 1 is singular)
                $days_committed_raw = floor( $minutes_committed / 60 / 24 );
                $committed_plural_count = ( (int) $days_committed_raw === 1 ) ? 1 : 2;
                ?>
                <div style="display: flex; gap: 40px; flex-wrap: wrap; margin-bottom: 20px; align-items: baseline;">
                    <div style="color: #fff; display: flex; align-items: baseline;">
                        <i class="lnr lnr-user" style="font-size: 1.5em; margin-right: 10px;"></i>
                        <span style="font-size: 2em; font-weight: bold;"><?php echo esc_html( $subscribers_count ?? 0 ); ?></span>
                        <span style="margin-left: 8px;"><?php esc_html_e( 'Prayer Warriors', 'disciple-tools-prayer-campaigns' ); ?></span>
                    </div>
                    <div style="color: #fff; display: flex; align-items: baseline;">
                        <i class="lnr lnr-calendar-full" style="font-size: 1.5em; margin-right: 10px;"></i>
                        <span style="font-size: 1.5em; font-weight: bold;"><?php echo esc_html( $time_committed ); ?></span>
                        <span style="margin-left: 8px;"><?php echo esc_html( _n( 'committed', 'committed', $committed_plural_count, 'disciple-tools-prayer-campaigns' ) ); ?></span>
                    </div>
                </div>
            </div>
            <!-- Right Column: Calendar -->
            <div class="col-sm-12 col-md-5 col-lg-4">
                <?php echo dt_generic_calendar_shortcode( $dt_campaign_selected_campaign_magic_link_settings ); //phpcs:ignore ?>
            </div>
        </div>
    </div>
</div>

<!-- Campaign Info Section End -->

<!-- SIGN UP TO PRAY -->
<section id="features" class="section" data-stellar-background-ratio="0.2">
    <div id="sign-up" name="sign-up" class="container">
        <div class="section-header">
            <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'Sign Up to Pray', 'disciple-tools-prayer-campaigns' ); ?></h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
        </div>
        <div class="row">
            <?php echo dt_generic_signup_shortcode( $dt_campaign_selected_campaign_magic_link_settings ); //phpcs:ignore ?>
        </div>
    </div>
</section>
<!-- Sign Up Section End -->

<!-- Prayer Fuel Section -->
<section id="blog" class="section" style="background-color: <?php
    $color = CAMPAIGN_LANDING_COLOR_SCHEME_HEX === 'preset' ? '#4676fa' : CAMPAIGN_LANDING_COLOR_SCHEME_HEX;
    // Convert hex to rgba with opacity for lighter appearance
    $hex = ltrim( $color, '#' );
    $r = hexdec( substr( $hex, 0, 2 ) );
    $g = hexdec( substr( $hex, 2, 2 ) );
    $b = hexdec( substr( $hex, 4, 2 ) );
    echo esc_attr( "rgba($r, $g, $b, 0.7)" );
?>;">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title" style="color: #fff;"><?php display_translated_field( 'prayer_fuel_title', 'btn-common', true ); ?></h2>
            <p style="color: rgba(255,255,255,0.9); margin-bottom: 20px;"><?php display_translated_field( 'prayer_fuel_description' ); ?></p>
            <p>
                <a href="<?php echo esc_html( $root_url . '/list' ); ?>" class="btn btn-common"><?php esc_html_e( 'View All', 'disciple-tools-prayer-campaigns' ); ?></a>
            </p>
        </div>
    </div>
</section>
<!-- Prayer Fuel Section End -->
