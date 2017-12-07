// Export and download settings
function exportSettings(idToEdit) {
  console.log('Exporting...');
  var data = {
    'action': 'ao_ccss_export',
    'ao_ccss_export_nonce': '<?php echo wp_create_nonce("ao_ccss_export_nonce"); ?>',
  };

  jQuery.post(ajaxurl, data, function(response) {
    response_array=JSON.parse(response);
    if (response_array['code'] == 200) {
      window.location.href = '<?php echo content_url(); ?>/cache/ao_ccss/' + response_array['file'];
    }
  });
}

// Upload and import settings
function upload(){
  var fd = new FormData();
  var file = jQuery(document).find('#settingsfile');
  var settings_file = file[0].files[0];
  fd.append('file', settings_file);
  fd.append('action', 'ao_ccss_import');
  fd.append('ao_ccss_import_nonce', '<?php echo wp_create_nonce("ao_ccss_import_nonce"); ?>');

  jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: fd,
    contentType: false,
    processData: false,
    success: function(response) {
      response_array=JSON.parse(response);
      if (response_array['code'] == 200) {
        window.location.reload();
      }
    }
  });
}