<?php

// this is an include only WP file
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( secnin_fs()->is__premium_only() ) {
	if ( secnin_fs()->can_use_premium_code() ) {
		if ( ( class_exists( 'wf_sn_wl' ) ) && ( wf_sn_wl::is_active() ) ) {
			return;
		}
	}
}

?>
<div class="secnin_content_cell" id="sidebar-container">
	<?php

	global $secnin_fs;
	if ( ( ! $secnin_fs->is_registered() ) && ( ! $secnin_fs->is_pending_activation() ) ) {
		?>
		<div class="sidebarsection feature">
			<h3><span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Never miss an important update', 'security-ninja' ); ?></h3>
			<p>
				<?php
				esc_html_e( 'Opt-in to our security and feature updates notifications, and non-sensitive diagnostic tracking.', 'security-ninja' );
				?>
			</p>
			<p><a href="<?php echo esc_url( secnin_fs()->get_reconnect_url() ); ?>" class="button button-primary button-hero">Click here to opt in.</a></p>
		</div>
		<?php
	}

	?>
	<div class="sidebarsection feature">
		<h3><span class="dashicons dashicons-welcome-learn-more"></span> <?php esc_html_e( 'Plugin help', 'security-ninja' ); ?></h3>
		<ul class="linklist">
			<?php
			global $secnin_fs;
			if ( ( $secnin_fs->is_registered() ) && ( ! $secnin_fs->is_pending_activation() ) ) {
				?>
				<li><a href="#" class="openhelpscout"><?php esc_html_e( 'Click to open help beacon', 'security-ninja' ); ?> <img src="<?php echo esc_url( WF_SN_PLUGIN_URL . 'images/helpscout.png' ); ?>" height="16" width="16" alt="Help Scout Beacon"></a></li>
				<?php
			}
			?>
			<li><a href="<?php echo esc_url( wf_sn::generate_sn_web_link( 'sidebar_link', '/docs/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Search Documentation', 'security-ninja' ); ?></a></li>

			<li><a href="<?php echo esc_url( wf_sn::generate_sn_web_link( 'sidebar_link', '/help/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Need human help? Click here', 'security-ninja' ); ?></a></li>

			<li><a href="<?php echo esc_url( wf_sn::generate_sn_web_link( 'sidebar_link', '/security-tests/' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'What security tests are made', 'security-ninja' ); ?></a></li>
		</ul>

		<h3><span class="dashicons dashicons-format-aside"></span> <?php esc_html_e( 'Learn more', 'security-ninja' ); ?></h3>
		<ul class="linklist">
			<li><a href="<?php echo esc_url( wf_sn::generate_sn_web_link( 'sidebar_link', '/why-is-insignificant-small-site-attacked-by-hackers/' ) ); ?>" target="_blank" rel="noopener">Even small sites are attacked by hackers</a></li>

			<li><a href="<?php echo esc_url( wf_sn::generate_sn_web_link( 'sidebar_link', '/wordpress-beginner-mistakes/' ) ); ?>" target="_blank" rel="noopener">New to WordPress? avoid these beginner mistakes</a></li>

			<li><a href="<?php echo esc_url( wf_sn::generate_sn_web_link( 'sidebar_link', '/your-guide-to-wordpress-password-and-username-security/' ) ); ?>" target="_blank" rel="noopener">Guide to Password and Username Security</a></li>

			<li><a href="<?php echo esc_url( wf_sn::generate_sn_web_link( 'sidebar_link', '/signs-wordpress-site-is-hacked/' ) ); ?>" target="_blank" rel="noopener">Signs that your site has been hacked</a></li>

		</ul>

	</div>

</div><!-- #sidebar-container -->
