<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */


/*
if we have code to edit htaccess then add
RewriteCond %{REQUEST_URI} !^/wp-admin [NC]
RewriteCond %{QUERY_STRING} ^author=\d+ [NC,OR]
RewriteRule ^ - [L,R=403]

if not .... let me know so we can figure out the quickes solution

*/


class wf_sn_af_fix_usernames_enumeration extends wf_sn_af {
  static function get_label($label) {
    $labels = array('title' => 'Prevent usernames discovery via user IDs',
                    'fixable' => true,
                    'info' => 'Fix will modify your .htaccess file by adding rules to prevent redirections from <i>yoursite.com/?author={id}</i> to <i>yoursite.com/author/username</i>',
                    'msg_ok' => 'Fix applied successfully',
                    'msg_bad' => 'Failed to apply fix');  
    if(!array_key_exists($label, $labels)){
        return '';	
    } else {	
        return $labels[$label];
    }
  }

  static function fix() {
    if(wf_sn_af::update_option('sn-disable-user-enumeration', true)){
      wf_sn_af::mark_as_fixed('usernames_enumeration');
      return self::get_label('msg_ok');	
    } else {
      return self::get_label('msg_bad');
    }
  }   
} // wf_sn_af_fix_usernames_enumeration
