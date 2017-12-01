<?php

// Render the queue panel
// NOTE: implements section 4, id 4.2 and 4.3 of the specs
function ao_ccss_render_queue() {

  // Attach required arrays
  global $ao_ccss_queue;

?>
  <ul>
    <li class="itemDetail">
      <h2 class="itemTitle"><?php _e('Jobs Queue', 'autoptimize'); ?></h2>
      <div class="howto">
        <div class="title-wrap">
          <h4 class="title"><?php _e('How To Use Autoptimize CriticalCSS Power-Up Queue', 'autoptimize');?></h4>
          <p class="subtitle"><?php _e('Click the side arrow to toggle instructions', 'autoptimize');?></p>
        </div>
        <button type="button" class="toggle-btn">
          <span class="toggle-indicator dashicons dashicons-arrow-up dashicons-arrow-down"></span>
        </button>
        <div class="howto-wrap hidden">
          <p><?php _e("TL;DR: soon...", 'autoptimize');?></p>
          <ol>
            <li><?php _e('Soon', 'autoptimize');?></li>
          </ol>
        </div>
      </div>
      <table class="queue" cellspacing="0">
        <tr><th>Path</th><th>Type</th><th>Status</th><th>Creation Date</th><th>Finish Date</th><th>Actions</th></tr>
        <tbody id="queue"></tbody>
      </table>
      <input type="text" id="ao-ccss-queue" name="autoptimize_ccss_queue" style="width:100%;" value='<?php echo (json_encode($ao_ccss_queue)); ?>'>
    </li>
  </ul>
  <?php
}
?>