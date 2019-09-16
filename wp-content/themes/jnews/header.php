<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 9]>    <html class="no-js lt-ie10" <?php language_attributes(); ?>> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" <?php language_attributes(); ?>> <!--<![endif]-->
<head>
    <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
    <meta name='viewport' content='width=device-width, initial-scale=1, user-scalable=yes' />
    <link rel="profile" href="http://gmpg.org/xfn/11" />
    <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
    <?php wp_head(); ?>
<!-- Start Alexa Certify Javascript -->
<script type="text/javascript">
_atrk_opts = { atrk_acct:"3yibi1aoZM00M2", domain:"nownews.com",dynamic: true};
(function() { var as = document.createElement('script'); as.type = 'text/javascript'; as.async = true; as.src = "https://certify-js.alexametrics.com/atrk.js"; var s = document.getElementsByTagName('script')[0];s.parentNode.insertBefore(as, s); })();
</script>
<noscript><img src="https://certify.alexametrics.com/atrk.gif?account=3yibi1aoZM00M2" style="display:none" height="1" width="1" alt="" /></noscript>
<!-- End Alexa Certify Javascript -->
<meta name='dailymotion-domain-verification' content='dm1pp8kef3t48drcc' />
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-5V76KWN');</script>
<!-- End Google Tag Manager -->
</head>
<body <?php body_class(); ?>>
    <!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5V76KWN"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->  
    <?php do_action('jnews_after_body'); ?>

    <?php get_template_part('fragment/side-feed'); ?>

    <div class="jeg_ad jeg_ad_top jnews_header_top_ads">
        <?php do_action('jnews_header_top_ads'); ?>
    </div>

    <!-- The Main Wrapper
    ============================================= -->
    <div class="jeg_viewport">

        <?php jnews_background_ads(); ?>

        <div class="jeg_header_wrapper">
            <?php get_template_part('fragment/header/desktop-builder'); ?>
        </div>

        <div class="jeg_header_sticky">
            <?php get_template_part('fragment/header/desktop-sticky-wrapper'); ?>
        </div>

        <div class="jeg_navbar_mobile_wrapper">
            <?php get_template_part('fragment/header/mobile-builder'); ?>
        </div>
