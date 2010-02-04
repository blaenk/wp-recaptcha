<?php

if (!class_exists('MailHide')) {
    class MailHide {
        // member variables
        private $options;
        private $wordpress_mu;
        private $mcrypt_loaded;
        
        function MailHide() {
            $this->__construct();
        }

        function __construct() {
            // verify mcrypt is loaded
            $this->verify_mcrypt();
            
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
            // load the plugin's textdomain for localization
            add_action('init', array(&$this, 'load_textdomain'));
            
            // options
            register_activation_hook($this->path_to_plugin(), array(&$this, 'register_default_options')); // this way it only happens once, when the plugin is activated
            add_action('admin_init', array(&$this, 'register_settings_group'));
            add_action('admin_init', array(&$this, 'settings_section'));
            
            // admin notice
            add_action('admin_notices', array(&$this, 'missing_mcrypt_notice'));
            add_action('admin_notices', array(&$this, 'missing_keys_notice'));
        }
        
        function register_filters() {
            // add the filters only if mcrypt is loaded
            if ($this->mcrypt_loaded) {
               if ($this->options['use_in_posts'])
                  add_filter('the_content', array(&$this, 'mailhide_emails'));
               if ($this->options['use_in_comments'])
                  add_filter('get_comment_text', array(&$this, 'mailhide_emails'));
               // todo: this seems like it doesn't work: http://codex.wordpress.org/Plugin_API/Filter_Reference/the_content_rss
               //   instead check for is_feed() on 'the_content' filter
               //   the_excerpt_rss filter works fine
               // concern: it seems like the feeds still show the email encoded in CDATA
               if ($this->options['use_in_rss']) {
                  add_filter('the_content_rss', array(&$this, 'mailhide_emails'));
                  add_filter('the_excerpt_rss', array(&$this, 'mailhide_emails')); // this one is sometimes used instead
               }
               // todo: atom requires the html to be escaped, rss does not. do so accordingly in the preg_replace_callbacks
               // todo: also be sure to escape replace_link_with
               //       - use htmlentities($var, ENT_QUOTES); for escaping?
               if ($this->options['use_in_rss_comments'])
                  add_filter('comment_text_rss', array(&$this, 'mailhide_emails'));
            }
        }
        
        function mailhide_enabled() {
            return ($this->options['use_in_posts'] || $this->options['use_in_comments'] || $this->options['use_in_rss'] || $this->options['use_in_rss_comments']);
        }
        
        function keys_missing() {
            return (empty($this->options['public_key']) || empty($this->options['private_key']));
        }
        
        function create_error_notice($message, $anchor = '') {
            $options_url = admin_url('options-general.php?page=wp-recaptcha/recaptcha.php') . $anchor;
            $error_message = sprintf(__($message . ' <a href="%s" title="WP-reCAPTCHA Options">Fix this</a>', 'recaptcha'), $options_url);
            
            echo '<div class="error"><p><strong>' . $error_message . '</strong></p></div>';
        }
        
        function missing_mcrypt_notice() {
            if ($this->mailhide_enabled() && !$this->mcrypt_loaded) {
                $this->create_error_notice('You enabled MailHide but the mcrypt PHP extension does not seem to be loaded.', '#mailhide');
            }
        }
        
        // todo: make a check in mailhide_settings partial so that if the keys are missing, the appropriate box is highlighted with #FFFFE0 bg-color 1px solid #E6DB55 border
        function missing_keys_notice() {
            if ($this->mailhide_enabled() && $this->keys_missing()) {
                $this->create_error_notice('You enabled MailHide, but some of the MailHide API Keys seem to be missing.', '#mailhide');
            }
        }
        
        function settings_section() {
            add_settings_section('mailhide_options', '', array(&$this, 'show_settings_section'), 'recaptcha_options_page');
        }
        
        function show_settings_section() {
            include('mailhide_settings.php');
        }
        
        function verify_mcrypt() {
            $this->mcrypt_loaded = extension_loaded('mcrypt');
        }
        
        // some utility methods for path-finding
        function plugins_directory() {
            if ($this->wordpress_mu)
                return WP_CONTENT_DIR . '/mu-plugins';
            else
                return WP_CONTENT_DIR . '/plugins';
        }
        
        function path_to_plugin_directory() {
            return $this->plugins_directory() . '/wp-recaptcha/';
        }
        
        function path_to_plugin() {
            if ($this->wordpress_mu)
                return $this->plugins_directory() . '/wp-recaptcha.php';
            else
                return $this->path_to_plugin_directory() . '/wp-recaptcha.php';
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
        
        // require the recaptcha library
        function require_library() {
            require_once($this->path_to_plugin_directory() . '/recaptchalib.php');
        }
        
        function load_textdomain() {
            load_plugin_textdomain('recaptcha', false, 'languages');
        }
        
        function register_default_options() {
            $option_defaults = array();
            
            // migrate the settings from the previous version of the plugin if they exist
            $old_options = get_option('recaptcha');
            if ($old_options) {
                // keys
                $option_defaults['public_key'] = $old_options['mailhide_pub']; // mailhide public key
                $option_defaults['private_key'] = $old_options['mailhide_priv']; // mailhide private key

                // placement
                $option_defaults['use_in_posts'] = $old_options['use_mailhide_posts']; // mailhide for posts/pages
                $option_defaults['use_in_comments'] = $old_options['use_mailhide_comments']; // use mailhide for comments
                $option_defaults['use_in_rss'] = $old_options['use_mailhide_rss']; // use mailhide for the rss feed of the posts/pages
                $option_defaults['use_in_rss_comments'] = $old_options['use_mailhide_rss_comments']; // use mailhide for the rss comments

                // bypass levels
                $option_defaults['bypass_for_registered_users'] = $old_options['mh_bypass']; // whether to sometimes skip the MailHide filter for registered users
                $option_defaults['minimum_bypass_level'] = $old_options['my_bypasslevel']; // who can see full emails normally (should be a valid WordPress capability slug)

                // styling
                $option_defaults['replace_link_with'] = $old_options['mh_replace_link']; // name the link something else
                $option_defaults['replace_title_with'] = $old_options['mh_replace_title']; // title of the link
                
                // now remove the option from the wp_options table because it's no longer needed
                // at least someone cares to keep the database nice and tidy, right?
                delete_option('recaptcha');
            }
            
            else {
                // keys
                $option_defaults['public_key'] = ''; // mailhide public key
                $option_defaults['private_key'] = ''; // mailhide private key

                // placement
                $option_defaults['use_in_posts'] = 0; // mailhide for posts/pages
                $option_defaults['use_in_comments'] = 0; // use mailhide for comments
                $option_defaults['use_in_rss'] = 0; // use mailhide for the rss feed of the posts/pages
                $option_defaults['use_in_rss_comments'] = 0; // use mailhide for the rss comments

                // bypass levels
                $option_defaults['bypass_for_registered_users'] = 0; // whether to sometimes skip the MailHide filter for registered users
                $option_defaults['minimum_bypass_level'] = 'read'; // who can see full emails normally (should be a valid WordPress capability slug)

                // styling
                $option_defaults['replace_link_with'] = ''; // name the link something else
                $option_defaults['replace_title_with'] = ''; // title of the link
            }
            
            // add the option based on what environment we're in
            if ($this->wordpress_mu)
                add_site_option('mailhide_options', $option_defaults);
            else
                add_option('mailhide_options', $option_defaults);
        }
        
        function retrieve_options() {
            if ($this->wordpress_mu)
                $this->options = get_site_option('mailhide_options');

            else
                $this->options = get_option('mailhide_options');
        }
        
        function register_settings_group() {
            register_setting('mailhide_options_group', 'mailhide_options', array(&$this, 'validate_options'));
        }
        
        function validate_dropdown($array, $key, $value) {
            // make sure that the capability that was supplied is a valid capability from the drop-down list
            if (in_array($value, $array))
                return $value;
            else // if not, load the old value
                return $this->options[$key];
        }
        
        function validate_options($input) {
            // keys
            $validated['public_key'] = trim($input['public_key']); // mailhide public key
            $validated['private_key'] = trim($input['private_key']); // mailhide private key

            // placement
            $validated['use_in_posts'] = ($input['use_in_posts'] == 1 ? 1 : 0); // mailhide for posts/pages
            $validated['use_in_comments'] = ($input['use_in_comments'] == 1 ? 1 : 0); // use mailhide for comments
            $validated['use_in_rss'] = ($input['use_in_rss'] == 1 ? 1 : 0); // use mailhide for the rss feed of the posts/pages
            $validated['use_in_rss_comments'] = ($input['use_in_rss_comments'] == 1 ? 1 : 0); // use mailhide for the rss comments

            $capabilities = array ('read', 'edit_posts', 'publish_posts', 'moderate_comments', 'level_10');

            // bypass levels
            $validated['bypass_for_registered_users'] = ($input['bypass_for_registered_users'] == 1 ? 1: 0); // whether to sometimes skip the MailHide filter for registered users
            $validated['minimum_bypass_level'] = $this->validate_dropdown($capabilities, 'minimum_bypass_level', $input['minimum_bypass_level']); // who can see full emails normally (should be a valid WordPress capability slug)

            // styling
            $validated['replace_link_with'] = $input['replace_link_with']; // name the link something else
            $validated['replace_title_with'] = $input['replace_title_with']; // title of the link
            
            return $validated;
        }
        
        function build_dropdown($name, $keyvalue, $checked_value) {
            echo '<select name="' . $name . '" id="' . $name . '">' . "\n";
            
            foreach ($keyvalue as $key => $value) {
                if ($value == $checked_value)
                    $checked = ' selected="selected" ';
                
                echo '\t <option value="' . $value . '"' . $checked . ">$key</option> \n";
                $checked = NULL;
            }
            
            echo "</select> \n";
        }
        
        function capabilities_dropdown() {
            // define choices: Display text => permission slug
            $capabilities = array (
                __('all registered users', 'recaptcha') => 'read',
                __('edit posts', 'recaptcha') => 'edit_posts',
                __('publish posts', 'recaptcha') => 'publish_posts',
                __('moderate comments', 'recaptcha') => 'moderate_comments',
                __('administer site', 'recaptcha') => 'level_10'
            );
            
            $this->build_dropdown('mailhide_options[minimum_bypass_level]', $capabilities, $this->options['minimum_bypass_level']);
        }
        
        function mailhide_emails($content) {
            // set the minimum capability needed to skip the MailHide if there is one
            if ($this->options['bypass_for_registered_users'] && $this->options['minimum_bypass_level'])
                $needed_capability = $this->options['minimum_bypass_level'];

            // skip the MailHide display if the minimum capability is met
            // todo: only 'use in comments' is checked, have to check each of them?
            // todo: wait, is that necessary? the filter isn't even added if that field is false, removed
            if (($needed_capability && current_user_can($needed_capability))) {
                // remove the nohides
                $content = preg_replace('/\[\/?nohide\]/i','',$content);
                return $content;
            }

            // Regular Expressions thanks to diabolic from EFNet #regex

            // match hyperlinks with emails
            $regex = '%(?<!\[nohide\])<a[^>]*href="((?:mailto:)?([^@"]+@[^@"]+))"[^>]*>(.+?)<\/a>(?!\[/nohide\])%i';
            $content = preg_replace_callback($regex, array(&$this, "replace_hyperlinked"), $content);

            // match emails
            $regex = '%\b([\w.+-]+@[a-z\d.-]+\.[a-z]{2,6})\b(?!\s*\[\/nohide\]|(?:(?!<a[^>]*>).)*<\/a>)%i';
            $content = preg_replace_callback($regex, array(&$this, "replace_plaintext"), $content);

            // remove the nohides
            $content = preg_replace('/\[\/?nohide\]/i','',$content);
            return $content;
        }
        
        // replace the hyperlinked emails i.e. <a href="haha@lol.com">this</a> or <a href="mailto:haha@lol.com">that</a>
        function replace_hyperlinked($matches) {
           // set the minimum capability needed to skip the MailHide if there is one
           if ($recaptcha_opt['bypass_for_registered_users'] && $this->options['minimum_bypass_level'])
              $needed_capability = $this->options['minimum_bypass_level'];

            // skip the MailHide display if the minimum capability is met
            if (($needed_capability && current_user_can($needed_capability))) {
              // remove the nohides
              $content = preg_replace('/\[\/?nohide\]/i','',$content);
                return $content;
           }

           // get the url, the part inside the href. this is the email of course
           $url = recaptcha_mailhide_url($this->options['mailhide_pub'], $this->options['mailhide_priv'], $matches[2]);

           // construct a new hyperlink with the url hidden but the link text the same
           $html = "<a href='" . $url . "' onclick=\"window.open('" . htmlentities ($url, ENT_QUOTES) . "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\">" . $matches[3] . "</a>";

           // style it
           $html = '<span class="mh-hyperlinked">' . $html . "</span>";

           return $html;
        }
        
        // replace the plain text emails i.e. haha@lol.com
        function replace_plaintext($matches) {
           if ($this->options['replace_link_with'] == "" && $this->options['replace_title_with'] == "") {
              // find plain text emails and hide them
              $html = recaptcha_mailhide_html($this->options['public_key'], $this->options['private_key'], $matches[0]);
           }

           else {
              // replace both things
              if ($this->options['replace_link_with'] != "" && $this->options['replace_title_with'] != "") {
                 $url = recaptcha_mailhide_url($this->options['public_key'], $this->options['private_key'], $matches[0]);
                 $html = "<a href='" . htmlentities($url, ENT_QUOTES) .
                    "' onclick=\"window.open('" . htmlentities($url, ENT_QUOTES) . "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" title=\"" . $this->options['replace_title_with'] . "\">" . $this->options['replace_link_with'] . "</a>";
              }

              // only replace the link
              else if ($this->options['replace_link_with'] != "" && $this->options['replace_title_with'] == "") {
                 $url = recaptcha_mailhide_url($this->options['public_key'], $this->options['private_key'], $matches[0]);
                 $html = "<a href='" . htmlentities($url, ENT_QUOTES) .
                    "' onclick=\"window.open('" . htmlentities($url, ENT_QUOTES) . "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" title=\"Reveal this e-mail address\">" . $this->options['replace_link_with'] . "</a>";
              }

              // only replace the title
              else if ($this->options['replace_link_with'] == "" && $this->options['replace_title_with'] != "") {
                 $url = recaptcha_mailhide_url($this->options['public_key'], $this->options['private_key'], $matches[0]);
                 $emailparts = _recaptcha_mailhide_email_parts ($matches[0]);

                $html = htmlentities($emailparts[0], ENT_QUOTES) . "<a href='" . htmlentities($url, ENT_QUOTES) .
                    "' onclick=\"window.open('" . htmlentities($url, ENT_QUOTES) . "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" title=\"" . $recaptcha_opt['replace_title_with'] . "\">...</a>@" . htmlentities($emailparts[0], ENT_QUOTES);
              }
           }

           // style it
           $html = '<span class="mh-plaintext">' . $html . "</span>";

           return $html;
        }
    } // end class declaration
} // end class exists clause

?>