=== Plugin Name ===
Contributors: BlaenkDenum
Donate link: http://www.blaenkdenum.com
Tags: comments, registration, recaptcha, antispam, mailhide, captcha, wpmu
Requires at least: 2.1
Tested up to: 2.7.1
Stable tag: 2.9.6

Integrates reCAPTCHA anti-spam methods with WordPress including comment, registration, and email spam protection. WPMU Compatible.

== Description ==

= What is reCAPTCHA? =

[reCAPTCHA](http://recaptcha.net/ "reCAPTCHA") is an anti-spam method originating from [Carnegie Mellon University](http://www.cmu.edu/index.shtml "Carnegie Mellon University") which uses [CAPTCHAs](http://recaptcha.net/captcha.html "CAPTCHA") in a [genius way](http://recaptcha.net/learnmore.html "How Does it Work? - reCAPTCHA"). Instead of randomly generating useless characters which users grow tired of continuosly typing in, risking the possibility that spammers will eventually write sophisticated spam bots which use [OCR](http://en.wikipedia.org/wiki/Optical_character_recognition "Optical Character Recognition - Wikipedia") libraries to read the characters, reCAPTCHA uses a different approach.

While the world is in the process of digitizing books, sometimes certain words cannot be read. reCAPTCHA uses a combination of these words, further distorts them, and then constructs a CAPTCHA image. After a ceratin percentage of users solve the 'uknown' word the same way it is assumed that it is the correct spelling of the word. This helps digitize books, giving users a ***reason*** to solve reCAPTCHA forms. Because the industry level scanners and OCR software which are used to digitize the books can't read the words with which the CAPTCHAs are constructed, it is safe to assume that in-house spam-bot OCR techniques will not be able to bypass the CAPTCHA either.

reCAPTCHA has earned a very prestigious reputation among the various CAPTCHA systems available and is used by such sites as [Facebook](http://www.facebook.com), [Twitter](http://www.twitter.com), [StumbleUpon](http://www.stumbleupon.com), and a few U.S. Government Websites such as the [TV Converter Box Coupon Program Website](https://www.dtv2009.gov/ "TV Converter Box Coupon Program Website").

This plugin is [WordPress MU](http://mu.wordpress.org/) compatible.

For more information please view the [plugin page](http://www.blaenkdenum.com/wp-recaptcha/ "WP-reCAPTCHA - Blaenk Denum")..

== Installation ==

To install in regular WordPress:

1. Upload the `wp-recaptcha` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the `Plugins` menu in WordPress
1. Get the reCAPTCHA keys [here](http://recaptcha.net/api/getkey?domain=www.blaenkdenum.com&app=wordpress "reCAPTCHA API keys") and/or the MailHide keys [here](http://mailhide.recaptcha.net/apikey "MailHide keys")

To install in WordPress MU (Optional Activation by Users):

1. Follow the instructions for regular WordPress above

To install in WordPress MU (Forced Activation/Site-Wide):

1. Upload the `wp-recaptcha` folder to the `/wp-content/mu-plugins` directory
1. **Move** the `wp-recaptcha.php` file out of the `wp-recaptcha` folder so that it is in `/wp-content/mu-plugins`
1. Now you should have `/wp-content/mu-plugins/wp-recaptcha.php` and `/wp-content/mu-plugins/wp-recaptcha/`
1. Go to the administrator menu and then go to **Site Admin > reCAPTCHA**
1. Get the reCAPTCHA keys [here](http://recaptcha.net/api/getkey?domain=www.blaenkdenum.com&app=wordpress "reCAPTCHA API keys") and/or the MailHide keys [here](http://mailhide.recaptcha.net/apikey "MailHide keys")

== Requirements ==

* You need the reCAPTCHA keys [here](http://recaptcha.net/api/getkey?domain=www.blaenkdenum.com&app=wordpress "reCAPTCHA API keys") and/or the MailHide keys [here](http://mailhide.recaptcha.net/apikey "MailHide keys")
* If you plan on using MailHide, you will need to have the [mcrypt](http://php.net/mcrypt "mcrypt") PHP module loaded (*Most servers do*)
* If you turn on XHTML 1.0 Compliance you and your users will need to have Javascript enabled to see and complete the reCAPTCHA form
* Your theme must have a `do_action('comment_form', $post->ID);` call right before the end of your form (*Right before the closing form tag*). Most themes do.

== ChangeLog ==

= Version 2.9.6 =
* Fixed a careless bug affecting custom hidden emails
* Fixed broken links in readme.txt
= Version 2.9.5 =
* Added flexibility to the enabling of MailHide. Can now separately choose to enable/disable MailHide for posts/pages, comments, RSS feed of posts/pages, and RSS feed of comments
* Fixed an ['endless redirection' bug](http://wordpress.org/support/topic/245154?replies=1 "endless redirection in wp-reCAPTCHA options form") thanks to Edilton Siqueira
* Fixed a bug in WPMU where wp-admin/user-new.php kept trying to validate the user registration with reCAPTCHA information despite not having shown the reCAPTCHA form, thanks to [Daniel Collis-Puro](http://blogs.law.harvard.edu/ "Weblogs at Harvard Law School") for letting me know
* Added a line break after the reCAPTCHA form to add some padding space between it and the submit button. Due to [popular demand](http://www.chriscredendino.com/2009/03/08/adding-space-between-recaptcha-and-the-comment-submit-button-on-wordpress/ "Adding space between reCAPTCHA and the comment Submit Button on WordPress")
* Fixed a validation problem where a style attribute was missing. Thanks to [nv1962](http://wordpress.org/support/profile/304093 "nv1962's profile")
* Public and Private keys are now trimmed since they are usually pasted from the recaptcha site, to avoid any careless errors
* Fixed the regular expressions for matching the emails, email@provider.co.uk type emails now work
= Version 2.9.4 =
* Fixed a bug where the comment would not be saved if the CAPTCHA wasn't entered correctly. Thanks to Justin Heideman.
= Version 2.9.3 =
* Fixed the `recaptcha_wp_saved_comment` function. Thanks to Tomi M.
= Version 2.9.2 =
* 'Beautified' the options page.
* Added two options to allow users to enter their own custom error messages. Also good for foreign language support.
* Fixed a conflict bug with the OpenID plugin where the reCAPTCHA form would show under the OpenID section in the registration form.
* Added two new options which allow one to choose the text to be shown for all hidden Emails and/or the title of the link.
* Fixed a 'Could not open socket' error in recaptchalib.php. [Bug ID 26](http://code.google.com/p/recaptcha/issues/detail?id=26 "recaptchalib.php: Could not open socket (Fix included)")
* Fixed a WPMU issue where blog registrations weren't possible due to a redirection to the first step in the registration process. Thanks to [Edward](http://yisheng.wordpress.com/2008/08/14/wp-recaptcha-for-wpmu-26/ "Edward").
= Version 2.9.1 =
* Forgot that if you can see emails in their true form, then you shouldn't have to see the [nohide][/nohide] tags either. Fixed.
= Version 2.8.6 =
* Administration interface is now integrated with 2.5's look and feel. Thanks to [Jeremy Clarke](http://simianuprising.com/ "Jeremy Clarke").
* Users can now have more control over who sees the reCAPTCHA form and who can see emails in their true form (If MailHide is enabled). Thanks to [Jeremy Clarke](http://simianuprising.com/ "Jeremy Clarke").
* Fixed a very stupid (**One character deal**) fatal error on most Windows Servers which don't support short tags (short_open_tag). I'm speaking of the so called 'Unexpected $end' error.
* Accomodated for the fact that in +2.6 the wp-content folder can be anywhere.

== Frequently Asked Questions ==

= HELP, I'm still getting spam! =
There are four common issues that make reCAPTCHA appear to be broken:

1. **Moderation Emails**: reCAPTCHA marks comments as spam, so even though the comments don't actually get posted, you will be notified of what is supposedly new spam. It is recommended to turn off moderation emails with reCAPTCHA.
1. **Akismet Spam Queue**: Again, because reCAPTCHA marks comments with a wrongly entered CAPTCHA as spam, they are added to the spam queue. These comments however weren't posted to the blog so reCAPTCHA is still doing it's job. It is recommended to either ignore the Spam Queue and clear it regularly or disable Akismet completely. reCAPTCHA takes care of all of the spam created by bots, which is the usual type of spam. The only other type of spam that would get through is human spam, where humans are hired to manually solve CAPTCHAs. If you still get spam while only having reCAPTCHA enabled, you could be a victim of the latter practice. If this is the case, then turning on Akismet will most likely solve your problem. Again, just because it shows up in the Spam Queue does NOT mean that spam is being posted to your blog, it's more of a 'comments that have been caught as spam by reCAPTCHA' queue.
1. **Trackbacks and Pingbacks**: reCAPTCHA can't do anything about pingbacks and trackbacks. You can disable pingbacks and trackbacks in Options > Discussion > Allow notifications from other Weblogs (Pingbacks and trackbacks).
1. **Human Spammers**: Believe it or not, there are people who are paid (or maybe slave labor?) to solve CAPTCHAs all over the internet and spam. This is the last and rarest reason for which it might appear that reCAPTCHA is not working, but it does happen. On this plugin's [home page](http://www.blaenkdenum.com/wp-recaptcha/ Blaenk Denum - WP-reCAPTCHA), these people sometimes attempt to post spam to try and make it seem as if reCAPTCHA is not working. A combination of reCAPTCHA and Akismet might help to solve this problem, and if spam still gets through for this reason, it would be very minimal and easy to manually take care of.

= Why am I getting “Warning: pack() [function.pack]: Type H: illegal hex digit”? =
You have the keys in the wrong place. Remember, the reCAPTCHA keys are different from the MailHide keys. And the Public keys are different from the Private keys as well. You can’t mix them around. Go through your keys and make sure you have them each in the correct box.

= Aren't you increasing the time users spend solving CAPTCHAs by requiring them to type two words instead of one? =
Actually, no. Most CAPTCHAs on the Web ask users to enter strings of random characters, which are slower to type than English words. reCAPTCHA requires no more time to solve than most other CAPTCHAs.

= Are reCAPTCHAs less secure than other CAPTCHAs that use random characters instead of words? =
Because we ask users to enter two words instead of one, we can increase the security of reCAPTCHA against programs that attempt to guess the words using a dictionary. Whenever an IP address fails one reCAPTCHA, we can show them more distorted words, and give them challenges for which we know both words. The probability of randomly guessing both words correctly would be less than one in ten million.

= Are CAPTCHAs secure? I heard spammers are using porn sites to solve them: the CAPTCHAs are sent to a porn site, and the porn site users are asked to solve the CAPTCHA before being able to see a pornographic image. =

CAPTCHAs offer great protection against abuse from automated programs. While it might be the case that some spammers have started using porn sites to attack CAPTCHAs (although there is no recorded evidence of this), the amount of damage this can inflict is tiny (so tiny that we haven't even seen this happen!). Whereas it is trivial to write a bot that abuses an unprotected site millions of times a day, redirecting CAPTCHAs to be solved by humans viewing pornography would only allow spammers to abuse systems a few thousand times per day. The economics of this attack just don't add up: every time a porn site shows a CAPTCHA before a porn image, they risk losing a customer to another site that doesn't do this.

== Screenshots ==

1. The reCAPTCHA Settings
2. The MailHide Settings
