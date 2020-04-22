<?php
/*
 * Security Ninja
 * Test functions
 */


// this is an include only WP file
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class Wf_Sn_Tests extends WF_SN {


	public static $security_tests;

	public static function return_security_tests() {
		return array(
			'ver_check'                 => array(
				'title'   => __( 'Check if WordPress core is up to date.', 'security-ninja' ),
				'score'   => 5,
				'msg_ok'  => __( 'You are using the latest version of WordPress.', 'security-ninja' ),
				'msg_bad' => __( 'You are not using the latest version of WordPress.', 'security-ninja' ),
			),

			'core_updates_check'        => array(
				'title'   => __( 'Check if automatic WordPress core updates are enabled.', 'security-ninja' ),
				'score'   => 5,
				'msg_ok'  => __( 'Core updates are configured optimally.', 'security-ninja' ),
				'msg_bad' => __( 'Automatic core updates are not configured optimally.', 'security-ninja' ),
			),

			'plugins_ver_check'         => array(
				'title'   => __( 'Check if plugins are up to date.', 'security-ninja' ),
				'score'   => 5,
				'msg_ok'  => __( 'All plugins are up to date.', 'security-ninja' ),
				// translators: Number of plugins with new versions available
				'msg_bad' => __( 'At least %s plugins have new versions available and have to be updated.', 'security-ninja' ),
			),

			'deactivated_plugins'       => array(
				'title'   => __( 'Check if there are deactivated plugins.', 'security-ninja' ),
				'score'   => 3,
				'msg_ok'  => __( 'There are no deactivated plugins.', 'security-ninja' ),
				// translators: Number of deactivated plugins
				'msg_bad' => __( 'There are %s deactivated plugins.', 'security-ninja' ),
			),

			'old_plugins'               => array(
				'title'       => __( 'Check if active plugins have been updated in the last 12 months.', 'security-ninja' ),
				'score'       => 4,
				'msg_ok'      => __( 'All active plugins have been updated in the last 12 months.', 'security-ninja' ),
				'msg_warning' => __( 'We were not able to verify the last update date for any of your active plugins.', 'security-ninja' ),
				// translators: Number of plugins not updated past 12 months
				'msg_bad'     => __( 'The following plugin(s) have not been updated in the last 12 months: %s.', 'security-ninja' ),
			),

			'incompatible_plugins'      => array(
				'title'       => __( 'Check if active plugins are compatible with your version of WP.', 'security-ninja' ),
				'score'       => 3,
				'msg_ok'      => __( 'All active plugins are compatible with your version of WordPress.', 'security-ninja' ),
				'msg_warning' => __( 'We were not able to verify the compatibility for any of your active plugins.', 'security-ninja' ),
				// translators: Number of plugins untested with current version of WordPress
				'msg_bad'     => __( 'The following plugin(s) have not been tested with your version of WP and could be incompatible: %s.', 'security-ninja' ),
			),

			'themes_ver_check'          => array(
				'title'   => __( 'Check if themes are up to date.', 'security-ninja' ),
				'score'   => 5,
				'msg_ok'  => __( 'All themes are up to date.', 'security-ninja' ),
				// translators: How many installed themes are outdated
				'msg_bad' => __( '%s theme(s) are outdated.', 'security-ninja' ),
			),

			'deactivated_themes'        => array(
				'title'   => __( 'Check if there are deactivated themes.', 'security-ninja' ),
				'score'   => 3,
				'msg_ok'  => __( 'There are no deactivated themes.', 'security-ninja' ),
				// translators: How many deactivated themes
				'msg_bad' => __( 'There are %s deactivated themes.', 'security-ninja' ),
			),

			'wp_header_meta'            => array(
				'title'       => __( 'Check if full WordPress version info is revealed in page\'s meta data.', 'security-ninja' ),
				'score'       => 4,
				'msg_ok'      => __( 'Your site does not reveal full WordPress version info.', 'security-ninja' ),
				'msg_warning' => __( 'Site homepage could not be fetched.', 'security-ninja' ),
				'msg_bad'     => __( 'Your site reveals full WordPress version info in meta tags.', 'security-ninja' ),
			),

			'readme_check'              => array(
				'title'       => __( 'Check if readme.html file is accessible via HTTP on the default location.', 'security-ninja' ),
				'score'       => 4,
				'msg_ok'      => __( 'readme.html is not accessible at the default location.', 'security-ninja' ),
				'msg_warning' => __( 'Unable to determine status of readme.html.', 'security-ninja' ),
				'msg_bad'     => __( 'readme.html is accessible via HTTP on the default location.', 'security-ninja' ),
			),

			'license_check'             => array(
				'title'       => __( 'Check if license.txt file is accessible via HTTP on the default location.', 'security-ninja' ),
				'score'       => 4,
				'msg_ok'      => __( 'license.txt is not accessible at the default location.', 'security-ninja' ),
				'msg_warning' => __( 'Unable to determine status of license.txt.', 'security-ninja' ),
				'msg_bad'     => __( 'license.txt is accessible via HTTP on the default location.', 'security-ninja' ),
			),

			'php_ver'                   => array(
				'title'       => __( 'Check the PHP version.', 'security-ninja' ),
				'score'       => 5,
				// translators: Which PHP version is in use - All is OK
				'msg_ok'      => __( 'You are using PHP version %s.', 'security-ninja' ),
				// translators: Which PHP version is in use - Warning
				'msg_warning' => __( 'You are using PHP version %s which meets the minimum requirements set by WP, but it is recommended upgrading to PHP 7.', 'security-ninja' ),
				// translators: Which PHP version is in use - Fail message
				'msg_bad'     => __( 'You are using PHP version %s which is obsolete. Please upgrade to PHP 7.', 'security-ninja' ),
			),

			'mysql_ver'                 => array(
				'title'       => __( 'Check the MySQL version.', 'security-ninja' ),
				'score'       => 5,
				// translators:
				'msg_ok'      => __( 'You are using MySQL version %s.', 'security-ninja' ),
				// translators:
				'msg_warning' => __( 'You are using MySQL version %s which meets the minimum requirements set by WP, but it is recommended upgrading to at least v5.6.', 'security-ninja' ),
				// translators:
				'msg_bad'     => __( 'You are using MySQL version %s which is obsolete. Please upgrade to at least v5.6.', 'security-ninja' ),
			),

			'php_headers'               => array(
				'title'   => __( 'Check if server response headers contain detailed PHP version info.', 'security-ninja' ),
				'score'   => 2,
				'msg_ok'  => __( 'Headers does not contain detailed PHP version info.', 'security-ninja' ),
				'msg_bad' => __( 'Server response headers contain detailed PHP version info.', 'security-ninja' ),
			),

			'expose_php_check'          => array(
				'title'   => __( 'Check if expose_php PHP directive is turned off.', 'security-ninja' ),
				'score'   => 1,
				'msg_ok'  => __( 'expose_php PHP directive is turned off.', 'security-ninja' ),
				'msg_bad' => __( 'expose_php PHP directive is turned on.', 'security-ninja' ),
			),

			'user_exists'               => array(
				'title'   => __( 'Check if user with username "admin" exists.', 'security-ninja' ),
				'score'   => 4,
				'msg_ok'  => __( 'User "admin" does not exist.', 'security-ninja' ),
				'msg_bad' => __( 'User "admin" exists.', 'security-ninja' ),
			),

			'anyone_can_register'       => array(
				'title'   => __( 'Check if "anyone can register" option is enabled.', 'security-ninja' ),
				'score'   => 3,
				'msg_ok'  => __( '"Anyone can register" option is disabled.', 'security-ninja' ),
				'msg_bad' => __( '"Anyone can register" option is enabled.', 'security-ninja' ),
			),

			'check_failed_login_info'   => array(
				'title'   => __( 'Check for display of unnecessary information on failed login attempts.', 'security-ninja' ),
				'score'   => 2,
				'msg_ok'  => __( 'No unnecessary info is shown on failed login attempts.', 'security-ninja' ),
				'msg_bad' => __( 'Unnecessary information is displayed on failed login attempts.', 'security-ninja' ),
			),

			'db_table_prefix_check'     => array(
				'title'   => __( 'Check if database table prefix is the default one (wp_).', 'security-ninja' ),
				'score'   => 2,
				'msg_ok'  => __( 'Database table prefix is not default.', 'security-ninja' ),
				'msg_bad' => __( 'Database table prefix is default.', 'security-ninja' ),
			),

			'salt_keys_check'           => array(
				'title'   => __( 'Check if security keys and salts have proper values.', 'security-ninja' ),
				'score'   => 5,
				'msg_ok'  => __( 'All keys have proper values set.', 'security-ninja' ),
				// translators:
				'msg_bad' => __( 'Following keys do not have proper values set: %s.', 'security-ninja' ),
			),

			'salt_keys_age_check'       => array(
				'title'       => __( 'Check the age of security keys and salts.', 'security-ninja' ),
				'score'       => 1,
				'msg_ok'      => __( 'Keys and salts have been changed in the last 3 months.', 'security-ninja' ),
				'msg_warning' => __( 'Unable to read wp-config.php.', 'security-ninja' ),
				'msg_bad'     => __( 'Keys and salts have not been changed for more than 3 months.', 'security-ninja' ),
			),

			'db_password_check'         => array(
				'title'   => __( 'Check the strength of WordPress database password.', 'security-ninja' ),
				'score'   => 5,
				'msg_ok'  => __( 'Database password is strong enough.', 'security-ninja' ),
				// translators:
				'msg_bad' => __( 'Database password is weak (%s).', 'security-ninja' ),
			),

			'debug_check'               => array(
				'title'   => __( 'Check if general debug mode is enabled.', 'security-ninja' ),
				'score'   => 4,
				'msg_ok'  => __( 'General debug mode is disabled.', 'security-ninja' ),
				'msg_bad' => __( 'General debug mode is enabled.', 'security-ninja' ),
			),

			'debug_log_file_check'      => array(
				'title'       => __( 'Check if WordPress debug.log file exists.', 'security-ninja' ),
				'score'       => 4,
				'msg_ok'      => __( 'Great, the debug.log file does not exist or you are blocking access to it.', 'security-ninja' ),
				'msg_warning' => __( 'We were not able to check for the debug.log file.', 'security-ninja' ),
				'msg_bad'     => __( 'The debug.log file exists - please delete or block access.', 'security-ninja' ),
			),

			'db_debug_check'            => array(
				'title'   => __( 'Check if database debug mode is enabled.', 'security-ninja' ),
				'score'   => 4,
				'msg_ok'  => __( 'Database debug mode is disabled.', 'security-ninja' ),
				'msg_bad' => __( 'Database debug mode is enabled.', 'security-ninja' ),
			),

			'script_debug_check'        => array(
				'title'   => __( 'Check if JavaScript debug mode is enabled.', 'security-ninja' ),
				'score'   => 4,
				'msg_ok'  => __( 'JavaScript debug mode is disabled.', 'security-ninja' ),
				'msg_bad' => __( 'JavaScript debug mode is enabled.', 'security-ninja' ),
			),

			'display_errors_check'      => array(
				'title'   => __( 'Check if display_errors PHP directive is turned off.', 'security-ninja' ),
				'score'   => 4,
				'msg_ok'  => __( 'display_errors PHP directive is turned off.', 'security-ninja' ),
				'msg_bad' => __( 'display_errors PHP directive is turned on.', 'security-ninja' ),
			),

			'blog_site_url_check'       => array(
				'title'   => __( 'Check if WordPress installation address is the same as the site address.', 'security-ninja' ),
				'score'   => 2,
				'msg_ok'  => __( 'WordPress installation address is different from the site address.', 'security-ninja' ),
				'msg_bad' => __( 'WordPress installation address is the same as the site address.', 'security-ninja' ),
			),

			'config_chmod'              => array(
				'title'       => __( 'Check if wp-config.php file has the right permissions (chmod) set.', 'security-ninja' ),
				'score'       => 5,
				// translators:
				'msg_ok'      => __( 'WordPress config file has the right chmod set.', 'security-ninja' ),
				'msg_warning' => __( 'Unable to read chmod of wp-config.php.', 'security-ninja' ),
				// translators:
				'msg_bad'     => __( 'Current wp-config.php chmod (%s) is not ideal and other users on the server can access the file.', 'security-ninja' ),
			),

			'install_file_check'        => array(
				'title'       => __( 'Check if install.php file is accessible via HTTP on the default location.', 'security-ninja' ),
				'score'       => 2,
				'msg_ok'      => __( 'install.php is not accessible on the default location.', 'security-ninja' ),
				'msg_warning' => __( 'Unable to determine status of install.php file.', 'security-ninja' ),
				'msg_bad'     => __( 'install.php is accessible via HTTP on the default location.', 'security-ninja' ),
			),

			'upgrade_file_check'        => array(
				'title'       => __( 'Check if upgrade.php file is accessible via HTTP on the default location.', 'security-ninja' ),
				'score'       => 2,
				'msg_ok'      => __( 'upgrade.php is not accessible on the default location.', 'security-ninja' ),
				'msg_warning' => __( 'Unable to determine status of upgrade.php file.', 'security-ninja' ),
				'msg_bad'     => __( 'upgrade.php is accessible via HTTP on the default location.', 'security-ninja' ),
			),

			'register_globals_check'    => array(
				'title'   => __( 'Check if register_globals PHP directive is turned off.', 'security-ninja' ),
				'score'   => 5,
				'msg_ok'  => __( 'register_globals PHP directive is turned off.', 'security-ninja' ),
				'msg_bad' => __( 'register_globals PHP directive is turned on.', 'security-ninja' ),
			),

			'safe_mode_check'           => array(
				'title'   => __( 'Check if PHP safe mode is disabled.', 'security-ninja' ),
				'score'   => 5,
				'msg_ok'  => __( 'Safe mode is disabled.', 'security-ninja' ),
				'msg_bad' => __( 'Safe mode is enabled.', 'security-ninja' ),
			),

			'allow_url_include_check'   => array(
				'title'   => __( 'Check if allow_url_include PHP directive is turned off.', 'security-ninja' ),
				'score'   => 5,
				'msg_ok'  => __( 'allow_url_include PHP directive is turned off.', 'security-ninja' ),
				'msg_bad' => __( 'allow_url_include PHP directive is turned on.', 'security-ninja' ),
			),

			'file_editor'               => array(
				'title'   => __( 'Check if plugins/themes file editor is enabled.', 'security-ninja' ),
				'score'   => 2,
				'msg_ok'  => __( 'File editor is disabled.', 'security-ninja' ),
				'msg_bad' => __( 'File editor is enabled.', 'security-ninja' ),
			),

			'uploads_browsable'         => array(
				'title'       => __( 'Check if uploads folder is browsable by browsers.', 'security-ninja' ),
				'score'       => 2,
				'msg_ok'      => __( 'Uploads folder is not browsable.', 'security-ninja' ),
				'msg_warning' => __( 'Unable to determine status of uploads folder.', 'security-ninja' ),
				// translators:
				'msg_bad'     => __( '<a href="%s" target="_blank">Uploads folder</a> is browsable.', 'security-ninja' ),
			),

			'id1_user_check'            => array(
				'title'   => __( 'Check if user with ID "1" exists.', 'security-ninja' ),
				'score'   => 1,
				'msg_ok'  => __( 'Such user does not exist.', 'security-ninja' ),
				// translators:
				'msg_bad' => __( 'User with ID "1" exists; username: %s.', 'security-ninja' ),
			),

			'wlw_meta'                  => array(
				'title'       => __( 'Check if Windows Live Writer link is present in the header data.', 'security-ninja' ),
				'score'       => 1,
				'msg_ok'      => __( 'WLW link is not present in the header.', 'security-ninja' ),
				'msg_warning' => __( 'Unable to perform test.', 'security-ninja' ),
				'msg_bad'     => __( 'WLW link is present in the header.', 'security-ninja' ),
			),

			'config_location'           => array(
				'title'   => __( 'Check if wp-config.php is present on the default location.', 'security-ninja' ),
				'score'   => 2,
				'msg_ok'  => __( 'wp-config.php is not present on the default location.', 'security-ninja' ),
				'msg_bad' => __( 'wp-config.php is present on the default location.', 'security-ninja' ),
			),

			'mysql_external'            => array(
				'title'       => __( 'Check if MySQL server is connectable from outside with the WP user.', 'security-ninja' ),
				'score'       => 2,
				'msg_ok'      => __( 'No, you can only connect to the MySQL from localhost.', 'security-ninja' ),
				// translators: Not conclusive for database user
				'msg_warning' => __( 'Test results are not conclusive for MySQL user %s.', 'security-ninja' ),
				'msg_bad'     => __( 'You can connect to the MySQL server from any host.', 'security-ninja' ),
			),

			'rpc_meta'                  => array(
				'title'       => __( 'Check if EditURI (XML-RPC) link is present in the header data.', 'security-ninja' ),
				'score'       => 1,
				'msg_ok'      => __( 'The EditURI (XML-RPC) link is not present in the header.', 'security-ninja' ),
				'msg_warning' => __( 'Unable to perform test.', 'security-ninja' ),
				'msg_bad'     => __( 'EditURI link is present in the header.', 'security-ninja' ),
			),

			'tim_thumb'                 => array(
				'title'       => __( 'Check if Timthumb script is used in the active theme.', 'security-ninja' ),
				'score'       => 5,
				// translators: The Timthumb script was not found in the theme
				'msg_ok'      => __( 'Timthumb was not found in %s.', 'security-ninja' ),
				'msg_warning' => __( 'Unable to perform test. Cannot read the PHP files in the theme', 'security-ninja' ),
				// translators: The Timthumb script WAS found in the theme
				'msg_bad'     => __( 'Timthumb was found in %s.', 'security-ninja' ),
			),

			'shellshock_6271'           => array(
				'title'       => __( 'Check if the server is vulnerable to the Shellshock bug #6271.', 'security-ninja' ),
				'score'       => 4,
				'msg_ok'      => __( 'Server is not vulnerable.', 'security-ninja' ),
				'msg_warning' => __( 'You are running WordPress on a server which is not affected by the Shellshock bug or PHP proc_open() is disabled on the server.', 'security-ninja' ),
				'msg_bad'     => __( 'Server is vulnerable to Shellshock!', 'security-ninja' ),
			),

			'shellshock_7169'           => array(
				'title'       => __( 'Check if the server is vulnerable to the Shellshock bug #7169.', 'security-ninja' ),
				'score'       => 4,
				'msg_ok'      => __( 'Server is not vulnerable.', 'security-ninja' ),
				'msg_warning' => __( 'You are running WordPress on a server which is not affected by the Shellshock bug or PHP proc_open() is disabled on the server.', 'security-ninja' ),
				'msg_bad'     => __( 'Server is vulnerable to Shellshock!', 'security-ninja' ),
			),

			'admin_ssl'                 => array(
				'title'       => __( 'Check if admin interface is delivered via SSL', 'security-ninja' ),
				'score'       => 3,
				'msg_ok'      => __( 'Connection is secured via SSL.', 'security-ninja' ),
				'msg_warning' => __( 'Unable to determine if the admin interface is secured via SSL.', 'security-ninja' ),
				'msg_bad'     => __( 'Connection is not secured via SSL.', 'security-ninja' ),
			),

			'mysql_permissions'         => array(
				'title'       => __( 'Check if MySQL account used by WordPress has too many permissions', 'security-ninja' ),
				'score'       => 2,
				'msg_ok'      => __( 'Only those permissions that are needed are granted.', 'security-ninja' ),
				'msg_warning' => __( 'Things are most probably fine but we would still advise you to manually check priviledges.', 'security-ninja' ),
				'msg_bad'     => __( 'Account has far too many unnecessary permissions granted.', 'security-ninja' ),
			),

			'usernames_enumeration'     => array(
				'title'   => __( 'Check if the list of usernames can be fetched by looping through user IDs', 'security-ninja' ),
				'score'   => 3,
				'msg_ok'  => __( 'Usernames cannot be fetched via user IDs', 'security-ninja' ),
				'msg_bad' => __( 'List of all usernames can be fetched by looping through user IDs via siteurl.com/?author={id}', 'security-ninja' ),
			),
			'rest_api_links'            => array(
				'title'       => __( 'Check if the REST API links are shown in code', 'security-ninja' ),
				'score'       => 2,
				'msg_ok'      => __( 'Great, REST API links are not shown in the HTML code.', 'security-ninja' ),
				'msg_warning' => __( 'The REST API links are visible in the source code.', 'security-ninja' ),
				'msg_bad'     => __( 'The REST API links are visible in the source code.', 'security-ninja' ),
			),

			'x_content_type_options'    => array(
				'title'   => __( 'Check if server response headers contain X-Content-Type-Options.', 'security-ninja' ),
				'score'   => 4,
				'msg_ok'  => __( 'Great, the header contains X-Content-Type-Options.', 'security-ninja' ),
				'msg_bad' => __( 'The server does not have the "X-Content-Type-Options" feature enabled.', 'security-ninja' ),
			),

			'x_frame_options'           => array(
				'title'   => __( 'Check if server response headers contain X-Frame-Options.', 'security-ninja' ),
				'score'   => 4,
				'msg_ok'  => __( 'Great, the header contains X-Frame-Options.', 'security-ninja' ),
				'msg_bad' => __( 'The server does not have the "X-Frame-Options" feature set.', 'security-ninja' ),
			),

			'xxss_protection'           => array(
				'title'   => __( 'Check if server response headers contain X-XSS-Protection.', 'security-ninja' ),
				'score'   => 4,
				'msg_ok'  => __( 'Great, the header contains X-XSS-Protection.', 'security-ninja' ),
				'msg_bad' => __( 'The server does not have the "X-XSS-Protection" feature enabled.', 'security-ninja' ),
			),

			'strict_transport_security' => array(
				'title'   => __( 'Check if server response headers contain Strict-Transport-Security.', 'security-ninja' ),
				'score'   => 4,
				'msg_ok'  => __( 'Great, the header contains Strict-Transport-Security.', 'security-ninja' ),
				'msg_bad' => __( 'The server does not have the "Strict-Transport-Security" feature enabled.', 'security-ninja' ),
			),

			'referrer_policy'           => array(
				'title'   => __( 'Check if server response headers contain Referrer-Policy.', 'security-ninja' ),
				'score'   => 4,
				'msg_ok'  => __( 'Great, the header contains Referrer-Policy.', 'security-ninja' ),
				'msg_bad' => __( 'Your website does not have the "Referrer-Policy" header enabled.', 'security-ninja' ),
			),

			'feature_policy'            => array(
				'title'   => __( 'Check if server response headers contain Feature-Policy.', 'security-ninja' ),
				'score'   => 4,
				'msg_ok'  => __( 'Great, the header contains Feature-Policy.', 'security-ninja' ),
				'msg_bad' => __( 'Your website does not have the "Feature-Policy" header enabled.', 'security-ninja' ),
			),

			'content_security_policy'   => array(
				'title'       => __( 'Check if server response headers contain Content-Security-Policy.', 'security-ninja' ),
				'score'       => 4,
				'msg_ok'      => __( 'Great, the header contains Content-Security-Policy.', 'security-ninja' ),
				'msg_bad'     => __( 'The server does not have the "Content-Security-Policy" feature enabled.', 'security-ninja' ),
				'msg_warning' => __( 'The report only mode has been set. Remember to turn off "Report Only" when you are finished.', 'security-ninja' ),
			),

			'rest_api_enabled'          => array(
				'title'       => __( 'Check if the REST API is enabled.', 'security-ninja' ), // @todo
				'score'       => 2,
				'msg_ok'      => __( 'The REST API is blocked.', 'security-ninja' ), // @todo
				'msg_bad'     => __( 'The REST API is enabled publicly.', 'security-ninja' ), // @todo
				'msg_warning' => __( 'We could not test if the REST API is enabled.', 'security-ninja' ),
			),

			'dangerous_files'           => array(
				'title'   => __( 'Check for unwanted files in your root folder you should remove.', 'security-ninja' ),
				'score'   => 3,
				'msg_ok'  => __( 'Great! No unwanted files were found!', 'security-ninja' ),
				'msg_bad' => __( 'Unwanted files found in your website root folder, you should delete these files.', 'security-ninja' ),
			),
		);
	}

	public static function rest_api_enabled() {
		$return = array();

		$url = get_rest_url();

		// We get false? Assume REST API blocked then
		if ( ! $url ) {
			$return['status']  = 10;
			$return['details'] = ''; //@i8n
		}
		$response = wp_remote_get( $url, array( 'redirection' => 0 ) );

		// We can access the API endpoint
		if ( ! is_wp_error( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
			if ( 200 === $response['response']['code'] ) {
				// Status code OK - means API is accessible
				$return['status'] = 0;
			} else {
				// If not 200, then it is blocked, yeah!
				$return['status'] = 10;
			}
		} else {
			$errormessage = $response->get_error_message();
			// We cannot access the API url - some error - send warning
			$return['status']  = 5;
			$return['details'] = $errormessage; //@i8n
		}
		return $return;
	}

	/**
	 * Scan for dangerous files in root
	 *
	 * @return array results
	 */
	public static function dangerous_files() {
		// @todo - fix case Insensitive files - glob no fun
		// @todo - maybe run in subfolders also? worth it?

		$return = array();

		$dangerous_files = array(
			'wp-config.php.old'      => 'Common name for config file backup - can contain critical information',
			'wp-config.php_bak'      => 'Common name for config file backup - can contain critical information',
			'wp-config.php~'         => 'Common name for config file backup - can contain critical information',
			'wp-config.php-'         => 'Common name for config file backup - can contain critical information',
			'wp-config.php--'        => 'Common name for config file backup - can contain critical information',
			'wp-config.php---'       => 'Common name for config file backup - can contain critical information',
			'wp-config.php.bkp'      => 'Common name for config file backup - can contain critical information',
			'wp-config.php_revision' => 'Common name for config file backup - can contain critical information',
			'php_errorlog'           => 'Can contain server details or errors that can be exploited.',
			'php_mail.log'           => 'Can contain user details or errors that can be exploited.',
			'.htaccess.sg'           => '.htaccess backup files on SiteGround - Can show server details or configurations that should not be public.',
			'.htaccess_swift_backup' => '.htaccess backup file by Swift Performance - Can show server details or configurations that should not be public.',
			'*.sql'                  => '.sql files should not be kept on your server - they may contain sensitive data.',
			'phpinfo.php'            => 'Displays all details about PHP on your website, should only exist briefly during development.',
			'info.php'               => 'Should only exist briefly during development and not on a live site.',
			'test.php'               => 'Should only exist briefly during development and not on a live site.',
			'*.bak'                  => 'Making a copy before making any changes to a file is a good idea, but remember to delete them once you are done.',
		);

		$return['status']  = 10;
		$return['details'] = '<dl>';

		foreach ( $dangerous_files as $key => $explanation ) {
			// If its a wildcard search
			if ( false !== strpos( $key, '*.' ) ) {
				$files = glob( ABSPATH . $key );
				if ( ( is_array( $files ) ) && ( count( $files ) > 0 ) ) {
					foreach ( $files as $f ) {
						$display_name       = str_replace( ABSPATH, '', $f );
						$return['details'] .= '<dt><strong>' . $display_name . '</strong></dt><dd>' . $explanation . '</dd>';

					}
					$return['status'] = 0;
				}
			} else {
				$check = file_exists( ABSPATH . $key );
				if ( $check ) {
					$return['details'] .= '<dt><strong>' . $key . '</strong></dt><dd>' . $explanation . '</dd>';
					$return['status']   = 0;
				}
			}
		}

		$return['details'] .= '</dl>';

		return $return;
	}


	/**
	 * Checks if admin is using SSL
	 *
	 * @return [type] [description]
	 */
	public static function admin_ssl() {
		$return = array();

		if ( ( ! defined( 'FORCE_SSL_ADMIN' ) || ! FORCE_SSL_ADMIN ) && ( false === stripos( get_home_url(), 'https' ) ) ) {
			$return['status'] = 10;
		} else {
			$return['status'] = 5;
		}

		return $return;
	} // admin_ssl


	// check if Timthumb is used
	public static function tim_thumb() {
		$return = array();
		$theme  = wp_get_theme();
		$theme  = $theme->Name . ' v' . $theme->Version; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$tmp    = self::tim_thumb_scan( get_template_directory() );

		$return['status'] = $tmp;
		$return['msg']    = $theme;

		return $return;
	} // tim_thumb


	// scan all PHP files and look for timtumb script
	public static function tim_thumb_scan( $path ) {
		$files = glob( $path . '/*.php' );
		$files = array_merge( $files, glob( $path . '/*/*.php' ) );

		foreach ( $files as $file ) {
			$content = file_get_contents( $file );
			if ( false !== $content ) {
				if ( false !== stristr( $content, 'TimThumb script created by Tim McDaniels' ) ) {
					return 0;
				}
			} else {
				return 5;
			}
		}

		return 10;
	} // tim_thumb_scan


	// check if user with DB ID 1 exists
	public static function id1_user_check() {
		$return = array();

		$check = get_userdata( 1 );
		if ( $check ) {
			$return['status'] = 0;
			$return['msg']    = $check->user_login;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // id1_user_check


	// check if wp-config is present on the default location
	public static function config_location() {
		$return = array();

		$check = file_exists( ABSPATH . 'wp-config.php' );
		if ( $check ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // config_location


	// check if the WP MySQL user can connect from an external host
	public static function mysql_external() {
		$return = array();
		global $wpdb;

		$check = $wpdb->get_var( 'SELECT CURRENT_USER()' );
		if ( strpos( $check, '@%' ) !== false ) {
			$return['status'] = 0;
		} elseif ( strpos( $check, '@127.0.0.1' ) !== false || stripos( $check, '@localhost' ) !== false ) {
			$return['status'] = 10;
		} else {
			$return['status'] = 5;
			$return['msg']    = $check;
		}

		return $return;
	} // mysql_external


	// check if the WP MySQL user has too many permissions granted
	public static function mysql_permissions() {
		$return = array( 'status' => 10 );
		global $wpdb;

		$grants = $wpdb->get_results( 'SHOW GRANTS', ARRAY_N );
		foreach ( $grants as $grant ) {
			if ( false !== stripos( $grant[0], 'GRANT ALL PRIVILEGES' ) ) {
				$return['status'] = 0;
				break;
			}
		} // foreach

		return $return;
	} // mysql_permissions


	// check if WLW link ispresent in header
	public static function wlw_meta() {
		$return = array();

		$request = wp_remote_get(
			get_home_url(),
			array(
				'sslverify'   => false,
				'timeout'     => 25,
				'redirection' => 2,
			)
		);
		$html    = wp_remote_retrieve_body( $request );

		if ( $html ) {
			$return['status'] = 10;
			// extract content in <head> tags
			$start = strpos( $html, '<head' );
			$len   = strpos( $html, 'head>', $start + strlen( '<head' ) );
			$html  = substr( $html, $start, $len - $start + strlen( 'head>' ) );
			// find all link tags
			preg_match_all( '#<link([^>]*)>#si', $html, $matches );
			$meta_tags = $matches[0];

			foreach ( $meta_tags as $meta_tag ) {
				if ( false !== stripos( $meta_tag, 'wlwmanifest' ) ) {
					$return['status'] = 0;
					break;
				}
			}
		} else {
			// error
			$return['status'] = 5;
		}

		return $return;
	} // wlw_meta


	// check if RPC link ispresent in header
	public static function rpc_meta() {
		$return = array();

		$request = wp_remote_get(
			get_home_url(),
			array(
				'timeout'     => 25,
				'redirection' => 2,
			)
		);
		$html    = wp_remote_retrieve_body( $request );

		if ( $html ) {
			$return['status'] = 10;
			// extract content in <head> tags
			$start = strpos( $html, '<head' );
			$len   = strpos( $html, 'head>', $start + strlen( '<head' ) );
			$html  = substr( $html, $start, $len - $start + strlen( 'head>' ) );
			// find all link tags
			preg_match_all( '#<link([^>]*)>#si', $html, $matches );
			$meta_tags = $matches[0];

			foreach ( $meta_tags as $meta_tag ) {
				if ( false !== stripos( $meta_tag, 'EditURI' ) ) {
					$return['status'] = 0;
					break;
				}
			}
		} else {
			// error
			$return['status'] = 5;
		}

		return $return;
	} // rpc_meta


	// check if register_globals is off
	public static function register_globals_check() {
		$return  = array();
		$getval  = 'register';
		$getval .= '_globals';
		$check   = (bool) ini_get( $getval );
		if ( $check ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // register_globals_check


	// check if display_errors is off
	public static function display_errors_check() {
		$return = array();

		$check = (bool) ini_get( 'display_errors' );
		if ( $check ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // display_errors_check


	// is theme/plugin editor disabled?
	public static function file_editor() {
		$return = array();

		if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) {
			$return['status'] = 10;
		} else {
			$return['status'] = 0;
		}

		return $return;
	} // file_editor


	// check if expose_php is off
	public static function expose_php_check() {
		$return = array();

		$check = (bool) ini_get( 'expose_php' );
		if ( $check ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // expose_php_check


	// check if allow_url_include is off
	public static function allow_url_include_check() {
		$return = array();

		$check = (bool) ini_get( 'allow_url_include' );
		if ( $check ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // allow_url_include_check


	// check if safe mode is off
	public static function safe_mode_check() {
		$return    = array();
		$checkval  = 'safe';
		$checkval .= '_mode';
		$check     = (bool) ini_get( $checkval );
		if ( $check ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // safe_mode_check


	// check if anyone can register on the site
	public static function anyone_can_register() {
		$return = array();
		$test   = get_option( 'users_can_register' );

		if ( $test ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // anyone_can_register

	// check REST api is enabled
	public static function rest_api_links() {
		$return = array();

		$collected_prios = intval( has_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' ) ) + intval( has_action( 'wp_head', 'rest_output_link_wp_head' ) ) + intval(
			has_action( 'template_redirect', 'rest_output_link_header' )
		);

		if ( $collected_prios > 0 ) {
			$return['status'] = 5;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // ver_check



	// check WP version
	public static function ver_check() {
		$return = array();

		if ( ! function_exists( 'get_preferred_from_update_core' ) ) {
			include_once ABSPATH . 'wp-admin/includes/update.php';
		}

		// get version
		wp_version_check();
		$latest_core_update = get_preferred_from_update_core();

		if ( isset( $latest_core_update->response ) && ( 'upgrade' === $latest_core_update->response ) ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // ver_check



	// check if debug.log file is accessible.
	public static function debug_log_file_check() {
		$return = array();

		// @todo - check if wp_Debug set and enabled
		// @todo - check if you can access debug.log file
		// @todo - offer way to block access to the debug.log file

		$url      = trailingslashit( content_url() ) . 'debug.log';
		$response = wp_remote_get( $url, array( 'redirection' => 0 ) );

		if ( ! is_wp_error( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
		}

		if ( is_wp_error( $response ) ) {
			$return['status'] = 5;
		} elseif ( 200 === $response['response']['code'] ) {
			$return['status'] = 0;
		} elseif ( 404 === $response['response']['code'] ) {
			$return['status'] = 10;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // ver_check





	// core updates should be enabled onlz for minor updates
	public static function core_updates_check() {
		$return = array();

		if ( ( defined( 'AUTOMATIC_UPDATER_DISABLED' ) && AUTOMATIC_UPDATER_DISABLED )
			|| ( defined( 'WP_AUTO_UPDATE_CORE' ) && 'minor' !== WP_AUTO_UPDATE_CORE )
		) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // core_updates_check


	// check if certain username exists
	public static function user_exists( $username = 'admin' ) {
		$return = array();

		if ( username_exists( $username ) ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // user_exists


	// check if plugins are up to date
	public static function plugins_ver_check() {
		$return = array();

		//Get the current update info
		$current = get_site_transient( 'update_plugins' );

		if ( ! is_object( $current ) ) {
			$current = new stdClass();
		}

		set_site_transient( 'update_plugins', $current );

		// run the internal plugin update check
		wp_update_plugins();

		$current = get_site_transient( 'update_plugins' );

		if ( isset( $current->response ) && is_array( $current->response ) ) {
			$plugin_update_cnt = count( $current->response );
		} else {
			$plugin_update_cnt = 0;
		}

		if ( $plugin_update_cnt > 0 ) {
			$return['status'] = 0;
			$return['msg']    = count( $current->response );
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // plugins_vec_check


	// check if there are deactivated plugins
	public static function deactivated_plugins() {
		$return = array();

		$all_plugins    = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );

		if ( count( $all_plugins ) > count( $active_plugins ) ) {
			$return['status'] = 0;
			$return['msg']    = count( $all_plugins ) - count( $active_plugins );
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // deactivated_plugins


	// check if there are deactivated themes
	public static function deactivated_themes() {
		$return         = array();
		$all_themes     = wp_get_themes();
		$all_themes_org = $all_themes;
		unset( $all_themes['twentysixteen'], $all_themes['twentyseventeen'] );

		if ( ( count( $all_themes ) > 1 && ! is_child_theme() ) || count( $all_themes ) > 2 ) {
			$return['status'] = 0;
			if ( is_child_theme() ) {
				$return['msg'] = count( $all_themes_org ) - 2;
			} else {
				$return['msg'] = count( $all_themes_org ) - 1;
			}
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // deactivated_themes


	// check themes versions
	public static function themes_ver_check() {
		$return = array();

		$current = get_site_transient( 'update_themes' );

		if ( ! is_object( $current ) ) {
			$current = new stdClass();
		}

		set_site_transient( 'update_themes', $current );
		wp_update_themes();

		$current = get_site_transient( 'update_themes' );

		if ( isset( $current->response ) && is_array( $current->response ) ) {
			$theme_update_cnt = count( $current->response );
		} else {
			$theme_update_cnt = 0;
		}

		if ( $theme_update_cnt > 0 ) {
			$return['status'] = 0;
			$return['msg']    = count( $current->response );
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // themes_ver_check


	// check DB table prefix
	public static function db_table_prefix_check() {
		global $wpdb;
		$return = array();

		if ( 'wp_' === $wpdb->prefix || 'wordpress_' === $wpdb->prefix || 'wp3_' === $wpdb->prefix ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // db_table_prefix_check


	// check if global WP debugging is enabled
	public static function debug_check() {
		$return = array();

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // debug_check


	// check if global WP JS debugging is enabled
	public static function script_debug_check() {
		$return = array();

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // script_debug_check


	// check if DB debugging is enabled
	public static function db_debug_check() {
		global $wpdb;
		$return = array();

		if ( true === $wpdb->show_errors ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // db_debug_check


	// does readme.html exist?
	public static function readme_check() {
		$return   = array();
		$url      = get_bloginfo( 'wpurl' ) . '/readme.html?rnd=' . wp_rand();
		$response = wp_remote_get( $url, array( 'redirection' => 0 ) );

		if ( is_wp_error( $response ) ) {
			$return['status'] = 5;
		} elseif ( 200 === $response['response']['code'] ) {
			$return['status'] = 0;
		} elseif ( 404 === $response['response']['code'] ) {
			$return['status'] = 10;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // readme_check



	// does readme.html exist?
	public static function license_check() {
		$return   = array();
		$url      = get_bloginfo( 'wpurl' ) . '/license.txt?rnd=' . wp_rand();
		$response = wp_remote_get( $url, array( 'redirection' => 0 ) );

		if ( is_wp_error( $response ) ) {
			$return['status'] = 5;
		} elseif ( 200 === $response['response']['code'] ) {
			$return['status'] = 0;
		} elseif ( 404 === $response['response']['code'] ) {
			$return['status'] = 10;

		} else {
			$return['status'] = 10;
		}

		return $return;
	} // license_check




	// does WP install.php file exist?
	public static function install_file_check() {
		$return   = array();
		$url      = get_bloginfo( 'wpurl' ) . '/wp-admin/install.php?rnd=' . wp_rand();
		$response = wp_remote_get( $url, array( 'redirection' => 0 ) );

		if ( is_wp_error( $response ) ) {
			$return['status'] = 5;
		} elseif ( 200 === $response['response']['code'] ) {
			$return['status'] = 0;
		} elseif ( 404 === $response['response']['code'] ) {
			$return['status'] = 10;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // install_file_check


	// does WP upgrade.php file exist?
	public static function upgrade_file_check() {
		$return   = array();
		$url      = get_bloginfo( 'wpurl' ) . '/wp-admin/upgrade.php?rnd=' . wp_rand();
		$response = wp_remote_get( $url, array( 'redirection' => 0 ) );

		if ( is_wp_error( $response ) ) {
			$return['status'] = 5;
		} elseif ( 200 === $response['response']['code'] ) {
			$return['status'] = 0;
		} elseif ( 404 === $response['response']['code'] ) {
			$return['status'] = 10;

		} else {
			$return['status'] = 10;
		}

		return $return;
	} // upgrade_file_check


	/**
	 * Check if wp-config.php has the right chmod
	 * @return array Results
	 */
	public static function config_chmod() {
		$return = array();

		require_once ABSPATH . 'wp-admin/includes/file.php';

		WP_Filesystem();

		global $wp_filesystem;

		$testedfile = ABSPATH . 'wp-config.php';
		if ( file_exists( $testedfile ) ) {
			$mode = $wp_filesystem->getchmod( ABSPATH . 'wp-config.php' );
		} else {
			$testedfile = dirname( ABSPATH . '../wp-config.php' );
			$mode       = $wp_filesystem->getchmod( $testedfile );
		}

		$return['details'] = 'Tested file: ' . $testedfile;

		$good_modes = array( 400, 440 );

		if ( ! $mode ) {
			$return['status'] = 5;
		} elseif ( ! in_array( $mode, $good_modes, true ) ) {
			$return['status'] = 0;
			$return['msg']    = $mode;
		} else {
			$return['status'] = 10;
			$return['msg']    = $mode;
		}

		return $return;
	} // config_chmod


	// check for unnecessary information on failed login
	public static function check_failed_login_info() {
		$return = array();

		$params = array(
			'log' => 'sn-test_3453344355',
			'pwd' => 'sn-test_2344323335',
		);

		if ( ! class_exists( 'WP_Http' ) ) {
			include ABSPATH . WPINC . '/class-http.php';
		}

		$http     = new WP_Http();
		$response = (array) $http->request(
			get_bloginfo( 'wpurl' ) . '/wp-login.php',
			array(
				'method' => 'POST',
				'body'   => $params,
			)
		);
		// @TODO lars - does this work in different languages?

		if ( ( isset( $response['body'] ) ) && ( stripos( $response['body'], 'invalid username' ) !== false ) ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // check_failed_login_info


	// helper function
	public static function try_login( $username, $password ) {
		$user = apply_filters( 'authenticate', null, $username, $password );

		if ( isset( $user->ID ) && ! empty( $user->ID ) ) {
			return true;
		} else {
			return false;
		}
	} // try_login


	// bruteforce user login
	public static function bruteforce_login() {
		$return           = array();
		$max_users_attack = 5;
		$passwords        = file( WF_SN_PLUGIN_DIR . 'misc/brute-force-dictionary.txt', FILE_IGNORE_NEW_LINES );

		$bad_usernames = array();

		if ( ! $max_users_attack ) {
			$return['status'] = 5;
			return $return;
		}

		$users = get_users( array( 'role' => 'administrator' ) );
		if ( count( $users ) < $max_users_attack ) {
			$users = array_merge( $users, get_users( array( 'role' => 'editor' ) ) );
		}
		if ( count( $users ) < $max_users_attack ) {
			$users = array_merge( $users, get_users( array( 'role' => 'author' ) ) );
		}
		if ( count( $users ) < $max_users_attack ) {
			$users = array_merge( $users, get_users( array( 'role' => 'contributor' ) ) );
		}
		if ( count( $users ) < $max_users_attack ) {
			$users = array_merge( $users, get_users( array( 'role' => 'subscriber' ) ) );
		}

		$i = 0;
		foreach ( $users as $user ) {
			$i++;
			$passwords[] = $user->user_login;
			foreach ( $passwords as $password ) {

				if ( self::try_login( $user->user_login, $password ) ) {
					$bad_usernames[] = $user->user_login;
					break;
				}
			} // foreach $passwords

			if ( $i > $max_users_attack ) {
				break;
			}
		} // foreach $users

		if ( empty( $bad_usernames ) ) {
			$return['status'] = 10;
		} else {
			$return['status'] = 0;
			$return['msg']    = implode( ', ', $bad_usernames );
		}

		return $return;
	} // bruteforce_login



	/**
	 * Test for X-XSS-Protection in http header
	 *
	 * @return [type] [description]
	 */
	public static function xxss_protection() {
		$return = array();

		if ( ! class_exists( 'WP_Http' ) ) {
			include ABSPATH . WPINC . '/class-http.php';
		}

		$http     = new WP_Http();
		$response = (array) $http->request( home_url() );

		if ( isset( $response['headers']['x-xss-protection'] ) ) {
			$return['status']  = 10;
			$return['msg']     = 'Great, X-XSS-Protection has been set to ' . $response['headers']['x-xss-protection'];
			$return['details'] = '"' . $response['headers']['x-xss-protection'] . '"';
		} else {
			// x-xss-protection has not been set
			$return['status'] = 0;
		}
		return $return;
	}

	/**
	 * Test for Strict-Transport-Security in http header
	 *
	 * @return [type] [description]
	 */
	public static function strict_transport_security() {
		$return = array();

		if ( ! class_exists( 'WP_Http' ) ) {
			include ABSPATH . WPINC . '/class-http.php';
		}

		$http     = new WP_Http();
		$response = (array) $http->request( home_url() );

		if ( isset( $response['headers']['strict-transport-security'] ) ) {
			$return['status']  = 10;
			$return['msg']     = 'Great, Strict-Transport-Security has been set.';
			$return['details'] = '"' . $response['headers']['strict-transport-security'] . '"';
		} else {
			// x-xss-protection has not been set
			$return['status'] = 0;
		}
		return $return;
	}




	/**
	 * Test for Content Security Policy in http header
	 *
	 * @return [type] [description]
	 */
	public static function referrer_policy() {
		$return = array();

		if ( ! class_exists( 'WP_Http' ) ) {
			include ABSPATH . WPINC . '/class-http.php';
		}

		$http     = new WP_Http();
		$response = (array) $http->request( home_url() );

		if ( isset( $response['headers']['referrer-policy'] ) ) {
			$return['status']  = 10;
			$return['msg']     = 'Great, Referrer-Policy has been set.';
			$return['details'] = '"' . $response['headers']['referrer-policy'] . '"';
		} else {
			$return['status'] = 0;
		}

		return $return;
	}



	/**
	 * Test for Feature Policy in http header
	 *
	 * @return [type] [description]
	 */
	public static function feature_policy() {
		$return = array();

		if ( ! class_exists( 'WP_Http' ) ) {
			include ABSPATH . WPINC . '/class-http.php';
		}

		$http     = new WP_Http();
		$response = (array) $http->request( home_url() );

		if ( isset( $response['headers']['feature-policy'] ) ) {
			$return['status']  = 10;
			$return['msg']     = 'Great, Feature-Policy has been set.';
			$return['details'] = '"' . $response['headers']['feature-policy'] . '"';
		} else {
			$return['status'] = 0;
		}

		return $return;
	}



	/**
	 * Test for Content Security Policy in http header
	 *
	 * @return [type] [description]
	 */
	public static function content_security_policy() {
		$return = array();

		if ( ! class_exists( 'WP_Http' ) ) {
			include ABSPATH . WPINC . '/class-http.php';
		}

		$http     = new WP_Http();
		$response = (array) $http->request( home_url() );

		if ( isset( $response['headers']['content-security-policy'] ) ) {
			$return['status']  = 10;
			$return['msg']     = 'Great, Content Security Policy has been set.';
			$return['details'] = '"' . $response['headers']['content-security-policy'] . '"';
		} else {
			$return['status'] = 0;
		}

		// Test for report-only mode has been set
		if ( 0 === $return['status'] ) {
			if ( isset( $response['headers']['content-security-policy-report-only'] ) ) {
				$return['status'] = 5;
			}
		}

		return $return;
	}




	/**
	 * Test for X-Frame-Options in http header
	 *
	 * @return [type] [description]
	 */
	public static function x_frame_options() {
		$return = array();

		if ( ! class_exists( 'WP_Http' ) ) {
			include ABSPATH . WPINC . '/class-http.php';
		}

		$http     = new WP_Http();
		$response = (array) $http->request( home_url() );

		if ( isset( $response['headers']['x-frame-options'] ) ) {
			$return['status']  = 10;
			$return['details'] = '"' . $response['headers']['x-frame-options'] . '"';
		} else {
			$return['status'] = 0;
		}
		return $return;
	}





	/**
	 * Test for X-Content-Type-Options in http header
	 *
	 * @return [type] [description]
	 */
	public static function x_content_type_options() {
		$return = array();

		if ( ! class_exists( 'WP_Http' ) ) {
			include ABSPATH . WPINC . '/class-http.php';
		}

		$http     = new WP_Http();
		$response = (array) $http->request( home_url() );

		if ( isset( $response['headers']['x-content-type-options'] ) ) {
			$return['status']  = 10;
			$return['details'] = '"' . $response['headers']['x-content-type-options'] . '"';
		} else {
			$return['status'] = 0;
		}
		return $return;
	}










	// check if php headers contain php version
	public static function php_headers() {
		$return = array();

		if ( ! class_exists( 'WP_Http' ) ) {
			include ABSPATH . WPINC . '/class-http.php';
		}

		$http     = new WP_Http();
		$response = (array) $http->request( home_url() );

		if ( ( isset( $response['headers']['server'] ) && stripos( $response['headers']['server'], phpversion() ) !== false ) || ( isset( $response['headers']['x-powered-by'] ) && stripos( $response['headers']['x-powered-by'], phpversion() ) !== false ) ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
			$return['msg']    = self::$security_tests[ __FUNCTION__ ]['msg_ok'];
		}

		return $return;
	} // php_headers


	// check for WP version in meta tags
	public static function wp_header_meta() {
		$return = array();

		$request = wp_remote_get(
			get_home_url(),
			array(
				'sslverify'   => false,
				'timeout'     => 25,
				'redirection' => 2,
			)
		);
		$html    = wp_remote_retrieve_body( $request );

		if ( $html ) {
			$return['status'] = 10;
			// extract content in <head> tags
			$start = strpos( $html, '<head' );
			$len   = strpos( $html, 'head>', $start + strlen( '<head' ) );
			$html  = substr( $html, $start, $len - $start + strlen( 'head>' ) );
			// find all Meta Tags
			preg_match_all( '#<meta([^>]*)>#si', $html, $matches );
			$meta_tags = $matches[0];

			foreach ( $meta_tags as $meta_tag ) {
				if ( stripos( $meta_tag, 'generator' ) !== false
					&& stripos( $meta_tag, get_bloginfo( 'version' ) ) !== false
				) {
					$return['status'] = 0;
					break;
				}
			}
		} else {
			// error
			$return['status'] = 5;
		}

		return $return;
	} // wp_header_meta


	// compare WP Blog Url with WP Site Url
	public static function blog_site_url_check() {
		$return = array();

		$siteurl = home_url();
		$wpurl   = site_url();

		if ( $siteurl === $wpurl ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // blog_site_url_check


	// brute force attack on password
	public static function dictionary_attack( $password ) {
		$dictionary = file( WF_SN_PLUGIN_DIR . 'misc/brute-force-dictionary.txt', FILE_IGNORE_NEW_LINES );

		if ( in_array( $password, $dictionary, true ) ) {
			return true;
		} else {
			return false;
		}
	} // dictionary_attack


	// check database password
	public static function db_password_check() {
		$return   = array();
		$password = DB_PASSWORD;

		if ( empty( $password ) ) {
			$return['status'] = 0;
			$return['msg']    = 'password is empty';
		} elseif ( self::dictionary_attack( $password ) ) {
			$return['status'] = 0;
			$return['msg']    = 'password is a simple word from the dictionary';
		} elseif ( strlen( $password ) < 6 ) {
			$return['status'] = 0;
			$return['msg']    = 'password length is only ' . strlen( $password ) . ' chars';
		} elseif ( count( count_chars( $password, 1 ) ) < 5 ) {
			$return['status'] = 0;
			$return['msg']    = 'password is too simple';
		} else {
			$return['status'] = 10;
			$return['msg']    = 'password is ok';
		}

		return $return;
	} // db_password_check


	// unique config keys check
	public static function salt_keys_check() {
		$return = array();
		$ok     = true;
		$keys   = array(
			'AUTH_KEY',
			'SECURE_AUTH_KEY',
			'LOGGED_IN_KEY',
			'NONCE_KEY',
			'AUTH_SALT',
			'SECURE_AUTH_SALT',
			'LOGGED_IN_SALT',
			'NONCE_SALT',
		);

		foreach ( $keys as $key ) {

			if ( defined( $key ) ) {
				$constant = constant( $key );
			}
			if ( 'put your unique phrase here' === trim( $constant ) || strlen( $constant ) < 50 ) {
				$bad_keys[] = $key;
				$ok         = false;
			}
		} // foreach

		if ( true === $ok ) {
			$return['status'] = 10;
		} else {
			$return['status'] = 0;
			$return['msg']    = implode( ', ', $bad_keys );
		}

		return $return;
	} // salt_keys_check


	// check if wp-config.php has the right chmod
	public static function salt_keys_age_check() {
		$return = array();
		$age    = 0;

		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
			$age = filemtime( ABSPATH . 'wp-config.php' );
		} else {
			$age = filemtime( ABSPATH . '../wp-config.php' );
		}

		if ( empty( $age ) ) {
			$return['status'] = 5;
		} else {
			$diff = time() - $age;
			if ( $diff > DAY_IN_SECONDS * 93 ) {
				$return['status'] = 0;
			} else {
				$return['status'] = 10;
			}
		}

		return $return;
	} // salt_key_age_check


	public static function uploads_browsable() {
		$return     = array();
		$upload_dir = wp_upload_dir();

		$args     = array(
			'method'      => 'GET',
			'timeout'     => 5,
			'redirection' => 0,
			'sslverify'   => false,
			'httpversion' => 1.0,
			'blocking'    => true,
			'headers'     => array(),
			'body'        => null,
			'cookies'     => array(),
		);
		$response = wp_remote_get( rtrim( $upload_dir['baseurl'], '/' ) . '/?nocache=' . wp_rand(), $args );

		if ( is_wp_error( $response ) ) {
			$return['status'] = 5;
			$return['msg']    = $upload_dir['baseurl'] . '/';
		} elseif ( '200' === $response['response']['code'] && false !== stripos( $response['body'], 'index' ) ) {
			$return['status'] = 0;
			$return['msg']    = $upload_dir['baseurl'] . '/';
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // uploads browsable


	public static function shellshock_6271() {
		$return = array();
		$pipes  = array();

		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
			$return['status'] = 10;
			return $return;
		}

		$env = array( 'SHELL_SHOCK_TEST' => '() { :;}; echo VULNERABLE' );

		$desc = array(
			0 => array( 'pipe', 'r' ),
			1 => array( 'pipe', 'w' ),
			2 => array( 'pipe', 'w' ),
		);

		$p = @proc_open( 'bash -c "echo Test"', $desc, $pipes, null, $env );
		if ( ! $pipes ) {
			$return['status'] = 5;
			return $return;
		}
		$output = stream_get_contents( $pipes[1] );
		proc_close( $p );

		if ( strpos( $output, 'VULNERABLE' ) === false ) {
			$return['status'] = 10;
			return $return;
		}

		$return['status'] = 0;
		return $return;
	} // shellshock_6271


	public static function shellshock_7169() {
		$return = array();
		$pipes  = array();

		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
			$return['status'] = 10;
			return $return;
		}

		$desc = array(
			0 => array( 'pipe', 'r' ),
			1 => array( 'pipe', 'w' ),
			2 => array( 'pipe', 'w' ),
		);

		$p = @proc_open( "rm -f echo; env 'x=() { (a)=>\' bash -c \"echo date +%Y\"; cat echo", $desc, $pipes, sys_get_temp_dir() );
		if ( ! $pipes ) {
			$return['status'] = 5;
			return $return;
		}
		$output = stream_get_contents( $pipes[1] );
		proc_close( $p );

		$test = gmdate( 'Y' );

		if ( trim( $output ) === $test ) {
			$return['status'] = 0;
			return $return;
		}

		$return['status'] = 10;
		return $return;
	} // shellshock_7169


	// check if any active plugin hasn't been updated in last 365 days
	// Note: This function stores details about plugins and stores it in an option for later use in incompatible_plugins() - This test needs to be run before incompatible_plugins().
	public static function old_plugins() {

		$return               = array();
		$good                 = array();
		$bad                  = array();
		$wf_sn_active_plugins = array();
		$active_plugins       = get_option( 'active_plugins', array() );

		foreach ( $active_plugins as $plugin_path ) {
			$plugin = explode( '/', $plugin_path );

			if ( empty( $plugin ) || empty( $plugin_path ) ) {
				continue;
			}
			if ( isset( $plugin[0] ) ) {
				$plugin = $plugin[0];
			}

			$response = wp_remote_get( 'https://api.wordpress.org/plugins/info/1.1/?action=plugin_information&request%5Bslug%5D=' . $plugin, array( 'timeout' => 5 ) );
			if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 && wp_remote_retrieve_body( $response ) ) {
				$details = wp_remote_retrieve_body( $response );
				$details = json_decode( $details, true );
				if ( empty( $details ) ) {
					// No details detected
					continue;
				}
				$wf_sn_active_plugins[ $plugin_path ] = $details;
				$updated                              = strtotime( $details['last_updated'] );
				if ( $updated + 365 * DAY_IN_SECONDS < time() ) {
					$bad[ $plugin_path ] = true;
				} else {
					$good[ $plugin_path ] = true;
				}
			}
		} // foreach active plugin
		update_option( WF_SN_ACTIVE_PLUGINS, $wf_sn_active_plugins );

		if ( empty( $bad ) && empty( $good ) ) {
			$return['status'] = 5;
		} elseif ( empty( $bad ) ) {
			$return['status'] = 10;
		} else {
			$plugins = get_plugins();
			foreach ( $bad as $plugin_path => $tmp ) {
				$bad[ $plugin_path ] = $plugins[ $plugin_path ]['Name'];
			}
			$return['msg']    = implode( ', ', $bad );
			$return['status'] = 0;
		}
		return $return;
	} // old_plugins


	// check if any active plugins are not compatible with current ver of WP
	public static function incompatible_plugins() {
		global $wp_version;
		$wf_sn_active_plugins = get_option( WF_SN_ACTIVE_PLUGINS, false );

		$return            = array();
		$return['details'] = '';
		$good              = array();
		$bad               = array();

		if ( empty( $wf_sn_active_plugins ) ) {
			// No active plugins stored from the old_plugins() test
			return array( 'status' => 0 );
		}

		$all_plugins = get_plugins();

		foreach ( $wf_sn_active_plugins as $plugin_path => $plugin ) {

			if ( version_compare( $wp_version, $plugin['tested'], '>' ) ) {
				$bad[ $plugin_path ] = $plugin;
			} else {
				$good[ $plugin_path ] = $plugin;
			}
		} // foreach active plugins we have details on

		if ( empty( $bad ) ) {
			$return['status'] = 10;
		} else {
			$plugins = get_plugins();
			foreach ( $bad as $plugin_path => $tmp ) {
				$bad[ $plugin_path ] = $plugins[ $plugin_path ]['Name'];
				if ( '' !== $return['details'] ) {
					// add comma if not empty
					$return['details'] .= ', ';
				}
				$return['details'] .= $plugins[ $plugin_path ]['Name'] . ' tested up to ' . $wf_sn_active_plugins[ $plugin_path ]['tested'];

			}
			$return['msg']    = implode( ', ', $bad );
			$return['status'] = 0;
		}
		return $return;
	} // incompatible_plugins


	// check if PHP is up-to-date
	public static function php_ver() {
		$return = array( 'msg' => PHP_VERSION );

		if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
			$return['status'] = 0;
		} elseif ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
			$return['status'] = 5;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // php_ver


	// check if mysql is up-to-date
	public static function mysql_ver() {
		global $wpdb;

		$mysql_version = $wpdb->get_var( 'SELECT VERSION()' );

		$return = array(
			'msg' => $mysql_version,
		);

		if ( version_compare( $mysql_version, '5.0', '<' ) ) {
			$return['status'] = 0;
		} elseif ( version_compare( $mysql_version, '5.6', '<' ) ) {
			$return['status'] = 5;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // mysql_ver

	/**
	 * Try getting usernames from user IDs
	 *
	 * @return array result
	 */
	public static function usernames_enumeration() {
		$users   = get_users( 'number=10' );
		$success = false;
		$url     = home_url() . '/?author=';

		foreach ( $users as $user ) {
			$response      = wp_remote_get( $url . $user->ID, array( 'redirection' => 0 ) );
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 301 === $response_code ) {
				$success = true;
				break;
			}
		} // foreach

		if ( $success ) {
			$return['status'] = 0;
		} else {
			$return['status'] = 10;
		}

		return $return;
	} // usernames_enumeration
} // class Wf_Sn_Tests
