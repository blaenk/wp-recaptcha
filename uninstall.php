<?php
// this is the uninstall handler
// include unregister_setting, delete_option, and other uninstall behavior here

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

function unregister_settings_group() {
    unregister_setting('recaptcha_options_group', 'recaptcha_options');
}

function delete_options() {
    if (is_wordpress_mu())
        delete_site_option('recaptcha_options');
    else
        delete_option('recaptcha_options');
}

unregister_settings_group();
delete_options();

?>