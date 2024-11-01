=== Category Subdomains for Woocommerce by TheCartPress ===
Contributors: 	   tcpteam
Plugin Name:       Category Subdomains for Woocommerce by TheCartPress
Plugin URI:        https://www.thecartpress.com
Tags:              subdomain, post, product, category, woocommerce, wildcard, thecartpress, tcp
Author URI:        https://www.thecartpress.com
Author:            TCP Team
Requires PHP: 	   5.6
Requires at least: 5.5
Tested up to:      5.8.4
Stable tag:        1.4.0
Version:           1.4.0
License:  		     GPLv3
License URI: 	     https://www.gnu.org/licenses/gpl-3.0.html

== Description ==
TCP Category Subdomains plugin can converts post and woocommerce product categories to sub-domains.

Simply Create a Posts or Product Categories. All post and product categories will be included inside the plugin page. You can choose to enable or disable them.
You may require configuration on adding wildcard subdomain. Normally it will be configured by host, ask them if needed. It will increase the performance of site and will help in SEO. Plugin will also auto redirect to new subdomain link to avoid duplicate content issue.

[FREE] Maximum 5 post or product subdomains
[PREMIUM] Unlimited subdomains for all post and product categories

Upgrade to [PREMIUM](https://app.thecartpress.com/plugin/tcp-category-subdomains/) to enjoy unlimited domains.

**IMPORTANT**
Don't forget to configure WildCard Subdomain.

== Installation ==
1. Unzip and Upload Folder to the /wp-content/plugins/ directory.
2. Activate through WordPress plugin dashboard page.
3. Visit TheCartPress > Category Subdomain > Choose cateories that need to set as subdomain.
4. Save Changes.

== Upgrade Notice ==

== Changelog ==

= 1.4.0 =
* Support subdomain for category page

= 1.3.0 =
* Add plugin link to TheCartPress sidebar menu
* Use tcp.php

= 1.2.0 =
* fix redirect bug reported
* fix unidentified index warning in menu page

= 1.1 =
* implement TheCartPress menu
* fix bugs reported

= 1.0 =
* First release

== Screenshots ==
1. TCP Category Subdomains settings page

== Frequently Asked Questions ==
=How to configure a WildCard Subdomain=
Read the following tutorial https://codex.wordpress.org/Configuring_Wildcard_Subdomains</p>

=Where to set the subdomain=
In Setting Menu > TCP Category Subdomains > Tick to enable product or post category > Save Changes

=When using woocommerce, add cart not working?=
This seems to be regarding cookies availability in subdomain issue.
one of the solution is to change the cookie domain.

eg: go to wp-config file, add in define('COOKIE_DOMAIN', '.example.com'); need to add `.` in front of the domain to support multiple subdomain.

== Donations ==