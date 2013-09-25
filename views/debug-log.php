<div class="wrap wp_bootstrap">
<style>
	#debuglog {
		width: 800px;
	}
</style>
	<?php include("_header.php"); ?>

	<div class="row-fluid" style="border-bottom: 1px solid #DADADA; height: 63px;">
		<div class="span12">
			<h2><?php _e('Debug Log', NEWSMAN); ?></h2>
		</div>		
	</div>

	<br>

	<textarea name="debuglog" id="debuglog" cols="80" rows="10"><?php $g = newsman::getInstance(); $g->echoDebugLog(); ?></textarea>
	<label for="" class="checkbox"><input type="checkbox" id="debug-log-auto-refresh">Auto-refresh</label>
	<button id="btn-empty-debug-log" class="btn btn-default">Empty Debug Log</button>

	<?php include("_footer.php"); ?>
</div>
