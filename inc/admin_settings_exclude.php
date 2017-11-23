<?php

// Render the exclusions panel
function ao_ccss_render_exclude($exclude) { ?>
  <ul>
    <li class="itemDetail">
      <h2 class="itemTitle"><?php _e('Path Based Exclusions', 'autoptimize'); ?></h2>
      <div style="margin-bottom:20px;padding:2px 10px;border-left:solid;border-left-width:5px;border-left-color:#46b450;background-color:white;">
        <p>
          <?php _e('Here you can <strong>exclude any pages</strong> on your site from CriticalCSS Powe-Up optimizations.<br /><strong>Add one full path per line</strong> leaving the domain part out.<br />For example:', 'autoptimize'); ?>
          <pre><?php _e("/\n/hello-world/\n/my-great-product-page/\n/super-duper-landing-page/", 'autoptimize'); ?></pre>
        </p>
      </div>
      <table class="form-table">
        <tr valign="top">
          <th scope="row">
            <?php _e('Paths to Exclude', 'autoptimize'); ?>
          </th>
          <td>
            <textarea id="autoptimize_ccss_exclude" name="autoptimize_ccss_exclude" rows='10' style="width:100%;" placeholder="<?php _e('Please add one full path per line.', 'autoptimize'); ?>"><?php echo trim($exclude); ?></textarea>
          </td>
        </tr>
      </table>
    </li>
  </ul>
  <?php
}
?>
