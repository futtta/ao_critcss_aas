<?php

// NOTE: implements OUT OF SCOPE DEBUG item of the specs

// Attach wpdb() object
global $wpdb;

// Query AO's options
$ao_options = $wpdb->get_results('
  SELECT option_name  AS name,
         option_value AS value
  FROM ' . $wpdb->options . '
  WHERE option_name LIKE "autoptimize_%%"
  ORDER BY name
', ARRAY_A);

// Query AO's transients
$ao_trans = $wpdb->get_results('
  SELECT option_name  AS name,
         option_value AS value
  FROM ' . $wpdb->options . '
  WHERE option_name LIKE "_transient_autoptimize_%%"
     OR option_name LIKE "_transient_timeout_autoptimize_%%"
', ARRAY_A);

// Render debug panel if there's something to show
if ($ao_options || $ao_trans) {
?>
<!-- BEGIN: Settings Debug -->
<ul>
  <li class="itemDetail">
    <h2 class="itemTitle"><?php _e('Autoptimize Debug Stuff <small>(to be removed after development is done!)</small>', 'autoptimize'); ?></h2>

    <?php
    // Render options
    if ($ao_options) { ?>
    <h4><?php _e('Options', 'autoptimize'); ?>:</h4>
    <table class="form-table debug">
      <?php foreach($ao_options as $option) { ?>
      <tr valign="top">
        <th scope="row">
          <?php echo $option['name']; ?>
        </th>
        <td>
          <?php
          if ($option['name'] == 'autoptimize_ccss_queue' || $option['name'] == 'autoptimize_ccss_rules') {
            $value = print_r(json_decode($option['value'], TRUE), TRUE);
            if ($value) {
              echo "Raw JSON:\n<pre>" . $option['value'] . "</pre>\n\nObject:\n<pre>" . $value . '</pre>';
            } else {
              echo "Empty";
            }
          } else {
            echo $option['value'];
          } ?>
        </td>
      </tr>
      <?php } ?>
    </table>
    <hr />
    <?php }

    // Render transients
    if ($ao_trans) { ?>
     <h4><?php _e('Transients', 'autoptimize'); ?>:</h4>
    <table class="form-table debug">
      <?php foreach($ao_trans as $trans) { ?>
      <tr valign="top">
        <th scope="row">
          <?php echo $trans['name']; ?>
        </th>
        <td>
          <?php echo $trans['value']; ?>
        </td>
      </tr>
      <?php } ?>
    </table>
    <?php } ?>

  </li>
</ul>
<!-- END: Settings Debug -->
<?php } ?>