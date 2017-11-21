<?php

// Check CriticalCSS license
function ccss_license_check($plugin='ao_critcsscom', $version='0.9', $licenseKey) {
  $licenseStatus = get_transient('autoptimize_ccss_licstat_' . md5($licenseKey));

  if ($licenseKey && $licenseStatus && $licenseStatus['state'] === 'valid') {
    return true;
  } elseif ($licenseKey && !$licenseStatus) {
    $licenseStatus = get_license_status($licenseKey, $plugin, $version);
    if ($licenseStatus && $licenseStatus['state'] === 'valid') {
      if (array_key_exists('warning', $licenseStatus)) {
        echo '<div class="notice notice-warning"><p>' . $licenseStatus['warning'] . '</p></div>';
      }
      return true;
    } else {
      if ($licenseStatus) {
        $reason = $licenseStatus['state'];
      } else {
        $reason = 'couldnotvalidate';
      }
      render_license_fields($reason);
    }
  } elseif (!$licenseKey) {
    render_license_fields('nokey');
  }
}

function get_license_status($licenseKey, $plugin, $version) {
  $licCheckURL = 'http://misc.optimizingmatters.com/api/checkLicense.php?licensekey=' .
                 $licenseKey . '&plugin=' . $plugin . '&version=' . $version . '&host=' .
                 urlencode(site_url());
  $checkResp   = wp_remote_get($licCheckURL);

  if (is_wp_error($checkResp)) {
    $checkStatus = false;
  } else {
    if (in_array(wp_remote_retrieve_response_code($checkResp), array(400, 403, 404))) {
      $checkStatus = false;
    } else {
      $licenseStatus = json_decode(wp_remote_retrieve_body($checkResp), true);
      if ($licenseStatus) {
        $checkStatus = true;
      } else {
        $checkStatus = false;
      }
    }
  }

  if ($checkStatus) {
    if ($licenseStatus['state'] === 'valid') {
      set_transient('autoptimize_ccss_licstat_' . md5($licenseKey), $licenseStatus, DAY_IN_SECONDS);
    }
    return $licenseStatus;
  } else {
    return false;
  }
}

function render_license_fields($reason) {
  global $ao_ccss_key;
  ?>

  <div class="notice notice-warning">
    <p>
    <?php
    switch ($reason) {
      case 'overuse':
        $ao_ccss_key = '';
        update_option('autoptimize_ccss_options', $ao_ccss_options);
      case 'nokey':
        _e('Please enter your CriticalCSS license key.', 'autoptimize');
        break;
      case 'invalid':
        _e('This is not a valid CriticalCSS license key.', 'autoptimize');
        break;
      case 'expired':
        _e('This CriticalCSS license key is valid but seems to have expired.', 'autoptimize');
        break;
      default:
        _e('Something went wrong while validating this key.', 'autoptimize');
    }
    _e(' Go to <a href="https://criticalcss.com/login" target="_blank">criticalcss.com</a> and click on <strong>account</strong> to verify your license key or to edit your payment details.', 'autoptimize');
    ?>
    </p>
  </div>

  <form method="post" action="options.php">
    <?php settings_fields('ao_ccss_options_group'); ?>
    <ul>
      <li class="itemDetail">
        <h2 class="itemTitle"><?php _e('License Status: ', 'autoptimize'); echo $reason; ?></h2>
        <table class="form-table">
          <tr valign="top">
            <th scope="row">
              <?php _e('License Key', 'ao_css'); ?>
            </th>
            <td>
              <input type="text" id="autoptimize_ccss_key" name="autoptimize_ccss_key" style="width:100%;" placeholder="<?php _e('Please enter your CriticalCSS license key', 'autoptimize'); ?>" value='<?php echo $ao_ccss_key; ?>'>
            </td>
          </tr>
        </table>
      </li>
        <p class="submit">
          <input type="submit" class="button-primary" value="<?php _e('Save Changes', 'autoptimize') ?>" />
        </p>
    </ul>
  </form>

  <?php
}

?>