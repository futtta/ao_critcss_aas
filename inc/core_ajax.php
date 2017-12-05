<?php

// Ajax handler to obtain a critical CSS file from the filesystem
function critcss_fetch_callback() {

  // Check referer
  check_ajax_referer('fetch_critcss_nonce', 'critcss_fetch_nonce');

  // Initialize error flag
  $error = TRUE;

  // Allow no content for MANUAL rules (as they may not exist just yet)
  if (current_user_can('manage_options') && empty($_POST['critcssfile'])) {
    $content = '';
    $error   = FALSE;

  // Or check user permissios and filename
  } elseif (current_user_can('manage_options') && critcss_check_filename($_POST['critcssfile'])) {

    // Set file path and obtain its content
    $critcssfile = AO_CCSS_DIR . strip_tags($_POST['critcssfile']);
    if (file_exists($critcssfile)) {
      $content = file_get_contents($critcssfile);
      $error   = FALSE;
    }
  }

  // Prepare response
  if ($error) {
    $response['code']   = '500';
    $response['string'] = 'Error reading file ' . $critcssfile . '.';
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

  // Allow empty contents for MANUAL rules (as they are fetched later)
  if (current_user_can('manage_options') && empty($_POST['critcssfile'])) {
    $critcssfile = FALSE;
    $status      = TRUE;

  // Or check user permissios and filename
  } elseif (current_user_can('manage_options') && critcss_check_filename($_POST['critcssfile'])) {

    // If there is content and it's valid, write the file
    if ($critcsscontents && ao_ccss_check_contents($critcsscontents)) {

      // Set file path and content
      $critcssfile     = AO_CCSS_DIR . strip_tags($_POST['critcssfile']);
      $critcsscontents = stripslashes($_POST['critcsscontents']);
      $status          = file_put_contents($critcssfile, $critcsscontents, LOCK_EX);

    // Or set as error
    } else {
      $error = TRUE;
    }

  // Or just set an error
  } else {
    $error = TRUE;
  }

  // Prepare response
  if (!$status || $error) {
    $response['code']   ='500';
    $response['string'] = 'Error saving file ' . $critcssfile . '.';
  } else {
    $response['code']   = '200';
    if ($critcssfile) {
      $response['string'] = 'File ' . $critcssfile . ' saved.';
    } else {
      $response['string'] = 'Empty content do not need to be saved.';
    }
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

  // Initialize error and status flags
  $error  = TRUE;
  $status = FALSE;

  // Allow no file for MANUAL rules (as they may not exist just yet)
  if (current_user_can('manage_options') && empty($_POST['critcssfile'])) {
    $error   = FALSE;

  // Or check user permissios and filename
  } elseif (current_user_can('manage_options') && critcss_check_filename($_POST['critcssfile'])) {

    // Set file path and delete it
    $critcssfile = AO_CCSS_DIR . strip_tags($_POST['critcssfile']);
    if (file_exists($critcssfile)) {
      $status = unlink($critcssfile);
      $error  = FALSE;
    }
  }

  // Prepare response
  if ($error) {
    $response['code']   = '500';
    $response['string'] = 'Error removing file ' . $critcssfile . '.';
  } else {
    $response['code']   = '200';
    if ($status) {
      $response['string'] = 'File ' . $critcssfile . ' removed.';
    } else {
      $response['string'] = 'No file to be removed.';
    }
  }

  // Dispatch respose
  echo json_encode($response);

  // Close ajax request
  wp_die();
}
add_action('wp_ajax_rm_critcss', 'critcss_rm_callback');

// Try to avoid directory traversal when reading/writing/deleting critical CSS files
function critcss_check_filename($filename) {
  if (strpos($filename, "ccss_") !== 0) {
    return false;
  } else if (substr($filename,-4,4)!==".css") {
    return false;

  // Use WordPress core's sanitize_file_name to see if anything fishy is going on
  } else if (sanitize_file_name($filename) !== $filename) {
    return false;
  } else {
    return true;
  }
}

?>