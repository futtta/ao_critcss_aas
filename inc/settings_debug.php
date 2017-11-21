<!-- BEGIN: Settings Debug -->
<ul>
  <li class="itemDetail">
    <h2 class="itemTitle"><?php _e('Debug Stuff <small>(This is going to disappear when dev is done!)</small>', 'ao_ccss'); ?></h2>
    <h4>Current Settings:</h4>
    <table class="form-table">
      <tr valign="top">
        <th scope="row">
          autoptimize_ccss_key:
        </th>
        <td>
          <?php echo (empty($ao_ccss_key) ? 'empty' : $ao_ccss_key); ?>
        </td>
      </tr>
    </table>
    <hr />
    <h4>Transients:</h4>
    <table class="form-table">
      <tr valign="top">
        <th scope="row">
          <?php echo 'ao_ccss_lic_status_' . md5($ao_ccss_key) . ':'; ?>
        </th>
        <td>
          <?php echo (empty(get_transient('ao_ccss_lic_status_' . md5($ao_ccss_key))) ? 'empty' : get_transient('ao_ccss_lic_status_' . md5($ao_ccss_key))); ?>
        </td>
      </tr>
    </table>
    <hr />
    <h4>Array Content: autoptimize_ccss_queue</h4>
    <pre>
      <?php print_r(get_option('autoptimize_ccss_queue')); ?>
    </pre>
  </li>
</li>
<!-- END: Settings Debug -->
