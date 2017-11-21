<?php

// Settings tab
function ao_ccss_settings() {

  // Attach globals
  global $ao_css_defer;
  global $ao_ccss_key;

  ?>
  <div class="wrap">
    <div id="autoptimize_main">
      <div id="ao_title_and_button">
        <h1><?php _e('Autoptimize Power-Up: CriticalCSS Settings', 'ao_ccss'); ?></h1>
      </div>

      <?php
      // Print AO settings tabs
      echo autoptimizeConfig::ao_admin_tabs();

      // Check CriticalCSS license
      $licstat = ccss_license_check('ao_critcsscom', '0.9', $ao_ccss_key);

      // Make sure dir to write ao_ccss exists and is writable
      if (!is_dir(AO_CCSS_DIR)) {
        $mkdirresp = @mkdir(AO_CCSS_DIR, 0775, true);
        $fileresp  = file_put_contents(AO_CCSS_DIR . 'index.html','<html><head><meta name="robots" content="noindex, nofollow"></head><body>Generated by <a href="http://wordpress.org/extend/plugins/autoptimize/" rel="nofollow">Autoptimize</a></body></html>');
        if ((!$mkdirresp) || (!$fileresp)) {
          ?><div class="notice-error notice"><p><?php
          _e("Could not create the required directory. Make sure the webserver can write to the wp-content directory.", "ao_ccss");
          ?></p></div><?php
        }
      }

      // Check for Autoptimize
      if (!defined('AUTOPTIMIZE_CACHE_NOGZIP')) {
        ?><div class="notice-error notice"><p><?php
        _e('Oops! Please install and activate Autoptimize first.', 'ao_css');
        ?></p></div><?php
        exit;
      } else if (!$ao_css_defer) {
        ?><div class="notice-error notice"><p><?php
        _e("Oops! Please <strong>activate the \"Inline and Defer CSS?\" option</strong> on Autoptimize's main settings page.", 'ao_css');
        ?></p></div><?php
      } else if (version_compare(get_option("autoptimize_version"),"2.2.0")===-1) {
        ?><div class="notice-error notice"><p><?php
        _e('Oops! It looks you need to upgrade to Autoptimize 2.2.0 or higher to use this power-up.', 'ao_css');
        ?></p></div><?php
      }
      ?>

      <!-- TODO: here goes more and more settings... -->

      <!-- BEGIN: Settings Debug -->
      <ul>
        <li class="itemDetail">
          <h2 class="itemTitle"><?php _e('Debug Stuff <small>(This is going to disappear when dev is done!)</small>', 'ao_ccss'); ?></h2>
          <h4>Current Settings:</h4>
          <table class="form-table">
            <tr valign="top">
              <th scope="row">
                autoptimize_ccss_key:
              </th>
              <td>
                <?php echo (empty($ao_ccss_key) ? 'empty' : $ao_ccss_key); ?>
              </td>
            </tr>
          </table>
          <hr />
          <h4>Transients:</h4>
          <table class="form-table">
            <tr valign="top">
              <th scope="row">
                <?php echo 'ao_ccss_lic_status_' . md5($ao_ccss_key) . ':'; ?>
              </th>
              <td>
                <?php echo (empty(get_transient('ao_ccss_lic_status_' . md5($ao_ccss_key))) ? 'empty' : get_transient('ao_ccss_lic_status_' . md5($ao_ccss_key))); ?>
              </td>
            </tr>
          </table>
          <hr />
          <h4>Array Content: autoptimize_ccss_queue</h4>
          <pre>
            <?php print_r(get_option('autoptimize_ccss_queue')); ?>
          </pre>
        </li>
      </li>
      <!-- END: Settings Debug -->
  </div>

  <div id="autoptimize_admin_feed" class="hidden">
    <div class="autoptimize_banner hidden">
      <ul>
      <?php
      if (apply_filters('autoptimize_settingsscreen_remotehttp',true)) {
        $AO_banner=get_transient("autoptimize_banner");
        if (empty($AO_banner)) {
          $banner_resp = wp_remote_get("http://misc.optimizingmatters.com/autoptimize_news.html");
          if (!is_wp_error($banner_resp)) {
            if (wp_remote_retrieve_response_code($banner_resp)=="200") {
                $AO_banner = wp_kses_post(wp_remote_retrieve_body($banner_resp));
                set_transient("autoptimize_banner",$AO_banner,DAY_IN_SECONDS);
            }
          }
        }
        echo $AO_banner;
      }
      ?>
      <li><?php _e("Need help? <a href='https://wordpress.org/plugins/autoptimize/faq/'>Check out the FAQ here</a>.","autoptimize"); ?></li>
      <li><?php _e("Happy with Autoptimize?","autoptimize"); ?><br /><a href="<?php echo network_admin_url(); ?>plugin-install.php?tab=search&type=author&s=optimizingmatters"><?php _e("Try my other plugins!","autoptimize"); ?></a></li>
      </ul>
    </div>
    <div style="margin-left:10px;margin-top:-5px;">
        <h2>
          <?php _e("futtta about","autoptimize") ?>
          <select id="feed_dropdown" >
              <option value="1"><?php _e("Autoptimize","autoptimize") ?></option>
              <option value="2"><?php _e("WordPress","autoptimize") ?></option>
              <option value="3"><?php _e("Web Technology","autoptimize") ?></option>
          </select>
        </h2>
        <div id="futtta_feed">
          <div id="autoptimizefeed">
            <?php getFutttaFeeds("http://feeds.feedburner.com/futtta_autoptimize"); ?>
          </div>
          <div id="wordpressfeed">
            <?php getFutttaFeeds("http://feeds.feedburner.com/futtta_wordpress"); ?>
          </div>
          <div id="webtechfeed">
            <?php getFutttaFeeds("http://feeds.feedburner.com/futtta_webtech"); ?>
          </div>
        </div>
    </div>
    <div style="float:right;margin:50px 15px;"><a href="http://blog.futtta.be/2013/10/21/do-not-donate-to-me/" target="_blank"><img width="100px" height="85px" src="<?php echo plugins_url().'/'.plugin_basename(dirname(__FILE__)).'/../img/do_not_donate_smallest.png'; ?>" title="<?php _e("Do not donate for this plugin!","autoptimize"); ?>"></a></div>
  </div>
  <?php
}

// Fetch Futta news feeds
function getFutttaFeeds($url) {
  if (apply_filters('autoptimize_settingsscreen_remotehttp',true)) {
    $rss = fetch_feed( $url );
    $maxitems = 0;

    if ( ! is_wp_error( $rss ) ) {
      $maxitems = $rss->get_item_quantity( 7 ); 
      $rss_items = $rss->get_items( 0, $maxitems );
    }
    ?>
    <ul>
      <?php if ( $maxitems == 0 ) : ?>
          <li><?php _e( 'No items', 'autoptimize' ); ?></li>
      <?php else : ?>
        <?php foreach ( $rss_items as $item ) : ?>
          <li>
            <a href="<?php echo esc_url( $item->get_permalink() ); ?>"
              title="<?php printf( __( 'Posted %s', 'autoptimize' ), $item->get_date('j F Y | g:i a') ); ?>">
              <?php echo esc_html( $item->get_title() ); ?>
            </a>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
    <?php
  }
}

?>