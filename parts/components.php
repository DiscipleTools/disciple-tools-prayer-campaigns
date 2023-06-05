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
    <style>
        .cp-wrapper .otp-input-wrapper {
            width: 240px;
            text-align: left;
            display: inline-block;
        }
        .cp-wrapper .otp-input-wrapper input {
            padding: 0;
            width: 264px;
            font-size: 22px;
            font-weight: 600;
            color: #3e3e3e;
            background-color: transparent;
            border: 0;
            margin-left: 2px;
            letter-spacing: 30px;
            font-family: sans-serif !important;
        }
        .cp-wrapper .otp-input-wrapper input:focus {
            box-shadow: none;
            outline: none;
        }
        .cp-wrapper .otp-input-wrapper svg {
            position: relative;
            display: block;
            width: 240px;
            height: 2px;
        }
    </style>
    <h2><?php esc_html_e( 'Confirm', 'disciple-tools-prayer-campaigns' ); ?></h2>
    <br>
    <form method="post" class="validate" target="_blank" novalidate >
        <div id="cp-submit-step-1">
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
                        <?php esc_html_e( 'Receive Prayer Time Notifications.', 'disciple-tools-prayer-campaigns' ); ?>
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
                    <button type="button" class="button loader" id="cp-confirm-email" name="subscribe" value="Subscribe">
                        <?php esc_html_e( 'Verify and submit', 'disciple-tools-prayer-campaigns' ); ?> <img class="cp-submit-form-spinner" style="display: none" src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>../spinner.svg" width="22px" alt="spinner "/></button>
                </div>
            </div>
        </div>
        <div style="display: none" id="cp-submit-step-2">
            <p>
                A confirmation code hase been sent to <span id="cp-sent-email"></span>. <br> Please enter the code below in the next 10 minutes to confirm your email address.
            </p>
            <label for='cp-confirmation-code' style="display: block">
                <strong><?php esc_html_e( 'Confirmation Code:', 'disciple-tools-prayer-campaigns' ); ?></strong><br>
                <span id="confirmation-error" class="form-error">
                    <?php echo esc_html( 'The code is required' ); ?>
                </span>
            </label>
            <div class='otp-input-wrapper' style="padding: 20px 0">
                <input class="cp-confirmation-code" id="cp-confirmation-code" type='text' maxlength='6' pattern='[0-9]*' autocomplete='off' required>
                <svg viewBox='0 0 240 1' xmlns='http://www.w3.org/2000/svg'>
                    <line x1='0' y1='0' x2='240' y2='0' stroke='#3e3e3e' stroke-width='2'
                          stroke-dasharray='20,22'/>
                </svg>
            </div>
            <div>
                <button type='submit' class='button loader' id='cp-submit-form' name='subscribe' value='Subscribe'>
                    <?php esc_html_e( 'Submit', 'disciple-tools-prayer-campaigns' ); ?> <img
                        class="cp-submit-form-spinner" style="display: none"
                        src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>../spinner.svg"
                        width="22px" alt="spinner "/></button>
            </div>

        </div>
        <div id="cp-form-error" class="form-error" style="display: none">

        </div>
    </form>
    <?php
}


function success_confirmation_section( $target = null ){
    ?>
    <div id='cp-success-confirmation-section' class='cp-view success-confirmation-section'>
        <div class='cell center'>
            <h2><?php esc_html_e( 'Success!', 'disciple-tools-prayer-campaigns' ); ?> &#9993;</h2>
            <p><?php esc_html_e( 'Your registration was successful.', 'disciple-tools-prayer-campaigns' ); ?></p>
            <p>
                <?php esc_html_e( 'Check you email for details and access to manage your prayer times.', 'disciple-tools-prayer-campaigns' ); ?>
            </p>
            <p>
                <? if ( !empty( $target ) ) : ?>
                    <a href="<?php echo esc_url( $target ) ?>" class='button'><?php esc_html_e( 'Return', 'disciple-tools-prayer-campaigns' ); ?></a>
                <?php else : ?>
                    <button class='button cp-nav cp-ok-done-button'><?php esc_html_e( 'Return', 'disciple-tools-prayer-campaigns' ); ?></button>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <?php
}