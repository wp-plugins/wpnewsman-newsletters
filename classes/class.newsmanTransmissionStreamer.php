<?php

class newsmanTransmissionStreamer {
	var $email = null;
	var $buffer = array();
	var $to = array();
	var $sl;
	var $currentList = null;

	var $batchSize = 50;

	var $total = 0;
	var $plainAddresses = array();

	function __construct($email){

		$this->sl = newsmanSentlog::getInstance();

		$this->email = $email;

		$to = $email->to;

		if ( is_string($to) ) {
			if ( preg_match('/^\[.*\]$/', $to) ) {
				$to = json_decode($to, true);
			} else {
				$to = explode(',', $to);
			}
		}

		$this->plainAddresses = array();

		// filling the buffer with plain email address transmissions.
		// It shouldn't be lots of them, so we don't apply streaming 
		// here

		foreach ($to as $dest) {
			$dest = trim($dest);
			if ( preg_match("/^[^@]*@[^@]*\.[^@]*$/", $dest) ) { 
				// email
				$this->plainAddresses[] = $dest;				
			} else {
				$this->to[] = $dest;
			}
		}

		$this->cleanupTempErrors();

		

		if ( count($this->plainAddresses) ) {
			$this->buffer = $this->sl->getPendingByEmails($email->id, $this->plainAddresses);
		}
	}

	function cleanupTempErrors() {
		$listsIds = array();
		foreach ($this->to as $listName) {
			$list = newsmanList::findOne('name = %s', array($listName) );
			if ( $list ) {
				$listsIds[] = $list->id;
			}
		}
		if ( count($listsIds) > 0 ) {
			$this->sl->cleanupTempErrors($listsIds, $this->email->id);	
		}		
	}

	function getTotal() {
		$u = newsmanUtils::getInstance();

		$this->total = count($this->plainAddresses);

		foreach ($this->to as $listName) {

			$ln = $u->parseListName($listName);

			$list = newsmanList::findOne('name = %s', array($ln->name) );
			$list->selectionType = $ln->selectionType;

			if ( $list ) {
				$this->total += $list->getTotal();
			}
		}

		return $this->total;
	}

	function fillBuffer() {
		if ( !$this->currentList ) { // getting next list from the "To:" field
			$this->currentList = array_shift($this->to);
			if ( $this->currentList ) {
				$this->currentList = trim($this->currentList);
			} else {
				// nothing left, empty buffer
				$this->buffer = array();
				return;
			}
		}
		// list name
		$this->buffer = array_merge($this->buffer, 
			$this->sl->getPendingFromList($this->email->id, $this->currentList, $this->batchSize)
		);

		$u = newsmanUtils::getInstance();

		if ( !count($this->buffer) ) { // buffer is empty, no data left in this list, switching to another
			$this->currentList = null;
			$this->fillBuffer();
		}
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

	// function getTransmission() {
	// 	if ( !count($this->buffer) ) {
	// 		$this->fillBuffer();
	// 	}
	// 	$t = array_pop($this->buffer);

	// 	$t = $this->applyFilter($t, $this->email);
	// 	return $t;
	// }

	function getTransmission() {
		if ( !$this->currentList ) { // getting next list from the "To:" field
			$this->currentList = array_shift($this->to);
			if ( $this->currentList ) {
				$this->currentList = trim($this->currentList);
			} else {
				// nothing left, empty buffer
				return NULL;
			}
		}

		// list name
		$t = $this->sl->getSinglePendingFromList($this->email->id, $this->currentList);

		$t = $this->applyFilter($t, $this->email);
		if ( $t === false ) { return null; }

		if ( !$t ) { // buffer is empty, no data left in this list, switching to another
			$this->currentList = null;
			return $this->getTransmission();
		}
		return $t;
	}

}
