<div class="wrap wp_bootstrap">
  <?php include("_header.php"); ?>
  <div class="newsman-welcome">
    <h1>Welcome to WPNewsman <?php echo NEWSMAN_VERSION; ?></h1>
    <div class="about-text">Thank you for installing WPNewsman. We hope you'll like it!</div>
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
    <div class="changelog">
      <div class="feature-section row" style="margin-bottom: .5em">
        <div class="span12">
          <h3>We love you and your kind words!</h3>
          <p>We love reviews because they encourage and inspire us. <a href="http://wordpress.org/support/view/plugin-reviews/wpnewsman-newsletters" target="_blank" title="Rate WPNewsman!">Add your own review</a> and make our day.</p>
        </div>
      </div>
      <div class="feature-section row" style="margin-bottom: .5em">
        <div class="span12">
          <h3>Help us understand your needs</h3>
          <p>Give us your top suggestion on how we can improve your experience with WPNewsman newsletter plugin <a href="http://support.glocksoft.net/feedback" target="_blank" title="Feedback">here</a>. So that we can effectively help your business gain better results from email marketing</p>
        </div>
      </div>
      <div class="feature-section row" style="margin-bottom: .5em">
        <div class="span12">
          <h3>Changed in this version:</h3>
          <?php $u = newsmanUtils::getInstance(); echo $u->getLastChanges(); ?>
          <p>For the correct work of the plugin, update WPNewsman and WordPress to the latest versions.</p>
        </div>
      </div>
    </div>
    <a class="btn btn-primary btn-large" href="admin.php?page=newsman-settings">Thanks! Now bring me to WPNewsman</a> </div>
</div>
