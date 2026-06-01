<?php
namespace WPMDRMain\Classes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers the Gutenberg (Block Editor) sidebar panel
 * for per-post WP Meta and Date Remover controls.
 */
class GutenbergSidebar
{
    /**
     * Initialize hooks.
     */
    public function init()
    {
        add_action('init', array($this, 'registerMeta'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueueAssets'));
    }

    /**
     * Register post meta for the REST API so the block editor can read/write it.
     */
    public function registerMeta()
    {
        $options = OptionsManager::getInstance()->getOptions();
        $postTypes = isset($options['targetPostTypes']) ? $options['targetPostTypes'] : array('post');

        foreach ($postTypes as $postType) {
            register_post_meta($postType, 'wpmdr_menu', array(
                'show_in_rest'  => true,
                'single'        => true,
                'type'          => 'integer',
                'default'       => $options['individualPostDefault'] ? 1 : 0,
                'auth_callback' => function () {
                    return current_user_can('edit_posts');
                },
            ));

            register_post_meta($postType, 'wpmdr_menu_extended', array(
                'show_in_rest'  => array(
                    'schema' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'individualPostRemove'             => array('type' => 'integer'),
                            'individualPostRemoveDate'         => array('type' => 'integer'),
                            'individualPostRemoveAuthor'       => array('type' => 'integer'),
                            'individualPostYoastRemovePublished' => array('type' => 'integer'),
                            'individualPostYoastRemoveModified'  => array('type' => 'integer'),
                        ),
                    ),
                ),
                'single'        => true,
                'type'          => 'object',
                'default'       => IndividualPostManager::getInstance()->individualPostOptionDefaults(),
                'auth_callback' => function () {
                    return current_user_can('edit_posts');
                },
            ));
        }
    }

    /**
     * Enqueue the sidebar panel script in the block editor.
     */
    public function enqueueAssets()
    {
        global $post;

        if (!$post) {
            return;
        }

        $options = OptionsManager::getInstance()->getOptions();

        // Only load if individual post option is enabled and post type is targeted.
        if (!$options['individualPostOption']) {
            return;
        }

        $targetTypes = isset($options['targetPostTypes']) ? $options['targetPostTypes'] : array('post');
        if (!in_array(get_post_type($post), $targetTypes, true)) {
            return;
        }

        wp_enqueue_script(
            'wpmdr-gutenberg-sidebar',
            WPMDR_URL . 'assets/js/gutenberg-sidebar.js',
            array('wp-plugins', 'wp-edit-post', 'wp-components', 'wp-data', 'wp-element', 'wp-compose'),
            WPMDR_VERSION,
            true
        );

        wp_localize_script('wpmdr-gutenberg-sidebar', 'wpmdrGutenberg', array(
            'isPro'    => !wpmdr_fs()->is_not_paying(),
            'defaults' => IndividualPostManager::getInstance()->individualPostOptionDefaults(),
        ));
    }
}
