<?php

/**
 * Plugin Name: WP Meta and Date remover
 * Plugin URI: https://wordpress.org/plugins/wp-meta-and-date-remover/
 * Description: Remove meta and date information from posts and pages
 * Author: Prasad Kirpekar
 * Author URI: https://profiles.wordpress.org/prasadkirpekar/
 * Version: 2.3.7
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
define( 'WPMDR_URL', plugin_dir_url( __FILE__ ) );
define( 'WPMDR_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPMDR_VERSION', '2.3.7' );
if ( function_exists( 'wpmdr_fs' ) ) {
    wpmdr_fs()->set_basename( false, __FILE__ );
} else {
    if ( !function_exists( 'wpmdr_fs' ) ) {
        // Create a helper function for easy SDK access.
        function wpmdr_fs() {
            global $wpmdr_fs;
            if ( !isset( $wpmdr_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $wpmdr_fs = fs_dynamic_init( array(
                    'id'               => '6753',
                    'slug'             => 'wp-meta-and-date-remover',
                    'type'             => 'plugin',
                    'public_key'       => 'pk_6bc68a469d4ab171bcc3dc4717f42',
                    'is_premium'       => false,
                    'premium_suffix'   => 'Pro',
                    'has_addons'       => false,
                    'has_paid_plans'   => true,
                    'has_affiliation'  => 'selected',
                    'menu'             => array(
                        'slug'    => 'wp-meta-and-date-remover.php',
                        'support' => false,
                        'parent'  => array(
                            'slug' => 'options-general.php',
                        ),
                    ),
                    'is_live'          => true,
                    'is_org_compliant' => true,
                ) );
            }
            return $wpmdr_fs;
        }

        // Init Freemius.
        wpmdr_fs();
        // Signal that SDK was initiated.
        do_action( 'wpmdr_fs_loaded' );
    }
    function wpmdr_uninstall_cleanup() {
    }

    add_action( 'after_uninstall', 'wpmdr_uninstall_cleanup' );
    class WPMDRMain {
        public function boot() {
            $this->loadClasses();
            $this->registerShortCodes();
            $this->ActivatePlugin();
            $this->renderMenu();
            $this->registerHooks();
            $this->registerAjax();
        }

        public function registerHooks() {
            $hookRegistrar = new \WPMDRMain\Classes\HookRegistrar();
            $hookRegistrar->registerHooks( plugin_basename( __FILE__ ) );
        }

        public function registerAjax() {
            $hookRegistrar = new \WPMDRMain\Classes\HookRegistrar();
            $hookRegistrar->registerAjax();
        }

        public function loadClasses() {
            require WPMDR_DIR . 'includes/autoload.php';
        }

        public function renderMenu() {
            add_action( 'admin_menu', function () {
                if ( !current_user_can( 'manage_options' ) ) {
                    return;
                }
                global $submenu;
                $page = add_options_page(
                    'WP Meta and Date Remover',
                    'WP Meta and Date Remover',
                    'manage_options',
                    basename( __FILE__ ),
                    array($this, 'renderAdminPage')
                );
            } );
        }

        public function addModuleToScript( $tag, $handle, $src ) {
            if ( $handle === 'WPMDR-script-boot' ) {
                $tag = '<script type="module" id="WPMDR-script-boot" src="' . esc_url( $src ) . '"></script>';
            }
            return $tag;
        }

        public function renderAdminPage() {
            $loadAssets = new \WPMDRMain\Classes\LoadAssets();
            $loadAssets->enqueueAssets();
            $ajax_nonce = wp_create_nonce( 'wpmdr_ajax_nonce' );
            $WPMDR = apply_filters( 'WPMDR/admin_app_vars', array(
                'assets_url'     => WPMDR_URL . 'assets/',
                'ajaxurl'        => admin_url( 'admin-ajax.php' ),
                'is_pro'         => !wpmdr_fs()->is_not_paying(),
                'upgrade_url'    => wpmdr_fs()->get_upgrade_url(),
                'account_url'    => wpmdr_fs()->get_account_url(),
                'plugin_version' => WPMDR_VERSION,
                'site_url'       => site_url(),
                'is_free'        => $this->isFreePlugin(),
                'nonce'          => $ajax_nonce,
            ) );
            wp_localize_script( 'WPMDR-script-boot', 'WPMDRAdmin', $WPMDR );
            echo '<div class="WPMDR-admin-page" id="WPWVT_app">
                
                <router-view></router-view>
            </div>';
        }

        public function isFreePlugin() {
            return true;
        }

        public function registerShortCodes() {
        }

        public function ActivatePlugin() {
        }

    }

    function wpmdr_custom_hide() {
        if ( get_option( 'wpmdr_custom_hide', "1" ) == "1" ) {
            return false;
        }
        return true;
    }

    function enqueue_custom_script() {
        // Only load inspector.js for logged-in administrators
        if ( !current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_enqueue_script(
            'custom-script',
            WPMDR_URL . 'assets/js/inspector.js',
            array(),
            '1.1',
            true
        );
        wp_localize_script( 'custom-script', 'wpdata', [
            'object_id' => get_queried_object_id(),
            'site_url'  => site_url(),
        ] );
    }

    add_action( 'wp_enqueue_scripts', 'enqueue_custom_script' );
    // Register activation hook
    register_activation_hook( __FILE__, array('\\WPMDRMain\\Classes\\Activator', 'activate') );
    // Run migration/version checks early on all requests as an update fallback.
    add_action( 'init', array('\\WPMDRMain\\Classes\\Activator', 'checkMigration'), 1 );
    // Check for migration on admin_init (handles updates without reactivation)
    add_action( 'admin_init', array('\\WPMDRMain\\Classes\\Activator', 'checkMigration') );
    ( new WPMDRMain() )->boot();
}