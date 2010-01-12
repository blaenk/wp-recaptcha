<?php

define ("RECAPTCHA_WP_HASH_SALT", "b7e0638d85f5d7f3694f68e944136d62");

if (!class_exists('recaptcha')) {
    class recaptcha {
	    // member variables
	    private $options;
	    private $wordpress_mu;
	    
	    private $saved_error;
	    
		function __construct() {
		    // initialize anything that might need initializing
		    
		    // determine what environment we're in
		    $this->determine_environment();
		    
		    // get the site options (get_site_option for sitewide WPMU options)
		    // $this->retrieve_options();
		    
		    // register the options
		    $this->register_options();
		    
		    // require the recaptcha library
		    $this->require_library();
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
		
		// retrieve the options
		function retrieve_options() {
		    if ($this->wordpress_mu)
		        $this->options = get_site_option('recaptcha');
		        
		    else
		        $this->options = get_option('recaptcha');
		}
		
		// require the recaptcha library
		function require_library() {
		    if ($this->wordpress_mu)
		        require_once(WP_CONTENT_DIR . '/mu-plugins/wp-recaptcha/recaptchalib.php');
		    else
		        require_once(WP_CONTENT_DIR . '/plugins/recaptchalib.php');
		}
		
		// register the settings
		function register_options() {
		    register_setting('recaptcha_options', 'public_key');
		    register_setting('recaptcha_options', 'private_key');
		    
		    register_setting('recaptcha_options', 'show_in_comments');
		    register_setting('recaptcha_options', 'show_in_registration');
		    
		    register_setting('recaptcha_options', 'bypass_for_registered_users');
		    register_setting('recaptcha_options', 'minimum_bypass_level');
		    
		    register_setting('recaptcha_options', 'comments_theme');
		    register_setting('recaptcha_options', 'language');
		    register_setting('recaptcha_options', 'xhtml_compliance');
		    register_setting('recaptcha_options', 'tab_index');
		    
		    register_setting('recaptcha_options', 'no_response_error');
		    register_setting('recaptcha_options', 'incorrect_response_error');
		}
		
		// plugin options (probably not needed)
		function register_defaults() {
		    // keys
		    add_option('public_key', ''); // the public key for reCAPTCHA
		    add_option('private_key', ''); // the private key for reCAPTCHA
		    
		    // placement
		    add_option('show_in_comments', true); // whether or not to show reCAPTCHA on the comment post
		    add_option('show_in_registration', true); // whether or not to show reCAPTCHA on the registration page
		    
		    // bypass levels
		    add_option('bypass_for_registered_users', true); // whether to skip reCAPTCHAs for registered users
		    add_option('minimum_bypass_level', ''); // who doesn't have to do the reCAPTCHA (WP capability slug)
		    
		    // styling
		    add_option('comments_theme', 'red'); // the default theme for reCAPTCHA on the comment post
		    add_option('registration_theme', 'red'); // the default theme for reCAPTCHA on the registration form
		    add_option('language', 'en'); // the default language for reCAPTCHA
		    add_option('xhtml_compliance', false); // whether or not to be XHTML 1.0 Strict Compliant
		    add_option('tab_index', 5); // the default tab-index for reCAPTCHA
		    
		    // error handling
		    add_option('no_response_error', '<strong>ERROR</strong>: Please fill in the reCAPTCHA form.'); // message for no CAPTCHA response
		    add_option('incorrect_response_error', '<strong>ERROR</strong>: That reCAPTCHA response was incorrect.'); // message for incorrect CAPTCHA response
		}
		
		function delete_options() {
		    if (!$this->wordpress_mu) {
                delete_option('recaptcha');
            }
		}
        
        function register_stylesheets() {
            $path = WP_CONTENT_URL . '/plugins/wp-recaptcha/recaptcha.css';
            
            if ($this->wordpres_mu)
                $path = WP_CONTENT_URL . '/mu-plugins/wp-recaptcha/recaptcha.css';
                
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
                    echo ($error ? '<p class="error">'.$error.'</p>' : '')
                    echo $format . recaptcha_wp_get_html($_GET['rerror'], $use_ssl);
                }
                
        		// for regular wordpress
        		else {
                    echo '<hr style="clear: both; margin-bottom: 1.5em; border: 0; border-top: 1px solid #999; height: 1px;" />';
                    echo $format . recaptcha_wp_get_html($_GET['rerror'], $use_ssl);
              }
           }
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
		    return recaptcha_get_html($recaptcha_opt['public_key'], $recaptcha_error, $use_ssl, $recaptcha_opt['xhtml_compliance']);
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
        
        function add_options_to_admin() {
            // for wordpress mu
            // todo: are both lines required?
            if ($this->wordpress_mu && is_site_admin()) {
                add_submenu_page('wpmu-admin.php', 'reCAPTCHA', 'reCAPTCHA', 'manage_options', __FILE__, 'recaptcha_wp_options_subpanel');
                add_options_page('reCAPTCHA', 'reCAPTCHA', 'manage_options', __FILE__, 'recaptcha_wp_options_subpanel');
            }
    
            // if it's regular wordpress
            else {
                add_options_page('reCAPTCHA', 'reCAPTCHA', 'manage_options', __FILE__, 'recaptcha_wp_options_subpanel');
            }
        }
        
        // store the xhtml in a separate file and use include on it
        function settings_page() {
            ?>
            
            <form method="post" action="self">
                <?php settings_fields('recaptcha_options'); ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Public Key</th>
                        <td><input type="text" name="private_key" value="<?php echo get_option('private_key'); ?>" /></td>
                    </tr>
                </table>
                
                <p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
            </form>
            
            <?php
        }
        
        function options_subpanel() {
            $this->register_defaults();
            
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