<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_db_password_check extends wf_sn_af {  
  static function get_label($label) {
	  $labels = array('title' => 'Update WordPress Database Password',
						 'fixable' => true,
						 'info' => 'Update the WordPress database password to a stronger one.',
						 'msg_ok' => 'Fix applied successfully.',
						 'msg_bad' => 'Could not update password.' ); 
						   
    if(!array_key_exists($label,$labels)){
      return '';	
    } else {	
      return $labels[$label];
    }
  }
  					
  static function fix() {
    global $wpdb;
    $backup_timestamp=time();  
    wf_sn_af::backup_file(wf_sn_af::$wp_config_path,$backup_timestamp, 'db_password_check');	
    
	// generate new password and update database and wp-config
    $new_database_password = wp_generate_password( 16 );
    $wpdb->query('SET PASSWORD = \''.$new_database_password.'\'');
    wf_sn_af::update_define(wf_sn_af::$wp_config_path,'DB_PASSWORD',$new_database_password);
     
	// if wordpress fails to load restore wp-config
    $no_wsod = wf_sn_af::test_wordpress_status();
    if(!$no_wsod){
      wf_sn_af::backup_file_restore(wf_sn_af::$wp_config_path,$backup_timestamp,'db_password_check');
      return self::get_label('msg_bad');	
    } else {
      wf_sn_af::mark_as_fixed('db_password_check');
      return self::get_label('msg_ok');	
    }
  } 
} // wf_sn_af_fix_db_password_check
