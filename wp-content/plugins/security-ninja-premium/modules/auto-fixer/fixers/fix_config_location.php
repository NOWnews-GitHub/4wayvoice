<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_config_location extends wf_sn_af {
  static function get_label($label) {
    $labels = array('title' => 'Move wp-config.php',
					'fixable' => true,
					'info' => 'Move <i>wp-config.php</i> one level up in the folder structure.',
					'msg_ok' => '<i>wp-config.php</i> moved successfully.',
					'msg_bad' => '<i>wp-config.php</i> move failed.');
    if(!array_key_exists($label,$labels)){
      return '';
    } else {
      return $labels[$label];
    }
  }

  static function fix() {
    $backup_timestamp = time();
	// check if a wp-config file doesn't already exist one level up
    if(file_exists(dirname(dirname(wf_sn_af::$wp_config_path)) . '/wp-config.php')){
      return 'A <i>wp-config.php</i> file already exists on the new location. Can\'t overwrite because it may belong to an existing installation.';
    }

	// check if a wp-config file exists in the default location
	if(!file_exists(dirname(wf_sn_af::$wp_config_path) . '/wp-settings.php')){
      return '<i>wp-config.php</i> file is already in a non-default location.';
    }

	// backup wp-config and generate hash
	wf_sn_af::backup_file(wf_sn_af::$wp_config_path,$backup_timestamp, 'core_updates_check');
	$current_config_hash=wf_sn_af::generate_hash_file(wf_sn_af::$wp_config_path);

	copy(wf_sn_af::$wp_config_path,dirname(dirname(wf_sn_af::$wp_config_path)) . '/wp-config.php');

	// check if file was copied successfully and delete it from the old location
	$new_config_hash=wf_sn_af::generate_hash_file(wf_sn_af::$wp_config_path);
	unlink(wf_sn_af::$wp_config_path);

	// if wordpress fails to load or file has doesn't match restore everything and abort
	$no_wsod = wf_sn_af::test_wordpress_status();
	if(!$no_wsod || $current_config_hash!==$new_config_hash){
      wf_sn_af::backup_file_restore(wf_sn_af::$wp_config_path, $backup_timestamp, 'core_updates_check');
	  unlink(dirname(dirname(wf_sn_af::$wp_config_path)) . '/wp-config.php');
      return self::get_label('msg_bad');
    } else {
      wf_sn_af::mark_as_fixed('config_location');
      return self::get_label('msg_ok');
    }
  }
} // wf_sn_af_fix_config_location
