<?php

if ( !defined('NEWSMAN') ) {
	echo htmlspecialchars(json_encode(
		array("error" => "Forbidden.")
	), ENT_NOQUOTES);		
}

/**
 * Handles file uploaded with XMLHttpRequest
 */
class nuFileXHR {
	/**
	 * Save the file to the specified path
	 * @return boolean TRUE on success
	 */
	function save($path) {    
		$input = fopen("php://input", "rb");
		$file = fopen($path, "wb");

		$realSize = stream_copy_to_stream($input, $file);

		while (!feof($input)) {
			fwrite($file, fread($input, 102400));
		}

		fclose($input);
		fclose($file);        
		
		// if ($realSize != $this->getSize()){            
		//     return false;
		// }        
		
		return true;
	}
	function getName() {
		return $_GET['nufile'];
	}
	function getSize() {
		if (isset($_SERVER["CONTENT_LENGTH"])){
			return (int)$_SERVER["CONTENT_LENGTH"];            
		} else {
			die( json_encode(array('error' => 'Getting content length is not supported.')) );
		}      
	}   
}

/**
 * Handles file uploaded with iFrame form post
 */
class nuFileForm {  

	function __construct() {
		if ( !isset($_FILES['nufile']) ) {
			$this->returnServerLimits();
		}
	}

	private function returnServerLimits(){        
		$postSize = ini_get('post_max_size');
		$uploadSize = ini_get('upload_max_filesize');

		die('{"error":"File is too large. increase post_max_size('.$postSize.') and upload_max_filesize('.$uploadSize.') to match file size."}');
	}    

	/**
	 * Save the file to the specified path
	 * @return boolean TRUE on success
	 */
	function save($path) {
		if ( !move_uploaded_file($_FILES['nufile']['tmp_name'], $path) ) {
			return false;
		}
		return true;
	}
	function getName() {
		return $_FILES['nufile']['name'];
	}
	function getSize() {
		return $_FILES['nufile']['size'];
	}
}

class nuUploadProcessor {
	private $allowedExtensions = array();
	private $sizeLimit = 10485760;
	private $file;

	function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760){        
		$allowedExtensions = array_map("strtolower", $allowedExtensions);
			
		$this->allowedExtensions = $allowedExtensions;        
		$this->sizeLimit = $sizeLimit;

		if (isset($_GET['nufile'])) {
			$this->file = new nuFileXHR();
		} else {
			$this->file = new nuFileForm();
		}
	}
	
	/**
	 * Returns array('success'=>true) or array('error'=>'error message')
	 */
	function handleUpload($uploadDirectory, $replaceOldFile = FALSE){

		// uncomment for error testing
		// return array('error' => "Server error. Upload directory isn't writable.");
		//

		if (!is_writable($uploadDirectory)){
			return array('error' => "Server error. Upload directory isn't writable.");
		}
		
		if (!$this->file){
			return array('error' => 'No files were uploaded.');
		}
		
		$size = $this->file->getSize();
		
		if ($size == 0) {
			return array('error' => 'File is empty');
		}
		
		// if ($size > $this->sizeLimit) {
		// 	return array('error' => 'File is too large');
		// }
		
		$pathinfo = pathinfo($this->file->getName());
		$filename = $pathinfo['filename'];
		//$filename = md5(uniqid());
		$ext = $pathinfo['extension'];

		if ( $this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions) ) {
			$these = implode(', ', $this->allowedExtensions);
			return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
		}
		
		if ( !$replaceOldFile ) {
			/// don't overwrite previous files that were uploaded
			while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
				$filename .= rand(10, 99);
			}
		}
		
		if ($this->file->save($uploadDirectory . $filename . '.' . $ext)){
			return array(
				'success' => true,
				'actualFileName' => $filename.'.'.$ext
			);
		} else {
			return array('error'=> 'Could not save uploaded file.' .
				'The upload was cancelled, or server error encountered');
		}
		
	}    
}

function nuHandleUpload() {
	// list of valid extensions, ex. array("jpeg", "xml", "bmp")
	$allowedExtensions = array('csv', 'txt', 'zip');
	// max file size in bytes
	$sizeLimit = 10 * 1024 * 1024;

	$uploader = new nuUploadProcessor($allowedExtensions, $sizeLimit);

	$n = newsman::getInstance();

	$type = isset($_REQUEST['type']) ? strtolower($_REQUEST['type']) : false;

	$subdir = false;

	if ( in_array($type, array('csv', 'template')) ) {
		$subdir = $type;
	}

	$upath = $n->ensureUploadDir($subdir);
	$upath .= DIRECTORY_SEPARATOR;

	$result = $uploader->handleUpload($upath);

	// to pass data through iframe you will need to encode all html tags
	echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);	
}

