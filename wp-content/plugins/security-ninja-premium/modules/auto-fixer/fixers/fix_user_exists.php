<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_user_exists extends wf_sn_af {  
  static function get_label($label) {
	$labels = array('title' => 'Change admin username',
						 'fixable' => true,
						 'info' => 'Fix will change the admin username. <br /><span style="color:#F00;">Once the fix is applied you will need to login again with the new username. Password will not be changed.</span><br /><br /> 
						 Please input your new desired username: <input type="text" name="new_user_login" value="" /><br /><small>Try not to use usernames like: "root", "god", "null" or similar ones.</small>',
						 'msg_ok' => 'Fix applied successfully.',
						 'msg_bad' => 'Could not change username.' ); 
						   
    if(!array_key_exists($label,$labels)){
      return '';	
    } else {	
      return $labels[$label];
    }
  }
  					
  static function fix() {
    global $wpdb;
    
    $fields = json_decode(stripslashes($_GET['fields']),true);
    
	// check if admin username still exists
    $admin_user_id = $wpdb->get_var('SELECT ID FROM '.$wpdb->users.' WHERE user_login = "admin"');
    if(!$admin_user_id){
      return 'Username admin does not exist. Please reanalyze your website to update the test status.';
    }	
	
	// check if new username entered is valid
	if(strlen($fields['new_user_login'])<1){
	  return 'Username field cannot be empty.';	
	}
      
    if ( false === $wpdb->update($wpdb->users,array( 'user_login' => $fields['new_user_login'] ), array( 'ID' => $admin_user_id )) ) {
      return self::get_label('msg_bad');
    } else {
      wf_sn_af::mark_as_fixed('user_exists');
      return self::get_label('msg_ok');	
    }	
  } 
} // wf_sn_af_fix_user_exists
