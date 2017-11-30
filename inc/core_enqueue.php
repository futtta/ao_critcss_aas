<?php

// Enqueue jobs for asynchronous processing
// NOTE: implements section 4, id 2 of the specs
function ao_ccss_enqueue($hash) {

  // As AO could be set to provide different CSS'es for logged in users,
  // just enqueue jobs for NOT logged in users to avoid useless jobs
  if (!is_user_logged_in()) {

    // Load the queue, get request path and page type, and initialize the queue update flag
    $ao_ccss_queue_raw = get_option('autoptimize_ccss_queue', FALSE);
    $req_path          = $_SERVER['REQUEST_URI'];
    $req_type          = ao_ccss_get_type();
    $upq               = FALSE;

    // Setup the queue array
    if (empty($ao_ccss_queue_raw)) {
      $ao_ccss_queue = [];
    } else {
      $ao_ccss_queue = json_decode($ao_ccss_queue_raw, TRUE);
    }

    // This is a NEW job
    if (!array_key_exists($req_path, $ao_ccss_queue)) {

      // Define properties for a NEW job
      $ao_ccss_queue[$req_path]['type']   = $req_type;
      $ao_ccss_queue[$req_path]['hashes'] = array($hash);
      $ao_ccss_queue[$req_path]['hash']   = NULL;
      $ao_ccss_queue[$req_path]['file']   = NULL;
      $ao_ccss_queue[$req_path]['jid']    = NULL;
      $ao_ccss_queue[$req_path]['jqstat'] = 'NEW';
      $ao_ccss_queue[$req_path]['jrstat'] = NULL;
      $ao_ccss_queue[$req_path]['jctime'] = microtime(TRUE);
      $ao_ccss_queue[$req_path]['jmtime'] = microtime(TRUE);
      $ao_ccss_queue[$req_path]['jftime'] = NULL;

      // Set update flag
      $upq = TRUE;

    // This is an existing job
    } else {

      // The job is still NEW, most likely this is extra CSS file for the same page that needs a hash
      if ($ao_ccss_queue[$req_path]['jqstat'] == 'NEW') {

        // Add hash if it's not already in the job
        if (!in_array($hash, $ao_ccss_queue[$req_path]['hashes'])) {

          // Push new hash to its array and update flag
          $upq = array_push($ao_ccss_queue[$req_path]['hashes'], $hash);

          // Return from here as the hash array is already updated
          return TRUE;
        }

      // The jobs is DONE, most likely its CSS files have changed and need to be requeued
      } elseif ($ao_ccss_queue[$req_path]['jqstat'] == 'JOB_DONE') {

        // We need to make sure the that at least one CSS has changed to update the job
        if (!in_array($hash, $ao_ccss_queue[$req_path]['hashes'])) {

          // Reset properties for a DONE job with any of the hashes different
          $ao_ccss_queue[$req_path]['type']   = $req_type;
          $ao_ccss_queue[$req_path]['hashes'] = array($hash);
          $ao_ccss_queue[$req_path]['hash']   = NULL;
          $ao_ccss_queue[$req_path]['file']   = NULL;
          $ao_ccss_queue[$req_path]['jid']    = NULL;
          $ao_ccss_queue[$req_path]['jqstat'] = 'NEW';
          $ao_ccss_queue[$req_path]['jrstat'] = NULL;
          $ao_ccss_queue[$req_path]['jctime'] = microtime(TRUE);
          $ao_ccss_queue[$req_path]['jmtime'] = microtime(TRUE);
          $ao_ccss_queue[$req_path]['jftime'] = NULL;

          // Set update flag
          $upq = TRUE;
        }
      }
    }

    // Save the job to the queue and return
    if ($upq) {
      $ao_ccss_queue_raw = json_encode($ao_ccss_queue);
      update_option('autoptimize_ccss_queue', $ao_ccss_queue_raw);
      return TRUE;

    // Or just return false if no job whas added
    } else {
      return FALSE;
    }

  }
}

?>