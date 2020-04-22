<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_file_editor extends wf_sn_af {  
  static function get_label($label) {
	  $labels = array('title' => 'Disable plugins/themes file editor',
					'fixable' => true,
					'info' => 'Fix will disable the plugins and themes file editor.',
					'msg_ok' => 'Fix applied successfully.',
					'msg_bad' => 'Could not disable file editor.' ); 
						   
    if(!array_key_exists($label,$labels)){
      return '';	
    } else {	
      return $labels[$label];
    }
  }
  					
  static function fix() {
    $backup_timestamp = time();  
    wf_sn_af::backup_file(wf_sn_af::$wp_config_path, $backup_timestamp, 'debug_check');	    
    wf_sn_af::update_define(wf_sn_af::$wp_config_path, 'DISALLOW_FILE_EDIT', true);    
    $no_wsod = wf_sn_af::test_wordpress_status();	
    if(!$no_wsod){
      wf_sn_af::backup_file_restore(wf_sn_af::$wp_config_path, $backup_timestamp, 'debug_check');
      return self::get_label('msg_bad');	
    } else {
      wf_sn_af::mark_as_fixed('file_editor');
      return self::get_label('msg_ok');	
    }
  } 
} // wf_sn_af_fix_file_editor
