<?php

// Render the queue panel
// NOTE: implements section 4, id 4.2 and 4.3 of the specs
function ao_ccss_render_queue() {

  // Attach required arrays
  global $ao_ccss_queue;

?>
  <ul id="queue-panel">
    <li class="itemDetail">
      <h2 class="itemTitle"><?php _e('Job Queue', 'autoptimize'); ?></h2>

      <!-- BEGIN Queue dialogs -->
      <!-- Retry dialog -->
      <div id="queue-confirm-retry" title="<?php _e('Retry Job', 'autoptimize') ?>" class="hidden">
        <p><?php _e('Are you sure you want to retry this job?', 'autoptimize'); ?></p>
      </div>

      <!-- Remove dialog -->
      <div id="queue-confirm-rm" title="<?php _e('Delete Job', 'autoptimize') ?>" class="hidden">
        <p><?php _e('Are you sure you want to delete this job?', 'autoptimize'); ?></p>
      </div>
      <!-- END Queue dialogs -->

      <!-- BEGIN Queue UI -->
      <div class="howto">
        <div class="title-wrap">
          <h4 class="title"><?php _e('How To Use Autoptimize CriticalCSS Power-Up Queue', 'autoptimize');?></h4>
          <p class="subtitle"><?php _e('Click the side arrow to toggle instructions', 'autoptimize');?></p>
        </div>
        <button type="button" class="toggle-btn">
          <span class="toggle-indicator dashicons dashicons-arrow-up dashicons-arrow-down"></span>
        </button>
        <div class="howto-wrap hidden">
          <p><?php _e('TL;DR:<br /><strong>Queue runs every 10 minutes.</strong> Job statuses are <span class="badge new">N</span> for NEW, <span class="badge pending">P</span> for PENDING, <span class="badge done">D</span> for DONE, <span class="badge review">R</span> for REVIEW, <span class="badge error">E</span> for ERROR and <span class="badge unknown">U</span> for UNKOWN.', 'autoptimize');?></p>
          <ol>
            <li><?php _e('The queue operates <strong>automatically, asynchronously and on regular intervals of 10 minutes.</strong> To view updated queue status, refresh this page.', 'autoptimize');?></li>
            <li><?php _e('When the conditions to create a job are met (i.e. user not logged in, no matching <span class="badge manual">MANUAL</span> rule or CSS files has changed for an <span class="badge auto">AUTO</span> rule), a <span class="badge new">N</span> job is created in the queue.', 'autoptimize');?></li>
            <li><?php _e("Autoptimize CriticalCSS Power-Up constantly query the queue for <span class='badge new'>N</span> jobs. When it finds one, gears spins and jobs becomes <span class='badge pending'>P</span> while they are running and <a href='https://criticalcss.com/' target='_blank'>criticalcss.com</a> doesn't return a result.", 'autoptimize');?></li>
            <li><?php _e('As soon as <a href="https://criticalcss.com/" target="_blank">criticalcss.com</a> returns a valid critical CSS file, the job is then <span class="badge done">D</span>. You can delete done jobs as you wish.', 'autoptimize');?></li>
            <li><?php _e('If <a href="https://criticalcss.com/" target="_blank">criticalcss.com</a> results are not perfect, then the job becomes <span class="badge review">R</span> and you need to review the resulting critical CSS file fetched in the target rule pointed out by that job.', 'autoptimize');?></li>
            <li><?php _e('When things go wrong, a job is marked as <span class="badge error">E</span>. You can retry faulty jobs, delete them or get in touch with <a href="https://criticalcss.com/" target="_blank">criticalcss.com</a> for assistance.', 'autoptimize');?></li>
            <li><?php _e('Sometimes an unknown condition can happen. In this case, the job status becomes <span class="badge unknown">U</span> and you may want to ask <a href="https://criticalcss.com/" target="_blank">criticalcss.com</a> for help or just delete it.', 'autoptimize');?></li>
            <li><?php _e('To get more information about jobs statuses, specially the ones with <span class="badge error">E</span> and <span class="badge unknown">U</span> status, hover your mouse in the status badge of that job. This information might be crucial when contacting <a href="https://criticalcss.com/" target="_blank">criticalcss.com</a> for assistance.', 'autoptimize');?></li>
            <li><?php _e('<strong>A WORD ABOUT WORDPRESS CRON:</strong> Autoptimize CriticalCSS Power-Up watch the queue by using WordPress Cron (or WP-Cron for short.) It <a href="https://www.smashingmagazine.com/2013/10/schedule-events-using-wordpress-cron/#limitations-of-wordpress-cron-and-solutions-to-fix-em" target="_blank">could be faulty</a> on very light or very heavy loads. If your site receives just a few or thousands visits a day, it might be a good idea to <a href="https://developer.wordpress.org/plugins/cron/hooking-into-the-system-task-scheduler/" target="_blank">turn WP-Cron off and use your system task scheduler</a> to fire it instead.', 'autoptimize');?></li>
          </ol>
        </div>
      </div>
      <table id="queue-tbl" class="queue tablesorter" cellspacing="0">
        <thead>
          <tr><th class="status"><?php _e('Status', 'autoptimize');?></th><th><?php _e('Target Rule', 'autoptimize');?></th><th><?php _e('Page Path', 'autoptimize');?></th><th><?php _e('Page Type', 'autoptimize');?></th><th><?php _e('Creation Date', 'autoptimize');?></th><th><?php _e('Finish Date', 'autoptimize');?></th><th class="btn"><?php _e('Actions', 'autoptimize');?></th></tr>
        </thead>
        <tbody id="queue"></tbody>
      </table>
      <input class="hidden" type="text" id="ao-ccss-queue" name="autoptimize_ccss_queue" value='<?php echo (json_encode($ao_ccss_queue)); ?>'>
      <!-- END Queue UI -->

    </li>
  </ul>
  <?php
}
?>