<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_plugins_ver_check extends wf_sn_af {
  
  static function get_label($label) {
    $labels = array('title' => 'Update Outdated Plugins',
					'fixable' => true,
					'info' => 'Update all plugins to the latest version.',
					'msg_ok' => 'Fix applied successfully.',
					'msg_bad' => 'Unable to apply fix.');  
    if(!array_key_exists($label,$labels)){
      return '';	
    } else {	
      return $labels[$label];
    }
  }
  					
  static function fix() {
    // load up core upgrade classes
	if(!class_exists("Core_Upgrader")){
      require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');  		
    }
    
    if (!function_exists('wp_update_plugins')){
      include_once(ABSPATH . 'wp-includes/update.php');
    }
    
	// get a list of plugins that need to be upgraded
    $current = get_site_transient('update_plugins');
  
    if (isset($current->response) && is_array($current->response) ) {
      $plugins_update_cnt = count($current->response);
    } else {
      return self::get_label('msg_bad');
    }
    
    $plugins_to_update = array();
    if( count($current->response)>0 ){
      foreach($current->response as $plugin_path => $plugin_data){		  
        $current_plugin_status = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);
        $plugins_to_update[$plugin_path] = $current_plugin_status['Version'];	
      }
    }
    
    $upgrader = new wf_sn_af_plugin_upgrader();
    $result   = $upgrader->bulk_upgrade(array_keys($plugins_to_update));
      
    $msg = 'Update Result:<br />';
    
	// upgrade plugins and log result for each one
	$plugins_updated = 0;
    foreach($plugins_to_update as $plugin=>$ver){
      $new_plugin_status = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);	
      if(version_compare($plugins_to_update[$plugin], $new_plugin_status['Version'], '<')){
        $msg .= '<strong>' . $new_plugin_status['Name'] . '</strong> updated from ' . $plugins_to_update[$plugin] . ' to ' . $new_plugin_status['Version'] . '<br />';
        $plugins_updated++;
      } else {
        $msg .= '<strong>' . $new_plugin_status['Name'] . '</strong> updated failed<br />'; 
      }
    }
    
    if(count($plugins_to_update) == $plugins_updated){
      wf_sn_af::mark_as_fixed('plugins_ver_check');
      return $msg;	
    } else {
      return $msg;
    }	
  }  
} // wf_sn_af_fix_ver_check
