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

?>