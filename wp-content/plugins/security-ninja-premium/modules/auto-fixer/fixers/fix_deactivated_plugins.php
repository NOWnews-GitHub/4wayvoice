<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_deactivated_plugins extends wf_sn_af {
  static function get_label($label) {
    $labels = array('title' => 'Delete inactive plugins',
					'fixable' => true,
					'info' => 'Fix will delete inactive plugins. There is NO undo.',
					'msg_ok' => 'Plugins removed successfully.',
					'msg_bad' => 'Plugins removal failed.');  
    if(!array_key_exists($label,$labels)){
      return '';	
    } else {	
      return $labels[$label];
    }
  }
  					
  static function fix() {
    $all_plugins = get_plugins();
    $active_plugins = get_option('active_plugins', array());
    $remove_plugins = array();
    $msg = '';
    $failed = false;
    
	// loop though all plugins and delete inactive ones
    foreach($all_plugins as $plugin_path => $plugin_data){
      $success=false;
      if(!in_array($plugin_path, $active_plugins)){		  
        if(strpos($plugin_path, '/') !== false){ // if plugin is a folder		 
          $plugin_path_array=explode('/', $plugin_path);
          if(2 == count($plugin_path_array)){  // make sure it's a valid plugin path and not some header from a subfolder inside a plugin
            if(wf_sn_af::directory_unlink(WP_PLUGIN_DIR . '/' . $plugin_path_array[0])){
              $success = true;
            }
          } 
        } else { // if plugin is a single file
          if(unlink(WP_PLUGIN_DIR . '/' . $plugin_path)){
            $success = true;  
          }
        }
        
        if($success){
          $msg .= '<strong>' . $all_plugins[$plugin_path]['Name'] . '</strong> removed.<br />'; 
        } else {
          $msg .= '<strong>' . $all_plugins[$plugin_path]['Name'] . '</strong> could not be removed.<br />'; 	
          $failed = true;
        }
      }
    }
    
    if(!$failed){
      wf_sn_af::mark_as_fixed('deactivated_plugins');
      return $msg;	
    } else {
      return $msg;
    }	
  } 
} // wf_sn_af_fix_ver_check
