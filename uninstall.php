<?php
/**
 * Uninstall cleanup for Cartdock.
 *
 * Runs when the plugin is deleted from wp-admin. Removes the options Cartdock
 * creates and the per-user dismissal of the PRO banner. No per-product data is
 * stored, so there is nothing else to clean up. `anchor_settings` also holds
 * Cartdock Pro's appearance and placement settings, so it stays while PRO is
 * on disk; whichever plugin is deleted last removes it.
 *
 * @package Anchor
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

if (! function_exists('get_plugins')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Core's bulk delete runs every uninstall in one request, and the cached plugin
// list would still show a PRO whose files were deleted a moment earlier.
wp_cache_delete('plugins', 'plugins');

// Matched on the text domain, so a renamed PRO folder still counts.
$anchor_addon_installed = in_array('cartdock-pro', array_column(get_plugins(), 'TextDomain'), true);

// A closure rather than a named function, so the file can be included more
// than once in a request without a redeclaration error.
$anchor_uninstall_site = static function () use ($anchor_addon_installed): void {
    if (! $anchor_addon_installed) {
        delete_option('anchor_settings');
    }
    delete_option('anchor_db_version');
};

// Options are per site, so a network uninstall has to visit every site. The
// add-on check above is about the plugin folders, which are shared by the whole
// network, so it is read once.
if (is_multisite()) {
    foreach (get_sites(['fields' => 'ids', 'number' => 0]) as $anchor_site_id) {
        switch_to_blog((int) $anchor_site_id);
        $anchor_uninstall_site();
        restore_current_blog();
    }
    unset($anchor_site_id);
} else {
    $anchor_uninstall_site();
}
unset($anchor_uninstall_site);

// The PRO banner's dismissal is stored per user, so it belongs to the
// plugin rather than to the site content. User meta is global, not
// per-site, which is why this uses delete_metadata's \$delete_all rather
// than a loop over the users of one blog.
delete_metadata('user', 0, 'anchor_pro_banner_dismissed', '', true);
