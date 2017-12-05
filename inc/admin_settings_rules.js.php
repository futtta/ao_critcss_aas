<?php
if ($ao_ccss_debug) echo "console.log('[WARN] Autoptimize CriticalCSS Power-Up is in DEBUG MODE!');\n";
if ($ao_ccss_debug) echo "console.log('[WARN] DO NOT use debug mode on production/live environments!');\n";
?>

document.getElementById("critCssOrigin").style.display = 'none';
document.getElementById("autoptimize_css_defer_inline").style.display = 'none';

jQuery(document).ready(function() {
  critCssArray=JSON.parse(document.getElementById("critCssOrigin").value);
  <?php if ($ao_ccss_debug) echo "console.log('Rules Object:', critCssArray);\n" ?>
  drawTable(critCssArray);
  jQuery("#addCritCssButton").click(function(){addEditRow();});
  jQuery("#editDefaultButton").click(function(){editDefaultCritCss();});
})

function drawTable(critCssArray) {
  jQuery("#rules-list").empty();
  jQuery.each(critCssArray,function(k,v) {
    if (k=="paths") {
      kstring="<?php _e("Path Based Rules", "autoptimize") ?>";
    } else {
      kstring="<?php _e("Conditional Tags, Custom Post Types and Page Templates Rules", "autoptimize") ?>";
    }
    if (!(jQuery.isEmptyObject(v))) {
      jQuery("#rules-list").append("<tr><td colspan='5'><h4>" + kstring + "</h4></td></tr>");
    }
    jQuery("#rules-list").append("<tr class='header'><th><?php _e('Type', 'autoptimize') ?></th><th><?php _e('Target', 'autoptimize') ?></th><th><?php _e('Critical CSS File', 'autoptimize') ?></th><th colspan='2'><?php _e('Actions', 'autoptimize') ?></th></tr>");
    nodeNumber=0;
    jQuery.each(v,function(i,nv){
      nodeNumber++;
      nodeId=k + "_" + nodeNumber;
      hash=nv.hash;
      file=nv.file;
      if (nv.file == 0) {
        file='<?php _e("To be fetched from criticalcss.com in the next queue run...", "autoptimize") ?>';
      } else {
        file=nv.file;
      }
      if (nv.hash === 0) {
        type='<?php _e("MANUAL", "autoptimize") ?>';
        typeClass = 'manual';
      } else {
        type='<?php _e("AUTO", "autoptimize") ?>';
        typeClass = 'auto';
      }
      jQuery("#rules-list").append("<tr class='rule'><td class='type'><span class='badge " + typeClass + "'>" + type + "</span></td><td class='target'>" + i + "</td><td class='file'>" + file + "</td><td class='btn edit'><span class=\"button-secondary\" id=\"" + nodeId + "_edit\"><?php _e("Edit", "autoptimize"); ?></span></td><td class='btn delete'><span class=\"button-secondary\" id=\"" + nodeId + "_remove\"><?php _e("Remove", "autoptimize"); ?></span></td></tr>");
      jQuery("#" + nodeId + "_edit").click(function(){addEditRow(this.id);});
      jQuery("#" + nodeId + "_remove").click(function(){confirmRemove(this.id);});
    })
  });
}

function confirmRemove(idToRemove) {
  jQuery( "#confirm-rm" ).dialog({
    resizable: false,
    height:235,
    modal: true,
    buttons: {
      "<?php _e("Delete", "autoptimize") ?>": function() {
        removeRow(idToRemove);
        updateAfterChange();
        jQuery( this ).dialog( "close" );
      },
      Cancel: function() {
        jQuery( this ).dialog( "close" );
      }
    }
  });
}

function removeRow(idToRemove) {
  splits=idToRemove.split(/_/);
  crit_type=splits[0];
  crit_item=splits[1];
  crit_key=Object.keys(critCssArray[crit_type])[crit_item-1];
  crit_file=critCssArray[crit_type][crit_key].file;
  delete critCssArray[crit_type][crit_key];

  var data = {
    'action': 'rm_critcss',
    'critcss_rm_nonce': '<?php echo wp_create_nonce( "rm_critcss_nonce" );?>',
    'cachebustingtimestamp': new Date().getTime(),
    'critcssfile': crit_file
  };

  jQuery.ajaxSetup({
    async: false
  });

  jQuery.post(ajaxurl, data, function(response) {
    response_array=JSON.parse(response);
    if (response_array["code"]!=200) {
      // not displaying notice, as the end result is OK; the file isn't there
      // displayNotice(response_array["string"]);
    }
  });
}

function addEditRow(idToEdit) {
  resetForm();
  if (idToEdit) {
    dialogTitle="<?php _e('Edit Critical CSS Rule', 'autoptimize') ?>";

    splits=idToEdit.split(/_/);
    crit_type=splits[0];
    crit_item=splits[1];
    crit_key=Object.keys(critCssArray[crit_type])[crit_item-1];
    crit_file=critCssArray[crit_type][crit_key].file;

    jQuery("#critcss_addedit_id").val(idToEdit);
    jQuery("#critcss_addedit_type").val(crit_type);
    jQuery("#critcss_addedit_file").val(crit_file);
    jQuery("#critcss_addedit_css").attr("placeholder", "<?php _e('Loading critical CSS...', 'autoptimize') ?>");
    jQuery("#critcss_addedit_type").attr("disabled",true);

    if (crit_type==="paths") {
      jQuery("#critcss_addedit_path").val(crit_key);
      jQuery("#critcss_addedit_path_wrapper").show();
      jQuery("#critcss_addedit_pagetype_wrapper").hide();
    } else {
      jQuery("#critcss_addedit_pagetype").val(crit_key);
      jQuery("#critcss_addedit_pagetype_wrapper").show();
      jQuery("#critcss_addedit_path_wrapper").hide();
    }

    var data = {
      'action': 'fetch_critcss',
      'critcss_fetch_nonce': '<?php echo wp_create_nonce( "fetch_critcss_nonce" );?>',
      'cachebustingtimestamp': new Date().getTime(),
      'critcssfile': crit_file
    };

    jQuery.post(ajaxurl, data, function(response) {
      response_array=JSON.parse(response);
      if (response_array["code"]==200) {
        jQuery("#critcss_addedit_css").val(response_array["string"]);
      } else {
        jQuery("#critcss_addedit_css").attr("placeholder", "").focus();
      }
    });

  } else {
    dialogTitle="<?php _e('Add Critical CSS Rule', 'autotimize') ?>";

    // default: paths, hide content type field
    jQuery("#critcss_addedit_type").val("paths");
    jQuery("#critcss_addedit_pagetype_wrapper").hide();

    // event handler on type to switch display
    jQuery("#critcss_addedit_type").on('change', function (e) {
      if(this.value==="types") {
        jQuery("#critcss_addedit_pagetype_wrapper").show();
        jQuery("#critcss_addedit_path_wrapper").hide();
      } else {
        jQuery("#critcss_addedit_path_wrapper").show();
        jQuery("#critcss_addedit_pagetype_wrapper").hide();
      }
    });
  }

  jQuery("#addEditCritCss").dialog({
    autoOpen: true,
    height: 500,
    width: 700,
    title: dialogTitle,
    modal: true,
    buttons: {
      "<?php _e("Submit", "autoptimize") ?>": saveEditCritCss,
      Cancel: function() {
          resetForm();
          jQuery("#addEditCritCss").dialog( "close" );
      }
    }
  });
}

function editDefaultCritCss(){
  document.getElementById("dummyDefault").value=document.getElementById("autoptimize_css_defer_inline").value;
  jQuery("#default_critcss_wrapper").dialog({
    autoOpen: true,
    height: 500,
    width: 700,
    title: "<?php _e("Your Default Critical CSS", "autoptimize"); ?>",
    modal: true,
    buttons: {
      "<?php _e("Submit", "autoptimize") ?>": function(){
      document.getElementById("autoptimize_css_defer_inline").value=document.getElementById("dummyDefault").value;
      jQuery("#unSavedWarning").show();
      jQuery("#default_critcss_wrapper").dialog( "close" );
      },
      Cancel: function() {
          jQuery("#default_critcss_wrapper").dialog( "close" );
      }
    }
  });
}

function saveEditCritCss(){
  critcssfile=jQuery("#critcss_addedit_file").val();
  critcsscontents=jQuery("#critcss_addedit_css").val();
  critcsstype=jQuery("#critcss_addedit_type").val();
  critcssid=jQuery("#critcss_addedit_id").val();

  if (critcssid) {
    // this was an "edit" action, so remove original
    // will also remove the file, but that will get rewritten anyway
    removeRow(critcssid);
  }
  if (critcsstype==="types") {
    critcsstarget=jQuery("#critcss_addedit_pagetype").val();
  } else {
    critcsstarget=jQuery("#critcss_addedit_path").val();
  }

  if (!critcssfile && !critcsscontents) {
    critcssfile=0;
  } else if (!critcssfile && critcsscontents) {
    critcssfile="ccss_" + md5(critcsscontents+critcsstarget) + ".css";
  }

  // Compose the rule object
  critCssArray[critcsstype][critcsstarget]={};
  critCssArray[critcsstype][critcsstarget].hash=0;
  critCssArray[critcsstype][critcsstarget].file=critcssfile;

  <?php if ($ao_ccss_debug) echo "console.log('[RULE PROPERTIES] Type:', critcsstype, ', Target:', critcsstarget, ', Hash:', 0, ', File:',  critcssfile);" ?>

  updateAfterChange();

  var data = {
    'action': 'save_critcss',
    'critcss_save_nonce': '<?php echo wp_create_nonce( "save_critcss_nonce" );?>',
    'critcssfile': critcssfile,
    'critcsscontents': critcsscontents
  };

  jQuery.post(ajaxurl, data, function(response) {
    response_array=JSON.parse(response);
    if (response_array["code"]!=200) {
      displayNotice(response_array["string"]);
    }
  });

  jQuery("#addEditCritCss").dialog( "close" );
}

function updateAfterChange() {
  document.getElementById("critCssOrigin").value=JSON.stringify(critCssArray);
  drawTable(critCssArray);
  jQuery("#unSavedWarning").show();
}

function displayNotice(textIn) {
  jQuery('<div class="error notice is-dismissable"><p>'+textIn+'</p></div>').insertBefore("#unSavedWarning");
}

function resetForm() {
  jQuery("#critcss_addedit_css").attr("placeholder", "Copy/paste minified critical CSS here or just leave empty to fetch it from criticalcss.com.");
  jQuery("#critcss_addedit_type").attr("disabled",false);
  jQuery("#critcss_addedit_path_wrapper").show();
  jQuery("#critcss_addedit_id").val("");
  jQuery("#critcss_addedit_path").val("");
  jQuery("#critcss_addedit_file").val("");
  jQuery("#critcss_addedit_pagetype").val("");
  jQuery("#critcss_addedit_css").val("");
}