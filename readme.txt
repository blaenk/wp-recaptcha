=== Plugin Name ===
Contributors: BlaenkDenum
Donate link: http://www.blaenkdenum.com
Tags: comments, registration, recaptcha, antispam, mailhide, captcha, wpmu
Requires at least: 2.1
Tested up to: 2.6
Stable tag: 2.8.6

Integrates reCAPTCHA anti-spam methods with WordPress including comment, registration, and email spam protection. WPMU Compatible.

== Description ==

= What is reCAPTCHA? =

[reCAPTCHA](http://recaptcha.net/ "reCAPTCHA") is an anti-spam method originating from [Carnegie Mellon University](http://www.cmu.edu/index.shtml "Carnegie Mellon University") which uses [CAPTCHAs](http://recaptcha.net/captcha.html "CAPTCHA") in a [genius way](http://recaptcha.net/learnmore.html "How Does it Work? - reCAPTCHA"). Instead of randomly generating useless characters which users grow tired of continuosly typing in, risking the possibility that spammers will eventually write sophisticated spam bots which use [OCR](http://en.wikipedia.org/wiki/Optical_character_recognition "Optical Character Recognition - Wikipedia") libraries to read the characters, reCAPTCHA uses a different approach.

While the world is in the process of digitizing books, sometimes certain words cannot be read. reCAPTCHA uses a combination of these words, further distorts them, and then constructs a CAPTCHA image. After a ceratin percentage of users solve the 'uknown' word the same way it is assumed that it is the correct spelling of the word. This helps digitize books, giving users a ***reason*** to solve reCAPTCHA forms. Because the industry level scanners and OCR software which are used to digitize the books can't read the words with which the CAPTCHAs are constructed, it is safe to assume that in-house spam-bot OCR techniques will not be able to bypass the CAPTCHA either.

reCAPTCHA has earned a very prestigious reputation among the various CAPTCHA systems available and is used by such sites as [Facebook](http://www.facebook.com), [Twitter](http://www.twitter.com), [StumbleUpon](http://www.stumbleupon.com), and a few U.S. Government Websites such as the [TV Converter Box Coupon Program Website](https://www.dtv2009.gov/ "TV Converter Box Coupon Program Website").

This plugin is [WordPress MU](http://mu.wordpress.org/) compatible.

For more information please view the [plugin page](http://www.blaenkdenum.com/wp-recaptcha "WP-reCAPTCHA - Blaenk Denum")..

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
* If turn on XHTML 1.0 Compliance you and your users will need to have Javascript enabled to see and complete the reCAPTCHA form
* Your theme must have a `do_action('comment_form', $post->ID);` call right before the end of your form (*Right before the closing form tag*). Most themes do.

== ChangeLog ==

**Version 2.8.6**

* Administration interface is now integrated with 2.5's look and feel. Thanks to [Jeremy Clarke](http://simianuprising.com/ "Jeremy Clarke").
* Users can now have more control over who sees the reCAPTCHA form and who can see emails in their true form (If MailHide is enabled). Thanks to [Jeremy Clarke](http://simianuprising.com/ "Jeremy Clarke").
* Fixed a very stupid (**One character deal**) fatal error on most Windows Servers which don't support short tags (short_open_tag). I'm speaking of the so called 'Unexpected $end' error.
* Accomodated for the fact that in +2.6 the wp-content folder can be anywhere.

== Frequently Asked Questions ==

= Aren't you increasing the time users spend solving CAPTCHAs by requiring them to type two words instead of one? =
Actually, no. Most CAPTCHAs on the Web ask users to enter strings of random characters, which are slower to type than English words. reCAPTCHA requires no more time to solve than most other CAPTCHAs.

= Are reCAPTCHAs less secure than other CAPTCHAs that use random characters instead of words? =
Because we ask users to enter two words instead of one, we can increase the security of reCAPTCHA against programs that attempt to guess the words using a dictionary. Whenever an IP address fails one reCAPTCHA, we can show them more distorted words, and give them challenges for which we know both words. The probability of randomly guessing both words correctly would be less than one in ten million.

= Are CAPTCHAs secure? I heard spammers are using porn sites to solve them: the CAPTCHAs are sent to a porn site, and the porn site users are asked to solve the CAPTCHA before being able to see a pornographic image. =

CAPTCHAs offer great protection against abuse from automated programs. While it might be the case that some spammers have started using porn sites to attack CAPTCHAs (although there is no recorded evidence of this), the amount of damage this can inflict is tiny (so tiny that we haven't even seen this happen!). Whereas it is trivial to write a bot that abuses an unprotected site millions of times a day, redirecting CAPTCHAs to be solved by humans viewing pornography would only allow spammers to abuse systems a few thousand times per day. The economics of this attack just don't add up: every time a porn site shows a CAPTCHA before a porn image, they risk losing a customer to another site that doesn't do this.

== Screenshots ==

1. The reCAPTCHA Settings
2. The MailHide Settings