<?php

// NOTE: implements section 4 of the specs

/*
 * cronned queue-processing
 * 
 * todo:
 * for each entry in queue check if for "page type" & "CSS hash" an critcss-file exists
 *  if not check if there is a critcss job id
 *   if not use criticalcss.com API to create crit css + update queue with critcss job id
 *   if yes use job id to check if critcss has finished and if yes get & write to file & update $aocritSettings
 *  if yes remove from queue and continue
 * only run if critcss is active (and deactivate cron if no AO or no "inline & defer"
 */

// Add a 5 seconds interval to WP-Cron
function ao_ccss_interval($schedules) {
   $schedules['5sec'] = array(
      'interval' => 5,
      'display' => __('Every 5 Seconds (FOR DEVELOPMENT ONLY)')
   );
   return $schedules;
}
add_filter('cron_schedules', 'ao_ccss_interval');

// Add queue to a registered event
add_action('ao_ccss_queue', 'ao_ccss_queue_control');

// The queue execution backend
function ao_ccss_queue_control() {

  // Attach required arrays
  global $ao_ccss_queue;

  // Initialize job counter
  $jc = 1;

  // Iterates over the entire queue
  foreach ($ao_ccss_queue as $path => $jprops) {

    // Initialize queue updte flag
    $queue_update = FALSE;

    // If this is not the first job, wait 5 seconds before process next job due criticalcss.com API limits
    if ($jc > 1) {
      ao_ccss_log('Wait 5 seconds before process the next job due criticalcss.com API limits', 3);
      sleep(5);
    }

    // Process NEW jobs
    // NOTE: implements section 4, id 3.1 of the specs
    if ($jprops['jqstat'] == 'NEW') {

      // Log the new job
      ao_ccss_log('Found NEW job with local ID <' . $jprops['ljid'] . '>, starting its queue processing', 3);

      // Compare and update job hash if required
      $hash = ao_ccss_compare_job_hashes($jprops['ljid'], $jprops['hash'], $jprops['hashes']);
      if ($hash) {
        $jprops['hash'] = $hash;
      }

      // If job hash is new or different of a previous one
      if ($hash) {

        // Dispatch the job generation request
        $apireq = ao_ccss_api_generate($path);

        // NOTE: All the following condigitons maps to the ones in admin_settings_queue.js.php

        // Request has a valid result
        // Process a PENDING job
        if ($apireq['job']['status'] == 'JOB_QUEUED' || $apireq['job']['status'] == 'JOB_ONGOING') {

          // Update job properties
          $jprops['jid']    = $apireq['job']['id'];
          $jprops['jqstat'] = $apireq['job']['status'];
          $queue_update     = TRUE;
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> generation request successful, remote id <' . $jprops['jid'] . '>, status now is <' . $jprops['jqstat'] . '>', 3);

        // Request has failed
        } else {

          // Update job properties
          $jprops['jqstat'] = 'JOB_UNKNOWN';
          $jprops['jftime'] = microtime(TRUE);
          $queue_update     = TRUE;
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> generation request has an UNKNOWN condition, status now is <' . $jprops['jqstat'] . '>, check log messages above for more information', 2);
        }

      // Job hash is equal a previous one
      } else {

        // Update job status and finish time
        $jprops['jqstat'] = 'JOB_DONE';
        $jprops['jftime'] = microtime(TRUE);
        $queue_update     = TRUE;
      }

    // Process QUEUED and ONGOING jobs
    // NOTE: implements section 4, id 3.2 of the specs
    } elseif ($jprops['jqstat'] == 'JOB_QUEUED' || $jprops['jqstat'] == 'JOB_ONGOING') {

      // Dispatch the job generation request
      $apireq = ao_ccss_api_results($jprops['jid']);

      // NOTE: All the following condigitons maps to the ones in admin_settings_queue.js.php

      // Request has a valid result
      // Process a PENDING job
      if ($apireq['status'] == 'JOB_QUEUED' || $apireq['status'] == 'JOB_ONGOING') {

        // Update job properties
        $jprops['jqstat'] = $apireq['status'];
        $queue_update     = TRUE;
        ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successful, remote id <' . $jprops['jid'] . '>, status <' . $jprops['jqstat'] . '> unchanged', 3);

      // Process a DONE job
      } elseif ($apireq['status'] == 'JOB_DONE') {

        // Process GOOD jobs
        if ($apireq['resultStatus'] == 'GOOD' || $apireq['validationStatus'] == 'GOOD') {

          // Update job properties
          $jprops['file']   = ao_ccss_save_file($apireq['css'], FALSE);
          $jprops['jqstat'] = $apireq['status'];
          $jprops['jrstat'] = $apireq['resultStatus'];
          $jprops['jvstat'] = $apireq['validationStatus'];
          $jprops['jftime'] = microtime(TRUE);
          $queue_update     = TRUE;
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successful, remote id <' . $jprops['jid'] . '>, status <' . $jprops['jqstat'] . '>, file saved <' . $jprops['file'] . '>', 3);

        // Process REVIEW jobs
        } elseif ($apireq['resultStatus'] == 'GOOD' && ($apireq['validationStatus'] == 'WARN' || $apireq['validationStatus'] == 'BAD')) {

          // Update job properties
          $jprops['file']   = ao_ccss_save_file($apireq['css'], TRUE);
          $jprops['jqstat'] = $apireq['status'];
          $jprops['jrstat'] = $apireq['resultStatus'];
          $jprops['jvstat'] = $apireq['validationStatus'];
          $jprops['jftime'] = microtime(TRUE);
          $queue_update     = TRUE;
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successful, remote id <' . $jprops['jid'] . '>, status <' . $jprops['jqstat'] . ', file saved <' . $jprops['file'] . '> but requires REVIEW', 3);

        // Process ERROR jobs
        } elseif ($apireq['resultStatus'] != 'GOOD' || ($apireq['validationStatus'] != 'GOOD' || $apireq['validationStatus'] != 'WARN' || $apireq['validationStatus'] != 'BAD')) {

          // Update job properties
          $jprops['jqstat'] = $apireq['status'];
          $jprops['jrstat'] = $apireq['resultStatus'];
          $jprops['jvstat'] = $apireq['validationStatus'];
          $jprops['jftime'] = microtime(TRUE);
          $queue_update     = TRUE;
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successfull but job FAILED, status now is <' . $jprops['jqstat'] . '>, check log messages above for more information', 2);

        // Process UNKNOWN jobs
        } else {

          // Update job properties
          $jprops['jqstat'] = 'JOB_UNKNOWN';
          $jprops['jrstat'] = $apireq['resultStatus'];
          $jprops['jvstat'] = $apireq['validationStatus'];
          $jprops['jftime'] = microtime(TRUE);
          $queue_update     = TRUE;
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successfull but job FAILED, status now is <' . $jprops['jqstat'] . '>, check log messages above for more information', 2);

        }

      // Process a FAILED job
      } elseif ($apireq['status'] == 'JOB_FAILED') {

        // Update job properties
        $jprops['jqstat'] = $apireq['status'];
        $jprops['jftime'] = microtime(TRUE);
        $queue_update     = TRUE;
        ao_ccss_log('Job id <' . $jprops['ljid'] . '> generation request successfull but job FAILED, status now is <' . $jprops['jqstat'] . '>, check log messages above for more information', 2);

      // Request has failed
      } elseif (empty($apireq) || $apireq['status'] == 'JOB_UNKNOWN') {

        // Update job properties
        $jprops['jqstat'] = 'JOB_UNKNOWN';
        $jprops['jftime'] = microtime(TRUE);
        $queue_update     = TRUE;
        ao_ccss_log('Job id <' . $jprops['ljid'] . '> generation request has an UNKNOWN condition, status now is <' . $jprops['jqstat'] . '>, check log messages above for more information', 2);
      }
    }

    // Persist update job to the queue
    // NOTE: implements section 4, id 3.2.1 of the specs
    if ($queue_update) {
      $ao_ccss_queue[$path] = $jprops;
      $ao_ccss_queue_raw = json_encode($ao_ccss_queue);
      update_option('autoptimize_ccss_queue', $ao_ccss_queue_raw);
      ao_ccss_log('Queue updated by job id <' . $jprops['ljid'] . '>', 3);

    // Or just log if no job was updated
    } else {
      ao_ccss_log('No queue updated required by job id <' . $jprops['ljid'] . '>', 3);
    }

    // Increment job counter
    $jc++;
  }
}

// Compare job hashes
function ao_ccss_compare_job_hashes($ljid, $hash, $hashes) {

  // Initialize hash checking flags
  $newhash  = FALSE;
  $diffhash = FALSE;

  // Hash checks for new jobs
  if (empty($hash) && count($hashes) == 1) {

    // Set job hash
    $hash    = $hashes[0];
    $newhash = TRUE;
    ao_ccss_log('Job id <' . $ljid . '> has an empty hash, updating with SINGLE hash <' . $hash . '>', 3);

  // Check if job has more than one hash to concatenate and hash them
  } elseif (empty($hash) && count($hashes) >= 1) {

    // Loop through hashes to concatenate them
    $nhash = '';
    foreach ($hashes as $shash) {
      $nhash .= $shash;
    }

    // Set job hash
    $hash    = md5($nhash);
    $newhash = TRUE;
    ao_ccss_log('Job id <' . $ljid . '> has an empty hash, updating with COMPOSITE hash <' . $hash . '>', 3);
  }

  // Hash checks for existing jobs
  $hash_type = '';
  if (!empty($hash) && $newhash == FALSE) {

    // Initialize temporary hash
    $tmphash = '';

    // Check if job has only one hash
    if (count($hashes) == 1) {

      // Set job hash
      $tmphash   = $hashes[0];
      $hash_type = 'SINGLE';

    // Check if job has more than one hash to concatenate and hash them
    } elseif (empty($hash) && count($hashes) >= 1) {

      // Loop through hashes to concatenate them
      $tmphash = '';
      foreach ($hashes as $shash) {
        $tmphash .= $shash;
      }

      // Set job hash
      $tmphash   = md5($jhash);
      $hash_type = 'COMPOSITE';
    }

    // If temporary and job hashes are different, update it
    if ($tmphash != $hash) {
      ao_ccss_log('Job id <' . $ljid . '> hash <' . $hash . '> has changed, updating with new ' . $hash_type . ' hash <' . $tmphash . '>', 3);
      $hash     = $tmphash;
      $diffhash = TRUE;
    }
  }

  if ($diffhash || $newhash) {
    return $hash;
  } else {
    ao_ccss_log('Job id <' . $ljid . '> hash <' . $hash . '> has not changed since last processing', 3);
    return FALSE;
  }
}

// POST jobs to criticalcss.com and return responses
function ao_ccss_api_generate($path) {

  // Prepare full URL for request
  $src_url = get_site_url() . $path;

  // Log request start
  ao_ccss_log('criticalcss.com: POST generate request for path <' . $src_url . '>', 3);

  // Get key
  global $ao_ccss_key;
  $key = $ao_ccss_key;

  // Prepare the request
  $url  = esc_url_raw(AO_CCSS_API . 'generate');
  $args = array(
    'headers' => array(
      'Content-type'  => 'application/json; charset=utf-8',
      'authorization' => 'JWT ' . $key,
      'Connection'    => 'close'
    ),
    // Body must be JSON
    'body' => json_encode(
      array(
        'url' => $src_url,
        'aff' => 1
      )
    )
  );

  // Prepare and add viewport size to the body if available
  $viewport = ao_ccss_viewport();
  if (!empty($viewport['w']) && !empty($viewport['h'])) {
    $args['body']['width']  = $viewport['w'];
    $args['body']['height'] = $viewport['h'];
  }

  // Dispatch the request and store its response code
  $req  = wp_safe_remote_post($url, $args);
  $code = wp_remote_retrieve_response_code($req);
  $body = json_decode(wp_remote_retrieve_body($req), TRUE);

  // Response code is ok (200)
  if ($code == 200) {

    // Workaround criticalcss.com non-RESTful reponses
    if ($body['job']['status'] == 'JOB_QUEUED' || $body['job']['status'] == 'JOB_ONGOING') {

      // Log successful and return encoded request body
      ao_ccss_log('criticalcss.com: POST generate request for path <' . $src_url . '> replied successfully', 3);
      return $body;

    // Log failed request and return false
    } else {
      ao_ccss_log('criticalcss.com: POST generate request for path <' . $src_url . '> replied with code <' . $code . '> but with an error condition, body follows...', 2);
      ao_ccss_log($body, 2);
      return FALSE;
    }

  // Response code is anything else
  } else {

    // Log failed request and return false
    ao_ccss_log('criticalcss.com: POST generate request for path <' . $src_url . '> replied with error code <' . $code . '>, body follows...', 2);
    ao_ccss_log($body, 2);
    return FALSE;
  }
}

// GET jobs from criticalcss.com and return responses
function ao_ccss_api_results($jobid) {

  // Log request start
  ao_ccss_log('criticalcss.com: GET results request for remote job id <' . $jobid . '>', 3);

  // Get key
  global $ao_ccss_key;
  $key = $ao_ccss_key;

  // Prepare the request
  $url  = AO_CCSS_API . 'results?resultId=' . $jobid;
  $args = array(
    'headers' => array(
      'authorization' => 'JWT ' . $key,
      'Connection'    => 'close'
    ),
  );

  // Dispatch the request and store its response code
  $req  = wp_safe_remote_get($url, $args);
  $code = wp_remote_retrieve_response_code($req);
  $body = json_decode(wp_remote_retrieve_body($req), TRUE);

  // Response code is ok (200)
  if ($code == 200) {

    // Workaround criticalcss.com non-RESTful reponses
    if ($body['status'] == 'JOB_QUEUED' || $body['status'] == 'JOB_ONGOING' || $body['status'] == 'JOB_DONE' || $body['status'] == 'JOB_FAILED' || $body['status'] == 'JOB_UNKNOWN') {

      // Log successful and return encoded request body
      ao_ccss_log('criticalcss.com: GET results request for remote job id <' . $jobid . '> replied successfully', 3);
      return $body;

    // Log failed request and return false
    } else {
      ao_ccss_log('criticalcss.com: GET results request for remote job id <' . $jobid . '> replied with code <' . $code . "> but with an error condition, body follows...", 2);
      ao_ccss_log($body, 2);
      return FALSE;
    }

  // Response code is anything else
  } else {

    // Log failed request and return false
    ao_ccss_log('criticalcss.com: GET results request for remote job id <' . $jobid . '> replied with error code <' . $code . ">, body follows...", 2);
    ao_ccss_log($body, 2);
    return FALSE;
  }
}

// Save critical CSS into the filesystem and return its filename
function ao_ccss_save_file($ccss, $review) {

  // Prepare reivew mark
  if ($review) {
    $rmark = '_R';
  } else {
    $rmark = '';
  }

  // Prepare filename and content
  $filename = FALSE;
  $content  = stripslashes($ccss);

  // Sanitize content, set filename and try to save file
  if (ao_ccss_check_contents($content)) {
    $file     = AO_CCSS_DIR . 'ccss_' . md5($ccss) . $rmark . '.css';
    $status   = file_put_contents($file, $content, LOCK_EX);
    $filename = pathinfo($file, PATHINFO_BASENAME);

    ao_ccss_log('Critical CSS file saved as <' . $filename . '>, size in bytes is <' . $status . ">", 3);

    // If file has not been saved, reset filename
    if (!$status) {
      ao_ccss_log('Critical CSS file <' . $filename . '> could not be not saved', 2);
      $filename = FALSE;
    }
  }

  // Return filename or false
  return $filename;
}