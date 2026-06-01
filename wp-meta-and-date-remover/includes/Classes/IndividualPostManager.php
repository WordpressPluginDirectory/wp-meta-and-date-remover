<?php

namespace WPMDRMain\Classes;

/**
 * IndividualPostManager
 *
 * Manages the per-post "WP Meta and Date Remover" meta-box, including
 * rendering, saving, Elementor page-settings sync, and providing default
 * values for the extended individual-post option array.
 */
class IndividualPostManager {
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
     * Return the default extended individual-post option array.
     * Honours the global individualPostDefault setting.
     */
    public function individualPostOptionDefaults() : array {
        $options = OptionsManager::getInstance()->getOptions();
        $defaultValue = ( $options['individualPostDefault'] ? 1 : 0 );
        return [
            'individualPostRemove'               => $defaultValue,
            'individualPostRemoveDate'           => 1,
            'individualPostRemoveAuthor'         => 1,
            'individualPostYoastRemovePublished' => $defaultValue,
            'individualPostYoastRemoveModified'  => $defaultValue,
        ];
    }

    /**
     * Register the meta box for supported post types (Pro only).
     * Hooked to add_meta_boxes.
     */
    public function addIndividualPostOptionCheckbox() : void {
    }

    /**
     * Render the meta-box HTML for a single post.
     * Hooked as the meta-box callback.
     */
    public function addOptionToPost() : void {
    }

    /**
     * Retrieve and—if missing—bootstrap the extended individual options for a post.
     *
     * @param int $postId
     * @return array [wpmdr_menu value, wpmdr_menu_extended array]
     */
    public function getAndPopulateExtendedIndvOptions( int $postId ) : array {
        $value = get_post_meta( $postId, 'wpmdr_menu', true );
        $value_extended = get_post_meta( $postId, 'wpmdr_menu_extended', true );
        if ( empty( $value_extended ) && !empty( $value ) && $value != 0 ) {
            $defaults = $this->individualPostOptionDefaults();
            add_post_meta(
                $postId,
                'wpmdr_menu_extended',
                $defaults,
                true
            );
            $value_extended = $defaults;
        }
        return [$value, $value_extended];
    }

    /**
     * Save the meta-box values when a post is saved.
     * Hooked to save_post.
     *
     * @param int $postid
     */
    public function updateOptionToPost( int $postid ) : void {
    }

    /**
     * Sync the WPMDR individual options into Elementor page settings meta.
     *
     * @param int   $postid
     * @param array $data Extended options array.
     */
    public function updateElementorOptionToPost( int $postid, array $data ) : void {
    }

    /**
     * Programmatically enable the individual option for a post (API helper).
     *
     * @param int $postId
     */
    public function addIndividualPostOption( int $postId ) : void {
        add_post_meta(
            $postId,
            'wpmdr_menu',
            1,
            true
        );
    }

}
