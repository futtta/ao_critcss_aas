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

    // Set critical CSS content
    $critcsscontents = stripslashes($_POST['critcsscontents']);

    // If there is content and it's valid, write the file
    if ($critcsscontents && ao_ccss_check_contents($critcsscontents)) {

      // Set file path and status
      $critcssfile = AO_CCSS_DIR . strip_tags($_POST['critcssfile']);
      $status      = file_put_contents($critcssfile, $critcsscontents, LOCK_EX);

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

// Ajax handler export settings
// NOTE: out of scope export settings
function ao_ccss_export_callback() {

  // Check referer
  check_ajax_referer('ao_ccss_export_nonce', 'ao_ccss_export_nonce');

  if (!class_exists('ZipArchive')) {
      $response['code'] = '500';
      $response['msg'] = 'PHP ZipArchive not present, cannot create zipfile';
      echo json_encode($response);
      wp_die();
  }

  // Init array, get options and prepare the raw object
  $settings               = array();
  $settings['rules']      = get_option('autoptimize_ccss_rules');
  $settings['additional'] = get_option('autoptimize_ccss_additional');
  $settings['viewport']   = get_option('autoptimize_ccss_viewport');
  $settings['finclude']   = get_option('autoptimize_ccss_finclude');
  $settings['rlimit']     = get_option('autoptimize_ccss_rlimit');
  $settings['noptimize']  = get_option('autoptimize_ccss_noptimize');
  $settings['debug']      = get_option('autoptimize_ccss_debug');
  $settings['key']        = get_option('autoptimize_ccss_key');

  // Initialize error flag
  $error = TRUE;

  // Check user permissios
  if (current_user_can('manage_options')) {

    // Prepare settings file path and content
    $exportfile = AO_CCSS_DIR . 'settings.json';
    $contents   = json_encode($settings);
    $status     = file_put_contents($exportfile, $contents, LOCK_EX);
    $error      = FALSE;
  }

  // Prepare archive
  $zipfile = AO_CCSS_DIR . date('Ymd-H\hi') . '_ao_ccss_settings.zip';
  $file    = pathinfo($zipfile, PATHINFO_BASENAME);
  $zip     = new ZipArchive();
  $ret     = $zip->open($zipfile, ZipArchive::CREATE);
  if ($ret !== TRUE) {
    $error = TRUE;
  } else {
    $zip->addFile(AO_CCSS_DIR . 'settings.json', 'settings.json');
    if (file_exists(AO_CCSS_DIR . 'queue.json')) {
      $zip->addFile(AO_CCSS_DIR . 'queue.json', 'queue.json');
    }
    $options = array('add_path' => './', 'remove_all_path' => TRUE);
    $zip->addGlob(AO_CCSS_DIR . '*.css', 0, $options);
    $zip->close();
  }

  // Prepare response
  if (!$status || $error) {
    $response['code'] ='500';
    $response['msg']  = 'Error saving file ' . $file . ', code: ' . $ret;
  } else {
    $response['code'] = '200';
    $response['msg']  = 'File ' . $file . ' saved.';
    $response['file']  = $file;
  }

  // Dispatch respose
  echo json_encode($response);

  // Close ajax request
  wp_die();
}
add_action('wp_ajax_ao_ccss_export', 'ao_ccss_export_callback');

// Ajax handler import settings
// NOTE: out of scope import settings
function ao_ccss_import_callback() {

  // Check referer
  check_ajax_referer('ao_ccss_import_nonce', 'ao_ccss_import_nonce');

  // Initialize error flag
  $error  = FALSE;

  // Process an uploaded file with no errors
  if (!$_FILES['file']['error']) {
    // Save file to the cache directory
    $zipfile = AO_CCSS_DIR . $_FILES['file']['name'];
    move_uploaded_file($_FILES['file']['tmp_name'], $zipfile);

    // Extract archive
    $zip = new ZipArchive;
    if ($zip->open($zipfile) === TRUE) {
      $zip->extractTo(AO_CCSS_DIR);
      $zip->close();
    } else {
      $error = 'extracting';
    }

    // Archive extraction ok, continue settings importing
    if (!$error) {

      // Settings file
      $importfile = AO_CCSS_DIR . 'settings.json';

      if (file_exists($importfile)) {

        // Get settings and turn them into an object
        $settings = json_decode(file_get_contents($importfile), TRUE);

        // Update options
        update_option('autoptimize_ccss_rules',      $settings['rules']);
        update_option('autoptimize_ccss_additional', $settings['additional']);
        update_option('autoptimize_ccss_viewport',   $settings['viewport']);
        update_option('autoptimize_ccss_finclude',   $settings['finclude']);
        update_option('autoptimize_ccss_rlimit',     $settings['rlimit']);
        update_option('autoptimize_ccss_noptimize',  $settings['noptimize']);
        update_option('autoptimize_ccss_debug',      $settings['debug']);
        update_option('autoptimize_ccss_key',        $settings['key']);

      // Settings file doesn't exist, update error flag
      } else {
        $error = 'settings file does not exist';
      }
    }
  }

  // Prepare response
  if ($error) {
    $response['code'] ='500';
    $response['msg']  = 'Error importing settings: ' . $error;
  } else {
    $response['code'] = '200';
    $response['msg']  = 'Settings imported successfully';
  }

  // Dispatch respose
  echo json_encode($response);

  // Close ajax request
  wp_die();
}
add_action('wp_ajax_ao_ccss_import', 'ao_ccss_import_callback');

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
