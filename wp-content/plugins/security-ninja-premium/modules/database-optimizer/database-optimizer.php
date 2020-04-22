<?php
if (!function_exists('add_action')) {
  die('Please don\'t open this file directly!');
}
require_once 'sn-do-actions.php';
class wf_sn_do {
  // init plugin
  static function init() {
    // does the user have enough privilages to use the plugin?
    if (is_admin()) {
      // add tab to Security Ninja tabs
      add_filter('sn_tabs', array(__CLASS__, 'sn_tabs'));
      // enqueue scripts
      add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
      // register ajax endpoints
      add_action('wp_ajax_sn_do_run_optimization', array(__CLASS__, 'ajax_run_optimization'));
      // add custom text for GUI overlay
      add_action('sn_overlay_content', array(__CLASS__, 'overlay_content'));
    } // if admin
  } // init

  // enqueue CSS and JS scripts on plugin's admin page
  static function enqueue_scripts() {
    if (wf_sn::is_plugin_page()) {
      $plugin_url = plugin_dir_url(__FILE__);

      $js_vars = array('sn_do_nonce' => wp_create_nonce('sn_do_run'),
                       'confirm' => 'Please confirm that you have backed up your database and understand there is NO UNDO for this action.',
                       'undocumented_error' => 'An undocumented error has occured. Please reload the page and try again.');

      wp_enqueue_script('sn-do-js', $plugin_url . 'js/wf-sn-do.js', array(), wf_sn::$version, true);
      wp_localize_script('jquery', 'wf_sn_do', $js_vars);
    } // if
  } // enqueue_scripts


  static function ajax_run_optimization() {
    check_ajax_referer('sn_do_run');
    $done = array();
    $start_time = microtime(true);

    $action = trim(@$_GET['optimization']);
    $actions = wf_sn_do_actions::get_actions();

    if (empty($actions[$action]) && $action != 'all') {
      wp_send_json_error('Unknown optimization action.');
    }

    if ($action == 'all') {
      foreach ($actions as $action_key => $action_name) {
        if (is_callable(array('wf_sn_do_actions', 'do_' . $action_key))) {
          $tmp = call_user_func(array('wf_sn_do_actions', 'do_' . $action_key));
          $done[$action_key] = $tmp;
        }
      }
    } else {
      $tmp = call_user_func(array('wf_sn_do_actions', 'do_' . $action));
      $done[$action] = $tmp;
    }

    do_action('security_ninja_database_optimizer_done_optimizing', $done, microtime(true) - $start_time);

    wp_send_json_success($done);
  } // ajax_run_optimization
  // add custom message to overlay
  static function overlay_content() {
    echo '<div id="sn-do" style="display: none;">';
    echo '<h3>'.__('Security Ninja is optimizing your database.', WF_SN_TEXT_DOMAIN).'<br/>'.__('It will only take a few moments.', WF_SN_TEXT_DOMAIN).'</h3>';
    echo '</div>';
  } // overlay_content

  // add new tab
  static function sn_tabs($tabs) {
    $core_tab = array('id' => 'sn_do', 'class' => 'hidden', 'label' => 'Database', 'callback' => array(__CLASS__, 'do_page'));
    $done = 0;
    for ($i = 0; $i < sizeof($tabs); $i++) {
      if ($tabs[$i]['id'] == 'sn_do') {
        $tabs[$i] = $core_tab;
        $done = 1;
        break;
      }
    } // for
    if (!$done) {
      $tabs[] = $core_tab;
    }
    return $tabs;
  } // sn_tabs

  static function get_table_prefix() {
    global $wpdb;

    if (is_multisite() && !defined('MULTISITE')) {
      $table_prefix = $wpdb->base_prefix;
    } else {
      $table_prefix = $wpdb->get_blog_prefix(0);
    }

    return $table_prefix;
  } // get_table_prefix


  static function format_size($bytes) {
    if ($bytes > 1073741824) {
      return number_format_i18n($bytes/1073741824, 2) . ' GB';
    } elseif ($bytes > 1048576) {
      return number_format_i18n($bytes/1048576, 1) . ' MB';
    } elseif ($bytes > 1024) {
      return number_format_i18n($bytes/1024, 1) . ' KB';
    } else {
      return number_format_i18n($bytes, 0) . ' bytes';
    }
  } // format_size

  // display results
  static function do_page() {
    global $wpdb;

    echo '<div class="submit-test-container">';
    $tables = $size = $records = $overhead = 0;

    $table_status = $wpdb->get_results('SHOW TABLE STATUS');
    $table_prefix = self::get_table_prefix();

    if (is_array($table_status)) {
      foreach ($table_status as $index => $table) {
        if (0 !== stripos($table->Name, $table_prefix)) {
          continue;
        }
        if ($table->Engine == 'MyISAM') {
          $overhead += $table->Data_free;
        }
        $tables++;
        $records += $table->Rows;
        $size += $table->Data_length;
      }
    }

    echo '<div id="counters">';
    echo '<span class="score" style="border-left: none;">' . $tables . '<br><i>Tables</i></span>';
    echo '<span class="score">' . self::format_size($size) . '<br><i>Total Data Size</i></span>';
    echo '<span class="score">' . self::format_size($overhead) . '<br><i>Wasted Space Available for Recovery</i></span>';
    echo '<span class="score">' . number_format_i18n($records) . '<br><i>Total Records</i></span>';
    echo '</div>';

    echo '<p><strong>Please read!</strong> As you use WordPress and add more content to your site, it will inevitably lead to garbage data accumulation in your database. And while ten or a couple of hundred records wont slow your site down a couple of thousand might, and tens of thousand definitely will. Speed aside, some people just love a clean database. This optimizer is meant to remove data that you don\'t need. However, you are the one who has to decide which data is that. So <strong>be careful what you remove</strong> and always make a backup first.</p>';
    echo '<p style="font-weight: 800;"><b style="color: #ea2327; font-weight: 800;">IMPORTANT!</b> Always backup your database before doing any work on it! We are not responsible for any data loss.</p>';
    echo '</div>';

    echo '<table class="wp-list-table widefat" cellspacing="0" id="security-ninja">';
    echo '<thead><tr>';
    echo '<th>Optimization Name</th>';
    echo '<th>Optimization Status</th>';
    echo '<th><a data-action-id="all" href="#" class="js-action button sn-do-action">Run All Optimizations</a></th>';
    echo '</tr></thead>';
    echo '<tbody>';

    foreach (wf_sn_do_actions::get_actions() as $action => $name) {
      if (is_callable(array('wf_sn_do_actions', 'info_' . $action))) {
        $desc = call_user_func(array('wf_sn_do_actions', 'info_' . $action));
      } else {
        $desc = 'No info available.';
      }

      echo '<tr>';
      echo '<td>' . $name . '</td>';
      echo '<td class="do-optimization-desc" id="' . $action . '_desc">' . $desc . '</td>';
      echo '<td class="sn-details"><a data-action-id="' . $action . '" href="#" class="js-action button sn-do-action">Run Optimization</a></td>';
      echo '</tr>';
    } // foreach


    echo '</tbody>';
    echo '</table><br>';
  } // do_page
} // wf_sn_do class
// hook everything up
add_action('plugins_loaded', array('wf_sn_do', 'init'));
