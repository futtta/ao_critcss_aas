<?php

// NOTE: implements section 3 of the specs

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

  // Iterates over the entire queue
  foreach ($ao_ccss_queue as $path => $jprops) {

    // Initialize queue updte flag
    $queue_update = FALSE;

    // Process NEW jobs
    if ($jprops['jqstat'] == 'NEW') {

      // Log the new job
      ao_ccss_log('Found NEW job with local ID <' . $jprops['ljid'] . '>, starting its queue processing', 3);

      // Initialize hash checking flags
      $newhash  = FALSE;
      $diffhash = FALSE;

      // Hash checks for new jobs
      if (empty($jprops['hash']) && count($jprops['hashes']) == 1) {

        // Set job hash
        $jprops['hash'] = $jprops['hashes'][0];
        $newhash        = TRUE;
        ao_ccss_log('Job id <' . $jprops['ljid'] . '> has an empty hash, updating with SINGLE hash <' . $jprops['hash'] . '>', 3);

      // Check if job has more than one hash to concatenate and hash them
      } elseif (empty($jprops['hash']) && count($jprops['hashes']) >= 1) {

        // Loop through hashes to concatenate them
        $nhash = '';
        foreach ($jprops['hashes'] as $shash) {
          $nhash .= $shash;
        }

        // Set job hash
        $jprops['hash'] = md5($nhash);
        $newhash        = TRUE;
        ao_ccss_log('Job id <' . $jprops['ljid'] . '> has an empty hash, updating with COMPOSITE hash <' . $jprops['hash'] . '>', 3);
      }

      // Hash checks for existing jobs
      $hash_type = '';
      if (!empty($jprops['hash']) && $newhash == FALSE) {

        // Initialize temporary hash
        $tmphash = '';

        // Check if job has only one hash
        if (count($jprops['hashes']) == 1) {

          // Set job hash
          $tmphash   = $jprops['hashes'][0];
          $hash_type = 'SINGLE';

        // Check if job has more than one hash to concatenate and hash them
        } elseif (empty($jprops['hash']) && count($jprops['hashes']) >= 1) {

          // Loop through hashes to concatenate them
          $tmphash = '';
          foreach ($jprops['hashes'] as $shash) {
            $tmphash .= $shash;
          }

          // Set job hash
          $tmphash   = md5($jhash);
          $hash_type = 'COMPOSITE';
        }

        // If temporary and job hashes are different, update it
        if ($tmphash != $jprops['hash']) {
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> hash <' . $jprops['hash'] . '> has changed, updating with new ' . $hash_type . ' hash <' . $tmphash . '>', 3);
          $jprops['hash'] = $tmphash;
          $diffhash = TRUE;
        }

      }

      // If job hash is new or different of a previous one
      if ($newhash || $diffhash) {

        // Dispatch the job generation request
        $apireq = ao_ccss_api_generate($path);

        // Request has a valid result
        if ($apireq) {

          // Update job properties
          $jprops['jid']    = $apireq['job']['id'];
          $jprops['jqstat'] = $apireq['job']['status'];
          $queue_update     = TRUE;
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> generation request successful, remote id <' . $jprops['jid'] . '>, status <' . $jprops['jqstat'] . '>', 3);

        // Request has failed
        } else {

          // Update job properties
          $jprops['jqstat'] = 'JOB_FAILED';
          $queue_update     = TRUE;
          ao_ccss_log('Job id <' . $jprops['ljid'] . '> generation request has FAILED, check log messages above for more information', 2);
        }

      // Job hash is equal of a previous one
      } else {

        // Update job status and finish time
        $jprops['jqstat'] = 'JOB_DONE';
        $jprops['jftime'] = microtime(TRUE);
        $queue_update     = TRUE;
        ao_ccss_log('Job id <' . $jprops['ljid'] . '> hash <' . $jprops['hash'] . '> has not changed since last processing, job is done', 3);
      }

      // Persist update job to the queue
      if ($queue_update) {
        $ao_ccss_queue[$path] = $jprops;
        $ao_ccss_queue_raw = json_encode($ao_ccss_queue);
        update_option('autoptimize_ccss_queue', $ao_ccss_queue_raw);

      // Or just log if no job was updated
      } else {
        ao_ccss_log('Queue not updated by job id <' . $jprops['ljid'] . '>', 3);
      }

    // Process QUEUED and ONGOING jobs
    } elseif ($jprops['jqstat'] == 'JOB_QUEUED' || $jprops['jqstat'] == 'JOB_ONGOING') {

      // Log and dispatch generate request to criticalcss.com
      ao_ccss_log('Found ' . $jprops['jqstat'] . ' job with local ID <' . $jprops['ljid'] . '> and remote ID <' . $jprops['jid'] . '>, continuing its queue processing', 3);
      //ao_ccss_api_results($path);
    }

    // Wait 5 seconds before process next job due criticalcss.com API limits
    ao_ccss_log('Wait 5 seconds before process next job due criticalcss.com API limits', 3);
    sleep(5);
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

  // Prepare viewport size
  global $ao_ccss_viewport;

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

  // Dispatch the request and store its response code
  $response = wp_safe_remote_post($url, $args);
  $code     = wp_remote_retrieve_response_code($response);
  $body     = wp_remote_retrieve_body($response);

  // Response code is ok (200)
  if ($code == 200) {

    // Log successful and return encoded request body
    ao_ccss_log('criticalcss.com: POST generate request for path <' . $src_url . '> replied sucessfully', 3);
    return json_decode($body, TRUE);

  // Response code is anything else
  } else {

    // Log failed request and return false
    ao_ccss_log('criticalcss.com: POST generate request for path <' . $src_url . '> replied with error code <' . $code . ">, body follows...", 2);
    ao_ccss_log($body, 2);
    return FALSE;
  }
}

// GET jobs from criticalcss.com and return responses
function ao_ccss_api_results($jobid) {

  // Log request start
  ao_ccss_log('criticalcss.com: GET results request for path <' . $src_url . '>', 3);

  // Get key
  global $ao_ccss_key;
  $key = $ao_ccss_key;

  // Prepare the request
  $url  = AO_CCSS_API . 'results?resultId=' . $jobid;
  $args = array(
    'headers' => array(
      'Content-type'  => 'application/json; charset=utf-8',
      'authorization' => 'JWT ' . $key
    )
  );

  // Dispatch the request and store its response code
  $response = wp_remote_get($url, $args);
  $code     = $response['response']['code'];

  // Response code is ok (200)
  if ($code == 200) {

    ao_ccss_log("RESULTS RESPONSE: \n" . print_r($response, TRUE) . "\n", 3);

  // Response code is anything else
  } else {

    ao_ccss_log("RESULTS RESPONSE: \n" . print_r($response, TRUE) . "\n", 3);
  }
}