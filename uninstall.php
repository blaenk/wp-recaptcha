<?php
// this is the uninstall handler
// include unregister_setting, delete_option, and other uninstall behavior here

require_once('plugin.php');

function uninstall_options($name) {
    unregister_setting("${name}_group", $name);
    Plugin::remove_options($name);
}

// recaptcha
uninstall_options('recaptcha_options');

// mailhide
uninstall_options('mailhide_options');

?>