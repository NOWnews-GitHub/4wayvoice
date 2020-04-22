<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_license_check extends wf_sn_af {
  static function get_label($label) {
    $labels = array('title' => __('Delete license.txt file', WF_SN_TEXT_DOMAIN) ,
					'fixable' => true,
					'info' => __('license.txt file will be deleted.', WF_SN_TEXT_DOMAIN) ,
					'msg_ok' => __('Fix applied successfully.', WF_SN_TEXT_DOMAIN),
					'msg_bad' => __('Delete failed.', WF_SN_TEXT_DOMAIN)
        );
    if(!array_key_exists($label,$labels)){
      return '';
    } else {
      return $labels[$label];
    }
  }

  static function fix() {
	if(!file_exists(ABSPATH . 'license.txt')){
	  return 'license.txt not found at default location.';
	}

    if(unlink(ABSPATH . 'license.txt') ){
      wf_sn_af::mark_as_fixed('readme_check');
      return self::get_label('msg_ok');
    } else {
      return self::get_label('msg_bad');
    }

  }
} // wf_sn_af_fix_readme_check
