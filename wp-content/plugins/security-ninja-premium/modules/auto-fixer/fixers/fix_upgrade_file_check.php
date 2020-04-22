<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_upgrade_file_check extends wf_sn_af {
  static function get_label($label) {
    $labels = array('title' => 'Delete upgrade.php',
					'fixable' => true,
					'info' => 'Delete upgrade.php so it is not accessible on the default location.',
					'msg_ok' => 'Fix applied successfully.',
					'msg_bad' => 'Delete failed.');
    if(!array_key_exists($label,$labels)){
      return '';
    } else {
      return $labels[$label];
    }
  }

  static function fix() {
    if(unlink(ABSPATH . 'wp-admin/upgrade.php') ){
      wf_sn_af::mark_as_fixed('upgrade_file_check');
      return __('upgrade.php file deleted', WF_SN_TEXT_DOMAIN);
    } else {
      return self::get_label('msg_bad');
    }
  }
} // wf_sn_af_fix_upgrade_file_check
