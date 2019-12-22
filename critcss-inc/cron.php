<?php

// NOTE: implements section 4 of the specs

// Add queue control to a registered event
add_action('ao_ccss_queue', 'ao_ccss_queue_control');

// The queue execution backend
function ao_ccss_queue_control() {

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
  $queue_debug = FALSE;
  if (file_exists(AO_CCSS_DEBUG)) {
    $qdobj_raw = file_get_contents(AO_CCSS_DEBUG);
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

  // Check if queue is already running
  $queue_lock = FALSE;
  if (file_exists(AO_CCSS_LOCK)) {
    $queue_lock = TRUE;
  }

  // Proceed with the queue if it's not already running
  if (!$queue_lock) {

    // Log queue start and create the lock file
    ao_ccss_log('Queue control started', 3);
    if (touch(AO_CCSS_LOCK)) {
      ao_ccss_log('Queue control locked', 3);
    }

    // Attach required variables
    global $ao_ccss_queue;
    global $ao_ccss_rlimit;

    // Initialize job counters
    $jc = 1;
    $jr = 1;
    $jt = count($ao_ccss_queue);

    // Sort queue by ascending job status (e.g. ERROR, JOB_ONGOING, JOB_QUEUED, NEW...)
    array_multisort(array_column($ao_ccss_queue, 'jqstat'), $ao_ccss_queue);

    // Iterates over the entire queue
    foreach ($ao_ccss_queue as $path => $jprops) {

      // Prepare flags and target rule
      $update      = FALSE;
      $deljob      = FALSE;
      $rule_update = FALSE;
      $oldccssfile = FALSE;
      $trule       = explode('|', $jprops['rtarget']);

      // Log job count
      ao_ccss_log('Processing job ' . $jc . ' of ' . $jt . ' with id <' . $jprops['ljid'] . '> and status <' . $jprops['jqstat'] . '>', 3);

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

          // If this is not the first job, wait 15 seconds before process next job due criticalcss.com API limits
          if ($jr > 1) {
            ao_ccss_log('Waiting 15 seconds due to criticalcss.com API limits', 3);
            sleep(15);
          }

          // Dispatch the job generate request and increment request count
          $apireq = ao_ccss_api_generate($path, $queue_debug, $qdobj['htcode']);
          $jr++;

          // NOTE: All the following conditions maps to the ones in admin_settings_queue.js.php

          // SUCCESS: request has a valid result
          if ($apireq['job']['status'] == 'JOB_QUEUED' || $apireq['job']['status'] == 'JOB_ONGOING') {

            // Update job properties
            $jprops['jid']    = $apireq['job']['id'];
            $jprops['jqstat'] = $apireq['job']['status'];
            ao_ccss_log('Job id <' . $jprops['ljid'] . '> generate request successful, remote id <' . $jprops['jid'] . '>, status now is <' . $jprops['jqstat'] . '>', 3);

          // ERROR: concurrent requests
          } elseif ($apireq['job']['status'] == 'STATUS_JOB_BAD') {

            // Update job properties
            $jprops['jid']    = $apireq['job']['id'];
            $jprops['jqstat'] = $apireq['job']['status'];
            $jprops['jrstat'] = $apireq['error'];
            $jprops['jvstat'] = 'NONE';
            $jprops['jftime'] = microtime(TRUE);
            ao_ccss_log('Concurrent requests when processing job id <' . $jprops['ljid'] . '>, job status is now <' . $jprops['jqstat'] . '>', 3);

          // ERROR: key validation
          } elseif ($apireq['errorCode'] == 'INVALID_JWT_TOKEN') {

            // Update job properties
            $jprops['jqstat'] = $apireq['errorCode'];
            $jprops['jrstat'] = $apireq['error'];
            $jprops['jvstat'] = 'NONE';
            $jprops['jftime'] = microtime(TRUE);
            ao_ccss_log('API key validation error when processing job id <' . $jprops['ljid'] . '>, job status is now <' . $jprops['jqstat'] . '>', 3);

          // ERROR: no response
          } elseif (empty($apireq)) {

            // Update job properties
            $jprops['jqstat'] = 'NO_RESPONSE';
            $jprops['jrstat'] = 'NONE';
            $jprops['jvstat'] = 'NONE';
            $jprops['jftime'] = microtime(TRUE);
            ao_ccss_log('Job id <' . $jprops['ljid'] . '> request has no response, status now is <' . $jprops['jqstat'] . '>', 3);

          // UNKNOWN: unhandled generate exception
          } else {

            // Update job properties
            $jprops['jqstat'] = 'JOB_UNKNOWN';
            $jprops['jrstat'] = 'NONE';
            $jprops['jvstat'] = 'NONE';
            $jprops['jftime'] = microtime(TRUE);
            ao_ccss_log('Job id <' . $jprops['ljid'] . '> generate request has an UNKNOWN condition, status now is <' . $jprops['jqstat'] . '>, check log messages above for more information', 2);
            ao_ccss_log( 'Job response was: ' . json_encode( $apireq ), 3 );
          }

        // SUCCESS: Job hash is equal to a previous one, so it's done
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

        // If this is not the first job, wait 15 seconds before process next job due criticalcss.com API limits
        if ($jr > 1) {
          ao_ccss_log('Waiting 15 seconds due to criticalcss.com API limits', 3);
          sleep(15);
        }

        // Dispatch the job result request and increment request count
        $apireq = ao_ccss_api_results($jprops['jid'], $queue_debug, $qdobj['htcode']);
        $jr++;

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

        // SUCCESS: request has a valid result
        // Process a PENDING job
        if ($apireq['status'] == 'JOB_QUEUED' || $apireq['status'] == 'JOB_ONGOING') {

          // Update job properties
          $jprops['jqstat'] = $apireq['status'];
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successful, remote id <' . $jprops['jid'] . '>, status <' . $jprops['jqstat'] . '> unchanged', 3);

        // Process a DONE job
        } elseif ($apireq['status'] == 'JOB_DONE') {

          // New resultStatus from ccss.com "HTML_404", consider as "GOOD" for now
          if ($apireq['resultStatus'] == 'HTML_404') {
            $apireq['resultStatus'] = 'GOOD';
          }

          // SUCCESS: GOOD job with GOOD validation
          if ($apireq['resultStatus'] == 'GOOD' && $apireq['validationStatus'] == 'GOOD') {

            // Update job properties
            $jprops['file']   = ao_ccss_save_file($apireq['css'], $trule, FALSE);
            $jprops['jqstat'] = $apireq['status'];
            $jprops['jrstat'] = $apireq['resultStatus'];
            $jprops['jvstat'] = $apireq['validationStatus'];
            $jprops['jftime'] = microtime(TRUE);
            $rule_update      = TRUE;
            ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successful, remote id <' . $jprops['jid'] . '>, status <' . $jprops['jqstat'] . '>, file saved <' . $jprops['file'] . '>', 3);

          // SUCCESS: GOOD job with WARN or BAD validation
          } elseif ($apireq['resultStatus'] == 'GOOD' && ($apireq['validationStatus'] == 'WARN' || $apireq['validationStatus'] == 'BAD' || $apireq['validationStatus'] == 'SCREENSHOT_WARN_BLANK' )) {

            // Update job properties
            $jprops['file']   = ao_ccss_save_file($apireq['css'], $trule, TRUE);
            $jprops['jqstat'] = $apireq['status'];
            $jprops['jrstat'] = $apireq['resultStatus'];
            $jprops['jvstat'] = $apireq['validationStatus'];
            $jprops['jftime'] = microtime(TRUE);
            $rule_update      = TRUE;
            ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successful, remote id <' . $jprops['jid'] . '>, status <' . $jprops['jqstat'] . ', file saved <' . $jprops['file'] . '> but requires REVIEW', 3);

          // ERROR: no GOOD, WARN or BAD results
          } elseif ($apireq['resultStatus'] != 'GOOD' && ($apireq['validationStatus'] != 'GOOD' || $apireq['validationStatus'] != 'WARN' || $apireq['validationStatus'] != 'BAD' || $apireq['validationStatus'] != 'SCREENSHOT_WARN_BLANK')) {

            // Update job properties
            $jprops['jqstat'] = $apireq['status'];
            $jprops['jrstat'] = $apireq['resultStatus'];
            $jprops['jvstat'] = $apireq['validationStatus'];
            $jprops['jftime'] = microtime(TRUE);
            ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successful but job FAILED, status now is <' . $jprops['jqstat'] . '>', 3);
            $apireq['css'] = '/* critical css removed for DEBUG logging purposes */';
            ao_ccss_log( 'Job response was: ' . json_encode( $apireq ), 3 );

          // UNKNOWN: unhandled JOB_DONE exception
          } else {

            // Update job properties
            $jprops['jqstat'] = 'JOB_UNKNOWN';
            $jprops['jrstat'] = $apireq['resultStatus'];
            $jprops['jvstat'] = $apireq['validationStatus'];
            $jprops['jftime'] = microtime(TRUE);
            ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successful but job is UNKNOWN, status now is <' . $jprops['jqstat'] . '>', 2);
            $apireq['css'] = '/* critical css removed for DEBUG logging purposes */';
            ao_ccss_log( 'Job response was: ' . json_encode( $apireq ), 3 );
          }

        // ERROR: failed job
        } elseif ($apireq['job']['status'] == 'JOB_FAILED' || $apireq['job']['status'] == 'STATUS_JOB_BAD') {

          // Update job properties
          $jprops['jqstat'] = $apireq['job']['status'];
          if ($apireq['error']) {
            $jprops['jrstat'] = $apireq['job']['error'];
          } else {
          }
          $jprops['jvstat'] = 'NONE';
          $jprops['jftime'] = microtime(TRUE);
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successful but job FAILED, status now is <' . $jprops['jqstat'] . '>', 3);

        // ERROR: CSS doesn't exist
        } elseif ($apireq['error'] == "This css no longer exists. Please re-generate it.") {

          // Update job properties
          $jprops['jqstat'] = 'NO_CSS';
          $jprops['jrstat'] = $apireq['error'];
          $jprops['jvstat'] = 'NONE';
          $jprops['jftime'] = microtime(TRUE);
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request successful but job FAILED, status now is <' . $jprops['jqstat'] . '>', 3);

        // ERROR: no response
        } elseif (empty($apireq)) {

          // Update job properties
          $jprops['jqstat'] = 'NO_RESPONSE';
          $jprops['jrstat'] = 'NONE';
          $jprops['jvstat'] = 'NONE';
          $jprops['jftime'] = microtime(TRUE);
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> request has no response, status now is <' . $jprops['jqstat'] . '>', 3);

        // UNKNOWN: unhandled results exception
        } else {

          // Update job properties
          $jprops['jqstat'] = 'JOB_UNKNOWN';
          $jprops['jrstat'] = 'NONE';
          $jprops['jvstat'] = 'NONE';
          $jprops['jftime'] = microtime(TRUE);
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> result request has an UNKNOWN condition, status now is <' . $jprops['jqstat'] . '>, check log messages above for more information', 2);
        }

        // Set queue update flag
        $update = TRUE;

      }

      // Mark DONE jobs for removal
      // NOTE: out of scope DONE job removal (issues #4 and #18)
      if ($jprops['jqstat'] == 'JOB_DONE') {
        $update = TRUE;
        $deljob = TRUE;
      }

      // Persist updated queue object
      // NOTE: implements section 4, id 3.2.1 of the specs
      if ($update) {

        // Update properties of a NEW or PENDING job...
        if (!$deljob) {
          $ao_ccss_queue[$path] = $jprops;

        // ...or remove the DONE job
        } else {
          unset($ao_ccss_queue[$path]);
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> is DONE and was removed from the queue', 3);
        }

        // Update queue object
        $ao_ccss_queue_raw = json_encode($ao_ccss_queue);
        update_option('autoptimize_ccss_queue', $ao_ccss_queue_raw, false);
        ao_ccss_log('Queue updated by job id <' . $jprops['ljid'] . '>', 3);

        // Update target rule
        if ($rule_update) {
          ao_ccss_rule_update($jprops['ljid'], $jprops['rtarget'], $jprops['file'], $jprops['hash']);
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> updated the target rule <' . $jprops['rtarget'] . '>', 3);
        }

      // Or log no queue action
      } else {
        ao_ccss_log('Nothing to do on this job', 3);
      }

      // Break the loop if request limit is set and was reached
      if ($ao_ccss_rlimit && $ao_ccss_rlimit == $jr) {
        ao_ccss_log('The limit of ' . $ao_ccss_rlimit . ' request(s) to criticalcss.com was reached, queue control must finish now', 3);
        break;
      }

      // Increment job counter
      $jc++;
    }

    // Remove the lock file and log the queue end
    if (file_exists(AO_CCSS_LOCK)) {
      unlink(AO_CCSS_LOCK);
      ao_ccss_log('Queue control unlocked', 3);
    }
    ao_ccss_log('Queue control finished', 3);

  // Log that queue is locked
  } else {
    ao_ccss_log('Queue is already running, skipping the attempt to run it again', 3);
  }
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

  // STEP 2: compare job to existing jobs to prevent double submission for same type+hash
  global $ao_ccss_queue;
    
  foreach ($ao_ccss_queue as $queue_item) {
    ao_ccss_log('Comparing <' . $rule.$hash . '> with <' . $queue_item['rtarget'].$queue_item['hash'] . '>', 3);
    if ( $queue_item['hash'] == $hash && $queue_item['rtarget'] == $rule && in_array( $queue_item['jqstat'], array( 'JOB_QUEUED', 'JOB_ONGOING', 'JOB_DONE' ) ) ) {
      ao_ccss_log('Job id <' . $ljid . '> matches the already pending job <' . $queue_item['ljid'] . '>', 3);
      return FALSE;
    }
  }

  // STEP 3: compare job and existing rule (if any) hashes
  // Attach required arrays
  global $ao_ccss_rules;

  // Prepare rule variables
  $trule = explode('|', $rule);
  $srule = $ao_ccss_rules[$trule[0]][$trule[1]];

  // Check if a MANUAL rule exist and return false
  if (!empty($srule) && ($srule['hash'] == 0 && $srule['file'] != 0)) {
    ao_ccss_log('Job id <' . $ljid . '> matches the MANUAL rule <' . $trule[0] . '|' . $trule[1] . '>', 3);
    return FALSE;

  // Check if an AUTO rule exist
  } elseif (!empty($srule)) {
    // Check if job hash matches rule, if the CCSS file exists said file is not empty and return FALSE is so
    if ( $hash === $srule['hash'] && is_file( AO_CCSS_DIR.$srule['file'] ) && 0 != filesize( AO_CCSS_DIR.$srule['file'] ) ) {
      ao_ccss_log('Job id <' . $ljid . '> with hash <' . $hash . '> MATCH the one in rule <' . $trule[0] . '|' . $trule[1] . '>', 3);
      return FALSE;

    // Or return the new hash if they differ
    } else {
      ao_ccss_log('Job id <' . $ljid . '> with hash <' . $hash . '> DOES NOT MATCH the one in rule <' . $trule[0] . '|' . $trule[1] . '> or rule\'s CCSS file was invalid.', 3);
      return $hash;
    }

  // Or just return the hash if no rule exist yet
  } else {
    ao_ccss_log('Job id <' . $ljid . '> with hash <' . $hash . '> has no rule yet', 3);
    return $hash;
  }
}

// POST jobs to criticalcss.com and return responses
function ao_ccss_api_generate($path, $debug, $dcode) {

  // Get key and key status
  global $ao_ccss_key;
  global $ao_ccss_keyst;
  $key        = $ao_ccss_key;
  $key_status = $ao_ccss_keyst;

  // Prepare full URL to request
  global $ao_ccss_noptimize;
  
  $site_host = get_site_url();
  $site_path = parse_url( $site_host, PHP_URL_PATH );
  
  if ( ! empty( $site_path )) {
    $site_host = str_replace( $site_path, '', $site_host );
  }

  // Logic to bind to one domain to avoid site clones of sites would 
  // automatically begin spawning requests to criticalcss.com which has
  // a per domain cost.
  global $ao_ccss_domain;
  if ( empty( $ao_ccss_domain ) ) {
    // first request being done, update option to allow future requests are only allowed if from same domain.
    update_option( 'autoptimize_ccss_domain', str_rot13($site_host) );
  } else if ( trim( $ao_ccss_domain, '\'"') !== 'none' && parse_url( $site_host, PHP_URL_HOST ) !== parse_url( $ao_ccss_domain, PHP_URL_HOST ) && apply_filters( 'autoptimize_filter_ccss_bind_domain', true ) ) {
    // not the same domain, log as error and return without posting to criticalcss.com.
    ao_ccss_log( 'Request for domain '.$site_host.' does not match bound domain '.$ao_ccss_domain.' so not proceeding.', 2 );
    return FALSE;
  }

  $src_url = $site_host . $path;

  // Avoid AO optimizations if required by config or if lazyload is active in AO
  if ( !empty( $ao_ccss_noptimize ) || ( class_exists( 'autoptimizeImages', false ) && autoptimizeImages::should_lazyload_wrapper() ) ) {
    $src_url .= '?ao_noptirocket=1';
  }
  $src_url = apply_filters( 'autoptimize_filter_ccss_cron_srcurl', $src_url );

  // Initialize request body
  $body           = array();
  $body['url']    = $src_url;
  $body['aff']    = 1;
  $body['aocssv'] = AO_CCSS_VER;

  // Prepare and add viewport size to the body if available
  $viewport = ao_ccss_viewport();
  if (!empty($viewport['w']) && !empty($viewport['h'])) {
    $body['width']  = $viewport['w'];
    $body['height'] = $viewport['h'];
  }

  // Prepare and add forceInclude to the body if available
  global $ao_ccss_finclude;
  $finclude = ao_ccss_finclude($ao_ccss_finclude);
  if (!empty($finclude)) {
    $body['forceInclude'] = $finclude;
  }

  // Body must be json and log it
  $body = json_encode($body);
  ao_ccss_log('criticalcss.com: POST generate request body is ' . $body, 3);

  // Prepare the request
  $url  = esc_url_raw(AO_CCSS_API . 'generate');
  $args = array(
    'headers' => array(
      'User-Agent'    => 'Autoptimize CriticalCSS Power-Up v' . AO_CCSS_VER,
      'Content-type'  => 'application/json; charset=utf-8',
      'Authorization' => 'JWT ' . $key,
      'Connection'    => 'close'
    ),
    'body' => $body
  );

  // Dispatch the request and store its response code
  $req  = wp_safe_remote_post($url, $args);
  $code = wp_remote_retrieve_response_code($req);
  $body = json_decode(wp_remote_retrieve_body($req), TRUE);

  // If queue debug is active, change response code
  if ($debug && $dcode) {
    $code = $dcode;
  }

  // Response code is OK
  if ($code == 200) {

    // Workaround criticalcss.com non-RESTful reponses
    if ($body['job']['status'] == 'JOB_QUEUED' || $body['job']['status'] == 'JOB_ONGOING' || $body['job']['status'] == 'STATUS_JOB_BAD') {

      // Log successful and return encoded request body
      ao_ccss_log('criticalcss.com: POST generate request for path <' . $src_url . '> replied successfully', 3);

      // This code also means the key is valid, so cache key status for 24h if not already cached
      if ( ( !$key_status || $key_status != 2 ) && $key) {
        update_option('autoptimize_ccss_keyst', 2);
        ao_ccss_log('criticalcss.com: API key is valid, updating key status', 3);
      }

      // Return the request body
      return $body;

    // Log successful requests with invalid reponses
    } else {
      ao_ccss_log('criticalcss.com: POST generate request for path <' . $src_url . '> replied with code <' . $code . '> and an UNKNOWN error condition, body follows...', 2);
      ao_ccss_log(print_r($body, TRUE), 2);
      return $body;
    }

  // Response code is anything else
  } else {

    // Log failed request with a valid response code and return body
    if ($code) {
      ao_ccss_log('criticalcss.com: POST generate request for path <' . $src_url . '> replied with error code <' . $code . '>, body follows...', 2);
      ao_ccss_log(print_r($body, TRUE), 2);

      // If request is unauthorized, also clear key status
      if ($code == 401) {
        update_option('autoptimize_ccss_keyst', 1);
        ao_ccss_log('criticalcss.com: API key is invalid, updating key status', 3);
      }

      // Return the request body
      return $body;

    // Log failed request with no response and return false
    } else {
      ao_ccss_log('criticalcss.com: POST generate request for path <' . $src_url . '> has no response, this could be a service timeout', 2);
      if ( is_wp_error($req) ) {
        ao_ccss_log( $req->get_error_message(), 2);
      }

      return FALSE;
    }
  }
}

// GET jobs from criticalcss.com and return responses
function ao_ccss_api_results($jobid, $debug, $dcode) {

  // Get key
  global $ao_ccss_key;
  $key = $ao_ccss_key;

  // Prepare the request
  $url  = AO_CCSS_API . 'results?resultId=' . $jobid;
  $args = array(
    'headers' => array(
      'User-Agent'    => 'Autoptimize CriticalCSS Power-Up v' . AO_CCSS_VER,
      'Authorization' => 'JWT ' . $key,
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

  // Response code is OK
  if ($code == 200) {

    // Workaround criticalcss.com non-RESTful reponses
    if ( is_array( $body ) && ( array_key_exists( 'status', $body ) || array_key_exists( 'job', $body ) ) && ( $body['status'] == 'JOB_QUEUED' || $body['status'] == 'JOB_ONGOING' || $body['status'] == 'JOB_DONE' || $body['status'] == 'JOB_FAILED' || $body['status'] == 'JOB_UNKNOWN' || $body['job']['status'] == 'STATUS_JOB_BAD') ) {

      // Log successful and return encoded request body
      ao_ccss_log('criticalcss.com: GET results request for remote job id <' . $jobid . '> replied successfully', 3);
      return $body;

    // Handle no CSS reply
    } elseif ( is_array( $body ) && ( array_key_exists( 'error', $body ) && $body['error'] = 'This css no longer exists. Please re-generate it.') ) {

      // Log no CSS error and return encoded request body
      ao_ccss_log('criticalcss.com: GET results request for remote job id <' . $jobid . '> replied successfully but the CSS for it does not exist anymore', 3);
      return $body;

    // Log failed request and return false
    } else {
      ao_ccss_log('criticalcss.com: GET results request for remote job id <' . $jobid . '> replied with code <' . $code . '> and an UNKNOWN error condition, body follows...', 2);
      ao_ccss_log(print_r($body, TRUE), 2);
      return FALSE;
    }

  // Response code is anything else
  } else {

    // Log failed request with a valid response code and return body
    if ($code) {
      ao_ccss_log('criticalcss.com: GET results request for remote job id <' . $jobid . '> replied with error code <' . $code . '>, body follows...', 2);
      ao_ccss_log(print_r($body, TRUE), 2);

      // If request is unauthorized, also clear key status
      if ($code == 401) {
        update_option('autoptimize_ccss_keyst', 1);
        ao_ccss_log('criticalcss.com: API key is invalid, updating key status', 3);
      }

      // Return the request body
      return $body;

    // Log failed request with no response and return false
    } else {
      ao_ccss_log('criticalcss.com: GET results request for remote job id <' . $jobid . '> has no response, this could be a service timeout', 2);
      return FALSE;
    }
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

  // Prepare target rule, filename and content
  $filename = FALSE;
  $content  = $ccss;

  // Sanitize content, set filename and try to save file
  if (ao_ccss_check_contents($content)) {
    $file     = AO_CCSS_DIR . 'ccss_' . md5($ccss . $target[1]) . $rmark . '.css';
    $status   = file_put_contents($file, $content, LOCK_EX);
    $filename = pathinfo($file, PATHINFO_BASENAME);

    ao_ccss_log('Critical CSS file for the rule <' . $target[0] . '|' . $target[1] . '> was saved as <' . $filename . '>, size in bytes is <' . $status . ">", 3);

    // If file has not been saved, reset filename
    if (!$status) {
      ao_ccss_log('Critical CSS file <' . $filename . '> could not be not saved', 2);
      $filename = FALSE;
      return $filename;
    }
  } else {
    ao_ccss_log('Critical CSS received did not pass content check', 2);
    return $filename;
  }

  // Remove old critical CSS if a previous one existed in the rule and if that file exists in filesystem
  // NOTE: out of scope critical CSS file removal (issue #5)
  // Attach required arrays
  global $ao_ccss_rules;

  // Prepare rule variables
  $srule   = $ao_ccss_rules[$target[0]][$target[1]];
  $oldfile = $srule['file'];

  if ($oldfile && $oldfile !== $filename) {
    $delfile = AO_CCSS_DIR . $oldfile;
    if (file_exists($delfile)) {
      $unlinkst = unlink($delfile);
      if ($unlinkst) {
        ao_ccss_log('A previous critical CSS file <' . $oldfile . '> was removed for the rule <' . $target[0] . '|' . $target[1] . '>', 3);
      }
    }
  }

  // Return filename or false
  return $filename;
}

// Update or create a rule
// NOTE: implements section 4, id 3.2.1 of the specs
function ao_ccss_rule_update($ljid, $srule, $file, $hash) {

  // Attach required arrays
  global $ao_ccss_rules;

  // Prepare rule variables
  $trule  = explode('|', $srule);
  $rule   = $ao_ccss_rules[$trule[0]][$trule[1]];
  $action = FALSE;
  $rtype  = '';

  if ($rule['hash'] === 0 && $rule['file'] !== 0) {
    // manual rule, don't ever overwrite
    $action       = 'NOT UPDATED';
    $rtype        = 'MANUAL';
    
  } elseif ($rule['hash'] === 0 && $rule['file'] === 0) {
    // If this is an user created AUTO rule with no hash and file yet, update its hash and filename
    // Set rule hash, file and action flag
    $rule['hash'] = $hash;
    $rule['file'] = $file;
    $action       = 'UPDATED';
    $rtype        = 'AUTO';

  // If this is an genuine AUTO rule, update its hash and filename
  } elseif ($rule['hash'] !== 0 && ctype_alnum($rule['hash'])) {

    // Set rule hash, file and action flag
    $rule['hash'] = $hash;
    $rule['file'] = $file;
    $action       = 'UPDATED';
    $rtype        = 'AUTO';

  // If rule doesn't exist, create an AUTO rule
  } else {

    // AUTO rules were only for types, but will now also work for paths
    if ($trule[0] == 'types' || $trule[0] == 'paths') {

      // Set rule hash and file and action flag
      $rule['hash'] = $hash;
      $rule['file'] = $file;
      $action       = 'CREATED';
      $rtype        = 'AUTO';

    // Log that no rule was created
    } else {
      ao_ccss_log('Exception, no AUTO rule created', 3);
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

// Prepare forceInclude object
function ao_ccss_finclude($finclude_raw) {

  // If there are any content
  if (!empty($finclude_raw)) {

    // Convert raw string into arra and initialize the returning object
    $fincludes = explode(',', $finclude_raw);
    $finclude  = array();

    // Interacts over every rule
    $i = 0;
    foreach ($fincludes as $include) {

      // Trim leading and trailing whitespaces
      $include = trim($include);

      // Regex rule
      if (substr($include, 0, 2) === '//') {

        // Format value as required
        $include = str_replace('//', '/', $include);
        $include = $include . '/i';

        // Store regex object
        $finclude[$i]['type'] = 'RegExp';
        $finclude[$i]['value'] = $include;

      // Simple value rule
      } else {
        $finclude[$i]['value'] = $include;
      }

      $i++;
    }

    // Return forceInclude object
    return $finclude;

  // Or just return false if empty
  } else {
    return FALSE;
  }

}

// Perform plugin maintenance
// NOTE: out of scope plugin maintenance
function ao_ccss_cleaning() {

  // Truncate log file >= 1MB
  if (file_exists(AO_CCSS_LOG)) {
    if (filesize(AO_CCSS_LOG) >= 1048576) {
      $logfile = fopen(AO_CCSS_LOG, "w");
      fclose($logfile);
    }
  }

  // Remove lock file
  if (file_exists(AO_CCSS_LOCK)) {
    unlink(AO_CCSS_LOCK);
  }

  // Make sure queue processing is scheduled, recreate if not.
  if ( !wp_next_scheduled( 'ao_ccss_queue' ) ) {
    wp_schedule_event( time(), apply_filters( 'ao_ccss_queue_schedule', 'ao_ccss'), 'ao_ccss_queue' );
  }
  
  // Queue cleaning.
  global $ao_ccss_queue;
  $queue_purge_threshold = 100;
  $queue_purge_age       = 24 * 60 * 60;
  $queue_length          = count( $ao_ccss_queue );
  $timestamp_yesterday   = microtime(true) - $queue_purge_age;
  $remove_old_new        = false;
  $queue_altered         = false;

  if ( $queue_length > $queue_purge_threshold ) {
    $remove_old_new = true;
  }

  foreach ($ao_ccss_queue as $path => $job) {
    if ( ( $remove_old_new && $job['jqstat'] == 'NEW' && $job['jctime'] < $timestamp_yesterday ) || in_array( $job['jqstat'], array( 'JOB_FAILED', 'STATUS_JOB_BAD', 'NO_CSS', 'NO_RESPONSE' ) ) ) {
      unset( $ao_ccss_queue[$path] );
      $queue_altered = true;
    }
  }

  // save queue to options!
  if ( $queue_altered ) {
    $ao_ccss_queue_raw = json_encode($ao_ccss_queue);
    update_option('autoptimize_ccss_queue', $ao_ccss_queue_raw, false);
    ao_ccss_log('Queue cleaning done.', 3);
  }

  // re-check key if invalid
  global $ao_ccss_keyst;
  if ( $ao_ccss_keyst == 1 ) {
    ao_ccss_api_generate('','','');
  }
}

// Add cleaning job to a registered event
add_action('ao_ccss_maintenance', 'ao_ccss_cleaning');

// get autoptimize service status file if there is a valid key
function ao_ccss_getservicestatus() {
  global $ao_ccss_keyst;
  global $ao_ccss_rules;

  $nbrt = $nbrp = 0;
  if ( array_key_exists( 'paths', $ao_ccss_rules ) ) {
    $nbrp = count( $ao_ccss_rules['paths'] );
  }
  if ( array_key_exists( 'types', $ao_ccss_rules ) ) {
    $nbrt = count( $ao_ccss_rules['types'] );
  }

  $ver = str_replace( '.', '', AO_CCSS_VER ) . '_ks' . $ao_ccss_keyst . '_pr' . $nbrp . '_tr' . $nbrt;

  $service_availability_resp = wp_remote_get( 'https://misc.optimizingmatters.com/api/autoptimize_service_availablity.json?from=aoccss&ver=' . $ver );
  if ( ! is_wp_error( $service_availability_resp ) ) {
    if ( '200' == wp_remote_retrieve_response_code( $service_availability_resp ) ) {
      $availabilities = json_decode( wp_remote_retrieve_body( $service_availability_resp ), true );
      update_option( 'autoptimize_ccss_servicestatus', $availabilities);
    }
  }
}

// action triggered as scheduled event
add_action('ao_ccss_servicestatus', 'ao_ccss_getservicestatus');
?>
