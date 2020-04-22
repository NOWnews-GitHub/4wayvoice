<?php
if (!function_exists('add_action')) {
	die('Please don\'t open this file directly!');
}

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

define('WF_SN_EL_OPTIONS_KEY', 'wf_sn_el');
define('WF_SN_EL_TABLE', 'wf_sn_el');


require 'sn-el-modules.php';

class wf_sn_el {
	// init plugin
	static function init() {
		// does the user have enough privilages to use the plugin GUI
		if (is_admin()) {
			// add tab to Security Ninja tabs
			add_filter('sn_tabs', array(__CLASS__, 'sn_tabs'));

			// enqueue scripts
			add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));

			// register ajax endpoints
			add_action('wp_ajax_sn_el_truncate_log', array(__CLASS__, 'truncate_log'));

			// check and set default settings
			self::default_settings(false);

			// register settings
			add_action('admin_init', array(__CLASS__, 'register_settings'));

			// remove old logs
			self::prune_log(false);
		} // if admin

		// hook on everything
		add_action('all', array(__CLASS__, 'watch_actions'), 9, 10);

		if ( self::syslogactive() ) {
			add_action( 'pre_http_request', array( __CLASS__, 'log_pre_http_request' ), 10, 3 );
		}

}// init

// Returns the table name to be looked up outside the class
static function return_table_name() {
	if ( defined( 'WF_SN_EL_TABLE' ) ) {
		return constant( 'WF_SN_EL_TABLE' );
	}
	return false;
}

/**
 * Checks if syslogging is activated
 * @return bool [description]
 */
static function syslogactive() {
	$options = get_option(WF_SN_EL_OPTIONS_KEY);
	if ( isset( $options['rotatingsyslog'] ) && (in_array($options['rotatingsyslog'], array(7,30))) ) {
		return true;
	}
	return false;
}

// only loaded when syslog is activated
static function log_pre_http_request( $false, $args, $url ) {
	global $wpdb;

	if ( false !== strpos( $url, 'doing_wp_cron' ) ) {
		return;
	}
// @todo - use internal log function?

	$options = get_option(WF_SN_EL_OPTIONS_KEY);
	if (!in_array($options['rotatingsyslog'], array(7,30))) {
		$rotateDays = 0; // defaults to 0
	} else {
		$rotateDays = $options['rotatingsyslog'];
	}

	$upload_dir = wp_upload_dir();
	$secninjaUploadDir = $upload_dir['basedir'] . '/security-ninja/logs/';
	$log = new Logger('Security Ninja');
	$handler = new \Monolog\Handler\RotatingFileHandler($secninjaUploadDir.'security-ninja.log', $rotateDays, Monolog\Logger::DEBUG);
	$handler->setFilenameFormat('{date}-{filename}', 'Y-m-d');
	$log->pushHandler($handler);
	$details = array('url' => $url);
	$log->notice('pre_http_request', $details );
	return $false;
}


	// enqueue CSS and JS scripts on plugin's admin page
static function enqueue_scripts() {
	if (wf_sn::is_plugin_page()) {
		$plugin_url = plugin_dir_url(__FILE__);

		wp_enqueue_script('sn-el-datatables', $plugin_url . 'js/jquery.dataTables.min.js', array('jquery'), wf_sn::$version, true);
		wp_enqueue_style('sn-el-datatables', $plugin_url . 'css/jquery.dataTables.min.css', array(), wf_sn::$version);
		wp_enqueue_script('sn-el', $plugin_url . 'js/wf-sn-el.js', array('jquery'), wf_sn::$version, true);
		wp_enqueue_style('sn-el', $plugin_url . 'css/wf-sn-el.css', array(), wf_sn::$version);
		} // if
	} // enqueue_scripts


	// add new tab
	static function sn_tabs($tabs) {
		$logger_tab = array(
			'id' => 'sn_logger',
			'class' => '',
			'label' => 'Events',
			'callback' => array(__CLASS__, 'logger_page')
		);
		$done = false;

		for ($i = 0; $i < sizeof($tabs); $i++) {
			if ($tabs[$i]['id'] == 'sn_logger') {
				$tabs[$i] = $logger_tab;
				$done = true;
				break;
			}
		} // for

		if (!$done) {
			$tabs[] = $logger_tab;
		}

		return $tabs;
	} // sn_tabs


	// set default options
	static function default_settings($force = false) {
		$defaults = array(
			'rotatingsyslog' => 0,
			'retention' => 'day-7',
			'email_reports' => '',
			'email_modules' => array('users', 'menus', 'file_editor', 'taxonomies', 'media', 'posts', 'widgets', 'installer', 'comments', 'settings', 'security_ninja'),
			'email_to' => get_bloginfo('admin_email'),
			'last_reported_event' => 0
		);

		$options = get_option(WF_SN_EL_OPTIONS_KEY);

		if ($force || !$options || !isset($options['retention'])) {
			update_option(WF_SN_EL_OPTIONS_KEY, $defaults);
		}
	} // default_settings


	// sanitize settings on save
	static function sanitize_settings($values) {
		$old_options = get_option(WF_SN_EL_OPTIONS_KEY);
		$old_options['rotatingsyslog'] = 0;
		foreach ($values as $key => $value) {
			switch ($key) {
				case 'rotatingsyslog':
				case 'retention':
				case 'email_reports':
				case 'email_to':
				$values[$key] = trim($value);
				break;
			} // switch
		} // foreach

		if ($values['email_to'] && !is_email($values['email_to'])) {
			add_settings_error('wf-sn-el', 'wf-sn-el-save', __('Please use a valid email', WF_SN_TEXT_DOMAIN), 'error');
		}
		self::check_var_isset($values, array('email_modules' => array()));

		return array_merge($old_options, $values);
	} // sanitize_settings


	// all settings are saved in one option key
	static function register_settings() {
		register_setting(WF_SN_EL_OPTIONS_KEY, WF_SN_EL_OPTIONS_KEY, array(__CLASS__, 'sanitize_settings'));
	} // register_settings


	// process selected actions / filters
	static function watch_actions() {
		$users = array('user_register',
			'wp_login_failed',
			'profile_update',
			'password_reset',
			'retrieve_password',
			'set_logged_in_cookie',
			'clear_auth_cookie',
			'delete_user',
			'deleted_user',
			'set_user_role');
		$menus = array('wp_create_nav_menu',
			'wp_update_nav_menu',
			'delete_nav_menu');
		$file_editor = array('wp_redirect');
		$taxonomies = array('created_term',
			'delete_term',
			'edited_term');
		$media = array('add_attachment',
			'edit_attachment',
			'delete_attachment',
			'wp_save_image_editor_file');
		$posts = array('transition_post_status',
			'deleted_post');
		$widgets = array('update_option_sidebars_widgets',
			'wp_ajax_widgets-order',
			'widget_update_callback');
		$installer = array('upgrader_process_complete',
			'activate_plugin',
			'deactivate_plugin',
			'switch_theme',
			'_core_updated_successfully');
		$comments = array('comment_flood_trigger',
			'wp_insert_comment',
			'edit_comment',
			'delete_comment',
			'trash_comment',
			'untrash_comment',
			'spam_comment',
			'unspam_comment',
			'transition_comment_status',
			'comment_duplicate_trigger');
		$settings = array('whitelist_options',
			'update_site_option',
			'update_option_permalink_structure',
			'update_option_category_base',
			'update_option_tag_base');
		$security_ninja = array('security_ninja_done_testing',
			'security_ninja_scheduled_scanner_done_cron',
			'security_ninja_core_scanner_done_scanning',
			'security_ninja_remote_access',
			'security_ninja_malware_scanner_done_scanning');

		$args = func_get_args();
		if (in_array(current_action(), $users)) {
			wf_sn_el_modules::parse_action_users(current_action(), $args);
		} elseif (in_array(current_action(), $menus)) {
			wf_sn_el_modules::parse_action_menus(current_action(), $args);
		} elseif (in_array(current_action(), $file_editor)) {
			wf_sn_el_modules::parse_action_file_editor(current_action(), $args);
		} elseif (in_array(current_action(), $taxonomies)) {
			wf_sn_el_modules::parse_action_taxonomies(current_action(), $args);
		} elseif (in_array(current_action(), $media)) {
			wf_sn_el_modules::parse_action_media(current_action(), $args);
		} elseif (in_array(current_action(), $posts)) {
			wf_sn_el_modules::parse_action_posts(current_action(), $args);
		} elseif (in_array(current_action(), $widgets)) {
			wf_sn_el_modules::parse_action_widgets(current_action(), $args);
		} elseif (in_array(current_action(), $installer)) {
			wf_sn_el_modules::parse_action_installer(current_action(), $args);
		} elseif (in_array(current_action(), $comments)) {
			wf_sn_el_modules::parse_action_comments(current_action(), $args);
		} elseif (in_array(current_action(), $settings)) {
			wf_sn_el_modules::parse_action_settings(current_action(), $args);
		} elseif (in_array(current_action(), $security_ninja)) {
			wf_sn_el_modules::parse_action_security_ninja(current_action(), $args);
		}
	} // watch_actions


	// truncate event log table
	static function truncate_log() {
		global $wpdb;
		$options = get_option(WF_SN_EL_OPTIONS_KEY);

		$options['last_reported_event'] = 0;
		update_option(WF_SN_EL_OPTIONS_KEY, $options);

		$wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . WF_SN_EL_TABLE);

		die('1');
	} // truncate_log


	// prune events log table
	static function prune_log($force = false) {
		global $wpdb;
		$options = get_option(WF_SN_EL_OPTIONS_KEY);

		// clean on 10% of requests
		if (!$force && rand(0, 100) < 90) {
			return false;
		}

		if (!$options['retention']) {
			return false;
		} elseif (substr($options['retention'], 0, 3) == 'cnt') {
			$tmp = explode('-', $options['retention']);
			$tmp = (int) $tmp[1];

			$id = $wpdb->get_var('SELECT id FROM ' . $wpdb->prefix . WF_SN_EL_TABLE . ' ORDER BY id DESC LIMIT ' . $tmp . ', 1');
			if ($id) {
				$wpdb->query('DELETE FROM ' . $wpdb->prefix . WF_SN_EL_TABLE . ' WHERE id < ' . $id);
			}
		} else {
			$tmp = explode('-', $options['retention']);
			$tmp = (int) $tmp[1];
			$wpdb->query('DELETE FROM ' . $wpdb->prefix . WF_SN_EL_TABLE . ' WHERE timestamp < DATE_SUB(NOW(), INTERVAL ' . $tmp . ' DAY)');
		}

		return true;
	} // prune_log


	// send email reports based on user's preferences
	static function send_email_reports($last_id) {
		global $wpdb;
		$options = get_option(WF_SN_EL_OPTIONS_KEY);
		$body = '';

		if (!$options['email_reports'] || !$last_id) {
			return false;
		}

		if ($last_id - $options['last_reported_event'] >= (int) $options['email_reports']) {
			$modules = '';
			if ($options['email_modules']) {
				$modules = " and module IN('" . implode("', '", $options['email_modules']). "') ";
			}

			$events = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . WF_SN_EL_TABLE . ' WHERE id > ' . $options['last_reported_event'] . $modules . ' ORDER BY id DESC LIMIT ' . $options['email_reports']);

			if (!$events || sizeof($events) != (int) $options['email_reports']) {
				return;
			}

			$options['last_reported_event'] = $events[0]->id;
			update_option(WF_SN_EL_OPTIONS_KEY, $options);

			$headers = array('Content-Type: text/html; charset=UTF-8');
			$body .= '<b>Recent events on ' . get_bloginfo('name') . ':</b> (<a href="' . admin_url('tools.php?page=wf-sn#sn_logger') . '">more details are available in WordPress admin</a>)<br>';
			$body .= '<ul>';
			foreach ($events as $event) {
				if ($event->user_id) {
					$user_info = get_userdata($event->user_id);
					if ($user_info) {
						$user = '<b>' . $user_info->user_nicename . '</b>';
						$user .= ' (' . implode(', ', $user_info->roles) . ')';
					} else {
						$user = '<b>user deleted</b>';
					}
				} else {
					if (substr($event->user_agent, 0, 10) == 'WordPress/') {
						$user = '<b>WP cron</b>';
					} else {
						$user = '<b>anonymous user</b>';
					}
				}
				$module = str_replace(array('_', '-', 'ninja'), array(' ', ' ', 'Ninja'), ucfirst($event->module));

				$body .= '<li>';
				$body .= $event->description;
				$body .= ' On ' . date(get_option('date_format'), strtotime($event->timestamp)) . ' @ ' . date(get_option('time_format'), strtotime($event->timestamp));
				$body .= ' by ' . $user;
				$body .= ' in ' . $module . ' module.';
				$body .= '</li>';
			}
			$body .= '</ul>';
			$body .= '<p>Security Ninja - Events Logger email report settings can be adjusted in <a href=' . admin_url('tools.php?page=wf-sn#sn_logger') . '>WordPress admin</a>.</p>';

			return wp_mail($options['email_to'], sprintf( __( '[%s] Security Ninja - Events Logger report' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ), $body, $headers);
		}
	} // send_email_reports


	// display results
	static function logger_page() {

		global $wpdb;
		$options = get_option(WF_SN_EL_OPTIONS_KEY);

		if (!isset($options['rotatingsyslog'])) $options['rotatingsyslog'] = '';

 // get events
		$events = $wpdb->get_results('SELECT id, timestamp, ip, user_agent, user_id, module, action, description FROM ' . $wpdb->prefix . WF_SN_EL_TABLE . ' ORDER BY id DESC LIMIT 2000');
		echo '<h3>'.__('Events Logger', WF_SN_TEXT_DOMAIN).'</h3>';

		global $secnin_fs;
		if (($secnin_fs->is_registered()) && (!$secnin_fs->is_pending_activation()) && ( !wf_sn_wl::is_active() ) ) {
			?>
			<p><a href="#" data-beacon-article="5d40db962c7d3a2ec4bfa2d6"><?php _e('Need help? How to use the event log.', WF_SN_TEXT_DOMAIN); ?></a></p>
			<?php
		}


		if ($events) {
			echo '<table class="wp-list-table widefat striped" id="sn-el-datatable" cellspacing="0">';
			echo '<thead>';
			echo '<tr>';
			echo '<th id="sn-el-date" class="column-primary">'.__('Time', WF_SN_TEXT_DOMAIN).'</th>';
			echo '<th id="sn-el-event">'.__('Event', WF_SN_TEXT_DOMAIN).'</th>';
			echo '<th id="sn-el-user">'.__('User', WF_SN_TEXT_DOMAIN).'</th>';
			echo '<th id="sn-el-ip">'.__('Details', WF_SN_TEXT_DOMAIN).'</th>';
			echo '<th id="sn-el-module">'.__('Module', WF_SN_TEXT_DOMAIN).'</th>';
			echo '<th id="sn-el-action">'.__('Action', WF_SN_TEXT_DOMAIN).'</th>';
			echo '</tr>';
			echo '</thead>';

			echo '<tbody>';
			foreach ($events as $event) {
				if ($event->user_id) {
					$user_info = get_userdata($event->user_id);
					if ($user_info) {
						$user = get_avatar($user_info->user_email, 20);
						$user .= '<a title="Filter events by this user" href="#" class="wf_sn_el_filter">' . $user_info->user_nicename . '</a><br>';
						$user .= '<small>' . implode(', ', $user_info->roles) . '</small>';
					} else {
						$user = get_avatar('nobody', 20);
						$user .= '<a title="Filter events by this user" href="#" class="wf_sn_el_filter">user deleted</a>';
					}
				} else {
					$user = get_avatar('nobody', 20);
					$user .= '<a title="Filter events by this user" href="#" class="wf_sn_el_filter">anonymous</a>';
				}

				$module = str_replace(array('_', '-', 'ninja'), array(' ', ' ', 'Ninja'), ucfirst($event->module));


				$ua_info = parse_user_agent($event->user_agent);

				$browser = '';

				if ( isset( $ua_info['browser'] ) ) {

					$browser = $ua_info['browser'] . ' v' . $ua_info['version'] . ' - ' . $ua_info['platform'];

					if ( 'WordPress' == $ua_info['browser'] ) {
						$browser = 'Internal WordPress Cron job';
					}

				}

/*
				} elseif (substr($event->user_agent, 0, 10) == 'WordPress/') {
					$browser = 'WP cron';
				} else {
					$browser = 'unknown';
				}
*/
				echo '<tr>';
				echo '<td class="column-primary"><b>' . human_time_diff(strtotime($event->timestamp), current_time('timestamp')) . ' ago</b><br><a title="Filter events on this date" href="#" class="wf_sn_el_filter">' . date(get_option('date_format'), strtotime($event->timestamp)) . '</a> @ ' . date(get_option('time_format'), strtotime($event->timestamp));
				echo '<button type="button" class="toggle-row"><span class="screen-reader-text">'.__('Show details', WF_SN_TEXT_DOMAIN ).'</span></button>';
				echo '</td>';
				echo '<td data-colname="Event">' . $event->description . '</td>';
				echo '<td>' . $user . '</td>';
				echo '<td data-colname="User"><a title="'.__('Filter events with this IP', WF_SN_TEXT_DOMAIN).'" href="#" class="wf_sn_el_filter">' . $event->ip . '</a><a title="'.__('View detailed information about the IP address', WF_SN_TEXT_DOMAIN).'" href="http://www.infobyip.com/ip-' . $event->ip . '.html" target="_blank"><div class="dashicons dashicons-location"></div></a><br>' . $browser . '</td>';
				echo '<td data-colname="Module"><a href="#" title="'.__('Filter events with this module', WF_SN_TEXT_DOMAIN).'" class="wf_sn_el_filter">' . $module . '</a></td>';
				echo '<td data-colname="Action"><a href="#" title="'.__('Filter events with this action', WF_SN_TEXT_DOMAIN).'" class="wf_sn_el_filter">' . $event->action . '</a></td>';
				echo '</tr>';
			} // foreach event

			echo '</tbody>';
			echo '</table>';
		} else {
			echo '<p>There are currently no events in the log. Update Logger\'s settings for instance to create an event.</p>';
		}



		$syslog_settings =   array();
		$syslog_settings[] = array('val' => '', 'label' => __( 'Do not save syslogs', WF_SN_TEXT_DOMAIN ) );
		$syslog_settings[] = array('val' => '7', 'label' => __( 'Keep a maximum of 7 days', WF_SN_TEXT_DOMAIN ) );
		$syslog_settings[] = array('val' => '30', 'label' => __( 'Keep a maximum of 30 days', WF_SN_TEXT_DOMAIN ) );



		$retention_settings =   array();
		$retention_settings[] = array('val' => 'cnt-100', 'label' => __( 'Keep a maximum of 100 logged events', WF_SN_TEXT_DOMAIN ) );
		$retention_settings[] = array('val' => 'cnt-200', 'label' => __( 'Keep a maximum of 200 logged events', WF_SN_TEXT_DOMAIN ) );
		$retention_settings[] = array('val' => 'cnt-500', 'label' => __( 'Keep a maximum of 500 logged events', WF_SN_TEXT_DOMAIN ) );
		$retention_settings[] = array('val' => 'cnt-1000', 'label' => __( 'Keep a maximum of 1000 logged events', WF_SN_TEXT_DOMAIN ) );
		$retention_settings[] = array('val' => 'day-7', 'label' => __( 'Keep event logs for up to 7 days', WF_SN_TEXT_DOMAIN ) );
		$retention_settings[] = array('val' => 'day-15', 'label' => __( 'Keep event logs for up to 15 days', WF_SN_TEXT_DOMAIN ) );
		$retention_settings[] = array('val' => 'day-30', 'label' => __( 'Keep event logs for up to 30 days', WF_SN_TEXT_DOMAIN ) );
		$retention_settings[] = array('val' => 'day-45', 'label' => __( 'Keep event logs for up to 45 days', WF_SN_TEXT_DOMAIN ) );

		$email_reports_settings = array();
		$email_reports_settings[] = array('val' => '0', 'label' => __( 'Do not email any reports', WF_SN_TEXT_DOMAIN ) );
		$email_reports_settings[] = array('val' => '1', 'label' => __( 'Send an email for every single event (not recommended)', WF_SN_TEXT_DOMAIN ) );
		$email_reports_settings[] = array('val' => '2', 'label' => __( 'Send one email for every 2 events', WF_SN_TEXT_DOMAIN ) );
		$email_reports_settings[] = array('val' => '3', 'label' => __( 'Send one email for every 3 events', WF_SN_TEXT_DOMAIN ) );
		$email_reports_settings[] = array('val' => '5', 'label' => __( 'Send one email for every 5 events', WF_SN_TEXT_DOMAIN ) );
		$email_reports_settings[] = array('val' => '10', 'label' => __( 'Send one email for every 10 events', WF_SN_TEXT_DOMAIN ) );
		$email_reports_settings[] = array('val' => '20', 'label' => __( 'Send one email for every 20 events', WF_SN_TEXT_DOMAIN ) );

		$modules = array();
		$modules[] = array('val' => 'comments', 'label' => 'Comments');
		$modules[] = array('val' => 'file_editor', 'label' => 'File editor');
		$modules[] = array('val' => 'installer', 'label' => 'Installer');
		$modules[] = array('val' => 'media', 'label' => 'Media');
		$modules[] = array('val' => 'menus', 'label' => 'Menus');
		$modules[] = array('val' => 'posts', 'label' => 'Posts');
		$modules[] = array('val' => 'security_ninja', 'label' => 'Security Ninja');
		$modules[] = array('val' => 'settings', 'label' => 'Settings');
		$modules[] = array('val' => 'taxonomies', 'label' => 'Taxonomies');
		$modules[] = array('val' => 'users', 'label' => 'Users');
		$modules[] = array('val' => 'widgets', 'label' => 'Widgets');

		echo '<div id="wf-sn-el-options-container">';

		echo '<h3>'.__('Settings', WF_SN_TEXT_DOMAIN ).'</h3>';

		echo '<form action="options.php" method="post">';

		settings_fields('wf_sn_el');

		echo '<table class="form-table"><tbody>';

		echo '<tr valign="top">
		<th scope="row"><label for="email_reports">'.__('Email Reports', WF_SN_TEXT_DOMAIN).'</label></th>
		<td><select id="email_reports" name="wf_sn_el[email_reports]">';
		self::create_select_options($email_reports_settings, $options['email_reports']);
		echo '</select>';
		echo '<br /><span>Email reports with a specified number of latest events can be automatically emailed to alert the admin of any suspicious events.<br>Default: do not email any reports.</span>';
		echo '</td></tr>';

		echo '<tr valign="top">
		<th scope="row"><label for="email_modules">Modules Included in Email Reports</label></th>
		<td><select size="6" id="email_modules" multiple="multiple" name="wf_sn_el[email_modules][]">';
		self::create_select_options($modules, $options['email_modules']);
		echo '</select>';
		echo '<br /><span>If you don\'t want to receive event reports from specific modules deselect them. Default: all modules.</span>';
		echo '</td></tr>';

		echo '<tr valign="top">
		<th scope="row"><label for="email_to2">Email Recipient</label></th>
		<td><input type="text" class="regular-text" id="email_to2" name="wf_sn_el[email_to]" value="' . $options['email_to'] . '" />';
		echo '<br><span>Email address of the person (usually the site admin) who\'ll receive the email reports. Default: WP admin email.</span>';
		echo '</td></tr>';

		echo '<tr valign="top">
		<th scope="row"><label for="retention">Log Retention Policy</label></th>
		<td><select id="retention" name="wf_sn_el[retention]">';
		self::create_select_options($retention_settings, $options['retention']);
		echo '</select>';
		echo '<p>In order to preserve disk space logs are automatically deleted based on this option. Default: keep logs for 7 days.</p>';
		echo '</td></tr>';
		?>


		<tr valign="top">
			<th scope="row">Rotating syslog</th>
			<td>

					<?php

if (!isset($options['rotatingsyslog'])) {
	$options['rotatingsyslog'] = '';
}
?>
				<select id="rotatingsyslog" name="wf_sn_el[rotatingsyslog]">
					<?php ;
				self::create_select_options($syslog_settings, $options['rotatingsyslog']); ?></select>
				<p><label for="rotatingsyslog">Log files are stored in <code>uploads/security-ninja/logs/</code> - Older logs are automatically deleted.</label></p>
			</td></tr>
			<?php
			echo '<tr valign="top">
			<th scope="row"><label for="">'.__('Miscellaneous', WF_SN_TEXT_DOMAIN).'</label></th>
			<td><input type="button" value="'.__('Delete all log entries', WF_SN_TEXT_DOMAIN).'" class="button-secondary button" id="sn-el-truncate" />';
			echo '<br><span>'.__('Delete all logged events in the database. Please note that there is NO undo for this action.', WF_SN_TEXT_DOMAIN).'</span>';
			echo '</td></tr>';

			echo '<tr valign="top"><td colspan="2">';
			echo '<p class="submit"><input type="submit" value="'.__('Save Changes', WF_SN_TEXT_DOMAIN).'" class="button-primary input-button" name="Submit" /></p>';
			echo '</td></tr>';
			echo '</table>';
			echo '</form>';
			echo '</div>';

	} // events_page


	// helper function for creating dropdowns
	static function create_select_options($options, $selected = null, $output = true) {
		$out = "\n";

		if(!is_array($selected)) {
			$selected = array($selected);
		}

		foreach ($options as $tmp) {
			if (in_array($tmp['val'], $selected)) {
				$out .= "<option selected=\"selected\" value=\"{$tmp['val']}\">{$tmp['label']}&nbsp;</option>\n";
			} else {
				$out .= "<option value=\"{$tmp['val']}\">{$tmp['label']}&nbsp;</option>\n";
			}
		} // foreach

		if ($output) {
			echo $out;
		} else {
			return $out;
		}
	} // create_select_options


	// helper function for $_POST checkbox handling
	static function check_var_isset(&$values, $variables) {
		foreach ($variables as $key => $value) {
			if (!isset($values[$key])) {
				$values[$key] = $value;
			}
		}
	} // check_var_isset

	// activate plugin
	static function activate() {
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$table_name = $wpdb->prefix . WF_SN_EL_TABLE;
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
			`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`timestamp` datetime NOT NULL,
			`ip` varchar(39) NOT NULL,
			`user_agent` varchar(255) NOT NULL,
			`user_id` int(10) unsigned NOT NULL,
			`module` varchar(32) NOT NULL,
			`action` varchar(64) NOT NULL,
			`description` text NOT NULL,
			`raw_data` blob NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		dbDelta($sql);
	}

	self::default_settings(false);
	} // activate


	// clean-up when deactivated
	static function deactivate() {
		global $wpdb;

		delete_option(WF_SN_EL_OPTIONS_KEY);
		$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . WF_SN_EL_TABLE);
	} // deactivate
} // wf_sn_el class


// hook everything up
add_action('plugins_loaded', array('wf_sn_el', 'init'));

// setup environment when activated
register_activation_hook(WF_SN_BASE_FILE, array('wf_sn_el', 'activate'));

// when deativated, clean up
register_deactivation_hook(WF_SN_BASE_FILE, array('wf_sn_el', 'deactivate'));
