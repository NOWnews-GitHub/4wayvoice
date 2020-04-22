<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_deactivated_themes extends wf_sn_af {
  static function get_label($label) {
    $labels = array('title' => 'Delete unused themes',
					'fixable' => true,
					'info' => 'Fix will delete unused themes. There is NO undo.',
					'msg_ok' => 'Inactive themes removed successfully.',
					'msg_bad' => 'Inactive Themes Removal Failed.');  
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
	
	// get all themes and determine active theme 
    $all_themes = wp_get_themes();
    $current_theme = get_option('stylesheet');
    $parent_theme = '';
	
	// if active theme is a child theme determine parent theme as well so it is not deleted either
    if(is_child_theme()){
      $parent_theme = $all_themes[$current_theme]->get('Template');		
    }
    $theme_directory = get_theme_root();
    $failed = false;		
    
	// loop though all themes and delete inactive ones
    foreach($all_themes as $theme_path => $theme_data){
      if( strlen($theme_path) > 0 && $theme_path != $current_theme && $theme_path != $parent_theme ){
        if(wf_sn_af::directory_unlink($theme_directory . '/' . $theme_path)){
          $msg .= '<strong>' . $all_themes[$theme_path]->get('Name') . '</strong> removed.<br />'; 
        } else {
          $msg .= '<strong>' . $all_themes[$theme_path]->get('Name') . '</strong> could not be removed.<br />'; 	
          $failed = true;
        }
      }
    }
    
    if(!$failed){
      wf_sn_af::mark_as_fixed('deactivated_themes');
      return $msg . self::get_label('msg_ok');
    } else {
      return $msg . self::get_label('msg_bad');
    }	
  } 
} // wf_sn_af_fix_ver_check
