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

function dt_campaign_sign_up_form() {
    ?>
    <h2><?php esc_html_e( 'Confirm', 'disciple-tools-prayer-campaigns' ); ?></h2>
    <br>
    <form method="post" class="validate" target="_blank" novalidate >
        <div>
            <label for="name"><?php esc_html_e( 'Name', 'disciple-tools-prayer-campaigns' ); ?><br>
                <span id="name-error" class="form-error">
                    <?php echo esc_html( 'Your name is required' ); ?>
                </span>
                <input class="cp-input" type="text" name="name" id="name" placeholder="<?php esc_html_e( 'Name', 'disciple-tools-prayer-campaigns' ); ?>" required/>
            </label>
        </div>
        <div>
            <label for="email"><?php esc_html_e( 'Email', 'disciple-tools-prayer-campaigns' ); ?><br>
                <span id="email-error" class="form-error">
                    <?php esc_html_e( 'Your email is required.', 'disciple-tools-prayer-campaigns' ); ?>
                </span>
                <input class="cp-input" type="email" name="email" id="email" placeholder="<?php esc_html_e( 'Email', 'disciple-tools-prayer-campaigns' ); ?>" />
                <input class="cp-input" type="email" name="EMAIL" id="e2" placeholder="<?php esc_html_e( 'Email', 'disciple-tools-prayer-campaigns' ); ?>" required />
            </label>
        </div>
        <div>
            <p>
                <label for="receive_prayer_time_notifications">
                    <input type="checkbox" id="receive_prayer_time_notifications" name="receive_prayer_time_notifications" checked />
                    <?php esc_html_e( 'Receive Prayer Time Notifications (email verification needed).', 'disciple-tools-prayer-campaigns' ); ?>
                </label>
            </p>
            <p>
                <label for="receive_pray4movement_news">
                    <input type="checkbox" id="receive_pray4movement_news" name="receive_pray4movement_news" <?php checked( !WP_DEBUG ) ?>/>
                    <?php esc_html_e( 'Receive Pray4Movement news and opportunities, and occasional communication from GospelAmbition.org.', 'disciple-tools-prayer-campaigns' ); ?>
                </label>
            </p>
            <div id='cp-no-selected-times' style='display: none'class="form-error" >
                <?php esc_html_e( 'No prayer times selected', 'disciple-tools-prayer-campaigns' ); ?>
            </div>
            <div>
                <button type="submit" class="button loader" id="cp-submit-form" name="subscribe" value="Subscribe">
                    <?php esc_html_e( 'Submit Your Prayer Commitment', 'disciple-tools-prayer-campaigns' ); ?> <img id="cp-submit-form-spinner" style="display: none" src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>../spinner.svg" width="22px" alt="spinner "/></button>
            </div>
        </div>
    </form>
    <?php
}
