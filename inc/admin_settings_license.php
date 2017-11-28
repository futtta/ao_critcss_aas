<?php

// NOTE: implements section 4, id 4.1 of the specs

// Validate critical.css API key
function ao_ccss_validate_key($key) {

  // Attach wpdb
  global $wpdb;

  // Get key status
  $key_status = get_transient('autoptimize_ccss_key_status_' . md5($key));

  // No key validation stored, let's validate it
  if (!$key_status && $key) {

    // Prepare the request
    $url  = "https://criticalcss.com/api/premium/generate";
    $args = array(
      'headers' => array(
        'Content-type'  => 'application/json; charset=utf-8',
        'authorization' => 'JWT ' . $key
      ),
      // Body must be JSON
      'body' => json_encode(
        array(
          'url' => get_site_url() . '/',
          'aff' => 1
        )
      )
    );

    // Dispatch the request and store its response code
    $response = wp_remote_post($url, $args);
    $code     = $response['response']['code'];

    // Response code is ok (200)
    if ($code == 200) {

      // Cache key status for 1 day
      set_transient("autoptimize_ccss_key_status_" . md5($key), TRUE, DAY_IN_SECONDS);

      // Set validated key status
      $status     = 'validated';
      $status_msg = __('Validated');
      $color      = '#46b450'; // Green
      $message    = __('Thank you! Your <a href="https://criticalcss.com/" target="_blank">criticalcss.com</a> API key is valid.', 'autoptimize');
      $key_status = TRUE;

    // Response code is unauthorized (401)
    } elseif ($code == 401) {

      // Delete cached status for all keys
      $wpdb->query("
        DELETE FROM $wpdb->options
        WHERE option_name LIKE ('_transient_autoptimize_ccss_key_status_%')
           OR option_name LIKE ('_transient_timeout_autoptimize_ccss_key_status_%')
      ");

      // Set invalid key status
      $status     = 'invalid';
      $status_msg = __('Invalid');
      $color      = '#dc3232'; // Red
      $message    = __('Your API key is invalid, please check again in <a href="https://criticalcss.com/" target="_blank">criticalcss.com</a>.', 'autoptimize');

    // Other response codes
    } else {

      // Set remote error status
      $status     = 'error';
      $status_msg = __('Error');
      $color      = '#dc3232'; // Red
      $message    = __('Something went wrong while validating your <a href="https://criticalcss.com/" target="_blank">criticalcss.com</a> API key. Please try again later.', 'autoptimize');
    }

  // Key is still valid
  } elseif ($key_status && $key) {

    // Set valid key status
    $status     = 'valid';
    $status_msg = __('Valid');
    $color      = '#00a0d2'; // Blue
    $message    = NULL;

  // No key nor status
  } else {

    // Delete cached status for all keys
    $wpdb->query("
      DELETE FROM $wpdb->options
      WHERE option_name LIKE ('_transient_autoptimize_ccss_key_status_%')
         OR option_name LIKE ('_transient_timeout_autoptimize_ccss_key_status_%')
    ");

    // Set no key status
    $status     = 'nokey';
    $status_msg = __('None');
    $color      = '#ffb900'; // Yellow
    $message    = __('Please, enter a valid <a href="https://criticalcss.com/" target="_blank">criticalcss.com</a> API key to start.', 'autoptimize');
  }

  // Render license panel
  ao_ccss_render_license($key, $status, $status_msg, $message, $color);

  // Return key status
  if ($key_status) {
    return TRUE;
  } else {
    return FALSE;
  }
}

// Render license panel
function ao_ccss_render_license($key, $status, $status_msg, $message, $color) { ?>
  <ul>
    <li class="itemDetail">
      <h2 class="itemTitle fleft"><?php _e('API Key', 'autoptimize'); ?>: <span style="color:<?php echo $color; ?>;"><?php echo $status_msg; ?></span></h2>
      <button type="button" class="handletbl">
        <?php if ($status !== 'valid') { ?>
        <span class="toggle-indicator dashicons dashicons-arrow-up"></span>
        <?php } else { ?>
        <span class="toggle-indicator dashicons dashicons-arrow-up dashicons-arrow-down"></span>
        <?php } ?>
      </button>
      <?php if ($status === 'valid' || $status === 'validated') { ?>
      <div class="collapsible hidden">
      <?php } else { ?>
      <div class="collapsible">
      <?php } ?>
        <?php if ($status !== 'valid') { ?>
        <div style="clear:both;padding:2px 10px;border-left:solid;border-left-width:5px;border-left-color:<?php echo $color; ?>;background-color:white;">
          <p><?php echo $message; ?></p>
        </div>
        <?php } ?>
        <table id="key" class="form-table">
          <tr valign="top">
            <th scope="row">
              <?php _e('Your API Key', 'autoptimize'); ?>
            </th>
            <td>
              <textarea id="autoptimize_ccss_key" name="autoptimize_ccss_key" rows='3' style="width:100%;" placeholder="<?php _e('Please enter your criticalcss.com API key here...', 'autoptimize'); ?>"><?php echo trim($key); ?></textarea>
            </td>
          </tr>
          <tr>
            <th scope="row">
            </th>
            <td class="notes">
              <?php _e('Enter your <a href="https://criticalcss.com/account/api-keys" target="_blank">criticalcss.com</a> API key above. The license is validated every 24h.<br />To obtain your license key, go to <a href="https://criticalcss.com/account/api-keys" target="_blank">criticalcss.com</a> > Account > API Keys.<br /><strong>Requests to generate CriticalCSS via the API are priced at £5 per domain per month.</strong>', 'autoptimize'); ?>
            </td>
          </tr>
        </table>
      </div>
    </li>
  </ul>
  <?php
}
?>
