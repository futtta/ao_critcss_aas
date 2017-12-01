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
  jQuery.each(aoCssQueue, function(k, v) {
    console.log('Job Object:', k, v);
    type   = v.type;
    status = v.jqstat;
    ctime  = EpochToDate(v.jctime);
    if (v.jftime === null) {
      ftime = '';
    } else {
      ftime = EpochToDate(v.jftime);
    }
    jQuery("#queue").append("<tr class='job'><td>" + k + "</td><td>" + type + "</td><td>" + status + "</td><td>" + ctime + "</td><td>" + ftime + "</td><td class='btn delete'><span class=\"button-secondary\" id=\"" + nodeId + "_remove\"><?php _e("Remove", "autoptimize"); ?></span></td></tr>");
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