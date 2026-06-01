<?php

namespace WPMDRMain\Classes;

/**
 * AjaxController
 *
 * Handles every wp_ajax_* action for the plugin:
 *  - get_settings       → getSettings()
 *  - update_settings    → updateSettings()
 *  - load_options       → loadOptions()
 *  - dashboard_data     → dashboardData()
 *  - wpmdr_export_settings → exportSettings()
 *  - wpmdr_import_settings → importSettings()
 *
 * Also provides the REST API filter callback removeRestApiDates().
 */
class AjaxController {
    private static $instance = null;

    public static function getInstance() : self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    // ── AJAX handlers ─────────────────────────────────────────────────────────
    /**
     * wp_ajax_get_settings
     * Returns current options + theme-detection info to the admin SPA.
     */
    public function getSettings() : void {
        $this->verifyRequest();
        $om = OptionsManager::getInstance();
        $data = $om->getOptions();
        $td = ThemeDetector::getInstance();
        $data['themeDetection'] = $td->getDetectionInfo();
        $data['autoDetectedCSS'] = $td->getAutoDetectedCSS();
        wp_send_json_success( $data );
        wp_die();
    }

    /**
     * wp_ajax_update_settings
     * Sanitises and persists settings posted from the admin SPA.
     */
    public function updateSettings() : void {
        $this->verifyRequest();
        $om = OptionsManager::getInstance();
        $post = ( isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? $_POST['settings'] : [] );
        $data = [];
        $data['removeByCSS'] = filter_var( $post['removeByCSS'], FILTER_VALIDATE_BOOLEAN );
        $data['removeByPHP'] = filter_var( $post['removeByPHP'], FILTER_VALIDATE_BOOLEAN );
        $data['removeByPHPLegacy'] = filter_var( $post['removeByPHPLegacy'], FILTER_VALIDATE_BOOLEAN );
        $data['cssCode'] = sanitize_text_field( $post['cssCode'] );
        $data['removeDate'] = filter_var( $post['removeDate'], FILTER_VALIDATE_BOOLEAN );
        $data['removeAuthor'] = filter_var( $post['removeAuthor'], FILTER_VALIDATE_BOOLEAN );
        $data['removeFromHome'] = filter_var( $post['removeFromHome'], FILTER_VALIDATE_BOOLEAN );
        $data['removeFromArchive'] = filter_var( ( isset( $post['removeFromArchive'] ) ? $post['removeFromArchive'] : false ), FILTER_VALIDATE_BOOLEAN );
        $data['removeFromCategory'] = filter_var( ( isset( $post['removeFromCategory'] ) ? $post['removeFromCategory'] : false ), FILTER_VALIDATE_BOOLEAN );
        $data['removeFromSearch'] = filter_var( ( isset( $post['removeFromSearch'] ) ? $post['removeFromSearch'] : false ), FILTER_VALIDATE_BOOLEAN );
        $data['showDebugLogs'] = filter_var( $post['showDebugLogs'], FILTER_VALIDATE_BOOLEAN );
        $data['adminActivationNotice'] = true;
        $existing = $om->getOptions();
        $data = array_merge( $existing, $data );
        $om->saveOptions( $data );
        wp_send_json_success( $data );
        wp_die();
    }

    /**
     * wp_ajax_load_options
     * Returns public post types and categories for the admin SPA dropdowns.
     */
    public function loadOptions() : void {
        $this->verifyRequest();
        $categories = get_categories();
        $postTypes = array_values( get_post_types( [
            'public' => true,
        ], 'object' ) );
        wp_send_json_success( [
            'categories' => $categories,
            'postTypes'  => $postTypes,
        ] );
        wp_die();
    }

    /**
     * wp_ajax_dashboard_data
     * Returns aggregate stats for the dashboard widget in the admin SPA.
     */
    public function dashboardData() : void {
        $this->verifyRequest();
        $options = OptionsManager::getInstance()->getOptions();
        $data = [
            'targetedPostCount'     => 0,
            'excludedCategoryCount' => count( $options['excludedCategories'] ),
        ];
        foreach ( $options['targetPostTypes'] as $type ) {
            $counts = wp_count_posts( $type );
            $data['targetedPostCount'] += ( isset( $counts->publish ) ? (int) $counts->publish : 0 );
        }
        $query = new \WP_Query([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'date_query'     => [
                'before' => wp_date( 'Y-m-d', strtotime( '-3 years' ) ),
            ],
            'fields'         => 'ids',
            'posts_per_page' => -1,
        ]);
        $data['olderPostsCount'] = $query->post_count;
        $td = ThemeDetector::getInstance();
        $data['themeDetection'] = $td->getDetectionInfo();
        wp_send_json_success( $data );
        wp_die();
    }

    /**
     * wp_ajax_wpmdr_export_settings
     * Exports all plugin settings + per-post individual settings as JSON.
     */
    public function exportSettings() : void {
        $this->verifyRequest();
        $individualPostSettings = [];
        wp_send_json_success( [
            'settings'               => OptionsManager::getInstance()->getOptions(),
            'individualPostSettings' => $individualPostSettings,
        ] );
        wp_die();
    }

    /**
     * wp_ajax_wpmdr_import_settings
     * Imports settings and per-post individual options from a JSON payload.
     */
    public function importSettings() : void {
        $this->verifyRequest();
        $json = ( isset( $_POST['settings_json'] ) ? sanitize_text_field( wp_unslash( $_POST['settings_json'] ) ) : '' );
        $imported = json_decode( $json, true );
        if ( json_last_error() !== JSON_ERROR_NONE || !is_array( $imported ) ) {
            wp_send_json_error( 'Invalid JSON: ' . json_last_error_msg() );
            wp_die();
        }
        // Support both the new { settings, individualPostSettings } envelope
        // and the legacy flat format.
        $rawSettings = ( isset( $imported['settings'] ) ? $imported['settings'] : $imported );
        $rawPosts = ( isset( $imported['individualPostSettings'] ) ? $imported['individualPostSettings'] : [] );
        // ── Plugin-wide settings ──────────────────────────────────────────────
        $om = OptionsManager::getInstance();
        $defaults = $om->getDefaultOptions();
        $sanitized = [];
        foreach ( $defaults as $key => $default_val ) {
            $sanitized[$key] = ( isset( $rawSettings[$key] ) ? $rawSettings[$key] : $default_val );
        }
        if ( isset( $sanitized['cssCode'] ) ) {
            $sanitized['cssCode'] = sanitize_text_field( $sanitized['cssCode'] );
        }
        if ( isset( $sanitized['visualRemoverClassMap'] ) ) {
            $sanitized['visualRemoverClassMap'] = $om->sanitizeClassMap( $sanitized['visualRemoverClassMap'] );
        }
        $sanitized['adminActivationNotice'] = true;
        $om->saveOptions( $sanitized );
        // ── Per-post individual settings ─────────────────────────────────────
        $importedPostCount = 0;
        wp_send_json_success( [
            'settings'          => $sanitized,
            'importedPostCount' => $importedPostCount,
        ] );
        wp_die();
    }

    /**
     * REST API filter: rest_prepare_{post_type}
     * Removes date fields from REST API responses when restApiRemoveDates is enabled.
     *
     * @param \WP_REST_Response $response
     * @param \WP_Post          $post
     * @param \WP_REST_Request  $request
     * @return \WP_REST_Response
     */
    public function removeRestApiDates( $response, $post, $request ) {
        $options = OptionsManager::getInstance()->getOptions();
        if ( empty( $options['restApiRemoveDates'] ) ) {
            return $response;
        }
        if ( !in_array( $post->post_type, $options['targetPostTypes'], true ) ) {
            return $response;
        }
        $data = $response->get_data();
        unset(
            $data['date'],
            $data['date_gmt'],
            $data['modified'],
            $data['modified_gmt']
        );
        $response->set_data( $data );
        return $response;
    }

    // ── Private helpers ───────────────────────────────────────────────────────
    /**
     * Verify nonce and capability for every AJAX request.
     * Dies with JSON error on failure.
     */
    private function verifyRequest() : void {
        if ( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'wpmdr_ajax_nonce' ) ) {
            wp_send_json_error( 'Invalid nonce' );
            wp_die();
        }
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
            wp_die();
        }
    }

}
