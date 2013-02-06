<?php
/*
Plugin Name: G-Lock WPNewsman Lite
Plugin URI: http://wpnewsman.com
Description: You get simple yet powerful newsletter solution for WordPress. Now you can easily add double optin subscription forms in widgets, articles and pages, import and manage your lists, create and send beautiful newsletters directly from your WordPress site. You get complete freedom and a lower cost compared to Email Service Providers. Free yourself from paying for expensive email campaigns. WPNewsman plugin updated regularly with new features.
Version: 1.3.4
Author: Alex Ladyga - G-Lock Software
Author URI: http://www.glocksoft.com
*/
/*  Copyright 2012  Alex Ladyga (email : alexladyga@glocksoft.com)
	Copyright 2012  G-Lock Software

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//error_reporting(E_ALL);

define('NEWSMAN', 'wpnewsman');
define('NEWSMAN_VERSION', '1.3.4');

define('NEWSMAN_PLUGIN_URL', get_bloginfo('wpurl').'/'.PLUGINDIR.'/'.basename(dirname(__FILE__)));
define('NEWSMAN_PLUGIN_PATH', ABSPATH.PLUGINDIR.'/'.basename(dirname(__FILE__)));
define('NEWSMAN_PLUGIN_MAINFILE_PATH', ABSPATH.PLUGINDIR.'/'.basename(dirname(__FILE__)).'/'.basename(__FILE__));
define('NEWSMAN_PLUGIN_MAINFILE', __FILE__);
define('NEWSMAN_BLOG_ADMIN_URL', get_bloginfo('wpurl').'/wp-admin/');

define('NEWSMAN_PLUGIN_PATHNAME', basename(dirname(__FILE__)).'/'.basename(__FILE__)); // newsman2/newsman2.php
define('NEWSMAN_PLUGIN_PRO_PATHNAME', 'newsman-pro/newsman-pro.php');

if ( strpos($_SERVER['REQUEST_URI'], 'frmGetPosts.php') !== false ) {
	define('INSER_POSTS_FRAME', true);
}

function newsmanStopActivation() {
	global $newsman_checklist;
	?>
		<style>
			.nesman-label {
				background: whiteSmoke;
				border: 1px solid #DFDFDF;				
				-moz-border-radius: 3px;
				-webkit-border-radius: 3px;
				border-radius: 3px;
				width: 46px;
				display: inline-block;
				text-align: center;
				font-size: 11px;
				margin-right: .5em;
			}
			.newsman-label-passed {
				color: green;
			} 
			.newsman-label-failed {
				color: red;
			}
			.newsman-label-help {
				color: gray;
				font-style: italic;
			} 			
		</style>
		<div class="error">
			
			<h3>Error: G-Lock WPNewsman compatibility check failed, the plugin cannot be activated.</h3>
			<p>Please, fix the issues below and try again.</p>
			<ul>
				<?php
					foreach ($newsman_checklist as $check) {
						if ( $check['passed'] ) {
							$lbl = 'Passed';
							$class = 'newsman-label-passed';
						} else {
							$lbl = 'Failed';
							$class = 'newsman-label-failed';
						}
						echo '<li><span class="nesman-label '.$class.'">'.$lbl.'</span> '.$check['name'].' (<span class="newsman-label-help">'.$check['help'].'</span>)</li>';
					}
				?>				
			</ul>
		</div>
	<?php
	deactivate_plugins(NEWSMAN_PLUGIN_PATHNAME);
	unset($_GET['activate']); // to disable "Plugin activated" message					

}

function newsmanCheckCompatibility() {
	global $newsman_checklist;

	$passed = true;
	$newsman_checklist = array();

	// 0. PHP version
	$v = explode('.', phpversion());
	for ($i=0; $i < count($v); $i++) { 
		$v[$i] = intval($v[$i]);
	}

	$newsman_checklist[] = array(
		'passed' => ( $v[0] >= 5 && $v[1] >= 3  ),
		'name'  => __( 'PHP version >= 5.3', NEWSMAN),
		'help'  => sprintf( __('You have PHP %s installed.', NEWSMAN) , phpversion())
	);

	// 1. Multisite setup
	$is_ms = function_exists('is_multisite') && is_multisite();

	$newsman_checklist[] = array(
		'passed' => !$is_ms,
		'name'  => __('Single-site mode', NEWSMAN),
		'help'  => __('Doesn\'t work in MultiSite setup.', NEWSMAN)
	);

	// 3. MCrypt

	$newsman_checklist[] = array(
		'passed' => function_exists('mcrypt_encrypt'),
		'name'  => __('MCrypt library', NEWSMAN),
		'help'  => 'MCrypt library is required to securely store your passwords in the database. Read <a href="http://php.net/manual/en/mcrypt.setup.php">how to Install/Configure</a> or contact your hosting provider if you\'re on a shared hosting.'
	);

	/// ----

	foreach ($newsman_checklist as $check) {
		if ( $check['passed'] === false ) {
			$passed = false;
		}
	}

	if ( !$passed ) {
		add_action('admin_notices', 'newsmanStopActivation');
	}

	return $passed;
}

function wpnewsmanActivationHook() {
	if ( newsmanCheckCompatibility() ) {
		require_once('core.php');
		$n = newsman::getInstance();
		$n->onActivate();		
		
	}
}

function wpnewsmanDeactivationHook() {
	if ( newsmanCheckCompatibility() ) {
		require_once('core.php');
		$n = newsman::getInstance();
		$n->onDeactivate();
	}
}

if ( newsmanCheckCompatibility() ) {
	require_once('core.php');	
	$n = newsman::getInstance();
	newsman_register_worker('newsmanMailer');	
}

register_activation_hook( NEWSMAN_PLUGIN_MAINFILE, 'wpnewsmanActivationHook');
register_deactivation_hook( NEWSMAN_PLUGIN_MAINFILE, 'wpnewsmanDeactivationHook' );

