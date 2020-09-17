<?php
/**
 * The template for displaying the header
 *
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<link rel="profile" href="http://gmpg.org/xfn/11" />
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
	<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-5V76KWN');</script>
<!-- End Google Tag Manager -->
	<!-- 開始Dable script / 有問題請洽 http://dable.io --> <script> (function(d,a,b,l,e,_) { d[b]=d[b]||function(){(d[b].q=d[b].q||[]).push(arguments)};e=a.createElement(l); e.async=1;e.charset='utf-8';e.src='//static.dable.io/dist/plugin.min.js'; _=a.getElementsByTagName(l)[0];_.parentNode.insertBefore(e,_); })(window,document,'dable','script'); dable('setService', '4wayvoice.nownews'); dable('sendLogOnce'); </script> <!-- Dable 結束script / 有問題請洽 http://dable.io -->
	<?php wp_head(); ?>
</head>

<body id="tie-body" <?php body_class(); ?>>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5V76KWN"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<?php wp_body_open(); ?>

<div class="background-overlay">

	<div id="tie-container" class="site tie-container">

		<?php do_action( 'TieLabs/before_wrapper' ); ?>

		<div id="tie-wrapper">

			<?php

				TIELABS_HELPER::get_template_part( 'templates/header/load' );

				do_action( 'TieLabs/before_main_content' );
