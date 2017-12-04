<?php // NOTE: out of scope feeds panel ?>
<!-- BEGIN: Settings Futta Feeds -->
<div id="autoptimize_admin_feed" class="hidden">
  <div class="autoptimize_banner hidden">
    <ul>
    <?php
    if (apply_filters('autoptimize_settingsscreen_remotehttp',true)) {
      $AO_banner=get_transient('autoptimize_banner');
      if (empty($AO_banner)) {
        $banner_resp = wp_remote_get('http://misc.optimizingmatters.com/autoptimize_news.html');
        if (!is_wp_error($banner_resp)) {
          if (wp_remote_retrieve_response_code($banner_resp)=="200") {
              $AO_banner = wp_kses_post(wp_remote_retrieve_body($banner_resp));
              set_transient('autoptimize_banner', $AO_banner, DAY_IN_SECONDS);
          }
        }
      }
      echo $AO_banner;
    }
    ?>
    <li><?php _e('Need help? <a href="https://wordpress.org/plugins/autoptimize/faq/">Check out the FAQ here</a>.', 'autoptimize'); ?></li>
    <li><?php _e('Happy with Autoptimize?', 'autoptimize'); ?><br /><a href="<?php echo network_admin_url(); ?>plugin-install.php?tab=search&type=author&s=optimizingmatters"><?php _e('Try my other plugins!', 'autoptimize'); ?></a></li>
    </ul>
  </div>
  <div style="margin-left:10px;margin-top:-5px;">
      <h2>
        <?php _e('futtta about', 'autoptimize') ?>
        <select id="feed_dropdown" >
            <option value="1"><?php _e('Autoptimize', 'autoptimize') ?></option>
            <option value="2"><?php _e('WordPress', 'autoptimize') ?></option>
            <option value="3"><?php _e('Web Technology', 'autoptimize') ?></option>
        </select>
      </h2>
      <div id="futtta_feed">
        <div id="autoptimizefeed">
          <?php getFutttaFeeds('http://feeds.feedburner.com/futtta_autoptimize'); ?>
        </div>
        <div id="wordpressfeed">
          <?php getFutttaFeeds('http://feeds.feedburner.com/futtta_wordpress'); ?>
        </div>
        <div id="webtechfeed">
          <?php getFutttaFeeds('http://feeds.feedburner.com/futtta_webtech'); ?>
        </div>
      </div>
  </div>
  <div style="float:right;margin:50px 15px;">
    <a href="http://blog.futtta.be/2013/10/21/do-not-donate-to-me/" target="_blank">
      <img width="100px" height="85px" src="<?php echo plugins_url() . '/' . plugin_basename(dirname(__FILE__)) . '/../img/do_not_donate_smallest.png'; ?>" title="<?php _e('Do not donate for this plugin!', 'autoptimize'); ?>">
    </a>
  </div>
</div>
<!-- END: Settings Futta Feeds -->

<?php
// Fetch Futta news feeds
function getFutttaFeeds($url) {
  if (apply_filters('autoptimize_settingsscreen_remotehttp', true)) {
    $rss      = fetch_feed($url);
    $maxitems = 0;

    if ( ! is_wp_error( $rss ) ) {
      $maxitems  = $rss->get_item_quantity(7); 
      $rss_items = $rss->get_items(0, $maxitems);
    }
    ?>
    <ul>
      <?php if ( $maxitems == 0 ) : ?>
          <li><?php _e('No items', 'autoptimize'); ?></li>
      <?php else : ?>
        <?php foreach ($rss_items as $item) : ?>
          <li>
            <a href="<?php echo esc_url($item->get_permalink()); ?>"
              title="<?php printf(__('Posted %s', 'autoptimize'), $item->get_date('j F Y | g:i a')); ?>">
              <?php echo esc_html($item->get_title()); ?>
            </a>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
    <?php
  }
}
?>