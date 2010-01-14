<?php

$mailhide_option_defaults = array (
   // keys
   'public_key' => '', // mailhide public key
   'private_key' => '', // mailhide private key

   // placement
   'use_in_posts' => false, // mailhide for posts/pages
   'use_in_comments' => false, // use mailhide for comments
   'use_in_rss' => false, // use mailhide for the rss feed of the posts/pages
   'use_in_rss_comments' => false, // use mailhide for the rss comments

   // bypass levels
   'bypass_for_registered_users' => '', // whether to sometimes skip the MailHide filter for registered users
   'minimum_bypass_level' => '', // who can see full emails normally (should be a valid WordPress capability slug)

   // styling
   'replace_link_with' => '', // name the link something else
   'replace_title_with' => '', // title of the link
);

// mailhide
function insert_email() {
    // set the minimum capability needed to skip the MailHide if there is one
    if ($this->options['mh_bypass'] && $this->options['mh_bypasslevel'])
        $needed_capability = $this->options['mh_bypasslevel'];

	// skip the MailHide display if the minimum capability is met
	if (($needed_capability && current_user_can($needed_capability)) || !$this->options['re_comments']) {
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

if (!class_exists('MailHide')) {
    class MailHide {
        // member variables
        private $options;
        private $wordpress_mu;

        function __construct() {
            // get the site options
            $this->retrieve_options();
            
            // require the recaptcha library
            $this->require_library();
            
            // register the hooks
            $this->register_actions();
            $this->register_filters();
        }
        
        function register_actions() {
            // load the plugin's textdomain for localization
            add_action('init', 'load_textdomain');
            
            // options
            register_activation_hook($this->path_to_plugin(), array(&$this, 'register_default_options')); // this way it only happens once, when the plugin is activated
            add_action('admin_init', array(&$this, 'register_settings_group'));
        }
        
        function register_filters() {
            
        }
        
        function load_textdomain() {
            load_plugin_textdomain('recaptcha', false, 'locales');
        }
        
        function register_default_options() {
            $option_defaults = array();
            
            // migrate the settings from the previous version of the plugin if they exist
            $old_options = get_option('recaptcha');
            if ($old_options) {
                // keys
                $option_defaults['public_key'] => $old_options['mailhide_pub'], // mailhide public key
                $option_defaults['private_key'] => $old_options['mailhide_priv'], // mailhide private key

                // placement
                $option_defaults['use_in_posts'] => $old_options['use_mailhide_posts'], // mailhide for posts/pages
                $option_defaults['use_in_comments'] => $old_options['use_mailhide_comments'], // use mailhide for comments
                $option_defaults['use_in_rss'] => $old_options['use_mailhide_rss'], // use mailhide for the rss feed of the posts/pages
                $option_defaults['use_in_rss_comments'] => $old_options['use_mailhide_rss_comments'], // use mailhide for the rss comments

                // bypass levels
                $option_defaults['bypass_for_registered_users'] => $old_options['mh_bypass'], // whether to sometimes skip the MailHide filter for registered users
                $option_defaults['minimum_bypass_level'] => $old_options['my_bypasslevel'], // who can see full emails normally (should be a valid WordPress capability slug)

                // styling
                $option_defaults['replace_link_with'] => $old_options['mh_replace_link'], // name the link something else
                $option_defaults['replace_title_with'] => $old_options['mh_replace_title'], // title of the link
                
                // now remove the option from the wp_options table because it's no longer needed
                // at least someone cares to keep the database nice and tidy, right?
                delete_option('recaptcha');
            }
            
            else {
                // keys
                $option_defaults['public_key'] => '', // mailhide public key
                $option_defaults['private_key'] => '', // mailhide private key

                // placement
                $option_defaults['use_in_posts'] => 0, // mailhide for posts/pages
                $option_defaults['use_in_comments'] => 0, // use mailhide for comments
                $option_defaults['use_in_rss'] => 0, // use mailhide for the rss feed of the posts/pages
                $option_defaults['use_in_rss_comments'] => 0, // use mailhide for the rss comments

                // bypass levels
                $option_defaults['bypass_for_registered_users'] => 0, // whether to sometimes skip the MailHide filter for registered users
                $option_defaults['minimum_bypass_level'] => 'read', // who can see full emails normally (should be a valid WordPress capability slug)

                // styling
                $option_defaults['replace_link_with'] => '', // name the link something else
                $option_defaults['replace_title_with'] => '', // title of the link
            }
            
            // add the option based on what environment we're in
            if ($this->wordpress_mu)
                add_site_option('recaptcha_options', $option_defaults);
            else
                add_option('recaptcha_options', $option_defaults);
        }
    } // end class declaration
} // end class exists clause

?>