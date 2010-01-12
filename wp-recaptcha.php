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

require_once('recaptcha.php');

// create the actual instance
if (class_exists("recaptcha")) {
    // initialize an object of type recaptcha (should take care of preliminary checks in constructor)
	$recaptcha = new recaptcha();
}

// register the actions and filters using the created instance
if (isset($recaptcha)) {
	// Actions
	
	// styling
	add_action('wp_head', array(&recaptcha, 'register_stylesheets'));
    add_action('admin_head', array(&recaptcha, 'register_stylesheets'));
    add_action('login_head', array(&recaptcha, 'register_stylesheets')); // possibly not needed, instead use jQuery
    
    // options
    register_activation_hook(__FILE__, array(&recaptcha, 'register_defaults'));
    // add_option here?
    register_deactivation_hook(__FILE__, array(&recaptcha, 'delete_options'));
    
    // recaptcha form display
    if ($recaptcha->is_wordpress_mu())
        add_action('signup_extra_fields', array(&recaptcha, 'show_recaptcha_form'));
    else
        add_action('register_form', array(&recaptcha, 'show_recaptcha_form'));
    
    add_action('comment_form', array(&recaptcha, 'recaptcha_comment_form'));
    
    // recaptcha comment processing
    add_action('wp_head', 'saved_comment');
    add_action('preprocess_comment', 'check_comment');
    add_action('comment_post_redirect', 'relative_redirect');
    
    // Filters
    
	// recaptcha validation
	if ($recaptcha->is_wordpress_mu())
	    add_filter('wpmu_validate_user_signup', array(&recaptcha, 'validate_response_wpmu'));
	else
	    add_filter('registration_errors', array(&recaptcha, 'validate_response'));
}

?>