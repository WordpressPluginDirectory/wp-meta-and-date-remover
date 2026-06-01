<?php

namespace WPMDRMain\Classes;

/**
 * HookRegistrar
 *
 * Centralises all add_action / add_filter calls for the plugin.
 * Replaces the inline registerHooks() and registerAjax() methods that
 * previously lived in WPMDRMain (the bootstrap class in the main plugin file).
 *
 * Called once from WPMDRMain::boot().
 */
class HookRegistrar {
    /**
     * Register all front-end, admin, and REST API hooks.
     *
     * @param string $pluginBasename  Plugin basename from plugin_basename(__FILE__)
     *                                in the main plugin file.
     */
    public function registerHooks( string $pluginBasename ) : void {
        $om = OptionsManager::getInstance();
        $options = $om->getOptions();
        // ── Individual-post meta box & block-editor sidebar ───────────────────
        if ( $options['individualPostOption'] ) {
            $ipm = IndividualPostManager::getInstance();
            add_action( 'add_meta_boxes', [$ipm, 'addIndividualPostOptionCheckbox'] );
            add_action( 'save_post', [$ipm, 'updateOptionToPost'] );
            if ( WPDateRemover::isElementorActive() ) {
                new ThirdParty\Elementor();
            }
            $gutenbergSidebar = new GutenbergSidebar();
            $gutenbergSidebar->init();
        }
        // ── Front-end CSS removal ─────────────────────────────────────────────
        $cssRemover = CSSRemover::getInstance();
        $phpRemover = PHPRemover::getInstance();
        add_action( 'wp_head', function () use($phpRemover) {
            $phpRemover->removerFilter( 'css' );
        }, 10 );
        // ── Front-end PHP removal ─────────────────────────────────────────────
        if ( $options['removeByPHPLegacy'] ) {
            add_action( 'loop_start', function () use($phpRemover) {
                $phpRemover->removerFilter( 'php' );
            }, 10 );
        } else {
            if ( !is_admin() ) {
                add_action( 'the_post', function () use($phpRemover) {
                    $phpRemover->resetFilter();
                    $phpRemover->removerFilter( 'php' );
                }, 10 );
            }
        }
        // ── Plugin action links ───────────────────────────────────────────────
        $wpmdr = WPDateRemover::getInstance();
        add_filter( "plugin_action_links_{$pluginBasename}", [$wpmdr, 'additionalLinks'] );
        // ── REST API date removal ─────────────────────────────────────────────
        add_action( 'rest_api_init', function () use($om) {
            $options = $om->getOptions();
            if ( !empty( $options['restApiRemoveDates'] ) ) {
                $ajaxCtrl = AjaxController::getInstance();
                foreach ( $options['targetPostTypes'] as $postType ) {
                    add_filter(
                        "rest_prepare_{$postType}",
                        [$ajaxCtrl, 'removeRestApiDates'],
                        10,
                        3
                    );
                }
            }
        } );
        // ── Multisite network settings ────────────────────────────────────────
        if ( is_multisite() ) {
            $netSettings = new MultisiteNetworkSettings();
            $netSettings->init();
        }
    }

    /**
     * Register all wp_ajax_* actions for the admin SPA.
     */
    public function registerAjax() : void {
        $ajax = AjaxController::getInstance();
        // Register prefixed actions first to avoid collisions with generic names from other plugins.
        add_action( 'wp_ajax_wpmdr_load_options', [$ajax, 'loadOptions'] );
        add_action( 'wp_ajax_wpmdr_get_settings', [$ajax, 'getSettings'] );
        add_action( 'wp_ajax_wpmdr_update_settings', [$ajax, 'updateSettings'] );
        add_action( 'wp_ajax_wpmdr_dashboard_data', [$ajax, 'dashboardData'] );
        add_action( 'wp_ajax_wpmdr_export_settings', [$ajax, 'exportSettings'] );
        add_action( 'wp_ajax_wpmdr_import_settings', [$ajax, 'importSettings'] );
        // Backward-compatible aliases for older builds.
        add_action( 'wp_ajax_load_options', [$ajax, 'loadOptions'] );
        add_action( 'wp_ajax_get_settings', [$ajax, 'getSettings'] );
        add_action( 'wp_ajax_update_settings', [$ajax, 'updateSettings'] );
        add_action( 'wp_ajax_dashboard_data', [$ajax, 'dashboardData'] );
    }

}
