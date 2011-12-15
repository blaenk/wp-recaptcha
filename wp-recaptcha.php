<?php
/*
Plugin Name: WP-reCAPTCHA
Plugin URI: http://www.blaenkdenum.com/wp-recaptcha/
Description: Integrates reCAPTCHA anti-spam solutions with wordpress
Version: 3.1.4
Author: Jorge Peña
Email: support@recaptcha.net
Author URI: http://www.blaenkdenum.com
*/

// this is the 'driver' file that instantiates the objects and registers every hook

define('ALLOW_INCLUDE', true);

require_once('recaptcha.php');
require_once('mailhide.php');

$recaptcha = new reCAPTCHA('recaptcha_options');
$mailhide = new MailHide('mailhide_options');

?>