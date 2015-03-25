<div class="wrap wp_bootstrap">
	<?php include("_header.php"); ?>
	<div class="newsman-welcome">
		<h1>Welcome to WPNewsman <?php echo NEWSMAN_VERSION; ?></h1>
		
		<?php if ( !get_option('newsman_version') ): ?>
			<div class="about-text">We're so glad you installed WPNewsman. We hope you'll like it!</div>
		<?php else: ?>
			<div class="about-text">You are updated and ready to get started with new cool features!</div>

			<div class="changelog">
		  		<div class="feature-section row" style="margin-bottom: .5em">
					<div class="span8">
						<h3>3,000+ active installs and 48 excellent reviews on wordpress.org!</h3>
						<p><a href="http://wordpress.org/support/view/plugin-reviews/wpnewsman-newsletters">
						<img src="http://s-plugins.wordpress.org/wpnewsman-newsletters/assets/hello-puppies.png" align="left" style="margin: 0 15px 0 0;" /></a>
						<p style="font-size: 18px; font-weight: bold;">We need your HELP to reach 100 reviews!</p>
						Before you continue with your WPNewsman plugin, please <a href="http://wordpress.org/support/view/plugin-reviews/wpnewsman-newsletters" target="_blank" title="Rate WPNewsman!">add your own review</a>
						 on wordpress.org!

						<p>Your likes, shares and comments encourage and inspire us to create a powerful alternative to email services and help you stay in touch with your clients, prospects and subscribers. We’re saying “Thank You!” for your support.</p>
						<a class="btn btn-success btn-large" href="http://wordpress.org/support/view/plugin-reviews/wpnewsman-newsletters" target="_blank" title="Rate WPNewsman!">Yes, I want to add my OWN 5★ Review!</a>

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
					<iframe width="853" height="480" src="http://www.youtube.com/embed/EvssRjYDoJQ?rel=0" frameborder="0" allowfullscreen></iframe>
				</p>
			</div>
			<?php endif; ?>
		</div>
<!--	   
		<h3 style="margin-top: 40px;">How to quickly create and send email newsletter</h3>
		
		<div class="feature-section normal">
			<p>Watch this quick video to learn how to quickly create and send a digest email newsletter in WPNewsman:</p>
			<p>
			  <iframe width="640" height="360" src="http://www.youtube.com/embed/8OOHboUXiPM?rel=0" frameborder="0" allowfullscreen></iframe>
			</p>
		</div>
-->
		<div class="feature-section row" style="margin: 35px 0 .5em 0;">
			<div class="span12">
				<h3>Changes in this version:</h3>
				<?php $u = newsmanUtils::getInstance(); echo $u->getLastChanges(); ?>
				<p>For the correct work of the plugin, update WPNewsman and WordPress to the latest versions.</p>
			</div>
		</div>
		
		<a class="btn btn-primary btn-large" href="<?php echo isset($_GET['return']) ? $_GET['return'] : 'admin.php?page=newsman-settings'; ?>">Thanks! Now take me to WPNewsman</a>
	</div>
</div>
