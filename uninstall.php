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
if (! in_array('cartdock-pro', array_column(get_plugins(), 'TextDomain'), true)) {
    delete_option('anchor_settings');
}
delete_option('anchor_db_version');

// The PRO banner's dismissal is stored per user, so it belongs to the
// plugin rather than to the site content. User meta is global, not
// per-site, which is why this uses delete_metadata's \$delete_all rather
// than a loop over the users of one blog.
delete_metadata('user', 0, 'anchor_pro_banner_dismissed', '', true);
