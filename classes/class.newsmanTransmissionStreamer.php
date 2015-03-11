<?php

class newsmanTransmissionStreamer {
	var $email = null;
	var $sl;
	var $currentList = null;

	var $batchSize = 50;

	var $total = 0;
	var $plainAddresses = array();

	function __construct($email){

		$this->sl = newsmanSentlog::getInstance();

		$this->email = $email;

		// $this->plainAddresses = array();

		// // filling the buffer with plain email address transmissions.
		// // It shouldn't be lots of them, so we don't apply streaming 
		// // here

		// if ( is_array($to) ) {
		// 	foreach ($to as $dest) {
		// 		$dest = trim($dest);
		// 		if ( preg_match("/^[^@]*@[^@]*\.[^@]*$/", $dest) ) { 
		// 			// email
		// 			$this->plainAddresses[] = $dest;				
		// 		} else {
		// 			$this->to[] = $dest;
		// 		}
		// 	}
		// }

		// if ( count($this->plainAddresses) ) {
		// 	$this->buffer = $this->sl->getPendingByEmails($email->id, $this->plainAddresses);
		// }
	}

	function getTotal() {
		return $this->sl->getTotal($this->email->id); 
	}

	function applyFilter($t, $email) {
		$g = newsman::getInstance();
		if ( class_exists('newsmanPro') ) {
			$gp = newsmanPro::getInstance();
			if ( !$gp->doFilters() ) {
				return $t;
			}
		}
		return $g->prepareTransmission($t, $email);
	}

	function getTransmission() {
		// list name
		$u = newsmanUtils::getInstance();

		$t = $this->sl->getSinglePendingForEmail($this->email->id);

		if ( !$t ) {
			$n = $this->sl->resetAbandonedEmails($this->email->id);
			$u->log('[resetAbandonedEmails] will retry sending for '.$n.' abandoned emails');
			$t = $this->sl->getSinglePendingForEmail($this->email->id);
			$u->log('Frist abandoned email: '.var_export($t, true));
		}

		$t = $this->applyFilter($t, $this->email);
		if ( $t === false ) { return null; }

		return $t;
	}

}
