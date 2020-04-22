<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_id1_user_check extends wf_sn_af {
  
  static function get_label($label) {
	  $labels = array('title' => 'Change user ID 1',
						 'fixable' => true,
						 'info' => 'This fix will change the ID of the user with the ID "1" to the next avilable ID in the users table. You will need to login again after this fix is applied.',
						 'msg_ok' => 'User ID changed successfully to ',
						 'msg_bad' => 'Could not change user ID.' ); 
						   
    if(!array_key_exists($label,$labels)){
      return '';	
    } else {	
      return $labels[$label];
    }
  }
  					
  static function fix() {
    global $wpdb;
    $success = false;
    
	// get data of user with ID 1
    $userid1_data = $wpdb->get_row('SELECT * FROM ' . $wpdb->users . ' WHERE ID = "1"');
		
    if($userid1_data){      
	  // create a new user as a copy of user ID 1
      $wpdb->insert( 
        $wpdb->users, 
        array( 
          'user_login' => $userid1_data->user_login, 
          'user_pass' => $userid1_data->user_pass, 
          'user_nicename' => $userid1_data->user_nicename, 
          'user_email' => $userid1_data->user_email, 
          'user_url' => $userid1_data->user_url, 
          'user_registered' => $userid1_data->user_registered,
          'user_activation_key' => $userid1_data->user_activation_key,
          'user_status' => $userid1_data->user_status,
          'display_name' => $userid1_data->display_name 
        ), 
        array('%s','%s','%s','%s','%s','%s','%s','%s','%s') 
      );
      $new_user_id = $wpdb->insert_id;
      
	  // if new user has been successfully created assign posts and meta and delete user ID 1
      if($new_user_id>0){
        $wpdb->query('UPDATE ' . $wpdb->usermeta . ' SET user_id="' . $new_user_id . '" WHERE user_id = "1"');
        $wpdb->query('UPDATE ' . $wpdb->posts . ' SET post_author="' . $new_user_id . '" WHERE post_author = "1"');
        $wpdb->query('DELETE FROM ' . $wpdb->users . ' WHERE ID = "1"');
      }
      $success = true;
	  
	  // logout user
      wp_logout();
    } 			
        
    if ( !$success ) {
      return self::get_label('msg_bad');
    } else {
      wf_sn_af::mark_as_fixed('user_exists');
      return self::get_label('msg_ok') . $new_user_id;	
    }	
  } 
} // wf_sn_af_fix_id1_user_check
