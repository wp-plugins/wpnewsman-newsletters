<?php

class newsmanWorkerRecord extends newsmanStorable {
	static $table = 'newsman_workers';
	static $props = array(
		'id' => 'autoinc',
		'workerId' => 'string',
		'workerClass' => 'string',
		'workerParams' => 'text',
		'started' => 'int',
		'ttl' => 'int' // in seconds
	);

	static $keys = array(
		'workerId' => array( 'cols' => array( 'workerId' ) )
	);
}
