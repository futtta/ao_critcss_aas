<?php

// NOTE: implements OUT OF SCOPE ADVANCED PANEL item of the specs

// Render the advanced panel
function ao_ccss_render_adv() {

  // Attach advanced settings
  global $ao_ccss_viewport;
  global $ao_ccss_debug;

  // Prepare viewport values
  // Width
  if (!empty($ao_ccss_viewport['w'])) {
    $width = $ao_ccss_viewport['w'];
  } else {
    $width = '';
  }
  // Height
  if (!empty($ao_ccss_viewport['h'])) {
    $height = $ao_ccss_viewport['h'];
  } else {
    $height = '';
  }

?>
  <ul>
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
              <label for="autoptimize_ccss_vw"><?php _e('Width', 'autoptimize'); ?>:</label> <input type="number" id="autoptimize_ccss_vw" name="autoptimize_ccss_viewport[w]" min="800" max="4096" placeholder="1300" value="<?php echo $width; ?>">&nbsp;&nbsp;
              <label for="autoptimize_ccss_vh"><?php _e('Height', 'autoptimize'); ?>:</label> <input type="number" id="autoptimize_ccss_vh" name="autoptimize_ccss_viewport[h]" min="600" max="2160" placeholder="900" value="<?php echo $height; ?>">
              <p class="notes">
                <?php _e('<a href="https://criticalcss.com/account/api-keys" target="_blank">criticalcss.com</a> default viewport size is 1300x900 pixels (width x height). You can change this size by entering desired width and height values above. Allowed values are 800 to 4096 for width, 600 to 2160 for height.', 'autoptimize'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <?php _e('Debug Mode', 'autoptimize'); ?>
            </th>
            <td>
              <input type="checkbox" id="autoptimize_ccss_debug" name="autoptimize_ccss_debug" value="1" <?php checked( 1 == $ao_ccss_debug ); ?>>
              <p class="notes">
                <?php _e('<strong>CAUTION! DO NOT use debug mode on production/live environments.</strong><br />Check the box above to enable Autoptimize CriticalCSS Power-Up debug mode. This will provide a dedicated debug panel in this screen and development information on web server log and browser console.', 'autoptimize'); ?>
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
