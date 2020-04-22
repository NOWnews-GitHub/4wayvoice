<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_check_failed_login_info extends wf_sn_af {
  static function get_label($label) {
    $labels = array('title' => 'Hide unnecessary information on failed login attempts',
					'fixable' => true,
					'info' => 'A universal "wrong username or password" message without any details will be displayed on all failed login attempts.',
					'msg_ok' => 'Fix applied successfully.',
					'msg_bad' => 'Unable to apply fix.');
    if(!array_key_exists($label,$labels)){
      return '';
    } else {
      return $labels[$label];
    }
  }

  static function fix() {
    if( wf_sn_af::update_option('sn-hide-wp-login-info', true) ){
      wf_sn_af::mark_as_fixed('check_failed_login_info');
      return self::get_label('msg_ok');
    } else {
      return self::get_label('msg_bad');
    }
  }
} // wf_sn_af_fix_check_failed_login_info
