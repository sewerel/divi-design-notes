<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

delete_option('diviDesignNotesUsersCan');

global $wpdb;

$wpdb->query(
    $wpdb->prepare(
        "
        DELETE FROM $wpdb->comments 
        WHERE comment_type = %s
        ",
        'divi_design_note'
    )
);