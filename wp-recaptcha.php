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
	add_action('wp_head', array(&$recaptcha, 'register_stylesheets')); // make unnecessary: instead, inform of classes for styling
    add_action('admin_head', array(&$recaptcha, 'register_stylesheets')); // make unnecessary: shouldn't require styling in the options page
    add_action('login_head', array(&$recaptcha, 'register_stylesheets')); // make unnecessary: instead use jQuery and add to the footer?
    
    // options
    register_activation_hook(__FILE__, 'register_default_options'); // this way it only happens once, when the plugin is activated
    
    // recaptcha form display
    if ($recaptcha->is_wordpress_mu())
        add_action('signup_extra_fields', array(&$recaptcha, 'show_recaptcha_form'));
    else
        add_action('register_form', array(&$recaptcha, 'show_recaptcha_form'));
    
    add_action('comment_form', array(&$recaptcha, 'recaptcha_comment_form'));
    
    // recaptcha comment processing (look into doing all of this with AJAX, optionally)
    add_action('wp_head', array(&$recaptcha, 'saved_comment'));
    add_action('preprocess_comment', array(&$recaptcha, 'check_comment'));
    add_action('comment_post_redirect', array(&$recaptcha, 'relative_redirect'));
    
    // administration (menus, pages, notifications, etc.)
    $plugin = plugin_basename(__FILE__);
    add_filter("plugin_action_links_$plugin", array(&$recaptcha, 'settings_link'));
    
    // add the options page
    if ($recaptcha->is_wordpress_mu() && is_site_admin())
        add_submenu_page('wpmu-admin.php', 'WP-reCAPTCHA', 'WP-reCAPTCHA', 'manage_options', __FILE__, array(&$recaptcha, 'settings_page'));
    
    add_options_page('WP-reCAPTCHA', 'WP-reCAPTCHA', 'manage_options', __FILE__, array(&$recaptcha, 'settings_page'));
    
    // Filters
    
	// recaptcha validation
	if ($recaptcha->is_wordpress_mu())
	    add_filter('wpmu_validate_user_signup', array(&$recaptcha, 'validate_response_wpmu'));
	else
	    add_filter('registration_errors', array(&$recaptcha, 'validate_response'));
}

function is_wordpress_mu() {
    // is it wordpress mu?
    if (is_dir(WP_CONTENT_DIR . '/mu-plugins')) {
        // is it site-wide?
    	if (is_file(WP_CONTENT_DIR . '/mu-plugins/wp-recaptcha.php')) // forced activated
    	   return true;
    }
    
    // otherwise it's just regular wordpress
    else {
        return false;
    }
}

function register_default_options() {
    // store the options in an array, to ensure that the options will be stored in a single database entry
    $option_defaults = array();

    // keys
    $option_defaults['public_key'] = ''; // the public key for reCAPTCHA
    $option_defaults['private_key'] = ''; // the private key for reCAPTCHA

    // placement
    $option_defaults['show_in_comments'] = true; // whether or not to show reCAPTCHA on the comment post
    $option_defaults['show_in_registration'] = true; // whether or not to show reCAPTCHA on the registration page

    // bypass levels
    $option_defaults['bypass_for_registered_users'] = true; // whether to skip reCAPTCHAs for registered users
    $option_defaults['minimum_bypass_level'] = ''; // who doesn't have to do the reCAPTCHA (should be a valid WordPress capability slug)

    // styling
    $option_defaults['comments_theme'] = 'red'; // the default theme for reCAPTCHA on the comment post
    $option_defaults['registration_theme'] = 'red'; // the default theme for reCAPTCHA on the registration form
    $option_defaults['language'] = 'en'; // the default language for reCAPTCHA
    $option_defaults['xhtml_compliance'] = false; // whether or not to be XHTML 1.0 Strict compliant
    $option_defaults['tab_index'] = 5; // the default tabindex for reCAPTCHA

    // error handling
    $option_defaults['no_response_error'] = '<strong>ERROR</strong>: Please fill in the reCAPTCHA form.'; // message for no CAPTCHA response
    $option_defaults['incorrect_response_error'] = '<strong>ERROR</strong>: That reCAPTCHA response was incorrect.'; // message for incorrect CAPTCHA response
    
    // add the option based on what environment we're in
    if (is_wordpress_mu())
        add_site_option('recaptcha_options', $option_defaults);
    else
        add_option('recaptcha_options', $option_defaults);
}

?>