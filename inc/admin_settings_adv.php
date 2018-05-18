<?php

// NOTE: out of scope advanced panel

// Render the advanced panel
function ao_ccss_render_adv() {

  // Attach required options
  global $ao_ccss_debug;
  global $ao_ccss_finclude;
  global $ao_ccss_rlimit;

  // Get viewport size
  $viewport = ao_ccss_viewport();

?>
  <ul id="adv-panel">
    <li class="itemDetail">
      <h2 class="itemTitle fleft"><?php _e('Advanced Settings', 'autoptimize'); ?></h2>
      <button type="button" class="toggle-btn">
        <span class="toggle-indicator dashicons dashicons-arrow-up dashicons-arrow-down"></span>
      </button>
      <div class="collapsible hidden">
        <table id="key" class="form-table">
          <tr>
            <th scope="row">
              <?php _e('Viewport Size', 'autoptimize'); ?>
            </th>
            <td>
              <label for="autoptimize_ccss_vw"><?php _e('Width', 'autoptimize'); ?>:</label> <input type="number" id="autoptimize_ccss_vw" name="autoptimize_ccss_viewport[w]" min="800" max="4096" placeholder="1300" value="<?php echo $viewport['w']; ?>" />&nbsp;&nbsp;
              <label for="autoptimize_ccss_vh"><?php _e('Height', 'autoptimize'); ?>:</label> <input type="number" id="autoptimize_ccss_vh" name="autoptimize_ccss_viewport[h]" min="600" max="2160" placeholder="900" value="<?php echo $viewport['h']; ?>" />
              <p class="notes">
                <?php _e('<a href="https://criticalcss.com/account/api-keys?aff=1" target="_blank">criticalcss.com</a> default viewport size is 1300x900 pixels (width x height). You can change this size by typing a desired width and height values above. Allowed value ranges are from 800 to 4096 for width and from 600 to 2160 for height.', 'autoptimize'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <?php _e('Force Include', 'autoptimize'); ?>
            </th>
            <td>
              <textarea id="autoptimize_ccss_finclude" name="autoptimize_ccss_finclude" rows='3' style="width:100%;" placeholder="<?php _e('.button-special,//#footer', 'autoptimize'); ?>"><?php echo trim($ao_ccss_finclude); ?></textarea>
              <p class="notes">
                <?php _e('Force include can be used to style dynamic content that is not part of the HTML that is seen during the Critical CSS generation. To use this feature, add comma separated values with both simple strings and/or regular expressions to match the desired selectors. Regular expressions must be preceeded by two forward slashes. For instance: <code>.button-special,//#footer</code>. In this example <code>.button-special</code> will match <code>.button-special</code> selector only, while <code>//#footer</code> will match <code>#footer</code>, <code>#footer-address</code> and <code>#footer-phone</code> selectors in case they exist.', 'autoptimize'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <?php _e('Request Limit', 'autoptimize'); ?>
            </th>
            <td>
              <input type="number" id="autoptimize_ccss_rlimit" name="autoptimize_ccss_rlimit" min="1" max="240" placeholder="0" value="<?php echo $ao_ccss_rlimit; ?>" />
              <p class="notes">
                <?php _e('Certain hosting services imposes hard limitations to background processes on either execution time, requests made from your server to any third party services, or both. This could lead to a faulty operation of the queue background process triggered by WP-Cron. If automated rules are not being created, you may be facing this limitation of your hosting provider. In that case, set the request limit to a reasonable number between 1 and 240. The queue fire a request to <a href="https://criticalcss.com/account/api-keys?aff=1" target="_blank">criticalcss.com</a> on every 15 seconds (due to service limitations). If your hosting provider allows a 60 seconds time span to background processes runtime, set this value to 3 or 4 so the queue can operate within the boundaries. The maximum value of 240 allows enough requests for one hour long. To disable this limit and to let requests be made at will, just delete any values in this setting (a grey 0 will show).', 'autoptimize'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <?php _e('Debug Mode', 'autoptimize'); ?>
            </th>
            <td>
              <input type="checkbox" id="autoptimize_ccss_debug" name="autoptimize_ccss_debug" value="1" <?php checked(1 == $ao_ccss_debug); ?>>
              <p class="notes"> <?php
                _e('<strong>CAUTION! DO NOT use debug mode on production/live environments.</strong><br />Check the box above to enable Autoptimize CriticalCSS Power-Up debug mode. It provides debug facilities in this screen, to the browser console and to this file: ', 'autoptimize');
                echo '<code>' . AO_CCSS_LOG . '</code>';
              ?></p>
            </td>
          </tr>
        </table>
      </div>
    </li>
  </ul>
  <?php
}
?>
