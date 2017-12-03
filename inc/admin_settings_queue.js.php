// Hide object text box
document.getElementById("ao-ccss-queue").style.display = 'none';

// Get queue object and call table renderer
jQuery(document).ready(function() {

  // Instance queue object
  aoCssQueue = JSON.parse(document.getElementById("ao-ccss-queue").value);
  <?php if ($ao_ccss_debug) echo "console.log('Queue Object:', aoCssQueue);\n" ?>

  // Render queue table
  drawQueueTable(aoCssQueue);

  // Let it be sortable
  jQuery("#queue-tbl").tablesorter({
    sortList: [[0,0]],
    headers: {6: {sorter: false}}
  });

  //jQuery("#editDefaultButton").click(function(){editDefaultCritCss();});
})

// Render the queue in a table
function drawQueueTable(aoCssQueue) {
  jQuery("#queue").empty();
  rowNumber=0;
  jQuery.each(aoCssQueue, function(path, keys) {

    // Prepare commom job values
    ljid      = keys.ljid;
    targetArr = keys.rtarget.split('|');
    target    = targetArr[1];
    type      = keys.ptype;
    ctime     = EpochToDate(keys.jctime);

    // Prepare job statuses
    // Status: NEW (N, sort priority 6)
    if (keys.jqstat === 'NEW') {
      status      = '<span class="hidden">6</span>N';
      statusClass = 'new';
      title       = '<?php _e("NEW", "autoptimize"); ?>';
      buttons     = '<?php _e("None", "autoptimize"); ?>';

    // Status: PENDING (P, sort priority 5)
    } else if (keys.jqstat === 'JOB_QUEUED' || keys.jqstat === 'JOB_ONGOING') {
      status      = '<span class="hidden">5</span>P';
      statusClass = 'pending';
      title       = '<?php _e("PENDING", "autoptimize"); ?>';
      buttons     = '<?php _e("None", "autoptimize"); ?>';

    // Status: DONE (D, sort priority 4)
    } else if (keys.jqstat === 'JOB_DONE' && keys.jrstat === 'GOOD' && keys.jvstat === 'GOOD') {
      status      = '<span class="hidden">4</span>D';
      statusClass = 'done';
      title       = '<?php _e("DONE", "autoptimize"); ?>';
      buttons     = '<span class="button-secondary" id="' + ljid + '_remove"><?php _e("Remove", "autoptimize"); ?></span>';

    // Status: REVIEW (R, sort priority 2)
    } else if (keys.jqstat === 'JOB_DONE' && keys.jrstat === 'GOOD' && keys.jvstat === 'WARN') {
      status      = '<span class="hidden">2</span>R';
      statusClass = 'review';
      title       = '<?php _e("REVIEW", "autoptimize"); ?>';
      buttons     = '<span class="button-secondary" id="' + ljid + '_approve"><?php _e("Approve", "autoptimize"); ?></span>&nbsp;<span class="button-secondary" id="' + ljid + '_reject"><?php _e("Reject", "autoptimize"); ?></span>';

    // Status: ERROR (E, sort priority 1)
    } else if (keys.jqstat === 'JOB_FAILED' || (keys.jqstat === 'JOB_DONE' && keys.jrstat === 'GOOD' && keys.jvstat === 'BAD')) {
      status      = '<span class="hidden">1</span>E';
      statusClass = 'error';
      title       = "<?php _e('ERROR', 'autoptimize'); ?>\n<?php _e('Info from criticalcss.com:', 'autoptimize'); ?>\n<?php _e('- Job ID: ', 'autoptimize'); ?>" + keys.jid + "\n<?php _e('- Status: ', 'autoptimize'); ?>" + keys.jqstat + "\n<?php _e('- Result: ', 'autoptimize'); ?>" + keys.jrstat + "\n<?php _e('- Validation: ', 'autoptimize'); ?>" + keys.jvstat;
      buttons     = '<span class="button-secondary" id="' + ljid + '_retry"><?php _e("Retry", "autoptimize"); ?></span>&nbsp;<span class="button-secondary" id="' + ljid + '_help"><a hef="https://criticalcss.com/faq/" target="_blank"><?php _e("Help", "autoptimize"); ?></a></span>';

    // Status: UNKNOWN (U, sort priority 3)
    } else {
      status      = '<span class="hidden">3</span>U';
      statusClass = 'unknown';
      title       = "<?php _e('UNKNOWN', 'autoptimize'); ?>\n<?php _e('Info from criticalcss.com:', 'autoptimize'); ?>\n<?php _e('- Job ID: ', 'autoptimize'); ?>" + keys.jid + "\n<?php _e('- Status: ', 'autoptimize'); ?>" + keys.jqstat + "\n<?php _e('- Result: ', 'autoptimize'); ?>" + keys.jrstat + "\n<?php _e('- Validation: ', 'autoptimize'); ?>" + keys.jvstat;
      buttons     = '<span class="button-secondary" id="' + ljid + '_help"><a hef="https://criticalcss.com/faq/" target="_blank"><?php _e("Help", "autoptimize"); ?></a></span>';
    }

    // Prepare job finish time
    if (keys.jftime === null) {
      ftime = '<?php _e("Not finished yet...", "autoptimize"); ?>';
    } else {
      ftime = EpochToDate(keys.jftime);
    }

    // Append job entry
    jQuery("#queue").append("<tr id='" + ljid + "' class='job " + statusClass + "'><td class='status'><span class='badge " + statusClass + "' title='<?php _e("Job status is ", "autoptimize"); ?>" + title + "'>" + status + "</span></td><td>" + target + "</td><td>" + path + "</td><td>" + type + "</td><td>" + ctime + "</td><td>" + ftime + "</td><td class='btn'>" + buttons + "</td></tr>");
  });
}

// Convert epoch to date for job times
function EpochToDate(epoch) {
  if (epoch < 10000000000)
    epoch *= 1000; // convert to milliseconds (Epoch is usually expressed in seconds, but Javascript uses Milliseconds)
  var epoch = epoch + (new Date().getTimezoneOffset() * -1); //for timeZone
  var sdate = new Date(epoch);
  var ldate = sdate.toLocaleString();
  return ldate;
}