document.getElementById("ao-ccss-queue").style.display = 'none';

// Get queue object and call table renderer
jQuery(document).ready(function() {
  aoCssQueue = JSON.parse(document.getElementById("ao-ccss-queue").value);
  <?php if ($ao_ccss_debug) echo "console.log('Queue Object:', aoCssQueue);\n" ?>
  drawQueueTable(aoCssQueue);
  //jQuery("#editDefaultButton").click(function(){editDefaultCritCss();});
})

// Render the queue in a table
function drawQueueTable(aoCssQueue) {
  jQuery("#queue").empty();
  rowNumber=0;
  jQuery.each(aoCssQueue, function(path, keys) {
    <?php if ($ao_ccss_debug) echo "console.log('Job Object:', path, keys);\n" ?>

    // Prepare commom job values
    target = keys.rtarget;
    type   = keys.ptype;
    ctime  = EpochToDate(keys.jctime);

    // Prepare row ID
    rowNumber++;
    rowId = target + '_' + rowNumber;

    // Prepare job statuses
    if (keys.jqstat === 'NEW') {
      status      = 'N';
      statusClass = 'new';
      title       = '<?php _e("NEW", "autoptimize"); ?>';
      buttons     = '<?php _e("No Action", "autoptimize"); ?>';
    } else if (keys.jqstat === 'JOB_QUEUED' || keys.jqstat === 'JOB_ONGOING') {
      status      = 'P';
      statusClass = 'pending';
      title       = '<?php _e("PENDING", "autoptimize"); ?>';
      buttons     = '<?php _e("No Action", "autoptimize"); ?>';
    } else if (keys.jqstat === 'JOB_DONE' && keys.jrstat === 'GOOD' && keys.jvstat === 'GOOD') {
      status      = 'D';
      statusClass = 'done';
      title       = '<?php _e("DONE", "autoptimize"); ?>';
      buttons     = '<span class="button-secondary" id="' + rowId + '"_remove"><?php _e("Remove", "autoptimize"); ?></span>';
    } else if (keys.jqstat === 'JOB_DONE' && keys.jrstat === 'GOOD' && keys.jvstat === 'WARN') {
      status      = 'R';
      statusClass = 'review';
      title       = '<?php _e("REVIEW", "autoptimize"); ?>';
      buttons     = '<span class="button-secondary" id="' + rowId + '_approve"><?php _e("Approve", "autoptimize"); ?></span>&nbsp;<span class="button-secondary" id="' + nodeId + '_reject"><?php _e("Reject", "autoptimize"); ?></span>';
    } else if (keys.jqstat === 'JOB_FAILED' || (keys.jqstat === 'JOB_DONE' && keys.jrstat === 'GOOD' && keys.jvstat === 'BAD')) {
      status      = 'E';
      statusClass = 'error';
      title       = '<?php _e("ERROR", "autoptimize"); ?>';
      buttons     = '<span class="button-secondary" id="' + rowId + '_retry"><?php _e("Retry", "autoptimize"); ?></span>&nbsp;<span class="button-secondary" id="' + nodeId + '_contact"><?php _e("Contact", "autoptimize"); ?></span>';
    } else {
      status      = 'U';
      statusClass = 'unknown';
      title       = '<?php _e("UNKNOWN", "autoptimize"); ?>';
      buttons     = '<span class="button-secondary" id="' + nodeId + '_contact"><?php _e("Contact", "autoptimize"); ?></span>';
    }

    // Prepare job finish time
    if (keys.jftime === null) {
      ftime = '<?php _e("Waiting...", "autoptimize"); ?>';
    } else {
      ftime = EpochToDate(keys.jftime);
    }

    // Append job entry
    jQuery("#queue").append("<tr class='job " + statusClass + "'><td class='status'><span class='badge " + statusClass + "' title='<?php _e("Job status is ", "autoptimize"); ?>" + title + "'>" + status + "</span></td><td>" + target + "</td><td>" + path + "</td><td>" + type + "</td><td>" + ctime + "</td><td>" + ftime + "</td><td class='btn'>" + buttons + "</td></tr>");
  });
}

//Epoch To Date
function EpochToDate(epoch) {
  if (epoch < 10000000000)
    epoch *= 1000; // convert to milliseconds (Epoch is usually expressed in seconds, but Javascript uses Milliseconds)
  var epoch = epoch + (new Date().getTimezoneOffset() * -1); //for timeZone
  var sdate = new Date(epoch);
  var ldate = sdate.toLocaleString();
  return ldate;
}