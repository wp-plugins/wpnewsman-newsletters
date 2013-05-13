<div class="wrap wp_bootstrap">
	<?php include("_header.php"); ?>
	<div class="newsman-welcome">
		<h1>Welcome to WPNewsman <?php echo NEWSMAN_VERSION; ?></h1>
		
		<?php if ( !get_option('newsman_old_version') ): ?>
			<div class="about-text">Thank you for installing WPNewsman. We hope you'll like it!</div>
		<?php else: ?>
			<div class="about-text">You updated and have better newsletter gadget!</div>

			<div class="changelog">
		  		<div class="feature-section row" style="margin-bottom: .5em">
					<div class="span8">
						<h3>Spread the word and keep this plugin essentially free</h3>
						<p><a href="http://wordpress.org/support/view/plugin-reviews/wpnewsman-newsletters"><img src="http://s-plugins.wordpress.org/wpnewsman-newsletters/assets/hello-puppies.png" align="left" style="margin: 0 15px 0 0;" /></a><p style="font-size: 18px; font-weight: bold;">Love puppies?</p>We love reviews and ★★★★★ because they encourage and inspire us.  <a href="http://wordpress.org/support/view/plugin-reviews/wpnewsman-newsletters" target="_blank" title="Rate WPNewsman!">Add your own review</a> on <a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/wpnewsman-newsletters">wordpress.org</a> and make it even more awesome.</p>
					</div>
				</div>
			</div>
		<?php endif; ?>
		
		<div class="changelog">
			<?php if ( !$hideVideo ): ?>
			<h3>Trying for the First Time?</h3>
			<div class="feature-section normal">
				<p>Watch this 7 min video to see it in action (it's dead-simple to use):</p>
				<p>
					<iframe width="853" height="480" src="http://www.youtube.com/embed/NhmAfJQH4EU?rel=0" frameborder="0" allowfullscreen></iframe>
				</p>
			</div>
			<?php endif; ?>
		</div>
	   
		<h3 style="margin-top: 40px;">How to quickly create and send email newsletter</h3>
		
		<div class="feature-section normal">
			<p>Watch this quick video to learn how to quickly create and send a digest email newsletter in WPNewsman:</p>
			<p>
			  <iframe width="640" height="360" src="http://www.youtube.com/embed/8OOHboUXiPM?rel=0" frameborder="0" allowfullscreen></iframe>
			</p>
		</div>

		<div class="feature-section row" style="margin: 35px 0 .5em 0;">
			<div class="span12">
				<h3>Changes in this version:</h3>
				<?php $u = newsmanUtils::getInstance(); echo $u->getLastChanges(); ?>
				<p>For the correct work of the plugin, update WPNewsman and WordPress to the latest versions.</p>
			</div>
		</div>
		
		<a class="btn btn-primary btn-large" href="admin.php?page=newsman-settings">Thanks! Now take me to WPNewsman</a>
	</div>
</div>
