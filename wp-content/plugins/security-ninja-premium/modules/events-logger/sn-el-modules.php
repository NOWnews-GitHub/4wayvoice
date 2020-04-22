<?php
/*
 * Security Ninja - Event Logger add-on
 * (c) Web factory Ltd, 2018
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class wf_sn_el_modules extends wf_sn_el {
	static $deleted_user = null;

	/**
	 * Write event to database
	 * @param  [type] $module					[description]
	 * @param  [type] $action					[description]
	 * @param  string $description		[description]
	 * @param  [type] $raw_data				[description]
	 * @param  [type] $user_id				[description]
	 * @param  [type] $ip							[description]
	 * @return [integer]							[inserted id in database]
	 */
	static function log_event($module, $action, $description = 'No details available.' , $raw_data = null, $user_id = null, $ip = null) {
		global $wpdb;

		if (!is_array($description)) {
			$description = array($description);
		}

		if (is_null($user_id)) {
			$user_id = get_current_user_id();
		}

		if ( self::syslogactive() ) {
			$upload_dir = wp_upload_dir();
			$secninjaUploadDir = $upload_dir['basedir'] . '/security-ninja/logs/';
			$log = new Logger('Security Ninja');

			$options = get_option(WF_SN_EL_OPTIONS_KEY);
			if (!in_array($options['rotatingsyslog'], array(7,30))) {
				$rotateDays = 0;
			} else {
				$rotateDays = $options['rotatingsyslog'];
			}


			$handler = new \Monolog\Handler\RotatingFileHandler($secninjaUploadDir.'security-ninja.log', $rotateDays, Monolog\Logger::DEBUG);
			$handler->setFilenameFormat('{date}-{filename}', 'Y-m-d');
			$log->pushHandler($handler);
		}

		foreach ($description as $desc) {

			if ( self::syslogactive() ) {
				$logarr = array(
					'module' => $module,
					'action' => $action,
					'description' => $description,
				);

				if ($raw_data) $logarr['raw_data'] =  $raw_data ;

				if ($user_id) {
					$theUser = get_user_by( 'ID', $user_id );
					if ($theUser) {
						$logarr['user_id'] = $theUser->ID;
						$logarr['user_name'] = $theUser->display_name;
					}
				}
				$log->debug( $action, $logarr );
			}

			if (!$ip) {
				$current_user = SN_Geolocation::geolocate_ip('',true,true);
				$ip = $current_user['ip'];
			}

			$new_id = $wpdb->insert($wpdb->prefix . WF_SN_EL_TABLE,
				array(
					'timestamp' => current_time( 'mysql' ),
					'ip' => sanitize_text_field( $ip ),
					'user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ),
					'user_id' => absint( $user_id ),
					'module' => $module,
					'action' => $action,
					'description' => $desc,
					'raw_data' => serialize($raw_data)
				),
				array(
					'%s',
					'%s',
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
					'%s'
				)
			);
		}

		parent::send_email_reports($wpdb->insert_id);

		return $wpdb->insert_id;
	} // log_event



	// users related events
	static function parse_action_users($action_name, $params) {
		$desc = '';
		$user_id = null;
		$raw_data = null;

		if ($params) $raw_data = $params;

		$current_user = SN_Geolocation::geolocate_ip('',true);
		if ( is_array( $current_user ) ) {

			$raw_data['ip'] = $current_user['ip'];
			$raw_data['country'] = $current_user['country'];

		}

		switch ($action_name) {
			case 'wp_login_failed':
			$desc = 'Failed login attempt with username ' . $params[1] ;

			break;
			case 'set_logged_in_cookie':
			$user = get_user_by('id', $params[4]);
			$desc = $user->display_name . ' logged in.';
			$user_id = $user->ID;

			break;
			case 'clear_auth_cookie':
			$user = wp_get_current_user();
			if (empty($user) || ! $user->exists()) {
				return;
			}
			$desc =  $user->display_name . ' logged out.';

			break;
			case 'user_register':
			$user = get_user_by('id', $params[1]);
			$desc = 'New user registered - ' . $user->display_name . '.';

			case 'profile_update':
			$user = get_user_by('id', $params[1]);
			$desc =  $user->display_name . '\'s profile was updated.';

			break;
			case 'retrieve_password':
			$desc =  $params[1] . '\'s password was requested to be reset.';
			$user = get_user_by('id', $params[1]);

			break;
			case 'password_reset':
			$desc =  $params[1]->data->user_login . '\'s password was reset.';
			$user = get_user_by('id', $params[1]);

			break;
			case 'delete_user':
			self::$deleted_user = get_user_by('id', $params[1]);

			return;
			break;
			case 'deleted_user':
			if (!self::$deleted_user) {
				return;
			}
			$desc =  self::$deleted_user->display_name . '\'s account was deleted.';
			self::$deleted_user = null;
			break;
			case 'set_user_role':
			if (!isset($params[3][0]) || !$params[3][0]) {
				return;
			}
			$user = get_user_by('id', $params[1]);
			$desc =  $user->display_name . '\'s role was changed from ' . $params[3][0] . ' to ' . $params[2] . '.';

			break;
			default:
			$desc = 'Unknown action or filter - ' . $action_name . '.';
		}

		self::log_event('users', $action_name, $desc, $raw_data, $user_id);
	} // log_action_users


	// menus related events
	static function parse_action_menus($action_name, $params) {
		$desc = '';
		$raw_data = null;

		switch ($action_name) {
			case 'wp_create_nav_menu':
			$desc = 'Menu ' . $params[2]['menu-name'] . ' created.';
			break;
			case 'wp_update_nav_menu':
			if (!isset($params[2])) {
				return;
			}
			$desc = 'Menu ' . $params[2]['menu-name'] . ' updated.';
			break;
			case 'delete_nav_menu':
			$desc = 'Menu ' . $params[3]->name . ' deleted.';
			break;
			default:
			$desc = 'Unknown action or filter - ' . $action_name . '.';
		}

		self::log_event('menus', $action_name, $desc, $raw_data);
	} // parse_action_menus


	// file editor related events
	static function parse_action_file_editor($action_name, $params) {
		$desc = '';
		$raw_data = null;



		switch ($action_name) {
			case 'wp_redirect':
			if (strpos($params[1], 'plugin-editor.php?') !== false) {
				list($url, $query) = explode('?', $params[1]);
				$query = wp_parse_args($query);
				$plugin = get_plugin_data(WP_PLUGIN_DIR . '/' . $query['file']);
				if (!$plugin['Name']) {
					return;
				}
				$desc = 'File ' . $query['file'] . ' in plugin ' . $plugin['Name'] . ' edited.';

			} elseif (strpos($params[1], 'theme-editor.php?') !== false) {
				list($url, $query) = explode('?', $params[1]);
				$query = wp_parse_args($query);
				$theme = wp_get_theme($query['theme']);
				if (!$theme->exists() || ($theme->errors() && 'theme_no_stylesheet' === $theme->errors()->get_error_code())) {
					return;
				}
				$desc = 'File ' . $query['file'] . ' in theme ' . $theme->get('Name') . ' edited.';

			} else {
				return;
			}
			break;
			default:
			$desc = 'Unknown action or filter - ' . $action_name . '.';
		}

		self::log_event('file_editor', $action_name, $desc, $raw_data);
	} // parse_action_file_editor


	// taxonomies related events
	static function parse_action_taxonomies($action_name, $params) {
		$desc = '';
		$raw_data = null;
//global $user;
		global $wp_taxonomies;

		switch ($action_name) {
			case 'created_term':
			$term = get_term($params[1], $params[3]);
			$desc =  $term->name . ' in ' . $wp_taxonomies[$params[3]]->labels->name . ' created.';

			break;
			case 'delete_term':
			$desc =  $params[4]->name . ' in ' . $wp_taxonomies[$params[3]]->labels->name . ' deleted.';

			break;
			case 'edited_term':
			$term = get_term($params[1], $params[3]);
			$desc =  $term->name . ' in ' . $wp_taxonomies[$params[3]]->labels->name . ' updated.';

			break;
			default:
			$desc = 'Unknown action or filter - ' . $action_name . '.';
		}

		self::log_event('taxonomies', $action_name, $desc, $raw_data);
	} // parse_action_taxonomies


	// media related events
	static function parse_action_media($action_name, $params) {
		$desc = '';
		$raw_data = null;

		switch ($action_name) {
			case 'add_attachment':
			$media = get_post($params[1]);
			$desc = 'Added media ' . $media->post_title . '.';

			break;
			case 'edit_attachment':
			$media = get_post($params[1]);
			$desc = 'Updated media ' . $media->post_title . '.';

			break;
			case 'delete_attachment':
			$media = get_post($params[1]);
			$desc = 'Deleted media ' . $media->post_title . '.';

			break;
			case 'wp_save_image_editor_file':
			$media = get_post($params[5]);
			$desc = 'Edited image ' . $media->post_title . '.';

			break;
			default:
			$desc = 'Unknown action or filter - ' . $action_name . '.';
		}

		self::log_event('media', $action_name, $desc, $raw_data);
	} // parse_action_media


	// posts related events
	static function parse_action_posts($action_name, $params) {
		$desc = '';
		$raw_data = null;

		switch ($action_name) {
			case 'transition_post_status':
			$new = $params[1];
			$old = $params[2];
			if ($new == 'auto-draft' || $new == 'inherit') {
				return;
			} elseif ($old == 'auto-draft' && $new == 'draft' ) {
				$action = 'drafted';
			} elseif ($old == 'auto-draft' && ($new == 'publish' || $new == 'private')) {
				$action  = 'published';
			} elseif ($old == 'draft' && ($new == 'publish' || $new == 'private')) {
				$action = 'published';
			} elseif ($old == 'publish' && ($new == 'draft')) {
				$action = 'unpublished';
			} elseif ($new == 'trash') {
				$action  = 'trashed';
			} elseif ($old == 'trash' && $new != 'trash') {
				$action  = 'restored from trash';
			} else {
				$action = 'updated';
			}
			if (empty($params[3]->post_title)) {
				$title = 'no title';
			} else {
				$title = $params[3]->post_title;
			}
			if (post_type_exists($params[3]->post_type)) {
				$post_type = get_post_type_object($params[3]->post_type);
				$type = strtolower($post_type->labels->singular_name);
			} else {
				$type = 'post';
			}
			if (in_array($type, array('nav_menu_item', 'attachment', 'revision'))) {
				return;
			}
			$desc =  $title . ' ' . $type . ' ' . $action . '.';

			break;
			case 'deleted_post':
			$post = get_post($params[1]);
			if (post_type_exists($post->post_type)) {
				$post_type = get_post_type_object($post->post_type);
				$type = strtolower($post_type->labels->singular_name);
			} else {
				$type = 'post';
			}
			if (in_array($type, array('nav_menu_item', 'attachment', 'revision'))) {
				return;
			}
			$desc =  $post->post_title . ' ' . $type . ' deleted from trash.';

			break;
			default:
			$desc = 'Unknown action or filter - ' . $action_name . '.';
		}

		self::log_event('posts', $action_name, $desc, $raw_data);
	} // parse_action_posts


	// widgets related events
	static function parse_action_widgets($action_name, $params) {
		$desc = '';
		$raw_data = null;
		global $wp_registered_sidebars, $wp_registered_widgets, $wp_widget_factory;

		switch ($action_name) {
			case 'widget_update_callback':
			$name = $wp_registered_sidebars[$_POST['sidebar']]['name'];
			if (empty($name)) {
				$name = 'unnamed';
			}
			$title = $params[1]['title'];
			if (empty($title)) {
				$title = 'titleless';
			}
			if ($_POST['add_new']) {
				$desc =  $params[4]->name . ' widget was added to ' . $name . ' sidebar.';
			} else {
				$desc =  $title . ' instance of ' . $params[4]->name . ' widget updated in ' . $name . ' sidebar.';
			}
			break;
			case 'wp_ajax_widgets-order':
			if (did_action('widget_update_callback') || $_POST['action'] != 'widgets-order') {
				return;
			}

			$new = $_POST['sidebars'];
			$old = apply_filters('sidebars_widgets', get_option('sidebars_widgets', array()));
			foreach ($new as $sidebar_id => $widget_ids) {
				$widget_ids = preg_replace('#(widget-\d+_)#', '', $widget_ids);
				$new[$sidebar_id] = array_filter(explode(',', $widget_ids));

				if ($new[$sidebar_id] !== $old[$sidebar_id]) {
					$changed = $sidebar_id;
					break;
				}
				} // foreach

				if (isset($changed)) {
					$name = $wp_registered_sidebars[$changed]['name'];
					if (empty($name)) {
						$name = 'unnamed';
					}
					$desc = 'Widgets in ' . $name . ' sidebar were reordered.';
				} else {
					return;
				}
				break;
				case 'update_option_sidebars_widgets':
				if (did_action('after_switch_theme')) {
					return;
				}

				if (isset($_POST['delete_widget']) && $_POST['delete_widget']) {
					$name = $wp_registered_sidebars[$_POST['sidebar']]['name'];
					if (empty($name)) {
						$name = 'unnamed';
					}
					$ids = array_combine(wp_list_pluck($wp_widget_factory->widgets, 'id_base'), array_keys($wp_widget_factory->widgets));
					$id_base = preg_match('#(.*)-(\d+)$#', $_POST['the-widget-id'], $matches)? $matches[1]: null;
					$widget = $wp_widget_factory->widgets[$ids[$id_base]]->name;
					$desc =  $widget . ' widget was removed from ' . $name . ' sidebar.';
				} else {
					return;
				}
				break;
				default:
				$desc = 'Unknown action or filter - ' . $action_name . '.';
			}

			self::log_event('widgets', $action_name, $desc, $raw_data);
	} // parse_action_widgets


	// installer related events
	static function parse_action_installer($action_name, $params) {
		$desc = '';
		$raw_data = null;

		switch ($action_name) {
			case 'activate_plugin':
			$plugin = get_plugin_data(WP_PLUGIN_DIR . '/' . $params[1]);
			if (!$plugin['Name']) {
				return;
			}
			$desc = 'Plugin ' . $plugin['Name'] . ' activated.';

			break;
			case 'deactivate_plugin':
			$plugin = get_plugin_data(WP_PLUGIN_DIR . '/' . $params[1]);
			if (!$plugin['Name']) {
				return;
			}
			$desc = 'Plugin ' . $plugin['Name'] . ' deactivated.';

			break;
			case 'switch_theme':
			$desc = 'Theme ' . $params[1] . ' activated.';

			break;
			case '_core_updated_successfully':
			$desc = 'WordPress core updated to v'. $params[1] . '.';

			break;
			case 'upgrader_process_complete':
			if (@$params[2]['action'] != 'update' || (@$params[2]['type'] != 'plugin' && @$params[2]['type'] != 'theme')) {
				return;
			}

			if (@$params[2]['type'] == 'theme' && isset($params[2]['themes']) && @$params[2]['action'] == 'update' && isset($params[2]['bulk']) &&$params[2]['bulk']) {
				$desc = array();
				foreach ($params[2]['themes'] as $theme_name) {
					$theme = wp_get_theme($theme_name);
					if (!$theme->exists() || ($theme->errors() && 'theme_no_stylesheet' === $theme->errors()->get_error_code())) {
						return;
					}
					$desc[] = 'Theme ' . $theme->get('Name') . ' updated.';

					} // foreach themes
					break;
				}

				if (@$params[2]['type'] == 'theme' && isset($params[2]['theme']) && @$params[2]['action'] == 'update') {
					$theme = wp_get_theme($params[2]['theme']);
					if (!$theme->exists() || ($theme->errors() && 'theme_no_stylesheet' === $theme->errors()->get_error_code())) {
						return;
					}

					$desc = 'Theme ' . $theme->get('Name') . ' updated.';
					break;
				}

				if (isset($params[2]['plugins']) && is_array($params[2]['plugins'])) {
					$desc = array();
					foreach ($params[2]['plugins'] as $plugin_file) {
						$plugin = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
						if (!$plugin['Name']) {
							return;
						}
						$desc[] = 'Plugin ' . $plugin['Name'] . ' updated.';
					}
				} elseif (isset($params[2]['plugin'])) {
					$plugin = get_plugin_data(WP_PLUGIN_DIR . '/' . $params[2]['plugin']);
					if (!$plugin['Name']) {
						return;
					}
					$desc = 'Plugin ' . $plugin['Name'] . ' updated.';

				} else {
					$desc = 'Unknown plugin updated.';
				}
				break;
				default:
				$desc = 'Unknown action or filter - ' . $action_name . '.';
			}

			self::log_event('installer', $action_name, $desc, $raw_data);
	} // parse_action_installer


	// comments related events
	static function parse_action_comments($action_name, $params) {
		$desc = '';
		$raw_data = null;

		switch ($action_name) {
			case 'comment_duplicate_trigger':
			$post_title = ($post = get_post($params[1]['comment_post_ID']))? $post->post_title : 'untitled';
			$desc = 'Duplicate comment by ' . $params[1]['comment_author_email'] . ' prevented on ' . $post_title . '.';
			break;
			case 'comment_flood_trigger':
			$post_title = ($post = get_post($_POST['comment_post_ID']))? $post->post_title : 'untitled';
			$desc = 'Comment flooding by ' . $_POST['email'] . ' prevented on ' . $post_title . '.';
			break;
			case 'wp_insert_comment':
			$post_title = ($post = get_post($params[2]->comment_post_ID))? $post->post_title : 'untitled';
			if ($params[2]->comment_parent) {
				$desc = 'New comment reply by ' . $params[2]->comment_author_email . ' created on ' . $post_title . '.';
			} else {
				$desc = 'New comment by ' . $params[2]->comment_author_email . ' created on ' . $post_title . '.';
			}
			break;
			case 'edit_comment':
			$post_title = ($post = get_post($_POST['comment_post_ID']))? $post->post_title : 'untitled';
			$desc = 'Comment by ' . $_POST['newcomment_author_email'] . ' on ' . $post_title . ' edited.';
			break;
			case 'trash_comment':
			$comment = get_comment($params[1]);
			$post_title = ($post = get_post($comment->comment_post_ID))? $post->post_title : 'untitled';
			$desc = 'Comment by ' . $comment->comment_author_email . ' on ' . $post_title . ' trashed.';
			break;
			case 'untrash_comment':
			$comment = get_comment($params[1]);
			$post_title = ($post = get_post($comment->comment_post_ID))? $post->post_title : 'untitled';
			$desc = 'Comment by ' . $comment->comment_author_email . ' on ' . $post_title . ' restored.';
			break;
			case 'delete_comment':
			$comment = get_comment($params[1]);
			$post_title = ($post = get_post($comment->comment_post_ID))? $post->post_title : 'untitled';
			$desc = 'Comment by ' . $comment->comment_author_email . ' on ' . $post_title . ' permanently deleted.';
			break;
			case 'spam_comment':
			$comment = get_comment($params[1]);
			$post_title = ($post = get_post($comment->comment_post_ID))? $post->post_title : 'untitled';
			$desc = 'Comment by ' . $comment->comment_author_email . ' on ' . $post_title . ' marked as spam.';
			break;
			case 'unspam_comment':
			$comment = get_comment($params[1]);
			$post_title = ($post = get_post($comment->comment_post_ID))? $post->post_title : 'untitled';
			$desc = 'Comment by ' . $comment->comment_author_email . ' on ' . $post_title . ' unmark as spam.';
			break;
			case 'transition_comment_status':
			if ($params[1] != 'approved' && $params[1] != 'unapproved' || 'trash' == $params[2] || 'spam' == $params[2] ) {
				return;
			}
			$comment = get_comment($params[1]);
			$post_title = ($post = get_post($params[3]->comment_post_ID))? $post->post_title : 'untitled';
			$desc = 'Comment by ' . $params[3]->comment_author_email . ' on ' . $post_title . ' ' . $params[1] . '.';
			break;
			default:
			$desc = 'Unknown action or filter - ' . $action_name . '.';
		}

		self::log_event('comments', $action_name, $desc, $raw_data);
	} // parse_action_comments


	// settings related events
	static function parse_action_settings($action_name, $params) {
		$desc = '';
		$raw_data = null;

		switch ($action_name) {
			case 'update_option_permalink_structure':
			$desc = 'Permalink settings updated.';
			break;
			case 'whitelist_options':
			if (in_array(@$_POST['option_page'], array('general', 'discussion', 'media', 'reading', 'writing'))) {
				$desc = ucfirst(@$_POST['option_page']) . ' settings updated.';
			} else {
				$desc =  @$_POST['option_page'] . ' settings updated.';
			}
			break;
			case 'update_option_tag_base':
			$desc = 'Tag base option updated.';
			break;
			case 'update_option_category_base':
			$desc = 'Category base option updated.';
			break;
			case 'update_site_option':
			return;
			break;
			default:
			$desc = 'Unknown action or filter - ' . $action_name . '.';
		}

		self::log_event('settings', $action_name, $desc, $raw_data);
	} // parse_action_settings


	// Security Ninja related events
	static function parse_action_security_ninja($action_name, $params) {
		$desc = '';
		$raw_data = null;

		switch ($action_name) {
			case 'security_ninja_done_testing':
			$desc = 'Security Ninja finished analyzing the site in ' . round($params[2], 1) . ' seconds.';
			break;
			case 'security_ninja_core_scanner_done_scanning':
			$desc = 'Security Ninja - Core Scanner add-on finished scanning files in ' . round($params[2], 1) . ' seconds.';
			break;
			case 'security_ninja_scheduled_scanner_done_cron':
			$desc = 'Security Ninja - Scheduled Scanner add-on finished a scheduled scan in ' . round($params[1], 1) . ' seconds.';
			break;
			case 'security_ninja_malware_scanner_done_scanning':
			$desc = 'Security Ninja - Malware Scanner add-on finished scanning and found ' . $params[1] . ' suspicious files.';
			break;
			case 'security_ninja_remote_access':
			$desc = 'Security Ninja Remote Access was ' . $params[1] . '.';
			break;
			default:
			$desc = 'Unknown action or filter - ' . $action_name . '.';
		}

		self::log_event('security_ninja', $action_name, $desc, $raw_data);
	} // parse_action_security_ninja

		// reset pointers on activation and save some info
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

	self::log_event('security_ninja', 'activated_pro', __('Plugin was activated :-)', WF_SN_TEXT_DOMAIN), '');
	} // activate

	// clean-up when deactivated
	static function deactivate() {
		global $wpdb;
		$wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . WF_SN_EL_TABLE);
	} // deactivate

} //class wf_sn_el_modules

// setup environment when activated
register_activation_hook(WF_SN_BASE_FILE, array('wf_sn_el_modules', 'activate'));

// when deativated clean up
register_deactivation_hook(WF_SN_BASE_FILE, array('wf_sn_el_modules', 'deactivate'));