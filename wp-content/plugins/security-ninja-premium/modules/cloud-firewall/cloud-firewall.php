<?php
/*
 * Security Ninja PRO
 * (c) 2018. Web factory Ltd
 */
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'WF_SN_CF_BLOCKED_COUNT', 'wf_sn_cf_blocked_count' ); // @todo
define( 'WF_SN_CF_OPTIONS_KEY', 'wf_sn_cf' );
define( 'WF_SN_CF_IPS_KEY', 'wf_sn_cf_ips' );
define( 'WF_SN_CF_LOG_TABLE', 'wf_sn_cf_vl' ); // vl for visitor log - sneaky, eh? :-)

class wf_sn_cf {
	static $options;

	public static function init() {
		self::$options = self::get_options();

		// update geolocation database via SN_Geolocation in class-sn-geolocation.php
		add_action( 'secnin_update_geoip', array( 'SN_Geolocation', 'update_database' ) );

		add_action( 'secnin_update_cloud_firewall', array( __CLASS__, 'update_cloud_ips' ) );

		add_action( 'do_action_secnin_prune_visitor_log', array( __CLASS__, 'prune_visitor_log' ) );

		add_action( 'init', array( __CLASS__, 'schedule_cron_jobs' ) );

		// setup_theme seems to be earliest hook - because of Freemius API me thinks, but not sure - Future - add as mu-plugin
		add_action( 'setup_theme', array( __CLASS__, 'check_visitor' ), 1 );

		add_action( 'login_init', array( __CLASS__, 'form_init_check' ) );
		add_filter( 'authenticate', array( __CLASS__, 'login_filter' ), 10, 3 );
		add_filter( 'login_message', array( __CLASS__, 'login_message' ) );
		add_action( 'wp_login_failed', array( __CLASS__, 'failed_login' ) );

		if ( is_admin() ) {
			// add tab to Security Ninja tabs
			add_filter( 'sn_tabs', array( __CLASS__, 'sn_tabs' ) );

			// enqueue scripts
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

			add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

			// add custom text for GUI overlay
			add_action( 'sn_overlay_content', array( __CLASS__, 'overlay_content' ) );

			// register ajax endpoints
			add_action( 'wp_ajax_sn_enable_firewall', array( __CLASS__, 'ajax_enable_firewall' ) );

			add_action( 'wp_ajax_sn_clear_blacklist', array( __CLASS__, 'ajax_clear_blacklist' ) );

			add_action( 'wp_ajax_sn_send_unblock_email', array( __CLASS__, 'ajax_send_unblock_email' ) );

			// Tests the parsed IP from Firewall tab
			add_action( 'wp_ajax_sn_test_ip', array( __CLASS__, 'ajax_test_ip' ) );
		}
	}


	public static function check_visitor() {

		$whitelisted_user = false;
		$administrator    = false;
		$current_user     = SN_Geolocation::geolocate_ip( '', true, true );

		if ( current_user_can( 'manage_options' ) ) {
			/* A user with admin privileges */
			$administrator = true;
		}

		// Prevents user from being blocked even from a blocked country if IP is whitelisted
		if ( in_array( $current_user['ip'], self::$options['whitelist'], true ) ) {
			$whitelisted_user = true;
		}

		// Adds the IP from where the admin is logged in
		if ( $administrator && ( 1 === (int) self::$options['active'] ) ) {
			if ( ! in_array( $current_user['ip'], self::$options['whitelist'], true ) ) {
				$whitelisted_user             = true;
				self::$options['whitelist'][] = $current_user['ip'];
				update_option( WF_SN_CF_OPTIONS_KEY, self::$options );
			}
		}

		// adding IP to whitelist
		if ( ! $administrator && ( 1 === (int) self::$options['active'] ) ) {
			// Checks if we are trying to unblock a new IP.
			if ( isset( $_REQUEST['snf'] ) && $_REQUEST['snf'] === self::$options['unblock_url'] ) {
				wf_sn_el_modules::log_event( 'security_ninja', 'unblocked_ip', __( 'New IP added to the whitelist using the secret access URL.', 'security-ninja' ), '' );
				if ( ! in_array( $current_user['ip'], self::$options['whitelist'], true ) ) {
					$whitelisted_user             = true;
					self::$options['whitelist'][] = $current_user['ip'];
					update_option( WF_SN_CF_OPTIONS_KEY, self::$options );
				}
			}
			// Check IP against blacklist
			$blacklist = self::$options['blacklist'];

			if ( ( ! $whitelisted_user ) && ( is_array( $blacklist ) ) ) {
				foreach ( $blacklist as $bl ) {
					if ( trim( $bl ) === $current_user['ip'] ) {
						wf_sn_el_modules::log_event( 'security_ninja', 'blacklisted_IP', $current_user['ip'] . ' IP is blacklisted.', '' );
						self::update_blocked_count( $current_user['ip'] );
						self::kill_request();
					}
				}
			}

			// Check if an IP is banned and blocks
			if ( ( ! $whitelisted_user ) && ( 1 === (int) self::$options['active'] ) ) {
				if ( self::is_banned_ip( $current_user['ip'] ) ) {
					// @todo add option to tack
					self::log_visitor( $current_user['ip'], $_SERVER['HTTP_USER_AGENT'], $current_user['country'], true, 'IP banned' );
					$extraarr = array();
					if ( wf_sn_el::syslogactive() ) {
						$extraarr['ip']         = $current_user['ip'];
						$extraarr['country']    = $current_user['country'];
						$extraarr['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
					}
					wf_sn_el_modules::log_event( 'security_ninja', 'blocked_ip_banned', 'IP is blocked.', $extraarr ); // @todo log med @i8n
					self::update_blocked_count( $current_user['ip'] );
					self::kill_request();
				}
			}

			// FILTER BAD QUERIES
			if ( ( ! $whitelisted_user ) && ( 1 === (int) self::$options['active'] ) ) {
				$bad_query = self::check_bad_queries();

				if ( $bad_query ) {
					$extramessage = '';

					$extraarr = array();
					if ( isset( $bad_query['request_uri'] ) ) {
						$extramessage       .= ' (Request URI) "' . sanitize_text_field( $bad_query['request_uri_string'] ) . '" ' . sanitize_text_field( $bad_query['request_uri'] );
						$extraarr['reason']  = 'request_uri';
						$extraarr['details'] = sanitize_text_field( $bad_query['request_uri'] );
					}

					if ( isset( $bad_query['query_string'] ) ) {
						$extramessage .= ' (Query string) "' . sanitize_text_field( $bad_query['query_string_string'] ) . '" ' . sanitize_text_field( $bad_query['query_string'] );

						$extraarr['reason']  = 'query_string';
						$extraarr['details'] = sanitize_text_field( $bad_query['query_string'] );
					}

					if ( isset( $bad_query['http_user_agent'] ) ) {
						$extraarr['reason']  = 'http_user_agent';
						$extraarr['details'] = sanitize_text_field( $bad_query['http_user_agent'] );
						$extramessage       .= ' (User Agent) "' . sanitize_text_field( $bad_query['user_agent_string'] ) . '" ' . sanitize_text_field( $bad_query['http_user_agent'] );
					}

					self::log_visitor( $current_user['ip'], $_SERVER['HTTP_USER_AGENT'], $current_user['country'], true, wp_json_encode( $bad_query ), __( 'Suspicious page request', 'security-ninja' ) . ' ' . $extramessage );

					if ( wf_sn_el::syslogactive() ) {
						$extraarr['ip']         = $current_user['ip'];
						$extraarr['country']    = $current_user['country'];
						$extraarr['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
					}

					$blockedmessage = 'Suspicious Request';
					if ( isset( $extramessage ) ) {
						$blockedmessage .= ' ' . $extramessage;
					}
					wf_sn_el_modules::log_event( 'security_ninja', 'blocked_ip_suspicious_request', $blockedmessage, $extraarr );
					self::update_blocked_count( $current_user['ip'] );
					self::kill_request();
				}
			}
			// checking if user is from blocked country
			if ( ( ! $whitelisted_user ) && ( $current_user['country'] === self::is_banned_country( $current_user['ip'] ) ) ) {

				self::log_visitor( $current_user['ip'], $_SERVER['HTTP_USER_AGENT'], $current_user['country'], true, __( 'Country banned', 'security-ninja' ) );

				wf_sn_el_modules::log_event( 'security_ninja', 'blocked_ip_country_ban', $current_user['country'] . ' is blocked.', '' );

				if ( wf_sn_el::syslogactive() ) {
					$upload_dir          = wp_upload_dir();
					$secninja_upload_dir = $upload_dir['basedir'] . '/security-ninja/logs/';
					$log                 = new Logger( 'Security Ninja' );
					$handler             = new \Monolog\Handler\RotatingFileHandler( $secninja_upload_dir . 'security-ninja.log', 7, Monolog\Logger::DEBUG );
					$handler->setFilenameFormat( '{date}-{filename}', 'Y-m-d' );
					$log->pushHandler( $handler );

					$log->warning(
						'Blocked Visitor (Country Blocked)',
						array(
							'ip'         => $current_user['ip'],
							'country'    => $current_user['country'],
							'user_agent' => $bad_query['user_agent_string'],
						)
					);
				}

				self::update_blocked_count( $current_user['ip'] );
				self::kill_request();
			}
		} // not admin and active

		self::log_visitor( $current_user['ip'], $_SERVER['HTTP_USER_AGENT'], $current_user['country'], false );
	}




	/**
	 * Checks for bad queries - CBQ - Taken without shame from BBQ - thank you for the superfast firewall
	 * @return null
	 */
	public static function check_bad_queries() {

		$request_uri_array  = apply_filters( 'request_uri_items', array( '@eval', 'eval\(', 'UNION(.*)SELECT', '\(null\)', 'base64_', '\/localhost', '\%2Flocalhost', '\/pingserver', 'wp-config\.php', '\/config\.', '\/wwwroot', '\/makefile', 'crossdomain\.', 'proc\/self\/environ', 'usr\/bin\/perl', 'var\/lib\/php', 'etc\/passwd', '\/https\:', '\/http\:', '\/ftp\:', '\/file\:', '\/php\:', '\/cgi\/', '\.cgi', '\.cmd', '\.bat', '\.exe', '\.sql', '\.ini', '\.dll', '\.htacc', '\.htpas', '\.pass', '\.asp', '\.jsp', '\.bash', '\/\.git', '\/\.svn', ' ', '\<', '\>', '\/\=', '\.\.\.', '\+\+\+', '@@', '\/&&', '\/Nt\.', '\;Nt\.', '\=Nt\.', '\,Nt\.', '\.exec\(', '\)\.html\(', '\{x\.html\(', '\(function\(', '\.php\([0-9]+\)', '(benchmark|sleep)(\s|%20)*\(', 'indoxploi', 'xrumer' ) );
		$query_string_array = apply_filters(
			'query_string_items',
			array(
				'@@',
				'\(0x',
				'0x3c62723e',
				'\;\!--\=',
				'\(\)\}',
				'\:\;\}\;',
				'\.\.\/',
				'127\.0\.0\.1',
				'UNION(.*)SELECT',
				'@eval',
				'eval\(',
				'base64_',
				'localhost',
				'loopback',
				'\%0A',
				'\%0D',
				'\%00',
				'\%2e\%2e',
				'allow_url_include',
				'auto_prepend_file',
				'disable_functions',
				'input_file',
				'execute',
				'file_get_contents',
				'mosconfig',
				'open_basedir',
				'(benchmark|sleep)(\s|%20)*\(',
				'phpinfo\(',
				'shell_exec\(',
				'\/wwwroot',
				'\/makefile',
				'path\=\.',
				'mod\=\.',
				'wp-config\.php',
				'\/config\.',
				'\$_session',
				'\$_request',
				'\$_env',
				'\$_server',
				'\$_post',
				'\$_get',
				'indoxploi',
				'xrumer',
				'%3Cscript', // <script

			)
		);
		$user_agent_array = apply_filters( 'user_agent_items', array( 'acapbot', '\/bin\/bash', 'binlar', 'casper', 'cmswor', 'diavol', 'dotbot', 'finder', 'flicky', 'md5sum', 'morfeus', 'nutch', 'planet', 'purebot', 'pycurl', 'semalt', 'shellshock', 'skygrid', 'snoopy', 'sucker', 'turnit', 'vikspi', 'zmeu' ) );

		$request_uri_string  = false;
		$query_string_string = false;
		$user_agent_string   = false;

		if ( isset( $_SERVER['REQUEST_URI'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri_string = $_SERVER['REQUEST_URI'];
		}

		if ( isset( $_SERVER['QUERY_STRING'] ) && ! empty( $_SERVER['QUERY_STRING'] ) ) {
			$query_string_string = $_SERVER['QUERY_STRING'];
		}

		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$user_agent_string = $_SERVER['HTTP_USER_AGENT'];
		}

		if ( $request_uri_string || $query_string_string || $user_agent_string ) {

			$response = array();

			preg_match( '/' . implode( '|', $request_uri_array ) . '/i', $request_uri_string, $req_matches );
			if ( $req_matches ) {
				$response['request_uri']        = $req_matches[0];
				$response['request_uri_string'] = $request_uri_string;
			}

			preg_match( '/' . implode( '|', $query_string_array ) . '/i', $query_string_string, $query_matches );
			if ( $query_matches ) {
				$response['query_string']        = $query_matches[0];
				$response['query_string_string'] = $query_string_string;
			}

			preg_match( '/' . implode( '|', $user_agent_array ) . '/i', $user_agent_string, $ua_matches );
			if ( $ua_matches ) {
				$response['http_user_agent']   = $ua_matches[0];
				$response['user_agent_string'] = $user_agent_string;
			}
			return $response;

		}
		return false;
	}





	/** Terminate current request - Checks if option is set to redirect to an URL first */
	public static function kill_request() {

		$redirect_url = esc_url_raw( self::$options['redirect_url'] );

		if ( ( isset( $redirect_url ) ) && ( wp_http_validate_url( $redirect_url ) ) ) {
			wp_redirect( $redirect_url, 301, 'WP Security Ninja' );
			exit;
		}

		wp_die(
			self::$options['message'],
			'Blocked',
			array(
				'response' => 403,
			)
		);
	}





	/**
	 * Updates global blocked visits count
	 * @param  string $ip IP that was blocked - NOT IN USE YET
	 * @return void
	 */
	public static function update_blocked_count( $ip ) {
		// @todo - store block count per IP somewhere
		$blocked_count = get_option( WF_SN_CF_BLOCKED_COUNT );
		if ( $blocked_count ) {
			$blocked_count++;
		} else {
			$blocked_count = 1;
		}
		update_option( WF_SN_CF_BLOCKED_COUNT, $blocked_count );
	}





	/**
	 * [log_visitor description]
	 * @param  [type]  $ip          [description]
	 * @param  [type]  $user_agent  [description]
	 * @param  [type]  $country     [description]
	 * @param  boolean $banned      [description]
	 * @param  string  $description [description]
	 * @return [type]               [description]
	 */
	public static function log_visitor( $ip, $user_agent, $country, $banned = false, $description = '', $ban_reason = '' ) {
		global $wpdb;
		global $wp_query;

		$new_id = $wpdb->insert(
			$wpdb->prefix . WF_SN_CF_LOG_TABLE,
			array(
				'timestamp'   => current_time( 'mysql' ),
				'ip'          => sanitize_text_field( $ip ),
				'user_agent'  => sanitize_text_field( $user_agent ),
				'country'     => sanitize_text_field( $country ),
				'banned'      => intval( $banned ),
				'ban_reason'  => sanitize_text_field( $ban_reason ),
				'description' => sanitize_text_field( $description ),
				'URL'         => esc_url_raw( $_SERVER['REQUEST_URI'] ),
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		return $wpdb->insert_id;
	} // log_event


	// prune events log table
	public static function prune_visitor_log( $force = false ) {
		global $wpdb;

		$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . WF_SN_CF_LOG_TABLE . ' WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY);' );
		wf_sn_el_modules::log_event( 'security_ninja', 'pruned_visitor_log', __( 'Pruned visitors log - 30 days', 'security-ninja' ), '' );
		return true;
	} // prune_log


	// activate plugin
	public static function activate() {

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name = $wpdb->prefix . WF_SN_CF_LOG_TABLE;
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name );
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
			$sql = 'CREATE TABLE IF NOT EXISTS ' . $table_name . " (
  		`id` bigint(32) unsigned NOT NULL AUTO_INCREMENT,
  		`timestamp` datetime NOT NULL,
  		`ip` varchar(39) NOT NULL,
  		`user_agent` varchar(255) NOT NULL,
  		`action` varchar(64) NOT NULL,
  		`description` text NOT NULL,
  		`raw_data` blob NOT NULL,
  		`country` varchar(2) NOT NULL DEFAULT '',
  		`banned` tinyint(1) NOT NULL DEFAULT '0',
  		`ban_reason` varchar(64) NOT NULL,
  		`URL` text,
  		PRIMARY KEY  (`id`)
  	) ENGINE=MyISAM DEFAULT CHARSET=utf8";
			dbDelta( $sql );
		}
		// Download first time the IP list or update
		self::update_cloud_ips();

		SN_Geolocation::update_database(); // updates the country database when turning on firewall + via cron afterwards.
	}

	// clean-up when deactivated
	public static function deactivate() {
		// global $wpdb;
	}


	/**
	 * Schedule cron jobs
	 * @return null
	 */
	public static function schedule_cron_jobs() {

		// Update GEOIP database - once a month
		if ( ! wp_next_scheduled( 'secnin_update_geoip' ) ) {
			wp_schedule_event( time(), 'monthly', 'secnin_update_geoip' );
		}

		// Update cloud IPs
		if ( ! wp_next_scheduled( 'secnin_update_cloud_firewall' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'secnin_update_cloud_firewall' );
		}
		// Prune visitor log - runs twice a day
		if ( ! wp_next_scheduled( 'do_action_secnin_prune_visitor_log' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'do_action_secnin_prune_visitor_log' );
		}
	}


	/**
	 * Check if IP is from banned country
	 * @param  string   $ip     User IP - optionals
	 * @return boolean/mixed    Returns country code if blocked and false if not blocked
	 */
	public static function is_banned_country( $ip = false ) {

		if ( $ip ) {
			$current_user_ip = $ip;
		} else {
			$current_user_ip = SN_Geolocation::geolocate_ip( '', true );
		}

		$blocked_countries = self::get_blocked_countries();

		$geolocate_ip = SN_Geolocation::geolocate_ip( $current_user_ip, true );

		if ( isset( $geolocate_ip['country'] ) ) {
			if ( in_array( $geolocate_ip['country'], $blocked_countries ) ) {
				return $geolocate_ip['country'];
			}
		}
		return false;
	}



	/**
	 * Enqueues JS and CSS needed for Firewall tab
	 * @return null
	 */
	public static function enqueue_scripts() {
		wp_enqueue_style( 'select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css', array(), Wf_Sn::$version );
		wp_enqueue_script( 'select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.min.js', array( 'jquery' ), Wf_Sn::$version );

		wp_enqueue_style( 'sn-cf-css', WF_SN_PLUGIN_URL . 'modules/cloud-firewall/css/wf-sn-cf-min.css', array(), Wf_Sn::$version );

		wp_register_script( 'sn-cf-js', WF_SN_PLUGIN_URL . 'modules/cloud-firewall/js/wf-sn-cf-min.js', array( 'select2' ), wf_sn::$version, true );

		$js_vars = array(
			'nonce' => wp_create_nonce( 'wf_sn_cf' ),
		);

		wp_localize_script( 'sn-cf-js', 'wf_sn_cf', $js_vars );

		wp_enqueue_script( 'sn-cf-js' );
	}


	/**
	 * Return firewall options
	 * @return array Contains firewall options.
	 */
	public static function get_options() {
		$defaults = array(
			'active'                  => '0',
			'global'                  => '1',
			'filterqueries'           => '1',
			'trackvisits'             => '1',
			'usecloud'                => '1', // use the cloud IP list
			'blocked_countries'       => array(), // lars blocked ips
			'blacklist'               => array(),
			'whitelist'               => array( self::get_user_ip() ),
			'banned_ips'              => array(),
			'max_login_attempts'      => 5,
			'max_login_attempts_time' => 5,
			'bruteforce_ban_time'     => 120,
			'login_msg'               => __( 'Warning: Multiple failed login attempts will get you banned.', 'security-ninja' ),
			'message'                 => __( 'You are not allowed to visit this website.', 'security-ninja' ),
			'redirect_url'            => '',
		);
		$options  = get_option( WF_SN_CF_OPTIONS_KEY, array() );

		if ( is_array( $options ) ) {
			$options = array_merge( $defaults, $options );
		} else {
			$options = $defaults;

		}
		return $options;
	} // get_options




	public static function ajax_enable_firewall() {

		check_ajax_referer( 'wf_sn_cf' );

		self::$options['active'] = 1;
		update_option( WF_SN_CF_OPTIONS_KEY, self::$options );
		SN_Geolocation::update_database(); // updates the geoip when turning on firewall + via cron afterwards.

		echo '1';
		die();
	} // ajax_enable_firewall


	public static function ajax_test_ip() {

		check_ajax_referer( 'wf_sn_cf' );

		$ip = trim( @$_GET['ip'] );
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			wp_send_json_success( __( 'Please enter a valid IP address to test.', 'security-ninja' ) );
		}

		// @todo - test if IP is from blocked country
		if ( self::is_banned_ip( $ip ) ) {
			wp_send_json_success( 'IP ' . $ip . ' is banned.' ); //@i8n
		} else {
			wp_send_json_success( 'IP ' . $ip . ' is NOT banned.' ); //@i8n
		}
	} // ajax_enable_firewall

	// https://stackoverflow.com/a/18560043/452515
	public static function url_to_domain( $url ) {
		return implode( array_slice( explode( '/', preg_replace( '/https?:\/\/(www\.)?/', '', $url ) ), 0, 1 ) );
	}

	public static function ajax_clear_blacklist() {
		check_ajax_referer( 'wf_sn_cf' );

		self::$options['banned_ips'] = array();
		update_option( WF_SN_CF_OPTIONS_KEY, self::$options );

		echo '1';
		die();
	} // ajax_clear_blacklist


	public static function ajax_send_unblock_email() {
		check_ajax_referer( 'wf_sn_cf' );

		if ( ! isset( $_GET['email'] ) ) {
			echo '0';
			die();
		}

		$sanitized_email = sanitize_email( $_GET['email'] );

		if ( false === is_email( $sanitized_email ) ) {
			echo '0';
			die();
		}

		if ( ! ( array_key_exists( 'unblock_url', self::$options ) && strlen( self::$options['unblock_url'] ) === 32 ) ) {
			self::$options['unblock_url'] = md5( time() );
			update_option( WF_SN_CF_OPTIONS_KEY, self::$options );
		}

		$subject = __( 'Security Ninja Firewall secret access link', 'security-ninja' );
		$body    = '<p>Your secret access link is ' . self::get_unblock_url() . '</p>';
		$body   .= '<p>Please keep it safe and do not share it with others. Use it only if you get blocked by the firewall.</p>';

		$sal_email_link = wf_sn::generate_sn_web_link( 'secret_access_link', '/docs/firewall-protection/secret-access-link/', array( 'utm_medium' => 'email' ) );

		$body .= '<p><a href="' . $sal_email_link . '" target="_blank">Documentation for Secret Access Link</a></p>';

		// @todo - move to a general email sending function...
		$template_path = WF_SN_PLUGIN_DIR . 'modules/scheduled-scanner/inc/email-default.php';

		$html = file_get_contents( $template_path );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$emailintrotext = 'Save your secret access link for ' . self::url_to_domain( site_url() );

		$dashboardlink       = admin_url( '?page=wf-sn' );
		$dashboardlinkanchor = 'Security Ninja settings';

		$body .= '<p><a href="' . $dashboardlink . '" target="_blank">' . $dashboardlinkanchor . '</a></p>';

		$my_replacements = array(
			'%%emailintrotext%%'      => $emailintrotext, // TODO
			'%%websitedomain%%'       => site_url(),
			'%%dashboardlink%%'       => $dashboardlink,
			'%%dashboardlinkanchor%%' => $dashboardlinkanchor,
			'%%secninlogourl%%'       => WF_SN_PLUGIN_URL . 'images/security-ninja-logo.png',
			'%%emailtitle%%'          => $subject, // TODO
			'%%sentfromtext%%'        => 'This email was sent by WP Security Ninja from ' . self::url_to_domain( site_url() ),
			'%%emailcontent%%'        => nl2br( $body ),
		);

		if ( class_exists( 'wf_sn_wl' ) ) {
			if ( wf_sn_wl::is_active() ) {
				$pluginname                          = wf_sn_wl::get_new_name();
				$my_replacements['%%sentfromtext%%'] = 'This email was sent by ' . $pluginname . ' from ' . self::url_to_domain( site_url() );
			}
		}

		$template_path = WF_SN_PLUGIN_DIR . 'modules/scheduled-scanner/inc/email-default.php';

		$html = file_get_contents( $template_path );

		foreach ( $my_replacements as $needle => $replacement ) {
			$html = str_replace( $needle, $replacement, $html );
		}

		$sendresult = wp_mail( $sanitized_email, $subject, $html, $headers ); // @todo

		if ( $sendresult ) {
			echo '1';
		} else {
			echo '0';
		}

		die();
	} // ajax_send_unblock_email





	public static function form_init_check() {
		// todo - add country blocking check also
		if ( self::is_banned_ip() ) {
			$current_user = SN_Geolocation::geolocate_ip( '', true );
			self::update_blocked_count( $current_user['ip'] );
			wf_sn_el_modules::log_event( 'security_ninja', 'blocked_ip', $current_user['ip'] . ' blocked from accessing the login page.', $current_user );
			// @todo syslog
			wp_clear_auth_cookie();
			self::kill_request();
			return false;
		}
	} // form_init_check





	public static function login_filter( $user, $username, $password ) {
		if ( self::is_banned_ip() ) {
			$current_user = SN_Geolocation::geolocate_ip( '', true );
			// Gets IP and country array with 'ip' and 'country'
			self::update_blocked_count( $current_user['ip'] );
			wf_sn_el_modules::log_event( 'security_ninja', 'blocked_ip', $current_user['ip'] . ' blocked from logging in.', $current_user );
			// Kills the request or redirects based on settings
			self::kill_request();
		}
		return $user;
	}





	/**
	 * Prune banned ips
	 * @return none
	 */
	public static function prune_banned() {
		$update = false;

		if ( ! array_key_exists( 'banned_ips', self::$options ) ) {
			self::$options['banned_ips'] = array();
		}

		foreach ( self::$options['banned_ips'] as $ip => $time ) {
			if ( $time < current_time( 'timestamp' ) ) {
				unset( self::$options['banned_ips'][ $ip ] );
				$update = true;
			}
		}

		if ( $update ) {
			update_option( WF_SN_CF_OPTIONS_KEY, self::$options );
		}
	}





	// log failed login
	public static function failed_login( $username ) {
		global $wpdb;

		if ( ! array_key_exists( 'banned_ips', self::$options ) ) {
			self::$options['banned_ips'] = array();
		}

		$current_user_ip = self::get_user_ip();
		$current_user    = SN_Geolocation::geolocate_ip( '', true ); // Gets IP and country array('ip', 'country')

		// @todo replace ip refs with $current_user - used throughout plugin
		$date           = date( 'Y-m-d H:i:m', current_time( 'timestamp' ) );
		$query          = $wpdb->prepare(
			'SELECT COUNT(id) FROM ' . $wpdb->prefix . WF_SN_EL_TABLE .
			' WHERE ip = %s AND action = %s AND timestamp >= DATE_SUB(%s, INTERVAL %s MINUTE)',
			$current_user_ip,
			'wp_login_failed',
			$date,
			self::$options['max_login_attempts_time']
		);
		$login_attempts = $wpdb->get_var( $query );

		if ( $login_attempts >= self::$options['max_login_attempts'] && ! isset( self::$options['banned_ips'][ $current_user_ip ] ) ) {
			self::$options['banned_ips'][ $current_user_ip ] = current_time( 'timestamp' ) + self::$options['bruteforce_ban_time'] * 60;
			wf_sn_el_modules::log_event( 'security_ninja', 'firewall_ip_banned', $current_user_ip . ' banned due to multiple failed login attempts.', '' );
			// @todo - tilføj option til at sende IP til central API + sanity check
		}

		update_option( WF_SN_CF_OPTIONS_KEY, self::$options );

		$ban = self::is_banned_ip();

		if ( $ban && is_user_logged_in() ) {
			wf_sn_el_modules::log_event( 'security_ninja', 'login_denied_banned_IP', $current_user_ip . ' blocked from logging in.', '' );
			self::log_visitor( $current_user['ip'], $_SERVER['HTTP_USER_AGENT'], $current_user['country'], false, 'Blocked login attempt.' );
			wp_clear_auth_cookie();
			self::kill_request();
		}
		self::log_visitor( $current_user['ip'], $_SERVER['HTTP_USER_AGENT'], $current_user['country'], false, 'Failed login attempt' );
	} // failed_login





	public static function ipCIDRCheck( $IP, $CIDR ) {
		list ($net, $mask) = explode( '/', $CIDR );
		$ip_net            = ip2long( $net );
		@$ip_mask          = ~( ( 1 << ( 32 - $mask ) ) - 1 );
		$ip_ip             = ip2long( $IP );
		$ip_ip_net         = $ip_ip & $ip_mask;
		return ( $ip_ip_net == $ip_net );
	} // ipCIDRCheck




	/**
	 * Checks if an IP is in array
	 * @param [type] $needle   [description]
	 * @param [type] $haystack [description]
	 */
	public static function IP_in_array( $needle, $haystack ) {

		// Check if haystack is array and makes sure it is trimmed from apostrophes
		if ( is_array( $haystack ) ) {
			$ip_arr = array();
			foreach ( $haystack as $key => $item ) {
				$ip_arr[] = trim( $item, "'" );
			}
		}

		if ( in_array( $needle, $ip_arr ) ) {
			return true;
		}

		foreach ( $haystack as $key => $item ) {
			if ( $item === $needle ) {
				return true;
			}
		}
	}





	/**
	 * Checks a specific IP is banned or not
	 * @param string $ip (defaults to false)
	 * @return true if IP banned
	 */
	public static function is_banned_ip( $ip = false ) {
		self::prune_banned();
		// Checks if IP is set or try to get it
		if ( $ip ) {
			$current_user_ip = $ip;
		} else {
			$current_user = SN_Geolocation::geolocate_ip( '', true ); // Gets IP and country array('ip', 'country')
			if ( $current_user ) {
				$current_user_ip = $current_user['ip'];
			}
		}
		// @debug
		// Check if IP is in blacklist. P.s. could use in_array() but had trouble with spaces ... perhaps trim first.. hmm...
		$blacklist = self::$options['blacklist'];
		if ( is_array( $blacklist ) ) {
			foreach ( $blacklist as $bl ) {
				if ( trim( $bl ) === $current_user_ip ) {
					return true;
				}
			}
		}

		$ips = get_option( WF_SN_CF_IPS_KEY );

		if ( ! array_key_exists( 'banned_ips', self::$options ) ) {
			self::$options['banned_ips'] = array();
		}

		if ( is_array( self::$options['whitelist'] ) && self::is_whitelisted( $current_user_ip, self::$options['whitelist'] ) ) {
			return false;
		} elseif ( array_key_exists( $current_user_ip, self::$options['banned_ips'] ) ) {
			return true;
		} elseif ( ( '1' === self::$options['usecloud'] ) && ( self::IP_in_array( $current_user_ip, $ips['ips'] ) ) ) {
			return true;
		} else {
			$nework_array = explode( '.', $current_user_ip, 2 );
			// is cloud firewall enabled?
			if ( '1' === self::$options['usecloud'] ) {
				if ( array_key_exists( $nework_array[0], $ips['subnets'] ) ) {
					foreach ( $ips['subnets'][ $nework_array[0] ] as $subnet ) {
						// trim apostrophes
						$subnet = trim( $subnet, "'" );
						if ( self::ipCIDRCheck( $current_user_ip, $subnet ) ) {
							return true;
						}
					}
				}
			}
		}
		// checks for visitor ban
		if ( self::is_banned_country( $current_user_ip ) ) {
			return true;
		}

		return false;
	} // is_banned_ip








	/**
	 * Checks if an IP is whitelisted
	 * @param  [type]  $ip        The IP to test
	 * @param  [type]  $whitelist The array of IPs to test against
	 * @return boolean            Returns true if the IP is whitelisted
	 */
	public static function is_whitelisted( $ip, $whitelist ) {
		foreach ( $whitelist as $wip ) {
			if ( strpos( $wip, '/' ) !== false ) {
				if ( self::ipCIDRCheck( $ip, $wip ) ) {
					return true;
				}
			} else {
				if ( $ip === $wip ) {
					return true;
				}
			}
		}
		return false;
	}






	/**
	 * Update cloud firewall blocked IPs and update server IP to whitelist
	 * @return none
	 */
	public static function update_cloud_ips() {

		$server_host = gethostname();
		$server_ip   = gethostbyname( $server_host );
		$options     = self::get_options();
		if ( ! in_array( $server_ip, $options['whitelist'] ) ) {
			$options['whitelist'][] = trim( $server_ip );
			update_option( WF_SN_CF_OPTIONS_KEY, $options );
			wf_sn_el_modules::log_event( 'security_ninja', 'unblocked_ip', 'Added server IP to whitelist ' . $server_ip );
		}

		$firehol = 'https://raw.githubusercontent.com/firehol/blocklist-ipsets/master/firehol_level1.netset';

		$response = wp_remote_get( $firehol );

		if ( ! is_wp_error( $response ) ) {
			$body             = wp_remote_retrieve_body( $response );
			$sn_firewall_data = array(
				'ips'     => array(),
				'subnets' => array(),
			);

			$lines = explode( PHP_EOL, $body );

			foreach ( $lines as $line_num => $line ) {

				if ( strpos( $line, '#' ) !== false ) {
					// Skip comments
					continue;

				} elseif ( strpos( $line, '/' ) !== false ) {

					$nework_array = explode( '.', trim( $line ), 2 );

					if ( ! array_key_exists( $nework_array[0], $sn_firewall_data['subnets'] ) ) {

						$sn_firewall_data['subnets'][ $nework_array[0] ] = array();

					}
					$sn_firewall_data['subnets'][ $nework_array[0] ][] = trim( $line );

				} else {
					$sn_firewall_data['ips'][] = trim( $line );

				}
			}
			$sn_firewall_data['timestamp'] = time();
			update_option( WF_SN_CF_IPS_KEY, $sn_firewall_data, 'no' );

			if ( wf_sn_el::syslogactive() ) {

				$upload_dir          = wp_upload_dir();
				$secninja_upload_dir = $upload_dir['basedir'] . '/security-ninja/logs/';
				$log                 = new Logger( 'Security Ninja' );

				if ( ! in_array( $options['rotatingsyslog'], array( 7, 30 ) ) ) {
					$rotate_days = 0;
				} else {
					$rotate_days = $options['rotatingsyslog'];
				}

				$handler = new \Monolog\Handler\RotatingFileHandler( $secninja_upload_dir . 'security-ninja.log', $rotatedays, Monolog\Logger::DEBUG );
				$handler->setFilenameFormat( '{date}-{filename}', 'Y-m-d' );
				$log->pushHandler( $handler );
				$user = wp_get_current_user();
				$log->info( 'Downloaded IP blocklist' );

			}
		} else {
			wf_sn_el_modules::log_event( 'security_ninja', 'geolocation_download', 'Unable to download GeoIP Database: ' . $response->get_error_message(), '' );

			if ( wf_sn_el::syslogactive() ) {
				$upload_dir          = wp_upload_dir();
				$secninja_upload_dir = $upload_dir['basedir'] . '/security-ninja/logs/';
				$log                 = new Logger( 'Security Ninja' );

				if ( ! in_array( $options['rotatingsyslog'], array( 7, 30 ) ) ) {
					$rotate_days = 0;
				} else {
					$rotate_days = $options['rotatingsyslog'];
				}

				$handler = new \Monolog\Handler\RotatingFileHandler( $secninja_upload_dir . 'security-ninja.log', $rotatedays, Monolog\Logger::DEBUG );
				$handler->setFilenameFormat( '{date}-{filename}', 'Y-m-d' );
				$log->pushHandler( $handler );
				$user = wp_get_current_user();

				$log->warning(
					'Unable to download GeoIP Database',
					array(
						'errormessage' => $response->get_error_message(),
						'response'     => $response,
					)
				);

			}
		}
	}







	public static function register_settings() {
		register_setting( WF_SN_CF_OPTIONS_KEY, 'wf_sn_cf', array( __CLASS__, 'sanitize_settings' ) );
	} // register_settings


	/** Centralized way to get users IP */
	public static function get_user_ip() {

		$client  = '';
		$forward = '';
		$remote  = '';

		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$client = $_SERVER['HTTP_CLIENT_IP'];
		}
		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$forward = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$remote = $_SERVER['REMOTE_ADDR'];
		}

		if ( filter_var( $client, FILTER_VALIDATE_IP ) ) {
			$ip = $client;
		} elseif ( filter_var( $forward, FILTER_VALIDATE_IP ) ) {
			$ip = $forward;
		} else {
			$ip = $remote;
		}

		return $ip;
	} // get_user_ip


	/** Adds warning message above login form */
	public static function login_message( $msg ) {
		if ( self::is_active() && self::$options['login_msg'] &&
			'register' !== @$_GET['action'] &&
			'lostpassword' !== @$_GET['action'] ) {
			$msg = '<p class="message register">' . self::$options['login_msg'] . '</p>' . $msg;
		}
		return $msg;
	} // login message


	// sanitize settings on save
	public static function sanitize_settings( $values ) {

		$old_options                  = get_option( WF_SN_CF_OPTIONS_KEY );
		$old_options['active']        = 0;
		$old_options['global']        = 0;
		$old_options['trackvisits']   = 0;
		$old_options['filterqueries'] = 0;
		$old_options['usecloud']      = 0;

		foreach ( $values as $key => $value ) {
			switch ( $key ) {
				case 'active':
				case 'global':
				case 'usecloud':
				case 'trackvisits':
				case 'filterqueries':
				case 'message':
				case 'max_login_attempts':
				case 'max_login_attempts_time':
				case 'bruteforce_ban_time':
				case 'login_msg':
				case 'unblock_url':
					$values[ $key ] = trim( $value );
					break;
				case 'redirect_url':
					$values[ $key ] = esc_url_raw( $value );
					break;
				case 'blacklist':
					if ( ! is_array( $values[ $key ] ) ) {
						$values[ $key ] = explode( PHP_EOL, $values[ $key ] );
					}
					break;
				case 'whitelist':
					if ( ! is_array( $values[ $key ] ) ) {
						$values[ $key ] = explode( PHP_EOL, $values[ $key ] );
					}
					break;
				case 'blocked_countries':
					if ( ! is_array( $values[ $key ] ) ) {
						$values[ $key ] = explode( PHP_EOL, $values[ $key ] );
					}
					break;
			} // switch
		} // foreach

		// temporarily disabled
		$user_ip = self::get_user_ip();
		if ( false && $values['active'] == 1 ) {
			if ( ! in_array( $user_ip, $values['whitelist'] ) ) {
				$values['whitelist'][] = $user_ip;
			}
		}

		// if not set
		if ( ! isset( $values['blocked_countries'] ) ) {
			$values['blocked_countries'] = array();
		}

		return array_merge( $old_options, $values );
	} // sanitize_settings





	// add new tab
	public static function sn_tabs( $tabs ) {
		$core_tab = array(
			'id'       => 'sn_cf',
			'class'    => '',
			'label'    => 'Firewall',
			'callback' => array( __CLASS__, 'do_page' ),
		);
		$done     = 0;

		for ( $i = 0; $i < sizeof( $tabs ); $i++ ) {
			if ( $tabs[ $i ]['id'] == 'sn_cf' ) {
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




	public static function get_table_prefix() {
		global $wpdb;

		if ( is_multisite() && ! defined( 'MULTISITE' ) ) {
			$table_prefix = $wpdb->base_prefix;
		} else {
			$table_prefix = $wpdb->get_blog_prefix( 0 );
		}

		return $table_prefix;
	} // get_table_prefix


	public static function create_toogle_switch( $name, $options = array(), $output = true ) {
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


	public static function checked( $value, $current, $echo = false ) {
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


	public static function create_select_options( $options, $selected = null, $output = true ) {
		$out = "\n";

		foreach ( $options as $tmp ) {
			if ( $selected == $tmp['val'] ) {
				$out .= "<option selected=\"selected\" value=\"{$tmp['val']}\">{$tmp['label']}&nbsp;</option>\n";
			} else {
				$out .= "<option value=\"{$tmp['val']}\">{$tmp['label']}&nbsp;</option>\n";
			}
		}

		if ( $output ) {
			echo $out;
		} else {
			return $out;
		}
	} //  create_select_options

	// add custom message to overlay
	public static function overlay_content() {
		echo '<div id="sn-cloud-firewall" style="display: none; text-align:center;">';
		echo '<h2 style="font-weight: bold;">' . __( 'Important! Please READ! This is not the usual mumbo-jumbo.', 'security-ninja' ) . '</h2>';
		echo '<p>' . __( 'In the unlikely situation that your IP gets banned, you will not be able to login or access the site. In that case you need the secret access link.', 'security-ninja' ) . '</p>';

		echo '<p>' . __( 'It whitelists your IP and enables access. Please store the link in a safe place or use the form below to get it sent to your email address.', 'security-ninja' ) . '</p>';

		echo '<p><code>' . self::get_unblock_url() . '</code></p>';

		echo '<p>' . __( 'Enter your email below to receive the secret access link in case you get locked out', 'security-ninja' ) . '</p>';
		echo '<input style="width: 250px;" type="text" id="sn-ublock-email" name="sn-ublock-email" value="' . get_option( 'admin_email' ) . '" placeholder="john@example.com"><br />
		<p id="sn-unblock-message"></p>';

		?>
		<input type="button" value="<?php esc_html_e( 'Send secret access link', 'security-ninja' ); ?>" id="sn-send-unlock-code" class="input-button button button-secondary" />
		<?php
		echo '<p><br><input type="button" value="Close (3)" id="sn-close-firewall" class="input-button button-primary" /></p>';

		echo '</div>';
	} // overlay_content


	/**
	 * Checks if the firewall module is active
	 * @return boolean true/false
	 */
	public static function is_active() {
		return (bool) self::$options['active'];
	} // is_active





	/**
	 * Returns list of blocked country codes for use with GEOIP.
	 * @return array Blocked countries.
	 */
	public static function get_blocked_countries() {
		$blocked_countries = self::$options['blocked_countries'];
		if ( ! $blocked_countries ) {
			return array();
		}
		if ( is_array( $blocked_countries ) ) {
			$bclist = array();
			foreach ( $blocked_countries as $key => $ba ) {
				$bclist[] = $ba;
			}
			return $bclist;
		}
		return array();
	}




	public static function get_unblock_url() {

		// check if already set
		if ( ! array_key_exists( 'unblock_url', self::$options ) ) {
			self::$options['unblock_url'] = md5( time() );
			update_option( WF_SN_CF_OPTIONS_KEY, self::$options );
		}

		$outurl = add_query_arg(
			array(
				'snf' => self::$options['unblock_url'],
			),
			get_site_url()
		);

		return $outurl;
	}

	// display results
	public static function do_page() {
		global $wpdb;

		$ips = get_option( WF_SN_CF_IPS_KEY );
		require_once WF_SN_PLUGIN_DIR . 'misc/country-codes.php';

		// Maybe cron not run yet? Just in case we force load if no IP's found.
		if ( ! isset( $ips['ips'] ) ) {
			self::update_cloud_ips();
			$ips = get_option( WF_SN_CF_IPS_KEY );
			do_action( 'secnin_update_cloud_firewall' ); //
		}

		$ips_count     = count( $ips['ips'] );
		$subnets_count = 0;
		foreach ( $ips['subnets'] as $subnet ) {
			$subnets_count += count( $subnet );
		}

		if ( ! array_key_exists( 'total', $ips ) ) {
			$total_ips = 0;
			foreach ( $ips['subnets'] as $prefix => $subnet ) {
				foreach ( $subnet as $sub ) {
					$mask       = explode( '/', str_replace( '\'', '', $sub ) );
					$total_ips += pow( 2, 32 - $mask[1] ) - 2;
				}
			}
			$ips['total'] = $total_ips + count( $ips['ips'] ) + count( self::$options['banned_ips'] );
			update_option( WF_SN_CF_IPS_KEY, $ips, 'no' );
		}

		?>
	<div class="submit-test-container">
		<h3><?php esc_html_e( 'Firewall - Protect your website', 'security-ninja' ); ?></h3>

		<?php
		global $secnin_fs;
		if ( ( $secnin_fs->is_registered() ) && ( ! $secnin_fs->is_pending_activation() ) && ( ! wf_sn_wl::is_active() ) ) {
			?>
			<p><a href="#" data-beacon-article="5cc4ddd42c7d3a026fd41d65"><?php esc_html_e( 'Need help? How to use the firewall.', 'security-ninja' ); ?></a></p>
			<?php
		}

		if ( self::is_active() ) {
		} else {
			echo '<input type="button" value="' . __( 'Enable Firewall', 'security-ninja' ) . '" id="sn-enable-firewall-overlay" class="button button-primary button-hero"/>';
		}

		if ( ! self::is_active() ) {
			?>
			<div class="testresults">
				<h3><?php esc_html_e( 'Block attacks to your website', 'security-ninja' ); ?></h3>
				<ul class="security-test-list">
					<li><?php esc_html_e( 'Protect against 600+ million known bad IPs - The list is automatically updated several times a day.', 'security-ninja' ); ?></li>
					<li><?php esc_html_e( 'Protect against dangerous requests - SQL injections and other malicious page requests.', 'security-ninja' ); ?></li>
					<li><?php esc_html_e( 'Country Blocking - Prevent visitors from specific countries to visit your website.', 'security-ninja' ); ?></li>
					<li><?php esc_html_e( 'Redirect blocked visitors. Prevent visitors from even viewing your website.', 'security-ninja' ); ?></li>
					<li><?php esc_html_e( 'Login Form Protection. Prevent multiple repeated failed logins.', 'security-ninja' ); ?></li>
				</ul>
			</div>

			<p>
			<?php
			printf(
				esc_html__( '%1$s bad IPs in list. Last updated %2$s (%3$s) ', 'security-ninja' ),
				'<strong>' . number_format_i18n( $ips['total'] ) . '</strong>',
				date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ips['timestamp'] ),
				human_time_diff( $ips['timestamp'], current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'security-ninja' )
			);
			?>
		</p>

	</div>
			<?php
		}
		$blocked_count = get_option( WF_SN_CF_BLOCKED_COUNT );
		if ( $blocked_count ) {
			?>
	<p>
			<?php
			printf(
				esc_html__( '%s blocked visits so far.', 'security-ninja' ),
				'<strong>' . number_format_i18n( $blocked_count ) . '</strong>'
			);
			?>
</p>
			<?php
		}

		if ( self::is_active() ) {
			?>
	<p>
			<?php
			printf(
				esc_html__( '%s bad IPs blocked from logging in.', 'security-ninja' ),
				'<strong>' . number_format_i18n( $ips['total'] ) . '</strong>'
			);
			?>
	</p>
	<p><small>
			<?php
			printf(
				esc_html__( 'List last updated %1$s (%2$s)', 'security-ninja' ),
				date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ips['timestamp'] ),
				human_time_diff( $ips['timestamp'], current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'security-ninja' )
			);
			?>
		</small></p>
			<?php
		}

		if ( (int) self::$options['active'] === 1 ) {
			echo '<input type="button" value="' . __( 'Disable Firewall', 'security-ninja' ) . '" id="sn-disable-firewall" class="input-button button-secondary button-secondary" />';
		}

		if ( (int) self::$options['active'] === 1 ) {
			echo '<form action="options.php" id="sn-firewall-settings-form" method="post">';
			settings_fields( 'wf_sn_cf' );
			?>
	<h3><?php esc_html_e( 'Firewall Settings', 'security-ninja' ); ?></h3>
	<hr>
	<table class="form-table" id="sn-cf-options"><tbody>
			<?php

			echo '<tr valign="top">
		<th scope="row"><label for="wf_sn_cf_global">' . __( 'Prevent Banned IPs from Accessing the Site', 'security-ninja' ) . '</label></th>
		<td class="sn-cf-options">';

			self::create_toogle_switch(
				WF_SN_CF_OPTIONS_KEY . '_global',
				array(
					'saved_value' => self::$options['global'],
					'option_key'  => WF_SN_CF_OPTIONS_KEY . '[global]',
				)
			);

			echo '<p class="description">' . __( 'If set to ON cloud and local firewall will prevent banned IPs from accessing the site all together.', 'security-ninja' );

			echo '<p class="description">' . __( 'If set to OFF they will not be able to log in, but will be able to view the site.', 'security-ninja' ) . '</p>';
			echo '</td></tr>';

			echo '<tr valign="top">
		<th scope="row"><label for="wf_sn_cf_global">Block Suspicious Page Requests</label></th>
		<td class="sn-cf-options">';

			self::create_toogle_switch(
				WF_SN_CF_OPTIONS_KEY . '_filterqueries',
				array(
					'saved_value' => self::$options['filterqueries'],
					'option_key'  => WF_SN_CF_OPTIONS_KEY . '[filterqueries]',
				)
			);

			echo '<p class="description">' . __( 'Filter out page requests with suspicious query strings.', 'security-ninja' );

			echo '</td></tr>';

			?>

		<tr valign="top">
			<th scope="row">
				<label for="wf_sn_cf_usecloud">Use Cloud Firewall</label>
			</th>
			<td class="sn-cf-options">
				<?php

				self::create_toogle_switch(
					WF_SN_CF_OPTIONS_KEY . '_usecloud',
					array(
						'saved_value' => self::$options['usecloud'],
						'option_key'  => WF_SN_CF_OPTIONS_KEY . '[usecloud]',
					)
				);
				?>
					<p class="description">The list of 600+ million IPs can sometimes block traffic that should not be blocked. Use this to turn off this feature.</p>
				</td>
			</tr>

					<?php

					echo '<tr valign="top"><th scope="row"><label for="wf_sn_cf_geoip">' . __( 'Block visits from these countries', 'security-ninja' ) . '</label></th><td class="sn-cf-options">';
					?>
			<select name="wf_sn_cf[blocked_countries][]" id="wf_sn_cf_blocked_countries" multiple="multiple" style="width:100%;" class="">
					<?php
					require WF_SN_PLUGIN_DIR . 'modules/cloud-firewall/class-sn-geoip-countrylist.php';
					$blocked_countries = self::get_blocked_countries();
					foreach ( $geoip_countrylist as $key => $gc ) {
						$selected = in_array( $key, $blocked_countries, true ) ? ' selected="selected" ' : '';
						?>
						<option value="<?php echo $key; ?>"<?php echo $selected; ?>><?php echo $gc . ' (' . $key . ')'; ?></option>
						<?php
					}
					?>
			</select>
			<p class="description"><label for="wf_sn_cf_blocked_countries"><?php esc_html_e( 'Choose the countries you want to block.', 'security-ninja' ); ?></label></p>

			<p class="description"><?php esc_html_e( 'Be careful not to block USA if you depend on traffic from Google as Google crawls your website from USA.', 'security-ninja' ); ?></p>

				<?php

				echo '<tr valign="top">
			<th scope="row"><label for="wf_sn_cf_message">' . __( 'Message for blocked IPs', 'security-ninja' ) . '</label></th>
			<td class="sn-cf-options"><textarea id="wf_sn_cf_message" name="' . WF_SN_CF_OPTIONS_KEY . '[message]" rows="3" cols="50">' . self::$options['message'] . '</textarea><span class="description">' . __( 'Message shown to blocked visitors when they try to access the site or log in.', 'security-ninja' ) . '</span></td></tr>';
				?>
			<tr>
				<th></th>
				<td><?php esc_html_e( 'Or you can redirect blocked visitors.', 'security-ninja' ); ?></td>
			</tr>
				<?php

				echo '<tr valign="top">
			<th scope="row"><label for="">Secret Access URL</label></th>
			<td id="sn-firewall-blacklist"><code>';
				echo self::get_unblock_url();
				echo '</code>';
				echo '<span class="description">Do not share this URL! Use it only to access the site if your IP gets banned.</span>';
				echo '</td></tr>';

				echo '<tr valign="top"><th scope="row"><label for="wf-cf-redirect-url">' . __( 'Redirect blocked visitors', 'security-ninja' ) . '</label></th><td>';
				echo '<input type="text" placeholder="https://" class="regular-text" value="' . self::$options['redirect_url'] . '" id="wf-cf-redirect-url" name=' . WF_SN_CF_OPTIONS_KEY . '[redirect_url]>';

				echo '<p class="description">' . __( '301 redirect blocked visitors to any URL. Leave empty to show message instead.', 'security-ninja' ) . '</p>';
				echo '</td></tr>';

				?>
			<tr valign="top">
				<th colspan="2">
					<h3><?php esc_html_e( 'Login Form Protection', 'security-ninja' ); ?></h3>
				</th>
			</tr>
			<?php

			for ( $i = 2; $i <= 10; $i++ ) {
				$max_login_attempts[] = array(
					'val'   => $i,
					'label' => $i,
				);
			}
			for ( $i = 2; $i <= 15; $i++ ) {
				$max_login_attempts_time_s[] = array(
					'val'   => $i,
					'label' => $i,
				);
			}

			$bruteforce_timeouts[] = array(
				'val'   => 2,
				'label' => __( '2 minutes', 'security-ninja' ),
			); //i8n
			$bruteforce_timeouts[] = array(
				'val'   => 10,
				'label' => __( '10 minutes', 'security-ninja' ),
			);
			$bruteforce_timeouts[] = array(
				'val'   => 20,
				'label' => __( '20 minutes', 'security-ninja' ),
			);
			$bruteforce_timeouts[] = array(
				'val'   => 30,
				'label' => __( '30 minutes', 'security-ninja' ),
			);
			$bruteforce_timeouts[] = array(
				'val'   => 60,
				'label' => __( '1 hour', 'security-ninja' ),
			);
			$bruteforce_timeouts[] = array(
				'val'   => 120,
				'label' => __( '2 hours', 'security-ninja' ),
			);
			$bruteforce_timeouts[] = array(
				'val'   => 1440,
				'label' => __( '1 day', 'security-ninja' ),
			);
			$bruteforce_timeouts[] = array(
				'val'   => 2880,
				'label' => __( '2 days', 'security-ninja' ),
			);
			$bruteforce_timeouts[] = array(
				'val'   => 10080,
				'label' => __( '7 days', 'security-ninja' ),
			);
			$bruteforce_timeouts[] = array(
				'val'   => 43200,
				'label' => __( '1 month', 'security-ninja' ),
			);
			$bruteforce_timeouts[] = array(
				'val'   => 525600,
				'label' => __( '1 year', 'security-ninja' ),
			);
			$bruteforce_timeouts[] = array(
				'val'   => 5256000,
				'label' => __( 'forever', 'security-ninja' ),
			);

			$notification_settings[] = array(
				'val'   => 0,
				'label' => __( 'No', 'security-ninja' ),
			);
			$notification_settings[] = array(
				'val'   => 1,
				'label' => __( 'Yes', 'security-ninja' ),
			);

			echo '<tr valign="top">';
			echo '<th scope="row"><label for="wf_sn_options_mla">Auto-ban rules for failed login attempts</label></th>'; //i8n
			echo '<td class="' . ( ! self::$options['active'] ? 'sn-disabled' : '' ) . ' sn-cf-options">
			<select name="' . WF_SN_CF_OPTIONS_KEY . '[max_login_attempts]" id="wf_sn_options_mla">';
			self::create_select_options( $max_login_attempts, self::$options['max_login_attempts'] );
			echo '</select> failed login attempts in ';
			echo '<select name="' . WF_SN_CF_OPTIONS_KEY . '[max_login_attempts_time]" id="wf_sn_options_mlat">';
			self::create_select_options( $max_login_attempts_time_s, self::$options['max_login_attempts_time'] );
			echo '</select> minutes get the IP banned for ';
			echo '<select name="' . WF_SN_CF_OPTIONS_KEY . '[bruteforce_ban_time]" id="wf_sn_options_bbt">';
			self::create_select_options( $bruteforce_timeouts, self::$options['bruteforce_ban_time'] );
			echo '</select>';
			echo '<span class="description">Users who continuously make failed login attempts will get banned. Five failed attempts in five minutes is a good threshold.</span>';
			echo '</td></tr>';

			echo '<tr valign="top">';
			echo '<th scope="row"><label for="wf_sn_options_login_msg">Login notice</label></th>';
			echo '<td class="sn-cf-options">
			<textarea cols="50" rows="3" name="' . WF_SN_CF_OPTIONS_KEY . '[login_msg]" id="wf_sn_options_login_msg">' . self::$options['login_msg'] . '</textarea>';
			echo '<span class="description">Usefull on a multi-user site to warn people what happens if they fail to login too many times.</span>';
			echo '</td>';
			echo '</tr>';

			?>

			<tr valign="top">
				<th colspan="2">
					<h3><?php esc_html_e( 'IP handling', 'security-ninja' ); ?></h3>
				</th>
			</tr>
			<?php

			$current_user = SN_Geolocation::geolocate_ip( '', true );

			echo '<tr valign="top">
			<th scope="row"><label for="wf_sn_cf_blacklist">' . __( 'BLACKLIST IPs', 'security-ninja' ) . '</label></th>
			<td class="sn-cf-options"><textarea id="wf_sn_cf_blacklist" name="' . WF_SN_CF_OPTIONS_KEY . '[blacklist]" rows="5" cols="50">' . ( is_array( self::$options['blacklist'] ) ? implode( PHP_EOL, self::$options['blacklist'] ) : '' ) . '</textarea>';
			echo '<p class="description">Manually block these IPs. Write one IP or subnet mask per line.</p>';
			echo '</td></tr>';

			echo '<tr valign="top">
			<th scope="row"><label for="wf_sn_cf_whitelist">' . __( 'Whitelist IPs', 'security-ninja' ) . '</label></th>
			<td class="sn-cf-options"><textarea id="wf_sn_cf_whitelist" name="' . WF_SN_CF_OPTIONS_KEY . '[whitelist]" rows="5" cols="50">' . ( is_array( self::$options['whitelist'] ) ? implode( PHP_EOL, self::$options['whitelist'] ) : '' ) . '</textarea>';
			echo '<p class="description">These IPs are never blocked. Write one IP or subnet mask per line.</p>';
			echo '<p>Your IP address is: ' . $current_user['ip'] . '</p>';
			$server_host = gethostname();
			$server_ip   = gethostbyname( $server_host );
			echo '<p>Your webserver IP address is: ' . $server_ip . '</p>';
			echo '</td></tr>';

			echo '<tr valign="top">
			<th scope="row"><label for="">Locally Banned IPs</label></th>
			<td id="sn-firewall-blacklist">';
			self::prune_banned();
			if ( count( self::$options['banned_ips'] ) ) {
				echo '<ul style="margin-top: 5px;">';
				foreach ( self::$options['banned_ips'] as $banned_ip => $ban_time ) {
					echo '<li>' . $banned_ip . '; time till ban expires: ' . human_time_diff( current_time( 'timestamp' ), $ban_time ) . '</li>';
				}
				echo '</ul>';
				echo '<br><input type="button" value="Clear list of banned IPs" id="sn-firewall-blacklist-clear" style="background: #cc0000;" class="button button-primary" />';
			} else {
				echo 'No locally banned IPs';
			}
			echo '<span class="description">IPs banned due to too many failed login attempts.</span>';
			echo '</td></tr>';

			echo '<tr valign="top">
			<th scope="row"><label for="wf-cf-ip-test">' . __( 'Test IP', 'security-ninja' ) . '</label></th>
			<td id="sn-firewall-test-ip">';
			echo '<input type="text" placeholder="' . __( 'IP address, ie: 213.45.66.12', 'security-ninja' ) . '" class="regular-text" value="" id="wf-cf-ip-test">&nbsp; &nbsp;';
			echo '<a href="#" id="wf-cf-do-test-ip" class="button js-action">' . __( 'Test if IP is Banned', 'security-ninja' ) . '</a>';
			echo '<span class="description">Enter an IP address in order to test if it\'s banned. Your IP address is: ' . $current_user['ip'] . '</span>';
			echo '</td></tr>';

			echo '<input type="hidden" id="wf_sn_cf_active" name="wf_sn_cf[active]" value="' . self::$options['active'] . '" />';
			echo '<tr valign="top"><td colspan="2" style="padding:0px;">';
			echo '<p class="submit"><br><input type="submit" value="' . __( 'Save Changes', 'security-ninja' ) . '" class="input-button button-primary" name="Submit" /></p>';
			echo '</td></tr>';
			echo '</table>';
			echo '</form>';

			?>

			<h3>Visitor logs</h3>

			<?php

			$latestQuery = 'SELECT *
			FROM ' . $wpdb->prefix . WF_SN_CF_LOG_TABLE . '
			WHERE 1=1
			ORDER BY ID DESC LIMIT 50;';

			$latestVisits = $wpdb->get_results( $latestQuery );

			if ( is_array( $latestVisits ) ) {

				?>
				<div class="testresults">
					<h3><?php esc_html_e( 'Latest visitors', 'security-ninja' ); ?></h3>

					<table class="wp-list-table widefat striped">
						<thead>
							<tr>
								<th class="column-primary" >
									<?php esc_html_e( 'IP', 'security-ninja' ); ?>
								</th>
								<th>
									<?php esc_html_e( 'Timestamp', 'security-ninja' ); ?>
								</th>
								<th>
									<?php esc_html_e( 'URL', 'security-ninja' ); ?>
								</th>
								<th>
									<?php esc_html_e( 'Action', 'security-ninja' ); ?>
								</th>
							</tr>
						</thead>
						<tbody>

							<?php

							foreach ( $latestVisits as $visit ) {
								$trclass = 'visit';

								if ( $visit->banned ) {
									$trclass .= ' banned';
								}
								?>
								<tr class="<?php echo $trclass; ?>">
									<td data-colname="IP" class="column-primary" >
										<?php
										echo $visit->ip;

										if ( $visit->country ) {
											echo '</br>' . $visit->country;
										}

										$imgurl_scr = wf_sn::get_country_img_src__premium_only( $visit->country, 16 );

										if ( $imgurl_scr ) {
											echo ' ' . $imgurl_scr;
										}
										?>
										<button type="button" class="toggle-row">
											<span class="screen-reader-text"><?php esc_html_e( 'Show details', 'security-ninja' ); ?></span>
										</button>
									</td>


									<td data-colname="Time">
									<?php
									echo $visit->timestamp . '</br><small>' . human_time_diff( strtotime( $visit->timestamp ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'security-ninja' ) . '</small>';
									?>

								</td>
								<td data-colname="URL">
									<?php
									echo esc_url( $visit->URL );
									?>
								</td>
								<td>Action
								</td>
							</tr>
								<?php
							}
							?>
					</tbody>
				</table>
			</div>
				<?php

			}

			$countryquery = 'SELECT country as code, COUNT(*) as cnt, count( DISTINCT(ip) ) as uniqueip FROM ' . $wpdb->prefix . WF_SN_CF_LOG_TABLE . ' GROUP BY code ORDER BY cnt DESC;';

			$countryvisits = $wpdb->get_results( $countryquery );

			if ( is_array( $countryvisits ) ) {
				$firstdatequery = 'SELECT timestamp FROM ' . $wpdb->prefix . WF_SN_CF_LOG_TABLE . ' ORDER BY timestamp ASC LIMIT 1;';
				$mindate        = $wpdb->get_var( $firstdatequery );

				$visitsquery    = 'SELECT count(*) FROM ' . $wpdb->prefix . WF_SN_CF_LOG_TABLE . ';';
				$recordedvisits = $wpdb->get_var( $visitsquery );

				echo '</div>';

				// ** TOP BLOCKED
				$topblockedquery = 'SELECT ip, sum(banned) as blocks, country FROM ' . $wpdb->prefix . WF_SN_CF_LOG_TABLE . ' WHERE banned>0 GROUP BY ip ORDER by blocks DESC LIMIT 10;';

				$topblocked = $wpdb->get_results( $topblockedquery );
				if ( ( is_array( $topblocked ) ) && ( count( $topblocked ) > 0 ) ) {
					?>
				<div class="testresults">
					<h3>Top 15 blocked IPs</h3>
					<table class="wp-list-table widefat striped">
						<thead>
							<tr>
								<th class="column-primary">IP</th>
								<th>Country</th>
								<th>Blocked Visits</th>
							</tr>
						</thead>
						<tbody>

							<?php
							foreach ( $topblocked as $ti ) {
								?>

								<tr>
									<td class="column-primary"><?php echo esc_html( $ti->ip ); ?><button type="button" class="toggle-row">
										<span class="screen-reader-text">Show details</span>
									</button>
								</td>
								<td data-colname="Country">

									<?php
									echo $ti->country;

									$imgurl_scr = wf_sn::get_country_img_src__premium_only( $ti->country, 16 );

									if ( $imgurl_scr ) {
										echo $imgurl_scr;
									}

									if ( ( isset( $ti->country ) ) && ( isset( $country_codes[ $ti->country ] ) ) ) {
										echo ' (' . $country_codes[ $ti->country ] . ')';
									}
									?>
								</td>
								<td data-colname="Blocked Visits"><?php echo number_format_i18n( $ti->blocks ); ?></td>
							</tr>
								<?php
							}
							?>
					</tbody>
				</table>
			</div>
					<?php
				}

				$latesteventsquery = 'SELECT * FROM ' . $wpdb->prefix . WF_SN_EL_TABLE . " WHERE `action` IN ( 'blocked_ip', 'blocked_ip_banned', 'banned_ip', 'blacklisted_IP', 'blocked_ip_suspicious_request') AND `module` = 'security_ninja' ORDER BY `timestamp` DESC LIMIT 50; ";

				$latestevents = $wpdb->get_results( $latesteventsquery );

				if ( ( is_array( $latestevents ) ) && ( count( $latestevents ) > 0 ) ) {
					?>
			<div class="testresults">
				<h3><?php esc_html_e( 'Latest Firewall Events', 'security-ninja' ); ?></h3>
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th class="column-primary">
								<?php esc_html_e( 'Timestamp', 'security-ninja' ); ?>
							</th>
							<th>
								<?php esc_html_e( 'IP', 'security-ninja' ); ?>
							</th>
							<th>
								<?php esc_html_e( 'Action', 'security-ninja' ); ?>
							</th>
							<th>
								<?php esc_html_e( 'Description', 'security-ninja' ); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $latestevents as $le ) {
							?>
							<tr>
								<td class="column-primary">
								<?php
								echo $le->timestamp . '</br><small>' . human_time_diff( strtotime( $le->timestamp ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'security-ninja' ) . '</small>';
								?>
								<button type="button" class="toggle-row">
									<span class="screen-reader-text"><?php esc_html_e( 'Show details', 'security-ninja' ); ?></span>
								</button>

							</td>
							<td data-colname="<?php esc_html_e( 'IP', 'security-ninja' ); ?>"><?php echo $le->ip; ?></td>
							<td data-colname="<?php esc_html_e( 'Action', 'security-ninja' ); ?>"><?php echo $le->action; ?></td>
							<td data-colname="<?php esc_html_e( 'Description', 'security-ninja' ); ?>"><?php echo esc_html( $le->description ); ?></td>
						</tr>
							<?php
						}
						?>
				</tbody>
			</table>
		</div>
					<?php
				}

				$topipsquery = 'SELECT ip,count(ip) as visits, sum(banned) as blocks, country FROM ' . $wpdb->prefix . WF_SN_CF_LOG_TABLE . ' GROUP BY ip ORDER by visits DESC LIMIT 15;';
				$topips      = $wpdb->get_results( $topipsquery );
				if ( ( is_array( $topips ) ) && ( count( $topips ) > 0 ) ) {
					?>
		<div class="testresults">
			<h3><?php esc_html_e( 'Top Visitors', 'security-ninja' ); ?></h3>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th class="column-primary">
							<?php esc_html_e( 'IP', 'security-ninja' ); ?>
						</th>
						<th>
							<?php esc_html_e( 'Details', 'security-ninja' ); ?>
						</th>
						<th>
							<?php esc_html_e( 'Visits', 'security-ninja' ); ?>
						</th>
						<th>
							<?php esc_html_e( 'Blocked Visits', 'security-ninja' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php

					$vtimgurl = trailingslashit( WF_SN_PLUGIN_URL ) . 'images/virustotal-icon.svg';

					foreach ( $topips as $ti ) {
						?>
						<tr>
							<td class="column-primary">
								<?php echo $ti->ip; ?>
								<button type="button" class="toggle-row">
									<span class="screen-reader-text"><?php esc_html_e( 'Show details', 'security-ninja' ); ?></span>
								</button>
							</td>
							<td data-colname="Details">

								<a title="<?php esc_html_e( 'Check with Virustotal', 'security-ninja' ); ?>" href="https://www.virustotal.com/gui/ip-address/<?php echo $ti->ip; ?>/details" target="_blank" rel="noopener" class="virustotal">
									<img src="<?php echo esc_url( $vtimgurl ); ?>">
								</a>
								<a title="<?php esc_html_e( 'Country & Details', 'security-ninja' ); ?>" href="https://www.infobyip.com/ip-<?php echo esc_attr( $ti->ip ); ?>.html" target="_blank" rel="noopener" class="iplink">

									<div class="dashicons dashicons-location"></div>
									<?php
									echo esc_html( $ti->country );
									$imgurl_scr = wf_sn::get_country_img_src__premium_only( $ti->country, 16 );
									if ( $imgurl_scr ) {
										echo $imgurl_scr;
									}
									if ( ( isset( $ti->country ) ) && ( isset( $country_codes[ $ti->country ] ) ) ) {
										echo ' (' . esc_html( $country_codes[ $ti->country ] ) . ')';
									}
									?>
								</a>
							</td>
							<td data-colname="Visits"><?php echo esc_html( number_format_i18n( $ti->visits ) ); ?>

						</td>
						<td data-colname="Blocked Visits"><?php echo esc_html( number_format_i18n( $ti->blocks ) ); ?>

					</td>
				</tr>
						<?php
					}
					?>
		</tbody>
	</table>
	<p>
					<?php
					// translators: Showing how many visits recorded since start date
					printf( esc_html__( '%1$s total visits recorded since %2$s.', 'security-ninja' ), number_format_i18n( $recordedvisits ), $mindate );
					?>
	</p>
</div>
					<?php
				}

				$topipsquery = 'SELECT country, count(ip) as visits, sum(banned) as blocks FROM ' . $wpdb->prefix . WF_SN_CF_LOG_TABLE . " WHERE COUNTRY<>'' GROUP BY country ORDER by visits DESC LIMIT 15;";
				$topips      = $wpdb->get_results( $topipsquery );
				if ( ( is_array( $topips ) ) && ( count( $topips ) > 0 ) ) {
					?>
	<div class="testresults">
		<h3>Top Countries</h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th class="column-primary">Country</th>
					<th>Visits</th>
					<th>Blocked Visits</th>
				</tr>
			</thead>
			<tbody>
					<?php
					foreach ( $topips as $ti ) {
						?>
					<tr>
						<td class="column-primary">
						<?php
						echo esc_html( $ti->country );
						$imgurl_scr = wf_sn::get_country_img_src__premium_only( $ti->country, 16 );
						if ( $imgurl_scr ) {
							echo $imgurl_scr;
						}
						if ( ( isset( $ti->country ) ) && ( isset( $country_codes[ $ti->country ] ) ) ) {
							echo ' (' . $country_codes[ $ti->country ] . ')';
						}
						?>
							<button type="button" class="toggle-row">
								<span class="screen-reader-text"><?php esc_html_e( 'Show details', 'security-ninja' ); ?></span>
							</button>
						</td>
						<td data-colname="Visits">
							<?php echo esc_html( number_format_i18n( $ti->visits ) ); ?>
						</td>
						<td data-colname="Blocked Visits">
							<?php echo esc_html( number_format_i18n( $ti->blocks ) ); ?>

						</td>
					</tr>
						<?php
					}
					?>
			</tbody>
		</table>
	</div>
					<?php
				}

				echo '<p>The Cloud Firewall is a dynamic, continuously changing database of bad IP addresses updated every six hours. It contains roughly 600 million IPs that are known for distributing malware, performing brute force attacks on sites and doing other "bad" activities. The database is created by analysing log files of millions of sites.</p>';

				echo '<p>By using the cloud firewall, you will be one step ahead of the bad guys. They will not be able to login to your site or access it at all (if you enable that option). You are not sharing any data with the Cloud Firewall, you are just using its list</p>';

				echo '<p>The local firewall protects your login from brute force attacks. Anybody who fails to log in several times in a given period will be banned.</p>';

			}
		}
		?>

		<?php
	} // do_page

} // wf_sn_cf class


// hook everything up
add_action( 'plugins_loaded', array( 'wf_sn_cf', 'init' ) );

// setup environment when activated
register_activation_hook( WF_SN_BASE_FILE, array( 'wf_sn_cf', 'activate' ) );

// when deativated clean up
register_deactivation_hook( WF_SN_BASE_FILE, array( 'wf_sn_cf', 'deactivate' ) );
