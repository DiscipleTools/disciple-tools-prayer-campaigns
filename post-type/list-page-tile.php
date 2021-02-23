<?php
/**
 * Adds tile at the left bottom of the Subscription lists page
 */
add_action( 'dt_post_list_filters_sidebar', function( $post_type ) {
    if ( 'prayers' === $post_type ) {
        ?>
        <div class="bordered-box" style="margin-top: 1em;">
            <div class="section-header"><?php esc_html_e( 'Public Apps', 'disciple-tools-list-exports' )?>&nbsp;
                <button class="float-right" data-open="export-help-text">
                    <img class="help-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>" alt="help"/>
                </button>
                <button class="section-chevron chevron_down">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>" alt="expand"/>
                </button>
                <button class="section-chevron chevron_up">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>" alt="collapse"/>
                </button>
            </div>
            <div class="section-body" style="padding-top:1em;">
                <a id="subscription-page" href="/prayers_app/subscription/" ><?php esc_html_e( "subscription page", 'disciple-tools-prayers' ) ?></a><br>
            </div>
        </div>
        <?php
    }
}, 20 );
