<?php

// Validate critical.css API key
function ao_ccss_validate_key($key, $key_status) {

  // Attach wpdb
  global $wpdb;

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
      set_transient("autoptimize_ccss_key_status_" . md5($key), 1, DAY_IN_SECONDS);

      // Set validated key status
      $status  = 'validated';
      $color   = '#46b450'; // Green
      $message = __('Nice! Your criticalcss.com API key is valid.', 'autoptimize');

    // Response code is unauthorized (401)
    } elseif ($code == 401) {

      // Delete cached status for the key
      delete_transient("autoptimize_ccss_key_status_" . md5($key), 1, DAY_IN_SECONDS);

      // Set invalid key status
      $status  = 'invalid';
      $color   = '#dc3232'; // Red
      $message = __('Your API key is invalid, please check again in criticalcss.com.', 'autoptimize');

    // Other response codes
    } else {

      // Set remote error status
      $status  = 'error';
      $color   = '#dc3232'; // Red
      $message = __('Something went wrong validating your criticalcss.com. API key. Please try again later.', 'autoptimize');
    }

  // Key is still valid
  } elseif ($key_status && $key) {

    // Set valid key status
    $status  = 'valid';
    $color   = NULL;
    $message = NULL;

  // No key nor status
  } else {

    // Delete cached status for all keys
    $wpdb->query("
      DELETE FROM $wpdb->options
      WHERE option_name LIKE ('_transient_autoptimize_ccss_key_status_%')
         OR option_name LIKE ('_transient_timeout_autoptimize_ccss_key_status_%')
    ");

    // Set no key status
    $status  = 'nokey';
    $color   = '#ffb900'; // Yellow
    $message = __('You need to enter a valid criticalcss.com API key to use this Power-Up.', 'autoptimize');
  }

  // Render license panel
  ao_ccss_render_license($key, $status, $message, $color);
}

// Render license panel
function ao_ccss_render_license($key, $status, $message, $color) { ?>
  <ul>
    <li class="itemDetail">
      <h2 class="itemTitle"><?php _e('License', 'autoptimize'); ?></h2>
      <?php if ($status !== 'valid') { ?>
      <div style="padding:2px 10px;border-left:solid;border-left-width:5px;border-left-color:<?php echo $color; ?>;background-color:white;">
        <p><?php echo $message; ?></p>
      </div>
      <?php } ?>
      <table class="form-table">
        <tr valign="top">
          <th scope="row">
            <?php _e('License Key', 'autoptimize'); ?>
          </th>
          <td>
            <textarea id="autoptimize_ccss_key" name="autoptimize_ccss_key" rows='3' style="width:100%;" placeholder="<?php _e('Please enter your CriticalCSS license key here...', 'autoptimize'); ?>"><?php echo trim($key); ?></textarea>
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
    </li>
  </ul>
  <?php
}
?>
