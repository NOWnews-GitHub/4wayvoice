<?php

add_action('wp_enqueue_scripts', 'tie_theme_child_styles_scripts', 80);
function tie_theme_child_styles_scripts()
{

	/* THIS WILL ALLOW ADDING CUSTOM CSS TO THE style.css */
	wp_enqueue_style('tie-theme-child-css', get_stylesheet_directory_uri() . '/style.css', array(), '1.0.4');

	/* Load the RTL.css file of the parent theme */
	if (is_rtl()) {
		wp_enqueue_style('tie-theme-rtl-css', get_template_directory_uri() . '/rtl.css', '');
	}

	/* Uncomment this line if you want to add custom javascript */
	//wp_enqueue_script( 'jannah-child-js', get_stylesheet_directory_uri() .'/js/scripts.js', '', false, true );
}

//BEGIN App API Meta
register_rest_field('post', 'metadata', array(
	'get_callback' => function ($data) {
		return get_post_meta($data['id'], '', '');
	},
));
//END App API Meta

//add_action('wp_head', 'my_header_scripts');
function my_header_scripts()
{
?>
	<!-- Google Tag Manager -->
	<script>
		(function(w, d, s, l, i) {
			w[l] = w[l] || [];
			w[l].push({
				'gtm.start': new Date().getTime(),
				event: 'gtm.js'
			});
			var f = d.getElementsByTagName(s)[0],
				j = d.createElement(s),
				dl = l != 'dataLayer' ? '&l=' + l : '';
			j.async = true;
			j.src =
				'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
			f.parentNode.insertBefore(j, f);
		})(window, document, 'script', 'dataLayer', 'GTM-5V76KWN');
	</script>
	<!-- End Google Tag Manager -->
<?php
}


//add_action('TieLabs/before_theme', 'fw_after_body_scripts');
function fw_after_body_scripts()
{
?>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5V76KWN" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
<?php
}

if (function_exists('acf_add_options_page')) {

	acf_add_options_page(array(
		'page_title' 	=> '四方報 Settings',
		'menu_title'	=> '4 Way Settings',
		'menu_slug' 	=> '4way-general-settings',
		'capability'	=> 'edit_posts',
		'redirect'		=> false
	));

}

add_shortcode( 'fw_line', 'fw_line_banner' );
function fw_line_banner() {
    add_filter('acf/settings/current_language', '__return_false');
    
	$banner = get_field('line_banner_desktop', 'options');
	$line_link = get_field('line_link', 'options');
	if ($banner) {
		if (empty($line_link)) {
			echo '<div class="line-banner"><img src=' . $banner['url'] . '></div>';
		} else {
			echo '<div class="line-banner"><a href="'.$line_link.'" target="_blank"><img src=' . $banner['url'] . '></a></div>';
		}
	}
    remove_filter('acf/settings/current_language', '__return_false');

}

add_action('TieLabs/before_post_components', 'fw_single_post_line_banner');
function fw_single_post_line_banner() {
	do_shortcode('[fw_line]');
}