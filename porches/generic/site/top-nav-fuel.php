<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$lang = dt_campaign_get_current_lang();

$campaign_fields = DT_Campaign_Landing_Settings::get_campaign();
$langs = DT_Campaign_Languages::get_enabled_languages( $campaign_fields['ID'] );
$campaign_url = DT_Campaign_Landing_Settings::get_landing_root_url();
$url_path = dt_get_url_path();

$sign_up_link = $campaign_url . '#sign-up';
$prayer_fuel_link = $campaign_url . '/list';
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
                        <a class="nav-link" href="<?php echo esc_url( $campaign_url . '/listEdit' ) ?>"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'edit_fuel_title' ) ) ?></a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <button class="close-button" id="close-button" aria-label="Menu Close"><i class="lnr lnr-cross"></i></button>
</div>
<div class="fixed-top menu-bg ">
    <div class="container">
        <div class="logo-menu">
            <a href="<?php echo esc_url( !empty( $campaign_fields['logo_link_url'] ) ? $campaign_fields['logo_link_url'] : $campaign_url ) ?>" class="logo">
                <!-- home icon -->
                <div class="icon">
                    <i class="lnr lnr-home"></i>
                </div>
            </a>
            <div class="d-flex align-items-center">

                <?php if ( count( $langs ) > 1 ): ?>

                    <select class="dt-magic-link-language-selector">

                        <?php if ( !isset( $langs[ $lang ] ) ) : ?>
                            <option value="<?php echo esc_html( $lang ); ?>" selected><?php esc_html_e( 'Select Language', 'disciple-tools-prayer-campaigns' ); ?></option>
                        <?php endif; ?>

                        <?php foreach ( $langs as $code => $language ) : ?>

                            <?php if ( isset( $language['native_name'] ) ) : ?>
                                <option value="<?php echo esc_html( $code ); ?>" <?php selected( $lang === $code ) ?>>

                                    <?php echo esc_html( $language['flag'] ?? '' ); ?> <?php echo esc_html( $language['native_name'] ); ?>

                                </option>
                            <?php endif; ?>

                        <?php endforeach; ?>

                    </select>

                <?php endif; ?>
                <button class="menu-button" id="share-button"  style="display:none">
                    <img class="dt-icon dt-white-icon" style="vertical-align: unset"  src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/share.svg' ) ?>"/>
                </button>
                <button class="menu-button" id="open-button" aria-label="Menu Open">
                    <i class="lnr lnr-menu"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<!-- end Nav -->

<!-- HEADER -->
<header id="hero-area" data-stellar-background-ratio="0.5" class="stencil-background">

    <?php if ( !isset( $campaign_fields['enable_overlay_blur'] ) || $campaign_fields['enable_overlay_blur']['key'] === 'yes' ) : ?>
        <div class="overlay"></div>
    <?php endif; ?>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="contents content-height text-center" style="padding: 100px 0">

                    <?php if ( !empty( $campaign_fields['logo_url'] ) ) : ?>

                        <a href="<?php echo esc_url( !empty( $campaign_fields['logo_link_url'] ) ? $campaign_fields['logo_link_url'] : $campaign_url ) ?>" class="logo">
                            <img class="logo-image" src="<?php echo esc_url( $campaign_fields['logo_url'] ) ?>" alt=""  />
                        </a>

                    <?php else : ?>

                        <h1 class="wow fadeInDown" style="font-size: 3em;" data-wow-duration="1000ms" data-wow-delay="0.3s">
                            <?php echo esc_html__( 'Prayer Fuel', 'disciple-tools-prayer-campaigns' ); ?>
                        </h1>

                    <?php endif; ?>

                    <h4>
                        <?php echo esc_html( DT_Porch_Settings::get_field_translation( 'name' ) ) ?>
                    </h4>


                    <?php do_action( 'dt_campaigns_prayer_fuel_header' ); ?>


                </div>
            </div>
        </div>
    </div>
</header>