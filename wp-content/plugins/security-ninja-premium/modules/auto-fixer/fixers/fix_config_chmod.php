<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_config_chmod extends wf_sn_af {
  static function get_label($label) {
    $labels = array('title' => 'Update wp-config.php permissions',
					'fixable' => true,
					'info' => '<i>wp-config.php</i> file permissions will be changed to an optimal value (0440).',
					'msg_ok' => 'Fix applied successfully.',
					'msg_bad' => 'Unable to apply fix.');  
    if(!array_key_exists($label,$labels)){
	  return '';	
	} else {	
	  return $labels[$label];
	}
  }
  					
  static function fix() {
    chmod(wf_sn_af::$wp_config_path, 0440);
    $no_wsod = wf_sn_af::test_wordpress_status();
    if($no_wsod){
      wf_sn_af::mark_as_fixed('config_chmod');	
      return self::get_label('msg_ok') . " Permission set to 0440";		
    }	
    chmod(wf_sn_af::$wp_config_path, 0666);
    return self::get_label('msg_bad');	
  } 
} // wf_sn_af_fix_config_chmod
