<?php


add_action( 'campaign_management_signup_controls', function ( $current_selected_porch ){
    if ( empty( $current_selected_porch ) || $current_selected_porch === '24hour' || $current_selected_porch === 'ongoing' ){
        ?>
        <div class="center">
            <button class="button" data-open="daily-select-modal" id="open-select-times-button"
                    style="margin-top: 10px">
                <?php esc_html_e( 'Add a Daily Prayer Time', 'disciple-tools-prayer-campaigns' ); ?>
            </button>
            <button class="button" data-open="select-times-modal" id="open-select-times-button"
                    style="margin-top: 10px">
                <?php esc_html_e( 'Add Individual Prayer Times', 'disciple-tools-prayer-campaigns' ); ?>
            </button>
            <a class="button" style="margin-top: 10px" target="_blank"
               href="<?php echo esc_attr( self::get_download_url() ); ?>"><?php esc_html_e( 'Download Calendar', 'disciple-tools-prayer-campaigns' ); ?></a>
        </div>
        <div style='padding: 40px; display: none' class='center' id='cp-missing-times-container'>
            <h3><?php esc_html_e( 'Help us cover these prayer times', 'disciple-tools-prayer-campaigns' ); ?></h3>
            <div id='cp-missing-time-slots'></div>
        </div>

        <h3 class="mc-title"><?php esc_html_e( 'My commitments', 'disciple-tools-prayer-campaigns' ); ?></h3>
        <div id="mobile-commitments-container">
        </div>

        <div class="reveal cp-wrapper" id="daily-select-modal" data-reveal>
            <label>
                <strong><?php esc_html_e( 'Prayer Time', 'disciple-tools-prayer-campaigns' ); ?></strong>
                <select id="cp-daily-time-select" class="cp-daily-time-select">
                    <option><?php esc_html_e( 'Daily Time', 'disciple-tools-prayer-campaigns' ); ?></option>
                </select>
            </label>
            <label>
                <strong><?php esc_html_e( 'For how long', 'disciple-tools-prayer-campaigns' ); ?></strong>
                <select id="cp-prayer-time-duration-select" class="cp-time-duration-select"></select>
            </label>
            <p class="timezone-label">
                <svg height='16px' width='16px' fill="#000000" xmlns="http://www.w3.org/2000/svg"
                     xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" viewBox="0 0 100 100"
                     enable-background="new 0 0 100 100" xml:space="preserve"><path
                        d="M50,13c20.4,0,37,16.6,37,37S70.4,87,50,87c-20.4,0-37-16.6-37-37S29.6,13,50,13 M50,5C25.1,5,5,25.1,5,50s20.1,45,45,45  c24.9,0,45-20.1,45-45S74.9,5,50,5L50,5z"></path>
                    <path
                        d="M77.9,47.8l-23.4-2.1L52.8,22c-0.1-1.5-1.3-2.6-2.8-2.6h-0.8c-1.5,0-2.8,1.2-2.8,2.7l-1.6,28.9c-0.1,1.3,0.4,2.5,1.2,3.4  c0.9,0.9,2,1.4,3.3,1.4h0.1l28.5-2.2c1.5-0.1,2.6-1.3,2.6-2.9C80.5,49.2,79.3,48,77.9,47.8z"></path></svg>
                <a href="javascript:void(0)" data-open="timezone-changer" class="timezone-current"></a>
            </p>

            <div class="success-confirmation-section">
                <div class="cell center">
                    <h2><?php esc_html_e( 'Your new prayer times have been saved.', 'disciple-tools-prayer-campaigns' ); ?></h2>
                </div>
            </div>


            <div class="center hide-on-success">
                <button class="button button-cancel clear select-view" data-close aria-label="Close reveal"
                        type="button">
                    <?php echo esc_html__( 'Cancel', 'disciple-tools-prayer-campaigns' ) ?>
                </button>

                <button disabled id="cp-confirm-daily-times" class="cp-nav button submit-form-button loader">
                    <?php esc_html_e( 'Confirm Times', 'disciple-tools-prayer-campaigns' ); ?>
                </button>
            </div>

            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
            <div class="center">
                <button class="button success-confirmation-section close-ok-success" data-close
                        aria-label="Close reveal" type="button">
                    <?php echo esc_html__( 'ok', 'disciple-tools-prayer-campaigns' ) ?>
                </button>
            </div>

        </div>

        <div class="reveal cp-wrapper" id="select-times-modal" data-reveal data-close-on-click="false" data-multiple-opened="true">

            <h2 id="individual-day-title" class="cp-center">
                <?php esc_html_e( 'Select a day and choose a time', 'disciple-tools-prayer-campaigns' ); ?>
            </h2>
            <div id="cp-day-content" class="cp-center" >
                <div style="margin-bottom: 20px">
                    <div id="day-select-calendar" class=""></div>
                </div>
                <label>
                    <strong><?php esc_html_e( 'Select a prayer time', 'disciple-tools-prayer-campaigns' ); ?></strong>
                    <select id="cp-individual-time-select" disabled style="margin: auto">
                        <option><?php esc_html_e( 'Daily Time', 'disciple-tools-prayer-campaigns' ); ?></option>
                    </select>
                </label>
                <label>
                    <strong><?php esc_html_e( 'For how long', 'disciple-tools-prayer-campaigns' ); ?></strong>
                    <select id="cp-individual-prayer-time-duration-select" class="cp-time-duration-select" style="margin: auto"></select>
                </label>
                <div>
                    <button class="button" id="cp-add-prayer-time" data-day="" disabled style="margin: 10px 0; display: inline-block"><?php esc_html_e( 'Add prayer time', 'disciple-tools-prayer-campaigns' ); ?></button>
                    <span style="display: none" id="cp-time-added"><?php esc_html_e( 'Time added', 'disciple-tools-prayer-campaigns' ); ?></span>
                </div>
                <p class="timezone-label">
                    <svg height='16px' width='16px' fill="#000000" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve"><path d="M50,13c20.4,0,37,16.6,37,37S70.4,87,50,87c-20.4,0-37-16.6-37-37S29.6,13,50,13 M50,5C25.1,5,5,25.1,5,50s20.1,45,45,45  c24.9,0,45-20.1,45-45S74.9,5,50,5L50,5z"></path><path d="M77.9,47.8l-23.4-2.1L52.8,22c-0.1-1.5-1.3-2.6-2.8-2.6h-0.8c-1.5,0-2.8,1.2-2.8,2.7l-1.6,28.9c-0.1,1.3,0.4,2.5,1.2,3.4  c0.9,0.9,2,1.4,3.3,1.4h0.1l28.5-2.2c1.5-0.1,2.6-1.3,2.6-2.9C80.5,49.2,79.3,48,77.9,47.8z"></path></svg><a href="javascript:void(0)" data-open="timezone-changer" class="timezone-current"></a>
                </p>

                <div style="margin: 30px 0">
                    <h3><?php esc_html_e( 'Selected Times', 'disciple-tools-prayer-campaigns' ); ?></h3>
                    <ul class="cp-display-selected-times">
                        <li><?php esc_html_e( 'No selected Time', 'disciple-tools-prayer-campaigns' ); ?></li>
                    </ul>
                </div>
            </div>

            <div class="success-confirmation-section">
                <div class="cell center">
                    <h2><?php esc_html_e( 'Your new prayer times have been saved.', 'disciple-tools-prayer-campaigns' ); ?></h2>
                </div>
            </div>


            <div class="center hide-on-success">
                <button class="button button-cancel clear select-view" data-close aria-label="Close reveal" type="button">
                    <?php echo esc_html__( 'Cancel', 'disciple-tools-prayer-campaigns' )?>
                </button>

                <button disabled id="cp-confirm-individual-times" class="button submit-form-button loader">
                    <?php esc_html_e( 'Confirm Times', 'disciple-tools-prayer-campaigns' ); ?>
                </button>
            </div>
            <button class="button button-cancel clear confirm-view" id="back-to-select" aria-label="Close reveal" type="button">
                <?php echo esc_html__( 'Back', 'disciple-tools-prayer-campaigns' )?>
            </button>

            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
            <div class="center">
                <button class="button success-confirmation-section close-ok-success" data-close aria-label="Close reveal" type="button">
                    <?php echo esc_html__( 'ok', 'disciple-tools-prayer-campaigns' )?>
                </button>
            </div>
        </div>
        <?php
    }
} );
