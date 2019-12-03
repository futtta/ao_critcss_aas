<?php
/*
Plugin Name: Autoptimize CriticalCSS.com Power-Up
Plugin URI: http://optimizingmatters.com/
Description: Let Autoptimize and CriticalCSS unleash your site performance and make it appear better than anyone in search results.
Author: Deny Dias & Optimizing Matters
Version: 1.18.0
Text Domain: autoptimize
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class autoptimizeCriticalCSS {
    /**
     * Main plugin filepath.
     * Used for activation/deactivation/uninstall hooks.
     *
     * @var string
     */
    protected $filepath = null;

    public function __construct()
    {
        // Define plugin version
        define( 'AO_CCSS_VER', '1.18.0' );

        // Define a constant with the directory to store critical CSS in
        if (is_multisite()) {
          $blog_id = get_current_blog_id();
          define( 'AO_CCSS_DIR', WP_CONTENT_DIR . '/uploads/ao_ccss/' . $blog_id . '/' );
        } else {
          define( 'AO_CCSS_DIR', WP_CONTENT_DIR . '/uploads/ao_ccss/' );
        }

        // Define support files locations
        define( 'AO_CCSS_LOCK',  AO_CCSS_DIR . 'queue.lock' );
        define( 'AO_CCSS_LOG',   AO_CCSS_DIR . 'queuelog.html' );
        define( 'AO_CCSS_DEBUG', AO_CCSS_DIR . 'queue.json' );

        // Define constants for criticalcss.com base path and API endpoints
        // fixme: AO_CCSS_URL should be read from the autoptimize availability json stored as option.
        define( 'AO_CCSS_URL', 'https://criticalcss.com' );
        define( 'AO_CCSS_API', AO_CCSS_URL . '/api/premium/' );

        $this->filepath = __FILE__;

        $this->setup();
        $this->load_requires();
    }

    public function setup()
    {
        // get all options.
        $all_options = $this->fetch_options();
        foreach ( $all_options as $option => $value ) {
            ${$option} = $value;
        }

        // make sure the 10 minutes cron schedule is added.
        add_filter( 'cron_schedules', array( $this, 'ao_ccss_interval' ) );

        // add admin hooks if need be.
        if ( is_admin() ) {
            $this->hooks_for_admin();
        }

        // check if we need to upgrade.
        $this->check_upgrade();

        // and register the activation hook.
        register_activation_hook( __FILE__, array( $this, 'on_activation' ) );
    }
    
    public function load_requires() {
        // Required libs, core is always needed.
        require_once( 'critcss-inc/core.php' );
        
        if ( defined( 'DOING_CRON' ) || is_admin() ) {
            // fix me: also include if overridden somehow to force queue processing to be executed.
            require_once( 'critcss-inc/cron.php' );
        }

        if ( is_admin() ) {
            require_once( 'critcss-inc/admin_settings.php' );
            require_once( 'critcss-inc/admin_settings_rules.php' );
            require_once( 'critcss-inc/admin_settings_queue.php' );
            require_once( 'critcss-inc/admin_settings_key.php' );
            require_once( 'critcss-inc/admin_settings_adv.php' );
            require_once( 'critcss-inc/admin_settings_explain.php' );
            require_once( 'critcss-inc/core_ajax.php' );
            require_once( 'critcss-inc/external/persist-admin-notices-dismissal/persist-admin-notices-dismissal.php' );
        } else {
            // enqueuing only done when not wp-admin.
            require_once( 'critcss-inc/core_enqueue.php' );
        }
    }

    public static function fetch_options() {
        // Get options
        $autoptimize_ccss_options['ao_css_defer']          = get_option( 'autoptimize_css_defer'         );
        $autoptimize_ccss_options['ao_css_defer_inline']   = get_option( 'autoptimize_css_defer_inline'  );
        $autoptimize_ccss_options['ao_ccss_rules_raw']     = get_option( 'autoptimize_ccss_rules'        , FALSE);
        $autoptimize_ccss_options['ao_ccss_additional']    = get_option( 'autoptimize_ccss_additional'   );
        $autoptimize_ccss_options['ao_ccss_queue_raw']     = get_option( 'autoptimize_ccss_queue'        , FALSE);
        $autoptimize_ccss_options['ao_ccss_viewport']      = get_option( 'autoptimize_ccss_viewport'     , FALSE);
        $autoptimize_ccss_options['ao_ccss_finclude']      = get_option( 'autoptimize_ccss_finclude'     , FALSE);
        $autoptimize_ccss_options['ao_ccss_rlimit']        = get_option( 'autoptimize_ccss_rlimit  '     , '5' );
        $autoptimize_ccss_options['ao_ccss_noptimize']     = get_option( 'autoptimize_ccss_noptimize'    , FALSE);
        $autoptimize_ccss_options['ao_ccss_debug']         = get_option( 'autoptimize_ccss_debug'        , FALSE);
        $autoptimize_ccss_options['ao_ccss_key']           = get_option( 'autoptimize_ccss_key'          );
        $autoptimize_ccss_options['ao_ccss_keyst']         = get_option( 'autoptimize_ccss_keyst'        );
        $autoptimize_ccss_options['ao_ccss_loggedin']      = get_option( 'autoptimize_ccss_loggedin'     , '1' );
        $autoptimize_ccss_options['ao_ccss_forcepath']     = get_option( 'autoptimize_ccss_forcepath'    , '1' );
        $autoptimize_ccss_options['ao_ccss_servicestatus'] = get_option( 'autoptimize_ccss_servicestatus' );
        $autoptimize_ccss_options['ao_ccss_deferjquery']   = get_option( 'autoptimize_ccss_deferjquery'  , FALSE);
        $autoptimize_ccss_options['ao_ccss_domain']        = get_option( 'autoptimize_ccss_domain'       );

        if ( strpos( $autoptimize_ccss_options['ao_ccss_domain'], 'http') === false && strpos( $autoptimize_ccss_options['ao_ccss_domain'], 'uggc') === 0 ) {
            $autoptimize_ccss_options['ao_ccss_domain'] = str_rot13( $autoptimize_ccss_options['ao_ccss_domain'] );
        } else if ( strpos( $autoptimize_ccss_options['ao_ccss_domain'], 'http') !== false ) {
            // not rot13'ed yet, do so now (goal; avoid migration plugins change the bound domain).
            update_option( 'autoptimize_ccss_domain', str_rot13( $autoptimize_ccss_options['ao_ccss_domain'] ) );
        }

        // Setup the rules array
        if ( empty( $autoptimize_ccss_options['ao_ccss_rules_raw'] ) ) {
          $autoptimize_ccss_options['ao_ccss_rules']['paths'] = [];
          $autoptimize_ccss_options['ao_ccss_rules']['types'] = [];
        } else {
          $autoptimize_ccss_options['ao_ccss_rules'] = json_decode( $autoptimize_ccss_options['ao_ccss_rules_raw'], TRUE);
        }

        // Setup the queue array
        if ( empty( $autoptimize_ccss_options['ao_ccss_queue_raw'] ) ) {
          $autoptimize_ccss_options['ao_ccss_queue'] = [];
        } else {
          $autoptimize_ccss_options['ao_ccss_queue'] = json_decode( $autoptimize_ccss_options['ao_ccss_queue_raw'], TRUE);
        }

        return $autoptimize_ccss_options;
    }

    public function hooks_for_admin()
    {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
        add_filter( 'autoptimize_filter_settingsscreen_tabs', array( $this, 'add_extra_tab' ) );
    }

    public function add_extra_tab( $in ) {
        $in = array_merge( $in, array( 'ao_ccss_settings' => '⚡ ' . __( 'CriticalCSS', 'autoptimize' ) ));
        return $in;
    }

    public function admin_menu() {
        // Add plugin page
        $hook = add_submenu_page(NULL, 'Autoptimize CriticalCSS Power-Up', 'Autoptimize CriticalCSS Power-Up', 'manage_options', 'ao_ccss_settings', 'ao_ccss_settings' );

        // Register settings
        register_setting( 'ao_ccss_options_group', 'autoptimize_css_defer_inline' );
        register_setting( 'ao_ccss_options_group', 'autoptimize_ccss_rules' );
        register_setting( 'ao_ccss_options_group', 'autoptimize_ccss_additional' );
        register_setting( 'ao_ccss_options_group', 'autoptimize_ccss_queue' );
        register_setting( 'ao_ccss_options_group', 'autoptimize_ccss_viewport' );
        register_setting( 'ao_ccss_options_group', 'autoptimize_ccss_finclude' );
        register_setting( 'ao_ccss_options_group', 'autoptimize_ccss_rlimit' );
        register_setting( 'ao_ccss_options_group', 'autoptimize_ccss_noptimize' );
        register_setting( 'ao_ccss_options_group', 'autoptimize_ccss_debug' );
        register_setting( 'ao_ccss_options_group', 'autoptimize_ccss_key' );
        register_setting( 'ao_ccss_options_group', 'autoptimize_ccss_keyst' );
        register_setting( 'ao_ccss_options_group', 'autoptimize_ccss_loggedin' );
        register_setting( 'ao_ccss_options_group', 'autoptimize_ccss_forcepath' );
        register_setting( 'ao_ccss_options_group', 'autoptimize_ccss_deferjquery' );
        register_setting( 'ao_ccss_options_group', 'autoptimize_ccss_domain' );  

        // Check if Autoptimize is installed
        if (!is_plugin_active( 'autoptimize/autoptimize.php') && !is_plugin_active( 'autoptimize-beta/autoptimize.php' ) ) {
            add_action( 'admin_notices', array( $this, 'notice_needao' ) );
        }
    }

    public function notice_needao() {
        echo '<div class="error"><p>';
        _e( 'Autoptimize Power-Up: CriticalCSS requires <a href="https://wordpress.org/plugins/autoptimize/" target="_blank">Autoptimize</a> to be installed and active.', 'autoptimize' );
        echo '</p></div>';
    }

    public function admin_assets( $hook ) {
        // Return if plugin is not hooked
        if( $hook != 'settings_page_ao_ccss_settings') {
            return;
        }

        // Stylesheets to add
        wp_enqueue_style( 'wp-jquery-ui-dialog' );
        wp_enqueue_style( 'ao-tablesorter',    plugins_url( 'critcss-inc/css/ao-tablesorter/style.css', __FILE__));
        wp_enqueue_style( 'ao-ccss-admin-css', plugins_url( 'critcss-inc/css/admin_styles.css',         __FILE__));

        // Scripts to add
        wp_enqueue_script( 'jquery-ui-dialog',      array( 'jquery' ));
        wp_enqueue_script( 'md5',                   plugins_url( 'critcss-inc/js/md5.min.js',                __FILE__), NULL, NULL, TRUE);
        wp_enqueue_script( 'tablesorter',           plugins_url( 'critcss-inc/js/jquery.tablesorter.min.js', __FILE__), array( 'jquery'), NULL, TRUE);
        wp_enqueue_script( 'ao-ccss-admin-license', plugins_url( 'critcss-inc/js/admin_settings.js',             __FILE__), array( 'jquery'), NULL, TRUE);
    }

    public function on_activation() {
        // Create the cache directory if it doesn't exist already
        if( ! file_exists( AO_CCSS_DIR ) ) {
            mkdir( AO_CCSS_DIR, 0755, true );
        }

        // Create a scheduled event for the queue
        if (!wp_next_scheduled( 'ao_ccss_queue' ) ) {
            wp_schedule_event(time(), apply_filters( 'ao_ccss_queue_schedule', 'ao_ccss'), 'ao_ccss_queue' );
        }

        // Create a scheduled event for log maintenance
        if ( ! wp_next_scheduled( 'ao_ccss_maintenance' ) ) {
            wp_schedule_event(time(), 'twicedaily', 'ao_ccss_maintenance' );
        }

        // Scheduled event to fetch service status.
        if ( ! wp_next_scheduled( 'ao_ccss_servicestatus' ) ) {
            wp_schedule_event( time(), 'daily', 'ao_ccss_servicestatus' );
        }

        register_uninstall_hook( $this->filepath, 'autoptimizeCriticalCSS::on_uninstall' );
    }

    function ao_ccss_deactivation() {
        /*
         * We don't delete options in "power-up" to ease switch to version integrated in AO
         */

        // delete_option( 'autoptimize_ccss_rules' );
        // delete_option( 'autoptimize_ccss_additional' );
        // delete_option( 'autoptimize_ccss_queue' );
        // delete_option( 'autoptimize_ccss_viewport' );
        // delete_option( 'autoptimize_ccss_finclude' );
        // delete_option( 'autoptimize_ccss_rlimit' );
        // delete_option( 'autoptimize_ccss_noptimize' );
        // delete_option( 'autoptimize_ccss_debug' );
        // delete_option( 'autoptimize_ccss_key' );
        // delete_option( 'autoptimize_ccss_keyst' );
        // delete_option( 'autoptimize_ccss_version' );
        // delete_option( 'autoptimize_ccss_loggedin' );
        // delete_option( 'autoptimize_ccss_forcepath' );
        // delete_option( 'autoptimize_ccss_servicestatus' );
        // delete_option( 'autoptimize_ccss_deferjquery' );
        // delete_option( 'autoptimize_ccss_domain' );

        // Remove scheduled events
        wp_clear_scheduled_hook( 'ao_ccss_queue' );
        wp_clear_scheduled_hook( 'ao_ccss_maintenance' );
        wp_clear_scheduled_hook( 'ao_ccss_servicestatus' );

        // Remove cached files and directory
        array_map( 'unlink', glob(AO_CCSS_DIR . '*.{css,html,json,log,zip,lock}', GLOB_BRACE));
        rmdir(AO_CCSS_DIR);
    }
    
    public function check_upgrade() {
        $db_version = get_option( 'autoptimize_ccss_version','' );
        if ( $db_version !== AO_CCSS_VER) {
            // upgrade stuff
            if ( $db_version === '') {
                if (file_exists(WP_CONTENT_DIR.'/cache/ao_ccss' ) ) {
                    rename(WP_CONTENT_DIR.'/cache/ao_ccss', WP_CONTENT_DIR.'/uploads/ao_ccss' );
                }
            } else if ( $db_version === '1.8.0' ) {
                // Schedule service status for upgrading plugins (as upgrades don't trigger activation hook).
                if (!wp_next_scheduled( 'ao_ccss_servicestatus' ) ) {
                    wp_schedule_event(time(), 'daily', 'ao_ccss_servicestatus' );
                }
            }
            // and update db_version
            update_option( 'autoptimize_ccss_version',AO_CCSS_VER);
        }
    }

    public function ao_ccss_interval($schedules) {
        // Let interval be configurable
        if (!defined('AO_CCSS_DEBUG_INTERVAL')) {
            $intsec = 600;
        } else {
            $intsec = AO_CCSS_DEBUG_INTERVAL;
            if ($intsec >= 120) {
              $inttxt = $intsec / 60 . ' minutes';
            } else {
              $inttxt = $intsec . ' second(s)';
            }
            ao_ccss_log('Using custom WP-Cron interval of ' . $inttxt, 3);
        }

        // Attach interval to schedule
        $schedules['ao_ccss'] = array(
            'interval' => $intsec,
            'display' => __('Autoptimize CriticalCSS.com Power-Up Queue')
        );
        return $schedules;
    }
}

$autoptimizeCriticalCSS = new autoptimizeCriticalCSS;
?>