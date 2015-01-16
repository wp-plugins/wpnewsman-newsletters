<?php
defined('NEWSMAN') or die('Restricted access');
/**
 * Classes Autoloader.
 * It loads automatically the right class on class instantation.
 * Since we still can't use namespaces, we use 'WJ_' as a prefix for our classes.
 * @param  Class $class Class name
 * @return
 */
function wpnewsman_classes_autoloader($class) {
    // Check if the class name has our prefix.
    $classFileName = 'class.'.$class.'.php';

    $includePaths = array(
    	NEWSMAN_CLASSES_PATH . DIRECTORY_SEPARATOR,
    	NEWSMAN_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'workers' . DIRECTORY_SEPARATOR
    );

    if (strpos($class, 'newsman') !== false) {
		// If the class file exists, let's load it.
		foreach ($includePaths as $path) {
			$classFilePath = $path.$classFileName;
			if (file_exists($classFilePath)) {
				require_once $classFilePath;
				break;
			}			
		}
    }
}

// This is the global PHP autoload register, where we register our autoloaders.
spl_autoload_register('wpnewsman_classes_autoloader');