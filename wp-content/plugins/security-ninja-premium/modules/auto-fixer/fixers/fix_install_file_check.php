<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_install_file_check extends wf_sn_af {
	static function get_label($label) {
		$labels = array('title' => __('Delete install.php', WF_SN_TEXT_DOMAIN),
			'fixable' => true,
			'info' => __('Delete install.php so it is not accessible on the default location.', WF_SN_TEXT_DOMAIN),
			'msg_ok' => __('Fix applied successfully.', WF_SN_TEXT_DOMAIN),
			'msg_bad' => __('Error - could not delete file.', WF_SN_TEXT_DOMAIN)
		);
		if(!array_key_exists($label,$labels)){
			return '';
		} else {
			return $labels[$label];
		}
	}

	static function fix() {
		if(unlink(ABSPATH . 'wp-admin/install.php') ){
			wf_sn_af::mark_as_fixed('install_file_check');
			return __('install.php file deleted', WF_SN_TEXT_DOMAIN);
		} else {
			return self::get_label('msg_bad');
		}
	}
} // wf_sn_af_fix_install_file_check
