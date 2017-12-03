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
  //ao_ccss_log("I'm the queue bot and I have nothing to do... yet. ;)", 3);
}
