<?php
/*
Plugin Name: WP-reCAPTCHA
Plugin URI: http://www.blaenkdenum.com/wp-recaptcha/
Description: Integrates reCAPTCHA anti-spam solutions with wordpress
Version: 2.9.6
Author: Jorge Peña
Email: support@recaptcha.net
Author URI: http://www.blaenkdenum.com
*/

// Plugin was initially created by Ben Maurer and Mike Crawford
// Permissions/2.5 transition help from Jeremy Clarke @ http://globalvoicesonline.org

// WORDPRESS MU DETECTION

// WordPress MU settings - DON'T EDIT
//    0 - Regular WordPress installation
//    1 - WordPress MU Forced Activated
//    2 - WordPress MU Optional Activation

$wpmu = 0;

if (basename(dirname(__FILE__)) == "mu-plugins") // forced activated
   $wpmu = 1;
else if (basename(dirname(__FILE__)) == "plugins" && function_exists('is_site_admin')) // optionally activated
   $wpmu = 2;

if ($wpmu == 1)
   $recaptcha_opt = get_site_option('recaptcha'); // get the options from the database
else
   $recaptcha_opt = get_option('recaptcha'); // get the options from the database

// END WORDPRESS MU DETECTION
   
if ($wpmu == 1)
   require_once(dirname(__FILE__) . '/wp-recaptcha/recaptchalib.php');
else
   require_once(dirname(__FILE__) . '/recaptchalib.php');

// doesn't need to be secret, just shouldn't be used by any other code.
define ("RECAPTCHA_WP_HASH_SALT", "b7e0638d85f5d7f3694f68e944136d62");

/* =============================================================================
   CSS - This links the pages to the stylesheet to be properly styled
   ============================================================================= */

function re_css() {
   global $recaptcha_opt, $wpmu;
   
   if (!defined('WP_CONTENT_URL'))
      define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
   
   $path = WP_CONTENT_URL . '/plugins/wp-recaptcha/recaptcha.css';
   
   if ($wpmu == 1)
		$path = WP_CONTENT_URL . '/mu-plugins/wp-recaptcha/recaptcha.css';
   
   echo '<link rel="stylesheet" type="text/css" href="' . $path . '" />';
}

function registration_css() {
   global $recaptcha_opt;
   
   if ($recaptcha_opt['re_registration']) {
		$width = 0;
		
		if ($recaptcha_opt['re_theme_reg'] == 'red' ||
          $recaptcha_opt['re_theme_reg'] == 'white' ||
          $recaptcha_opt['re_theme_reg'] == 'blackglass')
          $width = 358;
		else if ($recaptcha_opt['re_theme_reg'] == 'clean')
          $width = 485;
		
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
}

add_action('wp_head', 're_css'); // include the stylesheet in typical pages to style hidden emails
add_action('admin_head', 're_css'); // include stylesheet to style options page
add_action('login_head', 'registration_css'); // include the login div styling, embedded

/* =============================================================================
   End CSS
   ============================================================================= */

// If the plugin is deactivated, delete the preferences
function delete_preferences() {
   global $wpmu;

   if ($wpmu != 1)
		delete_option('recaptcha');
}

register_deactivation_hook(__FILE__, 'delete_preferences');

/* =============================================================================
   reCAPTCHA on Registration Form - Thanks to Ben C.'s recapture plugin
   ============================================================================= */
   
// Display the reCAPTCHA on the registration form
function display_recaptcha($errors) {
	global $recaptcha_opt, $wpmu;
   
   if ($recaptcha_opt['re_registration']) {
		$format = <<<END
		<script type='text/javascript'>
		var RecaptchaOptions = { theme : '{$recaptcha_opt['re_theme_reg']}', lang : '{$recaptcha_opt['re_lang']}' , tabindex : 30 };
		</script>
END;
		
		$comment_string = <<<COMMENT_FORM
		<script type='text/javascript'>   
		document.getElementById('recaptcha_table').style.direction = 'ltr';
		</script>
COMMENT_FORM;

		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
         $use_ssl = true;
		else
         $use_ssl = false;
		
		if ($wpmu == 1) {
   		$error = $errors->get_error_message('captcha'); ?>
   		<label for="verification">Verification:</label>
         <?php echo($error ? '<p class="error">'.$error.'</p>' : '') ?>
         <?php echo $format . recaptcha_wp_get_html($_GET['rerror'], $use_ssl); ?>
   		<?php }
		
		else {
         echo '<hr style="clear: both; margin-bottom: 1.5em; border: 0; border-top: 1px solid #999; height: 1px;" />';
         echo $format . recaptcha_wp_get_html($_GET['rerror'], $use_ssl);
      }
   }
}

// Hook the display_recaptcha function into WordPress
if ($wpmu != 1)
   add_action('register_form', 'display_recaptcha');
else
   add_action('signup_extra_fields', 'display_recaptcha');

// Check the captcha
function check_recaptcha() {
	global $recaptcha_opt, $errors;
	
   if (empty($_POST['recaptcha_response_field']))
		$errors['blank_captcha'] = $recaptcha_opt['error_blank'];
   
   else {
   	$response = recaptcha_check_answer($recaptcha_opt['privkey'],
		$_SERVER['REMOTE_ADDR'],
		$_POST['recaptcha_challenge_field'],
		$_POST['recaptcha_response_field']);

   	if (!$response->is_valid)
   		if ($response->error == 'incorrect-captcha-sol')
   				$errors['captcha_wrong'] = $recaptcha_opt['error_incorrect'];
   }
}

// Check the captcha
function check_recaptcha_new($errors) {
	global $recaptcha_opt;
	
   if (empty($_POST['recaptcha_response_field']) || $_POST['recaptcha_response_field'] == '') {
		$errors->add('blank_captcha', $recaptcha_opt['error_blank']);
		return $errors;
   }
   
	$response = recaptcha_check_answer($recaptcha_opt['privkey'],
      $_SERVER['REMOTE_ADDR'],
      $_POST['recaptcha_challenge_field'],
      $_POST['recaptcha_response_field'] );

	if (!$response->is_valid)
		if ($response->error == 'incorrect-captcha-sol')
			$errors->add('captcha_wrong', $recaptcha_opt['error_incorrect']);
   
   return $errors;
}

// Check the recaptcha on WordPress MU
function check_recaptcha_wpmu($result) {
   global $_POST, $recaptcha_opt;
   
   // must make a check here, otherwise the wp-admin/user-new.php script will keep trying to call
   // this function despite not having called do_action('signup_extra_fields'), so the recaptcha
   // field was never shown. this way it won't validate if it's called in the admin interface
   if (!is_admin()) {
         // It's blogname in 2.6, blog_id prior to that
      if (isset($_POST['blog_id']) || isset($_POST['blogname']))
      	return $result;

      // no text entered
      if (empty($_POST['recaptcha_response_field']) || $_POST['recaptcha_response_field'] == '') {
      	$result['errors']->add('blank_captcha', $recaptcha_opt['error_blank']);
      	return $result;
      }

      $response = recaptcha_check_answer($recaptcha_opt['privkey'],
         $_SERVER['REMOTE_ADDR'],
         $_POST['recaptcha_challenge_field'],
         $_POST['recaptcha_response_field'] );

      // incorrect CAPTCHA
      if (!$response->is_valid)
      	if ($response->error == 'incorrect-captcha-sol') {
      		$result['errors']->add('captcha_wrong', $recaptcha_opt['error_incorrect']);
            echo "<div class=\"error\">". $recaptcha_opt['error_incorrect'] . "</div>";
      	}
   }
   
   return $result;
}

if ($recaptcha_opt['re_registration']) {
   if ($wpmu == 1)
		add_filter('wpmu_validate_user_signup', 'check_recaptcha_wpmu');
   
   else if ($wpmu == 0) {
		// Hook the check_recaptcha function into WordPress
		if (version_compare(get_bloginfo('version'), '2.5' ) >= 0)
         add_filter('registration_errors', 'check_recaptcha_new');
		else
         add_filter('registration_errors', 'check_recaptcha');
   }
}
/* =============================================================================
   End reCAPTCHA on Registration Form
   ============================================================================= */

/* =============================================================================
   reCAPTCHA Plugin Default Options
   ============================================================================= */

$option_defaults = array (
   'pubkey'	=> '', // the public key for reCAPTCHA
   'privkey'	=> '', // the private key for reCAPTCHA
   'use_mailhide_posts' => '0', // mailhide for posts/pages
   'use_mailhide_comments' => '0', // use mailhide for comments
   'use_mailhide_rss' => '0', // use mailhide for the rss feed of the posts/pages
   'use_mailhide_rss_comments' => '0', // use mailhide for the rss comments
   're_bypass' => '', // whether to sometimes skip reCAPTCHAs for registered users
   're_bypasslevel' => '', // who doesn't have to do the reCAPTCHA (should be a valid WordPress capability slug)
   'mh_bypass' => '', // whether to sometimes skip the MailHide filter for registered users
   'mh_bypasslevel' => '', // who can see full emails normally (should be a valid WordPress capability slug)
   'mailhide_pub' => '', // mailhide public key
   'mailhide_priv' => '', // mailhide private key
   're_theme' => 'red', // the default theme for reCAPTCHA on the comment post
   're_theme_reg' => 'red', // the default theme for reCAPTCHA on the registration form
   're_lang' => 'en', // the default language for reCAPTCHA
   're_tabindex' => '5', // the default tabindex for reCAPTCHA
   're_comments' => '1', // whether or not to show reCAPTCHA on the comment post
   're_registration' => '1', // whether or not to show reCAPTCHA on the registratoin page
   're_xhtml' => '0', // whether or not to be XHTML 1.0 Strict compliant
   'mh_replace_link' => '', // name the link something else
   'mh_replace_title' => '', // title of the link
   'error_blank' => '<strong>ERROR</strong>: Please fill in the reCAPTCHA form.', // the message to display when the user enters no CAPTCHA response
   'error_incorrect' => '<strong>ERROR</strong>: That reCAPTCHA response was incorrect.', // the message to display when the user enters the incorrect CAPTCHA response
);

// install the defaults
if ($wpmu != 1)
   add_option('recaptcha', $option_defaults, 'reCAPTCHA Default Options', 'yes');

/* =============================================================================
   End reCAPTCHA Plugin Default Options
   ============================================================================= */

/* =============================================================================
   MailHide - This scans for addresses and hides them using the MailHide API
   ============================================================================= */

// The main mailhide filter
function mh_insert_email($content = '') {
   global $recaptcha_opt;
  
   // set the minimum capability needed to skip the MailHide if there is one
   if ($recaptcha_opt['mh_bypass'] && $recaptcha_opt['mh_bypasslevel'])
      $needed_capability = $recaptcha_opt['mh_bypasslevel'];
        
	// skip the MailHide display if the minimum capability is met
	if (($needed_capability && current_user_can($needed_capability)) || !$recaptcha_opt['re_comments']) {
      // remove the nohides
      $content = preg_replace('/\[\/?nohide\]/i','',$content);
		return $content;
   }

   // Regular Expressions thanks to diabolic from EFNet #regex
   
   // match hyperlinks with emails
   $regex = '%(?<!\[nohide\])<a[^>]*href="((?:mailto:)?([^@"]+@[^@"]+))"[^>]*>(.+?)<\/a>(?!\[/nohide\])%i';
   $content = preg_replace_callback($regex, "mh_replace_hyperlink", $content);
   
   // match emails
   $regex = '%\b([\w.+-]+@[a-z\d.-]+\.[a-z]{2,6})\b(?!\s*\[\/nohide\]|(?:(?!<a[^>]*>).)*<\/a>)%i';
   $content = preg_replace_callback($regex, "mh_replace", $content);
   
   // remove the nohides
   $content = preg_replace('/\[\/?nohide\]/i','',$content);
   return $content;
}

// replace the hyperlinked emails i.e. <a href="haha@lol.com">this</a> or <a href="mailto:haha@lol.com">that</a>
function mh_replace_hyperlink($matches) {
   global $recaptcha_opt;
   
   // set the minimum capability needed to skip the MailHide if there is one
   if ($recaptcha_opt['mh_bypass'] && $recaptcha_opt['mh_bypasslevel'])
      $needed_capability = $recaptcha_opt['mh_bypasslevel'];
        
	// skip the MailHide display if the minimum capability is met
	if (($needed_capability && current_user_can($needed_capability)) || !$recaptcha_opt['re_comments']) {
      // remove the nohides
      $content = preg_replace('/\[\/?nohide\]/i','',$content);
		return $content;
   }
   
   // get the url, the part inside the href. this is the email of course
   $url = recaptcha_mailhide_url($recaptcha_opt['mailhide_pub'], $recaptcha_opt['mailhide_priv'], $matches[2]);
   
   // construct a new hyperlink with the url hidden but the link text the same
   $html = "<a href='" . $url . "' onclick=\"window.open('" . htmlentities ($url, ENT_QUOTES) . "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\">" . $matches[3] . "</a>";
   
   // style it
   $html = '<span class="mh-hyperlinked">' . $html . "</span>";
   
   return $html;
}

// replace the plain text emails i.e. haha@lol.com
function mh_replace($matches) {
   global $recaptcha_opt;
   
   # var_dump($matches);
   
   if ($recaptcha_opt['mh_replace_link'] == "" && $recaptcha_opt['mh_replace_title'] == "") {
      // find plain text emails and hide them
      $html = recaptcha_mailhide_html($recaptcha_opt['mailhide_pub'], $recaptcha_opt['mailhide_priv'], $matches[0]);
   }
   
   else {
      // replace both things
      if ($recaptcha_opt['mh_replace_link'] != "" && $recaptcha_opt['mh_replace_title'] != "") {
         $url = recaptcha_mailhide_url($recaptcha_opt['mailhide_pub'], $recaptcha_opt['mailhide_priv'], $matches[0]);
         $html = "<a href='" . htmlentities($url, ENT_QUOTES) .
      		"' onclick=\"window.open('" . htmlentities($url, ENT_QUOTES) . "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" title=\"" . $recaptcha_opt['mh_replace_title'] . "\">" . $recaptcha_opt['mh_replace_link'] . "</a>";
      }
      
      // only replace the link
      else if ($recaptcha_opt['mh_replace_link'] != "" && $recaptcha_opt['mh_replace_title'] == "") {
         $url = recaptcha_mailhide_url($recaptcha_opt['mailhide_pub'], $recaptcha_opt['mailhide_priv'], $matches[0]);
         $html = "<a href='" . htmlentities($url, ENT_QUOTES) .
      		"' onclick=\"window.open('" . htmlentities($url, ENT_QUOTES) . "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" title=\"Reveal this e-mail address\">" . $recaptcha_opt['mh_replace_link'] . "</a>";
      }
      
      // only replace the title
      else if ($recaptcha_opt['mh_replace_link'] == "" && $recaptcha_opt['mh_replace_title'] != "") {
         $url = recaptcha_mailhide_url($recaptcha_opt['mailhide_pub'], $recaptcha_opt['mailhide_priv'], $matches[0]);
         $emailparts = _recaptcha_mailhide_email_parts ($matches[0]);
      	
      	$html = htmlentities($emailparts[0], ENT_QUOTES) . "<a href='" . htmlentities($url, ENT_QUOTES) .
      		"' onclick=\"window.open('" . htmlentities($url, ENT_QUOTES) . "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" title=\"" . $recaptcha_opt['mh_replace_title'] . "\">...</a>@" . htmlentities($emailparts[0], ENT_QUOTES);
      }
   }
   
   // style it
   $html = '<span class="mh-plaintext">' . $html . "</span>";
   
   return $html;
}

// add the filters only if mcrypt is loaded
if (extension_loaded('mcrypt')) {
   if ($recaptcha_opt['use_mailhide_posts'])
      add_filter('the_content', 'mh_insert_email');
   if ($recaptcha_opt['use_mailhide_comments'])
      add_filter('get_comment_text', 'mh_insert_email');
   if ($recaptcha_opt['use_mailhide_rss'])
      add_filter('the_content_rss', 'mh_insert_email');
   if ($recaptcha_opt['use_mailhide_rss_comments'])
      add_filter('comment_text_rss', 'mh_insert_email');
}

/* =============================================================================
   End MailHide
   ============================================================================= */

/* =============================================================================
   reCAPTCHA - The reCAPTCHA comment spam protection section
   ============================================================================= */
function recaptcha_wp_hash_comment($id)
{
	global $recaptcha_opt;
   
	if (function_exists('wp_hash'))
		return wp_hash(RECAPTCHA_WP_HASH_COMMENT . $id);
	else
		return md5(RECAPTCHA_WP_HASH_COMMENT . $recaptcha_opt['privkey'] . $id);
}

function recaptcha_wp_get_html ($recaptcha_error, $use_ssl=false) {
	global $recaptcha_opt;
   
	return recaptcha_get_html($recaptcha_opt['pubkey'], $recaptcha_error, $use_ssl, $recaptcha_opt['re_xhtml']);
}

/**
 *  Embeds the reCAPTCHA widget into the comment form.
 * 
 */	
function recaptcha_comment_form() {
   global $user_ID, $recaptcha_opt;

   // set the minimum capability needed to skip the captcha if there is one
   if ($recaptcha_opt['re_bypass'] && $recaptcha_opt['re_bypasslevel'])
      $needed_capability = $recaptcha_opt['re_bypasslevel'];

	// skip the reCAPTCHA display if the minimum capability is met
	if (($needed_capability && current_user_can($needed_capability)) || !$recaptcha_opt['re_comments'])
		return;
   
   else {
		// Did the user fail to match the CAPTCHA? If so, let them know
		if ($_GET['rerror'] == 'incorrect-captcha-sol')
		echo "<p class=\"recaptcha-error\">" . $recaptcha_opt['error_incorrect'] . "</p>";
   
		//modify the comment form for the reCAPTCHA widget
		$recaptcha_js_opts = <<<OPTS
		<script type='text/javascript'>
				var RecaptchaOptions = { theme : '{$recaptcha_opt['re_theme']}', lang : '{$recaptcha_opt['re_lang']}' , tabindex : {$recaptcha_opt['re_tabindex']} };
		</script>
OPTS;

		if ($recaptcha_opt['re_xhtml']) {
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

		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
         $use_ssl = true;
		else
         $use_ssl = false;
		
		echo $recaptcha_js_opts .  recaptcha_wp_get_html($_GET['rerror'], $use_ssl) . $comment_string;
   }
}

add_action('comment_form', 'recaptcha_comment_form');

function recaptcha_wp_show_captcha_for_comment() {
   global $user_ID;
   return true;
}

$recaptcha_saved_error = '';

/**
 * Checks if the reCAPTCHA guess was correct and sets an error session variable if not
 * @param array $comment_data
 * @return array $comment_data
 */
function recaptcha_wp_check_comment($comment_data) {
	global $user_ID, $recaptcha_opt;
	global $recaptcha_saved_error;
   	
   // set the minimum capability needed to skip the captcha if there is one
   if ($recaptcha_opt['re_bypass'] && $recaptcha_opt['re_bypasslevel'])
      $needed_capability = $recaptcha_opt['re_bypasslevel'];
        
	// skip the filtering if the minimum capability is met
	if (($needed_capability && current_user_can($needed_capability)) || !$recaptcha_opt['re_comments'])
		return $comment_data;
   
	if (recaptcha_wp_show_captcha_for_comment()) {
		if ( $comment_data['comment_type'] == '' ) { // Do not check trackbacks/pingbacks
			$challenge = $_POST['recaptcha_challenge_field'];
			$response = $_POST['recaptcha_response_field'];

			$recaptcha_response = recaptcha_check_answer ($recaptcha_opt ['privkey'], $_SERVER['REMOTE_ADDR'], $challenge, $response);
			if ($recaptcha_response->is_valid)
				return $comment_data;
			else {
				$recaptcha_saved_error = $recaptcha_response->error;
				add_filter('pre_comment_approved', create_function('$a', 'return \'spam\';'));
				return $comment_data;
			}
		}
	}
	return $comment_data;
}

/*
 * If the reCAPTCHA guess was incorrect from recaptcha_wp_check_comment, then redirect back to the comment form 
 * @param string $location
 * @param OBJECT $comment
 * @return string $location
 */
function recaptcha_wp_relative_redirect($location, $comment) {
	global $recaptcha_saved_error;
	if($recaptcha_saved_error != '') { 
		//replace the '#comment-' chars on the end of $location with '#commentform'.

		$location = substr($location, 0,strrpos($location, '#')) .
			((strrpos($location, "?") === false) ? "?" : "&") .
			'rcommentid=' . $comment->comment_ID . 
			'&rerror=' . $recaptcha_saved_error .
			'&rchash=' . recaptcha_wp_hash_comment ($comment->comment_ID) . 
			'#commentform';
	}
	return $location;
}

/*
 * If the reCAPTCHA guess was incorrect from recaptcha_wp_check_comment, then insert their saved comment text
 * back in the comment form. 
 * @param boolean $approved
 * @return boolean $approved
 */
function recaptcha_wp_saved_comment() {
   if (!is_single() && !is_page())
      return;

   if ($_GET['rcommentid'] && $_GET['rchash'] == recaptcha_wp_hash_comment ($_GET['rcommentid'])) {
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

function recaptcha_wp_blog_domain () {
	$uri = parse_url(get_settings('siteurl'));
	return $uri['host'];
}

add_filter('wp_head', 'recaptcha_wp_saved_comment',0);
add_filter('preprocess_comment', 'recaptcha_wp_check_comment',0);
add_filter('comment_post_redirect', 'recaptcha_wp_relative_redirect',0,2);

function recaptcha_wp_add_options_to_admin() {
   global $wpmu;

   if ($wpmu == 1 && is_site_admin()) {
		add_submenu_page('wpmu-admin.php', 'reCAPTCHA', 'reCAPTCHA', 'manage_options', __FILE__, 'recaptcha_wp_options_subpanel');
		add_options_page('reCAPTCHA', 'reCAPTCHA', 'manage_options', __FILE__, 'recaptcha_wp_options_subpanel');
   }
   else if ($wpmu != 1) {
		add_options_page('reCAPTCHA', 'reCAPTCHA', 'manage_options', __FILE__, 'recaptcha_wp_options_subpanel');
   }
}

function recaptcha_wp_options_subpanel() {
   global $wpmu;
	// Default values for the options array
	$optionarray_def = array(
		'pubkey'	=> '',
		'privkey' 	=> '',
		'use_mailhide_posts' => '',
		'use_mailhide_comments' => '',
		'use_mailhide_rss' => '',
		'use_mailhide_rss_comments' => '',
		're_bypasslevel' => '3',
		'mh_bypasslevel' => '3',
		'mailhide_pub' => '',
		'mailhide_priv' => '',
		're_theme' => 'red',
		're_theme_reg' => 'red',
		're_lang' => 'en',
		're_tabindex' => '5',
		're_comments' => '1',
		're_registration' => '1',
		're_xhtml' => '0',
      'mh_replace_link' => '',
      'mh_replace_title' => '',
      'error_blank' => '<strong>ERROR</strong>: Please fill in the reCAPTCHA form.',
      'error_incorrect' => '<strong>ERROR</strong>: That reCAPTCHA response was incorrect.',
		);

	if ($wpmu != 1)
		add_option('recaptcha', $optionarray_def, 'reCAPTCHA Options');

	/* Check form submission and update options if no error occurred */
	if (isset($_POST['submit'])) {
		$optionarray_update = array (
		'pubkey'	=> trim($_POST['recaptcha_opt_pubkey']),
		'privkey'	=> trim($_POST['recaptcha_opt_privkey']),
		'use_mailhide_posts' => $_POST['use_mailhide_posts'],
		'use_mailhide_comments' => $_POST['use_mailhide_comments'],
		'use_mailhide_rss' => $_POST['use_mailhide_rss'],
		'use_mailhide_rss_comments' => $_POST['use_mailhide_rss_comments'],
		're_bypass' => $_POST['re_bypass'],
		're_bypasslevel' => $_POST['re_bypasslevel'],
		'mailhide_pub' => trim($_POST['mailhide_pub']),
		'mailhide_priv' => trim($_POST['mailhide_priv']),
		'mh_bypass' => $_POST['mh_bypass'],
		'mh_bypasslevel' => $_POST['mh_bypasslevel'],
		're_theme' => $_POST['re_theme'],
		're_theme_reg' => $_POST['re_theme_reg'],
		're_lang' => $_POST['re_lang'],
		're_tabindex' => $_POST['re_tabindex'],
		're_comments' => $_POST['re_comments'],
		're_registration' => $_POST['re_registration'],
		're_xhtml' => $_POST['re_xhtml'],
      'mh_replace_link' => $_POST['mh_replace_link'],
      'mh_replace_title' => $_POST['mh_replace_title'],
      'error_blank' => $_POST['error_blank'],
      'error_incorrect' => $_POST['error_incorrect'],
		);
	// save updated options
	if ($wpmu == 1)
		update_site_option('recaptcha', $optionarray_update);
	else
		update_option('recaptcha', $optionarray_update);
}

	/* Get options */
	if ($wpmu == 1)
		$optionarray_def = get_site_option('recaptcha');
   else
		$optionarray_def = get_option('recaptcha');

/* =============================================================================
   reCAPTCHA Admin Page and Functions
   ============================================================================= */
   
/*
 * Display an HTML <select> listing the capability options for disabling security 
 * for registered users. 
 * @param string $select_name slug to use in <select> id and name
 * @param string $checked_value selected value for dropdown, slug form.
 * @return NULL
 */
 
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
	foreach ($capability_choices as $text => $capability) :
		if ($capability == $checked_value) $checked = ' selected="selected" ';
		echo '\t <option value="' . $capability . '"' . $checked . ">$text</option> \n";
		$checked = NULL;
	endforeach;
	echo "</select> \n";
 } // end recaptcha_dropdown_capabilities()
   
?>

<!-- ############################## BEGIN: ADMIN OPTIONS ################### -->
<div class="wrap">
	<h2>reCAPTCHA Options</h2>
	<h3>About reCAPTCHA</h3>
	<p>reCAPTCHA is a free, accessible CAPTCHA service that helps to digitize books while blocking spam on your blog.</p>
	
	<p>reCAPTCHA asks commenters to retype two words scanned from a book to prove that they are a human. This verifies that they are not a spambot while also correcting the automatic scans of old books. So you get less spam, and the world gets accurately digitized books. Everybody wins! For details, visit the <a href="http://recaptcha.net/">reCAPTCHA website</a>.</p>
   <p><strong>NOTE</strong>: If you are using some form of Cache plugin you will probably need to flush/clear your cache for changes to take effect.</p>
   
	<form name="form1" method="post" action="<?php echo $_SERVER['REDIRECT_SCRIPT_URI'] . '?page=' . plugin_basename(__FILE__); ?>&updated=true">
		<div class="submit">
			<input type="submit" name="submit" value="<?php _e('Update Options') ?> &raquo;" />
		</div>
	
	<!-- ****************** Operands ****************** -->
   <table class="form-table">
   <tr valign="top">
		<th scope="row">reCAPTCHA Keys</th>
		<td>
			reCAPTCHA requires an API key, consisting of a "public" and a "private" key. You can sign up for a <a href="<?php echo recaptcha_get_signup_url (recaptcha_wp_blog_domain (), 'wordpress');?>" target="0">free reCAPTCHA key</a>.
			<br />
			<p class="re-keys">
				<!-- reCAPTCHA public key -->
				<label class="which-key" for="recaptcha_opt_pubkey">Public Key:</label>
				<input name="recaptcha_opt_pubkey" id="recaptcha_opt_pubkey" size="40" value="<?php  echo $optionarray_def['pubkey']; ?>" />
				<br />
				<!-- reCAPTCHA private key -->
				<label class="which-key" for="recaptcha_opt_privkey">Private Key:</label>
				<input name="recaptcha_opt_privkey" id="recaptcha_opt_privkey" size="40" value="<?php  echo $optionarray_def['privkey']; ?>" />
			</p>
	    </td>
    </tr>
  	<tr valign="top">
		<th scope="row">Comment Options</th>
		<td>
			<!-- Show reCAPTCHA on the comment post -->
			<big><input type="checkbox" name="re_comments" id="re_comments" value="1" <?php if($optionarray_def['re_comments'] == true){echo 'checked="checked"';} ?> /> <label for="re_comments">Enable reCAPTCHA for comments.</label></big>
			<br />
			<!-- Don't show reCAPTCHA to admins -->
			<div class="theme-select">
			<input type="checkbox" id="re_bypass" name="re_bypass" <?php if($optionarray_def['re_bypass'] == true){echo 'checked="checked"';} ?>/>
			<label name="re_bypass" for="re_bypass">Hide reCAPTCHA for <strong>registered</strong> users who can:</label>
			<?php recaptcha_dropdown_capabilities('re_bypasslevel', $optionarray_def['re_bypasslevel']); // <select> of capabilities ?>
			</div>

			<!-- The theme selection -->
			<div class="theme-select">
			<label for="re_theme">Theme:</label>
			<select name="re_theme" id="re_theme">
			<option value="red" <?php if($optionarray_def['re_theme'] == 'red'){echo 'selected="selected"';} ?>>Red</option>
			<option value="white" <?php if($optionarray_def['re_theme'] == 'white'){echo 'selected="selected"';} ?>>White</option>
			<option value="blackglass" <?php if($optionarray_def['re_theme'] == 'blackglass'){echo 'selected="selected"';} ?>>Black Glass</option>
			<option value="clean" <?php if($optionarray_def['re_theme'] == 'clean'){echo 'selected="selected"';} ?>>Clean</option>
			</select>
			</div>
			<!-- Tab Index -->
			<label for="re_tabindex">Tab Index (<em>e.g. WP: <strong>5</strong>, WPMU: <strong>3</strong></em>):</label>
			<input name="re_tabindex" id="re_tabindex" size="5" value="<?php  echo $optionarray_def['re_tabindex']; ?>" />
			<br />
			<?php global $wpmu; if ($wpmu == 1 || $wpmu == 0) { ?>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">Registration Options</th>
		<td>
			<!-- Show reCAPTCHA on the registration page -->
			<big><input type="checkbox" name="re_registration" id="re_registration" value="1" <?php if($optionarray_def['re_registration'] == true){echo 'checked="checked"';} ?> /> <label for="re_registration">Enable reCAPTCHA on registration form.</label></big>
			<br />
			<!-- The theme selection -->
			<div class="theme-select">
				<label for="re_theme_reg">Theme:</label>
				<select name="re_theme_reg" id="re_theme_reg">
				<option value="red" <?php if($optionarray_def['re_theme_reg'] == 'red'){echo 'selected="selected"';} ?>>Red</option>
				<option value="white" <?php if($optionarray_def['re_theme_reg'] == 'white'){echo 'selected="selected"';} ?>>White</option>
				<option value="blackglass" <?php if($optionarray_def['re_theme_reg'] == 'blackglass'){echo 'selected="selected"';} ?>>Black Glass</option>
				<option value="clean" <?php if($optionarray_def['re_theme_reg'] == 'clean'){echo 'selected="selected"';} ?>>Clean</option>
				</select>
			</div>
			<?php } ?>
		</td>
	</tr>
   <tr valign="top">
      <th scope="row">Error Messages</th>
         <td>
            <p>The following are the messages to display when the user does not enter a CAPTCHA response or enters the incorrect CAPTCHA response.</p>
            <!-- Error Messages -->
            <p class="re-keys">
               <!-- Blank -->
      			<label class="which-key" for="error_blank">No response entered:</label>
      			<input name="error_blank" id="error_blank" size="80" value="<?php echo $optionarray_def['error_blank']; ?>" />
      			<br />
      			<!-- Incorrect -->
      			<label class="which-key" for="error_incorrect">Incorrect response entered:</label>
      			<input name="error_incorrect" id="error_incorrect" size="80" value="<?php echo $optionarray_def['error_incorrect']; ?>" />
      		</p>
         </td>
      </th>
   </tr>
	 <tr valign="top">
			<th scope="row">General Settings</th>
			<td>
				<!-- The language selection -->
				<div class="lang-select">
				<label for="re_lang">Language:</label>
				<select name="re_lang" id="re_lang">
				<option value="en" <?php if($optionarray_def['re_lang'] == 'en'){echo 'selected="selected"';} ?>>English</option>
				<option value="nl" <?php if($optionarray_def['re_lang'] == 'nl'){echo 'selected="selected"';} ?>>Dutch</option>
				<option value="fr" <?php if($optionarray_def['re_lang'] == 'fr'){echo 'selected="selected"';} ?>>French</option>
				<option value="de" <?php if($optionarray_def['re_lang'] == 'de'){echo 'selected="selected"';} ?>>German</option>
				<option value="pt" <?php if($optionarray_def['re_lang'] == 'pt'){echo 'selected="selected"';} ?>>Portuguese</option>
				<option value="ru" <?php if($optionarray_def['re_lang'] == 'ru'){echo 'selected="selected"';} ?>>Russian</option>
				<option value="es" <?php if($optionarray_def['re_lang'] == 'es'){echo 'selected="selected"';} ?>>Spanish</option>
				<option value="tr" <?php if($optionarray_def['re_lang'] == 'tr'){echo 'selected="selected"';} ?>>Turkish</option>
				</select>
				</label>
		    	</div>
		    	<!-- Whether or not to be XHTML 1.0 Strict compliant -->
				<input type="checkbox" name="re_xhtml" id="re_xhtml" value="1" <?php if($optionarray_def['re_xhtml'] == true){echo 'checked="checked"';} ?> /> <label for="re_xhtml">Be XHTML 1.0 Strict compliant. <strong>Note</strong>: Bad for users who don't have Javascript enabled in their browser (Majority do).</label>
				<br />
			</td>
		</tr>
	</table>
	
	<h3>About MailHide</h3>
	<p><a href="http://mailhide.recaptcha.net/" title="mailhide email obfuscation">MailHide</a> uses reCAPTCHA to protect email adresses displayed on your blog from being harvested for spam.</p>
	<p>Activating MailHide will automatically hide all emails in posts and comments from spam bots. For example, support@recaptcha.net would become supp<a href="http://mailhide.recaptcha.net/d?k=01a8k2oW96qNZ4JhiFx5zDRg==&amp;c=yifPREOOvfzA0o3dbnnwP8fy91UD8RL4SspHDIKHVRE=" onclick="window.open('http://mailhide.recaptcha.net/d?k=01a8k2oW96qNZ4JhiFx5zDRg==&amp;c=yifPREOOvfzA0o3dbnnwP8fy91UD8RL4SspHDIKHVRE=', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;" title="Reveal this e-mail address">...</a>@recaptcha.net. There are also options below for choosing the text to show for hidden emails.</p>
	<p>MailHide also requires a public and private key which you can generate using the <a href="http://mailhide.recaptcha.net/apikey">key generation service</a>.</p>
	<table class="form-table">
	<tr valign="top">
	<th scope="row">MailHide Keys</th>
	<td>
		<!-- MailHide Enabler -->
		<big>Enable MailHide email obfuscation for:</big><br />
		   <input type="checkbox" name="use_mailhide_posts" id="use_mailhide_posts" value="1" <?php if($optionarray_def['use_mailhide_posts'] == true){echo 'checked="checked"';} ?> /><label for="use_mailhide_posts">Posts/Pages</label><br />
		   <input type="checkbox" name="use_mailhide_comments" id="use_mailhide_comments" value="1" <?php if($optionarray_def['use_mailhide_comments'] == true){echo 'checked="checked"';} ?> /><label for="use_mailhide_comments">Comments</label><br />
		   <input type="checkbox" name="use_mailhide_rss" id="use_mailhide_rss" value="1" <?php if($optionarray_def['use_mailhide_rss'] == true){echo 'checked="checked"';} ?> /><label for="use_mailhide_rss">RSS Feed of Posts/Pages</label><br />
		   <input type="checkbox" name="use_mailhide_rss_comments" id="use_mailhide_rss_comments" value="1" <?php if($optionarray_def['use_mailhide_rss_comments'] == true){echo 'checked="checked"';} ?> /><label for="use_mailhide_rss_comments">RSS Feed of Comments</label><br />
		<!-- Public Key -->
		<p class="re-keys">
			<label class="which-key" for="mailhide_pub">Public Key:</label>
			<input name="mailhide_pub" id="mailhide_pub" size="40" value="<?php echo $optionarray_def['mailhide_pub']; ?>" />
			<br />
			<!-- Private Key -->
			<label class="which-key" for="mailhide_priv">Private Key:</label>
			<input name="mailhide_priv" id="mailhide_priv" size="40" value="<?php echo $optionarray_def['mailhide_priv']; ?>" />
		</p>
   </td>
   </tr>
   <tr valign="top">
   <th scope="row">Visibility Options</th>
   <td>
		<!-- Don't show mailhide to users who can... -->
		<div class="theme-select">
			<input type="checkbox" id="mh_bypass" name="mh_bypass" <?php if($optionarray_def['mh_bypass'] == true){echo 'checked="checked"';} ?>/>
			<label for="mh_bypass">Show full email adresses to <strong>registered</strong> users who can:</label>
			<?php recaptcha_dropdown_capabilities('mh_bypasslevel', $optionarray_def['mh_bypasslevel']); // <select> of capabilities ?>
		</div>
      <!-- Email Replacement Text -->
      <p class="re-keys">
         <p>The following allows you to show the replaced links differently. Usually, you get something like this, supp<a href="http://mailhide.recaptcha.net/d?k=01a8k2oW96qNZ4JhiFx5zDRg==&amp;c=yifPREOOvfzA0o3dbnnwP8fy91UD8RL4SspHDIKHVRE=" onclick="window.open('http://mailhide.recaptcha.net/d?k=01a8k2oW96qNZ4JhiFx5zDRg==&amp;c=yifPREOOvfzA0o3dbnnwP8fy91UD8RL4SspHDIKHVRE=', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;" title="Reveal this e-mail address">...</a>@recaptcha.net , where the email is broken up into two pieces and then a link with dots is placed in the middle. The <strong>Email Replacement Text</strong> value lets you choose what to name the link and then the <strong>Reveal Link Title</strong> value determines the text that is shown when the link is hovered over.</p>
         <p>For example, if the <strong>Email Replacement Text</strong> option is set to <strong>HIDDEN EMAIL</strong> and the <strong>Reveal Link Title</strong> option is set to <strong>Click here to reveal this address</strong>, then ALL emails will be hidden like this: <a href="http://mailhide.recaptcha.net/d?k=01a8k2oW96qNZ4JhiFx5zDRg==&amp;c=yifPREOOvfzA0o3dbnnwP8fy91UD8RL4SspHDIKHVRE=" onclick="window.open('http://mailhide.recaptcha.net/d?k=01a8k2oW96qNZ4JhiFx5zDRg==&amp;c=yifPREOOvfzA0o3dbnnwP8fy91UD8RL4SspHDIKHVRE=', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;" title="Click here to reveal this address">HIDDEN EMAIL</a></p>
         <p>If you want to maintain the default method of hiding emails then leave both boxes blank.</p>
         <label class="whch-key" for="mh_replace_link">EMail Replacement Text:</label>
         <input name="mh_replace_link" id="mh_replace_link" size="40" value="<?php echo $optionarray_def['mh_replace_link']; ?>" />
         <br />
         <label class="which-key" for="mh_replace_title">Reveal Link Title:</label>
         <input name="mh_replace_title" id="mh_replace_title" size="40" value="<?php echo $optionarray_def['mh_replace_title']; ?>" />
      </p>
   </td>
   </tr>
   <tr valign="top">
   <th scope="row">Other Information</th>
   <td>
		<!-- MailHide CSS -->
		<p>CSS: You can style the hidden emails and much more in the <strong>recaptcha.css</strong> stylesheet in wp-recaptcha's plugin folder.</p>
		<p>You can bypass email hiding for an address by enclosing it within <strong>[nohide][/nohide]</strong>, ex. [nohide]some@email.com[/nohide].</p>
	</td>
	</tr>
	</table>
	<div class="submit">
		<input type="submit" name="submit" value="<?php _e('Update Options') ?> &raquo;" />
	</div>

	</form>
   <p class="copyright">&copy; Copyright 2008&nbsp;&nbsp;<a href="http://recaptcha.net">reCAPTCHA</a></p>
</div> <!-- [wrap] -->
<!-- ############################## END: ADMIN OPTIONS ##################### -->

<?php
}

/* =============================================================================
   Apply the admin menu
============================================================================= */

add_action('admin_menu', 'recaptcha_wp_add_options_to_admin');

// If no reCAPTCHA API keys have been entered
if ( !($recaptcha_opt ['pubkey'] && $recaptcha_opt['privkey'] ) && !isset($_POST['submit']) ) {
   function recaptcha_warning() {
		global $wpmu;
		
		$path = plugin_basename(__FILE__);
		$top = 0;
		if ($wp_version <= 2.5)
		$top = 12.7;
		else
		$top = 7;
		echo "
		<div id='recaptcha-warning' class='updated fade-ff0000'><p><strong>reCAPTCHA is not active</strong> You must <a href='options-general.php?page=" . $path . "'>enter your reCAPTCHA API key</a> for it to work</p></div>
		<style type='text/css'>
		#adminmenu { margin-bottom: 5em; }
		#recaptcha-warning { position: absolute; top: {$top}em; }
		</style>
		";
   }
   
   if (($wpmu == 1 && is_site_admin()) || $wpmu != 1)
		add_action('admin_footer', 'recaptcha_warning');
   
   return;
}

$mailhide_enabled = ($recaptcha_opt['use_mailhide_posts'] || $recaptcha_opt['use_mailhide_comments'] || $recaptcha_opt['use_mailhide_rss'] || $recaptcha_opt['use_mailhide_rss_comments']);

// If the mcrypt PHP module isn't loaded then display an alert
if (($mailhide_enabled && !extension_loaded('mcrypt')) && !isset($_POST['submit'])) {
   function mcrypt_warning() {
		global $wpmu;
		
		$path = plugin_basename(__FILE__);
		$top = 0;
		if ($wp_version <= 2.5)
		$top = 12.7;
		else
		$top = 7;
		echo "
		<div id='recaptcha-warning' class='updated fade-ff0000'><p><strong>MailHide is not active</strong> You must have the <a href='http://us3.php.net/mcrypt'>mcrypt</a> module loaded for it to work</p></div>
		<style type='text/css'>
		#adminmenu { margin-bottom: 5em; }
		#recaptcha-warning { position: absolute; top: {$top}em; }
		</style>
		";
   }
   
   if (($wpmu == 1 && is_site_admin()) || $wpmu != 1)
		add_action('admin_footer', 'mcrypt_warning');
   
   return;
}

// If MailHide is enabled but no keys have been entered
if ($mailhide_enabled && !($recaptcha_opt['mailhide_pub'] && $recaptcha_opt['mailhide_pub']) && !isset($_POST['submit'])) {
	function mailhide_warning() {
		global $wpmu;
		
		$path = plugin_basename(__FILE__);
		$top = 0;
      
		if ($wp_version <= 2.5)
         $top = 12.7;
		else
         $top = 7;
         
		echo "
		<div id='recaptcha-warning' class='updated fade-ff0000'><p><strong>MailHide is not active</strong> You must <a href='options-general.php?page=" . $path . "'>enter your MailHide API keys</a> for it to work</p></div>
		<style type='text/css'>
		#adminmenu { margin-bottom: 5em; }
		#recaptcha-warning { position: absolute; top: {$top}em; }
		</style>
		";
	}
   
   if (($wpmu == 1 && is_site_admin()) || $wpmu != 1)
		add_action('admin_footer', 'mailhide_warning');
   
	return;
}

/* =============================================================================
   End Apply the admin menu
============================================================================= */
?>