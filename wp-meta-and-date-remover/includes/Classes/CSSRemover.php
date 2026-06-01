<?php
namespace WPMDRMain\Classes;

/**
 * CSSRemover
 *
 * Handles all CSS-based date/meta removal:
 *  - Outputting the user-defined CSS block on wp_head.
 *  - Outputting the visual-remover override rules on wp_head.
 *  - Sanitizing raw CSS strings before output.
 */
class CSSRemover
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
     * Echo the main CSS block (user CSS + auto-detected theme CSS).
     * Hooked to wp_head.
     */
    public function removeWithCSS(): void
    {
        $options = OptionsManager::getInstance()->getOptions();
        if ( $options['removeByCSS'] ) {
            $css = $this->sanitizeCSS( $options['cssCode'] );
            echo "<style>/* CSS added by WP Meta and Date Remover*/" . $css . "</style>";
        }
    }

    /**
     * Echo per-post visual-remover class overrides and the global visual-remover CSS.
     * Hooked to wp_head (premium only).
     */
    public function applyVisualRemoverCode(): void
    {
        $options  = OptionsManager::getInstance()->getOptions();
        $classMap = $options['visualRemoverClassMap'];
        if ( ! empty( $classMap ) ) {
            foreach ( $classMap as $key => $value ) {
                $isHomePage = is_home() || is_front_page();
                if ( $key == get_the_ID() || ( $isHomePage && $key == 0 ) ) {
                    foreach ( $value as $class ) {
                        $safeClass = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $class );
                        echo "<style>/* Added by visual remover */.{$safeClass}{display:none!important;}</style>";
                    }
                }
            }
        }
        echo "<style>/* Added by visual remover */" . $this->sanitizeCSS( $options['visualRemoverCSS'] ) . "</style>";
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Sanitize a CSS string to prevent XSS vectors.
     *
     * Strips: expression(), url(javascript:), @import, behavior,
     * -moz-binding, </style> tag breakouts, and dangerous HTML tags.
     *
     * @param string $css Raw CSS string.
     * @return string Sanitized CSS.
     */
    public function sanitizeCSS( string $css ): string
    {
        if ( empty( $css ) ) {
            return '';
        }
        $css = preg_replace( '/<\s*\/\s*style\s*>/i', '', $css );
        $css = preg_replace( '/<\s*(script|link|iframe|object|embed|form|input|svg|img)[^>]*>/i', '', $css );
        $css = preg_replace( '/expression\s*\(/i', '/* blocked */(', $css );
        $css = preg_replace( '/url\s*\(\s*["\']?\s*(javascript|data\s*:\s*text\/html)/i', 'url(/* blocked */', $css );
        $css = preg_replace( '/@import/i', '/* @import blocked */', $css );
        $css = preg_replace( '/behavior\s*:/i', '/* behavior blocked */:', $css );
        $css = preg_replace( '/-moz-binding\s*:/i', '/* moz-binding blocked */:', $css );
        return $css;
    }
}
