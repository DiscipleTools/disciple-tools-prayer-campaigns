<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $allowed_tags;
$allowed_tags['script'] = array(
    'async' => array(),
    'src' => array()
);
$porch_fields = DT_Porch_Settings::settings();
?>

<!-- Required meta tags -->
<?php
if ( ! empty( $porch_fields['google_analytics']['value'] ) ) { ?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $porch_fields['google_analytics']['value'] ); //phpcs:ignore?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', '<?php echo esc_attr( $porch_fields['google_analytics']['value'] );?>');
</script>
<?php } ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">

<script>
    let jsObject = [<?php echo json_encode([
        'theme_uri' => trailingslashit( get_stylesheet_directory_uri() ),
        'root' => esc_url_raw( rest_url() ),
        'nonce' => wp_create_nonce( 'wp_rest' ),
        'parts' => [
            'root' => $this->root,
            'type' => $this->type,
        ],
        'trans' => [],
    ]) ?>][0]
</script>

<style type="text/css">
    .stencil-background {
        background-image: url(<?php echo esc_html( empty( $porch_fields['header_background_url']['value'] ) ? trailingslashit( plugin_dir_url( __FILE__ ) ) . 'img/stencil-header.png' : $porch_fields['header_background_url']['value'] ) ?>);
        background-size: cover;
        background-repeat: no-repeat;
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
    }
    .logo-background {

    }
    .fixed-top.menu-bg .logo {
        color: <?php echo esc_attr( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ) ?> !important;
    }
    a {
        color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>
    }
    a:hover {
        color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .btn-common {
        border-color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
        background-color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .btn-common:hover {
        border-color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
        color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .btn-border:hover {
        background-color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .section-header .section-title span {
        color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .section-header .lines {
        border-color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .section-header .lines:before {
        background: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .overlay {
        background: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .navbar-light .navbar-nav .active > .nav-link, .navbar-light .navbar-nav .nav-link.active, .navbar-light .navbar-nav .nav-link.active::before, .navbar-light .navbar-nav .nav-link.open, .navbar-light .navbar-nav .open > .nav-link {
        color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .navbar-light .navbar-nav .nav-link:focus, .navbar-light .navbar-nav .nav-link:hover, .navbar-light .navbar-nav .nav-link:hover:before {
        color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .navbar-light .navbar-nav .nav-link:before {
        background: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .menu-wrap .navbar-nav li .active,
    .menu-wrap .navbar-nav li .active::before {
        color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .menu-wrap a:hover, .menu-wrap a:focus {
        color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .icon-list a::before {
        background: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .item-boxes .icon {
        border-color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .item-boxes .icon i {
        color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
    .back-to-top i {
        background-color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ); ?>;
    }
</style>
