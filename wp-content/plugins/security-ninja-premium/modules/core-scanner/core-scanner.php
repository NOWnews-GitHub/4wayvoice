<?php

if ( ! function_exists( 'add_action' ) ) {
	die( 'Please don\'t open this file directly!' );
}


define( 'WF_SN_CS_OPTIONS_KEY', 'wf_sn_cs_results' );
define( 'WF_SN_CS_SALT', 'monkey' );


class wf_sn_cs {
	static $hash_storage = 'https://api.wordpress.org/core/checksums/1.0/';

	// init plugin
	static function init() {

		// does the user have enough privilages to use the plugin?
		if ( current_user_can( 'manage_options' ) ) {
			// add tab to Security Ninja tabs
			add_filter( 'sn_tabs', array( __CLASS__, 'sn_tabs' ) );

			// enqueue scripts
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

			// register ajax endpoints
			add_action( 'wp_ajax_sn_core_get_file_source', array( __CLASS__, 'get_file_source' ) );

			add_action( 'wp_ajax_sn_core_delete_file', array( __CLASS__, 'delete_file_dialog' ) );
			add_action( 'wp_ajax_sn_core_delete_file_do', array( __CLASS__, 'delete_file' ) );

			add_action( 'wp_ajax_sn_core_restore_file', array( __CLASS__, 'restore_file_dialog' ) );
			add_action( 'wp_ajax_sn_core_restore_file_do', array( __CLASS__, 'restore_file' ) );
			add_action( 'wp_ajax_sn_core_run_scan', array( __CLASS__, 'scan_files' ) );

			// warn if tests were never run
			add_action( 'admin_notices', array( __CLASS__, 'run_tests_warning' ) );

			// add custom text for GUI overlay
			add_action( 'sn_overlay_content', array( __CLASS__, 'overlay_content' ) );

						// register settings key
			add_action( 'admin_init', array( __CLASS__, 'do_action_admin_init' ) );

		} // if admin
	} // init



	/**
	 * Runs on admin_init action
	 * @return void
	 */
	static function do_action_admin_init() {
		// @todo lars - review this nonce?
		if ( isset( $_POST['_wpnonce'] ) ) {
			$nonce = $_POST['_wpnonce'];
		}

		// DELETE ALL UNKNOWN FILES
		if ( ( isset( $_POST['_wpnonce'] ) ) && ( wp_verify_nonce( $nonce, 'wf-cs-delete-all-unknown-nonce' ) ) ) {
			$results = get_option( WF_SN_CS_OPTIONS_KEY );
			if ( isset( $results['unknown_bad'] ) && is_array( $results['unknown_bad'] ) ) {
				$deletedFiles = 0;
				foreach ( $results['unknown_bad'] as $ub ) {
					$filepath = ABSPATH . $ub;
					unlink( $filepath );
					$deletedFiles++;
				}

				if ( $deletedFiles > 0 ) {
					wf_sn_el_modules::log_event( 'security_ninja', 'core_scanner_delete_file', sprintf( esc_html__( 'Deleted %d unknown files in Core WordPress folders', 'security-ninja' ), $deletedFiles ) );
					$newresults = self::scan_files( true );
					if ( $newresults ) {
						update_option( WF_SN_CS_OPTIONS_KEY, $newresults );
					}
				}
			}

			if ( ! isset( $_POST['_wp_http_referer'] ) ) {
				$_POST['_wp_http_referer'] = wp_login_url();
			}

			$url = sanitize_text_field(
				wp_unslash( $_POST['_wp_http_referer'] )
			);
			wp_safe_redirect( urldecode( $url ) );
			exit;
		}
	}




	// enqueue CSS and JS scripts on plugin's admin page
	static function enqueue_scripts() {
		if ( wf_sn::is_plugin_page() ) {
			$plugin_url = plugin_dir_url( __FILE__ );

			wp_enqueue_style( 'wp-jquery-ui-dialog' );
			wp_enqueue_script( 'jquery-ui-dialog' );

			wp_register_script( 'sn-core-js', $plugin_url . 'js/wf-sn-core-min.js', array( 'jquery' ), wf_sn::$version, true );
			$js_vars = array(
				'nonce' => wp_create_nonce( 'wf_sn_cs' ),
			);
			wp_localize_script( 'sn-core-js', 'wf_sn_cs', $js_vars );
			wp_enqueue_script( 'sn-core-js' );

			wp_enqueue_style( 'sn-core-css', $plugin_url . 'css/wf-sn-core.css', array(), wf_sn::$version );
			wp_enqueue_script( 'sn-core-snippet', $plugin_url . 'js/snippet.min.js', array(), '1.0', true );
			wp_enqueue_style( 'sn-core-snippet', $plugin_url . 'css/snippet.min.css', array(), '1.0' );
		} // if
	} // enqueue_scripts


	// add custom message to overlay
	static function overlay_content() {
		echo '<div id="sn-core-scanner" style="display: none;">';

		echo '<h3>' . __( 'Scanning your core files.', 'security-ninja' ) . '<br/>' . __( 'It will only take a few moments', 'security-ninja' ) . '...</h3>';
		echo '</div>';
	} // overlay_content


	// ajax for viewing file source
	static function get_file_source() {
		check_ajax_referer( 'wf_sn_cs' );
		$out = array();

		if ( md5( WF_SN_CS_SALT . stripslashes( @$_POST['filename'] ) ) != $_POST['hash'] ) {
			$out['err'] = 'Cheating are you?';
			die( wp_json_encode( $out ) );
		}

		$out['ext']    = pathinfo( @$_POST['filename'], PATHINFO_EXTENSION );
		$out['source'] = '';

		if ( is_readable( $_POST['filename'] ) ) {
			$content = file_get_contents( $_POST['filename'] );
			if ( $content !== false ) {
				$out['err']    = 0;
				$out['source'] = utf8_encode( $content );
			} else {
				$out['err'] = 'File is empty.';
			}
		} else {
			$out['err'] = 'File does not exist or is not readable.';
		}

		die( wp_json_encode( $out ) );
	} // get_file_source


	// add new tab
	static function sn_tabs( $tabs ) {
		$core_tab = array(
			'id'       => 'sn_core',
			'class'    => '',
			'label'    => 'Core Scanner',
			'callback' => array( __CLASS__, 'core_page' ),
		);
		$done     = 0;

		for ( $i = 0; $i < sizeof( $tabs ); $i++ ) {
			if ( $tabs[ $i ]['id'] == 'sn_core' ) {
				$tabs[ $i ] = $core_tab;
				$done       = 1;
				break;
			}
		} // for

		if ( ! $done ) {
			$tabs[] = $core_tab;
		}

		return $tabs;
	} // sn_tabs


	// * lars @todo - simililar function, taken from malware, perhaps put in core?
	// generate a list of files to scan in a folder
	static function scan_folder( $path, $extensions = null, $depth = 3, $relative_path = '' ) {
		if ( ! is_dir( $path ) ) {
			return false;
		}

		if ( $extensions ) {
			$extensions  = (array) $extensions;
			$_extensions = implode( '|', $extensions );
		} else {
			//$extensions = array('php', 'php3', 'inc' );
			$extensions  = array(); // empty array to find all types of files.
			$_extensions = implode( '|', $extensions );
		}

		$relative_path = trailingslashit( $relative_path );
		if ( '/' === $relative_path ) {
			$relative_path = '';
		}

		$results = scandir( $path );
		$files   = array();

		foreach ( $results as $result ) {

			if ( '.' === $result[0] ) {
				continue;
			}

			if ( is_dir( $path . '/' . $result ) ) {
				if ( ! $depth || 'CVS' === $result ) {
					continue;
				}
				$found = self::scan_folder( $path . '/' . $result, $extensions, $depth - 1, $relative_path . $result );
				$files = array_merge_recursive( $files, $found );

			} elseif ( ! $extensions || preg_match( '~\.(' . $_extensions . ')$~', $result ) ) {
				$files[ $relative_path . $result ] = $path . '/' . $result;
			}
		} // foreach result

		return $files;
	} // scan_folder




	/**
	 * Gets file hashes from wordpress.org API - stores in transient value
	 * @return array Checksum list from stored transient or API.9
	 */
	public static function get_file_hashes() {

		$ver    = get_bloginfo( 'version' );
		$locale = get_locale();

		$cs = get_core_checksums( $ver, isset( $locale ) ? $locale : 'en_US' );

		if ( empty( $cs['checksums'] ) ) {
			$details = array(
				'ver'    => $ver,
				'locale' => $locale,
			);
			wf_sn_el_modules::log_event( 'security_ninja', 'core_scanner_update_hashes', sprintf( esc_html__( 'Could not get checksums with this locale, trying default checksums.', 'security-ninja' ) . ' v. ' . $ver . ' ' . $locale, $details ) );
			$cs = get_core_checksums( $ver, 'en_US' );
		}

		if ( $cs ) {
			$cleaned     = array();
			$themes_url  = trailingslashit( content_url( 'themes' ) );
			$plugins_url = trailingslashit( content_url( 'plugins' ) );

			$themes_path  = str_replace( site_url(), '', $themes_url );
			$plugins_path = str_replace( site_url(), '', $plugins_url );

			// Remove left trailing slash
			$themes_path  = ltrim( $themes_path, '/' );
			$plugins_path = ltrim( $plugins_path, '/' );

			foreach ( $cs as $path => $hash ) {
				if (
					strpos( $path, $themes_path ) !== false
					|| strpos( $path, $plugins_path ) !== false
					|| strpos( $path, '/plugins/akismet/' ) !== false
					|| strpos( $path, '/languages/themes/' ) !== false ) {
				} else {
					$cleaned[ $path ] = $hash;
				}
			}
			$tmp = array(
				'version'   => $ver,
				'checksums' => $cleaned,
			);
			set_transient( 'wf_sn_hashes_' . $ver . '_' . $locale, $cleaned, MINUTE_IN_SECONDS * 5 ); // cached for 5 mins
			return $cleaned;
		}

		wf_sn_el_modules::log_event( 'security_ninja', 'core_scanner_update_hashes', sprintf( esc_html__( 'There was a problem getting information about the WordPress original files.', 'security-ninja' ) . ' ' . $ver . ' ' . $locale, $response ) );

		//	delete_transient('wf_sn_hashes_' . $ver.'_'.$locale);
		return false;
	} // get_file_hashes




	// ref: https://stackoverflow.com/questions/27816105/php-in-array-wildcard-match
	static function stripos_array( $haystack, $needles ) {
		foreach ( $needles as $needle ) {
			if ( ( $res = stripos( $haystack, $needle ) ) !== false ) {
				return $res;
			}
		}
		return false;
	}


	// do the actual scanning
	public static function scan_files( $return = false ) {
		// No nonce check, can be run via scheduled scanner also
		$results['missing_ok']  = array();
		$results['changed_ok']  = array();
		$results['missing_bad'] = array();
		$results['changed_bad'] = array();
		$results['unknown_bad'] = array();
		$results['ok']          = array();
		$results['last_run']    = current_time( 'timestamp' );
		$results['total']       = $results['run_time'] = 0;
		$start_time             = microtime( true );

		$i = 0;

		$ver = get_bloginfo( 'version' );

		// Files ok to be missing
		$missing_ok = array( 'index.php', 'readme.html', 'license.txt', 'wp-config-sample.php', 'wp-admin/install.php', 'wp-admin/upgrade.php', 'wp-config.php', 'plugins/hello.php', 'licens.html', '/languages/plugins/akismet-' );

		// Files ok to be modified
		$changed_ok = array( 'index.php', 'wp-config.php', 'wp-config-sample.php', 'readme.html', 'license.txt', 'wp-includes/version.php' );

		$filehashes = self::get_file_hashes();

		if ( $filehashes ) {

			// ** Checking for unknown files
			$files     = self::scan_folder( ABSPATH . WPINC, null, 9, WPINC );
			$all_files = $files;

			$files     = self::scan_folder( ABSPATH . 'wp-admin', null, 9, 'wp-admin' );
			$all_files = array_merge( $all_files, $files );

			foreach ( $all_files as $key => $af ) {
				if ( ! isset( $filehashes[ $key ] ) ) {
					$results['unknown_bad'][] = $key;
				}
			}

			// Checking if core has been modified
			$results['total'] = sizeof( $filehashes );

			foreach ( $filehashes as $file => $hash ) {
				clearstatcache();

				if ( file_exists( ABSPATH . $file ) ) {
					if ( $hash === md5_file( ABSPATH . $file ) ) {
						// $results['ok'][] = $file; // FYLDER FOR MEGET i databasen og kan ikke loade i scheduled scanner results
					} elseif ( in_array( $file, $changed_ok ) ) {
						$results['changed_ok'][] = $file;
					} else {
						$results['changed_bad'][] = $file;
					}
				} else {
					// if ( self::stripos_array( $file, $missing_ok ) ) {
					if ( in_array( $file, $missing_ok ) ) {
						$results['missing_ok'][] = $file;
					} else {
						$results['missing_bad'][] = $file;
					}
				}
			} // foreach file

			do_action( 'security_ninja_core_scanner_done_scanning', $results, microtime( true ) - $start_time );
			$results['run_time'] = microtime( true ) - $start_time;

			if ( $return ) {
				return $results;
			} else {
				update_option( WF_SN_CS_OPTIONS_KEY, $results );
				die( '1' );
			}
		} else {
			// no file definitions for this version of WP
			if ( $return ) {
				return null;
			} else {
				update_option( WF_SN_CS_OPTIONS_KEY, null );
				die( '0' );
			}
		}

	} // scan_files


	// display results
	public static function core_page() {

		$results = get_option( WF_SN_CS_OPTIONS_KEY );

		echo '<div class="submit-test-container">';
		echo '<h3>' . __( 'Scan core WordPress files and folders', 'security-ninja' ) . '</h3>';
		?>
		<p><?php _e( 'Use the Core Scanner module to check for modified files in WordPress itself and detect extra files that should not exist. You can restore any modified files and delete any files that should not be there.', 'security-ninja' ); ?></p>
		<?php

		echo '<input type="button" value="' . __( 'Scan core files', 'security-ninja' ) . '" id="sn-run-core-scan" class="button button-primary button-hero" />';

		global $secnin_fs;
		if ( ( $secnin_fs->is_registered() ) && ( ! $secnin_fs->is_pending_activation() ) && ( ! wf_sn_wl::is_active() ) ) {
			?>
			<p><a href="#" data-beacon-article="5cc4d91104286301e753cec8"><?php _e( 'Need help? Open inline help', 'security-ninja' ); ?></a></p>
			<?php
		}

		if ( isset( $results['last_run'] ) && $results['last_run'] ) {

			$show_completed = true;

			if ( ! empty( $results['changed_bad'] ) ) {
				$show_completed = false;
			}
			if ( ! empty( $results['missing_bad'] ) ) {
				$show_completed = false;
			}
			if ( ! empty( $results['changed_ok'] ) ) {
				$show_completed = false;
			}
			if ( ! empty( $results['unknown_bad'] ) ) {
				$show_completed = false;
			}

			if ( $show_completed ) {
				?>

				<div class="testresults">
					<p><span class="dashicons dashicons-yes"></span> <?php _e( 'Everything looks good.', 'security-ninja' ); ?></p>
				</div>
				<?php
			}
			?>
			<p class="sn-notice">
				<?php
				printf(
					esc_html__( 'Last scan at %1$s. %2$s files were checked in %3$s sec.', 'security-ninja' ),
					date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $results['last_run'] ),
					number_format( $results['total'] ),
					number_format( $results['run_time'], 2 )
				);
				?>
			</p>

			<p class="sn-notice">
				<?php

				$version = get_bloginfo( 'version' );
				$locale  = get_locale();

				printf(
					esc_html__( 'You are running WordPress version %1$s %2$s.', 'security-ninja' ),
					$version,
					$locale
				);
				?>
			</p>

			<?php

		} else {
			?>
			<div class="testresults">

				<p><?php _e( 'Click the button to run the first test.', 'security-ninja' ); ?></p>
			</div>

			<?php
		}
		echo '</div>';

		if ( isset( $results['last_run'] ) && $results['last_run'] ) {

			echo '<div id="sn-cs-results">';

			if ( $results['unknown_bad'] ) {
				echo '<div class="sn-cs-changed-bad">';
				?>
				<div class="core-title">
					<h4><?php _e( 'Following files are unknown and should not be in your core folders', 'security-ninja' ); ?></h4>
				</div>

				<div class="changedcont">

					<p class="description"><?php _e( 'These are files not included with WordPress default installation and should not be in your core WordPress folders.', 'security-ninja' ); ?></p>

					<p class="description"><?php _e( 'These files can be leftovers from older WordPress installations, and are no longer needed.', 'security-ninja' ); ?></p>


					<form action="options.php" id="wf-cs-delete-all-unknown" method="post">
						<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
						<input type="hidden" name="action" value="wf_sn_cs_delete_all_unknown" />
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'wf-cs-delete-all-unknown-nonce' ); ?>" />
						<?php wp_referer_field(); ?>
						<?php
						$other_attributes = array( 'id' => 'wf-cs-delete-all-unknown-button' );
						submit_button( __( 'Delete all unknown files', 'security-ninja' ), 'secondary', 'wf-cs-delete-all-unknown', true, $other_attributes );
						?>
					</form>





					<?php
					echo self::list_files( $results['unknown_bad'], true, false, true );
					?>

				</div>

			</div>

				<?php
			}

			if ( $results['changed_bad'] ) {
				?>
			<div class="sn-cs-changed-bad">
				<div class="core-title">
					<h4><?php _e( 'The following WordPress core files have been modified', 'security-ninja' ); ?></h4>
				</div>
				<div class="changedcont">
					<p><?php _e( 'If you did not modify the following files yourself, this could indicate an infection on your website.', 'security-ninja' ); ?></p>

					<?php
					echo self::list_files( $results['changed_bad'], true, true );
					?>
				</div>
			</div>
				<?php
			}

			if ( $results['missing_bad'] ) {
				echo '<div class="sn-cs-missing-bad">';
				echo '<div class="core-title">';
				echo '<h4>' . __( 'Following core files are missing.', 'security-ninja' ) . '</h4>';
				echo '</div>';
				?>
			<div class="changedcont">

				<p class="description"><?php esc_html_e( 'Missing core files might indicate a bad auto-update or they simply were not copied on the server when the site was setup.', 'security-ninja' ); ?></p>

				<p class="description"><?php esc_html_e( 'If there is no legitimate reason for the files to be missing use the restore action to create them.', 'security-ninja' ); ?></p>
				<?php
				echo self::list_files( $results['missing_bad'], false, true );
				?>
			</div>
		</div>
				<?php
			}

			?>
</div><!-- #sn-cs-results -->


<p class="description"><?php printf( esc_html__( 'Your WordPress files are located in %s on your server.', 'security-ninja' ), '<code>' . ABSPATH . '</code>' ); ?></p>
<p class="description"><?php esc_html_e( 'Please use caution when deleting files because there is no undo button.', 'security-ninja' ); ?></p>


<p class="description"><?php esc_html_e( 'The fastest and easiest way to do any cleanup is to reinstall WordPress. You can do this fast and easy without any down time. Click the link to go to the Update page.', 'security-ninja' ); ?></p>
<p class="description"><?php esc_html_e( 'Find the button to reinstall WordPress. This will make sure no leftover or modified files.', 'security-ninja' ); ?> <a href="<?php echo admin_url( 'update-core.php' ); ?>">Update WordPress Updates</a></p>
<hr>

			<?php

			echo '<p class="description">' . __( 'Files are scanned and compared via the MD5 hashing algorithm to original WordPress core files available from wordpress.org.', 'security-ninja' ) . '</p>';

			echo '<p class="description">' . __( 'Not every change on core files is malicious and changes can serve a legitimate purpose. However if you are not a developer and you did not change the files yourself the changes most probably come from an exploit.', 'security-ninja' ) . '</p>';

			echo '<p class="description">' . __( 'The WordPress community strongly advises that you never modify any WP core files!', 'security-ninja' ) . '</p>';

		}
		// dialogs
		echo '<div id="source-dialog" style="display: none;" title="' . __( 'File Source', 'security-ninja' ) . '"><p>' . __( 'Please wait.', 'security-ninja' ) . '</p></div>';
		echo '<div id="restore-dialog" style="display: none;" title="' . __( 'Restore File', 'security-ninja' ) . '"><p>' . __( 'Please wait.', 'security-ninja' ) . '</p></div>';
		echo '<div id="delete-dialog" style="display: none;" title="' . __( 'Delete file', 'security-ninja' ) . '"><p>' . __( 'Please wait.', 'security-ninja' ) . '</p></div>';

	} // core_page


	// check if files can be restored
	public static function check_file_write() {
		// @todo lars check this out
		$url = wp_nonce_url( 'options.php?page=wf-sn', 'wf-sn-cs' );
		ob_start();
		$creds = request_filesystem_credentials( $url, '', false, false, null );
		ob_end_clean();

		return (bool) $creds;
	} // check_file_write


	// restore the selected file
	public static function restore_file() {
		check_ajax_referer( 'wf_sn_cs' );
		$file = str_replace( ABSPATH, '', stripslashes( $_POST['filename'] ) );
		// @lars todo?
		$url   = wp_nonce_url( 'options.php?page=wf-sn', 'wf-sn-cs' );
		$creds = request_filesystem_credentials( $url, '', false, false, null );
		if ( ! WP_Filesystem( $creds ) ) {
			die( 'can\'t write to file.' );
		}

		$org_file = wp_remote_get( 'http://core.trac.wordpress.org/browser/tags/' . get_bloginfo( 'version' ) . '/src/' . $file . '?format=txt' );
		if ( ! $org_file['body'] ) {
			die( 'can\'t download remote file source.' );
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem->put_contents( trailingslashit( ABSPATH ) . $file, $org_file['body'], FS_CHMOD_FILE ) ) {
			die( 'unknown error while writing file.' );
		}

		self::scan_files();
		die( '1' );
	} // restore_file



	// restore the selected file
	public static function delete_file() {
		check_ajax_referer( 'wf_sn_cs' );
		$file  = str_replace( ABSPATH, '', stripslashes( $_POST['filename'] ) );
		$url   = wp_nonce_url( 'options.php?page=wf-sn', 'wf-sn-cs' );
		$creds = request_filesystem_credentials( $url, '', false, false, null );
		if ( ! WP_Filesystem( $creds ) ) {
			wf_sn_el_modules::log_event( 'security_ninja', 'core_scanner_delete_file', sprintf( esc_html__( 'Cannot delete %s', 'security-ninja' ), $file ) );

			die( sprintf( esc_html__( 'Cannot delete %s', 'security-ninja' ), $file ) );
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem->delete( trailingslashit( ABSPATH ) . $file, false ) ) {
			wf_sn_el_modules::log_event( 'security_ninja', 'core_scanner_delete_file', sprintf( esc_html__( 'Unknown error deleting %s', 'security-ninja' ), $file ) );
			// translators: Problem deleting specific file
			die( sprintf( esc_html__( 'Unknown error deleting %s', 'security-ninja' ), $file ) );

		} else {
			// translators: A file was deleted by a user
			wf_sn_el_modules::log_event( 'security_ninja', 'core_scanner_delete_file', sprintf( esc_html__( 'File %s was deleted by user.', 'security-ninja' ), $file ) );

			// Finds and deletes file from result array also
			$wf_sn_cs_options = get_option( WF_SN_CS_OPTIONS_KEY );
			if ( ( $key = array_search( $file, $wf_sn_cs_options['unknown_bad'] ) ) !== false ) {
				unset( $wf_sn_cs_options['unknown_bad'][ $key ] );
				update_option( WF_SN_CS_OPTIONS_KEY, $wf_sn_cs_options );
			}
		}

		//self::scan_files(); // lars - don't want to rescan every time. list item is removed from results via js.
		die( '1' ); // @todo
	} // delete_file


	// render restore file dialog
	public static function delete_file_dialog() {
		check_ajax_referer( 'wf_sn_cs' );
		$out = array();

		if ( md5( WF_SN_CS_SALT . stripslashes( @$_POST['filename'] ) ) != $_POST['hash'] ) {
			$out['err'] = 'Cheating are you?';
			die( wp_json_encode( $out ) );
		}

		if ( self::check_file_write() ) {
			$out['out'] = '<p>' . __( 'Are you sure you want to delete this file?', 'security-ninja' ) . '</p>';

			$out['out'] .= '<p><strong>' . __( 'Please note there is NO undo function!', 'security-ninja' ) . '</strong></p>';

			$out['out'] .= '<p><input type="button" value="' . __( 'Delete file', 'security-ninja' ) . '" data-filename="' . stripslashes( @$_POST['filename'] ) . '" id="sn-delete-file" data-hash="' . stripslashes( $_POST['hash'] ) . '" class="button-primary input-button" /></p>';

		} else {
			$out['out']  = '<p>' . __( 'Your WordPress core files are not writable from PHP.', 'security-ninja' ) . '</p>';
			$out['out'] .= '<p>' . __( 'This is not a bad thing as it increases your security but you will have to delete the file manually by logging on to your FTP account.', 'security-ninja' ) . '</p>';
		}

		die( wp_json_encode( $out ) );
	} // delete_file_dialog






	// render restore file dialog
	public static function restore_file_dialog() {
		check_ajax_referer( 'wf_sn_cs' );

		$out = array();

		if ( md5( WF_SN_CS_SALT . stripslashes( @$_POST['filename'] ) ) !== $_POST['hash'] ) {
			$out['err'] = 'Cheating are you?';
			die( wp_json_encode( $out ) );
		}

		if ( self::check_file_write() ) {
			$out['out'] = '<p>' . __( 'By clicking the "restore file" button a copy of the original file will be downloaded from wordpress.org and the modified file will be overwritten.', 'security-ninja' ) . '</p>';

			$out['out'] .= '<p><strong>' . __( 'Please note there is NO undo function!', 'security-ninja' ) . '</strong></p>';

			$out['out'] .= '<p><input type="button" value="' . __( 'Restore file', 'security-ninja' ) . '" data-filename="' . stripslashes( @$_POST['filename'] ) . '" id="sn-restore-file" class="button-primary input-button" /></p>';

		} else {
			$out['out']  = '<p>' . __( 'Your WordPress core files are not writable from PHP.', 'security-ninja' ) . '</p>';
			$out['out'] .= '<p>' . __( 'This is not a bad thing as it increases your security but you will have to restore the file manually by logging on to your FTP account and overwriting the file.', 'security-ninja' ) . '</p>';

			// @i8n
			$out['out'] .= '<p>You can <a target="_blank" href="http://core.trac.wordpress.org/browser/tags/' . get_bloginfo( 'version' ) . '/' . str_replace( ABSPATH, '', stripslashes( $_POST['filename'] ) ) . '?format=txt' . '">download the file directly</a> from worpress.org.</p>';
		}

		die( wp_json_encode( $out ) );
	} // restore_file



	/**
	 * Helper function for listing files
	 * @param  [type]  $files   Array of files to show.
	 * @param  boolean $view    Show view button. Default false.
	 * @param  boolean $restore Show restore button. Default false.
	 * @param  boolean $delete  Show delete button. Default false.
	 * @return [type]           [description]
	 */
	public static function list_files( $files, $view = false, $restore = false, $delete = false ) {
		$out  = '';
		$out .= '<ul class="sn-file-list">';

		foreach ( $files as $file ) {
			$out .= '<li>';
			$out .= '<span class="sn-file">' . '' . $file . '</span>';
			if ( $view ) {
				$out .= ' <button data-hash="' . md5( WF_SN_CS_SALT . ABSPATH . $file ) . '" data-file="' . ABSPATH . $file . '" href="#source-dialog" class="sn-show-source input-button gray">' . __( 'View', 'security-ninja' ) . '</button>';
			}
			if ( $restore ) {
				$out .= ' <button data-hash="' . md5( WF_SN_CS_SALT . ABSPATH . $file ) . '" data-file-short="' . $file . '" data-file="' . ABSPATH . $file . '" href="#restore-dialog" class="sn-restore-source">' . __( 'Restore', 'security-ninja' ) . '</button>';
			}

			if ( $delete ) {
				$out .= ' <button data-hash="' . md5( WF_SN_CS_SALT . ABSPATH . $file ) . '" data-file-short="' . $file . '" data-file="' . ABSPATH . $file . '" href="#delete-dialog" class="sn-delete-source">' . __( 'Delete', 'security-ninja' ) . '</abutton>';
			}

			// @todo add delete option
			$out .= '</li>';
		} // foreach $files

		$out .= '</ul>';

		return $out;
	} // list_files


	// display warning if test were never run
	public static function run_tests_warning() {
		$tests = get_option( WF_SN_CS_OPTIONS_KEY );

		if ( ! wf_sn::is_plugin_page() ) {
			return;
		}

		if ( ! empty( $tests['last_run'] ) && wf_sn::is_plugin_page() && ( current_time( 'timestamp' ) - 30 * 24 * 60 * 60 ) > $tests['last_run'] ) {
			?>
			<div class="notice notice-error">

				<p><?php _e( 'Core Scanner tests were not run for more than 30 days.', 'security-ninja' ); ?></p>

				<p><?php _e( 'We advice you to run the tests regularly. Click "Scan core files" to run them now check your core files for exploits.', 'security-ninja' ); ?></p>

			</div>
			<?php
		}
	} // run_tests_warning


	// clean-up when deactivated
	public static function deactivate() {
		delete_option( WF_SN_CS_OPTIONS_KEY );
	} // deactivate
} // wf_sn_cs class


// hook everything up
add_action( 'plugins_loaded', array( 'wf_sn_cs', 'init' ) );

// when deativated clean up
register_deactivation_hook( WF_SN_BASE_FILE, array( 'wf_sn_cs', 'deactivate' ) );
