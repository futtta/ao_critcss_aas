<?php

/* BEGIN NOTE - ASK FRANK 01:
 * What was intended with these settings?
 * Are these the 4.2 item from the spec (aka path-based exclusions)?
 * Even so, what are 'inpath', 'type' and their 'dummies'?
 *
 * FRANK'S ANSWER:
 * this is the wordpress setting that holds "the rules", where "inpath" is
 * for URL (path) based rules (and as such the UI should allow for users to
 * list pages that require specific CCSS) and "type" is for page type
 * (conditional tags) rules. dummy is just to have a default empty ruleset to
 * enforce (avoiding ugly errors of my code not getting a nice little array)
 */
// get critical CSS settings from WP options (+ fallback to always have placeholder) and json_decode to have a nice array
if (empty($ao_ccss_rules_raw)) {
  $ao_ccss_rules_raw = '{"inpath":{"dummy":"dummy"},"type":{"dummy":"dummy"}}';
}
$ao_ccss_rules = json_decode($ao_ccss_rules_raw, true);

// order 'inpath' by string length, longest path is most specific and will be treated first
if (!empty($ao_ccss_rules['inpath'])) {
  $keys = array_map('strlen', array_keys($ao_ccss_rules['inpath']));
  array_multisort($keys, SORT_DESC, $ao_ccss_rules['inpath']);
}
/* END NOTE */

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
  // NOTE: switch logic for release
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
  // NOTE: switch logic for release
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
  // NOTE: switch logic for release
  if (!function_exists('edd_is_checkout')) {
    $ao_ccss_types=array_merge($ao_ccss_types, array(
      'edd_is_checkout',
      'edd_is_failed_transaction_page',
      'edd_is_purchase_history_page',
      'edd_is_success_page'
    ));
  }

  // WooCommerce tags
  // NOTE: switch logic for release
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

// Check if CriticalCSS is desired
$ao_css_defer_on = get_option('autoptimize_css_defer', false);
if ($ao_css_defer_on) {

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

// Apply CriticalCSS to frontend pages
function ao_ccss_frontend($inlined) {

  // Attach types and settings arrays
  global $ao_ccss_types;
  global $ao_ccss_rules;

  /* BEGIN NOTE - ASK FRANK 04: Is this still needed?
   *
   * FRANK'S ANSWER:
   * yes, this is hardcore functionality of the plugin; it looks at the
   * rules for "inpath" ones and injects CCSS in the page if applicable
   */
  // Check for a valid CriticalCSS based on path to return its contents
  if (!empty($ao_ccss_rules['inpath'])) {
    foreach ($ao_ccss_rules['inpath'] as $inpath => $ccss_file) {
      if ((strpos($_SERVER['REQUEST_URI'], str_replace(site_url(), '', $inpath)) !== false) && ($inpath !== 'dummy')) {
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
  if (!empty($ao_ccss_rules['type'])) {
    foreach ($ao_ccss_rules['type'] as $type => $ccss_file) {
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

// Enqueue jobs for CriticalCSS cron
function ao_ccss_enqueue($in) {

  // As AO could be set to provide different CSS'es for logged in users,
  // just enqueue jobs for NOT logged in users to avoid useless jobs
  if (!is_user_logged_in()) {
    $ao_ccss_queue = json_decode(get_option('autoptimize_ccss_queue', ''), true);
    $ao_ccss_queue[$_SERVER['REQUEST_URI']]['hashes'][md5($in)] = md5($in);
    if (!array_key_exists('type', $ao_ccss_queue[$_SERVER['REQUEST_URI']]) || empty($ao_ccss_queue[$_SERVER['REQUEST_URI']]['type'])) {
      $ao_ccss_queue[$_SERVER['REQUEST_URI']]['type'] = ao_ccss_get_type();
    }
    update_option('autoptimize_ccss_queue', json_encode($ao_ccss_queue));
  }
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

// Ajax handler to obtain a critical CSS file from the filesystem
function critcss_fetch_callback() {

  // Check referer
  check_ajax_referer('fetch_critcss_nonce', 'critcss_fetch_nonce');

  // Check user permissios and file
  if ((current_user_can('manage_options')) && (critcss_check_filename($_POST['critcssfile']))) {

    // Set file path and obtain its content
    $critcssfile = AO_CCSS_DIR . strip_tags($_POST['critcssfile']);
    if (file_exists($critcssfile)) {
      $content = file_get_contents($critcssfile);
    }
  }

  // Prepare response
  if (!$content) {
    $response['code']   = '500';
    $response['string'] = 'Error reading file ' . $critcssfile;
  } else {
    $response['code']   = '200';
    $response['string'] = $content;
  }

  // Dispatch respose
  echo json_encode($response);

  // Close ajax request
  wp_die();
}
add_action('wp_ajax_fetch_critcss', 'critcss_fetch_callback');

// Ajax handler to write a critical CSS to the filesystem
function critcss_save_callback() {

  // Check referer
  check_ajax_referer('save_critcss_nonce', 'critcss_save_nonce');

  // Check user permissios and file
  if ((current_user_can('manage_options')) && (critcss_check_filename($_POST['critcssfile']))) {

    // Set file path, content and write its content
    $critcssfile     = AO_CCSS_DIR . strip_tags($_POST['critcssfile']);
    $critcsscontents = stripslashes($_POST['critcsscontents']);
    if (critcss_check_csscontents($critcsscontents)) {
      $status = file_put_contents($critcssfile, $critcsscontents, LOCK_EX);
    } else {
      $error = true;
    }
  } else {
    $error = true;
  }

  // Prepare response
  if (!$status || $error) {
    $response['code']   ='500';
    $response['string'] = 'Error saving file ' . $critcssfile;
  } else {
    $response['code']   = '200';
    $response['string'] = 'File ' . $critcssfile . ' saved';
  }

  // Dispatch respose
  echo json_encode($response);

  // Close ajax request
  wp_die();
}
add_action('wp_ajax_save_critcss', 'critcss_save_callback');

// Ajax handler to delete a critical CSS from the filesystem
function critcss_rm_callback() {

  // Check referer
  check_ajax_referer('rm_critcss_nonce', 'critcss_rm_nonce');

  // Check user permissios and file
  if ((current_user_can('manage_options')) && (critcss_check_filename($_POST['critcssfile']))) {

    // Set file path and delete it
    $critcssfile = AO_CCSS_DIR . strip_tags($_POST['critcssfile']);
    if (file_exists($critcssfile)) {
      $status = unlink($critcssfile);
    }
  }

  // Prepare response
  if (!$status) {
    $response['code']   = '500';
    $response['string'] = 'Error removing file ' . $critcssfile;
  } else {
    $response['code']   = '200';
    $response['string'] = 'File ' . $critcssfile . ' removed';
  }

  // Dispatch respose
  echo json_encode($response);

  // Close ajax request
  wp_die();
}
add_action('wp_ajax_rm_critcss', 'critcss_rm_callback');

// Try to avoid directory traversal when reading/writing/deleting critical CSS files
function critcss_check_filename($filename) {
  if (strpos($filename,"ccss_") !== 0) { return false; }
  else if (substr($filename,-4,4)!==".css") { return false; }
  // Use WordPress core's sanitize_file_name to see if anything fishy is going on
  else if (sanitize_file_name($filename) !== $filename) { return false; }
  else { return true; }
}

// Perform basic exploit avoidance and CSS validation
function critcss_check_csscontents($cssin) {

  // Try to avoid code injection
  $blacklist=array("#!","function(","<script","<?php");
  foreach ($blacklist as $blacklisted) {
    if (strpos($cssin,$blacklisted)!==false) {
      return false;
    }
  }

  // Check for most basics CSS structures
  $pinklist=array("{","}",":");
  foreach ($pinklist as $needed) {
    if (strpos($cssin,$needed)===false) {
      return false;
    }
  }
  return true;
}

/*
* is_blog_page does not exist in wordpress core
* cfr. https://codex.wordpress.org/Conditional_Tags#The_Blog_Page
* this fixes that
*/
if (!function_exists("is_blog_page")) {
  function is_blog_page() {
    if (!is_front_page() && is_home()) {
      return true;
    } else {
      return false;
    }
  }
}

?>