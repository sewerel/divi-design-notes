<?php
/*
Plugin Name: Divi Design Notes
Plugin URI: https://milen.pro
Version: 1.0.0
Author: Powdistudio LTD
Author URI: https://milen.pro
*/

class Divi_Design_Notes
{

    public $version = '1.0.0';
    public $comment_type = 'divi_design_note';
    public $current_user = 0;
    public $user_can_notes = false;
    public $post_id = 0;
    public $users_can_notes = 0;
    public $notes = null;
    public $nonce = 'someString';
    public $debug = 'debug';

    function __construct(){
        $this->current_user = wp_get_current_user();
        $this->user_can_notes = $this->can_notes();

        $this->users_can_notes = get_users([
            'fields' => ['ID', 'display_name', 'user_email'],
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'wp_capabilities',
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

        //$this->debug = get_option('last_user_been_updated', 'empty');

        add_action('wp_ajax_create_note', [$this, 'create_note']);
        add_action('wp_ajax_divi_design_notes_ajax', [$this, 'ajax_divi_design_notes']);
        add_action('template_redirect', [$this, 'init']);
        add_action('edit_user_profile', [$this, 'custom_user_profile_fields'], 10, 1);
        add_action('edit_user_profile_update', [$this, 'user_meta_update']);
        //add_action('profile_update', [$this, 'last_user_been_updated'],10 ,2);
      
        //add_action('pre_get_comments', [$this, 'pre_get_comments']);

    }
    function last_user_been_updated($user_id, $old_user_data){
            update_option('last_user_been_updated', $old_user_data);
    }
    function init(){
        $this->post_id = get_queried_object_id();
        $this->notes = $this->get_notes();
        $this->nonce = wp_create_nonce('diviDesignNotes');

        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        add_action('admin_bar_menu', [$this, 'admin_bar_item'], 100);

        add_action('wp_footer', [$this, 'notes_create_html']);
        add_action('wp_footer', [$this, 'display_notes']);
    }
    public function pre_get_comments($query){

        if ($query->query_vars['type'] !== $this->comment_type) {
            $query->query_vars['type__not_in'] = array_merge(
                (array) $query->query_vars['type__not_in'],
                array($this->comment_type)
            );
        }
    }
    public function ajax_divi_design_notes(){
        if (
            !isset($_REQUEST['diviDesignNotesNonce'])
            && !wp_verify_nonce($_REQUEST['diviDesignNotesNonce'], 'diviDesignNotes')
            && !$this->user_can_notes
        ) {
            wp_send_json(['you cannot change the notes']);
        }
        $type =  !empty($_REQUEST['type']) ? $_REQUEST['type'] : false;
        $id = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : false;
        if (!$type) {
            wp_send_json(['No typ in the resolve ajax']);
        }
        if ($type === 'resolve' && $id) {
            if (!$id) {
                wp_send_json(['No id in the resolve ajax']);
            }
            $note = get_comment($id);
            $note_data = maybe_unserialize($note->comment_content);
            if (is_array($note_data)) {
                $note_data['resolved'] = '1';
                $content = maybe_serialize($note_data);
                $args = [
                    'comment_ID' => $id,
                    'comment_content' => $content
                ];
                if (wp_update_comment($args)) {
                    wp_send_json(['Comment resoved succesdfully']);
                }
                wp_send_json(['it is not array or wp_update_comment did not pass']);
            }
        }
        if ($type === 'post') {
            $args = [];
            if (!empty($_REQUEST['post_id']) && !empty($_REQUEST['content'])) {
                $args['post_id'] = $_REQUEST['post_id'];
                $args['content'] = stripslashes($_REQUEST['content']);
                $args['parent_id'] = !empty($_REQUEST['parent_id']) ? (int)$_REQUEST['parent_id'] : 0;
                $args['mensions'] = !empty($_REQUEST['mensions']) ? explode(',', $_REQUEST['mensions']) : '';
                $new_note = $this->insert_note($args);
                $output = '';
                $success = 0;
                if ($new_note) {
                    $note = get_comment($new_note);
                    $output = $this->generate_child_html($note);
                    $success = 1;
                }
                wp_send_json(['success' => $success, 'html' => $output]);
            }
        }
        if ($type === 'create') {
            $args = [];
            if (!empty($_REQUEST['post_id']) && !empty($_REQUEST['content'])) {
                $content = [
                    'res'  => 0,
                    'pos' => [
                        'x' => !empty($_REQUEST['x']) ? (int)$_REQUEST['x'] : 0,
                        'y' => !empty($_REQUEST['y']) ? (int)$_REQUEST['y'] : 0
                    ],
                    'el'   => !empty($_REQUEST['el']) ? $_REQUEST['el'] : false,
                    'text' => stripslashes($_REQUEST['content'])
                ];
                $args['post_id'] = $_REQUEST['post_id'];
                $args['parent_id'] = 0;
                $args['mensions'] = !empty($_REQUEST['mensions']) ? explode(',', $_REQUEST['mensions']) : '';
                $args['content'] = maybe_serialize($content);
                $new_note = $this->insert_note($args);
                $output = '';
                $success = 0;
                if ($new_note) {
                    $note = get_comment($new_note);
                    $output = $this->generate_parent_html($note);
                    $success = 1;
                }
                wp_send_json(['success' => $success, 'html' => $output]);
            }
        }
        if ($type === 'delete') {
            global $wpdb;
            if (!empty($_REQUEST['note_id']) && (int) $_REQUEST['note_id'] > 0) {
                $id = (int) $_REQUEST['note_id'];

                $children_deleted = $wpdb->query(
                    $wpdb->prepare(
                        "
                    DELETE FROM $wpdb->comments 
                    WHERE comment_parent = %d
                    ",
                        $id,
                    )
                );
                $parent_deleted = $wpdb->query(
                    $wpdb->prepare(
                        "
                    DELETE FROM $wpdb->comments 
                    WHERE comment_ID = %d
                    ",
                        $id,
                    )
                );
                //$children_deleted = $wpdb->delete($wpdb->comments, array('comment_parent' => $id));
                //$parent_deleted = $wpdb->delete($wpdb->comments, array('comment_ID' => $id));
                wp_send_json(['parent' => $parent_deleted, 'children' => $children_deleted]);
            }
            wp_send_json(['parent' => 0, 'children' => 0]);
        }
        wp_send_json(['The type is not']);
    }
    public function custom_user_profile_fields($profileuser){
        if (!user_can($profileuser, 'adminisrator')) { ?>
            <table class="form-table" style="background:#8F42ED;border-radius:3px;">
                <tr>
                    <td>
                        <h3 style="color:#fff;padding:0;"><?php esc_html_e('Divi Design Notes'); ?></h3>
                    </td>
                </tr>
                <tr style="background:#fff;font-weight:500;">
                    <td>
                        <input type="checkbox" name="divi_design_notes" id="divi_design_notes" value="1" <?php checked(get_the_author_meta('divi_design_notes', $profileuser->ID), 1); ?>" class="regular-text" />
                        <label for="divi_design_notes"><?php esc_html_e('Allow Divi Design Notes'); ?></label>
                    </td>
                </tr>
            </table>
        <?php }
    }
    public function user_meta_update($userId){
        if (!user_can($this->current_user, 'edit_user')) {
            return;
        }
        update_user_meta($userId, 'divi_design_notes', $_REQUEST['divi_design_notes']);
    }
    public function insert_note($args){
        $params = array(
            'comment_post_ID'   => $args['post_id'],
            'comment_type'      => $this->comment_type,
            'user_id'           => $this->current_user->ID,
            'comment_author'    => $this->current_user->user_nicename,
            'comment_approved'  => 'note',
            'comment_content'   => $args['content'],
            'comment_parent'    => $args['parent_id'],
        );
        return wp_insert_comment($params);
    }

    public function create_note(){

        global $_REQUEST;
        $content =  [
            'text' => $_REQUEST['text'],
            'el' => $_REQUEST['el'],
            'pos' => [
                'x' => $_REQUEST['x'],
                'y' => $_REQUEST['y'],
            ],
            'res' => $_REQUEST['res']
        ];

        $note_content = maybe_serialize($content);

        $args = array(
            'comment_post_ID'   => !empty($_REQUEST['post_id']) ? $_REQUEST['post_id'] : 0,
            'comment_type'      => $this->comment_type,
            'user_id'           => $this->current_user->ID,
            'comment_author'    => $this->current_user->user_nicename,
            'comment_approved'  => 'note',
            'comment_content'   => $note_content,
            'comment_parent'    => !empty($_REQUEST['parent']) ? (int) $_REQUEST['parent'] : 0,
        );
        wp_send_json(
            wp_insert_comment($args)
        );
    }

    public function enqueue_scripts(){
        if ($this->user_can_notes) {
            wp_enqueue_script('divi_design_notes_js', plugin_dir_url(__FILE__) . 'assets/js/main.js', ['jquery'], $this->version, true);
            wp_enqueue_style('divi_design_notes_css', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], $this->version);
        }
    }
    public function can_notes(){
        if (
            $this->current_user &&
            (user_can($this->current_user, 'administrator') || '1' == get_the_author_meta('divi_design_notes', $this->current_user->ID))
        ) {

            return true;
        }
        return false;
    }

    public function admin_bar_item(WP_Admin_Bar $wp_admin_bar){

        if (is_admin() || !$this->user_can_notes) {
            return;
        } // Display Menu only for wp-admin area

        $wp_admin_bar->add_menu(
            array(
                'id'     => 'design_notes',
                'parent' => null, // use 'top-secondary' for toggle menu position.
                'href'   => '#',
                'title'  => __('Design Notes', 'text-domain'),
            )
        );
    }

    public function get_notes(){
        return get_comments([
            'post_id'   => $this->post_id,
            'status'    => 'note',
            'order'     => 'ASC',
            'comment_type'     => $this->comment_type,
        ]);
    }
    function display_notes(){
        $parent_notes = array();
        $children_notes  = array();
        foreach ($this->notes as $note) {
            if (empty($note->comment_parent)) {
                $parent_notes[] = $note;
            } else {
                $children_notes[$note->comment_parent][] = $note;
            }
        }
        ob_start();
        echo '<div id="design_notes_template">';
        foreach ($parent_notes as $parent_note) {
            $id = $parent_note->comment_ID;
            $has_children = !empty($children_notes[$id]);
            $data = maybe_unserialize($parent_note->comment_content);
            $res = isset($data['res']) ? $data['res'] : '';
            $dataJson = array(
                'el'    => isset($data['el']) ? $data['el'] : 0,
                'pos'   => isset($data['pos']) ? $data['pos'] : 0,
                'res' => $res,
            ); ?>
            <span id="notemarker-<?php echo $parent_note->comment_ID; ?>" class="design_note_marker" data-params=<?php echo "'", esc_attr(json_encode($dataJson)), "'"; ?>>
                <svg xmlns="http://www.w3.org/2000/svg" version="1.0" width="100%" height="100%" viewBox="0 0 50.000000 50.000000" preserveAspectRatio="xMidYMid meet">
                    <g transform="translate(0.000000,50.000000) scale(0.100000,-0.100000)" fill="inherit" stroke="none">
                        <path d="M175 481 c-154 -70 -146 -254 20 -428 l55 -58 55 58 c102 107 148 220 126 304 -31 113 -152 172 -256 124z m147 -98 c40 -39 40 -103 -1 -144 -66 -65 -171 -20 -171 73 0 35 6 49 28 70 15 14 34 28 42 31 27 11 74 -3 102 -30z" />
                    </g>
                </svg>
            </span>
            <div id="notedropdown-<?php echo $parent_note->comment_ID; ?>" class="design_note_dropdown">
                <div class="design_note_dropdown_header">
                    <span class="author">@<?php echo $parent_note->comment_author; ?></span>
                    <?php if (!$res) { ?>
                        <span data-action="resolve" class="button" title="Mark Resolved">&#x2713;</span>
                    <?php } ?>
                    <span data-action="delete" class="button" title="Delete note">&#x1F5D1;</span>
                </div>
                <div class="design_note_dropdown_body">
                    <div class="design_note_dropdown_note">
                        <div class="design_note_dropdown_note_header">
                            <span class="author"></span>
                            <span class="time"><?php echo $parent_note->comment_date; ?></span>
                        </div>
                        <div class="design_note_dropdown_note_body">
                            <?php echo $data['text']; ?>
                        </div>
                    </div>
                    <?php if (!empty($children_notes[$id])) {
                        foreach ($children_notes[$id] as $child) {
                            echo $this->generate_child_html($child);
                        }
                    } ?>
                </div>
                <div class="design_note_dropdown_note_form">
                    <textarea class="design_note_textarea" placeholder="@mension add new comment" id=""></textarea>
                </div>
                <div class="design_note_dropdown_footer">
                    <button data-action="post" class="post">Post</button>
                    <button data-action="cancel" class="cancel">Close</button>
                </div>

            </div>
        <?php } ?>
        <span id="shadowmarker" class="design_note_marker">
            <svg xmlns="http://www.w3.org/2000/svg" version="1.0" width="100%" height="100%" viewBox="0 0 50.000000 50.000000" preserveAspectRatio="xMidYMid meet">
                <g transform="translate(0.000000,50.000000) scale(0.100000,-0.100000)" fill="inherit" stroke="none">
                    <path d="M175 481 c-154 -70 -146 -254 20 -428 l55 -58 55 58 c102 107 148 220 126 304 -31 113 -152 172 -256 124z m147 -98 c40 -39 40 -103 -1 -144 -66 -65 -171 -20 -171 73 0 35 6 49 28 70 15 14 34 28 42 31 27 11 74 -3 102 -30z" />
                </g>
            </svg>
        </span>
        <div id="shadowdropdown" class="design_note_dropdown">
            <div class="design_note_dropdown_header">
                <span class="author">@<?php echo $this->current_user->user_nicename; ?></span>
            </div>
            <div class="design_note_dropdown_note_form">
                <textarea class="design_note_textarea" placeholder="@mension add new comment" id=""></textarea>
            </div>
            <div class="design_note_dropdown_footer">
                <button data-action="create" class="post">Post</button>
                <button data-action="cancel" class="cancel">Close</button>
            </div>
        </div>
        </div>
    <?php echo ob_get_clean();
    }
    function generate_parent_html($parent_note){
        $id = $parent_note->comment_ID;
        $has_children = !empty($children_notes[$id]);
        $data = maybe_unserialize($parent_note->comment_content);
        $res = isset($data['res']) ? $data['res'] : '';
        $dataJson = array(
            'el'    => isset($data['el']) ? $data['el'] : 0,
            'pos'   => isset($data['pos']) ? $data['pos'] : 0,
            'res' => $res,
        );
        ob_start(); ?>
        <span id="notemarker-<?php echo $parent_note->comment_ID; ?>" class="design_note_marker" data-params=<?php echo "'", esc_attr(json_encode($dataJson)), "'"; ?>>
            <svg xmlns="http://www.w3.org/2000/svg" version="1.0" width="100%" height="100%" viewBox="0 0 50.000000 50.000000" preserveAspectRatio="xMidYMid meet">
                <g transform="translate(0.000000,50.000000) scale(0.100000,-0.100000)" fill="inherit" stroke="none">
                    <path d="M175 481 c-154 -70 -146 -254 20 -428 l55 -58 55 58 c102 107 148 220 126 304 -31 113 -152 172 -256 124z m147 -98 c40 -39 40 -103 -1 -144 -66 -65 -171 -20 -171 73 0 35 6 49 28 70 15 14 34 28 42 31 27 11 74 -3 102 -30z" />
                </g>
            </svg>
        </span>
        <div id="notedropdown-<?php echo $parent_note->comment_ID; ?>" class="design_note_dropdown">
            <div class="design_note_dropdown_header">
                <span class="author">@<?php echo $parent_note->comment_author; ?></span>
                <?php if (!$res) { ?>
                    <span data-action="resolve" class="button" title="Mark Resolved">&#x2713;</span>
                <?php } ?>
                <span data-action="delete" class="button" title="Delete note">&#x1F5D1;</span>
            </div>
            <div class="design_note_dropdown_body">
                <div class="design_note_dropdown_note">
                    <div class="design_note_dropdown_note_header">
                        <span class="author"></span>
                        <span class="time"><?php echo $parent_note->comment_date; ?></span>
                    </div><!-- note header 5 -->
                    <div class="design_note_dropdown_note_body">
                        <?php echo $data['text']; ?>
                    </div>
                </div>
            </div>
            <div class="design_note_dropdown_note_form">
                <textarea class="design_note_textarea" placeholder="@mension add new comment" id=""></textarea>
            </div><!-- form 7 -->
            <div class="design_note_dropdown_footer">
                <button data-action="post" class="post">Post</button>
                <button data-action="cancel" class="cancel">Close</button>
            </div>
        </div>
    <?php return ob_get_clean();
    }
    function generate_child_html($child){
        ob_start(); ?>
        <div class="design_note_dropdown_note">
            <div class="design_note_dropdown_note_header">
                <span class="author">@<?php echo $child->comment_author; ?></span>
                <span class="time"><?php echo $child->comment_date; ?></span>
            </div>
            <div class="design_note_dropdown_note_body">
                <?php echo $child->comment_content; ?>
            </div>
        </div>
        <?php return ob_get_clean();
    }
    function notes_create_html(){
        $data = [
            'user' => [
                'ID' => $this->current_user->ID,
                'display_name' => $this->current_user->user_nicename
            ],
            'users' => $this->users_can_notes,
            'post_id' => $this->post_id,
            'notes' => $this->notes,
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => $this->nonce,
            'debug' => $this->debug
        ];
        ob_start();
        echo '<script id="divi_design_notes_json" type="application/json">';
        echo json_encode($data);
        echo '</script>';

        echo ob_get_clean();
    }
}
add_action('init', function () {
    global $_REQUEST;
    if (isset($_REQUEST['et_fb'])) {
        return;
    }
    new Divi_Design_Notes();
});
