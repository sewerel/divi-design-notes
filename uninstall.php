<?php
//phpcs:ignoreFile WordPress.DB.DirectDatabaseQuery.DirectQuery 
// All database calls are for cleaning up purposes internally no user input what so ever
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

//DELETE Users Cache
delete_option('diviDesignNotesUsersCan');
//DELETE Update step flag
delete_option('divi_design_notes_update_step');
//DELETE Plugin version
delete_option('divi_design_notes_version');
//DELETE Users role
remove_role('design_notes');
//DELETE Notes
global $wpdb;
$wpdb->query("DELETE FROM $wpdb->posts WHERE post_type = 'divi_design_notes'");
