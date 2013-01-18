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
      <div class="feature-section row">
        <div class="span4">
          <h4>Customer Support</h4>
          <p>Our support team is active, accessible and happy to help when you need it. Place a ticket with our <a href="http://support.glocksoft.net/" target="_blank" title="Visit our support site">Support Department</a>. </p>
        </div>
        <div class="span4">
          <h4>Be part of WPNewsman's future</h4>
          <p>We are open to user feedbacks and feature suggestions. <a href="http://support.glocksoft.net/feedback" target="_blank" title="Feedback">Add your own</a> and let others vote for it. We'll consider the most requested features for addition in the future versions of the plugin.</p>
        </div>
        <div class="span4">
          <h4>We love you and your kind words!</h4>
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
          <h3>Change log</h3>
          <ul>
            <li>Added: support of multiple lists in the Lite version. Upon the first plugin activation, WPNewsman automatically creates the list called &quot;wp-users&quot; and imports the users of your WordPress site into that list. Thus, your wp-users list can now be used to send important site information, in addition to newsletters.</li>
            <li>Added: ability to merge Google Analytics and Piwik remote tracking into links in the Pro version.</li>
            <li>Added: ability to insert the Unsubscribe and Change Subscription links from the HTML editor menu.</li>
            <li>Added: the post selector dialog for the digest template.</li>
            <li>Added: &quot;Lists and Forms&quot; menu item under WPNewsman in your WordPress admin.</li>
            <li>Added: ability to select a template for action pages.</li>
            <li>Added: translation to the Russian, French, German and Italian languages. You can use WP Native Dashboard plugin to switch to your locale on the fly.</li>
            <li>Fixed: &quot;Edit post template&quot; feature for the digest template.</li>
            <li>Other small fixes and improvements.</li>
          </ul>
          <p>For the correct work of the plugin, update WPNewsman and WordPress to the latest versions.</p>
        </div>
      </div>
    </div>
    <a class="btn btn-primary btn-large" href="admin.php?page=newsman-settings">Thanks! Now bring me to WPNewsman</a> </div>
</div>
