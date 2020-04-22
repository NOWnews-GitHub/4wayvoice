<?php

if ( ! function_exists( 'add_action' ) ) {
	die( 'Please don\'t open this file directly!' );
}

define( 'WF_SN_AF_OPTIONS_KEY', 'wf_sn_af' );

require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/core_upgrader.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_anyone_can_register.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_blog_site_url_check.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_bruteforce_login.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_check_failed_login_info.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_config_chmod.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_config_location.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_core_updates_check.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_db_password_check.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_db_table_prefix_check.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_deactivated_plugins.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_deactivated_themes.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_debug_check.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_file_editor.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_install_file_check.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_plugins_ver_check.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_readme_check.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_license_check.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_rpc_meta.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_salt_keys_check.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_script_debug_check.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_themes_ver_check.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_upgrade_file_check.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_uploads_browsable.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_user_exists.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_ver_check.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_wlw_meta.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_wp_header_meta.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/plugin_upgrader.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/theme_upgrader.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_id1_user_check.php';
require_once WF_SN_PLUGIN_DIR . 'modules/auto-fixer/fixers/fix_usernames_enumeration.php';


class wf_sn_af {
	public static $wp_config_path = '';
	public static $hashed_files   = array();
	public static $af_options     = array();

	public static function init() {
		self::$af_options = get_option( WF_SN_AF_OPTIONS_KEY );

		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
			self::$wp_config_path = ABSPATH . 'wp-config.php';
		} elseif ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
			self::$wp_config_path = dirname( ABSPATH ) . '/wp-config.php';
		}

		if ( is_admin() ) {
			add_action( 'wp_ajax_sn_af_get_fix_info', array( __CLASS__, 'get_fix_info_ajax' ) );
			add_action( 'wp_ajax_sn_af_do_fix', array( __CLASS__, 'do_fix_ajax' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		} // if admin

		add_action( 'wp_ajax_nopriv_wf_sn_af_test_wp', array( __CLASS__, 'test_wordpress_status_request' ) );

		if ( ! empty( self::$af_options['sn-hide-wp-version'] ) ) {
			remove_action( 'wp_head', 'wp_generator' );
		}

		if ( ! empty( self::$af_options['sn-hide-wlw'] ) ) {
			remove_action( 'wp_head', 'wlwmanifest_link' );
		}

		if ( ! empty( self::$af_options['sn-hide-wp-login-info'] ) ) {
			  add_filter( 'login_errors', array( __CLASS__, 'hide_login_info' ) );
		}

		if ( isset( $_SERVER['QUERY_STRING'] ) && ! empty( self::$af_options['sn-disable-user-enumeration'] ) ) {
			if ( ( preg_match( '/author=([0-9]*)/i', $_SERVER['QUERY_STRING'] ) ) && ( strpos( $_SERVER['REQUEST_URI'], 'wp-admin/export.php' ) === false ) ) {
				http_response_code( 403 );
				die();
			}
			add_filter( 'redirect_canonical', array( __CLASS__, 'disable_usernames_enumeration' ), 10, 2 );
		}

		if ( ! empty( self::$af_options['sn-hide-rpc-meta'] ) ) {
			remove_action( 'wp_head', 'rsd_link' );
			add_filter( 'xmlrpc_enabled', '__return_false' );
		}

	} // init

	public static function disable_usernames_enumeration( $redirect, $request ) {
		if ( preg_match( '/\?author=([0-9]*)(\/*)/i', $request ) ) {
			http_response_code( 403 );
			die();
		} else {
			return $redirect;
		}
	}

	// update options key
	public static function update_option( $key, $value ) {
		self::$af_options[ $key ] = $value;
		return update_option( WF_SN_AF_OPTIONS_KEY, self::$af_options );
	} // update_option


	// enqueue CSS and JS scripts on plugin's admin page
	public static function enqueue_scripts() {
		if ( wf_sn::is_plugin_page() ) {
			$plugin_url = plugin_dir_url( __FILE__ );

			wp_enqueue_script( 'sn-af-js', $plugin_url . 'js/wf-sn-af.js', array( 'jquery' ), wf_sn::$version, true );
			$js_vars = array(
				'nonce_get_fix_info' => wp_create_nonce( 'wf_sn_get_fix_info' ),
				'nonce_do_fix'       => wp_create_nonce( 'wf_sn_do_fix' ),
			);
			wp_localize_script( 'jquery', 'wf_sn_af', $js_vars );
		} // if
	} // enqueue_scripts


	// see if we have a fix for that test and if we can apply
	public static function get_fix_info_ajax() {
		check_ajax_referer( 'wf_sn_get_fix_info' );

		if ( ! isset( $_GET['test_id'] ) ) {
			$data = array( 'message' => 'No test ID parsed' );
			wp_send_json_error( $data );
		}

		$test_id = sanitize_key( $_GET['test_id'] );

		$test_status = (int) @$_GET['test_status'];
		$out         = '';

		if ( ! class_exists( 'wf_sn_af_fix_' . $test_id ) ) {

			wp_send_json_success( __( 'Unfortunately, auto fix is not available for this test. Please read the instructions above to learn more about the test and how to resolve issues related to it.', 'security-ninja' ) );
		}

		if ( $test_status > 5 ) {
			$out .= __( 'There is nothing to fix for this test. It passed with flying colors.', 'security-ninja' );
		} elseif ( $test_status > 0 ) {
			$out .= __( "Unfortunately, automatic fix can't be applied.", 'security-ninja' );
		} else {
			if ( class_exists( 'wf_sn_af_fix_' . $test_id ) ) {
				$out .= '<p>' . call_user_func( 'wf_sn_af_fix_' . $test_id . '::get_label', 'info' ) . '</p>';
				if ( call_user_func( 'wf_sn_af_fix_' . $test_id . '::get_label', 'fixable' ) ) {
					$out .= '<a data-test-id="' . $test_id . '" href="#" class="button button-primary" id="sn_af_run_fix">' . __( 'Apply Fix', 'security-ninja' ) . '</a>';
				}
			} else {
				$out .= __( 'No automatic fix available.', 'security-ninja' );
			}
		}

		wp_send_json_success( $out );
	} // get_fix_info_ajax


	// see if we have a fix for that test and if we can apply
	public static function do_fix_ajax() {
		check_ajax_referer( 'wf_sn_do_fix' );

		if ( ! isset( $_GET['test_id'] ) ) {
			$data = array( 'message' => 'No test ID parsed' );
			wp_send_json_error( $data );
		}

		$test_id = sanitize_key( $_GET['test_id'] );

		if ( class_exists( 'wf_sn_af_fix_' . $test_id ) ) {
			$result = call_user_func( 'wf_sn_af_fix_' . $test_id . '::fix' );
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( __( 'Fix not available', 'security-ninja' ) );
		}
	} // do_fix_ajax


	public static function hide_login_info() {
		return __( 'Wrong username or password.', 'security-ninja' );
	} // hide_login_info


	public static function remove_define( $file_path, $constant ) {
		$file_contents = @file_get_contents( $file_path );
		if ( $file_contents && preg_match_all( '/define\(\s*[\'|"]\s*(' . $constant . ')\s*[\'|"]\s*,\s*(false|true|[\'|"].*[\'|"])\s*\);/i', $file_contents, $matches ) ) {
			$file_contents = str_replace( $matches[0], '', $file_contents );
			return file_put_contents( $file_path, $file_contents, LOCK_EX );
		} else {
			return false;
		}
	} // remove_define


	public static function update_define( $file_path, $constant, $new_value ) {
		$file_contents = file_get_contents( $file_path );

		// if define already exists update it
		if ( preg_match_all( '/define\([\'|"]\s*(' . $constant . ')\s*[\'|"]\s*,\s*(false|true|[\'|"].*[\'|"])\s*\);/i', $file_contents, $matches ) ) {
			if ( is_bool( $new_value ) ) {
				if ( $new_value ) {
					$file_contents = str_replace( $matches[0], "define('" . $constant . "', true);", $file_contents );
				} else {
					$file_contents = str_replace( $matches[0], "define('" . $constant . "', false);", $file_contents );
				}
			} else {
				$file_contents = str_replace( $matches[0], "define('" . $constant . "', '" . $new_value . "');", $file_contents );
			}
			file_put_contents( $file_path, $file_contents, LOCK_EX );
		} else {
			// if define does not exists insert it in a new line before require_once(ABSPATH.'wp-settings.php');
			if ( is_bool( $new_value ) ) {
				if ( $new_value ) {
					$new_define_line_contents = 'define(\'' . $constant . '\', true);';
				} else {
					$new_define_line_contents = 'define(\'' . $constant . '\', false);';
				}
			} else {
				$new_define_line_contents = 'define(\'' . $constant . '\', \'' . $new_value . '\');';
			}

			$config_file = file( $file_path );
			foreach ( $config_file as $line_num => $line ) {
				if ( strpos( str_replace( ' ', '', str_replace( '"', '\'', $line ) ), 'require_once(ABSPATH.\'wp-settings.php\');' ) !== false ) {
					$wp_settings_require_line = $line_num;
					break;
				}
			}
			array_splice( $config_file, $wp_settings_require_line, 0, $new_define_line_contents . PHP_EOL );
			file_put_contents( $file_path, implode( '', $config_file ), LOCK_EX );
		}
	} // update_define


	static function edit_variable( $file_path, $variable, $new_value ) {
		$file_contents = file_get_contents( $file_path );
		if ( preg_match_all( '/(\$' . $variable . ')\s*=\s*(.*?);/i', $file_contents, $matches ) ) {
			$full_expression     = $matches[0][0];
			$replaced_expression = str_replace( $matches[2][0], $new_value, $full_expression );
			$file_contents       = str_replace( $full_expression, $replaced_expression, $file_contents );
			file_put_contents( $file_path, $file_contents, LOCK_EX );
		}
	} // edit_variable


	static function find_string_in_folder( $folder, $string ) {
		$files   = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $folder ) );
		$results = array();
		foreach ( $files as $filename => $object ) {
			$content = @file_get_contents( $filename );
			if ( $content && strpos( $filename, 'security-ninja-auto-fixer-addon' ) === false && strpos( $content, $string ) !== false ) {
				$results[ self::get_string_line( $filename, $string ) ] = $filename;
			}
		}
		return $results;
	} // find_string_in_folder


	static function find_ini_set_in_folder( $folder, $directive, $values ) {
		if ( is_array( $values ) ) {
			$values = implode( '|', $values );
		}
		$pattern = '/ini_set\([\'|"]\s*(' . $directive . ')\s*[\'|"]\s*,\s*[\'|"|\s*]*(' . $values . ')[\'|"|\s*]*\s*\);/i';

		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $folder ) );

		$results = array();
		foreach ( $files as $filename => $object ) {
			$content = @file_get_contents( $filename );
			if ( $content && preg_match_all( $pattern, $content, $matches ) ) {
				foreach ( $matches[0] as $match ) {
					$results[ self::get_string_line( $filename, $match ) ] = $filename;
				}
			}
		}
		return $results;
	} // find_ini_set_in_folder


	static function get_string_line( $file_path, $string ) {
		$lines = file( $file_path );
		foreach ( $lines as $line_number => $line ) {
			if ( strpos( $line, $string ) !== false ) {
				return $line_number;
			}
		}
		return -1;
	} // get_string_line


	public static function backup_file( $file_path, $backup_timestamp, $fix ) {

		if ( ! is_dir( WP_CONTENT_DIR . '/sn-backups/' ) ) {
			mkdir( WP_CONTENT_DIR . '/sn-backups/', 0755 );
			chmod( WP_CONTENT_DIR . '/sn-backups/', 0755 );
		}

		copy( $file_path, WP_CONTENT_DIR . '/sn-backups/' . basename( $file_path ) . '_' . $backup_timestamp . '_' . $fix . '.wfbkp' );
	} // backup_file


	public static function backup_file_restore( $file_path, $backup_timestamp, $fix ) {
		if ( file_exists( WP_CONTENT_DIR . '/sn-backups/' . basename( $file_path ) . '_' . $backup_timestamp . '_' . $fix . '.wfbkp' ) ) {
			copy( WP_CONTENT_DIR . '/sn-backups/' . basename( $file_path ) . '_' . $backup_timestamp . '_' . $fix . '.wfbkp', $file_path );
		}
	} // backup_file_restore


	/**
	 * Tests if WordPress is accessible by posting AJAX req.
	 * @return bool True if WP is up, false is WP is down
	 */
	static function test_wordpress_status() {
		$response = wp_remote_post(
			admin_url( 'admin-ajax.php' ),
			array(
				'timeout' => 120,
				'body'    => array( 'action' => 'wf_sn_af_test_wp' ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( site_url() === trim( $response['body'] ) ) {
			return true;
		} else {
			return false;
		}
	} // test_wordpress_status


	static function generate_hashes_dir( $origin_directory ) {
		$current_working_directory = dir( $origin_directory );
		while ( $entry = $current_working_directory->read() ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			if ( is_dir( $origin_directory . '\\' . $entry ) ) {
				self::generate_hashes_dir( $origin_directory . '\\' . $entry );
			} else {
				$ext                             = pathinfo( $entry, PATHINFO_EXTENSION );
				$filepath                        = self::fix_path( $origin_directory . '\\' . $entry );
				$md5                             = md5_file( $origin_directory . '\\' . $entry );
				self::$hashed_files[ $filepath ] = $md5;
			}
		} // while
		$current_working_directory->close();
	} // generate_hashes_dir


	public static function generate_hash_file( $filepath ) {
		return md5_file( $filepath );
	} // generate_hash_file


	static function fix_path( $path ) {
		$path = str_replace( getcwd(), '', $path );
		$path = str_replace( '\\', '/', $path );
		$path = str_replace( '//', '/', $path );
		$path = trim( $path, '/' );

		return $path;
	} // fix_path


	public static function test_wordpress_status_request() {
		echo site_url();
		die();
	} // test_wordpress_status_request


	public static function mark_as_fixed( $test ) {
		return;
	} // mark_as_fixed

	public static function directory_unlink( $dir ) {
		$files = array_diff( scandir( $dir ), array( '.', '..' ) );
		foreach ( $files as $file ) {
			( is_dir( "$dir/$file" ) ) ? self::directory_unlink( "$dir/$file" ) : unlink( "$dir/$file" );
		}
		return rmdir( $dir );
	} // directory_unlink


	public static function directory_copy( $src, $dst ) {
		$dir = opendir( $src );
		@mkdir( $dst );
		while ( false !== ( $file = readdir( $dir ) ) ) {
			if ( ( '.' !== $file ) && ( '..' !== $file ) ) {
				if ( is_dir( $src . '/' . $file ) ) {
					self::directory_copy( $src . '/' . $file, $dst . '/' . $file );
				} else {
					copy( $src . '/' . $file, $dst . '/' . $file );
				}
			}
		}
		closedir( $dir );
	} // directory_copy


	// clean-up when deactivated
	public static function deactivate() {
		delete_option( WF_SN_EL_OPTIONS_KEY );
	} // deactivate
} // wf_sn_af class


// hook everything up
add_action( 'plugins_loaded', array( 'wf_sn_af', 'init' ) );

// when deativated, clean up
register_deactivation_hook( WF_SN_BASE_FILE, array( 'wf_sn_af', 'deactivate' ) );
