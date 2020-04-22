<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

if(!class_exists("Core_Upgrader")){
  require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );  		
}		
	
class wf_sn_af_core_upgrader extends Core_Upgrader
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
	}
}
