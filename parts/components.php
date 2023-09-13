<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function dt_campaign_post( $post ) {
    $content = apply_filters( 'the_content', $post->post_content );
    $content = str_replace( ']]>', ']]&gt;', $content );
    ?>

    <div class="col-md-12 mb-5 wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s">
        <div class="fuel-block">
            <div class="section-header">
                <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( $post->post_title ) ?></h2>
            </div>
            <div class="">
                <?php echo wp_kses_post( $content ) ?>
            </div>
        </div>
    </div>

    <?php
}

function success_confirmation_section( $target = null ){
    ?>
    <div id='cp-success-confirmation-section' class='cp-view success-confirmation-section'>
        <div class='cell center'>
            <h2><?php esc_html_e( 'Success!', 'disciple-tools-prayer-campaigns' ); ?> &#9993;</h2>
            <p><?php esc_html_e( 'Your registration was successful.', 'disciple-tools-prayer-campaigns' ); ?></p>
            <p>
                <?php esc_html_e( 'Check your email for details and for access to manage your prayer times.', 'disciple-tools-prayer-campaigns' ); ?>
            </p>
            <p>
                <?php if ( !empty( $target ) ) : ?>
                    <a href="<?php echo esc_url( $target ) ?>" class='button'><?php esc_html_e( 'Return', 'disciple-tools-prayer-campaigns' ); ?></a>
                <?php else : ?>
                    <button class='button cp-nav cp-ok-done-button'><?php esc_html_e( 'Return', 'disciple-tools-prayer-campaigns' ); ?></button>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <?php
}
