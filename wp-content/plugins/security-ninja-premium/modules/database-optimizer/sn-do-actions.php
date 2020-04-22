<?php
if ( ! function_exists( 'add_action' ) ) {
	die( 'Please don\'t open this file directly!' );
}


class wf_sn_do_actions extends wf_sn_do {
	public static function get_actions() {
		$actions                        = array();
		$actions['optimize_tables']     = 'Optimize database tables';
		$actions['clean_revisions']     = 'Remove all post revisions';
		$actions['clean_spam']          = 'Remove all spam &amp; trashed comments';
		$actions['clean_drafts']        = 'Remove all auto-draft &amp; trashed posts';
		$actions['clean_comments']      = 'Remove all unapproved comments';
		$actions['clean_transients']    = 'Remove all expired transients';
		$actions['clean_pingbacks']     = 'Remove all pingbacks';
		$actions['clean_trackbacks']    = 'Remove all trackbacks';
		$actions['clean_post_meta']     = 'Remove orphaned post meta data';
		$actions['clean_comment_meta']  = 'Remove orphaned comment meta data';
		$actions['clean_category_meta'] = 'Remove orphaned relationship data';

		return $actions;
	} // get_actions

	public static function info_optimize_tables() {
		global $wpdb;

		$tmp          = '';
		$overhead     = $count = 0;
		$table_status = $wpdb->get_results( 'SHOW TABLE STATUS' );
		$table_prefix = parent::get_table_prefix();

		if ( is_array( $table_status ) ) {
			foreach ( $table_status as $index => $table ) {
				if ( 0 !== stripos( $table->Name, $table_prefix ) || 'InnoDB' === $table->Engine ) {
					continue;
				}
				$count++;
				$overhead += $table->Data_free;
			}
		}

		$tmp = $count . ' tables will be optimized and ' . parent::format_size( $overhead ) . ' of space regained.';

		return $tmp;
	} // info_optimize_tables


	public static function do_optimize_tables() {
		global $wpdb;
		$count = 0;

		$table_status = $wpdb->get_results( 'SHOW TABLE STATUS' );
		$table_prefix = parent::get_table_prefix();

		if ( is_array( $table_status ) ) {
			foreach ( $table_status as $index => $table ) {
				if ( 0 !== stripos( $table->Name, $table_prefix ) || 'InnoDB' === $table->Engine ) {
					continue;
				}
				$count++;
				// Cannot use ->prepare here since table name cannot be encapsulated
				$wpdb->query( 'OPTIMIZE TABLE ' . $table->Name );

			}
		}

		return $count . ' tables have been optimized.';
	} // do_optimize_tables


	public static function info_clean_revisions() {
		global $wpdb;
		$tmp = '';
		// PREPARE

	$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type=%s", 'revision' ) );

		if ( ! empty( $count ) ) {
			$tmp = number_format_i18n( $count ) . ' post revisions found for removal.';
		} else {
			$tmp = 'No post revisions found.';
		}

		return $tmp;
	} // info_clean_revisions


	public static function do_clean_revisions() {
		global $wpdb;

		$count = (int) $wpdb->query( 'DELETE FROM ' . $wpdb->posts . " WHERE post_type = 'revision'" );

		return $count . ' revisions have been removed.';
	} // do_clean_revisions


	public static function info_clean_spam() {
		global $wpdb;
		$tmp = '';

		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = %s or comment_approved = %s", 'spam', 'trash' ) );

		if ( ! empty( $count ) ) {
			$tmp = number_format_i18n( $count ) . ' spam/trashed comments found for removal.';
		} else {
			$tmp = 'No spam or trashed comments found.';
		}

		return $tmp;
	} // info_clean_spam


	public static function do_clean_spam() {
		global $wpdb;

		$count = (int) $wpdb->query( 'DELETE FROM ' . $wpdb->comments . " WHERE comment_approved = 'spam' or comment_approved = 'trash'" );

		return $count . ' spam/trashed comments have been removed.';
	} // do_clean_spam


	public static function info_clean_drafts() {
		global $wpdb;
		$tmp = '';

		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status=%s or post_status=%s", 'auto-draft', 'trash' ) );

		if ( ! empty( $count ) ) {
			$tmp = number_format_i18n( $count ) . ' auto-draft/trash posts found for removal.';
		} else {
			$tmp = 'No auto-draft or trashed posts found.';
		}

		return $tmp;
	} // info_clean_drafts


	public static function do_clean_drafts() {
		global $wpdb;

		$count = (int) $wpdb->query( 'DELETE FROM ' . $wpdb->posts . " WHERE post_status = 'auto-draft' or post_status = 'trash'" );

		return $count . ' auto-draft/trashed posts have been removed.';
	} // do_clean_drafts


	public static function info_clean_comments() {
		global $wpdb;
		$tmp = '';

		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = %d", 0 ) );

		if ( ! empty( $count ) ) {
			$tmp = number_format_i18n( $count ) . ' unapproved comments found for removal.';
		} else {
			$tmp = 'No unapproved comments found.';
		}

		return $tmp;
	} // info_clean_comments


	public static function do_clean_comments() {
		global $wpdb;

		$count = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->comments} WHERE comment_approved = %d", 0 ) );

		return $count . ' unapproved comments have been removed.';
	} // do_clean_comments


	public static function info_clean_transients() {
		global $wpdb;
		$tmp                       = '';
		$sitemeta_table_transients = 0;

		$sql = '
			SELECT
				COUNT(*)
			FROM
				' . $wpdb->options . ' a, ' . $wpdb->options . " b
			WHERE
				a.option_name LIKE '%_transient_%' AND
				a.option_name NOT LIKE '%_transient_timeout_%' AND
				b.option_name = CONCAT(
					'_transient_timeout_',
					SUBSTRING(
						a.option_name,
						CHAR_LENGTH('_transient_') + 1
					)
				)
			AND b.option_value < UNIX_TIMESTAMP()
		";

		$count = $wpdb->get_var( $sql );

		if ( ! empty( $count ) ) {
			$tmp = number_format_i18n( $count ) . ' expired transients found for removal.';
		} else {
			$tmp = 'No expired transients found.';
		}

		return $tmp;
	} // info_clean_transients


	public static function do_clean_transients() {
		global $wpdb;

		$sql   = '
			DELETE
				a, b
			FROM
				' . $wpdb->options . ' a, ' . $wpdb->options . " b
			WHERE
				a.option_name LIKE '%_transient_%' AND
				a.option_name NOT LIKE '%_transient_timeout_%' AND
				b.option_name = CONCAT(
					'_transient_timeout_',
					SUBSTRING(
						a.option_name,
						CHAR_LENGTH('_transient_') + 1
					)
				)
			AND b.option_value < UNIX_TIMESTAMP()
		";
		$count = (int) $wpdb->query( $sql );

		return $count . ' expired transients have been removed.';
	} //do_clean_transients


	public static function info_clean_pingbacks() {
		global $wpdb;
		$tmp = '';
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type=%s", 'pingback' ) );
		if ( ! empty( $count ) ) {
			$tmp = number_format_i18n( $count ) . ' pingbacks found for removal.';
		} else {
			$tmp = 'No pingbacks found.';
		}

		return $tmp;
	} // info_clean_pingbacks


	public static function do_clean_pingbacks() {
		global $wpdb;

		$count = (int) $wpdb->query( 'DELETE FROM ' . $wpdb->comments . " WHERE comment_type='pingback'" );

		return $count . ' pingbacks have been removed.';
	} // do_clean_pingbacks


	public static function info_clean_trackbacks() {
		global $wpdb;
		$tmp = '';

		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type=%s", 'trackback' ) );

		if ( ! empty( $count ) ) {
			$tmp = number_format_i18n( $count ) . ' trackbacks found for removal.';
		} else {
			$tmp = 'No trackbacks found.';
		}

		return $tmp;
	} // info_clean_trackbacks


	public static function do_clean_trackbacks() {
		global $wpdb;

		$count = (int) $wpdb->query( 'DELETE FROM ' . $wpdb->comments . " WHERE comment_type='trackback'" );

		return $count . ' trackbacks have been removed.';
	} // do_clean_trackbacks


	public static function info_clean_post_meta() {
		global $wpdb;
		$tmp = '';

		$count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->postmeta . ' pm LEFT JOIN ' . $wpdb->posts . ' wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL' );

		if ( ! empty( $count ) ) {
			$tmp = number_format_i18n( $count ) . ' orphaned post meta data found for removal.';
		} else {
			$tmp = 'No orphaned post meta data found.';
		}

		return $tmp;
	} // info_clean_post_meta


	public static function do_clean_post_meta() {
		global $wpdb;

		$count = (int) $wpdb->query( 'DELETE pm FROM ' . $wpdb->postmeta . ' pm LEFT JOIN ' . $wpdb->posts . ' wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL' );

		return $count . ' orphaned post meta data have been removed.';
	} // do_clean_post_meta


	public static function info_clean_comment_meta() {
		global $wpdb;
		$tmp = '';

		$count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->commentmeta . ' WHERE comment_id NOT IN (SELECT comment_id FROM ' . $wpdb->comments . ')' );

		if ( ! empty( $count ) ) {
			$tmp = number_format_i18n( $count ) . ' orphaned comment meta data found for removal.';
		} else {
			$tmp = 'No orphaned comment meta data found.';
		}

		return $tmp;
	} // info_clean_comment_meta


	public static function do_clean_comment_meta() {
		global $wpdb;

		$count = (int) $wpdb->query( 'DELETE FROM ' . $wpdb->commentmeta . ' WHERE comment_id NOT IN (SELECT comment_id FROM ' . $wpdb->comments . ')' );
		$wpdb->query( 'DELETE FROM ' . $wpdb->commentmeta . " WHERE meta_key LIKE '%akismet%'" );

		return $count . ' orphaned comment meta data have been removed.';
	} // do_clean_comment_meta


	public static function info_clean_category_meta() {
		global $wpdb;
		$tmp = '';

		$count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->term_relationships . ' WHERE term_taxonomy_id = 1 AND object_id NOT IN (SELECT id FROM ' . $wpdb->posts . ')' );

		if ( ! empty( $count ) ) {
			$tmp = number_format_i18n( $count ) . ' orphaned relationship data found for removal.';
		} else {
			$tmp = 'No orphaned relationship data found.';
		}

		return $tmp;
	} // info_clean_category_meta


	public static function do_clean_category_meta() {
		global $wpdb;

		$count = (int) $wpdb->query( 'DELETE FROM ' . $wpdb->term_relationships . ' WHERE term_taxonomy_id=1 AND object_id NOT IN (SELECT id FROM ' . $wpdb->posts . ')' );

		return $count . ' orphaned relationship data have been removed.';
	} // do_clean_category_meta
} // wf_sn_do_actions
