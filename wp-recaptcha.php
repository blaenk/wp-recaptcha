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

require_once('recaptcha.php');
require_once('mailhide.php');

$recaptcha = new reCAPTCHA('recaptcha_options');
$mailhide = new MailHide('mailhide_options');

function create_error_notice($message, $anchor = '') {
      $options_url = admin_url('options-general.php?page=wp-recaptcha/recaptcha.php') . $anchor;
      $error_message = sprintf(__($message . ' <a href="%s" title="WP-reCAPTCHA Options">Fix this</a>', 'recaptcha'), $options_url);
      
      echo '<div class="error"><p><strong>' . $error_message . '</strong></p></div>';
}

function unconfigured_notice() {
   create_error_notice('You enabled reCAPTCHA, however, neither reCAPTCHA nor MailHide is configured.');
}

if ($recaptcha->keys_missing() && $recaptcha->keys_missing()) {
   
   
   add_action('admin_notices', 'unconfigured_notice');
}

?>