<?php

if (!class_exists('reCAPTCHA')) {
    class reCAPTCHA {
        // member variables
        private $options;
        private $wordpress_mu;

        private $saved_error;
        
        function __construct() {
            // initialize anything that might need initializing
            
            // determine what environment we're in
            $this->determine_environment();
            
            // get the site options
            $this->retrieve_options();
            
            // require the recaptcha library
            $this->require_library();
            
            // register the hooks
            $this->register_actions();
            $this->register_filters();
        }
        
        function register_actions() {
            // Actions

            // styling
            add_action('wp_head', array(&$this, 'register_stylesheets')); // make unnecessary: instead, inform of classes for styling
            add_action('admin_head', array(&$this, 'register_stylesheets')); // make unnecessary: shouldn't require styling in the options page
            add_action('login_head', array(&$this, 'register_stylesheets')); // make unnecessary: instead use jQuery and add to the footer?

            // options
            register_activation_hook($this->environment_prefix() . '/wp-recaptcha.php', array(&$this, 'register_default_options')); // this way it only happens once, when the plugin is activated
            add_action('admin_init', array(&$this, 'register_settings_group'));

            // recaptcha form display
            if ($this->wordpress_mu)
                add_action('signup_extra_fields', array(&$this, 'show_recaptcha_form'));
            else
                add_action('register_form', array(&$this, 'show_recaptcha_form'));

            add_action('comment_form', array(&$this, 'recaptcha_comment_form'));

            // recaptcha comment processing (look into doing all of this with AJAX, optionally)
            add_action('wp_head', array(&$this, 'saved_comment'));
            add_action('preprocess_comment', array(&$this, 'check_comment'));
            add_action('comment_post_redirect', array(&$this, 'relative_redirect'));

            // administration (menus, pages, notifications, etc.)
            $plugin = plugin_basename($this->environment_prefix() . '/wp-recaptcha.php');
            add_filter("plugin_action_links_$plugin", array(&$this, 'show_settings_link'));

            add_action('admin_menu', array(&$this, 'add_settings_page'));
        }
        
        function register_filters() {
            // Filters

            // recaptcha validation
            if ($this->wordpress_mu)
                add_filter('wpmu_validate_user_signup', array(&$this, 'validate_response_wpmu'));
            else
                add_filter('registration_errors', array(&$this, 'validate_response'));
        }

        function environment_prefix() {
            if ($this->wordpress_mu)
                return WP_CONTENT_DIR . '/mu-plugins/wp-recaptcha';
            else
                return WP_CONTENT_DIR . '/plugins/wp-recaptcha';
        }
        
        // determine whether it's WordPress regular or WordPress MU sitewide
        function determine_environment() {
            // is it wordpress mu?
            if (is_dir(WP_CONTENT_DIR . '/mu-plugins')) {
                // is it site-wide?
                if (is_file(WP_CONTENT_DIR . '/mu-plugins/wp-recaptcha.php')) // forced activated
                   $this->wordpress_mu = true;
            }
            
            // otherwise it's just regular wordpress
            else {
                $this->wordpress_mu = false;
            }
        }
        
        public function is_wordpress_mu() {
            return $wordpress_mu;
        }
        
        // set the default options
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
            if ($this->wordpress_mu)
                add_site_option('recaptcha_options', $option_defaults);
            else
                add_option('recaptcha_options', $option_defaults);
        }
        
        // retrieve the options (call as needed for refresh)
        function retrieve_options() {
            if ($this->wordpress_mu)
                $this->options = get_site_option('recaptcha_options');

            else
                $this->options = get_option('recaptcha_options');
        }
        
        // require the recaptcha library
        function require_library() {
            require_once($this->environment_prefix() . '/recaptchalib.php');
        }
        
        // register the settings
        function register_settings_group() {
            register_setting('recaptcha_options_group', 'recaptcha_options', array(&$this, 'validate_options'));
        }
        
        function register_stylesheets() {
            $path = $this->environment_prefix() . '/recaptcha.css';
                
            echo '<link rel="stylesheet" type="text/css" href="' . $path . '" />';
        }
        
        // stylesheet information
        function registration_style() {
            // if they don't want to show it in the registration form then just exit
           // todo: maybe just set the conditional on the add_action call?
           if (!$this->options['show_in_registration'])
               return;

            $width = 0; // the width of the recaptcha form

            // every theme is 358 pixels wide except for the
            // clean theme, so we have to programmatically handle that
            // todo: perhaps do this with jquery?
            if ($this->options['registration_theme'] == 'clean')
                $width = 485;
            else
                $width = 358;

            echo <<<REGISTRATION
                <style type="text/css">
                #login {
                    width: {$width}px !important;
                }

                #login a {
                    text-align: center;
                }

                #nav {
                    text-align: center;
                }
                form .submit {
                    margin-top: 10px;
                }
                </style>
REGISTRATION;
        }
        
        // display recaptcha
        function show_recaptcha_form($errors) {
            if ($this->options['show_in_registration']) {
                $format = <<<FORMAT
                <script type='text/javascript'>
                var RecaptchaOptions = { theme : '{$this->options['registration_theme']}', lang : '{$this->options['language']}' , tabindex : 30 };
                </script>
FORMAT;

                $comment_string = <<<COMMENT_FORM
                <script type='text/javascript'>   
                document.getElementById('recaptcha_table').style.direction = 'ltr';
                </script>
COMMENT_FORM;

                // todo: is this check necessary? look at the latest recaptchalib.php
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
                    $use_ssl = true;
                else
                    $use_ssl = false;

                // if it's for wordpress mu, show the errors
                if ($this->wordpress_mu) {
                    $error = $errors->get_error_message('captcha');
                    echo '<label for="verification">Verification:</label>';
                    echo ($error ? '<p class="error">'.$error.'</p>' : '');
                    echo $format . recaptcha_wp_get_html($_GET['rerror'], $use_ssl);
                }
                
                // for regular wordpress
                else {
                    echo '<hr style="clear: both; margin-bottom: 1.5em; border: 0; border-top: 1px solid #999; height: 1px;" />';
                    echo $format . recaptcha_wp_get_html($_GET['rerror'], $use_ssl);
              }
           }
        }
        
        function validate_options($input) {
            $validated['public_key'] = $input['public_key'];
            $validated['private_key'] = $input ['private_key'];
            $validated['show_in_comments'] = ($input['show_in_comments'] == 1 ? 1 : 0);
            return $validated;
        }
        
        // recaptcha validation
        function validate_response_old() {
            global $errors;
            
            // empty so throw the empty response error
            if (empty($_POST['recaptcha_response_field']))
                $errors['blank_captcha'] = $this->options['no_response_error'];
            
            else {
                $response = recaptcha_check_answer($this->options['private_key'], $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
                
                // response is bad, add incorrect response error
                if (!$response->is_valid)
                    if ($response->error == 'incorrect-captcha-sol')
                        $errors['captcha_wrong'] = $this->options['incorrect_response_error'];
            }
        }
        
        function validate_response($errors) {
            // empty so throw the empty response error
            if (empty($_POST['recaptcha_response_field']) || $_POST['recaptcha_response_field'] == '') {
                $errors->add('blank_captcha', $this->options['no_response_error']);
                return $errors;
            }

            $response = recaptcha_check_answer($this->options['private_key'], $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);

            // response is bad, add incorrect response error
            if (!$response->is_valid)
                if ($response->error == 'incorrect-captcha-sol')
                    $errors->add('captcha_wrong', $this->options['incorrect_response_error']);

           return $errors;
        }
        
        function validate_response_wpmu($result) {
            // must make a check here, otherwise the wp-admin/user-new.php script will keep trying to call
            // this function despite not having called do_action('signup_extra_fields'), so the recaptcha
            // field was never shown. this way it won't validate if it's called in the admin interface
            
            if (!is_admin()) {
                // blogname in 2.6, blog_id prior to that
                // todo: why is this done?
                if (isset($_POST['blog_id']) || isset($_POST['blogname']))
                    return $result;
                    
                // no text entered
                if (empty($_POST['recaptcha_response_field']) || $_POST['recaptcha_response_field'] == '') {
                    $result['errors']->add('blank_captcha', $this->options['no_response_error']);
                    return $result;
                }
                
                $response = recaptcha_check_answer($this->options['private_key'], $_SERVER['REMOTEADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
                
                // response is bad, add incorrect response error
                // todo: why echo the error here? wpmu specific?
                if (!$response->is_valid)
                    if ($response->error == 'incorrect-captcha-sol') {
                        $result['errors']->add('captcha_wrong', $this->options['incorrect_response_error']);
                        echo '<div class="error">' . $this->options['incorrect_response_error'] . '</div>';
                    }
                    
                return $result;
            }
        }
        
        // utility methods
        function hash_comment($id) {
            define ("RECAPTCHA_WP_HASH_SALT", "b7e0638d85f5d7f3694f68e944136d62");
            
            if (function_exists('wp_hash'))
                return wp_hash(RECAPTCHA_WP_HASH_COMMENT . $id);
            else
                return md5(RECAPTCHA_WP_HASH_COMMENT . $this->options['private_key'] . $id);
        }
        
        function get_recaptcha_html($recaptcha_error, $use_ssl=false) {
            return recaptcha_get_html($this->options['public_key'], $recaptcha_error, $use_ssl, $this->options['xhtml_compliance']);
        }
        
        function recaptcha_comment_form() {
            global $user_ID;

            // set the minimum capability needed to skip the captcha if there is one
            if ($this->options['bypass_for_registered_uysers'] && $this->options['minimum_bypass_level'])
                $needed_capability = $this->options['minimum_bypass_level'];

            // skip the reCAPTCHA display if the minimum capability is met
            if (($needed_capability && current_user_can($needed_capability)) || !$this->options['show_in_comments'])
                return;

            else {
                // Did the user fail to match the CAPTCHA? If so, let them know
                if ($_GET['rerror'] == 'incorrect-captcha-sol')
                    echo '<p class="recaptcha-error">' . $this->options['incorrect_response_error'] . "</p>";

                //modify the comment form for the reCAPTCHA widget
                $recaptcha_js_opts = <<<OPTS
                <script type='text/javascript'>
                    var RecaptchaOptions = { theme : '{$this->options['registration_theme']}', lang : '{$this->options['language']}' , tabindex : {$this->options['tab_index']} };
                </script>
OPTS;

                // todo: replace this with jquery: http://digwp.com/2009/06/including-jquery-in-wordpress-the-right-way/
                if ($this->options['xhtml_compliance']) {
                    $comment_string = <<<COMMENT_FORM
                        <div id="recaptcha-submit-btn-area"><br /></div>
                        <script type='text/javascript'>
                        var sub = document.getElementById('submit');
                        sub.parentNode.removeChild(sub);
                        document.getElementById('recaptcha-submit-btn-area').appendChild (sub);
                        document.getElementById('submit').tabIndex = 6;
                        if ( typeof _recaptcha_wordpress_savedcomment != 'undefined') {
                                document.getElementById('comment').value = _recaptcha_wordpress_savedcomment;
                        }
                        document.getElementById('recaptcha_table').style.direction = 'ltr';
                        </script>
COMMENT_FORM;
                }

                else {
                    $comment_string = <<<COMMENT_FORM
                        <div id="recaptcha-submit-btn-area"></div> 
                        <script type='text/javascript'>
                        var sub = document.getElementById('submit');
                        sub.parentNode.removeChild(sub);
                        document.getElementById('recaptcha-submit-btn-area').appendChild (sub);
                        document.getElementById('submit').tabIndex = 6;
                        if ( typeof _recaptcha_wordpress_savedcomment != 'undefined') {
                                document.getElementById('comment').value = _recaptcha_wordpress_savedcomment;
                        }
                        document.getElementById('recaptcha_table').style.direction = 'ltr';
                        </script>
                        <noscript>
                         <style type='text/css'>#submit {display:none;}</style>
                         <input name="submit" type="submit" id="submit-alt" tabindex="6" value="Submit Comment"/> 
                        </noscript>
COMMENT_FORM;
                }

                // todo: is this still needed with new recaptchalib?
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
                    $use_ssl = true;
                else
                    $use_ssl = false;

                echo $recaptcha_js_opts . recaptcha_wp_get_html($_GET['rerror'], $use_ssl) . $comment_string;
           }
        }
        
        // todo: this doesn't seem necessary
        function show_captcha_for_comment() {
            global $user_ID;
            return true;
        }
        
        function check_comment($comment_data) {
            global $user_ID;
            
            if ($this->options['bypass_for_registered_users'] && $this->options['minimum_bypass_level'])
                $needed_capability = $this->options['minimum_bypass_level'];
            
            if (($needed_capability && current_user_can($needed_capability)) || !$this->options['show_in_comments'])
                return $comment_data;
            
            if (show_captcha_for_comment()) {
                // do not check trackbacks/pingbacks
                if ($comment_data['comment_type'] == '') {
                    $challenge = $_POST['recaptcha_challenge_field'];
                    $response = $_POST['recaptcha_response_field'];
                    
                    $recaptcha_response = recaptcha_check_answer($this->options['private_key'], $_SERVER['REMOTE_ADDR'], $challenge, $response);
                    
                    if ($recaptcha_response->is_valid)
                        return $comment_data;
                        
                    else {
                        $this->saved_error = $recaptcha_response->error;
                        
                        add_filter('pre_comment_approved', create_function('$a', 'return \'spam\';'));
                        return $comment_data;
                    }
                }
            }
            
            return $comment_data;
        }
        
        function relative_redirect($location, $comment) {
            if ($this->saved_error != '') {
                // replace #comment- at the end of $location with #commentform
                
                $location = substr($location, 0, strpos($location, '#')) .
                    ((strpos($location, "?") === false) ? "?" : "&") .
                    'rcommentid=' . $comment->comment_ID .
                    '&rerror=' . $this->saved_error .
                    '&rchash=' . hash_comment($comment->comment_ID) .
                    '#commentform';
            }
            
            return $location;
        }
        
        function saved_comment() {
            if (!is_single() && !is_page())
                return;
            
            if ($_GET['rcommentid'] && $_GET['rchash'] == hash_comment($_GET['rcommentid'])) {
                $comment = get_comment($_GET['rcommentid']);

                $com = preg_replace('/([\\/\(\)\+\;\'\"])/e','\'%\'.dechex(ord(\'$1\'))', $comment->comment_content);
                $com = preg_replace('/\\r\\n/m', '\\\n', $com);

                echo "
                <script type='text/javascript'>
                var _recaptcha_wordpress_savedcomment =  '" . $com  ."';
                _recaptcha_wordpress_savedcomment = unescape(_recaptcha_wordpress_savedcomment);
                </script>
                ";

                wp_delete_comment($comment->comment_ID);
            }
        }
        
        // todo: is this still needed?
        function blog_domain() {
            $uri = parse_url(get_settings('siteurl'));
            return $uri['host'];
        }
        
        // add a settings link to the plugin in the plugin list
        function show_settings_link($links) {
            $settings_link = '<a href="options-general.php?page=wp-recaptcha/recaptcha.php" title="Go to the Settings for this Plugin">Settings</a>';
            array_unshift($links, $settings_link);
            return $links;
        }
        
        // add the settings page
        function add_settings_page() {
            // add the options page
            if ($this->wordpress_mu && is_site_admin())
                add_submenu_page('wpmu-admin.php', 'WP-reCAPTCHA', 'WP-reCAPTCHA', 'manage_options', __FILE__, array(&$this, 'show_settings_page'));

            add_options_page('WP-reCAPTCHA', 'WP-reCAPTCHA', 'manage_options', __FILE__, array(&$this, 'show_settings_page'));
        }
        
        // store the xhtml in a separate file and use include on it
        function show_settings_page() {
            $options = $this->options;
            include("settings.html");
        }
        
        function options_subpanel() {
            // $this->register_defaults(); this is no longer needed?
            
            // Check form submission and update options if no error occurred
            if (isset($_POST['submit'])) {
                $options_update = array();
                
                // keys
                $option_defaults['public_key'] = trim($_POST['public_key']); // the public key for reCAPTCHA
                $option_defaults['private_key'] = trim($_POST['private_key']); // the private key for reCAPTCHA

                // placement
                $option_defaults['show_in_comments'] = $_POST['show_in_comments']; // whether or not to show reCAPTCHA on the comment post
                $option_defaults['show_in_registration'] = $_POST['show_in_registration']; // whether or not to show reCAPTCHA on the registration page

                // bypass levels
                $option_defaults['bypass_for_registered_users'] = $_POST['bypass_for_registered_users']; // whether to skip reCAPTCHAs for registered users
                $option_defaults['minimum_bypass_level'] = $_POST['minimum_bypass_level']; // who doesn't have to do the reCAPTCHA (should be a valid WordPress capability slug)

                // styling
                $option_defaults['comments_theme'] = $_POST['comments_theme']; // the default theme for reCAPTCHA on the comment post
                $option_defaults['registration_theme'] = $_POST['registration_theme']; // the default theme for reCAPTCHA on the registration form
                $option_defaults['language'] = $_POST['language']; // the default language for reCAPTCHA
                $option_defaults['xhtml_compliance'] = $_POST['xhtml_compliance']; // whether or not to be XHTML 1.0 Strict compliant
                $option_defaults['tab_index'] = $_POST['tab_index']; // the default tabindex for reCAPTCHA

                // error handling
                $option_defaults['no_response_error'] = $_POST['no_response_error']; // message for no CAPTCHA response
                $option_defaults['incorrect_response_error'] = $_POST['incorrect_response_error']; // message for incorrect CAPTCHA response
                
                if ($this->wordpress_mu)
                    update_site_option('recaptcha', $options_update);
                else
                    update_option('recaptcha', $options_update);
            }
        }
        
        function recaptcha_dropdown_capabilities($select_name, $checked_value="") {
            // define choices: Display text => permission slug
            $capability_choices = array (
                'All registered users' => 'read',
                'Edit posts' => 'edit_posts',
                'Publish Posts' => 'publish_posts',
                'Moderate Comments' => 'moderate_comments',
                'Administer site' => 'level_10'
                );
                
            // print the <select> and loop through <options>
            echo '<select name="' . $select_name . '" id="' . $select_name . '">' . "\n";
            
            foreach ($capability_choices as $text => $capability) {
                if ($capability == $checked_value)
                    $checked = ' selected="selected" ';
                
                echo '\t <option value="' . $capability . '"' . $checked . ">$text</option> \n";
                $checked = NULL;
            }
            
            echo "</select> \n";
        }
    } // end class declaration
} // end of class exists clause

?>