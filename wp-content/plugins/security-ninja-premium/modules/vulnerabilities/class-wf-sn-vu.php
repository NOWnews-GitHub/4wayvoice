<?php

if ( ! function_exists( 'add_action' ) ) {
	die( 'Please don\'t open this file directly!' );
}

define( 'WF_SN_VU_OPTIONS_KEY', 'wf_sn_vu_results' );
define( 'WF_SN_VU_VULNS', 'wf_sn_vu_vulns' );


class Wf_Sn_Vu {

	public static $vu_api_url = 'https://wpsecurityninja.sfo2.cdn.digitaloceanspaces.com/vulnerabilities.json';

	// init plugin
	public static function init() {
		add_filter( 'sn_tabs', array( __CLASS__, 'sn_tabs' ) );
		add_action( 'admin_init', array( 'PAnD', 'init' ) );
		add_action( 'admin_notices', array( __CLASS__, 'run_tests_warning' ) );
	}


	/**
	 * Tab filter
	 *
	 * @param  [type] $tabs [description]
	 * @return [type]       [description]
	 */
	public static function sn_tabs( $tabs ) {

		$vuln_tab = array(
			'id'       => 'sn_vuln',
			'class'    => '',
			'label'    => 'Vulnerabilities',
			'callback' => array( __CLASS__, 'render_vuln_page' ),
		);

		// Add number of vulns to the tab list
		$return_vuln_count = self::return_vuln_count();
		if ( $return_vuln_count ) {
			$vuln_tab['count'] = $return_vuln_count;
		}

		$done     = 0;
		$tabcount = count( $tabs );
		for ( $i = 0; $i < $tabcount; $i++ ) {
			if ( 'sn_vuln' === $tabs[ $i ]['id'] ) {
				$tabs[ $i ] = $vuln_tab;
				$done       = 1;
				break;
			}
		} // for

		if ( ! $done ) {
			$tabs[] = $vuln_tab;
		}

		return $tabs;
	} // sn_tabs





/**
 * Strips http:// or https://
 */
		public static function remove_http( $url = '' ) {
			if ( 'http://' === $url or 'https://' === $url ) {
				return $url;
			}
			$matches = substr( $url, 0, 7 );
			if ( 'http://' === $matches ) {
				$url = substr( $url, 7 );
			} else {
				$matches = substr( $url, 0, 8 );
				if ( 'https://' === $matches ) {
					$url = substr( $url, 8 );
				}
			}
			return $url;
		}



	public static function update_vuln_list() {
		$wf_sn_vu_vulns = get_option( WF_SN_VU_VULNS );

		// If we have collected before
		if ( isset( $wf_sn_vu_vulns->timestamp ) ) {
			$last_update  = $wf_sn_vu_vulns->timestamp;
			$current_time = time();
			$seconds_diff = $current_time - $last_update;
			if ( $seconds_diff < DAY_IN_SECONDS ) {
				return true;
			}
		}

		global $secnin_fs;

		$request_url = self::$vu_api_url;
		$request_url = add_query_arg( 'ver', wf_sn::$version, $request_url );

		$response = wp_remote_get( $request_url );
		if ( ! is_wp_error( $response ) ) {
			$body                     = wp_remote_retrieve_body( $response );
			$sn_vulns_data            = json_decode( $body );
			$sn_vulns_data->timestamp = time();

			update_option( WF_SN_VU_VULNS, $sn_vulns_data, 'no' );
			if ( secnin_fs()->is__premium_only() ) {
				if ( secnin_fs()->can_use_premium_code() ) {

					wf_sn_el_modules::log_event( 'security_ninja', 'vulnerabilities_update', 'Downloaded list of known vulnerabilities.', '' );
				}
			}
		} else {
			if ( secnin_fs()->is__premium_only() ) {
				if ( secnin_fs()->can_use_premium_code() ) {

					wf_sn_el_modules::log_event( 'security_ninja', 'vulnerabilities_update', 'Unable to download list of known vulnerabilities.' . $response->get_error_message(), '' );
				}
			}
		}
	}







	/**
	 *  Check if an array is a multidimensional array.
	 *
	 * @param  array $arr The array to check
	 * @return boolean       Whether the the array is a multidimensional array or not
	 */
	public static function is_multi_array( $x ) {
		if ( count( array_filter( $x, 'is_array' ) ) > 0 ) {
			return true;
		}
		return false;
	}
	/**
	 *  Convert an object to an array.
	 *
	 * @param  array $object The object to convert
	 * @return array            The converted array
	 */
	public static function object_to_array( $object ) {
		if ( ! is_object( $object ) && ! is_array( $object ) ) {
			return $object;
		}
		return array_map( array( __CLASS__, 'object_to_array' ), (array) $object );
	}
	/**
	 *  Check if a value exists in the array/object.
	 *
	 * @param  mixed   $needle   The value that you are searching for
	 * @param  mixed   $haystack The array/object to search
	 * @param  boolean $strict   Whether to use strict search or not
	 * @return boolean             Whether the value was found or not
	 */
	public static function search_for_value( $needle, $haystack, $strict = true ) {
		$haystack = self::object_to_array( $haystack );
		if ( is_array( $haystack ) ) {
			if ( self::is_multi_array( $haystack ) ) {   // Multidimensional array
				foreach ( $haystack as $subhaystack ) {
					if ( self::search_for_value( $needle, $subhaystack, $strict ) ) {
						return true;
					}
				}
			} elseif ( array_keys( $haystack ) !== range( 0, count( $haystack ) - 1 ) ) {    // Associative array
				foreach ( $haystack as $key => $val ) {
					if ( $needle === $val && ! $strict ) {
						return true;
					} elseif ( $needle === $val && $strict ) {
						return true;
					}
				}
				return false;
			} else {    // Normal array
				if ( $needle === $haystack && ! $strict ) {
					return true;
				} elseif ( $needle === $haystack && $strict ) {
					return true;
				}
			}
		}
		return false;
	}



	/**
	 * Return list of known vulnerabilities from the website, checking installed plugins and WordPress version against list from API.
	 *
	 * @return array list of vulnerabilities.
	 */
	public static function return_vulnerabilities() {
		global $wp_version;
		$vulns = get_option( WF_SN_VU_VULNS );

		if ( ! $vulns ) {
			self::update_vuln_list();
			$vulns = get_option( WF_SN_VU_VULNS );
		}

		$vuln_plugin_arr = json_decode( wp_json_encode( $vulns->plugins ), true );

		$installed_plugins = get_plugins();

			// Tests for plugin problems
		if ( $installed_plugins && $vuln_plugin_arr ) {

			$found_vulnerabilities = array();

			wf_sn::timerstart( 'installed_plugins' );

			foreach ( $installed_plugins as $key => $ap ) {
				$lookup_id = strtok( $key, '/' );

				$findplugin = array_search( $lookup_id, array_column( $vuln_plugin_arr, 'slug' ), true );

				if ( $findplugin ) {

					if ( ( isset( $vuln_plugin_arr[ $findplugin ]['versionEndExcluding'] ) ) && ( '' !== $vuln_plugin_arr[ $findplugin ]['versionEndExcluding'] ) ) {
						// check #1 - versionEndExcluding

						if ( version_compare( $ap['Version'], $vuln_plugin_arr[ $findplugin ]['versionEndExcluding'], '<' ) ) {

							$found_vulnerabilities['plugins'][ $lookup_id ] = array(
								'name'                => $ap['Name'],
								'desc'                => $vuln_plugin_arr[ $findplugin ]['description'],
								'installedVersion'    => $ap['Version'],
								'versionEndExcluding' => $vuln_plugin_arr[ $findplugin ]['versionEndExcluding'],
								'CVE_ID'              => $vuln_plugin_arr[ $findplugin ]['CVE_ID'],
								'refs'								=> $vuln_plugin_arr[ $findplugin ]['refs'],
							);

						}
					}

					// Checks via the versionImpact method
					if ( ( isset( $vuln_plugin_arr[ $findplugin ]['versionImpact'] ) ) && ( '' !== $vuln_plugin_arr[ $findplugin ]['versionImpact'] ) ) {

						if ( version_compare( $ap['Version'], $vuln_plugin_arr[ $findplugin ]['versionImpact'], '<=' ) ) {

							$found_vulnerabilities['plugins'][ $lookup_id ] = array(
								'name'             => $ap['Name'],
								'desc'             => $vuln_plugin_arr[ $findplugin ]['description'],
								'installedVersion' => $ap['Version'],
								'versionImpact'    => $vuln_plugin_arr[ $findplugin ]['versionImpact'],
								'CVE_ID'           => $vuln_plugin_arr[ $findplugin ]['CVE_ID'],
								'refs'								=> $vuln_plugin_arr[ $findplugin ]['refs'],
							);

							if ( isset( $vuln_plugin_arr[ $findplugin ]['recommendation'] ) ) {

								$found_vulnerabilities['plugins'][ $lookup_id ]['recommendation'] = $vuln_plugin_arr[ $findplugin ]['recommendation'];
							}
						}
					}
				}
			}
		}

		// Find WordPress vulnerabilities
		$wordpressarr = json_decode( wp_json_encode( $vulns->wordpress ), true );

		$lookup_id = 0;
		foreach ( $wordpressarr as $key => $wpvuln ) {
			if ( version_compare( $wp_version, $wpvuln['versionEndExcluding'], '<' ) ) {
				$found_vulnerabilities['wordpress'][ $lookup_id ] = array(
					'desc'                => $wpvuln['description'],
					'versionEndExcluding' => $wpvuln['versionEndExcluding'],
					'CVE_ID'              => $wpvuln['CVE_ID'],
				);

				if ( isset( $wpvuln['recommendation'] ) ) {
					$found_vulnerabilities['wordpress'][ $lookup_id ]['recommendation'] = $wpvuln['recommendation'];

				}

				$lookup_id++;
			}
		}

		if ( $found_vulnerabilities ) {
			return $found_vulnerabilities;
		} else {
			return false;
		}
	}



	/**
	 * Gets list of WordPress from official API and their security status
	 *
	 * @return object    List of status of each public WordPress version
	 */
	public static function get_wp_ver_status() {
		$wp_vers_status = get_transient( 'wp_vers_status' );
		if ( false === ( $wp_vers_status ) ) {
			$request_url = 'https://api.wordpress.org/core/stable-check/1.0/';
			$response    = wp_remote_get( $request_url );
			if ( ! is_wp_error( $response ) ) {
				$body           = wp_remote_retrieve_body( $response );
				$wp_vers_status = json_decode( $body );
				if ( secnin_fs()->is__premium_only() ) {
					if ( secnin_fs()->can_use_premium_code() ) {
						wf_sn_el_modules::log_event( 'security_ninja', 'vulnerabilities_wp_stable_check', 'Downloaded list of known WordPress versions and their status.', '' );
					}
				}
			}
			set_transient( 'wp_vers_status', $wp_vers_status, 12 * HOUR_IN_SECONDS );
		}
		return $wp_vers_status;
	}

	/**
	 * Returns number of known vulnerabilities across all types
	 * @return int Combined count
	 */
	public static function return_vuln_count() {
		$vulnerabilities = self::return_vulnerabilities();

		if ( ! $vulnerabilities ) {
			return false;
		}
		$total_vulnerabilities = 0;
		if ( isset( $vulnerabilities['plugins'] ) ) {
			$total_vulnerabilities = $total_vulnerabilities + count( $vulnerabilities['plugins'] );
		}

		if ( isset( $vulnerabilities['wordpress'] ) ) {
			$total_vulnerabilities = $total_vulnerabilities + count( $vulnerabilities['wordpress'] );
		}

		return $total_vulnerabilities;
	}

	/**
	 * Renders vulnerability tab
	 *
	 * @return void
	 */
	public static function render_vuln_page() {

		global $wp_version;
		self::update_vuln_list(); // @todo - move to cron job

		// Get the list of vulnerabilities
		$vulnerabilities = self::return_vulnerabilities();

		$vulns = get_option( WF_SN_VU_VULNS );

		$plugin_vulns_count = count( $vulns->plugins );
		$wp_vulns_count     = count( $vulns->wordpress );

		$total_vulnerabilities = $plugin_vulns_count + $wp_vulns_count;

		$vuln_plug_arr = json_decode( wp_json_encode( $vulns->plugins ), true );

		// Used for the output of WordPress version being used
		$wp_status = '';

		?>
		<div class="submit-test-container">
			<div class="testresults">
				<?php
				if ( $vulnerabilities ) {
					?>
					<h2><?php esc_html_e( 'Vulnerabilities found on your system!', 'security-ninja' ); ?></h2>

					<?php
					if ( isset( $vulnerabilities['wordpress'] ) ) {

						$get_wp_ver_status = self::get_wp_ver_status();

						if ( isset( $get_wp_ver_status->$wp_version ) ) {
							if ( 'insecure' === $get_wp_ver_status->$wp_version ) {
								$wp_status = 'This version of WordPress (' . $wp_version . ') is considered <strong>INSECURE</strong>. You should upgrade as soon possible.';
							}

							if ( 'outdated' === $get_wp_ver_status->$wp_version ) {
								$wp_status = 'This version of WordPress (' . $wp_version . ') is considered <strong>OUTDATED</strong>. You should upgrade as soon possible.';
							}
						}

						?>

						<div class="vuln vulnwordpress">
							<h2>You are running WordPress version <?php echo esc_html( $wp_version ); ?> and there are known vulnerabilities that have been fixed in later versions. You should upgrade WordPress as soon as possible.</h2>

							<?php
							if ( '' !== $wp_status ) {
								?>
								<div class="vulnrecommendation"><p>
									<?php
								echo $wp_status; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							</p></div>
							<?php
						}
						?>

						<p>Known vulnerabilities:</p>

						<?php
						foreach ( $vulnerabilities['wordpress'] as $key => $wpvuln ) {
							if ( isset( $wpvuln['versionEndExcluding'] ) ) {
								?>
								<h3><span class="dashicons dashicons-warning"></span> <?php echo esc_html( 'WordPress ' . $wpvuln['CVE_ID'] ); ?></h3>
								<div class="wrap-collabsible">
									<input id="collapsible-<?php echo esc_attr( $key ); ?>" class="toggle" type="checkbox">
									<label for="collapsible-<?php echo esc_attr( $key ); ?>" class="lbl-toggle">Details</label>
									<div class="collapsible-content">
										<div class="content-inner">
											<p class="vulndesc"><?php echo esc_html( $wpvuln['desc'] ); ?></p>
											<p class="vulnDetails">Fixed in WordPress version
												<?php
												echo esc_html( $wpvuln['versionEndExcluding'] );
												?>
											</p>
											<?php
											if ( ( isset( $wpvuln['CVE_ID'] ) ) && ( '' !== $wpvuln['CVE_ID'] ) ) {
												?>
												<p><span class="nvdlink">More details: <a href="<?php echo esc_url( 'https://nvd.nist.gov/vuln/detail/' . $wpvuln['CVE_ID'] ); ?>" target="_blank">Read more about <?php echo esc_html( $wpvuln['CVE_ID'] ); ?></a></span></p>
												<?php
											}
											?>
										</div>
									</div>
								</div>


								<?php
							}
						}

						?>
					</div><!-- .vuln vulnwordpress -->
					<?php
				}

					// display list of vulns in plugins
				if ( isset( $vulnerabilities['plugins'] ) ) {
					?>

					<p>You should upgrade to latest version or find a different plugin as soon as possible.</p>

					<?php
					foreach ( $vulnerabilities['plugins'] as $key => $found_vuln ) {
						?>
						<div class="vuln vulnplugin">
							<h3><span class="dashicons dashicons-warning"></span> Plugin: <?php echo esc_html( $found_vuln['name'] ); ?> <span class="ver">v. <?php echo esc_html( $found_vuln['installedVersion'] ); ?></span></h3>

							<?php
							if ( isset( $found_vuln['desc'] ) || isset( $found_vuln['refs'] ) ) {
								?>
								<div class="wrap-collabsible">
									<input id="collapsible-<?php echo esc_attr( $key ); ?>" class="toggle" type="checkbox">
									<label for="collapsible-<?php echo esc_attr( $key ); ?>" class="lbl-toggle">Details</label>
									<div class="collapsible-content">
										<div class="content-inner">
											<p class="vulndesc"><?php echo esc_html( $found_vuln['desc'] ); ?></p>
											<?php

											if ( ( isset( $found_vuln['refs'] ) ) && ( '' !== $found_vuln['refs'] ) ) {
												$refs = json_decode( $found_vuln['refs'] );

												if (is_array($refs)) {
													?>
													<h4>Read more:</h4>
													<ul>
														<?php

														if ( ( isset( $found_vuln['CVE_ID'] ) ) && ( '' !== $found_vuln['CVE_ID'] ) ) {
															?>
															<li><a href="<?php echo esc_url( 'https://nvd.nist.gov/vuln/detail/' . $found_vuln['CVE_ID'] ); ?>" target="_blank" class="exlink"><?php echo esc_attr( $found_vuln['CVE_ID'] ); ?></a></li>
															<?php
														}
														foreach ($refs as $ref) {
															?>
															<li><a href="<?php echo esc_url( $ref->url ); ?>" target="_blank" class="exlink"><?php echo esc_html( self::remove_http( $ref->name ) ); ?></a></li>
															<?php
														}
														?>
													</ul>
													<?php
												}
											}
											?>
										</div>
									</div>
								</div>
								<?php
							}
							if ( isset( $found_vuln['versionEndExcluding'] ) ) {
								$searchurl = admin_url( 'plugins.php?s=' . rawurlencode( $found_vuln['name'] ) . '&plugin_status=all' );

								?>
								<div class="vulnrecommendation"><p>Recommendation: <a href="<?php echo esc_url( $searchurl ); ?>" target="_blank">Update <?php echo esc_html( $found_vuln['name'] ); ?> to minimum version <?php echo esc_html( $found_vuln['versionEndExcluding'] ); ?></a></p></div>
								<?php
							} elseif ( ( isset( $found_vuln['recommendation'] ) ) && ( '' !== $found_vuln['recommendation'] ) ) {
								?>
								<div class="vulnrecommendation"><p>Recommendation: <strong><?php echo esc_html( $found_vuln['recommendation'] ); ?></strong></p></div>
								<?php
							}
							?>
						</div><!-- .vuln .vulnplugin -->
						<?php
					}
				}
			} else {
				?>
				<p>Great, no known vulnerabilities found on your system.</p>
				<?php
			}
			?>
		</div>

		<p>
			<?php
			printf(
					// translators: Shows how many vulnerabilities are known and when list was updated
				esc_html__( 'Vulnerability list contains %1$s known  vulnerabilities. Last updated %2$s (%3$s)', 'security-ninja' ),
				'<strong>' . esc_html( number_format_i18n( $total_vulnerabilities ) ) . '</strong>',
				esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $vulns->timestamp ) ),
				esc_html( human_time_diff( $vulns->timestamp, time() ) . ' ' . __( 'ago', 'security-ninja' ) )
			);
			?>
		</p>

		<?php
		echo '</div>';
	} // render_vuln_page




	/**
	 * Display warning if test were never run
	 *
	 * @return void
	 */
	public static function run_tests_warning() {
		$tests = get_option( WF_SN_VU_OPTIONS_KEY );

		if ( ( ! PAnD::is_admin_notice_active( 'dismiss-vulnerabilities-notice-1' ) ) || ( wf_sn::is_plugin_page() ) ) {
			return;
		}

		$found_plugin_vulnerabilities = self::return_vulnerabilities();

		if ( $found_plugin_vulnerabilities ) {
			$total = 0;
			if ( isset( $found_plugin_vulnerabilities['plugins'] ) ) {
				$total = $total + count( $found_plugin_vulnerabilities['plugins'] );
			}

			if ( isset( $found_plugin_vulnerabilities['wordpress'] ) ) {
				$total = $total + count( $found_plugin_vulnerabilities['wordpress'] );
			}
			?>
			<div data-dismissible="dismiss-vulnerabilities-notice-1" class="notice notice-error is-dismissible" id="sn_vulnerability_warning_dismiss">

				<p style="font-size:1.2em;"><span class="dashicons dashicons-warning"></span> <strong>
					<?php
					// translators: Shown if one or multiple vulnerabilities found
					echo esc_html( sprintf( _n( 'You have %s known vulnerability on your website!', 'You have %s known vulnerabilities on your website!', $total, 'security-ninja' ), number_format_i18n( $total ) ) );
					?>
				</strong></p>
				<p><?php printf( 'Visit the <a href="%s">Vulnerabilities tab</a> for more details.', esc_url( admin_url( 'admin.php?page=wf-sn#sn_vuln' ) ) ); ?></p>
				<small>Dismiss warning for 24 hours.</small>
			</div>
			<?php
		}
	}

	/**
	 * Routines that run on deactivation
	 *
	 * @return [type] [description]
	 */
	public static function deactivate() {
		delete_option( WF_SN_VU_OPTIONS_KEY );
		delete_option( WF_SN_VU_VULNS );
	}

}

// hook everything up
add_action( 'plugins_loaded', array( 'Wf_Sn_Vu', 'init' ) );

// when deativated clean up
register_deactivation_hook( WF_SN_BASE_FILE, array( 'Wf_Sn_Vu', 'deactivate' ) );
