<?php

	define('IFRAME_REQUEST', true);

	$o = newsmanOptions::getInstance();
	$u = newsmanUtils::getInstance();

	// post type translations

	function newsman_get_posttype_title($type) {
		$arr = array(
			'post' => __('Post', NEWSMAN),
			'page' => __('Page', NEWSMAN),
			'attachment' => __('Attachment', NEWSMAN),		
			'newsman_ap' => __('Action Page', NEWSMAN)
		);
		return isset($arr[$type]) ? $arr[$type] : $type;
	}

	header("Content-type: text/html; charset=UTF-8");

?>
<!DOCTYPE html>
<html>
<head>
	<title></title>

	<script type="text/javascript">
		addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
		window.ajaxurl = "<?php echo admin_url( 'admin-ajax.php', 'relative' ); ?>";
	</script>
	<style type="text/css">

		html {
			overflow-y: hidden !important;
			font-family: Helvetica, Arial, sans-serif !important;
		}

		hr {
			margin: 0 0 9px 0 !important;
		}

		html, body {
			height:  100%;
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

/*		#posts-controls {
			height: 225px;
		}

		#newsman-bcst-posts {
			position: absolute;
			top: 189px;
			right: 0;
			bottom: 0;
			left: 0;
		}
*/

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
						<table style="width: 100%;">
							<tbody>
								<tr>
									<td><label><span class="text"><?php _e('Post type:', NEWSMAN);?></span></td>
									<td>
										<select name="newsman_post_type" id="newsman-post-type">
										<?php
											$u = newsmanUtils::getInstance();
											$type = $u->getPostTypes();

											foreach ($type as $item) {
												$sel = $item['selected'] ? ' selected="selected"' : '';
												echo '<option value="'.$item['name'].'"'.$sel.'>'.newsman_get_posttype_title($item['name']).'</option>';
											}
										?>
										</select>										
									</td>
									<td><label class="checkbox" id="newsman-private-posts-lbl" ><input type="checkbox" id="newsman-bcst-include-private"> <?php _e('Show private posts', NEWSMAN); ?></label></td>
								</tr>
								<tr>
									<td>
										<label><span class="text"><?php _e('Categories:', NEWSMAN);?></span></label>
									</td>
									<td>
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
									</td>
									<td>
										<label class="checkbox" id="newsman-show-thmb-placeholder-lbl"><input type="checkbox" id="newsman-show-thmb-placeholder"> <?php _e('Show thumbnails placeholders', NEWSMAN); ?></label>
									</td>
								</tr>
								<tr>
									<td>
										<label><span class="text"><?php _e('Authors:', NEWSMAN) ?></span></label>
									</td>
									<td>
										<select id="newsman-bcst-sel-auth" name="newsman_bcst_sel_auth"  multiple="multiple">
										<?php
											 $authors = $u->getAuthors();
											foreach ($authors as $item) {
												$sel = $item['selected'] ? ' selected="selected"': '';
												echo '<option value="'.$item['ID'].'"'.$sel.'>'.$item['user_nicename'].'</option>';			
											}
										?>
										</select>
									</td>
									<td>
									
									</td>
								</tr>
								<tr>
									<td>
										<label><span class="text"><?php _e('Use content:', NEWSMAN); ?></span>
									</td>
									<td>
										<select name="" id="newsman-content-type">
											<option value="full"><?php _e('Full post', NEWSMAN); ?></option>
											<option value="excerpt"><?php _e('Excerpt', NEWSMAN); ?></option>
											<option value="fancy" selected="selected"><?php _e('Fancy excerpt', NEWSMAN); ?></option>
										</select>
									</td>
									<td></td>
								</tr>
								<tr>
									<td colspan="3">
										<div id="newsman-select-buttons">
											<label><?php _e('Select post(s) for last:', NEWSMAN); ?></label>
											<div class="btn-group" data-toggle="buttons-radio">
												<button class="btn btn-mini" sel="day" ><?php _e('Day', NEWSMAN); ?></button>
												<button class="btn btn-mini" sel="week" ><?php _e('Week', NEWSMAN); ?></button>
												<button class="btn btn-mini" sel="month" ><?php _e('Month', NEWSMAN); ?></button>
											</div>
											<button class="btn btn-mini" sel="clear" style="vertical-align: top;"><?php _e('Clear Selection', NEWSMAN); ?></button>
										</div>
									</td>
								</tr>								
							</tbody>
						</table>						
						
<!-- 						<label><span class="text"><?php _e('Post type:', NEWSMAN);?></span>
							<select name="newsman_post_type" id="newsman-post-type">
							<?php
								$u = newsmanUtils::getInstance();
								$type = $u->getPostTypes();

								foreach ($type as $item) {
									$sel = $item['selected'] ? ' selected="selected"' : '';
									echo '<option value="'.$item['name'].'"'.$sel.'>'.newsman_get_posttype_title($item['name']).'</option>';
								}
							?>
							</select>
							<label class="checkbox" id="newsman-private-posts-lbl" ><input type="checkbox" id="newsman-bcst-include-private"> <?php _e('Show private posts', NEWSMAN); ?></label>
						</label> -->						
<!-- 						<label><span class="text"><?php _e('Categories:', NEWSMAN);?></span>
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
							<label class="checkbox" id="newsman-show-thmb-placeholder-lbl"><input type="checkbox" id="newsman-show-thmb-placeholder"> <?php _e('Show thumbnails placeholders', NEWSMAN); ?></label>
						</label> -->
<!-- 						<label><span class="text"><?php _e('Authors:', NEWSMAN) ?></span>
							<select id="newsman-bcst-sel-auth" name="newsman_bcst_sel_auth"  multiple="multiple">
							<?php
								 $authors = $u->getAuthors();
								foreach ($authors as $item) {
									$sel = $item['selected'] ? ' selected="selected"': '';
									echo '<option value="'.$item['ID'].'"'.$sel.'>'.$item['user_nicename'].'</option>';			
								}
							?>
							</select>
						</label>						 -->
<!-- 						<label><span class="text"><?php _e('Use content:', NEWSMAN); ?></span>
							<select name="" id="newsman-content-type">
								<option value="full"><?php _e('Full post', NEWSMAN); ?></option>
								<option value="excerpt"><?php _e('Excerpt', NEWSMAN); ?></option>
								<option value="fancy" selected="selected"><?php _e('Fancy excerpt', NEWSMAN); ?></option>
							</select>
							
							<div id="newsman-select-buttons">
								<label><?php _e('Select post(s) for last:', NEWSMAN); ?></label>
								<div class="btn-group" data-toggle="buttons-radio">
									<button class="btn btn-mini" sel="day" ><?php _e('Day', NEWSMAN); ?></button>
									<button class="btn btn-mini" sel="week" ><?php _e('Week', NEWSMAN); ?></button>
									<button class="btn btn-mini" sel="month" ><?php _e('Month', NEWSMAN); ?></button>
								</div>
								<button class="btn btn-mini" sel="clear" style="vertical-align: top;"><?php _e('Clear Selection', NEWSMAN); ?></button>
							</div>
						</label> -->
					</div>
				</div>
				<div id="newsman-bcst-posts" class="noselect">
				</div>
			</div>	
		</div>
		
	</div>
<?php
	/* Always have wp_footer() just before the closing </body>
	 * tag of your theme, or you will break many plugins, which
	 * generally use this hook to reference JavaScript files.
	 */

	//wp_footer();

	global $hook_suffix;
	$hook_suffix = '/wp-admin/?page=wpnewsman-settings';

	//require_once(ABSPATH . 'wp-admin/admin-footer.php');
	do_action('admin_footer', '');
	do_action('admin_print_scripts');
	do_action('admin_print_footer_scripts');
	do_action("admin_footer-" . $GLOBALS['hook_suffix']);
	
?>	
</body>
</html>