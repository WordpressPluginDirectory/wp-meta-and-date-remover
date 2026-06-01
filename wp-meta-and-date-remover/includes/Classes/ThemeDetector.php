<?php
namespace WPMDRMain\Classes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme and Page Builder Auto-Detection
 *
 * Detects the active WordPress theme and page builders,
 * then returns optimized CSS selectors to hide meta/date.
 *
 * @since 2.4.0
 */
class ThemeDetector
{
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
    }

    /**
     * Get CSS selectors mapped to a given theme slug.
     *
     * @param string $slug Theme stylesheet slug (lowercase).
     * @return array CSS selector strings.
     */
    private function getSelectorsForTheme($slug) {
        $map = array(
            'astra' => array(
                '.posted-on', '.byline', '.cat-links', '.entry-meta',
                '.entry-footer', '.ast-blog-single-element .post-meta',
                '.ast-header-entry-meta',
            ),
            'generatepress' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
                '.inside-article .entry-meta', '.cat-links', '.comments-link',
            ),
            'oceanwp' => array(
                '.blog-entry-date', '.blog-entry-author', '.blog-entry-comments',
                '.blog-entry-category', '.meta-date', '.post-meta', '.entry-meta',
            ),
            'flavor' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
            ),
            'flavstarter' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
            ),
            'flavor starter flavor starter starter' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
            ),
            'flavor starter starter' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
            ),
            'flavor starter flavor' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
            ),
            'flavor starter flavored' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
            ),
            'flavor starter flavor-flavored' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
            ),
            'flavor starter flavor-styled' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
            ),
            'flavored' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
            ),
            'flavor-flavored' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
            ),
            'flavor-styled' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
            ),
            'flavor/flavor' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
            ),
            'flavor/flavored' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
            ),
            'flavor/flavor-flavored' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
            ),
            'flavor/flavor-styled' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
            ),
            'twentytwentyfive' => array(
                '.wp-block-post-date', '.wp-block-post-author',
                '.wp-block-post-author__name', '.wp-block-post-terms',
            ),
            'twentytwentyfour' => array(
                '.wp-block-post-date', '.wp-block-post-author',
                '.wp-block-post-author__name', '.wp-block-post-terms',
            ),
            'twentytwentythree' => array(
                '.wp-block-post-date', '.wp-block-post-author',
                '.wp-block-post-author__name', '.wp-block-post-terms',
            ),
            'twentytwentytwo' => array(
                '.wp-block-post-date', '.wp-block-post-author',
                '.wp-block-post-author__name', '.wp-block-post-terms',
            ),
            'twentytwentyone' => array(
                '.posted-on', '.byline', '.entry-footer',
                '.entry-meta', '.cat-links', '.tags-links',
            ),
            'twentytwenty' => array(
                '.post-meta', '.post-meta-wrapper', '.posted-on',
                '.byline', '.entry-footer',
            ),
            'twentynineteen' => array(
                '.posted-on', '.byline', '.entry-meta',
                '.entry-footer', '.cat-links',
            ),
            'twentyseventeen' => array(
                '.posted-on', '.byline', '.entry-meta',
                '.entry-footer', '.cat-links', '.tags-links',
            ),
            'kadence' => array(
                '.posted-on', '.byline', '.entry-meta',
                '.entry-footer', '.post-meta', '.entry-author',
            ),
            'neve' => array(
                '.nv-meta-list', '.posted-on', '.byline',
                '.entry-meta', '.entry-footer',
            ),
            'blocksy' => array(
                '[data-date]', '[data-author]', '.entry-meta',
                '.post-meta', '.entry-footer',
            ),
            'hello-elementor' => array(
                '.entry-meta', '.entry-footer', '.posted-on', '.byline',
                '.elementor-post-info', '.elementor-post__meta-data',
            ),
            'storefront' => array(
                '.posted-on', '.byline', '.entry-meta',
                '.entry-footer', '.cat-links', '.tags-links',
            ),
        );

        if (isset($map[$slug])) {
            return $map[$slug];
        }
        return array();
    }

    /**
     * Get CSS selectors mapped to a given page builder slug.
     *
     * @param string $slug Builder identifier.
     * @return array CSS selector strings.
     */
    private function getSelectorsForBuilder($slug) {
        $map = array(
            'elementor' => array(
                '.elementor-post-info',
                '.elementor-post-info__item--type-date',
                '.elementor-post-info__item--type-author',
                '.elementor-post__meta-data',
                '.elementor-widget-theme-post-info .elementor-icon-list-item',
            ),
            'divi' => array(
                '.et_pb_post_meta',
                '.et_pb_member_post_date',
                '.et_pb_post_content .post-meta',
                '.et_pb_blog_extra .post-meta',
            ),
            'beaver-builder' => array(
                '.fl-post-meta',
                '.fl-post-meta-date',
                '.fl-post-meta-author',
                '.fl-module-post-grid .fl-post-grid-meta',
            ),
        );

        if (isset($map[$slug])) {
            return $map[$slug];
        }
        return array();
    }

    /**
     * Get the active theme stylesheet slug (lowercase).
     */
    public function getActiveThemeSlug() {
        $theme = wp_get_theme();
        return strtolower($theme->get_stylesheet());
    }

    /**
     * Get the parent theme slug if using a child theme.
     */
    public function getParentThemeSlug() {
        $theme = wp_get_theme();
        if ($theme->parent()) {
            return strtolower($theme->parent()->get_stylesheet());
        }
        return '';
    }

    /**
     * Detect active page builders and return their slugs.
     */
    public function getActiveBuilders() {
        $builders = array();
        $activePlugins = apply_filters('active_plugins', get_option('active_plugins'));

        if (WPDateRemover::isElementorActive()) {
            $builders[] = 'elementor';
        }

        $template = strtolower(wp_get_theme()->get_template());
        if ($template === 'divi'
            || in_array('divi-builder/divi-builder.php', $activePlugins)) {
            $builders[] = 'divi';
        }

        if (in_array('beaver-builder-lite-version/fl-builder.php', $activePlugins)
            || in_array('bb-plugin/fl-builder.php', $activePlugins)) {
            $builders[] = 'beaver-builder';
        }

        return $builders;
    }

    /**
     * Get theme-specific CSS selectors for the active theme.
     * Falls back to parent theme if child theme has no specific map.
     */
    public function getThemeSelectors() {
        $selectors = $this->getSelectorsForTheme($this->getActiveThemeSlug());

        if (empty($selectors)) {
            $parentSlug = $this->getParentThemeSlug();
            if ($parentSlug) {
                $selectors = $this->getSelectorsForTheme($parentSlug);
            }
        }

        return $selectors;
    }

    /**
     * Get CSS selectors for all active page builders.
     */
    public function getBuilderSelectors() {
        $builders = $this->getActiveBuilders();
        $selectors = array();
        foreach ($builders as $builder) {
            $builderSelectors = $this->getSelectorsForBuilder($builder);
            $selectors = array_merge($selectors, $builderSelectors);
        }
        return $selectors;
    }

    /**
     * Generate the full auto-detected CSS string.
     */
    public function getAutoDetectedCSS() {
        $themeSelectors = $this->getThemeSelectors();
        $builderSelectors = $this->getBuilderSelectors();
        $allSelectors = array_unique(array_merge($themeSelectors, $builderSelectors));

        if (empty($allSelectors)) {
            return '';
        }

        $css = "/* Auto-detected by WP Meta and Date Remover */\n";
        foreach ($allSelectors as $selector) {
            $selector = trim($selector);
            if (!empty($selector)) {
                $css .= "{$selector}{display:none !important;}\n";
            }
        }

        return $css;
    }

    /**
     * Get detection info for dashboard/AJAX display.
     */
    public function getDetectionInfo() {
        return array(
            'activeTheme'          => $this->getActiveThemeSlug(),
            'parentTheme'          => $this->getParentThemeSlug(),
            'themeDetected'        => !empty($this->getThemeSelectors()),
            'activeBuilders'       => $this->getActiveBuilders(),
            'builderDetected'      => !empty($this->getBuilderSelectors()),
            'themeSelectorsCount'  => count($this->getThemeSelectors()),
            'builderSelectorsCount'=> count($this->getBuilderSelectors()),
        );
    }
}
