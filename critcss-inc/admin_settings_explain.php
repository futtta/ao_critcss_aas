<?php
function ao_ccss_render_explain() {
    ?>
    <ul id="explain-panel">
        <li class="itemDetail">
            <h2 class="itemTitle"><?php _e('Congratulations!', 'autoptimize'); ?></h2>
            <div><p><?php _e('You have downloaded, installed and activated the Autoptimize Criticalcss.com Power-Up succesfully!','autoptimize'); ?></p></div>
            <div><p><?php _e("You're",'autoptimize'); ?> <strong><?php _e('almost ready','autoptimize'); ?></strong> <?php _e('to have your critical CSS generated automatically and','autoptimize'); ?> <strong><?php _e("improve customer experience significantly.",'autoptimize'); ?></strong></p></div>
            <div><p><?php _e('The next step is to sign up at ','autoptimize'); ?><a href="https://criticalcss.com/?aff=1" target="_blank">https://criticalcss.com</a> <?php _e('(this is a premium service, priced 2 GBP/month for membership and 5 GBP/month per domain)','autoptimize'); ?> <strong><?php _e('and get the API key, which you can copy from ','autoptimize'); ?><a href="https://criticalcss.com/account/api-keys?aff=1" target="_blank"><?php _e('the API-keys page','autoptimize'); ?></a></strong> <?php _e('and paste below.','autoptimize'); ?></p></div>
            <div><p><?php _e('If you have any questions or need support, head on over to','autoptimize'); ?> <a href="https://wordpress.org/support/plugin/autoptimize-criticalcss" target="_blank"><?php _e('our support forum','autoptimize'); ?></a> <?php _e('and we\'ll help you get up and running in no time! ','autoptimize'); ?></p></div>
        </li>
    </ul>
    <?php
}
?>
