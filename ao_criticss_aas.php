<?php
/*
Plugin Name: Autoptimize Power-Up: CriticalCSS
Plugin URI: http://optimizingmatters/autoptimize/criticalcss/
Description: Let Autoptimize and CriticalCSS unleash your site performance and make it appear better than anyone in search results.
Author: Optimizing Matters
Version: 0.dev
Author URI: http://optimizingmatters.com/
Text Domain: ao_ccss
*/

// Includes
include('inc/settings.php');
include('inc/license.php');
//include('inc/cron.php');

// Set a constant with the directory to store critical CSS in
if (is_multisite()) {
  $blog_id = get_current_blog_id();
  define('AO_CCSS_DIR', WP_CONTENT_DIR . '/ao_ccss/' . $blog_id . '/');
} else {
  define('AO_CCSS_DIR', WP_CONTENT_DIR . '/ao_ccss/');
}

// Get settings
$ao_css_defer = get_option('autoptimize_css_defer');
$ao_ccss_key  = get_option('autoptimize_ccss_key');

// Add hidden submenu and register allowed settings
function ao_ccss_settings_init() {
  $hook = add_submenu_page(null, 'AO critcss', 'AO critcss', 'manage_options', 'ao_ccss_settings', 'ao_ccss_settings');
  register_setting('ao_css_options_group',  'autoptimize_css_defer_inline');
  register_setting('ao_ccss_options_group', 'autoptimize_ccss_key');
  register_setting('ao_ccss_queue_group',   'autoptimize_ccss_queue');
}
add_action('admin_menu','ao_ccss_settings_init');

// Add admin styles and scripts
function ao_ccss_admin_assets($hook) {
  if($hook != 'settings_page_ao_ccss_settings') {
    return;
  }

  // Stylesheets
  wp_enqueue_style('unslider',          plugins_url('css/unslider.css',      __FILE__));
  wp_enqueue_style('unslider-dots',     plugins_url('css/unslider-dots.css', __FILE__));
  wp_enqueue_style('ao_ccss_admin_css', plugins_url('css/admin.css',        __FILE__));

  // Scripts
  wp_enqueue_script('jqcookie',              plugins_url('js/jquery.cookie.min.js', __FILE__), array('jquery'),null,true);
  wp_enqueue_script('unslider',              plugins_url('js/unslider-min.js',      __FILE__), array('jquery'),null,true);
  wp_enqueue_script('ao_ccss_admin_scripts', plugins_url('js/admin.js',             __FILE__), array('jquery'),null,true);
}
add_action('admin_enqueue_scripts', 'ao_ccss_admin_assets');

// Hook up settings tab
function ao_ccss_add_tab($in) {
  $in = array_merge($in, array('ao_ccss_settings' => __('⚡ CriticalCSS', 'ao_ccss')));
  return $in;
}
add_filter('autoptimize_filter_settingsscreen_tabs','ao_ccss_add_tab');

?>