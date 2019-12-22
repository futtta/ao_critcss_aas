<?php

// Enqueue jobs for asynchronous processing
// NOTE: implements section 4, id 2 of the specs
function ao_ccss_enqueue($hash) {

  // Get key status
  $key = ao_ccss_key_status(FALSE);

  // Queue is available to anyone...
  $enqueue = TRUE;
  // ...which are not the ones bellow
  // NOTE: out of scope check for allowed job enqueuing (inc. issue #2)
  if (is_user_logged_in() || is_feed() || is_404() || (defined('DOING_AJAX') && DOING_AJAX) || ao_ccss_ua() || $key['status'] == 'nokey' || $key['status'] == 'invalid') {
    $enqueue = FALSE;
    ao_ccss_log("Job queuing is not available for WordPress's logged in users, feeds, error pages, ajax calls, to criticalcss.com itself or when a valid API key is not found", 3);
  }

  // Continue if queue is available
  if ($enqueue) {

    // Attach required arrays/ vars
    global $ao_ccss_rules;
    global $ao_ccss_queue_raw;
    global $ao_ccss_queue;
    global $ao_ccss_forcepath;

    // Get request path and page type, and initialize the queue update flag
    $req_path          = strtok($_SERVER['REQUEST_URI'],'?');
    $req_type          = ao_ccss_get_type();
    $job_qualify       = FALSE;
    $target_rule       = FALSE;
    $rule_properties   = FALSE;
    $queue_update      = FALSE;

    // Match for paths in rules
    // NOTE: implements 'Rule Matching Stage' in the 'Job Submission Flow' of the specs
    foreach ($ao_ccss_rules['paths'] as $path => $props) {

      // Prepare rule target and log
      $target_rule = 'paths|' . $path;
      ao_ccss_log('Qualifying path <' . $req_path . '> for job submission by rule <' . $target_rule . '>', 3);

      // Path match
      // -> exact match needed for AUTO rules
      // -> partial match OK for MANUAL rules (which have empty hash and a file with CCSS)
      if ( $path === $req_path || ( $props['hash'] == FALSE && $props['file'] != FALSE && preg_match('|' . $path . '|', $req_path) ) ) {

        // There's a path match in the rule, so job QUALIFIES with a path rule match
        $job_qualify     = TRUE;
        $rule_properties = $props;
        ao_ccss_log('Path <' . $req_path . '> QUALIFIED for job submission by rule <' . $target_rule . '>', 3);

        // Stop processing other path rules
        break;
      }
    }

    // Match for types in rules if no path rule matches and if we're not enforcing paths
    if (!$job_qualify && ( !$ao_ccss_forcepath || !in_array($req_type,apply_filters('autoptimize_filter_ccss_coreenqueue_forcepathfortype', array('is_page'))))) {
      foreach ($ao_ccss_rules['types'] as $type => $props) {

        // Prepare rule target and log
        $target_rule = 'types|' . $type;
        ao_ccss_log('Qualifying page type <' . $req_type . '> on path <' . $req_path . '> for job submission by rule <' . $target_rule . '>', 3);

        // Type match
        if ($req_type == $type) {

          // There's a type match in the rule, so job QUALIFIES with a type rule match
          $job_qualify     = TRUE;
          $rule_properties = $props;
          ao_ccss_log('Page type <' . $req_type . '> on path <' . $req_path . '> QUALIFIED for job submission by rule <' . $target_rule . '>', 3);

          // Stop processing other type rules
          break;
        }
      }
    }

    // If job qualifies but rule hash is false and file isn't false  (MANUAL rule), job does not qualify despite what previous evaluations says
    if ($job_qualify && $rule_properties['hash'] == FALSE && $rule_properties['file'] != FALSE) {
      $job_qualify = FALSE;
      ao_ccss_log('Job submission DISQUALIFIED by MANUAL rule <' . $target_rule . '> with hash <' . $rule_properties['hash'] . '> and file <' . $rule_properties['file'] . '>', 3);

    // But if job does not qualify and rule properties are set, job qualifies as there is no matching rule for it yet
    } elseif (!$job_qualify && empty($rule_properties)) {

      // Fill-in the new target rule
      $job_qualify = TRUE;
      
      // Should we switch to path-base AUTO-rules? Conditions:
      // 1. forcepath option has to be enabled (off by default)
      // 2. request type should be (by default, but filterable) one of is_page (removed for now: woo_is_product or woo_is_product_category)
      if ($ao_ccss_forcepath && in_array($req_type,apply_filters('autoptimize_filter_ccss_coreenqueue_forcepathfortype',array('is_page')))) {
        if ($req_path !== "/") {
          $target_rule = 'paths|' . $req_path;
        } else {
          // Exception; we don't want a path-based rule for "/" as that messes things up, hard-switch this to a type-based is_front_page rule
          $target_rule = 'types|' . 'is_front_page';
        }
      } else {
        $target_rule = 'types|' . $req_type;
      }
      ao_ccss_log('Job submission QUALIFIED by MISSING rule for page type <' . $req_type . '> on path <' . $req_path . '>, new rule target is <' . $target_rule . '>', 3);

    // Or just log a job qualified by a matching rule
    } else {
      ao_ccss_log('Job submission QUALIFIED by AUTO rule <' . $target_rule . '> with hash <' . $rule_properties['hash'] . '> and file <' . $rule_properties['file'] . '>', 3);
    }

    // Submit job
    // NOTE: implements 'Job Submission/Update Stage' in the 'Job Submission Flow' of the specs
    if ($job_qualify) {

      // This is a NEW job
      if (!array_key_exists($req_path, $ao_ccss_queue)) {

        // Merge job into the queue
        $ao_ccss_queue[$req_path] = ao_ccss_define_job(
                                      $req_path,
                                      $target_rule,
                                      $req_type,
                                      $hash,
                                      NULL,
                                      NULL,
                                      NULL,
                                      NULL,
                                      TRUE
                                    );

        // Set update flag
        $queue_update = TRUE;

      // This is an existing job
      } else {

        // The job is still NEW, most likely this is extra CSS file for the same page that needs a hash
        if ($ao_ccss_queue[$req_path]['jqstat'] == 'NEW') {

          // Add hash if it's not already in the job
          if (!in_array($hash, $ao_ccss_queue[$req_path]['hashes'])) {

            // Push new hash to its array and update flag
            $queue_update = array_push($ao_ccss_queue[$req_path]['hashes'], $hash);

            // Log job update
            ao_ccss_log('Hashes UPDATED on local job id <' . $ao_ccss_queue[$req_path]['ljid'] . '>, job status NEW, target rule <' . $ao_ccss_queue[$req_path]['rtarget'] . '>, hash added: ' . $hash, 3);

            // Return from here as the hash array is already updated
            return TRUE;
          }

        // Allow requeuing jobs that are not NEW, JOB_QUEUED or JOB_ONGOING
        } elseif ($ao_ccss_queue[$req_path]['jqstat'] != 'NEW' && $ao_ccss_queue[$req_path]['jqstat'] != 'JOB_QUEUED' && $ao_ccss_queue[$req_path]['jqstat'] != 'JOB_ONGOING') {

          // Merge new job keeping some previous job values
          $ao_ccss_queue[$req_path] = ao_ccss_define_job(
                                        $req_path,
                                        $target_rule,
                                        $req_type,
                                        $hash,
                                        $ao_ccss_queue[$req_path]['file'],
                                        $ao_ccss_queue[$req_path]['jid'],
                                        $ao_ccss_queue[$req_path]['jrstat'],
                                        $ao_ccss_queue[$req_path]['jvstat'],
                                        FALSE
                                      );

          // Set update flag
          $queue_update = TRUE;
        }
      }

      // Persist the job to the queue and return
      if ($queue_update) {
        $ao_ccss_queue_raw = json_encode($ao_ccss_queue);
        update_option('autoptimize_ccss_queue', $ao_ccss_queue_raw, false);
        return TRUE;

      // Or just return false if no job was added
      } else {
        ao_ccss_log('A job for path <' . $req_path . '> already exist with NEW or PEDING status, skipping job creation', 3);
        return FALSE;
      }
    }
  }
}

// Get the type of a page
function ao_ccss_get_type() {

  // Attach the conditional tags array
  global $ao_ccss_types;

  // By default, a page type is false
  $page_type = FALSE;

  // Iterates over the array to match a type
  foreach ($ao_ccss_types as $type) {
    // Match custom post types
    if (strpos($type,'custom_post_') !== FALSE) {
      if (get_post_type(get_the_ID()) === substr($type, 12)) {
        $page_type = $type;
        break;
      }
    // If templates; don't break, templates become manual-only rules 
    } elseif (strpos($type, 'template_') !== FALSE) {
      /* if (is_page_template(substr($type, 9))) {
        $page_type = $type;
        break; 
      } */
    // Match all other existing types
    } else {
      // but remove prefix to be able to check if the function exists & returns true
      $_type = str_replace(array('woo_','bp_','bbp_','edd_'),'',$type);
      if (function_exists($_type) && call_user_func($_type)) {
        // Make sure we only return is_front_page (and is_home) for one page, not for the "paged frontpage" (/page/2 ..)
        if ( ($_type !== 'is_front_page' && $_type !== 'is_home') || !is_paged() ) {
          $page_type = $type;
          break;
        }
      }
    }
  }

  // Return the page type
  return $page_type;
}

// Define a job entry to be created or updated
function ao_ccss_define_job($path, $target, $type, $hash, $file, $jid, $jrstat, $jvstat, $create) {

    // Define commom job properties
    $path            = array();
    $path['ljid']    = ao_ccss_job_id();
    $path['rtarget'] = $target;
    $path['ptype']   = $type;
    $path['hashes']  = array($hash);
    $path['hash']    = $hash;
    $path['file']    = $file;
    $path['jid']     = $jid;
    $path['jqstat']  = 'NEW';
    $path['jrstat']  = $jrstat;
    $path['jvstat']  = $jvstat;
    $path['jctime']  = microtime(TRUE);
    $path['jftime']  = NULL;

    // Set operation requested
    if ($create) {
      $operation = 'CREATED';
    } else {
      $operation = 'UPDATED';
    }

    // Log job creation
    ao_ccss_log('Job ' . $operation . ' with local job id <' . $path['ljid'] . '> for target rule <' . $target . '>', 3);

    return $path;
}

// Generate random strings for the local job ID
// Based on https://stackoverflow.com/a/4356295
function ao_ccss_job_id($length = 6) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
  $charactersLength = strlen($characters);
  $randomString = 'j-';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }
  return $randomString;
}

// Check for criticalcss.com user agent
// NOTE: out of scope check for criticalcss.com UA
function ao_ccss_ua() {

  // Get UA
  $agent='';
  if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $agent = $_SERVER['HTTP_USER_AGENT'];
  }

  // Check for UA and return TRUE when criticalcss.com is the detected UA, false when not
  $rtn = strpos($agent, AO_CCSS_URL);
  if ($rtn === 0) {
    $rtn = TRUE;
  } else {
    $rtn = FALSE;
  }
  return ($rtn);
}
?>
