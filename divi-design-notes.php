<?php
/*
Plugin Name:        Divi Design Notes
Plugin URI:         https://divi-design-notes.powdithemes.com
Version:            2.0.3
Description:        Divi Design Notes gives you the ability to pin, point and comment any part of your Divi design directly on your Divi website.
Author:             Powdistudio LTD
Author URI:         https://powdisudio.com
License:            GPL v2 or later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html
*/

defined('ABSPATH') || exit;

if (!defined('DIVI_DESIGN_NOTES_VERSION')) {
    define('DIVI_DESIGN_NOTES_VERSION', '2.0.3');
}

include(plugin_dir_path(__FILE__) . 'inc/class-divi-design-notes.php');
function divi_design_notes_on_activation() {
    add_role(
        'design_notes',
        'Designer',
        array(
            'read_design_notes'   => true
        )
    );
    $admin = get_role('administrator');
    $admin->add_cap('read_design_notes', true);
    $admin->add_cap('edit_design_notes', true);
}
add_action('init', function () {
    // WordPress.Security.NonceVerification.Recommended
    global $_REQUEST; // phpcs:ignore
    // phpcs:ignore
    if (isset($_REQUEST['et_fb']) || !function_exists('et_setup_theme')) {
        return;
    }
    new Divi_Design_Notes(plugin_dir_url(__FILE__), plugin_dir_path(__FILE__));
});
//Changed logic back to usermeta...
//register_activation_hook(__FILE__, 'divi_design_notes_on_activation');
