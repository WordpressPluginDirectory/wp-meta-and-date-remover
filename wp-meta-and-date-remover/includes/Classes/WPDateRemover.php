<?php
namespace WPMDRMain\Classes;

/**
 * WPDateRemover
 *
 * Slim singleton kept as the public entry-point for legacy call-sites.
 * All business logic lives in the dedicated classes:
 *   OptionsManager, CSSRemover, PHPRemover, IndividualPostManager,
 *   SeoIntegrations, AjaxController, HookRegistrar.
 */
class WPDateRemover
{
    private static $instance = null;

    public static function getInstance(): self
    {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function isElementorActive(): bool
    {
        $activePlugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
        return in_array( 'elementor/elementor.php', $activePlugins, true )
            || in_array( 'elementor-pro/elementor-pro.php', $activePlugins, true );
    }

    public function __construct() {}

    public function additionalLinks( array $links ): array
    {
        $setting_link = '<a href="../wp-admin/options-general.php?page=wp-meta-and-date-remover.php">Settings</a>';
        array_unshift( $links, $setting_link );
        return $links;
    }
}
