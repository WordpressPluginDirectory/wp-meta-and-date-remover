<?php

namespace WPMDRMain\Classes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Activator
 * 
 * Handles plugin activation, default options setup,
 * and version migration from legacy option keys.
 *
 * @since 2.4.0
 */
class Activator
{
    /**
     * Prevent duplicate migration checks in the same request (e.g. init + admin_init).
     *
     * @var bool
     */
    private static $migrationChecked = false;

    /**
     * Run on plugin activation.
     * Sets default options if not already present and migrates legacy options.
     */
    public static function activate()
    {
        self::migrateLegacyOptions();
        self::setDefaults();
        update_option('wpmdr_plugin_version', WPMDR_VERSION);
    }

    /**
     * Check if migration is needed on admin_init (for users who update without reactivating).
     */
    public static function checkMigration()
    {
        if ( self::$migrationChecked ) {
            return;
        }

        self::$migrationChecked = true;

        $storedVersion = get_option('wpmdr_plugin_version', '0');
        if (version_compare($storedVersion, WPMDR_VERSION, '<')) {
            self::migrateLegacyOptions();
            update_option('wpmdr_plugin_version', WPMDR_VERSION);
        }
    }

    /**
     * Set default options if wpmdr_settings doesn't exist yet.
     */
    private static function setDefaults()
    {
        if (get_option('wpmdr_settings') !== false) {
            return; // Already has settings, don't overwrite
        }

        $css = ".wp-block-post-author__name{display:none !important;}\n"
             . ".wp-block-post-date{display:none !important;}\n"
             . ".entry-meta {display:none !important;}\n"
             . ".home .entry-meta { display: none; }\n"
             . ".entry-footer {display:none !important;}\n"
             . ".home .entry-footer { display: none; }";

        $defaults = array(
            'removeByCSS'                          => true,
            'removeByPHP'                          => true,
            'removeByPHPLegacy'                    => false,
            'cssCode'                              => $css,
            'removeDate'                           => true,
            'removeAuthor'                         => true,
            'targetPostTypes'                      => array('post'),
            'targetPostAge'                        => 0,
            'targetBasedOnPostAge'                 => false,
            'individualPostDefault'                => true,
            'individualPostOption'                 => false,
            'removeFromHome'                       => true,
            'excludedCategories'                   => array(),
            'yoastSchemaRemoveDatePublished'       => false,
            'yoastSchemaRemoveDateModified'        => false,
            'rankMathSchemaRemoveArticleDatePublished' => false,
            'rankMathSchemaRemoveArticleDateModified'  => false,
            'rankMathSchemaRemoveOgDateUpdated'        => false,
            'rankMathSchemaRemoveYaOVSUploadDate'      => false,
            'showDebugLogs'                        => false,
            'adminActivationNotice'                => true,
            'visualRemoverCSS'                     => '',
            'visualRemoverClassMap'                => array(),
            // Page-type controls
            'removeFromArchive'                    => false,
            'removeFromCategory'                   => false,
            'removeFromSearch'                     => false,
            // SEOPress schema
            'seopressSchemaRemoveDatePublished'    => false,
            'seopressSchemaRemoveDateModified'     => false,
            // All in One SEO schema
            'aioseoSchemaRemoveDatePublished'      => false,
            'aioseoSchemaRemoveDateModified'       => false,
            // WooCommerce
            'wooCommerceRemoveSchemaDate'           => false,
            // REST API
            'restApiRemoveDates'                   => false,
        );

        update_option('wpmdr_settings', $defaults);
    }

    /**
     * Migrate legacy individual option keys into the unified wpmdr_settings option.
     * Cleans up old option keys after migration.
     */
    private static function migrateLegacyOptions()
    {
        // Only migrate if old options exist and new unified option doesn't
        $legacyKeys = array(
            'wpmdr_disable_css',
            'wpmdr_disable_php',
            'wpmdr_css',
            'wpmdr_remove_date',
            'wpmdr_remove_author',
            'wpmdr_included_post_types',
            'wpmdr_post_age',
            'wpmdr_individual_post_default',
            'wpmdr_individual_post',
            'wpmdr_excluded_categories',
            'wpmdr_yoast_datepublished',
            'wpmdr_yoast_dateupdated',
            'wpmdr_rankmath_article_datepublished',
            'wpmdr_rankmath_article_dateupdated',
            'wpmdr_rankmath_og_dateupdated',
            'wpmdr_rankmath_ya_ovs_upload_date',
            'wpmdr_debug_info',
            'wpmdr_custom_hide',
        );

        $hasLegacy = false;
        foreach ($legacyKeys as $key) {
            if (get_option($key) !== false) {
                $hasLegacy = true;
                break;
            }
        }

        if (!$hasLegacy) {
            return; // No legacy options to migrate
        }

        // Explicitly construct/refresh the unified option from legacy-aware defaults
        // before cleaning up old option keys.
        $defaults = OptionsManager::getInstance()->getDefaultOptions();
        $existing = get_option('wpmdr_settings', false);

        if (is_array($existing)) {
            $defaults = array_merge($defaults, $existing);
        }

        update_option('wpmdr_settings', $defaults);

        // Clean up old legacy keys after values are persisted in wpmdr_settings.
        foreach ($legacyKeys as $key) {
            delete_option($key);
        }
    }
}
