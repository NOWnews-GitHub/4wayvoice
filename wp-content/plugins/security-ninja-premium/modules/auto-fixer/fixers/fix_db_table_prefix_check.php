<?php
/*
 * Security Ninja
 * (c) 2011 - 2018 Web factory Ltd
 *
 */

class wf_sn_af_fix_db_table_prefix_check extends wf_sn_af {  
  static function get_label($label) {
	  $labels = array('title' => 'Change database table prefix',
						 'fixable' => true,
						 'info' => 'Knowing the names of your database tables can help an attacker dump the table\'s data and get to sensitive information like password hashes. 
						 Since WP table names are predefined the only way you can change table names is by using a unique prefix. 
						 One that\'s different from "wp_" or any similar variation such as "wordpress_". <br /><br />
						 Enter your new desired table prefix: <input type="text" name="new_table_prefix" value="" />',
						 'msg_ok' => 'Prefix changed sucessfuly',
						 'msg_bad' => 'Could not change table prefix' ); 
						   
    if(!array_key_exists($label,$labels)){
      return '';	
    } else {	
      return $labels[$label];
    }
  }
  					
  static function fix() {
    global $wpdb;
    $fields = json_decode(stripslashes($_GET['fields']),true);
      
	if($wpdb->prefix != 'wp_'){
		return 'Table prefix is already changed to ' . $wpdb->prefix . '. Please reanalyze your website to update the status of this test.';	
	}
	
	// get a list of all tables in the database  
    $tables = $wpdb->get_results('SELECT * FROM information_schema.tables WHERE table_schema="' . DB_NAME . '"');
	
	// filter out all wp_ tables
    $table_names = array();	
    foreach($tables as $table_info){
      if(strpos($table_info->TABLE_NAME,'wp_') == 0){
        $table_names[]=$table_info->TABLE_NAME;
      }   
    }
    	
	// for each wp_table make a copy with the new desired prefix
	$failed=false;
	foreach($table_names as $table){
	  $new_table_name = $fields['new_table_prefix'] . '_' . substr($table,3);
	  if(false === $wpdb->query('CREATE TABLE `' . $new_table_name . '` LIKE `' . $table . '`')){
	    $failed=true;
		break;  
	  } else if(false === $wpdb->query('INSERT INTO `' . $new_table_name . '` SELECT * FROM `' . $table . '`')){
		$failed=true;
		break;  
	  }
    }
    
	// if copying any of the tables failed abort and remove any created tables
	if($failed){
	  foreach($table_names as $table){
         $new_table_name=$fields['new_table_prefix'].'_'.substr($table,3);
         $wpdb->query('DROP TABLE '.$new_table_name);
      }
      return self::get_label('msg_bad');
	}
	
	// update prefix in _usermeta and _options
    $wpdb->query('UPDATE `'.$fields['new_table_prefix'].'_usermeta` SET meta_key = replace(meta_key, \'wp_\', \''.$fields['new_table_prefix'].'_\') WHERE meta_key LIKE \'wp_%\' ');
    $wpdb->query('UPDATE `'.$fields['new_table_prefix'].'_options` SET option_name = replace(option_name, \'wp_\', \''.$fields['new_table_prefix'].'_\') WHERE option_name LIKE \'wp_%\' ');
	
	// updae wp_config   
    $backup_timestamp=time();
    wf_sn_af::backup_file(wf_sn_af::$wp_config_path,$backup_timestamp,'db_table_prefix_check');	
    wf_sn_af::edit_variable(wf_sn_af::$wp_config_path, 'table_prefix', '\''.$fields['new_table_prefix'].'_\'');    
	
	// test if wordpress works, if not restore everything and drop created tables
    $no_wsod = wf_sn_af::test_wordpress_status();    
    if(!$no_wsod){
      wf_sn_af::backup_file_restore(wf_sn_af::$wp_config_path,$backup_timestamp,'db_table_prefix_check');
      foreach($table_names as $table){
         $new_table_name=$fields['new_table_prefix'].'_'.substr($table,3);
         $wpdb->query('DROP TABLE '.$new_table_name);
      }
      return self::get_label('msg_bad');	
    } else {	
      foreach($table_names as $table){
         $wpdb->query('DROP TABLE '.$table);
      }
      wf_sn_af::mark_as_fixed('db_table_prefix_check');
      return self::get_label('msg_ok');	
    }
  } 
} // wf_sn_af_fix_db_table_prefix_check
