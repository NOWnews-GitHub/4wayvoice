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
			<h3><span class="dashicons dashicons-warning"></span> Never miss an important update</h3>
			<p>Opt-in to our security and feature updates notifications, and non-sensitive diagnostic tracking.</p>
			<p><a href="<?php echo esc_url( secnin_fs()->get_reconnect_url() ); ?>" class="button button-primary button-hero">Click here to opt in.</a></p>
		</div>
		<?php
	}

	if ( function_exists( 'secnin_fs' ) ) {
		$display_promotion = true;

		if ( secnin_fs()->is__premium_only() ) {
			if ( secnin_fs()->can_use_premium_code() ) {
				$display_promotion = false;
			}
		}

		if ( $display_promotion ) {
			?>
			<div class="snupgradebox sidebarsection feature">
				<h3>Get Security Ninja Pro</h3>
				<ul class="checkmarks">
					<li><strong>Firewall Protection</strong> - Protect your website from suspicious visitors.</li>
					<li><strong>Auto Fixer</strong> - Fix many security issues with one click.</li>
					<li><strong>Login Protection</strong> - Stop repeated failed logins.</li>
					<li><strong>Country Blocking</strong> - Block entire countries.</li>
					<li><strong>Core Scanner</strong> - Detect infected WordPress core files.</li>
					<li><strong>Plugin Validation</strong> - Check plugins have not been modified.</li>
					<li><strong>Malware Scanner</strong> - Find and remove suspicious files.</li>
					<li><strong>Events Logger</strong> - Audit log - Know who did what on your website</li>
					<li><strong>Premium Support</strong> - From the people who developed the plugin</li>
					<li><strong>Support the developers :-)</strong></li>
				</ul>
				<p>Try for free for 14 days!</p>
				<a href="<?php echo esc_url( secnin_fs()->get_trial_url() ); ?>" class="button button-primary trial-button"><?php echo 'Start free trial'; ?></a>

				<p><center><em>$7.99 per month. $39.99/year, or $119 lifetime single payment.</em></center></p>

				<div class="wrap-collabsible">
					<input id="collapsible-payment-details" class="toggle" type="checkbox">
					<label for="collapsible-payment-details" class="lbl-toggle">Click to see details</label>
					<div class="collapsible-content">
						<div class="content-inner">

							<ul class="salenotices">
								<li>We ask for your payment information to reduce fraud and provide a seamless subscription experience.</li>
								<li>CANCEL ANYTIME before the trial ends to avoid being charged.</li>
								<li>We will send you an email reminder BEFORE your trial ends.</li>
								<li>We accept Visa, Mastercard, American Express and PayPal.</li>
								<li>Upgrade, downgrade or cancel any time.</li>
								<li>Bulk discounts for more websites.</li>
							</ul>
							<p><a href="<?php echo esc_url( wf_sn::generate_sn_web_link( 'sidebar_link', '/' ) ); ?>" target="_blank" class="button button-primary" rel="noopener">Read more about the Pro version</a></p>

						</div>
					</div>
				</div>








			</div><!-- .snupgradebox -->
			<?php
		}
	}



	// Default to false so we don't show unless at least one element should be shown.


	if ( secnin_fs()->is__premium_only() ) {
		if ( secnin_fs()->can_use_premium_code() ) {

			$blocked_count = get_option( WF_SN_CF_BLOCKED_COUNT );
			if ( $blocked_count ) {
				?>
				<div class="sidebarsection feature">
					<h3><span class="dashicons dashicons-warning"></span> <?php echo 'Firewall'; ?></h3>
					<div class="counters"><span><?php echo esc_html( number_format_i18n( $blocked_count ) ); ?><br><i>Blocked visits</i></span></div>
				</div>
				<?php
			}
			// end code only for pro users
		}
	}

	?>
	<div class="sidebarsection feature">
		<h3><span class="dashicons dashicons-info"></span> Plugin help</h3>
		<ul class="linklist">
			<?php
			global $secnin_fs;
			if ( ( $secnin_fs->is_registered() ) && ( ! $secnin_fs->is_pending_activation() ) ) {
				?>
				<li><a href="#" class="openhelpscout">Click to open help beacon <img src="<?php echo esc_url( WF_SN_PLUGIN_URL . 'images/helpscout.png' ); ?>" height="16" width="16" alt="Help Scout Beacon"></a></li>
				<?php
			}
			?>
			<li><a href="<?php echo esc_url( wf_sn::generate_sn_web_link( 'sidebar_link', '/docs/' ) ); ?>" target="_blank" rel="noopener">Search Documentation</a></li>

			<li><a href="<?php echo esc_url( wf_sn::generate_sn_web_link( 'sidebar_link', '/help/' ) ); ?>" target="_blank" rel="noopener">Need human help? Click here</a></li>

			<li><a href="<?php echo esc_url( wf_sn::generate_sn_web_link( 'sidebar_link', '/security-tests/' ) ); ?>" target="_blank" rel="noopener">About the tests</a></li>

			<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=security-ninja-welcome' ) ); ?>" rel="noopener">Open welcome page</a></li>

		</ul>

		<h3><span class="dashicons dashicons-welcome-learn-more"></span> Learn more</h3>
		<ul class="linklist">
			<li><a href="<?php echo esc_url( wf_sn::generate_sn_web_link( 'sidebar_link', '/why-is-insignificant-small-site-attacked-by-hackers/' ) ); ?>" target="_blank" rel="noopener">Even small sites are attacked by hackers</a></li>

			<li><a href="<?php echo esc_url( wf_sn::generate_sn_web_link( 'sidebar_link', '/wordpress-beginner-mistakes/' ) ); ?>" target="_blank" rel="noopener">New to WordPress? avoid these beginner mistakes</a></li>

			<li><a href="<?php echo esc_url( wf_sn::generate_sn_web_link( 'sidebar_link', '/your-guide-to-wordpress-password-and-username-security/' ) ); ?>" target="_blank" rel="noopener">Guide to Password and Username Security</a></li>

			<li><a href="<?php echo esc_url( wf_sn::generate_sn_web_link( 'sidebar_link', '/signs-wordpress-site-is-hacked/' ) ); ?>" target="_blank" rel="noopener">Signs that your site has been hacked</a></li>
		</ul>
	</div>
</div><!-- #sidebar-container -->
