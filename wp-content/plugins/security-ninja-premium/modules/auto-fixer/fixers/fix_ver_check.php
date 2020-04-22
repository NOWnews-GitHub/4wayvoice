<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_ver_check extends wf_sn_af {
  static function get_label($label) {
    $labels = array('title' => 'Update Wordpress',
					'fixable' => true,
					'info' => 'Fix will update WordPress to the latest version.',
					'msg_ok' => 'Fix applied successfully.',
					'msg_bad' => 'Unable to apply fix.');  
    if(!array_key_exists($label,$labels)){
      return '';	
    } else {	
      return $labels[$label];
    }
  }
  					
  static function fix() {
    if(!class_exists("Core_Upgrader")){
      require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );  		
    }		
      
    if (!function_exists('get_preferred_from_update_core') ) {
      require_once(ABSPATH . 'wp-admin/includes/update.php');
    }
    
    wp_version_check();
    $latest_core_update = get_preferred_from_update_core();    
    $upgrader = new wf_sn_af_core_upgrader();    
    $update = find_core_update($latest_core_update->version,'en_US');
    $result = $upgrader->upgrade( $update );
    
    if($result == $latest_core_update->version){
      wf_sn_af::mark_as_fixed('ver_check');
      return self::get_label('msg_ok');	
    } else {
      return self::get_label('msg_bad');
    }	
  } 
} // wf_sn_af_fix_ver_check
