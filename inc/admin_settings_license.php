<?php

// NOTE: implements section 4, id 4.1 of the specs

// Get critical.css API key status
function ao_ccss_key_status($renderpanel) {

  // Attach wpdb
  global $wpdb;

  // Get key and key status
  global $ao_ccss_key;
  global $ao_ccss_keyst;
  $key        = $ao_ccss_key;
  $key_status = $ao_ccss_keyst;
  $status     = FALSE;

  // Key exists and its status is valid
  if ($key && $key_status == 2) {

    // Set valid key status
    $status     = 'valid';
    $status_msg = __('Valid');
    $color      = '#46b450'; // Green
    $message    = NULL;

  // Key exists but its validation has failed
  } elseif ($key && $key_status == 1) {

    // Set error status
    $status     = 'invalid';
    $status_msg = __('Invalid');
    $color      = '#dc3232'; // Red
    $message    = __('Your API key is invalid. Please enter a valid <a href="https://criticalcss.com/" target="_blank">criticalcss.com</a> key.', 'autoptimize');

  // Key exists but it has no valid status yet
  } elseif ($key && !$key_status) {

    // Set waiting validation status
    $status     = 'waiting';
    $status_msg = __('Waiting Validation');
    $color      = '#00a0d2'; // Blue
    $message    = __('Your API key is waiting for validation. It will be <strong>validated automatically</strong> when the first job in the queue run successfully.', 'autoptimize');

  // No key nor status
  } else {

    // Set no key status
    $status     = 'nokey';
    $status_msg = __('None');
    $color      = '#ffb900'; // Yellow
    $message    = __('Please enter a valid <a href="https://criticalcss.com/" target="_blank">criticalcss.com</a> API key to start.', 'autoptimize');
  }

  // Render license panel
  if ($renderpanel) {
    ao_ccss_render_license($key, $status, $status_msg, $message, $color);
  }

  // Return key status
  return $status;
}

// Render license panel
function ao_ccss_render_license($key, $status, $status_msg, $message, $color) { ?>
  <ul>
    <li class="itemDetail">
      <h2 class="itemTitle fleft"><?php _e('API Key', 'autoptimize'); ?>: <span style="color:<?php echo $color; ?>;"><?php echo $status_msg; ?></span></h2>
      <button type="button" class="toggle-btn">
        <?php if ($status != 'valid') { ?>
        <span class="toggle-indicator dashicons dashicons-arrow-up"></span>
        <?php } else { ?>
        <span class="toggle-indicator dashicons dashicons-arrow-up dashicons-arrow-down"></span>
        <?php } ?>
      </button>
      <?php if ($status != 'valid') { ?>
      <div class="collapsible">
      <?php } else { ?>
      <div class="collapsible hidden">
      <?php } ?>
        <?php if ($status != 'valid') { ?>
        <div style="clear:both;padding:2px 10px;border-left:solid;border-left-width:5px;border-left-color:<?php echo $color; ?>;background-color:white;">
          <p><?php echo $message; ?></p>
        </div>
        <?php } ?>
        <table id="key" class="form-table">
          <tr>
            <th scope="row">
              <?php _e('Your API Key', 'autoptimize'); ?>
            </th>
            <td>
              <textarea id="autoptimize_ccss_key" name="autoptimize_ccss_key" rows='3' style="width:100%;" placeholder="<?php _e('Please enter your criticalcss.com API key here...', 'autoptimize'); ?>"><?php echo trim($key); ?></textarea>
              <p class="notes">
                <?php _e('Enter your <a href="https://criticalcss.com/account/api-keys" target="_blank">criticalcss.com</a> API key above. The key is validated every time a new job is created there.<br />To obtain your API key, go to <a href="https://criticalcss.com/account/api-keys" target="_blank">criticalcss.com</a> > Account > API Keys.<br /><strong>Requests to generate a critical CSS via the API are priced at £5 per domain per month.</strong>', 'autoptimize'); ?>
              </p>
            </td>
          </tr>
        </table>
      </div>
    </li>
  </ul>
  <?php
}
?>
