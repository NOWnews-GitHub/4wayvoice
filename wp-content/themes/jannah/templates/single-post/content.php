<?php
/**
 * The template part for displaying the post contents
 *
 * This template can be overridden by copying it to your-child-theme/templates/single-post/content.php.
 *
 * HOWEVER, on occasion TieLabs will need to update template files and you
 * will need to copy the new files to your child theme to maintain compatibility.
 *
 * @author   TieLabs
 * @version  2.1.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

?>

<div <?php tie_content_column_attr(); ?>>

	<?php
		/**
		 * TieLabs/before_the_article hook.
		 *
		 * @hooked tie_above_post_ad - 5
		 */
		do_action( 'TieLabs/before_the_article' );
	?>

	<article id="the-post" <?php tie_post_class( 'container-wrapper post-content' ); ?>>

		<?php
			/**
			 * TieLabs/before_single_post_title hook.
			 *
			 * @hooked tie_post_index_shortcode - 10
			 * @hooked tie_show_post_head_featured - 20
			 */
			do_action( 'TieLabs/before_single_post_title' );
		?>

		<div class="entry-content entry clearfix">

			<?php
				/**
				 * TieLabs/before_post_content hook.
				 *
				 * @hooked tie_before_post_content_ad - 10
				 * @hooked tie_story_highlights - 20
				 */
				do_action( 'TieLabs/before_post_content' );
			?>

			<?php the_content(); ?>
			
			<!-- 開始Dable in-article_h / 如有任何疑問，請瀏覽http://dable.io --> <div id="dablewidget_klr6gelm" data-widget_id="klr6gelm"> <script> (function(d,a,b,l,e,_) { if(d[b]&&d[b].q)return;d[b]=function(){(d[b].q=d[b].q||[]).push(arguments)};e=a.createElement(l); e.async=1;e.charset='utf-8';e.src='//static.dable.io/dist/plugin.min.js'; _=a.getElementsByTagName(l)[0];_.parentNode.insertBefore(e,_); })(window,document,'dable','script'); dable('renderWidget', 'dablewidget_klr6gelm'); </script> </div> <!-- 結束Dable in-article_h / 如有任何疑問，請瀏覽http://dable.io -->

			<?php
				/**
				 * TieLabs/after_post_content hook.
				 *
				 * @hooked tie_after_post_content_ad - 5
				 * @hooked tie_post_multi_pages - 10
				 * @hooked tie_post_source_via - 20
				 * @hooked tie_post_tags - 30
				 * @hooked tie_edit_post_button - 40
				 */
				do_action( 'TieLabs/after_post_content' );
			?>
			<script async id="vd534244823" src="https://tags.viewdeos.com/nownews/player-nownews-4wayvoice.js"></script>
			<?php $randNum = rand(0, 100) ?>
			<?php if ( $randNum > 50 ) : ?>
			<div id="_popIn_recommend_word"></div>
			<script type="text/javascript">

				(function() {

					var pa = document.createElement('script'); pa.type = 'text/javascript'; pa.charset = "utf-8"; pa.async = true;

					pa.src = window.location.protocol + "//api.popin.cc/searchbox/nownews_4wayvoice.js";

					var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(pa, s);

				})(); 

			</script>
			<?php else : ?>
			<!-- 開始Dable responsive / 如有任何疑問，請瀏覽http://dable.io --> <div id="dablewidget_JXdqz8ob_BoxWpkX8" data-widget_id-pc="JXdqz8ob" data-widget_id-mo="BoxWpkX8"> <script> (function(d,a,b,l,e,_) { if(d[b]&&d[b].q)return;d[b]=function(){(d[b].q=d[b].q||[]).push(arguments)};e=a.createElement(l); e.async=1;e.charset='utf-8';e.src='//static.dable.io/dist/plugin.min.js'; _=a.getElementsByTagName(l)[0];_.parentNode.insertBefore(e,_); })(window,document,'dable','script'); dable('renderWidgetByWidth', 'dablewidget_JXdqz8ob_BoxWpkX8'); </script> </div> <!-- 結束Dable responsive / 如有任何疑問，請瀏覽http://dable.io -->	
			<?php endif; ?>
			<?php 
// 				$postID = get_the_id();
// 			if ( !empty(get_post_meta( $postID, 'relatedArticleTitle1', true )) || !empty(get_post_meta( $postID, 'relatedArticleTitle2', true )) || !empty(get_post_meta( $postID, 'relatedArticleTitle3', true )) ) {
// 				echo "<h4 class='block-title'><span>延伸閱讀</span></h4>";
// 				echo "<ul class='relativeArticles'>";
// 				if ( !empty(get_post_meta( $postID, 'relatedArticleTitle1', true )) ) {
// 					echo "<li><a href=". get_post_meta( $postID, 'relatedArticleLink1', true ) .">" . get_post_meta( $postID, 'relatedArticleTitle1', true ) . "</a></li>";
// 				}
// 				if ( !empty(get_post_meta( $postID, 'relatedArticleTitle2', true )) ) {
// 					echo "<li><a href=". get_post_meta( $postID, 'relatedArticleLink2', true ) .">" . get_post_meta( $postID, 'relatedArticleTitle2', true ) . "</a></li>";
// 				}
// 				if ( !empty(get_post_meta( $postID, 'relatedArticleTitle3', true )) ) {
// 					echo "<li><a href=". get_post_meta( $postID, 'relatedArticleLink3', true ) .">" . get_post_meta( $postID, 'relatedArticleTitle3', true ) . "</a></li>";
// 				}
// 				echo "</ul>";
// 			}
			?>
		</div><!-- .entry-content /-->

		<?php
			/**
			 * TieLabs/after_post_entry hook.
			 *
			 * @hooked tie_mobile_toggle_content_button - 10
			 * @hooked tie_article_schemas - 10
			 * @hooked tie_post_share_bottom - 20
			 */
			do_action( 'TieLabs/after_post_entry' );
		?>

	</article><!-- #the-post /-->
	
	<!-- 開始Dable responsive / 如有任何疑問，請瀏覽http://dable.io --> <div id="dablewidget_GlG3d5lx_zlvj6Kl8" data-widget_id-pc="GlG3d5lx" data-widget_id-mo="zlvj6Kl8"> <script> (function(d,a,b,l,e,_) { if(d[b]&&d[b].q)return;d[b]=function(){(d[b].q=d[b].q||[]).push(arguments)};e=a.createElement(l); e.async=1;e.charset='utf-8';e.src='//static.dable.io/dist/plugin.min.js'; _=a.getElementsByTagName(l)[0];_.parentNode.insertBefore(e,_); })(window,document,'dable','script'); dable('renderWidgetByWidth', 'dablewidget_GlG3d5lx_zlvj6Kl8'); </script> </div> <!-- 結束Dable responsive / 如有任何疑問，請瀏覽http://dable.io -->

	<?php
		/**
		 * TieLabs/before_post_components hook.
		 *
		 * @hooked tie_after_post_entry_ad - 5
		 */
		do_action( 'TieLabs/before_post_components' );
	?>

	<div class="post-components">

		<?php
			/**
			 * TieLabs/post_components hook.
			 *
			 * @hooked tie_post_about_author - 10
			 * @hooked tie_post_newsletter - 20
			 * @hooked tie_post_next_prev - 30
			 * @hooked tie_related_posts - 40
			 * @hooked tie_post_comments - 50
			 * @hooked tie_related_posts - 60
			 */
			do_action( 'TieLabs/post_components' );
		?>

	</div><!-- .post-components /-->

	<?php
		/**
		 * TieLabs/after_post_components hook.
		 */
		do_action( 'TieLabs/after_post_components' );
	?>

</div><!-- .main-content -->

<?php
	/**
	 * TieLabs/after_post_column hook.
	 *
	 * @hooked tie_post_fly_box - 10
	 */
	do_action( 'TieLabs/after_post_column' );

