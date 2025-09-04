<?php
$campaign_url = DT_Campaign_Landing_Settings::get_landing_root_url();
$campaign_id = DT_Campaign_Landing_Settings::get_campaign_id();
$campaign = DT_Campaign_Landing_Settings::get_campaign();
?>

<!-- Footer Section Start -->
<div id="loader">
    <div class="spinner">
        <div class="double-bounce1"></div>
        <div class="double-bounce2"></div>
    </div>
</div>
<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="social-icons wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s">
                    <ul>
                        <?php $porch_fields = DT_Porch_Settings::settings(); ?>
                        <!-- facebook -->
                        <?php if ( isset( $porch_fields['facebook']['value'] ) && !empty( $porch_fields['facebook']['value'] ) ) : ?>
                            <li class="facebook"><a href="<?php echo esc_url( $porch_fields['facebook']['value'] ) ?>"><i class="fa fa-facebook"></i></a></li>
                        <?php endif; ?>

                        <!-- instagram -->
                        <?php if ( isset( $porch_fields['instagram']['value'] ) && !empty( $porch_fields['instagram']['value'] ) ) : ?>
                            <li class="instagram"><a href="<?php echo esc_url( $porch_fields['instagram']['value'] ) ?>"><i class="fa fa-instagram"></i></a></li>
                        <?php endif; ?>

                        <!-- twitter -->
                        <?php if ( isset( $porch_fields['twitter']['value'] ) && !empty( $porch_fields['twitter']['value'] ) ) : ?>
                            <li class="twitter"><a href="<?php echo esc_url( $porch_fields['twitter']['value'] ) ?>"><i class="fa fa-twitter"></i></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="site-info wow fadeInUp" data-wow-duration="1000ms" data-wow-delay="0.3s">
                    <p>
                        <?php
                        if ( !empty( $porch_fields['footer_content']['value'] ) ) :
                            global $allowedtags;
                            echo wp_kses( DT_Porch_Settings::get_field_translation( 'footer_content' ), $allowedtags ) ?><br>
                        <?php endif; ?>
                        Built using <a href="https://prayer.tools/campaigns-tool/" target="_blank">Prayer.Tools</a> &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
                    </p>
                </div>
                <div class="site-info wow fadeInUp" data-wow-duration="1000ms" data-wow-delay="0.3s">
                    <p>
                        <?php if ( !is_user_logged_in() ) : ?>
                            <a href="<?php echo esc_html( wp_login_url( $campaign_url ) ); ?>">Login</a> |
                        <?php endif; ?>
                        <a href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns&campaign=' . $campaign_id . '&tab=campaign_landing' ) ); ?>">Campaign Settings</a> |
                        <a href="<?php echo esc_url( $campaign_url ) ?>/contact-us"> <?php
                            $campaign_name = !empty( $campaign['name'] ) ? $campaign['name'] : 'Us';
                            /* translators: %s: campaign name */
                            printf( esc_html__( 'Contact %s', 'disciple-tools-prayer-campaigns' ), esc_html( $campaign_name ) );
                        ?></a>
                        <?php do_action( 'campaign_footer_links' ); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- Footer Section End -->

<!-- Go To Top Link -->
<a href="#" class="back-to-top" aria-label="back to top">
    <i class="lnr lnr-arrow-up"></i>
</a>

