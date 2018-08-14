=== Autoptimize criticalcss.com power-up ===
Contributors: futtta, denydias
Tags: autoptimize, critical css, above-the-fold, render-blocking css
Requires at least: 4.9
Tested up to: 4.9
Stable tag: 1.7.0

Autoptimize criticalcss.com power-up adds automated critical css creation to Autoptimize integrating with the https://criticalcss.com service.

== Description ==

This plugin extends Autoptimize to automatically create critical CSS rules. These rules inject the correct critical CSS in different types of pages to ensure these pages are rendered even before the full CSS is loaded, improving the "start to render time" and user experience. For this purpose the plugin integrates with __criticalcss.com__, a 3rd party service, to have it generate the critical CSS.

Simply install and activate the plugin (you will need to have Autoptimize up and running), enter your [https://criticalcss.com](https://criticalcss.com/?aff=1) API key and the plugin will automatically start work to create rules.

If you want to change settings or review the rules, you can find these by clicking the “critical css” tab on the Autoptimize plugin settings screen. There are "installation instructions" and more info in [the FAQ](https://wordpress.org/plugins/autoptimize-criticalcss/#faq).

== Installation ==

1. Install from your WordPress "Plugins > Add New" screen (search for Autoptimize)
1. Activate the plugin.
1. You will see a "Critical CSS"-tab in Autoptimize.
1. Enter the API key from your [https://criticalcss.com](https://criticalcss.com/account/api-keys?aff=1) 
1. (optional): create a default rule which can be used if no automated rule applies.
1. (optional): create manual Path-based rules for specific pages to override automated rules. If you leave the critical CSS field of path-based rules empty, the plugin will automatically extract it.
1. To get critical CSS going, make sure there are requests coming in that are not served by a page cache

== Frequently Asked Questions ==

= Where do I get an API key from? =

Please sign up at [https://criticalcss.com](https://criticalcss.com/?aff=1) then go to [CriticalCSS.com API Keys](https://criticalcss.com/account/api-keys?aff=1). This is a premium service, so be sure to read the additional pricing information!

At the time of writing (4 May 2018) the price for using CriticalCSS.com is:

> £2/month for membership + £5/domain/month.

This means the total cost will be £7/month if you use this plugin for one site.

If you're not sure yet; with the [30 day free trial](https://criticalcss.com/faq/#trial), you have nothing to lose!

= Will this work for inside paywalls or membershp sites? =

No; CriticalCSS.com needs the pages for which it has to generate critical css to be publicaly visible to work.

= What are the Terms of Service for CriticalCSS.com usage = 

See [https://criticalcss.com/general/terms-of-service/](https://criticalcss.com/general/terms-of-service/?aff=1).

= Why isn't the critical CSS visible immediately? =

Critical CSS generation is based on a job-queue. For jobs to be added to the queue, your site should have requests and those requests should not be served by a page cache (because in that case WordPress and Autoptimize are not triggered). If you want to speed things up, you can temporarily disable your page cache and click around on your website yourself. 

Once a job is in the queue it can be executed and sent to criticalcss.com and at one of the next queue runs the critical CSS is retrieved and turned into a rule and it will be used for the next matching request (again for a page not in page cache).

= What if my hosts limits the time PHP processes can run? =

Autoptimize CriticalCss.com power-up uses scheduled jobs to go over a queue with URL's for which to fetch critical CSS. If there are many items in the queue, the process can take a couple of minutes to finish. If your hosts limits the time scheduled PHP processes can run, you can set the number of requests sent to criticalcss.com (the "request limit") under the Advanced Options.

= I use a pagebuilder, so my pages are very different yet the same CCSS is applied? =

As from AO CCSS 1.7 there is an (advanced) option you can activate to enforce PATH-based rules creation for pages so each page will end up with its own critical CSS.

= Can I stop critical CSS being applied on some pages? =

Yes; create a manual rule (can be both path- and conditional-tag based) and enter `none` for critical CSS. If the rule matches, no critical CSS will be added and the full CSS will be inlined instead.

= I only see "N" jobs and no rules and I'm getting "WordPress cron"-warnings, what should I do? =

If all jobs remain in "N" then wordpress "cron job" that does the queue processing is not getting triggered. To verify you can install the ["wp crontrol"-plugin](https://wordpress.org/plugins/wp-crontrol/) and then under Tools -> Cron Events look for "ao_ccss_queue" and check the "next run" time/ date.

If the "ao_ccss_queue" job is not there, you'll have to de- and re-activate the "autoptimize critical css" plugin to have it re-register the queue-processing task.

If the "ao_ccss_queue" job is there, but has a "next run" date in the past, there is an issue with your site/ hosters WordPress cron and you will have to contact your hoster. Some hosters' info on the topic: [WP Engine](https://wpengine.com/support/wp-cron-wordpress-scheduling/), [BlueHost](https://my.bluehost.com/hosting/help/411), [HostGator](https://support.hostgator.com/articles/how-to-replace-wordpress-cron-with-a-real-cron-job) and [SiteGround](https://www.siteground.com/tutorials/wordpress/real-cron-job/).

== Changelog ==

= 1.7.0 =
* new: (advanced) option to allow PATH-based rules to be auto-created for pages (incl. WooCommerce product pages) allowing different CCSS for each page.
* improvement: workaround a quirk in WordPress core's is_front_page which also returns true for e.g. /page/12
* improvement: ensure path-based rules with non-ascii characters can match the path

= 1.6.0 =

* new: (advanced) option to disable CCSS injection for logged on users (as the CCSS is always extracted from an anonymous visitor context, the CCSS might not apply for logged in users).
* improvement: warn when the job queue processing task is not getting triggered (due to WordPress cron issues).
* improvement: also submit a request to criticalcss.com if a rule exists but the file containing the CCSS does not exist or is empty.
* added info about cron issues to the FAQ.

= 1.5.0 =

* bugfix: when deactivating make sure lockfile is removed before removing cache directory.
* bugfix: make sure Autoptimize's "inline & defer above the fold CSS" is not removed when submitting criticalcss API key.
* bugfix: ensure order of rules does not depend on when they were added, but is custom post types first, template 2nd, plugins (Woo, EDD, BuddyPress & BBPress) conditional tags and lastly the WordPress core conditionals.
* bugfix: make sure non-core conditionals are checked against.

= 1.4.0 =

* move cache to wp-content/uploads/ao_ccss/ (to prevent files from being deleted by a sometimes overzealous WP Super Cache purge)
* warn if DISABLE_WP_CRON is true
* update default viewport size in advanced settings

= 1.3.0 =

* New: you can now create manual rules to make sure no Critical CSS is injected by entering `none` as critical CSS.
* Improvement: make sure "advanced options" are visible when "activate inline & defer" warning is shown
* Further copy changes in description (thanks for the great feedback Paul!)

= 1.2.0 =

* New advanced option: "Fetch Original CSS"
* Minor copy changes in Key settings panel and FAQ.

= 1.1.0 =

* Changes to queue processing to cater for hosts with hard limits on PHP processes duration

= 1.0.1 =

* Extra info on the API key entry page

= 1.0.0 =

* Initial release
