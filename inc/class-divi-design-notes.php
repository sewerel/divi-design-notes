<?php

class Divi_Design_Notes {

    public $version = '1.0.0';
    public $comment_type = 'divi_design_note';
    public $current_user = 0;
    public $user_can_notes = false;
    public $user_is_admin = false;
    public $post = '';
    public $post_id = 0;
    public $permalink = '';
    public $users_can_notes = 0;
    public $notes = null;
    public $child_notes = null;
    public $pages = null;
    public $nonce = '';
    public $plugin_url = null;
    public $plugin_path = null;
    public $update_needed = false;

    function __construct($url, $path) {
        $this->version = DIVI_DESIGN_NOTES_VERSION;
        $this->plugin_url = $url;
        $this->plugin_path = $path;
        $this->current_user = wp_get_current_user();
        if (!get_option('divi_design_notes_version', false)) {
            $this->check_update_needed();
        }
        if ($this->update_needed) {
            $this->update_design_notes_api();
            add_action('admin_notices', [$this, 'update_notice']);
        }
        if ($this->current_user->ID) {
            $this->set_user_status();
        }
        if ($this->user_can_notes) {
            if (!wp_doing_ajax()) {
                $this->nonce = wp_create_nonce('diviDesignNotes');
            }
            add_filter('show_admin_bar', '__return_true', 100, 0);
            add_action('template_redirect', [$this, 'init']);
        }
        $this->set_users();

        add_action('wp_ajax_get_design_notes', [$this, 'ajax_all_design_notes']);
        add_action('wp_ajax_divi_design_notes_ajax', [$this, 'ajax_divi_design_notes']);
        add_action('edit_user_profile', [$this, 'custom_user_profile_fields'], 10, 1);
        add_action('edit_user_profile_update', [$this, 'user_meta_update'], 1, 1);

        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('edit_user_created_user', [$this, 'wipe_out_user_cache'], 10, 1);
        add_action('profile_update', [$this, 'wipe_out_user_cache'], 10, 1);
        add_action('deleted_user', [$this, 'wipe_out_user_cache'], 10, 1);

        add_action('admin_enqueue_scripts', [$this, 'admin_scripts'], 10, 1);
    }
    function admin_scripts($suffix) {
        if ('toplevel_page_design_notes' === $suffix) {
            wp_enqueue_style('ddn_admin_css', $this->plugin_url . 'admin/css/admin.css', [], $this->version);
            wp_enqueue_script('ddn_admin_js', $this->plugin_url . 'admin/js/admin.js', [], $this->version, true);
        }
    }
    function wipe_out_user_cache($user_id) {
        delete_option('diviDesignNotesUsersCan');
    }
    function update_api() {
        global $wpdb;
        include $this->plugin_path . 'inc/update.php';
    }
    function update_notice() { ?>
        <div class="notice notice-error is-dismissible">
            <p>Divi Design Notes currently updating old API please <a href="#" onclick="window.location.reload(true);">refresh</a> to finish the update?</p>
        </div>
        <?php
    }
    function notes_post_type() {

        // Add a new capability.
        $args = array(
            'label'                 => 'Note',
            'supports'              => [],
            'hierarchical'          => true,
            'public'                => false,
            'show_ui'               => false,
            'show_in_menu'          => false,
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'can_export'            => false,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'rewrite'               => false,
            'show_in_rest'          => false
        );
        register_post_type('divi_design_notes', $args);
    }
    function init() {

        $this->notes_post_type();
        $this->post = get_post();
        if ($this->post) {
            $this->post_id = $this->post->ID;
            $this->permalink = get_permalink($this->post);
            $this->notes = $this->get_current_notes();
            $this->nonce = wp_create_nonce('diviDesignNotes');

            add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
            add_action('admin_bar_menu', [$this, 'admin_bar_item'], 100);
            add_action('wp_footer', [$this, 'notes_create_html']);
            add_action('wp_footer', [$this, 'display_notes']);
        }
    }
    function check_update_needed() {
        global $wpdb;
        // WordPress.DB.DirectDatabaseQuery.DirectQuery WordPress.DB.DirectDatabaseQuery.NoCaching
        // phpcs:ignore
        $has_note = $wpdb->query("SELECT comment_ID FROM $wpdb->comments WHERE comment_type = 'divi_design_note' LIMIT 1");
        // phpcs:ignore
        $has_user = $wpdb->query("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'divi_design_notes' LIMIT 1");
        if ($has_note || $has_user) {
            $this->update_needed = 1;
        } else {
            update_option('divi_design_notes_version', $this->version);
        }
    }
    function update_design_notes_api() {
        global $wpdb;
        include($this->plugin_path . 'inc/update.php');
    }
    function admin_menu() {
        add_menu_page('Design Notes', 'Design Notes', 'manage_options', 'design_notes', [$this, 'admin_page'], 'dashicons-excerpt-view', 6);
    }

    function admin_page() {

        include($this->plugin_path . 'admin/admin.php');
    }
    public function ajax_divi_design_notes() {


        if (empty($_REQUEST['diviDesignNotesNonce'])) {
            wp_send_json(['You don\'t have permissions to work with Notes or refresh the page "nonce expired"']);
        } elseif (
            !wp_verify_nonce(
                sanitize_text_field($_REQUEST['diviDesignNotesNonce']),
                'diviDesignNotes'
            )
            && !$this->user_can_notes
        ) {
            wp_send_json(['You don\'t have permissions to work with Notes or refresh the page "nonce expired"']);
        }
        $type =  !empty($_REQUEST['type']) ? sanitize_key($_REQUEST['type']) : false;
        $id = !empty($_REQUEST['id']) ? absint($_REQUEST['id']) : false;
        if (!$type) {
            wp_send_json(['No type in the resolve ajax']);
        }
        if ($type === 'resolve' && $id) {
            $args = [
                'ID' =>           $id,
                'post_status' => 'resolved'
            ];
            wp_send_json(['resolved' => $this->insert_design_note_post($args)]);
        }
        if ($type === 'post') {
            $args = [];
            if (!empty($_REQUEST['post_id']) && !empty($_REQUEST['content'])) {
                $args['post_id']    = absint($_REQUEST['post_id']);
                $args['href']       = !empty($_REQUEST['href']) ? esc_url_raw($_REQUEST['href']) : '';
                $args['title']      = !empty($_REQUEST['title']) ? sanitize_text_field($_REQUEST['title']) : '';
                $args['content']    = wp_kses_post(wp_unslash($_REQUEST['content']), ['span' => []], []);
                $args['parent_id']  = !empty($_REQUEST['parent_id']) ? absint($_REQUEST['parent_id']) : 0;
                $args['mensions']   = !empty($_REQUEST['mensions']) ? explode(',', sanitize_text_field($_REQUEST['mensions'])) : false;
                $args['name']       = $this->current_user->display_name;

                $new_note = $this->insert_note($args);
                $output = '';
                $success = 0;
                if ($new_note) {
                    $note = get_post($new_note);
                    $this->grab_note_author_name($note);
                    $output = $this->generate_child_html($note);
                    $success = 1;
                    $this->send_mails($args);
                }
                wp_send_json(['success' => $success, 'html' => $output]);
            }
        }
        if ($type === 'create') {
            $args = [];
            if (!empty($_REQUEST['post_id']) && !empty($_REQUEST['content'])) {
                $data = [
                    'pos'           => [
                        'x'         => !empty($_REQUEST['x']) ? (int)$_REQUEST['x'] : 0,
                        'y'         => !empty($_REQUEST['y']) ? (int)$_REQUEST['y'] : 0
                    ],
                    'el'            => !empty($_REQUEST['el']) ? sanitize_text_field($_REQUEST['el']) : false,
                ];
                $args['href']       = !empty($_REQUEST['href']) ? esc_url_raw($_REQUEST['href']) : '';
                $args['title']      = !empty($_REQUEST['title']) ? sanitize_text_field($_REQUEST['title']) : '';
                $args['post_id']    = absint($_REQUEST['post_id']);
                $args['parent_id']  = 0;
                $args['mensions']   = !empty($_REQUEST['mensions']) ?  explode(',', sanitize_text_field($_REQUEST['mensions'])) : false;
                $args['content']    = wp_kses_post(wp_unslash($_REQUEST['content']), ['span' => []], []);
                $args['data']       = maybe_serialize($data);
                $args['name']       = $this->current_user->display_name;


                $new_note = $this->insert_note($args);
                $output = '';
                $success = 0;
                if ($new_note) {
                    update_post_meta($new_note, 'divi_design_notes_meta', $args['data']);
                    $note = get_post($new_note);
                    $note->data = $args['data'];
                    $this->grab_note_author_name($note);
                    $output = $this->generate_parent_html($note);
                    $success = 1;
                    $this->send_mails($args);
                }
                wp_send_json(['success' => $success, 'html' => $output, 'debug' => maybe_unserialize($note->comment_content)]);
            }
        }
        if ($type === 'delete') {
            global $wpdb;
            if (!empty($_REQUEST['note_id']) && (int) $_REQUEST['note_id'] > 0) {
                $id = absint($_REQUEST['note_id']);
                $delete_children_query_string = "
                    DELETE FROM $wpdb->posts 
                    WHERE post_type = 'divi_design_notes'
                    AND post_parent = %d
                    ";
                $delete_parent_query_string = "
                    DELETE FROM $wpdb->posts 
                    WHERE post_type = 'divi_design_notes'
                    AND ID = %d
                    ";
                $delete_postmeta_query_string = "
                        DELETE FROM $wpdb->postmeta 
                        WHERE post_id = %d
                        ";
                // WordPress.DB.DirectDatabaseQuery.DirectQuery WordPress.DB.DirectDatabaseQuery.NoCaching
                //phpcs:ignore
                $children_deleted = $wpdb->query($wpdb->prepare($delete_children_query_string, $id));
                //phpcs:ignore
                $parent_deleted = $wpdb->query($wpdb->prepare($delete_parent_query_string, $id));
                $meta_deleted = 0;
                if ($parent_deleted || $children_deleted) {
                    //phpcs:ignore
                    $meta_deleted = $wpdb->query($wpdb->prepare($delete_postmeta_query_string, $id));
                }
                wp_send_json(['parent' => $parent_deleted, 'children' => $children_deleted, 'meta' => $meta_deleted]);
            }
            wp_send_json(['parent' => 0, 'children' => 0]);
        }
        if ($type === 'get_all') {
            $this->ajax_all_design_notes();
        }
        wp_send_json(['The type is not']);
    }
    public function ajax_all_design_notes() {

        global $wpdb;
        $select_pages_with_notes_mysql = "
        SELECT ID, post_title
            FROM $wpdb->posts
            WHERE ID IN (SELECT comment_count 
                FROM $wpdb->posts
                WHERE post_type = 'divi_design_notes'
                AND comment_count != 0)";
        // WordPress.DB.DirectDatabaseQuery.DirectQuery
        //phpcs:ignore 
        $posts = $wpdb->get_results($select_pages_with_notes_mysql);
        $select_notes_mysql = "
        SELECT ID,
        post_author,
        post_date,
        post_content,
        post_status,
        post_parent,
        comment_count AS 'page_id'
        FROM $wpdb->posts
        WHERE post_type = 'divi_design_notes'
        ORDER BY post_date ASC";
        //phpcs:ignore
        $notes = $wpdb->get_results($select_notes_mysql);
        $top_level_notes = array();
        $children_notes  = array();

        foreach ($notes as $note) {

            $this->grab_note_author_name($note);

            if ($note->post_parent > 0) {
                $children_notes[$note->post_parent][] = $note;
            } else {
                $top_level_notes[] = $note;
            }
        }

        wp_send_json([
            'home_url'  => home_url(),
            'notes'     => $top_level_notes,
            'children'  => $children_notes,
            'users'     => $this->users_can_notes,
            'pages'     => $posts
        ]);
    }
    public function custom_user_profile_fields($profileuser) {
        if (!$profileuser->has_cap('administrator')) { ?>
            <table class="form-table" style="background:#8F42ED;border-radius:3px;">
                <tr>
                    <td>
                        <h3 style="color:#fff;padding:0;"><?php esc_html_e('Divi Design Notes'); ?></h3>
                    </td>
                </tr>
                <tr style="background:#fff;font-weight:500;">
                    <td>
                        <input type="checkbox" name="divi_design_notes" id="divi_design_notes" value="1" <?php checked(get_the_author_meta('divi_design_notes', $profileuser->ID), 1); ?>" class="regular-text" />
                        <label for="divi_design_notes"><?php echo esc_html('Allow Divi Design Notes'); ?></label>
                    </td>
                </tr>
            </table>
        <?php }
    }
    public function user_meta_update($user_id) {

        if (!$this->current_user->has_cap('edit_user')) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!empty($_REQUEST['divi_design_notes'])) {
            update_user_meta($user_id, 'divi_design_notes', 1);
        } else {
            update_user_meta($user_id, 'divi_design_notes', 0);
        }
    }
    public function grab_note_author_name($note) {

        foreach ($this->users_can_notes as $user) {
            if ($user->id == $note->post_author) {
                $note->author_name = $user->display_name;
                break;
            }
        }
        if (!$note->author_name) {
            $note->author_name = 'Unassigned';
        }
    }
    public function send_mails($args) {
        $admin_email    = get_option('admin_email');
        $blog_name      = get_option('blogname');
        ob_start();
        include($this->plugin_path . 'assets/mail/mail.php');
        $template       = ob_get_clean();
        $sitename       = wp_parse_url(network_home_url(), PHP_URL_HOST);
        $from_email     = 'noreply@';

        if (null !== $sitename) {
            if ('www.' === substr($sitename, 0, 4)) {
                $sitename = substr($sitename, 4);
            }

            $from_email .= $sitename;
        }
        $headers = array('Content-Type: text/html; charset=UTF-8', "From: $blog_name <$from_email>");
        if ($args['mensions'] && is_array($args['mensions'])) {

            $to = $args['mensions'];
            $subject = 'You\'ve been mentioned in a note';
            $body = str_replace(['%%NAME%%', '%%TEXT%%', '%%TITLE%%', '%%BLOGNAME%%', '%%CONTENT%%', '%%HREF%%'], [$args['name'], 'mentioned you on', $args['title'], $blog_name, $args['content'], $args['href']], $template);

            //$headers[] = 'From: Me Myself <me@example.net>';

            wp_mail($to, $subject, $body, $headers);
        }

        $to = $admin_email;
        $subject = 'New note';
        $body = str_replace(['%%NAME%%', '%%TEXT%%', '%%TITLE%%', '%%BLOGNAME%%', '%%CONTENT%%', '%%HREF%%'], [$args['name'], 'posted a note on', $args['title'], $blog_name, $args['content'], $args['href']], $template);
        //$headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail($to, $subject, $body, $headers);
    }

    public function enqueue_scripts() {

        if ($this->user_can_notes) {
            wp_enqueue_script('divi_design_notes_js', $this->plugin_url . 'assets/js/main.js', ['jquery'], $this->version, true);
            wp_enqueue_style('divi_design_notes_css', $this->plugin_url . 'assets/css/style.min.css', [], $this->version);
        }
    }
    public function set_users() {
        global $wpdb;
        $this->users_can_notes = get_option('diviDesignNotesUsersCan', false);
        if (!$this->users_can_notes) {
            $this->users_can_notes = get_users([
                'fields' => ['ID', 'display_name', 'user_email'],
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => $wpdb->prefix . 'capabilities',
                        'value' => 'administrator',
                        'compare' => 'LIKE'
                    ],
                    [
                        'key' => 'divi_design_notes',
                        'value' => '1',
                        'compare' => '='
                    ]
                ]
            ]);
            update_option('diviDesignNotesUsersCan', $this->users_can_notes, true);
        }
    }
    public function set_user_status() {
        if ($this->current_user->has_cap('administrator')) {
            $this->user_is_admin = true;
            $this->user_can_notes = true;
        } elseif ('1' == get_user_meta($this->current_user->ID, 'divi_design_notes', true)) {
            $this->user_can_notes = true;
        }
    }

    public function admin_bar_item(WP_Admin_Bar $wp_admin_bar) {

        if (is_admin() || !$this->user_can_notes) {
            return;
        }
        $wp_admin_bar->add_menu(
            array(
                'id'     => 'design_notes',
                'parent' => null,
                'href'   => '#',
                'title'  => __('Design Notes', 'text-domain')
            )
        );
    }

    public function get_current_notes() {


        global $wpdb;
        $select_current_post_notes_mysql = "SELECT p.ID,
                p.post_author,
                p.post_date,
                p.post_content,
                p.post_status,
                p.post_parent,
                p.comment_count AS 'page_id',
                m.meta_value AS 'data'
                FROM $wpdb->posts p
                LEFT JOIN $wpdb->postmeta m ON p.ID=m.post_id AND m.meta_key='divi_design_notes_meta'
                WHERE p.post_type = 'divi_design_notes' AND p.comment_count=%d";
        // WordPress.DB.DirectDatabaseQuery.DirectQuery
        //phpcs:ignore
        return $wpdb->get_results($wpdb->prepare($select_current_post_notes_mysql, [(int) $this->post->ID]));
    }
    public function get_pages() {


        global $wpdb;
        $select_pages_title_id_with_notes_mysql = "
        SELECT ID, post_title
            FROM $wpdb->posts
            WHERE ID IN (SELECT comment_count 
                FROM $wpdb->posts
                WHERE post_type = 'divi_design_notes'
                AND comment_count != 0)";
        // WordPress.DB.DirectDatabaseQuery.DirectQuery
        //phpcs:ignore 
        $pages = $wpdb->get_results($select_pages_title_id_with_notes_mysql);
    }
    function display_notes() {


        $parent_notes = array();
        $children_notes  = array();
        foreach ($this->notes as $note) {
            $this->grab_note_author_name($note);
            if (empty($note->post_parent)) {
                $parent_notes[] = $note;
            } else {
                $children_notes[$note->post_parent][] = $note;
            }
        }
        ob_start();
        echo '<div id="design_notes_template">';
        foreach ($parent_notes as $parent_note) {
            $id = $parent_note->ID;
            //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
            $data = maybe_unserialize(unserialize($parent_note->data));
            $status = $parent_note->post_status;
            $res = ($status == 'active') ? 0 : 1;
            $res_class = $res ? ' resolved' : '';
            $dataJson = array(
                'el'    => isset($data['el']) ? $data['el'] : 0,
                'pos'   => isset($data['pos']) ? $data['pos'] : 0,
                'res'   => $res
            ); ?>

            <span id="notemarker-<?php echo esc_attr($parent_note->ID); ?>" class="design_note_marker<?php echo esc_attr($res_class); ?>" data-params=<?php echo "'", esc_attr(wp_json_encode($dataJson)), "'"; ?>>
                <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z" />
                </svg>
            </span>
            <div id="notedropdown-<?php echo esc_attr($parent_note->ID); ?>" class="design_note_dropdown<?php echo esc_attr($res_class); ?>">
                <div class="design_note_dropdown_header">
                    <span class="author">Note:</span>
                    <?php if (!$res) { ?>
                        <span data-action="resolve" class="button" title="Mark Resolved"></span>
                    <?php }
                    if ($this->user_is_admin) { ?>
                        <span data-action="delete" class="button" title="Delete note"></span>
                    <?php } ?>
                </div>
                <div class="design_note_dropdown_body">
                    <div class="design_note_dropdown_note">
                        <div class="design_note_dropdown_note_header">
                            <span class="author"><?php echo esc_html($parent_note->author_name); ?></span>
                            <span class="time"><?php echo esc_html(str_replace(' ', '&nbsp;&nbsp;&nbsp;', $parent_note->post_date)); ?></span>
                        </div>
                        <div class="design_note_dropdown_note_body">
                            <?php echo wp_kses_post($parent_note->post_content); ?>
                        </div>
                    </div>
                    <?php if (!empty($children_notes[$id])) {
                        foreach ($children_notes[$id] as $child) {
                            //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $this->generate_child_html($child);
                        }
                    } ?>
                </div>
                <div class="design_note_dropdown_note_form">
                    <textarea class="design_note_textarea" placeholder="Type your reply. Use @ to mension" id=""></textarea>
                </div>
                <div class="design_note_dropdown_footer">
                    <?php if ($parent_note->post_status == 'active') { ?>
                        <button data-action="post" class="post-note">Post note</button>
                    <?php } ?>
                    <button data-action="cancel" class="cancel">Close</button>
                </div>

            </div>
        <?php } ?>
        <span id="shadowmarker" class="design_note_marker">
            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z" />
            </svg>
        </span>
        <div id="shadowdropdown" class="design_note_dropdown">
            <div class="design_note_dropdown_header">
                <span class="author">Note:</span>
            </div>
            <div class="design_note_dropdown_note_form">
                <textarea class="design_note_textarea" placeholder="Type your reply. Use @ to mension" id=""></textarea>
            </div>
            <div class="design_note_dropdown_footer">
                <button data-action="create" class="post-note">Post note</button>
                <button data-action="cancel" class="cancel">Close</button>
            </div>
        </div>
        <div id="divi_design_notes_menu">
            <div class="design_notes_menu_header">
                <span class="title">Notes Panel</span>
                <span data-action="move" class="button" title="Move">
                </span>
                <span data-action="toggle" class="button" title="Open/Close">
                </span>
            </div>
            <div class="design_notes_menu_body">
                <p>
                    <span>Add new note</span>
                    <span data-action="new"></span>
                </p>
                <p>
                    <span>Show active notes</span>
                    <input type="checkbox" id="active_notes" checked />
                    <label for="active_notes">Toggle</label>
                </p>
                <p>
                    <span>Show resolved notes</span>
                    <input type="checkbox" id="resolved_notes" checked />
                    <label for="resolved_notes">Toggle</label>
                </p>

            </div>
        </div>
        </div>
    <?php echo ob_get_clean(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    function generate_parent_html($parent_note) {


        $id = $parent_note->ID;
        $data = maybe_unserialize($parent_note->data);
        $res = $parent_note->post_status;
        $dataJson = array(
            'el'    => isset($data['el']) ? $data['el'] : 0,
            'pos'   => isset($data['pos']) ? $data['pos'] : 0,
            'res' => $res
        );
        ob_start(); ?>
        <span id="notemarker-<?php echo esc_attr($id); ?>" class="design_note_marker" data-params=<?php echo "'", esc_attr(wp_json_encode($dataJson)), "'"; ?>>
            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z" />
            </svg>
        </span>
        <div id="notedropdown-<?php echo esc_attr($id); ?>" class="design_note_dropdown">
            <div class="design_note_dropdown_header">
                <span class="author">Note:</span>
                <?php if ($res == 'active') { ?>
                    <span data-action="resolve" class="button" title="Mark Resolved"></span>
                <?php }
                if ($this->user_is_admin) { ?>
                    <span data-action="delete" class="button" title="Delete note"></span>
                <?php } ?>
            </div>
            <div class="design_note_dropdown_body">
                <div class="design_note_dropdown_note">
                    <div class="design_note_dropdown_note_header">
                        <span class="author"><?php echo esc_html($parent_note->author_name); ?></span>
                        <span class="time"><?php echo esc_html(str_replace(' ', '&nbsp;&nbsp;&nbsp;', $parent_note->post_date)); ?></span>
                    </div>
                    <div class="design_note_dropdown_note_body">
                        <?php echo wp_kses_post($parent_note->post_content); ?>
                    </div>
                </div>
            </div>
            <div class="design_note_dropdown_note_form">
                <textarea class="design_note_textarea" placeholder="Type your reply. Use @ to mension" id=""></textarea>
            </div>
            <div class="design_note_dropdown_footer">
                <button data-action="post" class="post-note">Post note</button>
                <button data-action="cancel" class="cancel">Close</button>
            </div>
        </div>
    <?php return ob_get_clean();
    }
    function generate_child_html($child) {


        ob_start(); ?>
        <div class="design_note_dropdown_note">
            <div class="design_note_dropdown_note_header">
                <span class="author"><?php echo esc_html($child->author_name); ?></span>
                <span class="time"><?php echo esc_html(str_replace(' ', '&nbsp;&nbsp;&nbsp;', $child->post_date)); ?></span>
            </div>
            <div class="design_note_dropdown_note_body">
                <?php echo wp_kses_post($child->post_content); ?>
            </div>
        </div>
<?php return ob_get_clean();
    }
    public function delete_note($args) {


        global $wpdb;
        $params = array(
            'comment_count'  => $args['post_id'],
            'post_author'    => $this->current_user->ID,
            'post_content'   => $args['content'],
            'post_parent'    => $args['parent_id']
        );
        return $this->insert_design_note_post($params);
    }
    public function insert_note($args) {


        $params = array(
            'comment_count'  => $args['post_id'],
            'post_author'    => $this->current_user->ID,
            'post_content'   => $args['content'],
            'post_parent'    => $args['parent_id']
        );
        return $this->insert_design_note_post($params);
    }
    function insert_design_note_post($postarr) {


        global $wpdb;

        $user_id = $this->current_user->ID;

        $defaults = array(
            'post_author'           => $user_id,
            'post_content'          => '',
            'post_parent'           => 0,
            'comment_count'         => 0
        );

        $postarr = wp_parse_args($postarr, $defaults);

        unset($postarr['filter']);

        $postarr = sanitize_post($postarr, 'db');

        // Are we updating or creating?
        $post_id = 0;
        $update  = false;

        if (!empty($postarr['ID'])) {
            $update = true;

            $post_id     = $postarr['ID'];
            $post_before = get_post($post_id);

            if (is_null($post_before)) {
                return 0;
            }
        }

        if ($update) {

            if (in_array($postarr['post_status'], ['active', 'resolved'])) {
                $data = ['post_status' => $postarr['post_status']];
            } elseif (!empty($postarr['post_content'])) {
                $data = ['post_content' => $postarr['post_content']];
            }
            $where = ['ID' => $post_id];
            $data  = wp_unslash($data);
            // WordPress.DB.DirectDatabaseQuery.DirectQuery
            // phpcs:ignore 
            return $wpdb->update($wpdb->posts, $data, $where);
        }

        //Then Note is new...

        if (empty($postarr['post_content'])) {
            return 0;
        }

        //$ID                   = will automatic increment
        $post_author            = isset($postarr['post_author']) ? absint($postarr['post_author']) : $user_id;
        $post_date              = current_time('mysql');
        $post_date_gmt          = current_time('mysql', 1);
        $post_content           = $postarr['post_content'];
        $post_title             = '';
        $post_excerpt           = '';
        $post_status            = (isset($postarr['post_status']) && in_array($postarr['post_status'], ['active', 'resolved'])) ? sanitize_key($postarr['post_status']) : 'active';
        $comment_status         = 'closed';
        $ping_status            = 'closed';
        $post_password          = '';
        $post_name              = '';
        $to_ping                = '';
        $pinged                 = '';
        $post_modified          = $post_date;
        $post_modified_gmt      = $post_date_gmt;
        $post_content_filtered  = '';
        $post_parent            = absint($postarr['post_parent']);
        $guid                   = '';
        $menu_order             = 0;
        $post_type              = 'divi_design_notes';
        $post_mime_type         = '';
        $comment_count          = isset($postarr['comment_count']) ? absint($postarr['comment_count']) : 0;

        $data = compact(
            'post_author',
            'post_date',
            'post_date_gmt',
            'post_content',
            'post_content_filtered',
            'post_title',
            'post_excerpt',
            'post_status',
            'post_type',
            'comment_status',
            'ping_status',
            'post_password',
            'post_name',
            'to_ping',
            'pinged',
            'post_modified',
            'post_modified_gmt',
            'post_parent',
            'guid',
            'menu_order',
            'post_mime_type',
            'comment_count'
        );


        $data  = wp_unslash($data);
        // WordPress.DB.DirectDatabaseQuery.DirectQuery
        //phpcs:ignore
        if (false === $wpdb->insert($wpdb->posts, $data)) {
            return 0;
        }

        $post_id = (int) $wpdb->insert_id;

        return $post_id;
    }
    function notes_create_html() {


        $data = [
            'user' => [
                'ID' => $this->current_user->ID,
                'display_name' => $this->current_user->display_name
            ],
            'users'     => $this->users_can_notes,
            'post_id'   => $this->post->ID,
            // 'notes'     => $this->notes,
            'ajaxurl'   => admin_url('admin-ajax.php'),
            'nonce'     => $this->nonce,
            'title'     => $this->post->post_title,
            'href'      => $this->permalink
        ];
        ob_start();
        echo '<script id="divi_design_notes_json" type="application/json">';
        echo wp_json_encode($data);
        echo '</script>';
        //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo ob_get_clean();
    }
}
