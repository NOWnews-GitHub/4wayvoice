<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_uploads_browsable extends wf_sn_af {  
  static function get_label($label) {
	  $labels = array('title' => 'Make uploads folder non browsable',
					'fixable' => true,
					'info' => 'Fix will modify the uploads folder so it is not browsable.',
					'msg_ok' => 'Fix applied successfully.',
					'msg_bad' => 'Could not apply fix.' ); 
						   
    if(!array_key_exists($label,$labels)){
      return '';	
    } else {	
      return $labels[$label];
    }
  }
  					
  static function fix() {
	$msg = '';
	$error = false;  
      	
    $htaccess_contents = array();
	
	$htaccess_paths = array(ABSPATH . '.htaccess');   
	//Determine if wordpress is in a subdirectory. If that is the case we have to edit the copy of .htaccess in the directory one level up as well
	$siteurl = home_url();
    $wpurl = site_url();
	
	//Determine what subdirectory wordpress is in and check that .htaccess at the ABSPATH location is in that directory as well. if it is we can get the path to the other .htaccess one level up
	$subdirectory = str_replace('/', '', str_replace($siteurl, '', $wpurl));
	if(strpos($htaccess_paths[0], $subdirectory . '/.htaccess') !== false){
	  if(file_exists(dirname(ABSPATH) . '/.htaccess')){
		$htaccess_paths[] = dirname(ABSPATH) . '/.htaccess';
	  } else {
		// if we can't find the htaccess in the directory above something might be wrong
		$msg .= '.htacess file one level up in the directory structure not found.';  
		$error = true;
	  }
	}
	
	if(!$error){
	  foreach($htaccess_paths as $htaccess_fid => $htaccess_path){
		if(!file_exists($htaccess_path)){
		  $msg .= '.htaccess file not found.<br />';
		  $error = true;
		  break;
		}
  
		$htaccess_contents[$htaccess_fid] = @file_get_contents($htaccess_path);
		if(!$htaccess_contents[$htaccess_fid] || strlen($htaccess_contents[$htaccess_fid]) === false){
		  $msg .= 'Cannot read .htaccess file.<br />';
		  $error = true;
		  break;
		}
		
		if(strpos($htaccess_contents[$htaccess_fid], 'Options -Indexes') !== false){
		  $msg .= 'Options -Indexes already exists in your .htaccess. Please reanalyze your website to update the test status.<br />';	
		  $error = true;
		  break;
		}
		
		$new_htaccess = $htaccess_contents[$htaccess_fid] . PHP_EOL . 'Options -Indexes';
		
		if( file_put_contents($htaccess_path, $new_htaccess, LOCK_EX) === false){
		  $msg .= 'Could not write to .htaccess.<br />';	
		  $error = true;
		  break;
		}
	  }
	}
	
    $no_wsod = wf_sn_af::test_wordpress_status();	
    if(!$no_wsod){
      foreach($htaccess_paths as $htaccess_fid => $htaccess_path){
        // check if we have read the htaccess files, so we don't write an empty file
		if(array_key_exists($htaccess_fid, $htaccess_contents) && $htaccess_contents[$htaccess_fid] !== false && strlen($htaccess_contents[$htaccess_fid]) !== false){
		  file_put_contents($htaccess_path, $htaccess_contents[$htaccess_fid], LOCK_EX);
		}
	  }
      return self::get_label('msg_bad');	
    } else {
      wf_sn_af::mark_as_fixed('uploads_browsable');
      return self::get_label('msg_ok');	
    }
  } 
} // wf_sn_af_fix_uploads_browsable
