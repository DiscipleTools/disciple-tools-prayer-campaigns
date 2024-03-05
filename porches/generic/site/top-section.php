<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$lang = dt_campaign_get_current_lang();

$campaign_fields = DT_Campaign_Landing_Settings::get_campaign();
$langs = DT_Campaign_Languages::get_enabled_languages( $campaign_fields['ID'] );
$campaign_url = DT_Campaign_Landing_Settings::get_landing_root_url();
$url_path = dt_get_url_path();

$sign_up_link = $campaign_url . '#sign-up';
?>
<style>
    :root {
        --cp-color: <?php echo esc_html( CAMPAIGN_LANDING_COLOR_SCHEME_HEX ) ?>;
        --cp-color-dark: color-mix(in srgb, var(--cp-color), #000 10%);
        --cp-color-light: color-mix(in srgb, var(--cp-color), #fff 70%);
    }
</style>

<!-- Nav -->
<div class="menu-wrap">
    <nav class="menu navbar">
        <div class="icon-list navbar-collapse">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( $campaign_url ) ?>"><?php esc_html_e( 'Home', 'disciple-tools-prayer-campaigns' ); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href='<?php echo esc_url( $sign_up_link ) ?>'><?php esc_html_e( 'Sign Up', 'disciple-tools-prayer-campaigns' ); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( $campaign_url . '/list' ) ?>"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'prayer_fuel_name' ) ) ?></a>
                </li>
                <?php
                $landing_post_type = CAMPAIGN_LANDING_POST_TYPE;
                if ( is_user_logged_in() && current_user_can( 'edit_' . $landing_post_type ) ) : ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo esc_url( $campaign_url . '/listEdit' ) ?>"><?php esc_html_e( 'Edit Prayer Fuel', 'disciple-tools-prayer-campaigns' ); ?></a>
                    </li>
                <?php endif; ?>
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
                <a href="<?php echo esc_url( !empty( $campaign_fields['logo_link_url'] ) ? $campaign_fields['logo_link_url'] : $campaign_url ) ?>" class="logo"><?php echo esc_html( $campaign_fields['name'] ?? 'Set Up a Campaign' ) ?></a>
                <div class="d-flex align-items-center">

                    <?php if ( count( $langs ) > 1 ): ?>

                    <select class="dt-magic-link-language-selector">

                        <?php foreach ( $langs as $code => $language ) : ?>

                            <option value="<?php echo esc_html( $code ); ?>" <?php selected( $lang === $code ) ?>>

                            <?php echo esc_html( $language['flag'] ); ?> <?php echo esc_html( $language['native_name'] ); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <?php endif; ?>

                    <button class="menu-button" id="open-button" aria-label="Menu Open"><i class="lnr lnr-menu"></i></button>
                </div>
            </div>
        </div>
    </div>
    <?php if ( !isset( $campaign_fields['enable_overlay_blur'] ) || $campaign_fields['enable_overlay_blur']['key'] === 'yes' ) : ?>
    <div class="overlay"></div>
    <?php endif; ?>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="contents content-height text-center">

                    <?php if ( !empty( $campaign_fields['logo_url'] ) ) : ?>

                        <img class="logo-image" src="<?php echo esc_url( $campaign_fields['logo_url'] ) ?>" alt=""  />

                    <?php else : ?>

                        <h1 class="wow fadeInDown" style="font-size: 3em;" data-wow-duration="1000ms" data-wow-delay="0.3s">
                            <?php echo esc_html( $campaign_fields['name'] ?? 'Set Up A Campaign' ) ?>
                        </h1>

                    <?php endif; ?>

                    <h4>
                        <?php display_translated_field( 'subtitle' ); ?>
                    </h4>

                    <?php
                    if ( !isset( $campaign_fields['end_date']['formatted'] ) || time() <= strtotime( $campaign_fields['end_date']['formatted'] ?? '' ) ) : ?>

                        <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s">
                            <a href="<?php echo esc_url( $sign_up_link ) ?>" class="btn btn-common btn-rm"><?php esc_html_e( 'Sign Up to Pray', 'disciple-tools-prayer-campaigns' ); ?></a>
                        </p>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</header>
<!-- Header Section End -->
