<?php
namespace WPMDRMain\Classes;

/**
 * OptionsManager
 *
 * Responsible for reading, writing, and sanitizing plugin settings.
 * All other classes should call OptionsManager::getInstance()->getOptions()
 * instead of reading the option directly.
 */
class OptionsManager
{
    private static $instance = null;

    public static function getInstance(): self
    {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Return the full options array, bootstrapping defaults on first run
     * and back-filling any keys that were added in later plugin versions.
     */
    public function getOptions(): array
    {
        $data = get_option( 'wpmdr_settings' );
        if ( ! $data ) {
            $data = $this->getDefaultOptions();
            update_option( 'wpmdr_settings', $data );
        }
        // Back-fill keys added in subsequent plugin versions.
        if ( ! isset( $data['removeByPHPLegacy'] ) )                          $data['removeByPHPLegacy']                          = false;
        if ( ! isset( $data['rankMathSchemaRemoveArticleDatePublished'] ) )    $data['rankMathSchemaRemoveArticleDatePublished']    = false;
        if ( ! isset( $data['rankMathSchemaRemoveArticleDateModified'] ) )     $data['rankMathSchemaRemoveArticleDateModified']     = false;
        if ( ! isset( $data['rankMathSchemaRemoveOgDateUpdated'] ) )           $data['rankMathSchemaRemoveOgDateUpdated']           = false;
        if ( ! isset( $data['rankMathSchemaRemoveYaOVSUploadDate'] ) )         $data['rankMathSchemaRemoveYaOVSUploadDate']         = false;
        if ( ! isset( $data['removeFromArchive'] ) )                           $data['removeFromArchive']                          = false;
        if ( ! isset( $data['removeFromCategory'] ) )                          $data['removeFromCategory']                         = false;
        if ( ! isset( $data['removeFromSearch'] ) )                            $data['removeFromSearch']                           = false;
        if ( ! isset( $data['seopressSchemaRemoveDatePublished'] ) )           $data['seopressSchemaRemoveDatePublished']           = false;
        if ( ! isset( $data['seopressSchemaRemoveDateModified'] ) )            $data['seopressSchemaRemoveDateModified']            = false;
        if ( ! isset( $data['aioseoSchemaRemoveDatePublished'] ) )             $data['aioseoSchemaRemoveDatePublished']             = false;
        if ( ! isset( $data['aioseoSchemaRemoveDateModified'] ) )              $data['aioseoSchemaRemoveDateModified']              = false;
        if ( ! isset( $data['wooCommerceRemoveSchemaDate'] ) )                 $data['wooCommerceRemoveSchemaDate']                 = false;
        if ( ! isset( $data['restApiRemoveDates'] ) )                          $data['restApiRemoveDates']                         = false;
        return $data;
    }

    /**
     * Persist a fully-sanitized settings array to the database.
     */
    public function saveOptions( array $data ): void
    {
        update_option( 'wpmdr_settings', $data );
    }

    /**
     * Build and return the factory-default options array.
     * Migrates legacy individual option values when present.
     */
    public function getDefaultOptions(): array
    {
        $css = ".wp-block-post-author__name{display:none !important;}\n.wp-block-post-date{display:none !important;}\n .entry-meta {display:none !important;}\r\n\t.home .entry-meta { display: none; }\r\n\t.entry-footer {display:none !important;}\r\n\t.home .entry-footer { display: none; }";

        $default = array();
        $default['removeByCSS']        = get_option( 'wpmdr_disable_css', '0' ) === '0';
        $default['removeByPHP']        = get_option( 'wpmdr_disable_php', '0' ) === '0';
        $default['removeByPHPLegacy']  = false;
        $default['cssCode']            = get_option( 'wpmdr_css', $css );
        $default['removeDate']         = get_option( 'wpmdr_remove_date', '1' ) === '1';
        $default['removeAuthor']       = get_option( 'wpmdr_remove_author', '1' ) === '1';
        $default['targetPostTypes']    = get_option( 'wpmdr_included_post_types', ['post'] );
        $default['targetPostAge']      = get_option( 'wpmdr_post_age', 0 );
        $default['targetBasedOnPostAge'] = get_option( 'wpmdr_post_age', '-1' ) !== '-1';
        $default['individualPostDefault'] = get_option( 'wpmdr_individual_post_default', 1 );
        $default['individualPostOption']  = get_option( 'wpmdr_individual_post', '0' ) !== '0';
        $default['removeFromHome']     = true;
        $default['excludedCategories'] = get_option( 'wpmdr_excluded_categories', [] );
        $default['yoastSchemaRemoveDatePublished']           = get_option( 'wpmdr_yoast_datepublished', '0' ) !== '0';
        $default['yoastSchemaRemoveDateModified']            = get_option( 'wpmdr_yoast_dateupdated', '0' ) !== '0';
        $default['rankMathSchemaRemoveArticleDatePublished'] = get_option( 'wpmdr_rankmath_article_datepublished', '0' ) !== '0';
        $default['rankMathSchemaRemoveArticleDateModified']  = get_option( 'wpmdr_rankmath_article_dateupdated', '0' ) !== '0';
        $default['rankMathSchemaRemoveOgDateUpdated']        = get_option( 'wpmdr_rankmath_og_dateupdated', '0' ) !== '0';
        $default['rankMathSchemaRemoveYaOVSUploadDate']      = get_option( 'wpmdr_rankmath_ya_ovs_upload_date', '0' ) !== '0';
        $default['showDebugLogs']      = get_option( 'wpmdr_debug_info', '0' ) !== '0';
        $default['adminActivationNotice'] = true;
        $default['visualRemoverCSS']      = '';
        $default['visualRemoverClassMap'] = array();
        // Page-type controls
        $default['removeFromArchive']  = false;
        $default['removeFromCategory'] = false;
        $default['removeFromSearch']   = false;
        // SEOPress schema
        $default['seopressSchemaRemoveDatePublished'] = false;
        $default['seopressSchemaRemoveDateModified']  = false;
        // All in One SEO schema
        $default['aioseoSchemaRemoveDatePublished'] = false;
        $default['aioseoSchemaRemoveDateModified']  = false;
        // WooCommerce
        $default['wooCommerceRemoveSchemaDate'] = false;
        // REST API
        $default['restApiRemoveDates'] = false;
        return $default;
    }

    /**
     * Sanitize the visual-remover class map received from an AJAX request.
     * Keys = int post IDs (0 = home page).
     * Values = arrays of valid CSS class-name strings.
     *
     * @param mixed $classMap Raw input.
     * @return array Sanitized map.
     */
    public function sanitizeClassMap( $classMap ): array
    {
        if ( ! is_array( $classMap ) ) {
            return array();
        }
        $sanitized = array();
        foreach ( $classMap as $postId => $classes ) {
            $postId = intval( $postId );
            if ( ! is_array( $classes ) ) {
                continue;
            }
            $sanitizedClasses = array();
            foreach ( $classes as $class ) {
                $class = sanitize_text_field( $class );
                $class = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $class );
                if ( ! empty( $class ) ) {
                    $sanitizedClasses[] = $class;
                }
            }
            if ( ! empty( $sanitizedClasses ) ) {
                $sanitized[ $postId ] = $sanitizedClasses;
            }
        }
        return $sanitized;
    }
}
