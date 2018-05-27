=== Autoptimize criticalcss.com power-up ===
Contributors: futtta, denydias
Tags: autoptimize, critical css, above-the-fold, render-blocking css
Requires at least: 4.9
Tested up to: 4.9
Stable tag: 1.2.0

Autoptimize criticalcss.com power-up adds automated critical css creation to Autoptimize integrating with the premium https://criticalcss.com service.

== Description ==

This "power-up" can make your pages start rendering sooner, improving user experience. This is done by automated critical css extraction and inlining based on [Autoptimize's](https://wordpress.org/plugins/autoptimize/) "inline and defer" option and integrating with the premium __criticalcss.com__ service.

To use this "power-up" you should have Autoptimize installed and configured and you need a paying subscription at [https://criticalcss.com](https://criticalcss.com/?aff=1).

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

If you're not sure yet; with the [30 day free trial](https://criticalcss.com/faq/#refunds), you have nothing to lose!

= Will this work for inside paywalls or membershp sites? =

No; CriticalCSS.com needs the pages for which it has to generate critical css to be publicaly visible to work.

= What are the Terms of Service for CriticalCSS.com usage = 

See [https://criticalcss.com/general/terms-of-service/](https://criticalcss.com/general/terms-of-service/?aff=1).

= Why isn't the critical CSS visible immediately? =

Critical CSS generation is based on a job-queue. For jobs to be added to the queue, your site should have requests and those requests should not be served by a page cache (because in that case WordPress and Autoptimize are not triggered). If you want to speed things up, you can temporarily disable your page cache and click around on your website yourself. 

Once a job is in the queue it can be executed and sent to criticalcss.com and at one of the next queue runs the critical CSS is retrieved and turned into a rule and it will be used for the next matching request (again for a page not in page cache).

= What if my hosts limits the time PHP processes can run? =

Autoptimize CriticalCss.com power-up uses scheduled jobs to go over a queue with URL's for which to fetch critical CSS. If there are many items in the queue, the process can take a couple of minutes to finish. If your hosts limits the time scheduled PHP processes can run, you can set the number of requests sent to criticalcss.com (the "request limit") under the Advanced Options.

== Changelog ==

= 1.2.0 =

* New advanced option: "Fetch Original CSS"
* Minor copy changes in Key settings panel and FAQ.

= 1.1.0 =

* Changes to queue processing to cater for hosts with hard limits on PHP processes duration

= 1.0.1 =

* Extra info on the API key entry page

= 1.0.0 =

* Initial release
