<?php
$lang = dt_campaign_get_current_lang();
$porch_fields = DT_Porch_Settings::settings();
$content = 'No post found';
if ( isset( $this->parts['post_id'] ) && ! empty( $this->parts['post_id'] ) ) {
    $my_postid = $this->parts['post_id'];//This is page id or post id
    global $wpdb;
    $content_post_id = $wpdb->get_var( $wpdb->prepare( "
        SELECT p.ID
        FROM $wpdb->posts p
        JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_ID AND pm.meta_key = 'prayer_fuel_magic_key' AND pm.meta_value = %s )
        JOIN $wpdb->postmeta lang ON ( p.ID = lang.post_ID AND lang.meta_key = 'post_language' AND lang.meta_value = %s )
        WHERE post_type = %s
        AND ( post_status = 'publish' OR post_status = 'future' )
    ", $this->parts['public_key'], $lang, PORCH_LANDING_POST_TYPE ) );

    if ( empty( $content_post_id ) ){
        $content_post_id = $wpdb->get_var( $wpdb->prepare( "
            SELECT p.ID
            FROM $wpdb->posts p
            JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_ID AND pm.meta_key = 'prayer_fuel_magic_key' AND pm.meta_value = %s )
            LEFT JOIN $wpdb->postmeta lang ON ( p.ID = lang.post_ID AND lang.meta_key = 'post_language' )
            WHERE post_type = %s
            AND ( post_status = 'publish' OR post_status = 'future' )
            AND ( lang.meta_value = 'en_US' OR lang.meta_value IS NULL )
        ", $this->parts['public_key'], PORCH_LANDING_POST_TYPE ) );
    }

    if ( !empty( $content_post_id ) ){
        $my_postid = $content_post_id;
    }


    $post_status = get_post_status( $my_postid );
    $content_post = get_post( $my_postid );
    if ( !empty( $content_post ) ){
        $content = $content_post->post_content;
        $content = apply_filters( 'the_content', $content );
        $content = str_replace( ']]>', ']]&gt;', $content );
    }
}
?>


<!-- Contact Section Start -->
<section id="contact" class="section">
    <div class="container">
        <div class="row justify-content-md-center">
            <div class="col-md-12 wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s">
                <div class="fuel-block">
                    <div class="section-header">
                        <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'prayer_fuel_name' ) ) ?></h2>
                        <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                    </div>
                    <div class="">
                        <?php echo wp_kses_post( $content ) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Contact Section End -->
