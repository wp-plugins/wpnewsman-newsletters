<?php
	define('WP_ADMIN', true);
	define('IFRAME_REQUEST', true);
	require_once ('../../../wp-config.php');
	require_once('class.utils.php');
	require_once('class.options.php');

	$o = newsmanOptions::getInstance();
	$u = newsmanUtils::getInstance();

?>

<html>
<head>
	<title></title>
	<?php wp_head(); ?>
	<script type="text/javascript">
		window.ajaxurl = '<?php echo admin_url( 'admin-ajax.php', 'relative' ); ?>';
	</script>
	<style type="text/css">
		.wrap {
			padding: 1em;
		}

		#post-selector {
			height: 100%;
			box-sizing: border-box;
			-moz-box-sizing: border-box;
			-webkit-box-sizing: border-box;
			-o-box-sizing: border-box;
		}

		.maxh {
			height: 100%;
			box-sizing: border-box;
			-moz-box-sizing: border-box;
			-webkit-box-sizing: border-box;
			-o-box-sizing: border-box;
		}

		.rel {
			position: relative !important;
		}

		#newsman-search {
			width: 100%;
			box-sizing: border-box;
			-moz-box-sizing: border-box;
			-webkit-box-sizing: border-box;
			-o-box-sizing: border-box;
			font-size: 16px;
			line-height: 24px;
			height: auto;			
		}

		#posts-controls {
			height: 225px;
		}

		#newsman-bcst-posts {
			position: absolute;
			top: 248px;
			right: 0;
			bottom: 0;
			left: 0;
		}

		.ui-multiselect-menu, .ui-datepicker-div {
			display: none;
		}

		#newsman-content-type {
			display: inline-block;
			vertical-align: baseline;
		}

	</style>
</head>
<body>
	<div id="post-selector" class="wrap wp_bootstrap">		
		<div class="row-fluid maxh">
			<div class="span12 maxh rel">
				<div id="posts-controls">
					<input type="text" id="newsman-search" placeholder="<?php esc_attr_e('Search...', NEWSMAN);?>">
					<hr>
					<div class="newsman-bcst-topbar">
						<label><span class="text"><?php _e('Post type:', NEWSMAN);?></span>
							<select name="newsman_post_type" id="newsman-post-type" multiple="multiple">
							<?php
								$u = newsmanUtils::getInstance();
								$type = $u->getPostTypes();
								
								foreach ($type as $item) {
									$sel = $item->selected ? ' selected="selected"' : '';
									echo '<option value="'.$item->name.'"'.$sel.'>'.$item->name.'</option>';
								}
							?>
							</select>
						</label>						
						<label><span class="text"><?php _e('Categories:', NEWSMAN);?></span>
							<select name="newsman_bcst_sel_cat" id="newsman-bcst-sel-cat" multiple="multiple">
							<?php
								$u = newsmanUtils::getInstance();
								$categories = $u->getCategories();
								
								foreach ($categories as $item) {
									$sel = $item->selected ? ' selected="selected"' : '';
									echo '<option value="'.$item->cat_ID.'"'.$sel.'>'.$item->name.'</option>';
								}
							?>
							</select>
						</label>
						<label><span class="text"><?php _e('Authors:', NEWSMAN) ?></span>
							<select id="newsman-bcst-sel-auth" name="newsman_bcst_sel_auth"  multiple="multiple">
							<?php
								 $authors = $u->getAuthors();
								foreach ($authors as $item) {
									$sel = $item['selected'] ? ' selected="selected"': '';
									echo '<option value="'.$item['ID'].'"'.$sel.'>'.$item['user_nicename'].'</option>';			
								}
							?>
							</select>
						</label>
						<label class="checkbox"><input type="checkbox" id="newsman-bcst-include-private"> <?php _e('Show private posts', NEWSMAN); ?></label>
						<label><span class="text"><?php _e('Use content:', NEWSMAN); ?></span>
							<select name="" id="newsman-content-type">
								<option value="full"><?php _e('Full post'); ?></option>
								<option value="excerpt"><?php _e('Excerpt'); ?></option>
								<option value="fancy" selected="selected"><?php _e('Fancy excerpt'); ?></option>
							</select>
						</label>
						<h3 id="posts-counter"><?php _e('No posts selected'); ?></h3>
					</div>
				</div>
				<div id="newsman-bcst-posts">
				</div>
			</div>	
		</div>
		
	</div>
<?php
	/* Always have wp_footer() just before the closing </body>
	 * tag of your theme, or you will break many plugins, which
	 * generally use this hook to reference JavaScript files.
	 */

	wp_footer();
?>	
</body>
</html>