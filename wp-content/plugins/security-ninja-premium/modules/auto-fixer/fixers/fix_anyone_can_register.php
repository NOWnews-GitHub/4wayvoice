<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_anyone_can_register extends wf_sn_af {
  static function get_label($label) {
    $labels = array('title' => 'Disable Anyone can register',
					'fixable' => true,
					'info' => 'Fix will disable the "Anyone can register" option.',
					'msg_ok' => 'Fix applied successfully.',
					'msg_bad' => 'Unable to apply fix.');  
    if(!array_key_exists($label,$labels)){
      return '';	
    } else {	
      return $labels[$label];
    }
  }
  					
  static function fix() {
    if(update_option('users_can_register', 0)){
      wf_sn_af::mark_as_fixed('anyone_can_register');
      return self::get_label('msg_ok');	
    } else {
      return self::get_label('msg_bad');
    }	
  } 
} // wf_sn_af_fix_anyone_can_register
