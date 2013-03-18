<div class="wrap wp_bootstrap">
  <?php include("_header.php"); ?>
  <div class="newsman-welcome">
    <h1>Welcome to WPNewsman <?php echo NEWSMAN_VERSION; ?></h1>
    <?php if ( !get_option('newsman_old_version') ): ?>
    <div class="about-text">Thank you for installing WPNewsman. We hope you'll like it!</div>
    <?php else: ?>
    <div class="about-text">You updated! We hope you'll like it!</div>

 <h3>Added new email templates store</h3>
      <div class="feature-section normal">
        <p>See this quick video tutorial How to Create and Edit Templates and Messages in WPNewsman:</p>
        <p>
          <iframe width="640" height="360" src="http://www.youtube.com/embed/uB5YYzhsQuw?rel=0" frameborder="0" allowfullscreen=""></iframe>
        </p>
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
    <div class="changelog">
      <div class="feature-section row" style="margin-bottom: .5em">
      <div class="span8">
          <h3>Spread the word</h3>
          <h4 style="font-size: 18px;">Love puppies?</h4>
          <p> <img src="http://wpnewsman.com/images/hello-puppies.jpg" align="left" style="margin: 0 15px 0 0;" />Each time one of our users forgets to write a review, a puppy dies. It's sad and breaks our hearts. <a href="http://wordpress.org/support/view/plugin-reviews/wpnewsman-newsletters" target="_blank" title="Rate WPNewsman!">Add your own review</a> and save a puppy today.</p>
        </div>
      </div>
      <div class="feature-section row" style="margin: 35px 0 .5em 0;">
        <div class="span12">
          <h3>Changes in this version:</h3>
          <?php $u = newsmanUtils::getInstance(); echo $u->getLastChanges(); ?>
          <p>For the correct work of the plugin, update WPNewsman and WordPress to the latest versions.</p>
        </div>
      </div>
    </div>
    <a class="btn btn-primary btn-large" href="admin.php?page=newsman-settings">Thanks! Now bring me to WPNewsman</a> </div>
</div>
