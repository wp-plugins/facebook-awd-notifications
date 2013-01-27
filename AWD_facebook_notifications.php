<?php
/*
Plugin Name: Facebook AWD Notifications
Plugin URI: http://facebook-awd.ahwebdev.fr
Description: Send notifications to Facebook users
Version: 1.0
Author: AHWEBDEV
Author URI: http://www.ahwebdev.fr
License: GPL
Text Domain: AWD_facebook_notifications
*/

/**
 *
 * @author alexhermann
 *
 */
add_action('plugins_loaded', 'initial_notifications');
function initial_notifications()
{
	global $AWD_facebook;
	if(is_object($AWD_facebook)){
		$model_path = $AWD_facebook->get_plugins_model_path();
		require_once($model_path);
		require_once(dirname(__FILE__).'/inc/classes/class.AWD_facebook_notifications.php');
		require_once(dirname(__FILE__).'/inc/classes/class.AWD_facebook_notifications_base.php');
		$AWD_facebook_notifications = new AWD_facebook_notifications(__FILE__,$AWD_facebook);
	}
}
?>