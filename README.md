# Autoptimize CriticalCSS Power-Up

Let Autoptimize and CriticalCSS unleash your site performance and make it appear better than anyone else in search results.

**THIS IS A DEVELOPMENT REPOSITORY - NO RELEASE CODE HERE!**

## Contents

- [ngrok and criticalcss.com Playing Together](#ngrok-and-criticalcsscom-playing-together)
- [Project Stats](#project-stats)

## ngrok and criticalcss.com Playing Together

Local development of this plugin is possible with the help of [ngrok](https://ngrok.com/). This is required because <criticalcss.com> needs to reach the environment to extract HTML and CSS content to make its thing happen. ngrok exposes your *localhost* in a safe manner using a tunnel.

The steps to configure ngrok and criticalcss.com to play together are bellow.

1. Subscribe to a [**paid** ngrok plan](https://ngrok.com/pricing). Basic (US$ 5/mo billed annually) is enough. If you want to get billed monthly, then Pro (US$ 10/mo billed monthly) is what you looking for.

2. Login to your ngrok account and setup a **Reserved** domain. This will ensure that you local development environment won't change it's URL between sessions, something that's not supported by criticalcss.com.

3. Once your reserved ngrok tunnel is ready to use, go to **Auth* section and copy your **Your Tunnel Authtoken**.

4. Create the file `~/.ngrok2/ngrok.yml` with the following content (see [ngrok's configuration file document](https://ngrok.com/docs#config) for more specific setup):

    ```
    authtoken: <your_tunnel_authtoken>
    tunnels:
      aodev:
        proto: http
        addr: 8000
        subdomain: <reserved_domain_without_.ngrok.io_part>
    ```

5. Go to the plugin development base path and run:

```
./tunnel.sh
```

6. Subscribe to the [**paid** criticalcss.com plan](https://criticalcss.com/#pricing). There is only one for £2/mo, but keep in mind that API usage required by this plugin costs another £5/mo. **IMPORTANT:** DO NOT mark any of the WordPress options in the signup form. None of them are required.

7. Login to your criticalcss.com account and request to *Generate Critical CSS* using your ngrok's reserved domain in FQDN form (e.g. `myaodev.ngrok.io`).

If everything went fine, you'll see criticalcss.com requesting your WordPress's home page in the webserver logs. At this point you're ready to code. If there was any problems, please check [ngrok](https://ngrok.com/docs) and [criticalcss.com](https://criticalcss.com/faq) documentation for troubleshooting.

## Project Stats

*Stats updated at: 2017/12/08*

**Project size:** 240 KB

### Lines of Code

**Language**|**Files**|**Blank Lines**|**Comments**|**Functional Code**
:-------|-------:|-------:|-------:|-------:
PHP|15|423|432|1986
CSS|2|6|8|380
JavaScript|2|13|2|112
Bourne Shell|2|18|0|57
--------|--------|--------|--------|--------
**SUM:**|**21**|**460**|**442**|**2535**

### Media and Other

**Type**|**Files**|**Size (B)**
:-------|-------:|-------:
GIF|3|172
PNG|1|9585
I18|2|41674
--------|--------|--------
**SUM:**|**6**|**51431**

### Spec Items

```
Arquivo binário .README.md.kate-swp coincide com o padrão
inc/admin_settings_license.php:3:// NOTE: implements section 4, id 4.1 of the specs
inc/admin_settings.php:3:// NOTE: implements section 4, id 4 of the specs
inc/admin_settings_queue.php:4:// NOTE: implements section 4, id 4.2 and 4.3 of the specs
inc/admin_settings_rules.php:4:// NOTE: implements section 4, id 4.2 and 4.3 of the specs
inc/core_enqueue.php:4:// NOTE: implements section 4, id 2 of the specs
inc/core.php:41:// NOTE: implements section 4, id 1 of the specs
inc/core.php:49:  // NOTE: implements section 4, id 1.1 of the specs (for paths)
inc/core.php:61:  // NOTE: implements section 4, id 1.1 of the specs (for types)
inc/core.php:85:  // NOTE: implements section 4, id 1.2 of the specs
inc/cron.php:3:// NOTE: implements section 4 of the specs
inc/cron.php:81:    // NOTE: implements section 4, id 3.1 of the specs
inc/cron.php:132:    // NOTE: implements section 4, id 3.2 of the specs
inc/cron.php:235:    // NOTE: implements section 4, id 3.2.1 of the specs
inc/cron.php:486:// NOTE: implements section 4, id 3.2.1 of the specs
```

### Out of Scope Items

```
Arquivo binário .README.md.kate-swp coincide com o padrão
inc/admin_settings_adv.php:3:// NOTE: out of scope advanced panel
inc/admin_settings_debug.php:3:// NOTE: out of scope debug panel
inc/admin_settings_feeds.php:1:<?php // NOTE: out of scope feeds panel ?>
inc/core_ajax.php:145:// NOTE: out of scope export settings
inc/core_ajax.php:208:// NOTE: out of scope import settings
inc/core_enqueue.php:10:  // NOTE: out of scope check for criticalcss.com UA
inc/cron.php:41:  // NOTE: out of scope queue debug
inc/cron.php:545:// NOTE: out of scope log file maintenance
languages/ao_critcss_aas.pot:3:# NOTE: out of scope localization template
languages/autoptimize-pt_BR.po:1:# NOTE: out of scope pt-BR localization
```

### Items To Fix

```
Arquivo binário .README.md.kate-swp coincide com o padrão
ao_criticss_aas.php:120:  // FIXME: change this to 10min for relase (also required in inc/cron.php)
inc/cron.php:6:// FIXME: change this to 10min ('interval' => 600) for relase (also required in ../ao_criticcss_aas.php)
```

#### Notes

1. Run the command bellow to generate or update [FILELIST.txt](https://github.com/futtta/ao_critcss_aas/blob/master/FILELIST.txt).

    ```
    find . -type f ! -path "./.git/*" ! -path "./FILELIST.txt" ! -path "./README.md" \
      ! -path "./languages/*.po~" ! -path "./lib/*" > FILELIST.txt
    ```

2. To update the stats above, run the command bellow once you have FILELIST.txt in place. Then copy and paste its output overriding the content of the stats above.

    ```
    ./stats.sh
    ```