<?php

namespace WPMDRMain\Classes;

/**
 * PHPRemover
 *
 * Handles PHP-filter–based date/author removal and the central routing
 * logic that decides whether removal should be applied on a given request.
 *
 * Dependencies (resolved at call-time via singletons / instantiation):
 *  - OptionsManager
 *  - CSSRemover
 *  - IndividualPostManager  (for per-post defaults inside removerFilter)
 */
class PHPRemover {
    private static $instance = null;

    public static function getInstance() : self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    // ── Public API ────────────────────────────────────────────────────────────
    /**
     * Add WordPress filters that blank out date and author output.
     *
     * @param array $contentTypes Subset of ['date','author'] to apply.
     */
    public function removeWithPHP( array $contentTypes = ['date', 'author'] ) : void {
        $options = OptionsManager::getInstance()->getOptions();
        if ( $options['removeDate'] && in_array( 'date', $contentTypes, true ) ) {
            add_filter( 'the_date', '__return_false' );
            add_filter( 'the_time', '__return_false' );
            add_filter( 'the_modified_date', '__return_false' );
            add_filter( 'get_the_date', '__return_false' );
            add_filter( 'get_the_time', '__return_false' );
            add_filter( 'get_the_modified_date', '__return_false' );
        }
        if ( $options['removeAuthor'] && in_array( 'author', $contentTypes, true ) ) {
            add_filter( 'the_author', '__return_false' );
            add_filter( 'get_the_author', '__return_false' );
            add_filter( 'get_the_author_display_name', '__return_false' );
        }
    }

    /**
     * Remove any previously attached date/author filters.
     * Used by the per-post (the_post) hook to reset between posts.
     */
    public function resetFilter() : void {
        remove_filter( 'the_date', '__return_false' );
        remove_filter( 'the_time', '__return_false' );
        remove_filter( 'the_modified_date', '__return_false' );
        remove_filter( 'get_the_date', '__return_false' );
        remove_filter( 'get_the_time', '__return_false' );
        remove_filter( 'get_the_modified_date', '__return_false' );
        remove_filter( 'the_author', '__return_false' );
        remove_filter( 'get_the_author', '__return_false' );
        remove_filter( 'get_the_author_display_name', '__return_false' );
    }

    /**
     * Central routing method: determines whether removal should be applied
     * for the current request and post, then delegates to applyRemover().
     *
     * @param string $type 'css' or 'php'.
     */
    public function removerFilter( string $type ) : void {
        $contentTypes = ['date', 'author'];
        $options = OptionsManager::getInstance()->getOptions();
        // Home / front-page.
        if ( (is_home() || is_front_page()) && $options['removeFromHome'] ) {
            $this->applyRemover( $type );
        }
        // Archive, category/tag/taxonomy, and search.
        if ( is_archive() && !empty( $options['removeFromArchive'] ) ) {
            $this->applyRemover( $type, $contentTypes );
        }
        if ( (is_category() || is_tag() || is_tax()) && !empty( $options['removeFromCategory'] ) ) {
            $this->applyRemover( $type, $contentTypes );
        }
        if ( is_search() && !empty( $options['removeFromSearch'] ) ) {
            $this->applyRemover( $type, $contentTypes );
        }
        global $post;
        if ( is_null( $post ) ) {
            $this->logDebug( 'Invalid post to remove meta and date' );
            return;
        }
        $this->applyRemover( $type, $contentTypes );
    }

    // ── Private helpers ───────────────────────────────────────────────────────
    /**
     * Dispatch to the appropriate remover (CSS or PHP).
     *
     * @param string $type         'css' or 'php'.
     * @param array  $contentTypes Subset of ['date','author'].
     */
    private function applyRemover( string $type, array $contentTypes = ['date', 'author'] ) : void {
        $options = OptionsManager::getInstance()->getOptions();
        if ( $options['removeByCSS'] && $type === 'css' ) {
            CSSRemover::getInstance()->removeWithCSS();
        }
        if ( $options['removeByPHP'] && $type === 'php' ) {
            $this->removeWithPHP( $contentTypes );
        }
    }

    /** Forward debug messages through the shared logger. */
    private function logDebug( string $msg ) : void {
        $options = OptionsManager::getInstance()->getOptions();
        if ( $options['showDebugLogs'] ) {
            error_log( '[WP Meta and Date Remover] ' . $msg );
        }
    }

}
