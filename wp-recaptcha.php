<?php
/*
Plugin Name: WP-reCAPTCHA
Plugin URI: http://www.blaenkdenum.com/wp-recaptcha/
Description: Integrates reCAPTCHA anti-spam solutions with wordpress
Version: 3.0
Author: Jorge Peña
Email: support@recaptcha.net
Author URI: http://www.blaenkdenum.com
*/

// this is the 'driver' file that instantiates the objects and registers every hook

define('ALLOW_INCLUDE', true);

require_once('plugin.php');
require_once('recaptcha.php');
require_once('mailhide.php');

// get the old option defaults in case they exist
// todo: have to use get_site_option if wpmu
$old_option_defaults = Plugin::retrieve_options('recaptcha');

echo '<div class="initial">' . var_dump($old_option_defaults) . '</div>';

// initialize an object of type recaptcha (should take care of preliminary checks in constructor)
$recaptcha = new reCAPTCHA('recaptcha_options', $old_option_defaults);
$mailhide = new MailHide('mailhide_options', $old_option_defaults);

// remove the old option defaults in case they exist
Plugin::remove_options('recaptcha');

?>