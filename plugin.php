<?php

// just making sure the constant is defined
if (!defined('WP_CONTENT_DIR'))
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
 

if (!class_exists('Environment')) {
    class Environment {
        const WordPress = 1; // regular wordpress
        const WordPressMU = 2; // wordpress mu
        const WordPressMS = 3; // wordpress multi-site
    }
}

if (!class_exists('Plugin')) {
    abstract class Plugin {
        protected $environment; // what environment are we in
        protected $options_name; // the name of the options associated with this plugin
        
        protected $options;
        
        function Plugin($options_name) {
            $args = func_get_args();
            call_user_func_array(array(&$this, "__construct"), $args);
        }
        
        function __construct($options_name) {
            $this->environment = Plugin::determine_environment();
            $this->options_name = $options_name;
            
            $this->options = Plugin::retrieve_options($this->options_name);
        }
        
        // sub-classes determine what actions and filters to hook
        abstract protected function register_actions();
        abstract protected function register_filters();
        
        // environment checking
        static function determine_environment() {
            global $wpmu_version;
            
            if (function_exists('is_multisite'))
                if (is_multisite())
                    return Environment::WordPressMS;
            
            if (!empty($wpmu_version))
                return Environment::WordPressMU;
                
            return Environment::WordPress;
        }
        
        // path finding
        static function plugins_directory() {
            if (Plugin::determine_environment() == Environment::WordPressMU)
                return WP_CONTENT_DIR . '/mu-plugins';
            else
                return WP_CONTENT_DIR . '/plugins';
        }
        
        static function path_to_plugin_directory() {
            $current_directory = basename(dirname(__FILE__));
            
            return Plugin::plugins_directory() . "/${current_directory}";
        }
        
        static function path_to_plugin($file_path) {
            $file_name = basename($file_path); // /etc/blah/file.txt => file.txt
            
            if (Plugin::determine_environment() == Environment::WordPressMU)
                return Plugin::plugins_directory() . "/${file_name}";
            else
                return Plugin::path_to_plugin_directory() . "/${file_name}";
        }
        
        // options
        abstract protected function register_default_options();
        
        // option retrieval
        static function retrieve_options($options_name) {
            if (Plugin::determine_environment() == Environment::WordPressMU || Plugin::determine_environment() == Environment::WordPressMS)
                return get_site_option($options_name);
            else
                return get_option($options_name);
        }
        
        static function remove_options($options_name) {
            if (Plugin::determine_environment() == Environment::WordPressMU || Plugin::determine_environment() == Environment::WordPressMS)
                return delete_site_option($options_name);
            else
                return delete_option($options_name);
        }
        
        static function add_options($options_name, $options) {
            if (Plugin::determine_environment() == Environment::WordPressMU || Plugin::determine_environment() == Environment::WordPressMS)
                return add_site_option($options_name, $options);
            else
                return add_option($options_name, $options);
        }
        
        protected function is_multi_blog() {
            return $this->environment != Environment::WordPress;
        }
        
        // calls the appropriate 'authority' checking function depending on the environment
        protected function is_authority() {
            if ($this->environment == Environment::WordPress)
                return is_admin();
            
            if ($this->environment == Environment::WordPressMU)
                return is_site_admin();
            
            if ($this->environment == Environment::WordPressMS)
                return is_super_admin();
        }
    }
}

?>