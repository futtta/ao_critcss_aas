=== Autoptimize criticalcss.com power-up ===
Contributors: futtta, denydias
Tags: autoptimize, critical css, above-the-fold, render-blocking css
Requires at least: 4.9
Tested up to: 5.2
Stable tag: 1.17.1

Autoptimize criticalcss.com power-up adds automated critical css creation to Autoptimize integrating with the https://criticalcss.com service.

== Description ==

This plugin extends Autoptimize to automatically create critical CSS rules. These rules inject the correct critical CSS in different types of pages to ensure these pages are rendered even before the full CSS is loaded, improving the "start to render time" and user experience. For this purpose the plugin integrates with __criticalcss.com__, a 3rd party service, to have it generate the critical CSS (see [FAQ for info on pricing](https://wordpress.org/plugins/autoptimize-criticalcss/#faq)).

Simply install and activate the plugin (you will need to have Autoptimize up and running), enter your [https://criticalcss.com](https://criticalcss.com/?aff=1) API key and the plugin will automatically start work to create rules.

If you want to change settings or review the rules, you can find these by clicking the “critical css” tab on the Autoptimize plugin settings screen. There are "installation instructions" and more info in [the FAQ](https://wordpress.org/plugins/autoptimize-criticalcss/#faq).

== Installation ==

1. Install from your WordPress "Plugins > Add New" screen (search for Autoptimize)
1. Activate the plugin.
1. You will see a "Critical CSS"-tab in Autoptimize.
1. Enter the API key from your [https://criticalcss.com](https://criticalcss.com/account/api-keys?aff=1) 
1. (optional): create a default rule which can be used if no automated rule applies.
1. (optional): create manual Path-based rules for specific pages to override automated rules. If you leave the critical CSS field of path-based rules empty, the plugin will automatically generate it.
1. To get critical CSS going, make sure there are requests coming in that are not served by a page cache

== Frequently Asked Questions ==

= Where do I get an API key from/ how is this priced? =

Please sign up at [https://criticalcss.com](https://criticalcss.com/?aff=1) then go to [CriticalCSS.com API Keys](https://criticalcss.com/account/api-keys?aff=1). This is a premium service, so be sure to read the additional pricing information!

At the time of writing (4 May 2018) the price for using CriticalCSS.com is:

> £2/month for membership + £5/domain/month.

This means the total cost will be £7/month if you use this plugin for one site.

If you're not sure yet; with the [30 day free trial](https://criticalcss.com/faq/?aff=1#trial), you have nothing to lose!

= Will this work for inside paywalls or membership sites? =

No; CriticalCSS.com needs the pages for which it has to generate critical css to be publicaly visible to work.

= What are the Terms of Service for CriticalCSS.com usage = 

See [https://criticalcss.com/general/terms-of-service/](https://criticalcss.com/general/terms-of-service/?aff=1).

= Why did nothing happen after I installed the plugin/ Why isn't the critical CSS visible immediately? =

Critical CSS generation is based on a job-queue. For jobs to be added to the queue, your site should have requests and those requests should not be served by a page cache (because in that case WordPress and Autoptimize are not triggered). If you want to speed things up, you can temporarily disable your page cache and click around on your website yourself. 

Once a job is in the queue it can be executed and sent to criticalcss.com and at one of the next queue runs the critical CSS is retrieved and turned into a rule and it will be used for the next matching request (again for a page not in page cache).

= My hoster claims the plugin is time consuming, is this normal? =

When just installed the plugin will be more active, generating new jobs and for most of those jobs making calls to criticalcss.com. As rules are automatically generated that way, the number of jobs and the number of requests to criticalcss.com will go down significantly.

Most importantly; as the bulk of the work is done asynchronously (by the cronned queue processing job), there is no negative impact on the performance of your site, so your visitors will not notice any slowdown.

= What if my hosts limits the time PHP processes can run? =

Autoptimize CriticalCss.com power-up uses scheduled jobs to go over a queue with URL's for which to fetch critical CSS. If there are many items in the queue, the process can take a couple of minutes to finish. If your hosts limits the time scheduled PHP processes can run, you can change the number of requests sent to criticalcss.com (the "request limit") under the Advanced Options (default is 5).

= I use a pagebuilder, so my pages are very different yet the same CCSS is applied? =

As from AO CCSS 1.7 there is an (advanced) option you can activate to enforce PATH-based rules creation for pages so each page will end up with its own critical CSS.

= Can I stop critical CSS being applied on some pages? =

Yes; create a manual rule (can be both path- and conditional-tag based) and enter `none` for critical CSS. If the rule matches, no critical CSS will be added and the full CSS will be inlined instead.

= I only see "N" jobs and no rules and I'm getting "WordPress cron"-warnings, what should I do? =

If all jobs remain in "N" then wordpress "cron job" that does the queue processing is not getting triggered. To verify you can install the ["wp crontrol"-plugin](https://wordpress.org/plugins/wp-crontrol/) and then under Tools -> Cron Events look for "ao_ccss_queue" and check the "next run" time/ date.

If the "ao_ccss_queue" job is not there, you'll have to de- and re-activate the "autoptimize critical css" plugin to have it re-register the queue-processing task.

If the "ao_ccss_queue" job is there, but has a "next run" date in the past, there is an issue with your site/ hosters WordPress cron and you will have to contact your hoster. Some hosters' info on the topic: [WP Engine](https://wpengine.com/support/wp-cron-wordpress-scheduling/), [BlueHost](https://my.bluehost.com/hosting/help/411), [HostGator](https://support.hostgator.com/articles/how-to-replace-wordpress-cron-with-a-real-cron-job) and [SiteGround](https://www.siteground.com/tutorials/wordpress/real-cron-job/).

= So how can I improve my start render/ first paint times? =

Ensuring the CSS is not render-blocking through this plugin is a first important step to improve rendering performance, but to get a significant better first paint time, you'll need to ensure you have no other render-blocking resources.

Some tips:
* for jQuery: try enabling the advanced "Defer jQuery and other non-aggregated JS-files?"-option (introduced in AO CCSS 1.12.0). This will also wrap inline JS that depends on jQuery in a function for it to be executed late as well. Test your site thoroughly after enabling this option and disable it if anything breaks!
* for other JS: try to find an Autoptimize configuration where no JavaScript-files have to be excluded.
* for excluded/ external JS: try to async/ defer using the Autopitmize Extra Async field or using the [Async JavaScript plugin](https://wordpress.org/plugins/async-javascript/).
* for Google Fonts: try the options on Autoptimize "Extra" tab. Remove Google Fonts is great but might be too aggressive for designers, "aggregate CSS & Preload" is a good alternative that is inline with what Autoptimize does with CSS.
* for YouTube videos: try a plugin that lazyloads embedded videos (e.g. [WP YouTube Lyte](https://wordpress.org/plugins/wp-youtube-lyte/)).

= When I clone my site to a new environment or on a domain mapped multi-site environment the queue does not get processed any more? =

As of AO CCSS 1.13.0 the plugin binds itself to a domain to avoid unexpected requests from cloned sites. You can either deactivate and reactivate the plugin to reset the "bound domain" or you can pass `false` to the `autoptimize_filter_ccss_bind_domain` filter to disable the domain binding.

== Changelog ==

= 1.17.1 =

* Urgent fix for `Uncaught Error: Call to undefined function is_user_logged_in() ` as reported by Matthew Rode.

= 1.17.0 =

* Probably the last version before the merge into Autoptimize proper!
* improvement: also save CCSS if validation was not succesfull due to blank screenshot (SCREENSHOT_WARN_BLANK)
* improvement: jQuery deferring should also happen when "aggregate inlne JS" is on but should not happen for logged on users when AO CCSS is not returne for logged on users either.
* bugfix: export didn't work + usability improvements (requires ZipArchive class to be available in PHP)
* bugfix: creation of AO CCSS cache directory failed in multisite context
* misc. smaller improveent

= 1.16.0 =

* new: allow "bound domain" to be changed (advanced option)
* improvement: improved default settings logic
* improvement: checks to prevent some PHP notices
* bugfix: prevent files with CCSS being removed even when still referenced in a rule
* bugfix: make sure rules and queue objects are global variables, thanks for the fix Marius!
* bugfix: fix defer-wrapping of non-javascript script tags (ld/json for example), thanks for reporting Kyla!

= 1.15.2 =

* bugfix to stop P (pending) jobs from being overwritten by N (new) ones, leading to rules being generated later or (in rare cases) not at all.

= 1.15.1 =

* tell class_exists not to autoload classes to avoid queue processing breaking down (thanks to Markus for reporting and helping identify the issue)

= 1.15.0 =

* bugfix for blacklist false positive
* improvement: extra logic to ensure manual rules don't get overwritten
* improvement: ensure old rules don't break site when API key is not valid and rules are not updated any more
* logfile now queuelog.html instead of queue.log

= 1.14.0 =

* better first time user experience
* by default limit requests to criticalcss.com to 5 per cron run
* bugfix: retry failed jobs icon
* bugfix: ignore http vs https for domain binding
* documentation: add tips to FAQ regarding how to improve start render/ first paint time

= 1.13.0 =

* improvement: make sure the front-page only gets critical CSS from the is_front_page and not e.g. is_page
* improvement: automatically bind plugin activity to a domain to avoid site clones start making requests
* bugfix: if the default CCSS contained double quotes it broke both the CCSS when injected and the settings page, thanks to Baris for identifying the problem!
* copy improvement for path-based rules.

= 1.12.0 =

* new: advanced option (default off) to defer non-autoptimized JS (linked and inline).
* improvement: in maintenance job check if `ao_ccss_queue` is scheduled and schedule if not.
* improvement: added filter (`ao_ccss_queue_schedule`) to allow (power-)users to change the scheduling of the queue processing job.

= 1.11.0 =

* keep API key and some other key (non-transient) settings when plugin is deactivated
* improve "force include" copy + set max-size on that input field
* add extra info (wp_error) to debug log if key check does not succeed

= 1.10.1 =

* remove a line of debug code

= 1.10.0 =

* improvement: create path-based rules for pages by default for new installs (change the setting manually when upgrading from 1.9.0) 
* bugfix: recheck invalid API key during the daily maintenance to avoid it getting stuck
* bugfix: don't strip slashes in generated CCSS
* update to latest version of the "persist admin notice dismissal" framework library

= 1.9.0 =

* improvement: make some notices dismissable
* improvement: job queue cleanup logic improved
* improvement: there now is a different error-message for "bad API key" vs "could not check your API key"
* improvement: logic added to be able to warn if criticalcss.com would be down
* bugfix for subfolder installations of WordPress resulting in wrong URL's to be sent to criticalcss.com
* bugfix: some EDD conditionals were empty in the dropdown
* bugfix for notice "Argument #2 is not an array /wp-content/plugins/autoptimize-criticalcss/inc/core.php: 243"

= 1.8.0 =

* improvement (efficiency): don't submit request to criticalcss.com if the same one (same type and same hash) has been submitted already in the current queue processing run
* improvement (UI): job queue panel is collapsable and only shown if no AUTO rule is available yet
* improvement (debugging): add rule info in comment in Critical CSS if "debug" is on
* improvement (conditional rules): added is_attachement as additional core conditional tag
* new: handle (future) resultStatus (HTML_404) from criticalcss.com

= 1.7.0 =

* new: (advanced) option to allow PATH-based rules to be auto-created for pages allowing different CCSS for each page.
* improvement: workaround a quirk in WordPress core's is_front_page which also returns true for e.g. /page/12
* improvement: ensure path-based rules with non-ascii characters can match the path

= 1.6.0 =

* new: (advanced) option to disable CCSS injection for logged on users (as the CCSS is always geneated from an anonymous visitor context, the CCSS might not apply for logged in users).
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
