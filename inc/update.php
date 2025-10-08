<?php
//phpcs:ignoreFile WordPress.DB.DirectDatabaseQuery.DirectQuery 
// All database calls are for update purposes internally no user input what so ever
$update_process = get_option('divi_design_notes_update_step', 1);
function fast_insert_divi_design_note($comment) {
    global $wpdb;
    $data   =   [
        'post_author'            => absint($comment->user_id),
        'post_date'              => $comment->comment_date,
        'post_date_gmt'          => $comment->comment_date_gmt,
        'post_type'              => 'divi_design_notes',
        'post_modified'          => $comment->comment_date,
        'post_modified_gmt'      => $comment->comment_date_gmt,
        'post_content'           => $comment->comment_content,
        'post_status'            => isset($comment->comment_status) ? $comment->comment_status : 'active',
        'post_parent'            => absint($comment->comment_parent),
        'comment_count'          => absint($comment->comment_post_ID),
        'comment_status'         => 'closed',
        'ping_status'            => 'closed',
        'menu_order'             => 0,
        'post_excerpt'           => '',
        'post_title'             => '',
        'post_password'          => '',
        'post_name'              => '',
        'to_ping'                => '',
        'pinged'                 => '',
        'post_content_filtered'  => '',
        'guid'                   => '',
        'post_mime_type'         => ''
    ];
    $data = wp_unslash($data);

    if (false === $wpdb->insert($wpdb->posts, $data)) {
        return 0;
    }

    return (int) $wpdb->insert_id;
}

if ($update_process == 'Not needed for now') {
    //The entire block goes away logic back to usermeta
    //divi_design_notes_on_activation();
    $users = get_users([
        'meta_query' => [
            [
                'key' => 'divi_design_notes',
                'value' => '1',
                'compare' => '='
            ]
        ]
    ]);
    foreach ($users as $user) {
        $user->add_role('design_notes');
    }
    $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = 'divi_design_notes'");
    update_option('divi_design_notes_update_step', 1);
} elseif ($update_process == 1) {
    $old_notes = get_comments([
        'status'            => 'note',
        'order'             => 'ASC',
        'comment_type'      => 'divi_design_note',
    ]);
    $parent_notes = array();
    $children_notes  = array();
    foreach ($old_notes as $note) {
        if (empty($note->comment_parent)) {
            $parent_notes[] = $note;
        } else {
            $children_notes[$note->comment_parent][] = $note;
        }
    }
    //Delete old notes
    $wpdb->query("DELETE FROM $wpdb->comments WHERE comment_type = 'divi_design_note'");

    foreach ($parent_notes as $parent_note) {
        $note_data = maybe_unserialize($parent_note->comment_content);
        $parent_note->comment_status = !empty($note_data['res']) ? 'resolved' : 'active';
        $parent_note->comment_content = $note_data['text'];
        $meta = array(
            'el'    => isset($note_data['el']) ? $note_data['el'] : 0,
            'pos'   => isset($note_data['pos']) ? $note_data['pos'] : ['x' => 0, 'y' => 0],
        );
        $new_note_ID = fast_insert_divi_design_note($parent_note);
        if ($new_note_ID) {
            update_post_meta($new_note_ID, 'divi_design_notes_meta', maybe_serialize($meta));
            if (isset($children_notes[$parent_note->comment_ID])) {
                foreach ($children_notes[$parent_note->comment_ID] as $child) {
                    $child->comment_parent = $new_note_ID;
                    fast_insert_divi_design_note($child);
                }
            }
        }
    }
    update_option('divi_design_notes_update_step', 2);
} elseif ($update_process == 2) {
    update_option('divi_design_notes_version', $this->version);
}
