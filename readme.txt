=== Autoptimize criticalcss.com power-up ===
Contributors: futtta, denydias
Tags: autoptimize, critical css, above-the-fold, render-blocking css
Requires at least: 4.9
Tested up to: 4.9
Stable tag: 1.0.0

Autoptimize criticalcss.com power-up adds automated critical css creation to Autoptimize integrating with the premium https://criticalcss.com service.

== Description ==

This "power-up" adds automated critical css functionality to Autoptimize using the premium __criticalcss.com__ service.

Make sure to install and configure Autoptimize first and foremost.

To use this "power-up" you will need a paying subscription at [https://criticalcss.com](https://criticalcss.com/?aff=1).

== Installation ==

1. Install from your WordPress "Plugins > Add New" screen (search for Autoptimize)
1. Activate the plugin.
1. You will see a "Critical CSS"-tab in Autoptimize.
1. Enter the API key from your [https://criticalcss.com](https://criticalcss.com/account/api-keys/?aff=1) 
1. (optional): create a default rule which can be used if no automated rule applies.
1. (optional): create manual Path-based rules for specific pages to override automated rules. If you leave the critical CSS field of path-based rules empty, the plugin will automatically extract it.

== Frequently Asked Questions ==

= Where do I get an API key from? =

Please sign up at [https://criticalcss.com](https://criticalcss.com/?aff=1) then go to [CriticalCSS.com API Keys](https://criticalcss.com/account/api-keys/?aff=1). This is a premium service, so be sure to read the additional pricing information!

At the time of writing (4 May 2018) the price for using CriticalCSS.com is:

> £2/month for membership + £5/domain/month.

This means the total cost will be £7/month if you use this plugin for one site.

= Will this work for inside paywalls or membershp sites? =

No; CriticalCSS.com needs the pages for which it has to generate critical css to be publicaly visible to work.

= What are the Terms of Service for CriticalCSS.com usage = 

See [https://criticalcss.com/general/terms-of-service/](https://criticalcss.com/general/terms-of-service/?aff=1).

= Why isn't the critical CSS visible immediately =

First and foremost you (should) have a page cache, so requests don't end up in WordPress, Autopitmize and the power-up until a page is not in cache. At that point the power-up will add the page to a queue for critical CSS extraction and return the page with default critical CSS (if available). After the job has been executed by criticalcss.com the critical CSS is turned into a rule and it will be used for the next matching request (again for a page not in page cache).

== Changelog ==

= 1.0.0 =

* Initial release
