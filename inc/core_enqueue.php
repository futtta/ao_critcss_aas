<?php

// Enqueue jobs for asynchronous processing
// NOTE: implements section 4, id 2 of the specs
function ao_ccss_enqueue($hash) {

  // As AO could be set to provide different CSS'es for logged in users,
  // just enqueue jobs for NOT logged in users to avoid useless jobs
  if (!is_user_logged_in()) {

    // Attach rules object and debug
    global $ao_ccss_rules;

    // Load the queue, get request path and page type, and initialize the queue update flag
    $ao_ccss_queue_raw = get_option('autoptimize_ccss_queue', FALSE);
    $req_path          = $_SERVER['REQUEST_URI'];
    $req_type          = ao_ccss_get_type();
    $job_qualify       = FALSE;
    $rule_target       = FALSE;
    $rule_properties   = FALSE;
    $queue_update      = FALSE;

    // Match for paths in rules
    // NOTE: implements 'Rule Matching Stage' in the 'Job Submission Flow' of the specs
    foreach ($ao_ccss_rules['paths'] as $path => $properties) {

      ao_ccss_log('Qualifying path <' . $req_path . '> for job submission by path rule <' . $path . '>');

      // Path match
      if (preg_match('#' . $req_path . '#', $path)) {

        // There's a path match in the rule, so job QUALIFIES
        $job_qualify     = TRUE;
        $rule_target     = $path;
        $rule_properties = $properties;
        ao_ccss_log('Path <' . $req_path . '> QUALIFIED for job submission by path rule <' . $path . '>');

        // Stop processing other path rules
        break;
      }
    }

    // Match for types in rules if no path rule matches
    if (!$job_qualify) {
      foreach ($ao_ccss_rules['types'] as $type => $properties) {

        ao_ccss_log('Qualifying page type <' . $req_type . '> on <' . $req_path . '> for job submission by type rule <' . $type . '>');

        // Type match
        if ($req_type == $type) {

          // There's a type match in the rule, so job QUALIFIES
          $job_qualify     = TRUE;
          $rule_target     = $type;
          $rule_properties = $properties;
          ao_ccss_log('Page type <' . $req_type . '> on <' . $req_path . '> QUALIFIED for job submission by type rule <' . $type . '>');

          // Stop processing other type rules
          break;
        }
      }
    }

    // If rule is MANUAL, job does not qualify despite what previous evaluations says
    if (is_array($rule_properties) && $rule_properties['hash'] == FALSE) {
      $job_qualify = FALSE;
      ao_ccss_log('Job submission DISQUALIFIED by MANUAL rule <' . $rule_target . '> with hash <' . $rule_properties['hash'] . '>');
    }


    // Submit job
    // NOTE: implements 'Job Submission/Update Stage' in the 'Job Submission Flow' of the specs
    if ($job_qualify) {

      ao_ccss_log('Job submission QUALIFIED by AUTO rule <' . $rule_target . '> with hash <' . $rule_properties['hash'] . '>');

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
        $queue_update = TRUE;

      // This is an existing job
      } else {

        // The job is still NEW, most likely this is extra CSS file for the same page that needs a hash
        if ($ao_ccss_queue[$req_path]['jqstat'] == 'NEW') {

          // Add hash if it's not already in the job
          if (!in_array($hash, $ao_ccss_queue[$req_path]['hashes'])) {

            // Push new hash to its array and update flag
            $queue_update = array_push($ao_ccss_queue[$req_path]['hashes'], $hash);

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
            $queue_update = TRUE;
          }
        }
      }

      // Save the job to the queue and return
      if ($queue_update) {
        $ao_ccss_queue_raw = json_encode($ao_ccss_queue);
        update_option('autoptimize_ccss_queue', $ao_ccss_queue_raw);
        return TRUE;

      // Or just return false if no job whas added
      } else {
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
  $page_type = false;

  // Iterates over the array to match a type
  foreach ($ao_ccss_types as $type) {

    // Match custom post types
    if (strpos($type,'custom_post_') !== FALSE) {

      // Replace prefix and break the loop
      if (get_post_type(get_the_ID()) === substr($type, 12)) {
        $page_type = str_replace('custom_post_', '', $type);
        break;
      }

    // Match templates
    } elseif (strpos($type, 'template_') !== FALSE) {

      // Replace prefix and break the loop
      if (is_page_template(substr($type, 9))) {
        $page_type = str_replace('template_', '', $type);
        break;
      }

    // Match all other existing types
    } elseif (function_exists($type) && call_user_func($type)) {

      // Replace BBPress prefix
      if ($type == 'bbp_is_bbpress') {
        $page_type = str_replace('bbp_', '', $type);

      // Replace BudyPress prefix
      } elseif ($type == 'bp_is_buddypress') {
         $page_type = str_replace('bp_', '', $type);

      // Replace WooCommerce prefix
      } elseif (strpos($type, 'woo_') !== FALSE) {
         $page_type = str_replace('woo_', '', $type);

      // Assign all other types
      } else {
        $page_type = $type;
      }

      // Break the loop
      break;
    }
  }

  // Return the page type
  return $page_type;
}

?>