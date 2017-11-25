<?php

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

?>