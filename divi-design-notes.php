<?php
/*
Plugin Name:        Divi Design Notes
Plugin URI:         https://divi-design-notes.powdithemes.com
Version:            1.0.0
Author:             Powdistudio LTD
Author URI:         https://powdisudio.com
License:            GPL v2 or later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html
*/

class Divi_Design_Notes
{

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
    public $nonce = '';
    public $plugin_url = null;

    function __construct(){

        $this->plugin_url = plugin_dir_url( __FILE__ );
        $this->current_user = wp_get_current_user();

        if($this->current_user->ID){
            $this->set_user_status();
        }
        if($this->user_can_notes){
           add_filter( 'show_admin_bar', '__return_true', 100, 0);
           add_action('template_redirect', [$this, 'init']);
        }
        $this->set_users();

        //add_action('wp_ajax_create_note', [$this, 'create_note']);
        add_action('wp_ajax_divi_design_notes_ajax', [$this, 'ajax_divi_design_notes']);
        add_action('edit_user_profile', [$this, 'custom_user_profile_fields'], 10, 1);
        add_action('edit_user_profile_update', [$this, 'user_meta_update']);

        
        add_action( 'edit_user_created_user', [$this,'wipe_out_user_cache'],10,1);
        add_action( 'profile_update', [$this, 'wipe_out_user_cache'],10 ,1);
        add_action( 'deleted_user', [$this,'wipe_out_user_cache'],10,1);
        
      
    }
    function wipe_out_user_cache($user_id){
            delete_option('diviDesignNotesUsersCan');
    }
    function init(){
        $this->post = get_post();
        if($this->post){
            $this->post_id = $this->post->ID;
            $this->permalink = get_permalink($this->post); 
            $this->notes = $this->get_notes();
            $this->nonce = wp_create_nonce('diviDesignNotes');

            add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

            add_action('admin_bar_menu', [$this, 'admin_bar_item'], 100);

            add_action('wp_footer', [$this, 'notes_create_html']);
            add_action('wp_footer', [$this, 'display_notes']);
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
                $note_data['res'] = 1;
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
                $args['title'] = !empty($_REQUEST['title']) ? $_REQUEST['title']: "";
                $args['href'] = !empty($_REQUEST['href']) ? $_REQUEST['href']: "";
                $args['content'] = stripslashes($_REQUEST['content']);
                $args['parent_id'] = !empty($_REQUEST['parent_id']) ? (int)$_REQUEST['parent_id'] : 0;
                $args['mensions'] = !empty($_REQUEST['mensions']) ? explode(',',$_REQUEST['mensions']) : false;
                $args['name'] = $this->current_user->display_name;

                $new_note = $this->insert_note($args);
                $output = '';
                $success = 0;
                if ($new_note) {
                    $note = get_comment($new_note);
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
                $args['title'] = !empty($_REQUEST['title']) ? $_REQUEST['title']: "";
                $args['href'] = !empty($_REQUEST['href']) ? $_REQUEST['href']: "";
                $args['parent_id'] = 0;
                $args['mensions'] = !empty($_REQUEST['mensions']) ? json_decode( $_REQUEST['mensions'] ) : 0;
                $args['content'] = maybe_serialize($content);
                $args['name'] = $this->current_user->display_name;

                
                $new_note = $this->insert_note($args);
                $output = '';
                $success = 0;
                if ($new_note) {
                    $note = get_comment($new_note);
                    $output = $this->generate_parent_html($note);
                    $success = 1;
                    $this->send_mails($args);
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
                wp_send_json(['parent' => $parent_deleted, 'children' => $children_deleted]);
            }
            wp_send_json(['parent' => 0, 'children' => 0]);
        }
        wp_send_json(['The type is not']);
    }
    public function custom_user_profile_fields($profileuser){
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
    public function user_meta_update($user_id){
        if (!$this->current_user->has_cap('edit_user')) {
            return;
        }
        update_user_meta($user_id, 'divi_design_notes', $_REQUEST['divi_design_notes']);
    }
    public function send_mails($args){
        
        $admin_email = get_option('admin_email');
        $blog_name = get_option( 'blogname' );
        ob_start();
        include(plugin_dir_path( __FILE__ ).'assets/mail/mail.php');
        $template = ob_get_clean();
        $sitename   = wp_parse_url( network_home_url(), PHP_URL_HOST );
		$from_email = 'noreply@';

		if ( null !== $sitename ) {
			if ( 'www.' === substr( $sitename, 0, 4 ) ) {
				$sitename = substr( $sitename, 4 );
			}

			$from_email .= $sitename;
		}
        $headers = array('Content-Type: text/html; charset=UTF-8',"From: $blog_name <$from_email>");
        if($args['mensions'] && is_array($args['mensions'])){

                $to = $args['mensions'];
                $subject = 'You\'ve been mentioned in a note';
                $body = str_replace(['%%NAME%%','%%TEXT%%','%%TITLE%%','%%BLOGNAME%%','%%CONTENT%%','%%HREF%%'],[ $args['name'], 'mentioned you on', $args['title'], $blog_name, $args['content'], $args['href']],$template);
                
                //$headers[] = 'From: Me Myself <me@example.net>';

            wp_mail( $to, $subject, $body, $headers );
        }

        $to = $admin_email;
        $subject = 'New note';
        $body = str_replace(['%%NAME%%','%%TEXT%%','%%TITLE%%','%%BLOGNAME%%','%%CONTENT%%','%%HREF%%'],[ $args['name'], 'posted a note on', $args['title'], $blog_name, $args['content'], $args['href']], $template);
        //$headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail( $to, $subject, $body, $headers );
        

    }

    public function enqueue_scripts(){
        if ($this->user_can_notes) {
            wp_enqueue_script('divi_design_notes_js', $this->plugin_url . 'assets/js/main.js', ['jquery'], $this->version, true);
            wp_enqueue_style('divi_design_notes_css', $this->plugin_url . 'assets/css/style.min.css', [], $this->version);
        }
    }
    public function set_users(){
        global $wpdb;
        $this->users_can_notes = get_option('diviDesignNotesUsersCan',false);
        if(!$this->users_can_notes){
            $this->users_can_notes = get_users([
                'fields' => ['ID', 'display_name', 'user_email'],
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => $wpdb->prefix.'capabilities',
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
            update_option('diviDesignNotesUsersCan', $this->users_can_notes, true );
        }
    }
    public function set_user_status(){
        if ( $this->current_user->has_cap('administrator') ){
            $this->user_is_admin = true;
            $this->user_can_notes = true;
        }elseif('1' == get_user_meta($this->current_user->ID,'divi_design_notes',true )){
            $this->user_can_notes = true;
        }
    }

    public function admin_bar_item(WP_Admin_Bar $wp_admin_bar){

        if (is_admin() || !$this->user_can_notes) {
            return;
        }
        $wp_admin_bar->add_menu(
            array(
                'id'     => 'design_notes',
                'parent' => null, 
                'href'   => '#',
                'title'  => __('Design Notes', 'text-domain')
                ),
            );
    }

    public function get_notes(){
        return get_comments([
            'post_id'           => $this->post_id,
            'status'            => 'note',
            'order'             => 'ASC',
            'comment_type'      => $this->comment_type,
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
            <span id="notemarker-<?php echo esc_attr($parent_note->comment_ID); ?>" class="design_note_marker<?php echo $res ? ' resolved' :''; ?>" data-params=<?php echo "'", esc_attr(json_encode($dataJson)), "'"; ?>>
                <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                </svg>
            </span>
            <div id="notedropdown-<?php echo $parent_note->comment_ID; ?>" class="design_note_dropdown<?php echo $res ? ' resolved' :''; ?>">
                <div class="design_note_dropdown_header">
                    <span class="author">Note:</span>
                    <?php if (!$res) { ?>
                        <span data-action="resolve" class="button" title="Mark Resolved"></span>
                    <?php } if ($this->user_is_admin) { ?>    
                        <span data-action="delete" class="button" title="Delete note"></span>
                    <?php } ?>
                </div>
                <div class="design_note_dropdown_body">
                    <div class="design_note_dropdown_note">
                        <div class="design_note_dropdown_note_header">
                            <span class="author"><?php echo esc_html($parent_note->comment_author); ?></span>
                            <span class="time"><?php echo str_replace(' ','&nbsp;&nbsp;&nbsp;',$parent_note->comment_date); ?></span>
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
                    <textarea class="design_note_textarea" placeholder="Type your reply. Use @ to mension" id=""></textarea>
                </div>
                <div class="design_note_dropdown_footer">
                    <?php if(!$res){ ?>
                    <button data-action="post" class="post-note">Post note</button>
                    <?php } ?>
                    <button data-action="cancel" class="cancel">Close</button>
                </div>

            </div>
        <?php } ?>
        <span id="shadowmarker" class="design_note_marker">
            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
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
                    <input type="checkbox" id="resolved_notes" checked/>
                    <label for="resolved_notes">Toggle</label>
                </p>

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
        <span id="notemarker-<?php echo esc_attr($parent_note->comment_ID); ?>" class="design_note_marker" data-params=<?php echo "'", esc_attr(json_encode($dataJson)), "'"; ?>>
            <svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
            </svg>
        </span>
        <div id="notedropdown-<?php echo esc_attr($parent_note->comment_ID); ?>" class="design_note_dropdown">
            <div class="design_note_dropdown_header">
                <span class="author">Note:</span>
                <?php if (!$res) { ?>
                    <span data-action="resolve" class="button" title="Mark Resolved"></span>
                <?php } if ($this->user_is_admin) { ?>    
                        <span data-action="delete" class="button" title="Delete note"></span>
                <?php } ?>
            </div>
            <div class="design_note_dropdown_body">
                <div class="design_note_dropdown_note">
                    <div class="design_note_dropdown_note_header">
                        <span class="author"><?php echo $parent_note->comment_author; ?></span>
                        <span class="time"><?php echo str_replace(' ','&nbsp;&nbsp;&nbsp;',$parent_note->comment_date); ?></span>
                    </div>
                    <div class="design_note_dropdown_note_body">
                        <?php echo $data['text']; ?>
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
    function generate_child_html($child){
        ob_start(); ?>
        <div class="design_note_dropdown_note">
            <div class="design_note_dropdown_note_header">
                <span class="author"><?php echo esc_html($child->comment_author); ?></span>
                <span class="time"><?php echo str_replace(' ','&nbsp;&nbsp;&nbsp;',$child->comment_date); ?></span>
            </div>
            <div class="design_note_dropdown_note_body">
                <?php echo $child->comment_content; ?>
            </div>
        </div>
        <?php return ob_get_clean();
    }
    public function insert_note($args){
        $params = array(
            'comment_post_ID'   => $args['post_id'],
            'comment_type'      => $this->comment_type,
            'user_id'           => $this->current_user->ID,
            'comment_author'    => $args['name'],
            'comment_approved'  => 'note',
            'comment_content'   => $args['content'],
            'comment_parent'    => $args['parent_id'],
        );
        return wp_insert_comment($params);
    }
    function notes_create_html(){
        $data = [
            'user' => [
                'ID' => $this->current_user->ID,
                'display_name' => $this->current_user->display_name
            ],
            'users'     => $this->users_can_notes,
            'post_id'   => $this->post->ID,
            'notes'     => $this->notes,
            'ajaxurl'   => admin_url('admin-ajax.php'),
            'nonce'     => $this->nonce,
            'title'     => $this->post->post_title,
            'href'      => $this->permalink
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
    if ( isset($_REQUEST['et_fb']) || ( 'Divi' !== get_option('template') ) ) {
        return;
    }
    new Divi_Design_Notes();
});
