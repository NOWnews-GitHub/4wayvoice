<?php
/**
 * Whitelabel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WF_SN_WL_OPTIONS_KEY', 'wf_sn_wl' );

class Wf_Sn_Wl {

	static $options;

	static function init() {
		self::$options = self::get_options();
		// does the user have enough privilages to use the plugin?
		if ( is_admin() ) {
			// add tab to Security Ninja tabs
			add_filter( 'sn_tabs', array( __CLASS__, 'sn_tabs' ) );

			// settings registration
			add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
			// check and set default settings
			self::default_settings( false );

		} // if admin

		if ( self::is_active() ) {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'updates_core_page' ) );
			add_action( 'admin_head', array( __CLASS__, 'do_action_admin_head_add_extra_css' ) );
			add_filter( 'all_plugins', array( __CLASS__, 'do_filter_all_plugins' ), 9999 );
			add_action( 'pre_current_active_plugins', array( __CLASS__, 'action_pre_current_active_plugins' ) );
		}

	} // init

	/**
	 * Removes plugin from list.
	 *
	 * @param [type] $plugins [description]
	 *
	 * @return [type]          [description]
	 */
	static function do_filter_all_plugins( $plugins ) {

		$key = 'security-ninja-premium/security-ninja.php';

		if ( isset( $plugins[ $key ] ) ) {
			unset( $plugins[ $key ] );
		}
		return $plugins;

	}





	/**
	 * Outputs simple CSS to hide the plugin icon.
	 *
	 * @return [type] [description]
	 */
	static function do_action_admin_head_add_extra_css() {

		if ( ! self::is_active() ) {
			return;
		}

		$styleout = '<style>
	/*
	img[src*="images/sn-logo.svg"]{
		opacity:0;
	}
	*/

		#security-ninja-update {
	display:none;
}

tr[data-slug="security-ninja"] .open-plugin-details-modal {
	display:none;
}
</style>';

		echo $styleout;

	}


	/**
	 * Update strings on the update-core.php page.
	 *
	 * @since  1.6.14
	 * @return void
	 */
	static function updates_core_page() {

		global $pagenow;

		if ( 'update-core.php' == $pagenow ) {

			$default_name = 'Security Ninja';
			$newtitle     = self::get_new_name();

			$newicon = self::get_new_icon_url();

			if ( false !== $newtitle ) {
				wp_add_inline_script(
					'updates',
					"
				var _secnin_default_name = '$default_name';
				var _secnin_branded_name = '" . esc_js( $newtitle ) . "';
				var _secnin_icon_url = '" . esc_js( $newicon ) . "';

				// Replace image
				document.querySelectorAll( '#update-plugins-table .plugin-title img[src*=\'security-ninja\']' )
				.forEach(function(plugin) {

					jQuery(plugin).attr('src',_secnin_icon_url).attr('width',18);
					});


					// Remove 'View details' link

					jQuery('a[href*=\'&plugin=security-ninja&\']').remove();

					// Renames plugin title
					document.querySelectorAll( '#update-plugins-table .plugin-title strong' )
					.forEach(function(plugin) {
						if( _secnin_default_name === plugin.innerText ) {
							plugin.innerText = _secnin_branded_name;

						}
						});
						"
				);
			}
		}
	}



	/**
	 * Hides the plugin from list of active plugins
	 *
	 * @return [type] [description]
	 */
	static function action_pre_current_active_plugins() {
		global $wp_list_table;
		$hidearr   = array( 'security-ninja/security-ninja.php' );
		$myplugins = $wp_list_table->items;
		foreach ( $myplugins as $key => $val ) {
			if ( in_array( $key, $hidearr ) ) {

				$new_name        = self::get_new_name();
				$new_url         = self::get_new_url();
				$new_author_name = self::get_new_author_name();
				$new_desc        = self::get_new_desc();
				$wl_newiconurl   = self::get_new_icon_url();

				if ( $wl_newiconurl ) {
					$wp_list_table->items[ $key ]['icons']['default'] = $wl_newiconurl;
				}

				if ( $new_name ) {
					$wp_list_table->items[ $key ]['Name'] = $new_name;
				}
				if ( $new_name ) {
					$wp_list_table->items[ $key ]['Title'] = $new_name;
				}

				if ( $new_author_name ) {
					$wp_list_table->items[ $key ]['Author'] = $new_author_name;
				}
				if ( $new_author_name ) {
					$wp_list_table->items[ $key ]['AuthorName'] = $new_author_name;
				}

				if ( $new_url ) {
					$wp_list_table->items[ $key ]['PluginURI'] = $new_url;
				}
				if ( $new_url ) {
					$wp_list_table->items[ $key ]['AuthorURI'] = $new_url;
				}

				if ( $new_desc ) {
					$wp_list_table->items[ $key ]['Description'] = $new_desc;
				}
			}
		}
	}


	static function get_options() {
		$defaults = array(
			'wl_active'     => '0',
			'wl_newname'    => 'Security Ninja',
			'wl_newdesc'    => '',
			'wl_newauthor'  => '',
			'wl_newurl'     => 'https://wpsecurityninja.com/',
			'wl_newiconurl' => '',
		);
		$options  = get_option( WF_SN_WL_OPTIONS_KEY, array() );
		$options  = array_merge( $defaults, $options );
		return $options;
	} // get_options



	// add new tab
	static function sn_tabs( $tabs ) {

		$whitelabel_tab = array(
			'id'       => 'sn_whitelabel',
			'class'    => '',
			'label'    => 'Whitelabel',
			'callback' => array( __CLASS__, 'do_page' ),
		);

		// Check if active and then remove the tab
		if ( self::is_active() ) {

			$licensinfo = secnin_fs()->_get_license();

			$quota = intval( $licensinfo->quota );

			if ( ( isset( $quota ) ) and ( ( $quota == 0 ) or ( $quota > 24 ) ) ) {
				$whitelabel_tab = array(
					'id'       => 'sn_whitelabel',
					'class'    => 'hide',
					'label'    => 'Whitelabel',
					'callback' => array( __CLASS__, 'do_page' ),
				);
			}
		}

		$done = 0;
		for ( $i = 0; $i < sizeof( $tabs ); $i++ ) {
			if ( $tabs[ $i ]['id'] === 'sn_whitelabel' ) {
				$tabs[ $i ] = $whitelabel_tab;
				$done       = 1;
				break;
			}
		} // for
		if ( ! $done ) {
			$tabs[] = $whitelabel_tab;
		}
		return $tabs;
	} // sn_tabs



	/**
	 * Display admin page
	 *
	 * @return [type] [description]
	 */
	static function do_page() {
		global $wpdb, $secnin_fs;

		$licensinfo = $secnin_fs->_get_license();
		$quota      = $licensinfo->quota;
		if ( isset( $licensinfo ) ) {

			$message = 'You do not have a big enough license to use Whitelabel - this requires a license for 25+ sites.';

			if ( $quota === null ) {
				  $message = 'You have an unlimited sites license';
			} elseif ( $quota > 24 ) {
				$message = 'You have a ' . $quota . '-site license';
			}
			echo '<p>' . $message . '</p>';
		}

		?>
	<div class="submit-test-container">
		<?php
		echo '<form action="options.php" method="post">';
		settings_fields( 'wf_sn_wl' );

		echo '<h3 class="ss_header">Settings</h3>';

		echo '<table class="form-table"><tbody>';

		echo '<tr valign="top">
		<th scope="row"><label for="wf_sn_wl_active">Enable whitelabel</label></th>
		<td class="sn-cf-options">';

		self::create_toggle_switch(
			WF_SN_WL_OPTIONS_KEY . '_wl_active',
			array(
				'saved_value' => self::$options['wl_active'],
				'option_key'  => WF_SN_WL_OPTIONS_KEY . '[wl_active]',
			)
		);

		echo '<p class="description">' . 'Allows you to whitelabel the plugin. It will also hide notifications made by Freemius.com, the system that handles payments and licensing.' . '</p>';

		echo '<p>' . 'Warning - If you enable whitelabeling this tab will disappear. To turn off or edit the name you have to manually enter change the URL in your browser and update the page.' . '</p>';
		?>
		<pre><?php echo admin_url( 'admin.php?page=wf-sn' ); ?><strong>#sn_whitelabel</strong></pre>
	</td>
</tr>
<tr>
	<th scope="row"><label for="input_id"><?php echo 'Plugin Name'; ?></label></th>
	<td><input name="<?php echo WF_SN_WL_OPTIONS_KEY; ?>[wl_newname]" type="text" value="<?php echo self::$options['wl_newname']; ?>" class="regular-text" placeholder="Security Ninja"></td>
</tr>

<tr>
	<th scope="row"><label for="input_id"><?php echo 'Plugin Description'; ?></label></th>
	<td><input name="<?php echo WF_SN_WL_OPTIONS_KEY; ?>[wl_newdesc]" type="text" value="<?php echo self::$options['wl_newdesc']; ?>" class="regular-text" placeholder="Since 2011 Security Ninja has helped thousands of site owners like you to feel safe!"></td>
</tr>

<tr>
	<th scope="row"><label for="input_id"><?php echo 'Author Name'; ?></label></th>
	<td><input name="<?php echo WF_SN_WL_OPTIONS_KEY; ?>[wl_newauthor]" type="text" value="<?php echo self::$options['wl_newauthor']; ?>" class="regular-text" placeholder="WP Security Ninja"></td>
</tr>

<tr>
	<th scope="row"><label for="input_id"><?php echo 'Author URL'; ?></label></th>
	<td><input name="<?php echo WF_SN_WL_OPTIONS_KEY; ?>[wl_newurl]" type="text" value="<?php echo self::$options['wl_newurl']; ?>" class="regular-text" placeholder="https://wpsecurityninja.com/"></td>
	<p class="description"><?php echo 'Enter the new URL for both the author and the plugin.'; ?></p>
</tr>

<tr>
	<th scope="row"><label for="input_id"><?php echo 'Plugin Icon URL'; ?></label></th>
	<td>
		<input name="<?php echo esc_attr( WF_SN_WL_OPTIONS_KEY ); ?>[wl_newiconurl]" type="text" value="<?php echo esc_attr( self::$options['wl_newiconurl'] ); ?>" class="regular-text" placeholder="">
		<p class="description"><?php echo 'The little square image used to represent the plugin, eg on the update-core page.'; ?></p>
	</td>
</tr>

<tr>
	<th scope="row"><label for="input_id"><?php echo 'Plugin Menu Icon URL'; ?></label></th>
	<td>
		<input name="<?php echo esc_attr( WF_SN_WL_OPTIONS_KEY ); ?>[wl_newmenuiconurl]" type="text" value="<?php echo esc_attr( self::$options['wl_newmenuiconurl'] ); ?>" class="regular-text" placeholder="">
		<p class="description"><?php echo 'This is the little menu icon in the sidebar'; ?></p>
	</td>
</tr>

<tr>
	<td colspan="2">
		<p class="submit"><input type="submit" value="Save Changes" class="input-button button-primary" name="Submit" />

		</td>
	</tr>
</tbody>
</table>

</form>
</div>
		<?php

	} // do_page

	// set default options
	static function default_settings( $force = false ) {
		$defaults = array(
			'wl_active'         => '0',
			'wl_newname'        => '',
			'wl_newdesc'        => '',
			'wl_newauthor'      => '',
			'wl_newurl'         => '',
			'wl_newiconurl'     => '',
			'wl_newmenuiconurl' => '',

		);

		$options = get_option( WF_SN_WL_OPTIONS_KEY );

		if ( $force || ! $options || ! $options['wl_active'] ) {
			update_option( WF_SN_WL_OPTIONS_KEY, $defaults );
		}
	} // default_settings


	/**
	 * Clean-up when deactivated
	 *
	 * @return void
	 */
	static function deactivate() {
		delete_option( WF_SN_WL_OPTIONS_KEY );

	} // deactivate

	/**
	 * Creates a toggle switch for admin page
	 *
	 * @param [type]  $name    [description]
	 * @param array   $options [description]
	 * @param boolean $output  [description]
	 *
	 * @return string Generated output in HTML - Can also be echoed, see $output
	 */
	static function create_toggle_switch( $name, $options = array(), $output = true ) {
		$default_options = array(
			'value'       => '1',
			'saved_value' => '',
			'option_key'  => $name,
		);
		$options         = array_merge( $default_options, $options );

		$out  = "\n";
		$out .= '<div class="toggle-wrapper">';
		$out .= '<input type="checkbox" id="' . $name . '" ' . self::checked( $options['value'], $options['saved_value'] ) . ' type="checkbox" value="' . $options['value'] . '" name="' . $options['option_key'] . '">';
		$out .= '<label for="' . $name . '" class="toggle"><span class="toggle_handler"></span></label>';
		$out .= '</div>';

		if ( $output ) {
			echo $out;
		} else {
			return $out;
		}
	} // create_toggle_switch


	static function checked( $value, $current, $echo = false ) {
		$out = '';

		if ( ! is_array( $current ) ) {
			$current = (array) $current;
		}

		if ( in_array( $value, $current ) ) {
			$out = ' checked="checked" ';
		}

		if ( $echo ) {
			echo $out;
		} else {
			return $out;
		}
	} // checked




	/**
	 * Returns the whitelabel name of plugin, if any - else returns "Security";
	 *
	 * @return string The whitelabeled name of the plugin
	 */
	static function get_new_url() {
		$newurl = '';
		if ( ( isset( self::$options['wl_newurl'] ) )
			&& ( '' !== self::$options['wl_newurl'] )
		) {
			$newurl = self::$options['wl_newurl'];
		}
		return $newurl;
	}

	static function get_new_icon_url() {
		$newurl = '';
		if ( ( isset( self::$options['wl_newiconurl'] ) )
			&& ( '' !== self::$options['wl_newiconurl'] )
		) {
			$newurl = self::$options['wl_newiconurl'];
		}
		return $newurl;
	}

	/**
	 * Returns new menu icon URL if set
	 *
	 * @return string full URL or encoded SVG
	 */
	static function get_new_menu_icon_url() {
		$newmenuiconurl = wf_sn::get_icon_svg();
		if ( ( isset( self::$options['wl_newmenuiconurl'] ) )
			&& ( '' !== self::$options['wl_newmenuiconurl'] )
		) {
			$newmenuiconurl = self::$options['wl_newmenuiconurl'];
		}
		return $newmenuiconurl;
	}


	/**
	 * Returns the whitelabel name of plugin, if any - else returns "Security";
	 *
	 * @return string The whitelabeled name of the plugin
	 */
	static function get_new_name() {
		$newname = 'Security';
		if ( ( isset( self::$options['wl_newname'] ) )
			&& ( '' !== self::$options['wl_newname'] )
		) {
			$newname = self::$options['wl_newname'];
		}
		return $newname;
	}


	/**
	 * Return new description or false
	 * @return string Description entered in settings
	 */
	static function get_new_desc() {

		if ( ( isset( self::$options['wl_newdesc'] ) )
			&& ( '' !== self::$options['wl_newdesc'] )
		) {
			$newdesc = self::$options['wl_newdesc'];
			return $newdesc;
		}
		return false;
	}


	static function get_new_author_name() {
		$newauthorname = 'WP Security Ninja';
		if ( ( isset( self::$options['wl_newauthor'] ) )
			&& ( '' !== self::$options['wl_newauthor'] )
		) {
			$newauthorname = self::$options['wl_newauthor'];
		}
		return $newauthorname;
	}


	/**
	 * Is the whitelabel feature enabled
	 *
	 * @return boolean Return true if enabled
	 */
	static function is_active() {
		return (bool) self::$options['wl_active'];
	} // is_active


	static function admin_init() {
		register_setting( WF_SN_WL_OPTIONS_KEY, 'wf_sn_wl', array( __CLASS__, 'SanitizeSettings' ) );

		if ( self::is_active() ) {
			// Filter if whitelabel is not turned on
			global $submenu;
			// Filter out submenu items we do not want shown.
			if ( isset( $submenu['wf-sn'] ) ) {
				$newwfsn = array();
				foreach ( $submenu['wf-sn'] as $sfs ) {
					if ( ! in_array( $sfs[2], array( 'wf-sn-affiliation', 'wf-sn-account', 'wf-sn-contact', 'wf-sn-pricing' ) ) ) {
						$newwfsn[] = $sfs;
					}
				}
				$submenu['wf-sn'] = $newwfsn;
			}
		}
	} // admin_init



	/**
	 * Sanitize settings on save
	 *
	 * @param array $values values to sanitize
	 *
	 * @return array         Sanitized values
	 */
	static function SanitizeSettings( $values ) {
		$old_options = get_option( WF_SN_WL_OPTIONS_KEY );
		if ( ! is_array( $values ) ) {
			$values = array();
		}
		$old_options['wl_active']         = 0;
		$old_options['wl_newname']        = '';
		$old_options['wl_newdesc']        = '';
		$old_options['wl_newauthor']      = '';
		$old_options['wl_newurl']         = '';
		$old_options['wl_newiconurl']     = '';
		$old_options['wl_newmenuiconurl'] = '';
		return array_merge( $old_options, $values );
	} // SanitizeSettings




} // wf_sn_wl class
// hook everything up
add_action( 'plugins_loaded', array( 'wf_sn_wl', 'init' ) );

// when deativated clean up
register_deactivation_hook( WF_SN_BASE_FILE, array( 'wf_sn_wl', 'deactivate' ) );
