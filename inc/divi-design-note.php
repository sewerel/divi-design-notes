<?php
function notes_create_html(){
    $data = [
        'users' => $this->users_can_notes,
        'post_id' => $this->post_id,
        'notes' => $this->notes
    ];
    $dataJson = array(
        'el'    =>".et_pb_row.et_pb_row_0",
        'pos'   =>['x'=>150,'y'=>45]
    );
    ob_start();
    echo '<script id="divi_design_notes_json" type="application/json">';
    echo json_encode($data);
    echo '</script>'; 
    ?>
    
    <div id="design_notes_template" style="display:none;">
        <span id="notemarker-14" class="design_note_marker absolute open" data-params=<?php echo "'",json_encode($dataJson),"'"; ?>>
        <svg xmlns="http://www.w3.org/2000/svg" version="1.0" width="100%" height="100%" viewBox="0 0 50.000000 50.000000" preserveAspectRatio="xMidYMid meet">

        <g transform="translate(0.000000,50.000000) scale(0.100000,-0.100000)" fill="inherit" stroke="none">
        <path d="M175 481 c-154 -70 -146 -254 20 -428 l55 -58 55 58 c102 107 148 220 126 304 -31 113 -152 172 -256 124z m147 -98 c40 -39 40 -103 -1 -144 -66 -65 -171 -20 -171 73 0 35 6 49 28 70 15 14 34 28 42 31 27 11 74 -3 102 -30z"/>
        </g>
        </svg>
        </span>
        <div id="dropdown-14" class="design_note_dropdown">
            <div class="design_note_dropdown_header">
                <span class="author">ClientX</span>
                <span data-action="resove" class="button" title="Mark Resoved">&#x2713;</span>    
                <span data-action="delete" class="button" title="Delete note">&#x1F5D1;</span>
            </div>
            <div class="design_note_dropdown_body">
                <div class="design_note_dropdown_note">
                    <div class="design_note_dropdown_note_header">
                            <span class="author"></span>
                            <span class="time">2022-10-19 13:51:47</span>
                    </div>
                    <div class="design_note_dropdown_note_body">
                            @admin Can we change the header to be bold instead of tyni
                    </div>
                </div>
                <div class="design_note_dropdown_note">
                    <div class="design_note_dropdown_note_header">
                            <span class="author">@admin</span>
                            <span class="time">2022-10-19 13:51:47</span>
                    </div>
                    <div class="design_note_dropdown_note_body">
                        @ClientX Yeah sure why not.
                        How does it loook now
                    </div>
                </div>
                <div class="design_note_dropdown_note">
                    <div class="design_note_dropdown_note_header">
                            <span class="author">@ClientX</span>
                            <span class="time">2022-10-19 13:51:47</span>
                    </div>
                    <div class="design_note_dropdown_note_body">
                            @admin yes match better
                    </div>
                </div>
            </div>
            <div class="design_note_dropdown_note_form">
                <textarea class="design_note_textarea" placeholder="@mension add new comment" id=""></textarea>
            </div>
            <div class="design_note_dropdown_footer">
                <button data-action="create" class="post">Post</button>
                <button data-action="cancel" class="cancel">Cancel</button>
            </div>

        </div>
</div>
    <?php
    echo ob_get_clean();
}