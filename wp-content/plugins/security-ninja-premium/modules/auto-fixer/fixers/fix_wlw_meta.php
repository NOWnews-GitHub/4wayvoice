<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_wlw_meta extends wf_sn_af {
  static function get_label($label) {
    $labels = array('title' => 'Remove Windows Live Writer Link from wordpress page header',
					'fixable' => true,
					'info' => 'Fix will remove Windows Live Writer link from pages\' header.',
					'msg_ok' => 'Fix applied successfully.',
					'msg_bad' => 'Unable to apply fix.');  
    if(!array_key_exists($label,$labels)){
      return '';	
    } else {	
      return $labels[$label];
    }
  }
  					
  static function fix() {
    if(wf_sn_af::update_option('sn-hide-wlw', true)){
      wf_sn_af::mark_as_fixed('wlw_meta');
      return self::get_label('msg_ok');	
    } else {
      return self::get_label('msg_bad');
    }
  }   
} // wf_sn_af_fix_wlw_meta
