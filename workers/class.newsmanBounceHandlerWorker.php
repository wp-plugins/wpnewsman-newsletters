<?php

class newsmanBounceHandlerWorker extends newsmanWorker {

	function __construct($workerId) {
		parent::__construct($workerId);

		$this->lockName = 'bounce-handler-lock';
	}

	function isStopped() {
		$this->processMessages();
		return $this->stopped;
	}

	function worker() {
		$u = newsmanUtils::getInstance();

		//file_put_contents(NEWSMAN_PLUGIN_PATH.DIRECTORY_SEPARATOR.'bh.pid', getmypid());

		$u->log('[BH] update_option newsman_bh_wid = %s', $this->workerId);

		$bh = new newsmanBouncedHandler(null, $this);

		//$bh->onTotal = array($this, 'onGotTotalEmails');
		//$bh->onEmail = array($this, 'onEmail');
		//$bh->onUnknown = array($this, 'onUnknown');
		//$bh->onFinalStats = array($this, 'onFinalStats');
		if ( defined('NEWSMAN_DEBUG') && NEWSMAN_DEBUG === true ) {
			?>
				<style>
					.toggle-block {
						border-bottom: 1px dashed;
					}
				</style>
			<?php
		}

		if ( $bh->connect() ) {			
			//echo '<pre>';
			$u->log('[BH worker] connected to mailbox');
			$bh->findBounces( array( $this, 'isStopped' ) );
			// echo '</pre>';	
			// echo '<pre>';
			// print_r($bh->stats);
			// echo '</pre>';

			if ( defined('NEWSMAN_DEBUG') && NEWSMAN_DEBUG === true ) {
				?>
				<script src="http://code.jquery.com/jquery.min.js"></script>
				<script>
					jQuery(function($){
						$('.toggle-block').click(function(){
							var lnk = $(this);
							var b = $(this).closest('.block');
							if ( b ) {
								var pre = $('pre', b);
								if ( pre.is(':visible') ) {
									lnk.text('(Open)');
									pre.hide();
								} else {
									lnk.text('(Close)');
									pre.show();
								}
							}
						});
					});

				</script>
				<?php
			}

			$bh->close();

		} else {
			echo '<pre>';
			print_r($bh->errors);
			echo '</pre>';
		}
	}

}