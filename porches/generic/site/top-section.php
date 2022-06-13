<?php

$lang = PORCH_DEFAULT_LANGUAGE;
if ( isset( $_GET["lang"] ) && !empty( $_GET["lang"] ) ){
    $lang = sanitize_text_field( wp_unslash( $_GET["lang"] ) );
    setcookie( 'dt-magic-link-lang', $lang, 0, '/' );
} elseif ( isset( $_COOKIE["dt-magic-link-lang"] ) && !empty( $_COOKIE["dt-magic-link-lang"] ) ){
    $lang = sanitize_text_field( wp_unslash( $_COOKIE["dt-magic-link-lang"] ) );
}
dt_ramadan_set_translation( $lang );

$porch_fields = DT_Prayer_Campaigns::instance()->porch_fields();
$campaign_fields = p4r_get_campaign();
$langs = dt_ramadan_list_languages();
?>

<!-- Nav -->
<div class="menu-wrap">
    <nav class="menu navbar">
        <div class="icon-list navbar-collapse">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="/"><?php esc_html_e( 'Home', 'pray4ramadan-porch' ); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( site_url() ) ?>#sign-up"><?php esc_html_e( 'Sign Up', 'pray4ramadan-porch' ); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( site_url() ) ?>/prayer/list"><?php esc_html_e( 'Prayer Fuel', 'pray4ramadan-porch' ); ?></a>
                </li>
            </ul>
        </div>
    </nav>
    <button class="close-button" id="close-button" aria-label="Menu Close"><i class="lnr lnr-cross"></i></button>
</div>
<!-- end Nav -->

<!-- HEADER -->
<header id="hero-area" data-stellar-background-ratio="0.5" class="stencil-background">
    <div class="fixed-top">
        <div class="container">
            <div class="logo-menu">
                <a href="<?php echo esc_url( $porch_fields['logo_link_url']['value'] ?: site_url() ) ?>" class="logo"><?php echo esc_html( $porch_fields['title']['value'] ) ?></a>
                <select class="dt-magic-link-language-selector">
                    <?php foreach ( $langs as $code => $language ) : ?>
                        <option value="<?php echo esc_html( $code ); ?>" <?php selected( $lang === $code ) ?>>
                            <?php echo esc_html( $language["flag"] ); ?> <?php echo esc_html( $language["native_name"] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="menu-button" id="open-button" aria-label="Menu Open"><i class="lnr lnr-menu"></i></button>
            </div>
        </div>
    </div>
    <div class="overlay"></div>
    <div class="container">
        <div class="row justify-content-md-center">
            <div class="col-md-12">
                <div class="contents content-height text-center">
                    <?php if ( isset( $porch_fields['logo_url']['value'] ) && ! empty( $porch_fields['logo_url']['value'] ) ) : ?>
                        <img class="logo-image" src="<?php echo esc_url( $porch_fields['logo_url']['value'] ) ?>" alt=""  />
                    <?php else : ?>
                        <h1 class="wow fadeInDown" style="font-size: 3em;" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( get_field_translation( $porch_fields["title"], $lang ) ) ?></h1>
                    <?php endif; ?>
                    <?php if ( !empty( $porch_fields["country_name"]["value"] ) ) : ?>
                        <h4><?php echo esc_html( sprintf( __( 'Strategic prayer for a disciple making movement in %s', 'pray4ramadan-porch' ), get_field_translation( $porch_fields["country_name"], $lang ) ) ); ?></h4>
                    <?php else : ?>
                        <h4><?php esc_html_e( 'Strategic prayer for a disciple making movement', 'pray4ramadan-porch' ); ?>
                    <?php endif; ?>
                    <?php
                    if ( time() <= strtotime( $campaign_fields['end_date']['formatted'] ?? '' ) ) :
                        ?>
                        <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s">
                            <a href="<?php echo esc_url( site_url() ) ?>#sign-up" class="btn btn-common btn-rm"><?php esc_html_e( 'Sign Up to Pray', 'pray4ramadan-porch' ); ?></a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- Header Section End -->
