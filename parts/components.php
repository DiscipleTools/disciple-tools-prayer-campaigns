<?php

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
            <!-- @todo summary -->
            <!--            <p>-->
            <!--                You are signing up to praying for-->
            <!--                <span id="time-duration-confirmation" style="font-weight: bold" ></span>-->
            <!--                on each selected day at <span id="time-confirmation" style="font-weight: bold"></span>-->
            <!--                in <span class="timezone-current"></span> timezone.-->
            <!--            </p>-->
            <form
                action="https://training.us14.list-manage.com/subscribe/post?u=d9ad41b66865008d664ac28bf&amp;id=4df6e5ea4e"
                method="post"
                id="mc-embedded-subscribe-form"
                name="mc-embedded-subscribe-form"
                class="validate"
                target="_blank"
                novalidate
            >
                <div>
                    <span id="name-error" class="form-error">
                        <?php echo esc_html( "Your name is required" ); ?>
                    </span>
                    <label for="name"><?php esc_html_e( 'Name', 'disciple-tools-prayer-campaigns' ); ?><br>
                        <input class="cp-input" type="text" name="name" id="name" placeholder="<?php esc_html_e( 'Name', 'disciple-tools-prayer-campaigns' ); ?>" required/>
                    </label>
                </div>
                <div>
                    <span id="email-error" class="form-error">
                        <?php esc_html_e( "Your email is required.", 'disciple-tools-prayer-campaigns' ); ?>
                    </span>
                    <label for="email"><?php esc_html_e( 'Email', 'disciple-tools-prayer-campaigns' ); ?><br>
                        <input class="cp-input" type="email" name="email" id="email" placeholder="<?php esc_html_e( 'Email', 'disciple-tools-prayer-campaigns' ); ?>" />
                        <input class="cp-input" type="email" name="EMAIL" id="e2" placeholder="<?php esc_html_e( 'Email', 'disciple-tools-prayer-campaigns' ); ?>" required />
                    </label>
                    <div hidden="true"><input type="hidden" name="tags" value="7315913"></div>
                    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
                    <div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_d9ad41b66865008d664ac28bf_4df6e5ea4e" tabindex="-1" value=""></div>
                    <div id="mce-responses" class="clear">
                        <div class="response" id="mce-error-response" style="display:none"></div>
                    </div>
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
                            <input type="checkbox" id="receive_pray4movement_news" name="receive_pray4movement_news" checked />
                            <?php esc_html_e( 'Recieve Pray4Movement Newsletter (email verification needed).', 'disciple-tools-prayer-campaigns' ); ?>
                        </label>
                    </p>
                    <div>
                        <button type="submit" class="button loader" id="cp-submit-form" name="subscribe" value="Subscribe">
                            <?php esc_html_e( 'Submit Your Prayer Commitment', 'disciple-tools-prayer-campaigns' ); ?> <img id="cp-submit-form-spinner" style="display: none" src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>../spinner.svg" width="22px" alt="spinner "/></button>
                    </div>
                </div>
            </form>
    <?php
}
