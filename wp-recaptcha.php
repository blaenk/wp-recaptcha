<?php
/*
Plugin Name: WP-reCAPTCHA
Plugin URI: http://www.blaenkdenum.com/wp-recaptcha/
Description: Integrates reCAPTCHA anti-spam solutions with wordpress
Version: 2.8.1
Author: Jorge Peña, Ben Maurer, and Mike Crawford
Email: support@recaptcha.net
Author URI: http://www.blaenkdenum.com
*/

require_once (dirname(__FILE__) . '/recaptchalib.php');
$recaptcha_opt = get_option('recaptcha'); // get the options from the database

#doesn't need to be secret, just shouldn't be used by any other code.
define ("RECAPTCHA_WP_HASH_SALT", "b7e0638d85f5d7f3694f68e944136d62");

/* =============================================================================
   CSS - This links the pages to the stylesheet to be properly styled
   ============================================================================= */

function re_css() {
   global $recaptcha_opt;
   
   $path = '/wp-content/plugins/wp-recaptcha/recaptcha.css';
   
   if ($recaptcha_opt['re_wpmu'])
      $path = '/mu-plugins/wp-recaptcha/recaptcha.css';
   
   echo '<link rel="stylesheet" type="text/css" href="' . get_bloginfo('siteurl') . $path . '" />';
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
      <style>
         #login {
            width: {$width}px !important;
         }
         
         #login a {
            text-align: center;
         }
         
         #nav {
            text-align: center;
         }
         
      </style>
REGISTRATION;
   }
}

add_action('wp_head', 're_css'); // include the stylesheet in typical pages to style hidden emails
add_action('admin_head', 're_css'); // include stylesheet to style options page
add_action('login_head', 're_css'); // include stylesheet to style reCAPTCHA on registration page
add_action('login_head', 'registration_css'); // include the login div styling, embedded

/* =============================================================================
   End CSS
   ============================================================================= */

// If the plugin is deactivated, delete the preferences
function delete_preferences() {
   delete_option('recaptcha');
}

register_deactivation_hook(__FILE__, 'delete_preferences');

/* =============================================================================
   reCAPTCHA on Registration Form - Thanks to Ben C.'s recapture plugin
   ============================================================================= */
   
// Display the reCAPTCHA on the registration form
function display_recaptcha() {
	global $recaptcha_opt;
   
   if ($recaptcha_opt['re_registration']) {
      $format = <<<END
      <script type='text/javascript'>
         var RecaptchaOptions = { theme : '{$recaptcha_opt['re_theme_reg']}', lang : '{$recaptcha_opt['re_lang']}' , tabindex : 30 };
      </script>
END;
      if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
         $use_ssl = true;
      else
         $use_ssl = false;
      
      echo $format . recaptcha_get_html($recaptcha_opt['pubkey'], $error, $use_ssl, $recaptcha_opt['re_xhtml']);
   }
}

// Hook the display_recaptcha function into WordPress
if (!$recaptcha_opt['re_wpmu'])
   add_action('register_form', 'display_recaptcha');
else
   add_action('signup_extra_fields', 'display_recaptcha');

// Check the captcha
function check_recaptcha() {
	global $recaptcha_opt, $errors;
	
   if (empty($_POST['recaptcha_response_field']))
      $errors['blank_captcha'] = 'Please complete the reCAPTCHA.';
   
   else {
   	$response = recaptcha_check_answer($recaptcha_opt['privkey'],
         $_SERVER['REMOTE_ADDR'],
         $_POST['recaptcha_challenge_field'],
         $_POST['recaptcha_response_field']);

   	if (!$response->is_valid)
         if ($response->error == 'incorrect-captcha-sol')
            $errors['captcha_wrong'] = 'That reCAPTCHA was incorrect.';
   }
}

// Check the captcha
function check_recaptcha_new($errors) {
	global $recaptcha_opt;
	
   if (empty($_POST['recaptcha_response_field']) || $_POST['recaptcha_response_field'] == '') {
      $errors->add('blank_captcha', 'Please complete the reCAPTCHA.');
      return $errors;
   }
   
	$response = recaptcha_check_answer($recaptcha_opt['privkey'],
                  $_SERVER['REMOTE_ADDR'],
                  $_POST['recaptcha_challenge_field'],
                  $_POST['recaptcha_response_field'] );

	if (!$response->is_valid)
		if ($response->error == 'incorrect-captcha-sol')
			$errors->add('captcha_wrong', 'That reCAPTCHA was incorrect.');
   
   return $errors;
}

function check_recaptcha_wpmu($content) {
   global $_POST, $recaptcha_opt;
   
   if (empty($_POST['recaptcha_response_field']) || $_POST['recaptcha_response_field'] == '') {
      $content['errors']->add('blank_captcha', 'Please complete the reCAPTCHA.');
      return $content;
   }
   
	$response = recaptcha_check_answer($recaptcha_opt['privkey'],
                  $_SERVER['REMOTE_ADDR'],
                  $_POST['recaptcha_challenge_field'],
                  $_POST['recaptcha_response_field'] );

	if (!$response->is_valid)
		if ($response->error == 'incorrect-captcha-sol')
			$content['errors']->add('captcha_wrong', 'That reCAPTCHA was incorrect.');
   
   return $errors;
}

if ($recaptcha_opt['re_registration']) {
   if ($recaptcha_opt['re_wpmu'])
      add_filter('wpmu_validate_user_signup', 'check_recaptcha_wpmu');
   else {
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
         'use_mailhide' => '0', // whether or not to use mailhide
         're_noadmins' => '0', // display reCAPTCHA for admins?
         'mh_noadmins' => '0', // hide emails from admins?
         'mailhide_pub' => '', // mailhide public key
         'mailhide_priv' => '', // mailhide private key
         're_theme' => 'red', // the default theme for reCAPTCHA on the comment post
         're_theme_reg' => 'red', // the default theme for reCAPTCHA on the registration form
         're_lang' => 'en', // the default language for reCAPTCHA
         're_tabindex' => '5', // the default tabindex for reCAPTCHA
         're_comments' => '1', // whether or not to show reCAPTCHA on the comment post
         're_registration' => '1', // whether or not to show reCAPTCHA on the registratoin page
         're_xhtml' => '0', // whether or not to be XHTML 1.0 Strict compliant
         're_wpmu' => '0', // whether or not the user is in a forced WPMU environment
);

// install the defaults
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
   
   // we're not hiding emails from admins
   if ($recaptcha_opt['mh_noadmins'] && current_user_can('level_10'))
      return $content;
   
   // Regular Expressions thanks to diabolic from EFNet #regex
   
   // match hyperlinks with emails
   $regex = '%(?<!\\[nohide\\])<a[^>]*href="(?:mailto:)?([^@"]+@[^@"]+)"[^>]*>(.+?)<\\/a>(?!\\[/nohide\\])%i';
   $content = preg_replace_callback($regex, "mh_replace_hyperlink", $content);
   
   // match emails
   $regex = '%\\b([\\w.+-]+@[a-z\\d.-]+\\.[a-z]{2,6})\\b(?!\\s*\\[\\/nohide\\]|(?:(?!<a[^>]*>).)*<\\/a>)%iU';
   $content = preg_replace_callback($regex, "mh_replace", $content);
   
   // remove the nohides
   $content = preg_replace('/\[\/?nohide\]/i','',$content);
   return $content;
}

// replace the hyperlinked emails i.e. <a href="haha@lol.com">this</a> or <a href="mailto:haha@lol.com">that</a>
function mh_replace_hyperlink($matches) {
   global $recaptcha_opt;
   
   // get the url, the part inside the href. this is the email of course
   $url = recaptcha_mailhide_url($recaptcha_opt['mailhide_pub'], $recaptcha_opt['mailhide_priv'], $matches[1]);
   
   // construct a new hyperlink with the url hidden but the link text the same
   $html = "<a href='" . $url . "' onclick=\"window.open('" . htmlentities ($url, ENT_QUOTES) . "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\">" . $matches[2] . "</a>";
   
   // style it
   $html = '<span class="mh-hyperlinked">' . $html . "</span>";
   
   return $html;
}

// replace the plain text emails i.e. haha@lol.com
function mh_replace($matches) {
   global $recaptcha_opt;
   
   // fine plain text emails and hide them
   $html = recaptcha_mailhide_html($recaptcha_opt['mailhide_pub'], $recaptcha_opt['mailhide_priv'], $matches[1]);
   
   // style it
   $html = '<span class="mh-plaintext">' . $html . "</span>";
   
   return $html;
}

// add the filters only if mcrypt is loaded
if ($recaptcha_opt['use_mailhide'] && extension_loaded('mcrypt')) {
   add_filter('the_content', 'mh_insert_email'); // For posts/pages
   add_filter('get_comment_text', 'mh_insert_email'); // For comments
   add_filter('the_content_rss', 'mh_insert_email'); // For RSS
   add_filter('comment_text_rss', 'mh_insert_email'); // For RSS Comments
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

   // don't show reCAPTCHA to admins
   if (($recaptcha_opt['re_noadmins'] && current_user_can('level_10')) || !$recaptcha_opt['re_comments'])
      return;
   
   else {
      // Did the user fail to match the CAPTCHA? If so, let them know
      if ($_GET['rerror'] == 'incorrect-captcha-sol')
         echo "<p class=\"recaptcha-error\">Incorrect CAPTCHA. Please try again.</p>";
   
      //modify the comment form for the reCAPTCHA widget 
      $recaptcha_js_opts = <<<OPTS
         <script type='text/javascript'>
            var RecaptchaOptions = { theme : '{$recaptcha_opt['re_theme']}', lang : '{$recaptcha_opt['re_lang']}' , tabindex : {$recaptcha_opt['re_tabindex']} };
         </script>
OPTS;
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
   if ((current_user_can('level_10') && $recaptcha_opt['re_noadmins']) || !$recaptcha_opt['re_comments'])
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
		echo "<script type='text/javascript'>
         var _recaptcha_wordpress_savedcomment =  '" . preg_replace('/([\<\>\/\(\)\+\;\'\"])/e', '\'%\'.dechex(ord(\'$1\'))', $comment->comment_content) ."';
			_recaptcha_wordpress_savedcomment = unescape(_recaptcha_wordpress_savedcomment);
		      </script>";
		wp_delete_comment($comment->comment_ID);
	}
}

function recaptcha_wp_blog_domain ()
{
	$uri = parse_url(get_settings('siteurl'));
	return $uri['host'];
}

add_filter('wp_head', 'recaptcha_wp_saved_comment',0);
add_filter('preprocess_comment', 'recaptcha_wp_check_comment',0);
add_filter('comment_post_redirect', 'recaptcha_wp_relative_redirect',0,2); // STUB

function recaptcha_wp_add_options_to_admin() {
   if (function_exists('is_site_admin')) { // && $recaptcha_opt['re_wpmu'] ?
      if (is_site_admin()) {
         add_submenu_page('wpmu-admin.php', 'reCAPTCHA', 'reCAPTCHA', 'manage_options', __FILE__, 'recaptcha_wp_options_subpanel');
         add_options_page('reCAPTCHA', 'reCAPTCHA', 'manage_options', __FILE__, 'recaptcha_wp_options_subpanel');
      }
   }
   else {
      add_options_page('reCAPTCHA', 'reCAPTCHA', 'manage_options', __FILE__, 'recaptcha_wp_options_subpanel');
   }
}

function recaptcha_wp_options_subpanel() {

	$optionarray_def = array(
				 'pubkey'	=> '',
				 'privkey' 	=> '',
             'use_mailhide' => '',
             're_noadmins' => '',
             'mh_noadmins' => '',
             'mailhide_pub' => '',
             'mailhide_priv' => '',
             're_theme' => 'red',
             're_theme_reg' => 'red',
             're_lang' => 'en',
             're_tabindex' => '5',
             're_comments' => '1',
             're_registration' => '1',
             're_xhtml' => '0',
             're_wpmu' => '0',
				 );

	add_option('recaptcha', $optionarray_def, 'reCAPTCHA Options');

	/* Check form submission and update options if no error occurred */
	if (isset($_POST['submit'])) {
		$optionarray_update = array (
			'pubkey'	=> $_POST['recaptcha_opt_pubkey'],
			'privkey'	=> $_POST['recaptcha_opt_privkey'],
         'use_mailhide' => $_POST['use_mailhide'],
         're_noadmins' => $_POST['re_noadmins'],
         'mh_noadmins' => $_POST['mh_noadmins'],
         'mailhide_pub' => $_POST['mailhide_pub'],
         'mailhide_priv' => $_POST['mailhide_priv'],
         're_theme' => $_POST['re_theme'],
         're_theme_reg' => $_POST['re_theme_reg'],
         're_lang' => $_POST['re_lang'],
         're_tabindex' => $_POST['re_tabindex'],
         're_comments' => $_POST['re_comments'],
         're_registration' => $_POST['re_registration'],
         're_xhtml' => $_POST['re_xhtml'],
         're_wpmu' => $_POST['re_wpmu'],
		);
		update_option('recaptcha', $optionarray_update);
	}

	/* Get options */
	$optionarray_def = get_option('recaptcha');

/* =============================================================================
   reCAPTCHA
   ============================================================================= */
?>

<!-- ############################## BEGIN: ADMIN OPTIONS ################### -->
<div class="wrap">


	<h2>reCAPTCHA Options</h2>
	<p>reCAPTCHA asks commenters to read two words from a book. One of these words proves
	   that they are a human, not a computer. The other word is a word that a computer couldn't read.
	   Because the user is known to be a human, the reading of that word is probably correct. So you don't
	   get comment spam, and the world gets books digitized. Everybody wins! For details, visit
	   the <a href="http://recaptcha.net/">reCAPTCHA website</a>.</p>
   <p><strong>NOTE</strong>: If you are using some form of Cache plugin you will probably need to
      flush/clear your cache for changes to take effect.</p>

	<form class="recaptcha-form" name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?page=' . plugin_basename(__FILE__); ?>&updated=true">


	<!-- ****************** Operands ****************** -->
   <table class="recaptcha-options">
   <tr>
   <td>
	<fieldset class="options">
		<legend>reCAPTCHA Key</legend>
		<p>reCAPTCHA requires an API key, consisting of a "public" and a "private" key. You can sign up for a <a href="<?php echo recaptcha_get_signup_url (recaptcha_wp_blog_domain (), 'wordpress');?>" target="0">free reCAPTCHA key</a>.</p>
      <!-- reCAPTCHA public key -->
		<label class="which-key" for="recaptcha_opt_pubkey">Public Key:</label>
		<br />
		<input name="recaptcha_opt_pubkey" id="recaptcha_opt_pubkey" size="40" value="<?php  echo $optionarray_def['pubkey']; ?>" />
      <!-- reCAPTCHA private key -->
		<label class="which-key" for="recaptcha_opt_privkey">Private Key:</label>
		<br />
		<input name="recaptcha_opt_privkey" id="recaptcha_opt_privkey" size="40" value="<?php  echo $optionarray_def['privkey']; ?>" />
      <br /><br />
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
      <br />
      <!-- Whether or not to be XHTML 1.0 Strict compliant -->
      <input type="checkbox" name="re_xhtml" id="re_xhtml" value="1" <?php if($optionarray_def['re_xhtml'] == true){echo 'checked="checked"';} ?> /> <label for="re_xhtml">Be XHTML 1.0 Strict compliant. <strong>Note</strong>: Bad for users who don't have Javascript enabled in their browser (Majority do).</label><br /><br />
      <!-- Whether or not the plugin is in a WPMU environment -->
      <input type="checkbox" name="re_wpmu" id="re_wpmu" value="1" <?php if ($optionarray_def['re_wpmu'] == true){echo 'checked="checked"';} ?> /> <label for="re_wpmu">Enable site-wide activation in a WPMU environment.</label>
      <hr />
      <!-- Show reCAPTCHA on the comment post -->
      <input type="checkbox" name="re_comments" id="re_comments" value="1" <?php if($optionarray_def['re_comments'] == true){echo 'checked="checked"';} ?> /> <label for="re_comments">Use reCAPTCHA for comment spam protection.</label>
      <br /><br />
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
      <br />
      <!-- Tab Index -->
      <label for="re_tabindex">Tab Index:</label>
      <input name="re_tabindex" id="re_tabindex" size="5" value="<?php  echo $optionarray_def['re_tabindex']; ?>" />
      <br /><br />
      <!-- Don't show reCAPTCHA to admins -->
      <input type="checkbox" name="re_noadmins" id="re_noadmins" value="1" <?php if($optionarray_def['re_noadmins'] == true){echo 'checked="checked"';} ?> /> <label for="re_noadmins">Admins don't have to do the CAPTCHA.</label>
      <hr />
      <!-- Show reCAPTCHA on the registration page -->
      <input type="checkbox" name="re_registration" id="re_registration" value="1" <?php if($optionarray_def['re_registration'] == true){echo 'checked="checked"';} ?> /> <label for="re_registration">Use reCAPTCHA for registration spam protection.</label>
      <br /><br />
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
   </fieldset>
   </td>
   <td>
   <fieldset class="options">
      <legend>MailHide Options</legend>
      <p>MailHide hides email addresses like so: supp<a href="http://mailhide.recaptcha.net/d?k=01a8k2oW96qNZ4JhiFx5zDRg==&amp;c=yifPREOOvfzA0o3dbnnwP8fy91UD8RL4SspHDIKHVRE=" onclick="window.open('http://mailhide.recaptcha.net/d?k=01a8k2oW96qNZ4JhiFx5zDRg==&amp;c=yifPREOOvfzA0o3dbnnwP8fy91UD8RL4SspHDIKHVRE=', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;" title="Reveal this e-mail address">...</a>@recaptcha.net. MailHide also requires a public and private key which you can generate using the <a href="http://mailhide.recaptcha.net/apikey">key generation service</a>.</p>
      <!-- MailHide Enabler -->
      <input type="checkbox" name="use_mailhide" id="use_mailhide" value="1" <?php if($optionarray_def['use_mailhide'] == true){echo 'checked="checked"';} ?> /> <label for="use_mailhide">Enable MailHide</label><br /><br />
      
      <!-- Public -->
      <label class="which-key" for="mailhide_pub">Public Key:</label>
		<br />
		<input name="mailhide_pub" id="mailhide_pub" size="40" value="<?php  echo $optionarray_def['mailhide_pub']; ?>" />
      
      <!-- Private -->
		<label class="which-key" for="mailhide_priv">Private Key:</label>
		<br />
		<input name="mailhide_priv" id="mailhide_priv" size="40" value="<?php  echo $optionarray_def['mailhide_priv']; ?>" />
      
      <!-- MailHide CSS -->
      <p>You can style the hidden emails with the <strong>emailrecaptcha</strong> CSS class in the <em>recaptcha.css</em> stylesheet in recaptcha's plugin folder.</p>
      <p>You can bypass email hiding by enclosing the email with <strong>[nohide][/nohide]</strong> tags.</p>
      
      <!-- Don't hide emails from admins -->
      <input type="checkbox" name="mh_noadmins" id="mh_noadmins" value="1" <?php if($optionarray_def['mh_noadmins'] == true){echo 'checked="checked"';} ?> /> <label for="mh_noadmins">Don't hide emails from admins.</label>
	</fieldset>
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
   add_action('admin_footer', 'recaptcha_warning');
   return;
}

// If the mcrypt PHP module isn't loaded then display an alert
if (($recaptcha_opt['use_mailhide'] && !extension_loaded('mcrypt')) && !isset($_POST['submit'])) {
   function mcrypt_warning() {
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
   add_action('admin_footer', 'mcrypt_warning');
   return;
}

// If MailHide is enabled but no keys have been entered
if ($recaptcha_opt['use_mailhide'] &&
    !($recaptcha_opt['mailhide_pub'] && $recaptcha_opt['mailhide_pub']) &&
    !isset($_POST['submit'])) {
	function mailhide_warning() {
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
	add_action('admin_footer', 'mailhide_warning');
	return;
}

/* =============================================================================
   End Apply the admin menu
============================================================================= */
?>
