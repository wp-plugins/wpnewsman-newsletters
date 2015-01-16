<?php


require_once __DIR__.'/../vendor/autoload.php';
use GeoIp2\Database\Reader;


//namespace GeoIp2\Database;

use MaxMind\Db\Reader as DbReader;


class newsmanGeoLiteDbReader extends Reader {

	protected $dbFilePath;

	public function __construct() {
		$n = newsman::getInstance();
		$this->dbFilePath = $n->ensureUploadDir('geoip').DIRECTORY_SEPARATOR.'GeoLite2-City.mmdb';

		parent::__construct($this->dbFilePath);
	}

	public function dbExists() {
		return file_exists($this->dbFilePath);
	}

    public function isNewVersionAvailable() {
    	$u = newsmanUtils::getInstance();

    	$url = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.md5';

    	$currentDBHash = md5_file($this->dbFilePath);

		$res = wp_remote_request($url);

		if ( !is_wp_error($res) ) {
			$webDBHash = $res['body'];
			$u->log('[isNewVersionAvailable] currentDBHash: %s', $currentDBHash);
			$u->log('[isNewVersionAvailable] webDBHash: %s', $webDBHash);

			return trim(strtolower($currentDBHash)) !== trim(strtolower($webDBHash));
		}

		return $res;
    }

}