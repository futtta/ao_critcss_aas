<?php

// Render the exclusions panel
function ao_ccss_render_rules() {

  // Attach the conditional tags array
  global $ao_ccss_types;

?>
  <ul>
    <li class="itemDetail">
      <h2 class="itemTitle"><?php _e('Rules', 'autoptimize'); ?></h2>
      <div id="unSavedWarning" class="hidden updated settings-error notice is-dismissible">
        <p><?php _e("You have unsaved changes, don't forget to save!","ao_critcss"); ?></p>
      </div>
      <div id="addEditCritCss">
        <form>
          <table class="form-table rules">
            <tr valign="top" id="critcss_addedit_type_wrapper">
              <th scope="row">
                <?php _e("Rule Type","ao_critcss"); ?>
              </th>
              <td>
                <select id="critcss_addedit_type" style="width:100%;">
                  <option value="inpath"><?php _e('Path', 'autoptimize'); ?></option>
                  <option value="type"><?php _e('Conditional Tag', 'autoptimize'); ?></option>
                </select>
              </td>
            </tr>
            <tr valign="top" id="critcss_addedit_path_wrapper">
              <th scope="row">
                <?php _e('String in Path', 'autoptimize'); ?>
              </th>
              <td>
                <input type="text" id="critcss_addedit_path" placeholder="<?php _e("Enter a part of the URL that identifies the page(s) you're targetting.", 'autoptimize'); ?>" style="width:100%;">
              </td>
            </tr>
            <tr valign="top" id="critcss_addedit_pagetype_wrapper">
              <th scope="row">
                <?php _e('Conditional Tag, Custom Post Type or Page Template', 'autoptimize'); ?>
              </th>
              <td>
                <select id="critcss_addedit_pagetype" style="width:100%;">
                  <option value="" disabled selected><?php _e('Select from the list below...', 'autoptimize'); ?></option>
                  <optgroup label="<?php _e('Conditional Tags', 'autoptimize'); ?>"><?php
                    foreach ($ao_ccss_types as $ctag) {
                      $optgrp = substr($ctag, 0, 3);
                      if (substr($ctag, 0, 3) === "is_") {
                        echo '<option value="' . $ctag . '">' . str_replace(array('template_','custom_post_'), '', $ctag) . ' (' . $optgrp . ')</option>';
                      }
                      $prevgrp = substr($ctag, 0, 3);
                    }
                    foreach ($ao_ccss_types as $type) {
                      $optgrp = substr($type, 0, 3);
                      if ($optgrp !== $prevgrp && $optgrp !== "is_") { ?>
                  </optgroup> <?php
                        if (substr($type, 0, 12) === 'custom_post_') { ?>
                          <optgroup label="<?php _e('Custom Post Types', 'autoptimize'); ?>"><?php
                        } elseif (substr($type, 0, 9) === "template_") { ?>
                          <optgroup label="<?php _e('Page Templates', 'autoptimize'); ?>"><?php
                        } elseif (substr($type, 0, 4) === "bbp_") { ?>
                          <optgroup label="<?php _e('BBPress', 'autoptimize'); ?>"><?php
                        } elseif (substr($type, 0, 3) === "bp_") { ?>
                          <optgroup label="<?php _e('BuddyPress', 'autoptimize'); ?>"><?php
                        } elseif (substr($type, 0, 4) === "edd_") { ?>
                          <optgroup label="<?php _e('Easy Digital Downloads', 'autoptimize'); ?>"><?php
                        }
                      }
                      if ($optgrp !== "is_") {
                        echo '<option value="' . $type . '">' . str_replace(array('template_', 'custom_post_'), '', $type) . ' (' . $optgrp . ')</option>';
                        $prevgrp = $optgrp;
                      }
                    }
                  ?>
                  </optgroup>
                </select>
              </td>
            </tr>
            <tr valign="top" id="critcss_addedit_css">
              <th scope="row">
                <?php _e('Custom Critical CSS', 'autoptimize') ?>
              </th>
              <td>
                <textarea rows="9" cols="10" style="width:100%;"></textarea>
                <input type="hidden" id="critcss_addedit_file">
                <input type="hidden" id="critcss_addedit_id">
              </td>
            </tr>
          </table>
        </form>
      </div>
      <div id="confirm-rm" title="<?php _e('Remove rule and CSS-file?', 'autoptimize') ?>" class="hidden">
        <p><?php _e('The file with critical CSS will be deleted immediately and cannot be recovered! Are you sure?', 'autoptimize'); ?></p>
      </div>
    </li>
  </ul>
  <?php
}
?>
