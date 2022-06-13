

<!-- Footer Section Start -->
<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="social-icons wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s">
                    <ul>
                        <!-- facebook -->
                        <?php if ( isset( $porch_fields['facebook']["value"] ) && !empty( $porch_fields['facebook']["value"] ) ) : ?>
                            <li class="facebook"><a href="<?php echo esc_url( $porch_fields['facebook']["value"] ) ?>"><i class="fa fa-facebook"></i></a></li>
                        <?php endif; ?>

                        <!-- instagram -->
                        <?php if ( isset( $porch_fields['instagram']["value"] ) && !empty( $porch_fields['instagram']["value"] ) ) : ?>
                            <li class="instagram"><a href="<?php echo esc_url( $porch_fields['instagram']["value"] ) ?>"><i class="fa fa-instagram"></i></a></li>
                        <?php endif; ?>

                        <!-- twitter -->
                        <?php if ( isset( $porch_fields['twitter']["value"] ) && !empty( $porch_fields['twitter']["value"] ) ) : ?>
                            <li class="twitter"><a href="<?php echo esc_url( $porch_fields['twitter']["value"] ) ?>"><i class="fa fa-twitter"></i></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="site-info wow fadeInUp" data-wow-duration="1000ms" data-wow-delay="0.3s">
                    <p>
                        Made with &#10084;&#65039; by <a href="https://pray4movement.org">Pray4Movement.org</a><br>
                        Powered by <a href="https://disciple.tools">Disciple.Tools</a><br>
                        &copy; <?php echo esc_html( gmdate( "Y" ) ); ?>
                    </p>
                </div>
                <div class="site-info wow fadeInUp" data-wow-duration="1000ms" data-wow-delay="0.3s">
                    <p><a href="<?php echo esc_html( site_url( '/contacts' ) ); ?>">Login</a> | <a href="<?php echo esc_html( admin_url( 'edit.php?post_type=landing&page=dt_porch_template' ) ); ?>">Page Settings</a></p>
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

<!-- jQuery first, then Tether, then Bootstrap JS. -->
<script src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>js/jquery-min.js"></script>
<script src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>js/tether.min.js"></script>
<script src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>js/bootstrap.min.js"></script>
<script src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>js/classie.js"></script>
<!--<script src="--><?php //echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?><!--js/mixitup.min.js"></script>-->
<!--<script src="--><?php //echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?><!--js/nivo-lightbox.js"></script>-->
<!--<script src="--><?php //echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?><!--js/owl.carousel.min.js"></script>-->
<script src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>js/jquery.stellar.min.js"></script>
<script src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>js/jquery.nav.js"></script>
<script src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>js/smooth-scroll.js"></script>
<script src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>js/smooth-on-scroll.js"></script>
<script src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>js/wow.js"></script>
<script src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>js/menu.js"></script>
<!--<script src="--><?php //echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?><!--js/jquery.vide.js"></script>-->
<script src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>js/jquery.counterup.min.js"></script>
<!--<script src="--><?php //echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?><!--js/jquery.magnific-popup.min.js"></script>-->
<script src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>js/waypoints.min.js"></script>
<!--<script src="--><?php //echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?><!--js/form-validator.min.js"></script>-->
<!--<script src="--><?php //echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?><!--js/contact-form-script.js"></script>-->
<script src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>js/main.js?ver=<?php echo esc_attr( filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'js/main.js' ) ) ?>"></script>
