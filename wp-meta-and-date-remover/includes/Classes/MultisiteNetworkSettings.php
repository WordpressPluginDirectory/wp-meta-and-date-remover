<?php
namespace WPMDRMain\Classes;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles multisite network-level settings for WP Meta and Date Remover.
 *
 * Network admins can configure defaults that propagate to all sites.
 * Individual sites can still override unless "Force network settings" is enabled.
 */
class MultisiteNetworkSettings
{
    const OPTION_KEY   = 'wpmdr_network_settings';
    const OVERRIDE_KEY = 'wpmdr_network_override';  // Sites inherit but can override
    const FORCE_KEY    = 'wpmdr_network_force';     // Sites cannot override

    /**
     * Register hooks.
     */
    public function init()
    {
        if (!is_multisite()) {
            return;
        }

        add_action('network_admin_menu', array($this, 'addNetworkMenu'));
        add_action('network_admin_edit_wpmdr_network_save', array($this, 'saveNetworkSettings'));
        add_action('init', array($this, 'maybeApplyNetworkSettings'), 1);
    }

    /**
     * Add a page under Network Admin > Settings.
     */
    public function addNetworkMenu()
    {
        add_submenu_page(
            'settings.php',
            'WP Meta & Date Remover — Network Settings',
            'WP Meta & Date Remover',
            'manage_network_options',
            'wpmdr-network-settings',
            array($this, 'renderNetworkPage')
        );
    }

    /**
     * Render the network settings page.
     */
    public function renderNetworkPage()
    {
        if (!current_user_can('manage_network_options')) {
            wp_die(__('You do not have permission to access this page.'));
        }

        $networkSettings = get_site_option(self::OPTION_KEY, array());
        $isOverride      = (bool) get_site_option(self::OVERRIDE_KEY, false);
        $isForced        = (bool) get_site_option(self::FORCE_KEY, false);
        $defaults        = OptionsManager::getInstance()->getDefaultOptions();

        $settings = wp_parse_args($networkSettings, $defaults);

        echo '<div class="wrap">';
        echo '<h1>WP Meta &amp; Date Remover — Network Settings</h1>';

        if (isset($_GET['updated'])) {
            echo '<div class="notice notice-success"><p>Network settings saved.</p></div>';
        }

        $actionUrl = esc_url(network_admin_url('edit.php?action=wpmdr_network_save'));
        $nonce     = wp_nonce_field('wpmdr_network_nonce', '_wpnonce', true, false);

        echo '<form method="post" action="' . $actionUrl . '">';
        echo $nonce;

        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th scope="row">Propagate to sites</th>';
        echo '<td>';
        echo '<label>';
        echo '<input type="checkbox" name="wpmdr_network_override" value="1"' . checked($isOverride, true, false) . '> ';
        echo 'Apply network defaults to new &amp; existing sites (sites can still override)';
        echo '</label>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row">Force on all sites</th>';
        echo '<td>';
        echo '<label>';
        echo '<input type="checkbox" name="wpmdr_network_force" value="1"' . checked($isForced, true, false) . '> ';
        echo 'Prevent individual sites from changing these settings (use with caution)';
        echo '</label>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row">Remove by CSS (default)</th>';
        echo '<td><input type="checkbox" name="settings[removeByCSS]" value="1"' . checked(!empty($settings['removeByCSS']), true, false) . '></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row">Remove by PHP (default)</th>';
        echo '<td><input type="checkbox" name="settings[removeByPHP]" value="1"' . checked(!empty($settings['removeByPHP']), true, false) . '></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row">Remove date (default)</th>';
        echo '<td><input type="checkbox" name="settings[removeDate]" value="1"' . checked(!empty($settings['removeDate']), true, false) . '></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row">Remove author (default)</th>';
        echo '<td><input type="checkbox" name="settings[removeAuthor]" value="1"' . checked(!empty($settings['removeAuthor']), true, false) . '></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row">Remove from homepage (default)</th>';
        echo '<td><input type="checkbox" name="settings[removeFromHome]" value="1"' . checked(!empty($settings['removeFromHome']), true, false) . '></td>';
        echo '</tr>';

        echo '</table>';

        submit_button('Save Network Settings');
        echo '</form>';
        echo '</div>';
    }

    /**
     * Handle form submission for network settings.
     */
    public function saveNetworkSettings()
    {
        if (!current_user_can('manage_network_options')) {
            wp_die('Unauthorized');
        }
        check_admin_referer('wpmdr_network_nonce');

        $isOverride = isset($_POST['wpmdr_network_override']) ? true : false;
        $isForced   = isset($_POST['wpmdr_network_force']) ? true : false;

        update_site_option(self::OVERRIDE_KEY, $isOverride);
        update_site_option(self::FORCE_KEY, $isForced);

        $posted   = isset($_POST['settings']) && is_array($_POST['settings']) ? $_POST['settings'] : array();
        $defaults = OptionsManager::getInstance()->getDefaultOptions();

        $saved = array();
        foreach ($defaults as $key => $default) {
            if (is_bool($default)) {
                $saved[$key] = isset($posted[$key]) ? true : false;
            } else {
                $saved[$key] = isset($posted[$key]) ? sanitize_text_field($posted[$key]) : $default;
            }
        }

        update_site_option(self::OPTION_KEY, $saved);

        wp_redirect(add_query_arg('updated', '1', network_admin_url('settings.php?page=wpmdr-network-settings')));
        exit;
    }

    /**
     * If network override or force is on, merge network defaults into the site's settings.
     * Runs at priority 1 on 'init' so it applies before plugin logic reads options.
     */
    public function maybeApplyNetworkSettings()
    {
        if (!is_multisite()) {
            return;
        }

        $isForced   = (bool) get_site_option(self::FORCE_KEY, false);
        $isOverride = (bool) get_site_option(self::OVERRIDE_KEY, false);

        if (!$isForced && !$isOverride) {
            return;
        }

        $networkSettings = get_site_option(self::OPTION_KEY, array());
        if (empty($networkSettings)) {
            return;
        }

        if ($isForced) {
            // Force: replace site settings entirely with network settings.
            update_option('wpmdr_settings', $networkSettings);
            return;
        }

        // Override (not forced): set network defaults only if site has no settings yet.
        if (get_option('wpmdr_settings') === false) {
            update_option('wpmdr_settings', $networkSettings);
        }
    }
}
