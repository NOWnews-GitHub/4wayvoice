<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

if(!class_exists("Core_Upgrader")){
  require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );  		
}		
	
class wf_sn_af_theme_upgrader extends Theme_Upgrader
{
	public function upgrade_strings() {
		$this->strings['up_to_date'] = '';
		$this->strings['locked'] = '';
		$this->strings['no_package'] = '';
		$this->strings['downloading_package'] = '';
		$this->strings['unpack_package'] = '';
		$this->strings['copy_failed'] = '';
		$this->strings['copy_failed_space'] = '';
		$this->strings['start_rollback'] = '';
		$this->strings['rollback_was_required'] = '';
		$this->strings['bad_request'] = '';
		$this->strings['fs_unavailable'] = '';
		$this->strings['fs_error'] = '';
		$this->strings['fs_no_root_dir'] = '';
		$this->strings['fs_no_content_dir'] = '';
		$this->strings['fs_no_plugins_dir'] = '';
		$this->strings['fs_no_themes_dir'] = '';
		$this->strings['fs_no_folder'] = '';
		$this->strings['download_failed'] = '';
		$this->strings['installing_package'] = '';
		$this->strings['no_files'] = '';
		$this->strings['folder_exists'] = '';
		$this->strings['mkdir_failed'] = '';
		$this->strings['incompatible_archive'] = '';
		$this->strings['files_not_writable'] = '';
		$this->strings['maintenance_start'] = '';
		$this->strings['maintenance_end'] = '';
		$this->strings['remove_old'] = '';
		$this->strings['process_success'] = '';
		$this->skin->done_header=true;
		$this->skin->done_footer=true;
	}
}
