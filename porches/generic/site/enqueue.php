<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.
// Add Open Graph Protocol meta tags in header

add_action( 'wp_head', function() {
    $og_title = DT_Porch_Settings::get_field_translation( 'name' );
    $og_description = DT_Porch_Settings::get_field_translation( 'description' );
    $og_url = get_site_url();
    $campaign_fields = DT_Campaign_Landing_Settings::get_campaign();
    if ( !empty( $campaign_fields['logo_url'] ) ){
        $og_image_url = $campaign_fields['logo_url'];
    } else {
        $og_image_url = 'https://s3.gospelambition.org/icons%2Fp4m.png';
    }
    ?>


    <!-- Open Graph Protocol -->
    <meta property="og:title" content="<?php echo esc_attr( $og_title ); ?>"/>
    <meta property="og:description" content="<?php echo esc_attr( $og_description ); ?>"/>
    <meta property="og:type" content="article"/>
    <meta property="og:url" content="<?php echo esc_attr( $og_url ); ?>"/>
    <meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo() ); ?>"/>
    <meta property="og:image" content="<?php echo esc_attr( $og_image_url ); ?>" />
    <?php
});

add_action( 'wp_enqueue_scripts', function (){
    wp_enqueue_style( 'bootstrap', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.5.3/css/bootstrap.min.css', array(), '4.5.3' );
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/fontawesome.min.css', array(), '6.5.2' );
    wp_enqueue_style( 'line-icons', 'https://cdn.linearicons.com/free/1.0.0/icon-font.min.css', array(), '1.0.0' );
    wp_enqueue_style( 'animate-css', 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.7.2/animate.min.css', array(), '3.7.2' );
    wp_enqueue_style( 'main-styles', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'css/main.css', array(), filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'css/main.css' ) );
    wp_enqueue_style( 'menu_sideslide', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'css/menu_sideslide.css', array(), filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'css/menu_sideslide.css' ) );
    wp_enqueue_style( 'responsive', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'css/responsive.css', array(), filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'css/responsive.css' ) );
    $custom_css_path = trailingslashit( plugin_dir_path( __FILE__ ) ) . 'css/colors/' . CAMPAIGN_LANDING_COLOR_SCHEME . '.css';
    if ( file_exists( $custom_css_path ) ){
        wp_enqueue_style( 'p4m-colors', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'css/colors/' . CAMPAIGN_LANDING_COLOR_SCHEME . '.css', array(), filemtime( $custom_css_path ) );
    }
});
