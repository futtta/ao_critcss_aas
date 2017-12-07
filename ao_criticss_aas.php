<?php
/*
Plugin Name: Autoptimize Power-Up: CriticalCSS
Plugin URI: http://optimizingmatters/autoptimize/criticalcss/
Description: Let Autoptimize and CriticalCSS unleash your site performance and make it appear better than anyone in search results.
Author: Deny Dias on behalf of Optimizing Matters
Version: 0.dev
Author URI: http://optimizingmatters.com/
Text Domain: autoptimize
*/

// Get options
$ao_css_defer        = get_option('autoptimize_css_defer', FALSE);
$ao_css_defer_inline = get_option('autoptimize_css_defer_inline');
$ao_ccss_key         = get_option('autoptimize_ccss_key');
$ao_ccss_rules_raw   = get_option('autoptimize_ccss_rules', FALSE);
$ao_ccss_queue_raw   = get_option('autoptimize_ccss_queue', FALSE);
$ao_ccss_viewport    = get_option('autoptimize_ccss_viewport', FALSE);
$ao_ccss_debug       = get_option('autoptimize_ccss_debug', FALSE);


// Setup the rules array
if (empty($ao_ccss_rules_raw)) {
  $ao_ccss_rules['paths'] = [];
  $ao_ccss_rules['types'] = [];
} else {
  $ao_ccss_rules = json_decode($ao_ccss_rules_raw, TRUE);
}

// Setup the queue array
if (empty($ao_ccss_queue_raw)) {
  $ao_ccss_queue = [];
} else {
  $ao_ccss_queue = json_decode($ao_ccss_queue_raw, TRUE);
}

// Required libs
require_once('inc/core.php');
require_once('inc/core_ajax.php');
require_once('inc/core_enqueue.php');
require_once('inc/admin_settings.php');
require_once('inc/admin_settings_license.php');
require_once('inc/admin_settings_rules.php');
require_once('inc/admin_settings_queue.php');
require_once('inc/admin_settings_adv.php');
require_once('inc/cron.php');

// Define a constant with the directory to store critical CSS in
if (is_multisite()) {
  $blog_id = get_current_blog_id();
  define('AO_CCSS_DIR', WP_CONTENT_DIR . '/cache/ao_ccss/' . $blog_id . '/');
} else {
  define('AO_CCSS_DIR', WP_CONTENT_DIR . '/cache/ao_ccss/');
}

// Define constants for criticalcss.com base path and API endpoints
define('AO_CCSS_URL', 'https://criticalcss.com');
define('AO_CCSS_API', AO_CCSS_URL . '/api/premium/');

// Add hidden submenu and register allowed settings
function ao_ccss_settings_init() {
  $hook = add_submenu_page(NULL, 'Autoptimize CriticalCSS Power-Up', 'Autoptimize CriticalCSS Power-Up', 'manage_options', 'ao_ccss_settings', 'ao_ccss_settings');
  register_setting('ao_ccss_options_group', 'autoptimize_ccss_key');
  register_setting('ao_ccss_options_group', 'autoptimize_ccss_rules');
  register_setting('ao_ccss_options_group', 'autoptimize_ccss_queue');
  register_setting('ao_ccss_options_group', 'autoptimize_ccss_viewport');
  register_setting('ao_ccss_options_group', 'autoptimize_ccss_debug');
  register_setting('ao_ccss_options_group', 'autoptimize_css_defer_inline');
}
add_action('admin_menu','ao_ccss_settings_init');

// Add admin styles and scripts
function ao_ccss_admin_assets($hook) {
  if($hook != 'settings_page_ao_ccss_settings') {
    return;
  }

  // Stylesheets to add
  wp_enqueue_style('wp-jquery-ui-dialog');
  wp_enqueue_style('unslider',          plugins_url('lib/css/unslider.css',         __FILE__));
  wp_enqueue_style('unslider-dots',     plugins_url('lib/css/unslider-dots.css',    __FILE__));
  wp_enqueue_style('ao-tablesorter',    plugins_url('css/ao-tablesorter/style.css', __FILE__));
  wp_enqueue_style('ao-ccss-admin-css', plugins_url('css/admin_styles.css',         __FILE__));

  // Scripts to add
  wp_enqueue_script('jquery-ui-dialog',      array( 'jquery' ));
  wp_enqueue_script('jqcookie',              plugins_url('lib/js/jquery.cookie.min.js',      __FILE__), array('jquery'), NULL, TRUE);
  wp_enqueue_script('unslider',              plugins_url('lib/js/unslider-min.js',           __FILE__), array('jquery'), NULL, TRUE);
  wp_enqueue_script('md5',                   plugins_url('lib/js/md5.min.js',                __FILE__), NULL, NULL, TRUE);
  wp_enqueue_script('tablesorter',           plugins_url('lib/js/jquery.tablesorter.min.js', __FILE__), array('jquery'), NULL, TRUE);
  wp_enqueue_script('ao-ccss-admin-license', plugins_url('js/admin_settings.js',             __FILE__), array('jquery'), NULL, TRUE);
  wp_enqueue_script('ao-ccss-admin-feeds',   plugins_url('js/admin_settings_feeds.js',       __FILE__), array('jquery'), NULL, TRUE);
}
add_action('admin_enqueue_scripts', 'ao_ccss_admin_assets');

// Hook up settings tab
function ao_ccss_add_tab($in) {
  $in = array_merge($in, array('ao_ccss_settings' => '⚡ ' . __('CriticalCSS', 'autoptimize')));
  return $in;
}
add_filter('autoptimize_filter_settingsscreen_tabs', 'ao_ccss_add_tab');

// Perform plugin activation tasks
function ao_ccss_activation() {

  // Create options with empty values
  add_option('autoptimize_ccss_key', '');
  add_option('autoptimize_ccss_rules', '');
  add_option('autoptimize_ccss_queue', '');
  add_option('autoptimize_ccss_viewport', '');
  add_option('autoptimize_ccss_debug', '');

  // Create a scheduled event for the queue
  // FIXME: change this to 10min for relase (also required in inc/cron.php)
  if (!wp_next_scheduled('ao_ccss_queue')) {
    wp_schedule_event(time(), '5sec', 'ao_ccss_queue');
  }
}
register_activation_hook(__FILE__, 'ao_ccss_activation');

// Perform plugin deactivation tasks
function ao_ccss_deactivation() {

  // Delete options
  delete_option('autoptimize_ccss_key');
  delete_option('autoptimize_ccss_rules');
  delete_option('autoptimize_ccss_queue');
  delete_option('autoptimize_ccss_viewport');
  delete_option('autoptimize_ccss_debug');

  // Remove the scheduled event of the queue
  wp_clear_scheduled_hook('ao_ccss_queue');
}
register_deactivation_hook(__FILE__, 'ao_ccss_deactivation');

?>