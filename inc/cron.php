<?php

// NOTE: implements section 4 of the specs

// Add a 5 seconds interval to WP-Cron
// FIXME: change this to 10min ('interval' => 600) for relase (also required in ../ao_criticcss_aas.php)
function ao_ccss_interval($schedules) {
   $schedules['5sec'] = array(
      'interval' => 5,
      'display' => __('Every 5 Seconds (FOR DEVELOPMENT ONLY)')
   );
   return $schedules;
}
add_filter('cron_schedules', 'ao_ccss_interval');

// Add queue control to a registered event
add_action('ao_ccss_queue', 'ao_ccss_queue_control');

// The queue execution backend
function ao_ccss_queue_control() {

  // Log queue start
  ao_ccss_log('Queue control started', 3);

  /**
   * Provide a debug facility for the queue
   *  This debug facility provides a way to easily force some queue behaviors useful for development and testing.
   *  To enable this feature, create the file AO_CCSS_DIR . 'queue.json' with a JSON object like the one bellow:
   *
   *  {"enable":bool,"htcode":int,"status":0|"str","resultStatus ":0|"str","validationStatus":0|"str"}
   *
   *  Where values are:
   *  - enable          : 0 or 1, enable or disable this debug facility straight from the file
   *  - htcode          : 0 or any HTTP reponse code (e.g. 2xx, 4xx, 5xx) to force API responses
   *  - status          : 0 or a valid value for 'status' (see 'Generating critical css - Job Status Types' in spec docs)
   *  - resultStatus    : 0 or a valid value for 'resultStatus' (see 'Appendix - Result status types' in the spec docs)
   *  - validationStatus: 0 or a valid value for 'validationStatus' (see 'Appendix - Validation status types' in the spec docs)
   *
   *  When properly set, queue will always finish a job with the declared settings above regardless of the real API responses.
   */
  // NOTE: out of scope queue debug
  $queue_debug      = FALSE;
  $queue_debug_file = AO_CCSS_DIR . strip_tags('queue.json');
  if (file_exists($queue_debug_file)) {
    $qdobj_raw = file_get_contents($queue_debug_file);
    $qdobj     = json_decode($qdobj_raw, TRUE);
    if ($qdobj) {
      if ($qdobj['enable'] === 1) {
        $queue_debug = TRUE;
        ao_ccss_log('Queue operating in debug mode with the following settings: <' . $qdobj_raw . '>', 3);
      }
    }
  }

  // Set some default values for $qdobj to avoid function call warnings
  if (!$queue_debug) {
    $qdobj['htcode'] = FALSE;
  }

  // Attach required arrays
  global $ao_ccss_queue;

  // Initialize job counter
  $jc = 1;
  $jt = count($ao_ccss_queue);

  // Iterates over the entire queue
  foreach ($ao_ccss_queue as $path => $jprops) {

    // Prepare flags and target rule
    $update      = FALSE;
    $rule_update = FALSE;
    $trule       = explode('|', $jprops['rtarget']);

    // Log job count
    ao_ccss_log('Processing job ' . $jc . ' (of ' . $jt . ' in the queue at this moment)', 3);

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

      // Compare job and rule hashes (if any)
      $hash = ao_ccss_diff_hashes($jprops['ljid'], $jprops['hash'], $jprops['hashes'], $jprops['rtarget']);

      // If job hash is new or different of a previous one
      if ($hash) {

        // Set job hash
        $jprops['hash'] = $hash;

        // Dispatch the job generation request
        $apireq = ao_ccss_api_generate($path, $queue_debug, $qdobj['htcode']);

        // NOTE: All the following conditions maps to the ones in admin_settings_queue.js.php

        // Request has a valid result
        // Process a PENDING job
        if ($apireq['job']['status'] == 'JOB_QUEUED' || $apireq['job']['status'] == 'JOB_ONGOING') {

          // Update job properties
          $jprops['jid']    = $apireq['job']['id'];
          $jprops['jqstat'] = $apireq['job']['status'];
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> generation request successful, remote id <' . $jprops['jid'] . '>, status now is <' . $jprops['jqstat'] . '>', 3);

        // Request has failed
        } else {

          // Update job properties
          $jprops['jqstat'] = 'JOB_UNKNOWN';
          $jprops['jftime'] = microtime(TRUE);
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> generation request has an UNKNOWN condition, status now is <' . $jprops['jqstat'] . '>, check log messages above for more information', 2);
        }

      // Job hash is equal a previous one, so it's done
      } else {

        // Update job status and finish time
        $jprops['jqstat'] = 'JOB_DONE';
        $jprops['jftime'] = microtime(TRUE);
        ao_ccss_log('Job id <' . $jprops['ljid'] . '> requires no further processing, status now is <' . $jprops['jqstat'] . '>', 3);
      }

      // Set queue update flag
      $update = TRUE;

    // Process QUEUED and ONGOING jobs
    // NOTE: implements section 4, id 3.2 of the specs
    } elseif ($jprops['jqstat'] == 'JOB_QUEUED' || $jprops['jqstat'] == 'JOB_ONGOING') {

      // Log the pending job
      ao_ccss_log('Found PENDING job with local ID <' . $jprops['ljid'] . '>, continuing its queue processing', 3);

      // Dispatch the job generation request
      $apireq = ao_ccss_api_results($jprops['jid'], $queue_debug, $qdobj['htcode']);

      // NOTE: All the following condigitons maps to the ones in admin_settings_queue.js.php

      // Replace API response values if queue debugging is enabled and some value is set
      if ($queue_debug) {
        if ($qdobj['status']) {
          $apireq['status'] = $qdobj['status'];
        }
        if ($qdobj['resultStatus']) {
          $apireq['resultStatus'] = $qdobj['resultStatus'];
        }
        if ($qdobj['validationStatus']) {
          $apireq['validationStatus'] = $qdobj['validationStatus'];
        }
      }

      // Request has a valid result
      // Process a PENDING job
      if ($apireq['status'] == 'JOB_QUEUED' || $apireq['status'] == 'JOB_ONGOING') {

        // Update job properties
        $jprops['jqstat'] = $apireq['status'];
        ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successful, remote id <' . $jprops['jid'] . '>, status <' . $jprops['jqstat'] . '> unchanged', 3);

      // Process a DONE job
      } elseif ($apireq['status'] == 'JOB_DONE') {

        // Process GOOD jobs
        if ($apireq['resultStatus'] == 'GOOD' && $apireq['validationStatus'] == 'GOOD') {

          // Update job properties
          $jprops['file']   = ao_ccss_save_file($apireq['css'], $trule[1], FALSE);
          $jprops['jqstat'] = $apireq['status'];
          $jprops['jrstat'] = $apireq['resultStatus'];
          $jprops['jvstat'] = $apireq['validationStatus'];
          $jprops['jftime'] = microtime(TRUE);
          $rule_update      = TRUE;
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successful, remote id <' . $jprops['jid'] . '>, status <' . $jprops['jqstat'] . '>, file saved <' . $jprops['file'] . '>', 3);

        // Process REVIEW jobs
        } elseif ($apireq['resultStatus'] == 'GOOD' && ($apireq['validationStatus'] == 'WARN' || $apireq['validationStatus'] == 'BAD')) {

          // Update job properties
          $jprops['file']   = ao_ccss_save_file($apireq['css'], $trule[1], TRUE);
          $jprops['jqstat'] = $apireq['status'];
          $jprops['jrstat'] = $apireq['resultStatus'];
          $jprops['jvstat'] = $apireq['validationStatus'];
          $jprops['jftime'] = microtime(TRUE);
          $rule_update      = TRUE;
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successful, remote id <' . $jprops['jid'] . '>, status <' . $jprops['jqstat'] . ', file saved <' . $jprops['file'] . '> but requires REVIEW', 3);

        // Process ERROR jobs
        } elseif ($apireq['resultStatus'] != 'GOOD' && ($apireq['validationStatus'] != 'GOOD' || $apireq['validationStatus'] != 'WARN' || $apireq['validationStatus'] != 'BAD')) {

          // Update job properties
          $jprops['jqstat'] = $apireq['status'];
          $jprops['jrstat'] = $apireq['resultStatus'];
          $jprops['jvstat'] = $apireq['validationStatus'];
          $jprops['jftime'] = microtime(TRUE);
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successfull but job FAILED, status now is <' . $jprops['jqstat'] . '>, check log messages above for more information', 2);

        // Process UNKNOWN jobs
        } else {

          // Update job properties
          $jprops['jqstat'] = 'JOB_UNKNOWN';
          $jprops['jrstat'] = $apireq['resultStatus'];
          $jprops['jvstat'] = $apireq['validationStatus'];
          $queue_update     = TRUE;
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successfull but job FAILED, status now is <' . $jprops['jqstat'] . '>, check log messages above for more information', 2);

        }

      // Process a FAILED job
      } elseif ($apireq['status'] == 'JOB_FAILED') {

        // Update job properties
        $jprops['jqstat'] = $apireq['status'];
        $jprops['jftime'] = microtime(TRUE);
        ao_ccss_log('Job id <' . $jprops['ljid'] . '> generation request successfull but job FAILED, status now is <' . $jprops['jqstat'] . '>, check log messages above for more information', 2);

      // Request has failed with an UNKNOWN condition
      } elseif (empty($apireq) || $apireq['status'] == 'JOB_UNKNOWN') {

        // Update job properties
        $jprops['jqstat'] = 'JOB_UNKNOWN';
        $jprops['jrstat'] = 'criticalcss.com replied with status ' . $apireq['status'] . ' and error message ' . $apireq['error'];
        $jprops['jftime'] = microtime(TRUE);
        ao_ccss_log('Job id <' . $jprops['ljid'] . '> generation request has an UNKNOWN condition, status now is <' . $jprops['jqstat'] . '>, check log messages above for more information', 2);
      }

      // Set queue update flag
      $update = TRUE;
    }

    // Persist updated queue object
    // NOTE: implements section 4, id 3.2.1 of the specs
    if ($update) {

      // Update queue
      $ao_ccss_queue[$path] = $jprops;
      $ao_ccss_queue_raw    = json_encode($ao_ccss_queue);
      update_option('autoptimize_ccss_queue', $ao_ccss_queue_raw);
      ao_ccss_log('Queue updated by job id <' . $jprops['ljid'] . '>', 3);

      // Update rules
      if ($rule_update) {
        ao_ccss_log('Job id <' . $jprops['ljid'] . '> requires rules update for target rule <' . $jprops['rtarget'] . '>', 3);
        ao_ccss_rule_update($jprops['ljid'], $jprops['rtarget'], $jprops['file'], $jprops['hash']);
      }

    // Or log no queue action
    } else {
      ao_ccss_log('Nothing to do on job' . $jc . ' (of ' . $jt . 'in the queue at this moment), job id <' . $jprops['ljid'] . '>, status <' . $jprops['status'] . '>', 3);
    }

    // Increment job counter
    $jc++;
  }

  // Log queue end
  ao_ccss_log('Queue control finished', 3);
}

// Compare job hashes
function ao_ccss_diff_hashes($ljid, $hash, $hashes, $rule) {

  // STEP 1: update job hashes
  // Job with a single hash
  if (count($hashes) == 1) {

    // Set job hash
    $hash = $hashes[0];
    ao_ccss_log('Job id <' . $ljid . '> updated with SINGLE hash <' . $hash . '>', 3);

  // Job with multiple hashes
  } else {

    // Loop through hashes to concatenate them
    $nhash = '';
    foreach ($hashes as $shash) {
      $nhash .= $shash;
    }

    // Set job hash
    $hash = md5($nhash);
    ao_ccss_log('Job id <' . $ljid . '> updated with a COMPOSITE hash <' . $hash . '>', 3);
  }

  // STEP 2: compare job and existing rule (if any) hashes
  // Attach required arrays
  global $ao_ccss_rules;

  // Prepare rule variables
  $trule = explode('|', $rule);
  $srule = $ao_ccss_rules[$trule[0]][$trule[1]];

  // Check if an AUTO rule exist
  if (!empty($srule) && $srule['hash'] !== 0) {

    // Check if job hash matches rule and return false if yes
    if ($hash == $srule['hash']) {
      ao_ccss_log('Job id <' . $ljid . '> with hash <' . $hash . '> MATCH the one in rule <' . $trule[0] . '|' . $trule[1] . '>', 3);
      return FALSE;

    // Or return the new hash if they differ
    } else {
      ao_ccss_log('Job id <' . $ljid . '> with hash <' . $hash . '> DOES NOT MATCH the one in rule <' . $trule[0] . '|' . $trule[1] . '>', 3);
      return $hash;
    }

  // Check if a MANUAL rule exist and return false
  } elseif (!empty($srule) && $srule['hash'] === 0) {
    ao_ccss_log('Job id <' . $ljid . '> matches the MANUAL rule <' . $trule[0] . '|' . $trule[1] . '>', 3);
    return FALSE;

  // Or just return the hash if no rule exist yet
  } else {
    ao_ccss_log('Job id <' . $ljid . '> with hash <' . $hash . '> has no rule yet', 3);
    return $hash;
  }
}

// POST jobs to criticalcss.com and return responses
function ao_ccss_api_generate($path, $debug, $dcode) {

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

  // If queue debug is active, change response code
  if ($debug && $dcode) {
    $code = $dcode;
  }

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
      ao_ccss_log(print_r($body, TRUE), 2);
      return $body;
    }

  // Response code is anything else
  } else {

    // Log failed request and return false
    ao_ccss_log('criticalcss.com: POST generate request for path <' . $src_url . '> replied with error code <' . $code . '>, body follows...', 2);
    ao_ccss_log(print_r($body, TRUE), 2);
    return FALSE;
  }
}

// GET jobs from criticalcss.com and return responses
function ao_ccss_api_results($jobid, $debug, $dcode) {

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

  // If queue debug is active, change response code
  if ($debug && $dcode) {
    $code = $dcode;
  }

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
      ao_ccss_log(print_r($body, TRUE), 2);
      return FALSE;
    }

  // Response code is anything else
  } else {

    // Log failed request and return false
    ao_ccss_log('criticalcss.com: GET results request for remote job id <' . $jobid . '> replied with error code <' . $code . ">, body follows...", 2);
    ao_ccss_log(print_r($body, TRUE), 2);
    return FALSE;
  }
}

// Save critical CSS into the filesystem and return its filename
function ao_ccss_save_file($ccss, $target, $review) {

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
    $file     = AO_CCSS_DIR . 'ccss_' . md5($ccss . $target) . $rmark . '.css';
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

// Upate or create a rule
// NOTE: implements section 4, id 3.2.1 of the specs
function ao_ccss_rule_update($ljid, $srule, $file, $hash) {

  // Attach required arrays
  global $ao_ccss_rules;

  // Prepare rule variables
  $trule  = explode('|', $srule);
  $rule   = $ao_ccss_rules[$trule[0]][$trule[1]];
  $action = FALSE;
  $rtype  = '';

  // If this is an existing MANUAL rule with no file yet, update with the fetched filename
  if ($rule['hash'] === 0 && $rule['file'] === 0) {

    // Set rule file and action flag
    $rule['file'] = $file;
    $action       = 'UPDATED';
    $rtype        = 'MANUAL';

  // If this is an existing AUTO rule, update its hash and the fetched filename
  } elseif ($rule['hash'] !== 0 && ctype_alnum($rule['hash'])) {

    // Set rule hash and file and action flag
    $rule['hash'] = $hash;
    $rule['file'] = $file;
    $action       = 'UPDATED';
    $rtype        = 'AUTO';

  // If rule doesn't exist, create an AUTO rule
  } else {

    // AUTO rules are only for types
    if ($trule[0] == 'types') {

      // Set rule hash and file and action flag
      $rule['hash'] = $hash;
      $rule['file'] = $file;
      $action       = 'CREATED';
      $rtype        = 'AUTO';

    // Log that no rule was created
    } else {
      ao_ccss_log('AUTO rules are only for page types, no rule created', 3);
    }
  }

  // If a rule creation/update is required, persist updated rules object
  if ($action) {
    $ao_ccss_rules[$trule[0]][$trule[1]] = $rule;
    $ao_ccss_rules_raw = json_encode($ao_ccss_rules);
    update_option('autoptimize_ccss_rules', $ao_ccss_rules_raw);
    ao_ccss_log('Target rule <' . $srule . '> of type <' . $rtype . '> was ' . $action . ' for job id <' . $ljid . '>', 3);
  } else {
    ao_ccss_log('No rule action required', 3);
  }
}

// Truncate log file if it exist and is >= 1MB
// NOTE: out of scope log file maintenance
function ao_ccss_log_truncate() {
  if (file_exists(AO_CCSS_LOG)) {
    if (filesize(AO_CCSS_LOG) >= 1048576) {
      $logfile = fopen(AO_CCSS_LOG, "w");
      fclose($logfile);
    }
  }
}

// Add truncate log to a registered event
add_action('ao_ccss_log', 'ao_ccss_log_truncate');
?>