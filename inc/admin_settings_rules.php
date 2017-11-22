<?php

// Setup an array with the default conditional tags
$ao_ccss_types = array(
  array(
    "is_page",
    "is_post",
    "is_home",
    "is_archive",
    "is_front_page",
    "is_single",
    "is_sticky",
    "is_category",
    "is_author",
    "is_search",
    "is_404",
    "is_blog_page"
  )
);

// Render the rules panel
function ccss_render_rules() {
  global $ao_ccss_types ?>
  <ul>
    <li class="itemDetail">
      <h2 class="itemTitle"><?php _e('Rules', 'autoptimize'); ?></h2>
      <div style="margin-bottom:20px;padding:2px 10px;border-left:solid;border-left-width:5px;border-left-color:#46b450;background-color:white;">
        <p><strong><?php _e('3 EASY steps to automate your CriticalCSS optimization:', 'autoptimize');?></strong></p>
        <ol>
          <li><?php _e('A <strong>default critical CSS will be generated and cached automatically</strong> from your site homepage. This will be used on all post/pages by default, but has the lowest priority.', 'autoptimize');?></li>
          <li><?php _e('Add/edit <strong>rules based on conditional tags, custom post types or page templates</strong>. For each conditional rule, a critical CSS will be <strong>generated and cached automatically</strong> to override the default one in that post/page. Conditional rules are medium priority.', 'autoptimize');?></li>
          <li><?php _e('Add/edit <strong>rules based on path (url)</strong>. For each path rule, a critical CSS will be <strong>generated and cached automatically</strong> to override priors for that post/page. Path rules have the highest priority, and the more precise (longer) the path, the higher priority it takes.', 'autoptimize');?></li>
        </ol>
      </div>
      <table class="form-table rules">
        <tr valign="top">
          <th scope="row">
            <select id="autoptimize_ccss_rule_type" name="autoptimize_ccss_rule_type">
              <option value="cond"><?php _e('Conditional', 'autoptimize'); ?></option>
              <option value="path"><?php _e('Path', 'autoptimize'); ?></option>
            </select>
          </th>
          <td>
            <select>
            <option value="" selected disabled><?php _e('Select one...', 'autoptimize'); ?></option>
            <optgroup label="<?php _e('Tags', 'autoptimize'); ?>">
              <?php
              foreach ($ao_ccss_types[0] as $option) {
                echo '<option value="' . $option . '">' . $option . '</option>';
              } ?>
            </optgroup>
            <optgroup label="<?php _e('Custom Post Types', 'autoptimize'); ?>">
              <option value="">Price List</option>
              <option value="">Offer</option>
            </optgroup>
            <optgroup label="<?php _e('Templates', 'autoptimize'); ?>">
              <option value="">price-tpl</option>
              <option value="">offer-tpl</option>
            </optgroup>
          </select>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row">
            <select id="autoptimize_ccss_rule_type" name="autoptimize_ccss_rule_type">
              <option value="path"><?php _e('Path', 'autoptimize'); ?></option>
              <option value="path"><?php _e('Conditional', 'autoptimize'); ?></option>
            </select>
          </th>
          <td>
            <input type='text' id="autoptimize_ccss_rule_content" name="autoptimize_ccss_rule_content" rows='3' style="width:100%;" placeholder="<?php _e('/my/path/', 'autoptimize'); ?>" value="">
          </td>
        </tr>
      </table>
    </li>
  </ul>
  <?php
}
?>
