<?php
if (!function_exists('add_action')) {
	die('Please don\'t open this file directly!');
}


define('WF_SN_SS_OPTIONS_KEY', 'wf_sn_ss');
define('WF_SN_SS_CRON', 'wf_sn_ss_cron');
define('WF_SN_SS_TABLE', 'wf_sn_ss_log');
define('WF_SN_SS_LOG_LIMIT', 50);


class wf_sn_ss {
	// earlier hook for problematic filters
	static function plugins_loaded() {
		add_filter('cron_schedules', array(__CLASS__, 'cron_intervals'));
	} // plugins_loaded

	// init plugin
	static function init() {
		// does the user have enough privilages to use the plugin?
		if (is_admin()) {
			// add tab to Security Ninja tabs
			add_filter('sn_tabs', array(__CLASS__, 'sn_tabs'));

			// enqueue scripts
			add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));

			// register ajax endpoints
			add_action('wp_ajax_sn_ss_truncate_log', array(__CLASS__, 'truncate_log'));
			add_action('wp_ajax_sn_ss_sn_details', array(__CLASS__, 'dialog_sn_details'));
			add_action('wp_ajax_sn_ss_cs_details', array(__CLASS__, 'dialog_cs_details'));
			add_action('wp_ajax_sn_ss_cs_test', array(__CLASS__, 'do_cron_task_ajax'));

			// check and set default settings
			self::default_settings(false);

			// settings registration
			add_action('admin_init', array(__CLASS__, 'register_settings'));

			// add custom text for GUI overlay
			add_action('sn_overlay_content', array(__CLASS__, 'overlay_content'));
		} // if admin

		// register cron action
		add_action('wf_sn_ss_cron', array(__CLASS__, 'do_cron_task'));
	} // init


	// enqueue CSS and JS scripts on plugin's pages
	static function enqueue_scripts() {

		if (wf_sn::is_plugin_page()) {
			wp_enqueue_script('sn-ss', WF_SN_PLUGIN_URL. 'modules/scheduled-scanner/js/wf-sn-ss.js', array(), wf_sn::$version, true);

		}

	}


	// add new tab
	static function sn_tabs($tabs) {
		$schedule_tab = array('id' => 'sn_schedule', 'class' => '', 'label' => __('Scheduler', WF_SN_TEXT_DOMAIN) , 'callback' => array(__CLASS__, 'schedule_page'));
		$done = 0;

		for ($i = 0; $i < sizeof($tabs); $i++) {
			if ($tabs[$i]['id'] == 'sn_schedule') {
				$tabs[$i] = $schedule_tab;
				$done = 1;
				break;
			}
		} // for

		if (!$done) {
			$tabs[] = $schedule_tab;
		}

		return $tabs;
	} // sn_tabs


	// add custom message to overlay
	static function overlay_content() {
		echo '<div id="sn-scheduled-scanner" style="display: none;">';
		echo '<h3>'.__('Security Ninja is testing Scheduled Scanner settings.', WF_SN_TEXT_DOMAIN).'<br/>'.__('It will only take a few moments...', WF_SN_TEXT_DOMAIN).'</h3>';
		echo '</div>';
	} // overlay_content


	// set default options
	static function default_settings($force = false) {
		$defaults = array('main_setting' => '0',
			'scan_schedule' => 'twicedaily',
			'email_report' => '2',
			'email_to' => get_bloginfo('admin_email'));

		$options = get_option(WF_SN_SS_OPTIONS_KEY);

		if ($force || !$options || !$options['scan_schedule']) {
			update_option(WF_SN_SS_OPTIONS_KEY, $defaults);
		}
	} // default_settings


	// sanitize settings on save
	static function sanitize_settings($values) {
		$old_options = get_option(WF_SN_SS_OPTIONS_KEY);

		foreach ($values as $key => $value) {
			switch ($key) {
				case 'main_setting':
				case 'scan_schedule':
				case 'email_report':
				case 'email_to':
				$values[$key] = trim($value);
				break;
			} // switch
		} // foreach

		if ($values['email_to'] && !is_email($values['email_to'])) {
			add_settings_error('wf-sn-ss', 'wf-sn-ss-save', __('Please use a valid email address', WF_SN_TEXT_DOMAIN), 'error');
		}

		self::setup_cron($values);

		return array_merge($old_options, $values);
	} // sanitize_settings


	// register cron event
	static function setup_cron($options = false) {
		if (!$options) {
			$options = get_option(WF_SN_SS_OPTIONS_KEY);
		}

		wp_clear_scheduled_hook(WF_SN_SS_CRON);

		if ($options['main_setting'] && $options['scan_schedule']) {
			if (!wp_next_scheduled(WF_SN_SS_CRON)) {
				wp_schedule_event(time() + 300, $options['scan_schedule'], WF_SN_SS_CRON);
			}
		}
	} // setup_cron


	// add additional cron intervals
	static function cron_intervals($schedules) {
		$schedules['weekly'] = array(
			'interval' => DAY_IN_SECONDS * 7,
			'display' => __( 'Once Weekly', WF_SN_TEXT_DOMAIN ) );
		$schedules['monthly'] = array(
			'interval' => DAY_IN_SECONDS * 30,
			'display' => __('Once Monthly',WF_SN_TEXT_DOMAIN ) );
		$schedules['2days'] = array(
			'interval' => DAY_IN_SECONDS * 2,
			'display' => __('Once in Two Days',WF_SN_TEXT_DOMAIN ) );
		return $schedules;
	} // cron_intervals


	// runs cron tast manually
	static function do_cron_task_ajax() {


		self::do_cron_task();
		die('1');
	} // do_cron_task_ajax


	// core cron function
	static function do_cron_task() {
		global $wpdb;
		$options = get_option(WF_SN_SS_OPTIONS_KEY);
		$sn_change = $cs_change = 0;
		$sn_change_details = $cs_change_details = array();
		$sn_results = $cs_results = 0;
		$start_time = microtime(true);

		$old = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . WF_SN_SS_TABLE . ' ORDER BY id DESC LIMIT 1');


		if ($options['main_setting'] == '1' || $options['main_setting'] == '3') {

			$sn_results = wf_sn::run_all_tests(true);

			if (!$old) {
				$sn_change = 1;
			} else {
				$old_sn_results = unserialize($old->sn_results);

				if ($sn_results['test'] != $old_sn_results['test']) {
					$sn_change = 1;

					foreach ( $sn_results['test'] as $snname => $testresult ) {
						if ( $testresult <> $old_sn_results['test'][$snname] ) {
							$changes = array();
							$changes['title'] = $testresult['title'];
							if ( $testresult['status'] <> $old_sn_results['test'][$snname]['status'] ) {
								$changes['status_new'] = $testresult['status'];
								$changes['status_old'] = $old_sn_results['test'][$snname]['status'];

								if ( $changes['status_new'] > $changes['status_old'] ) {
									$changes['progress'] = 'good';
								}
								else {
									$changes['progress'] = 'bad';
								}
							}

							if ( $testresult['score'] <> $old_sn_results['test'][$snname]['score'] ) {
								$changes['score_new'] = $testresult['score'];
								$changes['score_old'] = $old_sn_results['test'][$snname]['score'];
							}

							$changes[ 'msg' ] = $testresult[ 'msg' ];

							$sn_change_details[$snname] = $changes;

						}
					}
					// @todo - figure out WHAT exactly has changed and SHOW IT :-)
				}
			}
		}

		// Core Scanner
		if ($options['main_setting'] == '2' || $options['main_setting'] == '3') {
			$cs_results = wf_sn_cs::scan_files(true);
			if (!$old) {
				$cs_change = 1;
			} else {
				$old_cs_results = unserialize($old->cs_results);
				unset($cs_results['last_run'], $old_cs_results['last_run'], $cs_results['run_time'], $old_cs_results['run_time']);
				if ($cs_results != $old_cs_results) {
					$cs_change = 1;
				}
			}
		}

		// write results in database
		$date = date('Y-m-d H:i:s', current_time('timestamp'));
		$query = $wpdb->prepare('INSERT INTO ' . $wpdb->prefix . WF_SN_SS_TABLE .
			' (runtime, timestamp, sn_results, cs_results, sn_change, cs_change)
			VALUES (%s, %s, %s, %s, %s, %s)',
			microtime(true) - $start_time, $date,
			serialize($sn_results), serialize($cs_results), $sn_change, $cs_change);
		$wpdb->query($query);

		// send report email
		if ($options['email_report']) {
			if ($options['email_report'] == '2' && !$sn_change && !$cs_change) {
				// no change - don't send
			} else {
				$subject = __('Here is your current security status for', WF_SN_TEXT_DOMAIN).' '. get_home_url();
				$body = 'Scan was run on ' . date(get_option('date_format') . ' @ ' . get_option('time_format')) . "\r\n";
				$body .= 'Run time: ' . round(microtime(true) - $start_time, 1) . " sec \r\n";

				$body .= 'Plugin version: ' . wf_sn::get_plugin_version(). "\r\n";

				if (!$sn_results) {
					$body .= "\r\n";
					$body .= __('Security Testing Results: Test were not run.', WF_SN_TEXT_DOMAIN)  . "\r\n";
				} else {
					if ($sn_change) {
						$body .= "\r\n";
						$body .= '<strong>'.__('Security Testing Results: Results have changed since last scan.', WF_SN_TEXT_DOMAIN).'</strong>' . "\r\n";

						if (is_array( $sn_change_details ) ) {
							foreach ($sn_change_details as $scd ) {
								$body .= "\r\n";
								$body .= '<strong>'.$scd['title'].'</strong>' . "\r\n";
								$body .= 'Result: '.$scd['msg'].'' . "\r\n";
							}
						}
					} else {
						$body .= "\r\n";
						$body .= __('Security Testing Results: No changes since last scan.', WF_SN_TEXT_DOMAIN)  . "\r\n";
					}
				}
				if (!$cs_results) {
					$body .= "\r\n";
					$body .= __('Core Scanner results: Test were not run.', WF_SN_TEXT_DOMAIN)  . "\r\n";
				} else {
					if ($cs_change) {
						$body .= "\r\n";
						$body .= '<strong>'.__('Core Scanner results: Results have changed since last scan.', WF_SN_TEXT_DOMAIN) .'</strong>' . "\r\n";



					} else {
						$body .= "\r\n";
						$body .= __('Core Scanner results: No changes since last scan.', WF_SN_TEXT_DOMAIN)  . "\r\n";
					}
				}
				$body .= "\r\n";

				$dashboardlink = admin_url('?page=wf-sn');
				$dashboardlinkanchor = __('Security Ninja Dashboard',  WF_SN_TEXT_DOMAIN);

				$emailintrotext = __('Report from a scheduled scan of your website.', WF_SN_TEXT_DOMAIN);
				$emailtitle = $subject; // @todo -

				$dialog_cs_details = self::dialog_cs_details(true);

				$body .= $dialog_cs_details;

				$body .= "\r\n";
				$body .= __('See details here:', WF_SN_TEXT_DOMAIN).' <a href="' . admin_url('admin.php?page=wf-sn#sn_tests').'" target="_blank">'.admin_url('admin.php?page=wf-sn#sn_schedule').'</a>';

				$my_replacements = array (
					'%%emailintrotext%%'    => $emailintrotext, // TODO
					'%%websitedomain%%' => site_url(),
					'%%dashboardlink%%' => $dashboardlink,
					'%%dashboardlinkanchor%%' => $dashboardlinkanchor,
					'%%secninlogourl%%' => WF_SN_PLUGIN_URL.'images/security-ninja-logo.png',
					'%%emailtitle%%' => $emailtitle, // TODO
					'%%sentfromtext%%' => 'This email was sent by WP Security Ninja from '.wf_sn_cf::url_to_domain( site_url() ),
					'%%emailcontent%%' => nl2br($body)
				);

				// inserts the whitelabel name
				if ( class_exists( 'wf_sn_wl' ) ) {
					if ( wf_sn_wl::is_active( ) ) {
						$pluginname = wf_sn_wl::get_new_name();
						$my_replacements['%%sentfromtext%%'] = 'This email was sent by '.$pluginname.' from '.wf_sn_cf::url_to_domain( site_url() );
					}
				}


				$template_path = WF_SN_PLUGIN_DIR.'modules/scheduled-scanner/inc/email-default.php';

				$html = file_get_contents( $template_path );

				foreach ($my_replacements as $needle => $replacement) {
					$html = str_replace($needle, $replacement, $html);
				}

				$headers = array('Content-Type: text/html; charset=UTF-8');

				$sendresult = wp_mail( $options['email_to'], $subject , $html, $headers );

			}
		}

		do_action('security_ninja_scheduled_scanner_done_cron', microtime(true) - $start_time);
	} // do_cron_task





	// all settings are saved in one option key
	static function register_settings() {
		register_setting(WF_SN_SS_OPTIONS_KEY, 'wf_sn_ss', array(__CLASS__, 'sanitize_settings'));
	} // register_settings


	// display results
	static function schedule_page() {
		$main_settings =   array();
		$main_settings[] = array('val' => '0', 'label' => __('Disable scheduled scans', WF_SN_TEXT_DOMAIN));
		$main_settings[] = array('val' => '1', 'label' => __('Enable scheduled scans only for Security Testing', WF_SN_TEXT_DOMAIN));
		$main_settings[] = array('val' => '2', 'label' => __('Enable scheduled scans only for Core Scanner', WF_SN_TEXT_DOMAIN));
		$main_settings[] = array('val' => '3', 'label' => __('Enable scheduled scans for both', WF_SN_TEXT_DOMAIN));

		$scan_schedule = array();
		$tmp = wp_get_schedules();
		foreach ($tmp as $name => $details) {
			if ($name == 'twicedaily') {
				$scan_schedule[] = array('val' => $name, 'label' => $details['display'] . ' '.__('(recommended)', WF_SN_TEXT_DOMAIN));
			} else {
				$scan_schedule[] = array('val' => $name, 'label' => $details['display']);
			}
		}

		$email_reports =   array();
		$email_reports[] = array('val' => '0', 'label' => __('Never send any emails', WF_SN_TEXT_DOMAIN ) );
		$email_reports[] = array('val' => '1', 'label' => __('Send an email each time the tests run', WF_SN_TEXT_DOMAIN ) );
		$email_reports[] = array('val' => '2', 'label' => __('Send an email only when test results change', WF_SN_TEXT_DOMAIN ) );

		$options = get_option(WF_SN_SS_OPTIONS_KEY);

		echo '<div class="submit-test-container">';
		if ($options['main_setting']) {
			$tmp = wp_get_schedules();
			$tmp = '<span class="sn-ss-nochange">'.__('Scheduled scans are enabled and will run', WF_SN_TEXT_DOMAIN).' '. strtolower($tmp[$options['scan_schedule']]['display']) . '</span>';
		} else {
			$tmp = '<span class="sn-ss-change">'.__('Scheduled scans are disabled', WF_SN_TEXT_DOMAIN).'</span>';
		}
		echo '</div>';

		echo '<form action="options.php" method="post">';
		settings_fields('wf_sn_ss');

		echo '<h3 class="ss_header">Settings - ' . $tmp . '</h3>';


		global $secnin_fs;
		if (($secnin_fs->is_registered()) && (!$secnin_fs->is_pending_activation()) && (!wf_sn_wl::is_active()) ) {
			?>
			<p><a href="#" data-beacon-article="5d3a50af2c7d3a2ec4bf7091"><?php _e('Need help? How to use the scheduler.', WF_SN_TEXT_DOMAIN); ?></a></p>
			<?php
		}

		echo '<table class="form-table"><tbody>';

		echo '<tr valign="top">
		<th scope="row"><label for="main_setting">'.__('Scan Settings', WF_SN_TEXT_DOMAIN).'</label></th>
		<td><select id="main_setting" name="wf_sn_ss[main_setting]">';
		self::create_select_options($main_settings, $options['main_setting']);
		echo '</select>';
		echo '<p class="description">'.__('Depending on the Security Ninja add-ons that are active you can choose to include them in scheduled scans or not.', WF_SN_TEXT_DOMAIN).'</p>';
		echo '</td></tr>';

		echo '<tr valign="top">
		<th scope="row"><label for="scan_schedule">'.__('Scan Schedule', WF_SN_TEXT_DOMAIN).'</label></th>
		<td><select id="scan_schedule" name="wf_sn_ss[scan_schedule]">';
		self::create_select_options($scan_schedule, $options['scan_schedule']);
		echo '</select>';
		echo '<p class="description">'.__("Running the scan once a day will ensure you get a prompt notice of any problems and at the same time don't overload the server.", WF_SN_TEXT_DOMAIN).'</p>';
		echo '</td></tr>';

		echo '<tr valign="top">
		<th scope="row"><label for="email_report">'.__('Email Report', WF_SN_TEXT_DOMAIN).'</label></th>
		<td><select id="email_report" name="wf_sn_ss[email_report]">';
		self::create_select_options($email_reports, $options['email_report']);
		echo '</select>';
		echo '<p class="description">'.__('Depending on the amount of email you like to receive you can get reports for all scans or just ones when results change.', WF_SN_TEXT_DOMAIN).'</p>';
		echo '</td></tr>';

		echo '<tr valign="top">
		<th scope="row"><label for="email_to">'.__('Email Recipient', WF_SN_TEXT_DOMAIN).'</label></th>
		<td><input type="text" class="regular-text" id="email_to" name="wf_sn_ss[email_to]" value="' . $options['email_to'] . '" />';
		echo '<p class="description">'.__("Email address of the person (usually the site admin) who'll receive the email reports.", WF_SN_TEXT_DOMAIN).'</p>';
		echo '<p class="description">'.__('Separate multiple recipients with a comma ","', WF_SN_TEXT_DOMAIN).'</p>';
		echo '</td></tr>';

		echo '<tr valign="top"><td colspan="2" style="padding:0px;">';
		echo '<p class="submit"><input type="submit" value="Save Changes" class="input-button button-primary" name="Submit" />';
		echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" value="Test settings (run scan)" class="input-button gray button-secondary" id="sn-ss-test" /></p>';
		echo '</td></tr>';
		echo '</table>';
		echo '</form>';

		self::log_list();

		?>
		<p><?php _e('Please read!', WF_SN_TEXT_DOMAIN ); ?></p>
		<p><?php _e('WordPress cron function depends on site visitors to regularly run its tasks. If your site has very few visitors the tasks wont be run on a regular, predefined interval.', WF_SN_TEXT_DOMAIN ); ?></p>

		<?php
		$url = 'https://wp.tutsplus.com/articles/insights-into-wp-cron-an-introduction-to-scheduling-tasks-in-wordpress/';
		$link = sprintf( wp_kses( __( 'Wptuts+ has a great <a href="%s" target="_blank">article</a> explaining how to make sure the cron does run even if you have very few visitors.', WF_SN_TEXT_DOMAIN ), array(  'a' => array( 'href' => array() ) ) ), esc_url( $url ) );
		echo '<p>'.$link.'</p>';

		?>
		<p><?php _e("Please test the settings after changing them to ensure you're getting the emails and that the testing finish in a timely manner.", WF_SN_TEXT_DOMAIN ); ?></p>

		<?php

		echo '<div id="wf-ss-dialog"></div>';
	} // core_page


	static function log_list() {
		global $wpdb;

		$logs = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . WF_SN_SS_TABLE . " ORDER by timestamp DESC LIMIT " . WF_SN_SS_LOG_LIMIT);

		?>
		<h3><?php _e('Scan log', WF_SN_TEXT_DOMAIN); ?></h3>

		<table class="wp-list-table widefat striped" cellspacing="0" id="wf-sn-ss-log">
			<thead><tr>
				<th id="header_time" class="column-primary"><?php _e('Timestamp', WF_SN_TEXT_DOMAIN); ?></th>
				<th id="header_runtime"><?php _e('Run time', WF_SN_TEXT_DOMAIN); ?></th>
				<th id="header_sn"><?php _e('Security Tests', WF_SN_TEXT_DOMAIN); ?></th>
				<th id="header_ss"><?php _e('Core Scanner', WF_SN_TEXT_DOMAIN); ?></th>
			</tr></thead>
			<tbody>

				<?php
				if ($logs) {
					foreach ($logs as $log) {
						$tmp = strtotime($log->timestamp);
						$tmp = date(get_option('date_format') . ' @ ' . get_option('time_format') ,$tmp);
						?>
						<tr>
							<td class="column-primary log-sn-ss-timestamp"><?php echo $tmp; ?>
							<button type="button" class="toggle-row">
								<span class="screen-reader-text">show details</span>
							</button>
						</td>

						<td class="log-sn-ss-runtime" data-colname="<?php _e('Run time', WF_SN_TEXT_DOMAIN); ?>"><?php echo round($log->runtime, 1); ?> sec</td>

						<td class="log-sn-ss-sn" data-colname="<?php _e('Security Tests', WF_SN_TEXT_DOMAIN); ?>">

							<?php


							if (!unserialize($log->sn_results)) {
								echo '<i>Tests were not run.</i>';
							} else {
								if ($log->sn_change) {
									echo '<span class="sn-ss-change">'.__('The results have changed since last scan.', WF_SN_TEXT_DOMAIN).'</span>';
								} else {
									echo '<span class="sn-ss-nochange">'.__('No changes in results since last scan.', WF_SN_TEXT_DOMAIN).'</span>';
								}
								echo ' &nbsp;&nbsp;<a href="#" data-timestamp="' . $tmp . '" data-row-id="' . $log->id . '" class="ss-details-sn">'.__('View details', WF_SN_TEXT_DOMAIN).'</a>';
							}
							?>
						</td>


						<td class="log-sn-ss-ss"  data-colname="<?php _e('Core Scanner', WF_SN_TEXT_DOMAIN); ?>">
							<?php
							if (!($log->cs_results)) {
								echo '<i>'.__('Tests were not run.', WF_SN_TEXT_DOMAIN).'</i>';
							} else {
								if ($log->cs_change) {
									echo '<span class="sn-ss-change">'.__('The results have changed since last scan.', WF_SN_TEXT_DOMAIN).'</span>';
								} else {
									echo '<span class="sn-ss-nochange">'.__('No changes in results since last scan.', WF_SN_TEXT_DOMAIN).'</span>';
								}
								echo ' &nbsp;&nbsp;<a href="#" data-timestamp="' . $tmp . '" data-row-id="' . $log->id . '" class="ss-details-cs">View details</a></td>';
							}
							?>
						</td>
					</tr>
					<?php
			} // foreach $logs
		} else {
			echo '<tr><td colspan="4"><span class="no-logs">'.__('No log records found.', WF_SN_TEXT_DOMAIN).'</span></td></tr>';
		}
		echo '</tbody>';
		echo '</table>';

		echo '<br /><br />';
		echo '<p><input type="button" value="Delete all log entries" class="button button-secondary" id="wf-sn-ss-truncate-log"></p>';
	} // log_list









	// helper function for creating dropdowns
	static function create_select_options($options, $selected = null, $output = true) {
		$out = "\n";

		foreach ($options as $tmp) {
			if ($selected == $tmp['val']) {
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


	// truncate scan log table
	static function truncate_log() {
		global $wpdb;

		$wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . WF_SN_SS_TABLE);
		die('1');
	} // truncate_log


	// display dialog with SN test details
	static function dialog_sn_details() {
		global $wpdb;

		$id = (int) $_POST['row_id'];
		$result = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . WF_SN_SS_TABLE . ' WHERE id = ' . $id . ' LIMIT 1');

		if ($result->sn_results && is_array(unserialize($result->sn_results))) {
			echo '<table class="wp-list-table widefat" cellspacing="0" id="security-ninja">';
			echo '<thead><tr>';
			echo '<th class="sn-status">Status</th>';
			echo '<th>Test description</th>';
			echo '<th>Test results</th>';
			echo '<th>&nbsp;</th>';
			echo '</tr></thead>';
			echo '<tbody>';

			$tmp = unserialize($result->sn_results);
			foreach($tmp['test'] as $test_name => $details) {
				echo '<tr>
				<td class="sn-status">' . wf_sn::status($details['status']) . '</td>
				<td>' . $details['title'] . '</td>
				<td>' . $details['msg'] . '</td>
				<td class="sn-details"><a href="#' . $test_name . '" class="button action">Details, tips &amp; help</a></td>
				</tr>';
			} // foreach ($tests)

			echo '</tbody>';
			echo '<tfoot><tr>';
			echo '<th class="sn-status">'.__('Status', WF_SN_TEXT_DOMAIN).'</th>';
			echo '<th>'.__('Test Description', WF_SN_TEXT_DOMAIN).'</th>';
			echo '<th>'.__('Test Results', WF_SN_TEXT_DOMAIN).'</th>';
			echo '<th>&nbsp;</th>';
			echo '</tr></tfoot>';
			echo '</table>';
		} else {
			echo __('Unknown Error.', WF_SN_TEXT_DOMAIN);
		}

		die();
	} // dialog_sn_details


	// displays dialog with core scanner details
	static function dialog_cs_details($return = false, $hidebuttons = false) {
		global $wpdb;

		$output = '';

		if (isset($_POST['row_id'] ) ) {
			$id = (int) $_POST['row_id'];
			$result = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . WF_SN_SS_TABLE . ' WHERE id = ' . $id . ' LIMIT 1');
		}
		else {
			$result = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . WF_SN_SS_TABLE . ' ORDER BY `id` DESC LIMIT 1;');
		}

		if ( ( isset( $result->cs_results ) ) && ( is_array( unserialize( $result->cs_results ) ) ) ) {
			$results = unserialize($result->cs_results);
			$output .= '<div style="margin: 20px">';
			if ($results['changed_bad']) {
				$output .= '<div class="sn-cs-changed-bad"><h4>'.__('The following WordPress core files have been modified', WF_SN_TEXT_DOMAIN).'</h4><p>'.__('If you did not modify the following files, you should review them to make sure no malicious code is there.', WF_SN_TEXT_DOMAIN).'</p>';
				$output .= wf_sn_cs::list_files($results['changed_bad'], 0, 0);
				$output .= '</div>';
			}

			if ($results['unknown_bad']) {
				$output .= '<div class="sn-cs-changed-bad"><h4>'.__('Following files are unknown and should not be in your core folders', WF_SN_TEXT_DOMAIN).'</h4><p>'.__('These are files not included with WordPress default installation and should not be in your core WordPress folders.', WF_SN_TEXT_DOMAIN).'</p>';
				if ($return) {
					$output .= wf_sn_cs::list_files($results['unknown_bad'], false, false, false);
				}
				else {
					$output .= wf_sn_cs::list_files($results['unknown_bad'], true, false, true);
				}
				$output .= '</div>';
			}


			if ($results['missing_bad']) {
				$output .= '<div class="sn-cs-missing-bad">';
				$output .= '<h4>'.__('Following core files are missing and they should not be.', WF_SN_TEXT_DOMAIN).'</h4>';
				$output .= '<p>'.__('Missing core files my indicate a bad auto-update or they simply were not copied on the server when the site was setup.', WF_SN_TEXT_DOMAIN).'<br>'.__('If there is no legitimate reason for the files to be missing use the restore action to create them.', WF_SN_TEXT_DOMAIN).'</p>';
				if ($return) {
					$output .= wf_sn_cs::list_files($results['missing_bad'], false, false, false);
				}
				else {
					$output .= wf_sn_cs::list_files($results['missing_bad'], false, false, false);
				}
				$output .= '</div>';
			}

			if ($results['ok']) {
				$output .= '<div class="sn-cs-ok">';

				$output .= '<h4>'.sprintf( esc_html__( 'A total of <span class="sn_count">%1$s</span> files were scanned and <span class="sn_count">%2$s</span> are unmodified and safe.', WF_SN_TEXT_DOMAIN ), $results['total'], $results['ok'] ).'</h4>';
				$output .= '</div>';
			}

		} else {
			$output .= '<p>Problem loading Core Scanner Results - '.__('Undocumented error.', WF_SN_TEXT_DOMAIN).'</p>';
		}

		$output .= '</div>';
		if ( $return ) return $output;
		echo $output;
		wp_die();
	} // dialog_cs_details


	// activate plugin
	static function activate() {
		// create table
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$table_name = $wpdb->prefix . WF_SN_SS_TABLE;
		$wpdb->query('DROP TABLE IF EXISTS ' . $table_name);
		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
			`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`timestamp` datetime NOT NULL,
			`runtime` float NOT NULL,
			`sn_results` text,
			`cs_results` text,
			`sn_change` tinyint(4) NOT NULL,
			`cs_change` tinyint(4) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8";
		dbDelta($sql);
	}

	self::default_settings(false);
	} // activate


	// clean-up when deactivated
	static function deactivate() {
		global $wpdb;

		wp_clear_scheduled_hook(WF_SN_SS_CRON);
		delete_option(WF_SN_SS_OPTIONS_KEY);
		$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . WF_SN_SS_TABLE);
	} // deactivate
} // wf_sn_ss class


// hook everything up
add_action('plugins_loaded', array('wf_sn_ss', 'init'));
add_action('plugins_loaded', array('wf_sn_ss', 'plugins_loaded'));

// setup environment when activated
register_activation_hook(WF_SN_BASE_FILE, array('wf_sn_ss', 'activate'));

// when deativated clean up
register_deactivation_hook(WF_SN_BASE_FILE, array('wf_sn_ss', 'deactivate'));
