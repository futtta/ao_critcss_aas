<?php

// Check if CriticalCSS is desired
if ($ao_css_defer) {

  // Set AO behavior: disable minification to avoid double minifying and caching
  add_filter('autoptimize_filter_css_critcss_minify', '__return_false');
  add_filter('autoptimize_filter_css_defer_inline', 'ao_ccss_frontend', 10,1);

  // Add the filter to enqueue jobs for CriticalCSS cron
  add_filter('autoptimize_action_css_hash', 'ao_ccss_enqueue',10,2);

  // Order paths by length, as longest ones have greater priority in the rules
  if (!empty($ao_ccss_rules['paths'])) {
    $keys = array_map('strlen', array_keys($ao_ccss_rules['paths']));
    array_multisort($keys, SORT_DESC, $ao_ccss_rules['paths']);
  }

  // Add an array with default WordPress's conditional tags
  // NOTE: these tags are sorted
  $ao_ccss_types = array(
    'is_404',
    'is_archive',
    'is_author',
    'is_blog_page',
    'is_category',
    'is_front_page',
    'is_home',
    'is_page',
    'is_post',
    'is_search',
    'is_single',
    'is_sticky'
  );

  // Extend conditional tags on pugin initalization
  add_action('init', 'ao_ccss_extend_types');
}

// Apply CriticalCSS to frontend pages
// NOTE: implements section 4, id 1 of the specs
function ao_ccss_frontend($inlined) {

  // Attach types and settings arrays
  global $ao_ccss_types;
  global $ao_ccss_rules;

  // Check for a valid CriticalCSS based on path to return its contents
  // NOTE: implements section 4, id 1.1 of the specs (for paths)
  if (!empty($ao_ccss_rules['paths'])) {
    foreach ($ao_ccss_rules['paths'] as $paths => $ccss_file) {
      if ((strpos($_SERVER['REQUEST_URI'], str_replace(site_url(), '', $paths)) !== FALSE) && ($paths !== 'dummy')) {
        if (file_exists(AO_CCSS_DIR . $ccss_file)) {
          return apply_filters('ao_ccss_filter', file_get_contents(AO_CCSS_DIR . $ccss_file));
        }
      }
    }
  }

  // Check for a valid CriticalCSS based on conditional tags to return its contents
  // NOTE: implements section 4, id 1.1 of the specs (for types)
  if (!empty($ao_ccss_rules['types'])) {
    foreach ($ao_ccss_rules['types'] as $type => $ccss_file) {
      if (in_array($type, $ao_ccss_types) && file_exists(AO_CCSS_DIR . $ccss_file)) {
        if (strpos($type, 'custom_post_') === 0) {
          if (get_post_type(get_the_ID()) === substr($type, 12)) {
            return apply_filters('ao_ccss_filter', file_get_contents(AO_CCSS_DIR . $ccss_file));
          }
        } elseif (strpos($type, 'template_') === 0) {
          if (is_page_template(substr($type, 9))) {
            return apply_filters('ao_ccss_filter', file_get_contents(AO_CCSS_DIR . $ccss_file));
          }
        } elseif (function_exists($type) && call_user_func($type)) {
          return apply_filters('ao_ccss_filter', file_get_contents(AO_CCSS_DIR . $ccss_file));
        }
      }
    }
  }

  // Finally, inline the CriticalCSS or, in case it's missing, the entire CSS for the page
  // NOTE: implements section 4, id 1.2 of the specs
  if (!empty($inlined)) {
    return apply_filters('ao_ccss_filter', $inlined);
  } else {
    add_filter('autoptimize_filter_css_inline', '__return_true');
    return;
  }
}

// Extend contidional tags
// NOTE: all tags are sorted
function ao_ccss_extend_types() {

  // Attach the conditional tags array
  global $ao_ccss_types;

  // Custom Post Types
  $cpts = get_post_types(
    array(
      'public'   => TRUE,
      '_builtin' => FALSE
    ),
    'names',
    'and'
  );
  foreach ($cpts as $cpt) {
    $ao_ccss_types[] = "custom_post_" . $cpt;
  }

  // Templates
  $templates = wp_get_theme()->get_page_templates();
  foreach ($templates as $tplfile => $tplname) {
    $ao_ccss_types[] = 'template_' . $tplfile;
  }

  // bbPress tags
  // FIXME: switch logic for release
  if (!function_exists('is_bbpress')) {
    $ao_ccss_types = array_merge($ao_ccss_types, array(
      'bbp_is_bbpress',
      'bbp_is_favorites',
      'bbp_is_forum_archive',
      'bbp_is_replies_created',
      'bbp_is_reply_edit',
      'bbp_is_reply_move',
      'bbp_is_search',
      'bbp_is_search_results',
      'bbp_is_single_forum',
      'bbp_is_single_reply',
      'bbp_is_single_topic',
      'bbp_is_single_user',
      'bbp_is_single_user_edit',
      'bbp_is_single_view',
      'bbp_is_subscriptions',
      'bbp_is_topic_archive',
      'bbp_is_topic_edit',
      'bbp_is_topic_merge',
      'bbp_is_topic_split',
      'bbp_is_topic_tag',
      'bbp_is_topic_tag_edit',
      'bbp_is_topics_created',
      'bbp_is_user_home',
      'bbp_is_user_home_edit'
    ));
  }

  // BuddyPress tags
  // FIXME: switch logic for release
  if (!function_exists('is_buddypress')) {
    $ao_ccss_types=array_merge($ao_ccss_types, array(
      'bp_is_activation_page',
      'bp_is_activity',
      'bp_is_blogs',
      'bp_is_buddypress',
      'bp_is_change_avatar',
      'bp_is_create_blog',
      'bp_is_friend_requests',
      'bp_is_friends',
      'bp_is_friends_activity',
      'bp_is_friends_screen',
      'bp_is_group_admin_page',
      'bp_is_group_create',
      'bp_is_group_forum',
      'bp_is_group_forum_topic',
      'bp_is_group_home',
      'bp_is_group_invites',
      'bp_is_group_leave',
      'bp_is_group_members',
      'bp_is_group_single',
      'bp_is_groups',
      'bp_is_messages',
      'bp_is_messages_compose_screen',
      'bp_is_messages_conversation',
      'bp_is_messages_inbox',
      'bp_is_messages_sentbox',
      'bp_is_my_activity',
      'bp_is_my_blogs',
      'bp_is_notices',
      'bp_is_profile_edit',
      'bp_is_register_page',
      'bp_is_settings_component',
      'bp_is_user',
      'bp_is_user_profile',
      'bp_is_wire'
    ));
  }

  // Easy Digital Downloads (EDD) tags
  // FIXME: switch logic for release
  if (!function_exists('edd_is_checkout')) {
    $ao_ccss_types=array_merge($ao_ccss_types, array(
      'edd_is_checkout',
      'edd_is_failed_transaction_page',
      'edd_is_purchase_history_page',
      'edd_is_success_page'
    ));
  }

  // WooCommerce tags
  // FIXME: remove 'woo_' prefix in the frontend logic
  // FIXME: switch logic for release
  if (!class_exists('WooCommerce')) {
    $ao_ccss_types = array_merge($ao_ccss_types, array(
      'woo_is_account_page',
      'woo_is_cart',
      'woo_is_checkout',
      'woo_is_product',
      'woo_is_product_category',
      'woo_is_product_tag',
      'woo_is_shop',
      'woo_is_wc_endpoint_url',
      'woo_is_woocommerce'
    ));
  }

  // Sort values
  sort($ao_ccss_types);
}

// Add is_blog_page conditional tag as per
// https://codex.wordpress.org/Conditional_Tags#The_Blog_Page
if (!function_exists("is_blog_page")) {
  function is_blog_page() {
    if (!is_front_page() && is_home()) {
      return TRUE;
    } else {
      return FALSE;
    }
  }
}
add_action('template_redirect', 'is_blog_page');

// Get viewport size
function ao_ccss_viewport() {

  // Attach viewport option
  global $ao_ccss_viewport;

  // Prepare viewport array
  $viewport = array();

  // Viewport Width
  if (!empty($ao_ccss_viewport['w'])) {
    $viewport['w'] = $ao_ccss_viewport['w'];
  } else {
    $viewport['w'] = '';
  }

  // Viewport Height
  if (!empty($ao_ccss_viewport['h'])) {
    $viewport['h'] = $ao_ccss_viewport['h'];
  } else {
    $viewport['h'] = '';
  }

  return $viewport;
}

// Perform basic exploit avoidance and CSS validation
function ao_ccss_check_contents($ccss) {

  // Try to avoid code injection
  $blacklist = array("#!", "function(", "<script", "<?php");
  foreach ($blacklist as $blacklisted) {
    if (strpos($ccss, $blacklisted) !== FALSE) {
      return FALSE;
    }
  }

  // Check for most basics CSS structures
  $pinklist = array("{", "}", ":");
  foreach ($pinklist as $needed) {
    if (strpos($ccss, $needed) === FALSE) {
      return FALSE;
    }
  }

  // Return true if file critical CSS is sane
  return TRUE;
}

// Commom logging facility
function ao_ccss_log($msg, $lvl) {

  // Attach debug option
  global $ao_ccss_debug;

  // Prepare log levels, where accepted $lvl are:
  // 1: II (for info)
  // 2: EE (for error)
  // 3: DD (for debug)
  // Default: UU (for unkown)
  $level = FALSE;
  switch ($lvl) {
    case 1:
      $level = 'II';
      break;
    case 2:
      $level = 'EE';
      break;
    case 3:
      // Debug allowed only if enabled
      if ($ao_ccss_debug)
        $level = 'DD';
      break;
    default:
      $level = 'UU';
  }

  // Prepare and write a log message if there's a valid level
  if ($level) {

    // Set log file
    $logfile = AO_CCSS_DIR . 'messages.log';

    // Prepare message
    $message = date('c') . ' - [' . $level . '] ' . $msg . "\n";

    //Write message to log file
    error_log($message, 3,  $logfile);
  }
}

?>