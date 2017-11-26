<?php

// Check if CriticalCSS is desired

if ($ao_css_defer) {

  // Set AO behavior: disable minification to avoid double minifying and caching
  add_filter('autoptimize_filter_css_critcss_minify', '__return_false');
  add_filter('autoptimize_filter_css_defer_inline', 'ao_ccss_frontend', 10,1);

  /* BEGIN NOTE - ASK FRANK 03: This filter is unconditional. Should it be kept? See 06 bellow...
   *
   * FRANK'S ANSWER:
   * this one is hardcore functionality; it's where we hook into AO to get
   * all individual CSS parts to md5 them and add to the queue
   */
  // Add the filter to enqueue jobs for CriticalCSS cron
  add_filter('autoptimize_css_individual_style', 'ao_ccss_enqueue',10,2);
  /* END NOTE */
}

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

// Extend contidional tags
// NOTE: all tags are sorted
function ao_ccss_extend_types() {

  // Attach the conditional tags array
  global $ao_ccss_types;

  // Custom Post Types
  $cpts = get_post_types(
    array(
      'public'   => true,
      '_builtin' => false
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

// Apply CriticalCSS to frontend pages
function ao_ccss_frontend($inlined) {

  // Attach types and settings arrays
  global $ao_ccss_types;
  global $ao_ccss_rules;

  /* BEGIN NOTE - ASK FRANK 04: Is this still needed?
   *
   * FRANK'S ANSWER:
   * yes, this is hardcore functionality of the plugin; it looks at the
   * rules for "paths" ones and injects CCSS in the page if applicable
   */
  // Check for a valid CriticalCSS based on path to return its contents
  if (!empty($ao_ccss_rules['paths'])) {
    foreach ($ao_ccss_rules['paths'] as $paths => $ccss_file) {
      if ((strpos($_SERVER['REQUEST_URI'], str_replace(site_url(), '', $paths)) !== false) && ($paths !== 'dummy')) {
        if (file_exists(AO_CCSS_DIR . $ccss_file)) {
          return apply_filters('ao_ccss_filter', file_get_contents(AO_CCSS_DIR . $ccss_file));
        }
      }
    }
  }
  /* END NOTE */

  /* BEGIN NOTE - ASK FRANK 05: See first question...
   *
   * FRANK'S ANSWER:
   * also hardcore functionality of the plugin; it looks at the rules for
   * "type" ones and injects CCSS in the page if applicable
   */
  // Check for a valid CriticalCSS based on conditional tags to return its contents
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
  /* END NOTE */

  // Finally, inline the CriticalCSS or, in case it's missing, the entire CSS for the page
  if (!empty($inlined)) {
    return apply_filters('ao_ccss_filter', $inlined);
  } else {
    add_filter('autoptimize_filter_css_inline', '__return_true');
    return;
  }
}

// Enqueue jobs for asynchronous processing
function ao_ccss_enqueue($in) {

  // As AO could be set to provide different CSS'es for logged in users,
  // just enqueue jobs for NOT logged in users to avoid useless jobs
  if (!is_user_logged_in()) {

    error_log("CSS Input: \n" . $in);

    // Set flag, get request path and load an existing queue
    $upd_queue      = FALSE;
    $req_path      = $_SERVER['REQUEST_URI'];
    $ao_ccss_queue = json_decode(get_option('autoptimize_ccss_queue', ''), true);

    // First, check if the job does not exist already
    if (!array_key_exists($req_path, $ao_ccss_queue)) {

      // Define proporties for a NEW job
      $ao_ccss_queue[$req_path]['type']   = ao_ccss_get_type();
      $ao_ccss_queue[$req_path]['hashes'] = array(md5($in));
      $ao_ccss_queue[$req_path]['file']   = NULL;
      $ao_ccss_queue[$req_path]['jid']    = NULL;
      $ao_ccss_queue[$req_path]['jqstat'] = NULL;
      $ao_ccss_queue[$req_path]['jrstat'] = NULL;
      $ao_ccss_queue[$req_path]['jctime'] = microtime(TRUE);
      $ao_ccss_queue[$req_path]['jmtime'] = microtime(TRUE);
      $ao_ccss_queue[$req_path]['jftime'] = NULL;

      // Set update flag
      $upd_queue = TRUE;

    // Or the job already exists and may need some properties updates
    } else {

      // The job is NEW, most likely this is extra CSS file for the same page that needs a hash
      if (empty($ao_ccss_queue[$req_path]['jqstat'])) {

        // Hash CSS
        $hash = md5($in);

        // Add hash if it's not already in the job
        if (!in_array($hash, $ao_ccss_queue[$req_path]['hashes'])) {

          // Push new hash to its array and update flag
          $upd_queue = array_push($ao_ccss_queue[$req_path]['hashes'], $hash);
        }

      // The jobs is DONE, most likely its CSS files have changed and need to be requeued
      } elseif ($ao_ccss_queue[$req_path]['jqstat'] == 'JOB_DONE') {

        // Hash CSS
        $hash = md5($in);

        // We need to make sure the that at least one CSS has changed to update the job
        if (!in_array($hash, $ao_ccss_queue[$req_path]['hashes'])) {

          // Reset proporties for a DONE job with any of the hashes different
          $ao_ccss_queue[$req_path]['type']   = ao_ccss_get_type();
          $ao_ccss_queue[$req_path]['hashes'] = array(md5($in));
          $ao_ccss_queue[$req_path]['file']   = NULL;
          $ao_ccss_queue[$req_path]['jid']    = NULL;
          $ao_ccss_queue[$req_path]['jqstat'] = NULL;
          $ao_ccss_queue[$req_path]['jrstat'] = NULL;
          $ao_ccss_queue[$req_path]['jctime'] = microtime(TRUE);
          $ao_ccss_queue[$req_path]['jctime'] = microtime(TRUE);
          $ao_ccss_queue[$req_path]['jftime'] = NULL;

          // Set update flag
          $upd_queue = TRUE;
        }
      }
    }

    // Save the job to the queue
    if ($upd_queue) {
      update_option('autoptimize_ccss_queue', json_encode($ao_ccss_queue));
    }

  }

  // Return the input CSS as is
  return $in;
}

// Return the conditional tag for the page
function ao_ccss_get_type() {

  // Attach the conditional tags array
  global $ao_ccss_types;

  // Iterates over the array to match a type
  foreach ($ao_ccss_types as $type) {

    // Match custom post types
    if (strpos($type,'custom_post_') === 0) {
      if (get_post_type(get_the_ID()) === substr($type, 12)) {
        return $type;
      }

    // Match templates
    } elseif (strpos($type, 'template_') === 0) {
      if (is_page_template(substr($type, 9))) {
        return $type;
      }

    // Match all the other coditional tags
    } elseif (function_exists($type) && call_user_func($type)) {
      return $type;
    }
  }

  // If no valid type was found, just return false
  return false;
}

// Add is_blog_page conditional tag as per
// https://codex.wordpress.org/Conditional_Tags#The_Blog_Page
if (!function_exists("is_blog_page")) {
  function is_blog_page() {
    if (!is_front_page() && is_home()) {
      return true;
    } else {
      return false;
    }
  }
}
add_action('template_redirect', 'is_blog_page');

?>