

<!-- Footer Section Start -->
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
                        <?php echo wp_kses( DT_Porch_Settings::get_field_translation( 'footer_content' ), array(
                            'a' => array('href'),
                            'br' => array(),
                        ) ) ?><br>
                        Powered by <a href="https://disciple.tools">Disciple.Tools</a><br>
                        &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
                    </p>
                </div>
                <div class="site-info wow fadeInUp" data-wow-duration="1000ms" data-wow-delay="0.3s">
                    <p><a href="<?php echo esc_html( site_url( '/subscriptions' ) ); ?>">Login</a> | <a href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns' ) ); ?>">Page Settings</a></p>
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

<div id="loader">
    <div class="spinner">
        <div class="double-bounce1"></div>
        <div class="double-bounce2"></div>
    </div>
</div>
