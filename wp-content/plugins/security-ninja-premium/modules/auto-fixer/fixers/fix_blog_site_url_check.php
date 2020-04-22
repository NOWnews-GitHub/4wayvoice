<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_blog_site_url_check extends wf_sn_af {
  static function get_label($label) {
    $labels = array('title' => 'Change WordPress installation address',
					'fixable' => true,
					'info' => 'WordPress installation address will be changed so it\'s not the same as the site address. Please login again after the fix is applied.<br />
							   Enter the new folder name/WordPress address: <br /><strong>' . site_url() . '/</strong><input type="text" name="new_wordpress_address" value="" />',
					'msg_ok' => 'Fix applied successfully.',
					'msg_bad' => 'Unable to apply fix.');
    if(!array_key_exists($label,$labels)){
      return '';
    } else {
      return $labels[$label];
    }
  }

  static function fix() {
    set_time_limit(600);
    $fields = json_decode(stripslashes($_GET['fields']),true);

	$directories_to_move = array(
	                       'wp-admin',
						   'wp-content',
						   'wp-includes');

	$files_to_move = array('.htaccess',
						   'index.php',
						   'license.txt',
						   'my-hacks.php',
						   'readme.html',
						   'wp-atom.php',
						   'wp-activate.php',
						   'wp-blog-header.php',
						   'wp-cron.php',
						   'wp-comments-post.php',
						   'wp-commentsrss2.php',
						   'wp-config-sample.php',
						   'wp-config.php',
						   'wp-feed.php',
						   'wp-links-opml.php',
						   'wp-load.php',
						   'wp-login.php',
						   'wp-mail.php',
						   'wp-pass.php',
						   'wp-rdf.php',
						   'wp-register.php',
						   'wp-rss.php',
						   'wp-rss2.php',
						   'wp-settings.php',
						   'wp-trackback.php',
						   'wp.php',
						   'wp-signup.php',
						   'wp-trackback.php',
						   'xmlrpc.php'
						   );

	// if WP_CONTENT_DIR is already customized abort as there are too many scenarios
	if(WP_CONTENT_DIR != ABSPATH . 'wp-content'){
	  return 'wp-content directory is in a non-default location. Aborting.';
	}

	// check if new directory name is valid
    if(strlen($fields['new_wordpress_address']) !== false){
      $new_path = ABSPATH . $fields['new_wordpress_address'];

	  // if directory already exists abort
	  if(file_exists($new_path)){
		  return $new_path . ' already exists. Aborting.';
	  }

	  // create directory
	  if(mkdir($new_path,0755)){
        chmod($new_path,0755);

		// copy wordpress directories and files to new location. generate hash for each folder/file and compare with hash of new location. if any hashes don't match delete everything and abort
		foreach($directories_to_move as $directory){
		  wf_sn_af::generateHashesDir(ABSPATH . $directory);
		  $hashes = wf_sn_af::$hashed_files;
		  wf_sn_af::$hashed_files = array();

		  wf_sn_af::directory_copy(ABSPATH . $directory, $new_path . '/' . $directory);

		  wf_sn_af::generateHashesDir($new_path . '/' . $directory);
		  $new_hashes = wf_sn_af::$hashed_files;
		  wf_sn_af::$hashed_files = array();

		  if(md5(implode(',', $hashes)) !== md5(implode(',', $new_hashes))){
			wf_sn_af::directory_unlink($new_path);
			return $directory . ' could not be moved sucessfuly. Aborting.';
		  }
		}

		foreach($files_to_move as $file){
		  if(file_exists(ABSPATH . $file)){
			$hash = wf_sn_af::generate_hash_file(ABSPATH.$file);
			copy(ABSPATH . $file, $new_path . '/' . $file);
			$new_hash = wf_sn_af::generate_hash_file($new_path . '/' . $file);

			if($hash !== $new_hash){
			  wf_sn_af::directory_unlink($new_path);
			  return $file . ' could not be moved sucessfuly. Aborting.';
			}
		  }
		}

		$current_siteurl= site_url();
		$new_siteurl= site_url() . '/' . $fields['new_wordpress_address'];
		// update siteurl
        update_option('siteurl', $new_siteurl);

		// update index.php wp-blog-header.php path
        $index_file_contents = file_get_contents(ABSPATH . 'index.php');
        $updated_index_file_contents = str_replace('\'/wp-blog-header.php\'', '\'/' . $fields['new_wordpress_address'] . '/wp-blog-header.php\'', $index_file_contents);
		if( false !== file_put_contents(ABSPATH . 'index.php', $updated_index_file_contents, LOCK_EX)){

		  // check if wordpress works at the new location
		  $no_wsod = false;
		  $response = wp_remote_post($new_siteurl . '/wp-admin/admin-ajax.php', array( 'timeout' => 120, 'sslverify' => false, 'body' => array ('action' => 'wf_sn_af_test_wp') ));
		  if (!is_wp_error($response) && trim($response['body']) == $new_siteurl) {
			$no_wsod = true;
		  }

		  // if everything works delete old directories and files except .htaccess and index.php
          if($no_wsod){
			foreach($directories_to_move as $directory){
			  wf_sn_af::directory_unlink(ABSPATH . $directory);
			}
			foreach($files_to_move as $file){
			  if($file != '.htaccess' && $file != 'index.php'){
				unlink(ABSPATH . $file);
			  }
			}
			wf_sn_af::mark_as_fixed('blog_site_url_check');
            return 'Wordpress files moved successfully. You need to login again to the admin panel url: <a href="' . $new_siteurl . '/wp-admin/tools.php?page=wf-sn' . '">' . $new_siteurl . '/wp-admin/</a>';
          } else {
			// if wsod check fails restore index.php, siteurl and delete new created directory
            file_put_contents(ABSPATH . 'index.php', $index_file_contents, LOCK_EX);
			update_option('siteurl', $current_siteurl);
            wf_sn_af::directory_unlink($new_path);
            return 'An error occured and could not move the wordpress files into the new directory. No changes have been made.';
          }
        } else {
		  // if index.php could not be updated delete new directory
		  wf_sn_af::directory_unlink($new_path);
          return 'Could not write index.php file';
        }
      } else {
        return 'Could not create new directory';
      }
    } else {
      return 'Enter a valid directory name';
    }
  }
} // wf_sn_af_fix_blog_site_url_check
