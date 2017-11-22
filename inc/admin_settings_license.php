<?php

function ccss_render_license($key) { ?>
  <ul>
    <li class="itemDetail">
      <h2 class="itemTitle"><?php _e('License', 'autoptimize'); ?></h2>
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
            <?php _e('To obtain your license key, go to <a href="https://criticalcss.com/account/api-keys" target="_blank">criticalcss.com</a> > Account > API Keys.<br />Copy the API Key there and paste in the field above.<br /><strong>Requests to generate criticalcss via the API are priced at £5 per domain per month.</strong>', 'autoptimize'); ?>
          </td>
        </tr>
      </table>
    </li>
  </ul>
  <?php
}
?>
