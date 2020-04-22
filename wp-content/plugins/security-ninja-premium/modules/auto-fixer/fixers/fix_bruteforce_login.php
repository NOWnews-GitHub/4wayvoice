<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_bruteforce_login extends wf_sn_af {
  static function get_label($label) {
    $labels = array('title' => 'Fix weak user passwords',
               'fixable' => true,
               'info' => '',
               'msg_ok' => 'Fix applied successfully.',
               'msg_bad' => 'Could not change the username.');


    if($label == 'info'){
      $return = array();
      $max_users_attack = 5;
      $passwords = file(WF_SN_PLUGIN_DIR . 'misc/brute-force-dictionary.txt', FILE_IGNORE_NEW_LINES);
      $passwords = file(WF_SN_PLUGIN_DIR . 'misc/10k-most-common.txt', FILE_IGNORE_NEW_LINES);
      $bad_usernames = array();

      $users = get_users(array('role' => 'administrator'));
      if (sizeof($users) < $max_users_attack) {
        $users = array_merge($users, get_users(array('role' => 'editor')));
      }
      if (sizeof($users) < $max_users_attack) {
        $users = array_merge($users, get_users(array('role' => 'author')));
      }
      if (sizeof($users) < $max_users_attack) {
        $users = array_merge($users, get_users(array('role' => 'contributor')));
      }
      if (sizeof($users) < $max_users_attack) {
        $users = array_merge($users, get_users(array('role' => 'subscriber')));
      }


      $i = 0;
      foreach ($users as $user) {
        $i++;
        $passwords[] = $user->user_login;
        foreach ($passwords as $password) {
          if (wf_sn_tests::try_login($user->user_login, $password)) {
            $bad_usernames[] = $user->user_login;
            break;
          }
        } // foreach $passwords

        if ($i > $max_users_attack) {
          break;
        }
      } // foreach $users

      $current_user = wp_get_current_user();
      $return = 'This fix can change the password for users that have a weak one. Enter the new desired password for each user or leave the input field blank to assign a randomly generated password.<br /><br />';
      foreach($bad_usernames as $user){
        if($current_user->user_login == $user){
          $return .= '<label for="users_' . $user.'"><strong>' . $user . ':</strong></label> <input type="text" id="users_' . $user . '" name="' . $user . '" value="" /> <span style="color:#F00; font-size:12px;">You are currently logged in as ' . $user . '. Can not set random password. If you leave this field empty the current password will not be changed.</span><br />';
        } else {
          $return .= '<label for="users_' . $user . '"><strong>' . $user . ':</strong></label> <input type="text" id="users_' . $user . '" name="' . $user . '" value="" /><br />';
        }
      }

      return $return;
    }


    if(!array_key_exists($label, $labels)){
      return '';
    } else {
      return $labels[$label];
    }
  }

  static function fix() {
    global $wpdb;

    $fields = json_decode(stripslashes($_GET['fields']), true);
    $return_msg = '';

	// update password for each user
    foreach($fields as $user => $password){
      $user_id = $wpdb->get_var('SELECT ID FROM '.$wpdb->users.' WHERE user_login = "' . $user . '"');
      $current_user = wp_get_current_user();

	  // if a password was entered for the user it will be set to that otherwise generate a random password. a random password will NOT be set for current user
      if( strlen($password) > 0 ){
        $return_msg .= 'Password for user <strong>' . $user . '</strong> set to <strong>' . $password . '</strong><br />';
      } else if($current_user->user_login != $user){
        $password = wp_generate_password();
        $return_msg .= 'Password for user <strong>' . $user . '</strong> set to <strong>' . $password . '</strong><br />';
      } else {
        $password = false;
        $return_msg .= 'Password for user <strong>' . $user . '</strong> was <strong>not changed.</strong><br />';
      }

      if($password){
        wp_set_password($password, $user_id);
      }
    }

	// test again to see if there are any users left that have weak passwords
    $bad_users = wf_sn_tests::bruteforce_login();

    if ( $bad_users['status'] == 10 ) {
      wf_sn_af::mark_as_fixed('bruteforce_login');
      return $return_msg . '<br />' . self::get_label('msg_ok');
    } else {
      $return_msg .= '<br /><span style="color:#F00">Some users still have weak passwords.</span>';
      return $return_msg;
    }
  }
} // wf_sn_af_fix_bruteforce_login
