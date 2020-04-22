<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_wp_header_meta extends wf_sn_af {
  static function get_label($label) {
    $labels = array('title' => 'Hide WP version info',
            'fixable' => true,
            'info' => 'Fix will remove WordPress version info from pages\' header data.',
            'msg_ok' => 'Fix applied successfully.',
            'msg_bad' => 'Unable to apply fix.');  
    
    if(!array_key_exists($label,$labels)){
      return '';	
    } else {	
      return $labels[$label];
    }
  }
  					
  static function fix() {
    if(wf_sn_af::update_option('sn-hide-wp-version', true)){
      wf_sn_af::mark_as_fixed('wp_header_meta');
      return self::get_label('msg_ok');	
    } else {
      return self::get_label('msg_bad');
    }
  }   
  
} // wf_sn_af_fix_wp_header_meta
