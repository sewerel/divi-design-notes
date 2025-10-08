<?php
// Design notes admin page
?>
<!-- html -->
<div>
    <h1>Design Notes</h1>
</div>
<div id="design-notes-container">
    <div id="design-notes-screen" data-screen='table'>
        <div id="design-notes-table">
            <table>
                <thead>
                    <tr>
                        <th>
                            <select name="page" id="filter_page">
                                <option value="all" selected>Posts</option>
                            </select>
                        <th>
                            <select name="author" id="filter_author">
                                <option value="all" selected>Author</option>
                            </select>
                        </th>
                        <th>Date</th>
                        <th>
                            <select name="status" id="filter_status">
                                <option value="all" selected>All</option>
                                <option value="resolved">Resolved</option>
                                <option value="active">Active</option>
                                <option value="trashed">Trashed</option>
                            </select>
                        </th>
                        <th>Action</th>
                        <th><span class="dashicons dashicons-admin-comments"></span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan=6>Loading Design notes...</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td id="note_pagination" colspan="4" style="text-align:center">
                        </td>
                        <td>Per page:
                            <select name="per_page" id="per_page">
                                <option value="10" selected>10</option>
                                <option value="20">20</option>
                                <option value="30">30</option>
                                <option value="40">40</option>
                            </select>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div id="design-notes-preview">
            <div class="preview-container">
                <div class="preview-header">
                    <span style="flex-grow:1">
                        <button data-action="table">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M16 14a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2zm-4.5-6.5H5.707l2.147-2.146a.5.5 0 1 0-.708-.708l-3 3a.5.5 0 0 0 0 .708l3 3a.5.5 0 0 0 .708-.708L5.707 8.5H11.5a.5.5 0 0 0 0-1" />
                            </svg>
                        </button>
                    </span>
                    <button title="Resolve!" data-action="resolve">
                        <svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='#fff' viewBox='0 0 16 16'>
                            <path d='M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm10.03 4.97a.75.75 0 0 1 .011 1.05l-3.992 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.75.75 0 0 1 1.08-.022z' />
                        </svg>
                    </button>
                    <button title="Delete!" data-action="delete_parent">
                        <svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='#fff' viewBox='0 0 16 16'>
                            <path d='M11 1.5v1h3.5a.5.5 0 0 1 0 1h-.538l-.853 10.66A2 2 0 0 1 11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038 3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5Zm-5 0v1h4v-1a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5ZM4.5 5.029l.5 8.5a.5.5 0 1 0 .998-.06l-.5-8.5a.5.5 0 1 0-.998.06Zm6.53-.528a.5.5 0 0 0-.528.47l-.5 8.5a.5.5 0 0 0 .998.058l.5-8.5a.5.5 0 0 0-.47-.528ZM8 4.5a.5.5 0 0 0-.5.5v8.5a.5.5 0 0 0 1 0V5a.5.5 0 0 0-.5-.5Z' />
                        </svg>
                    </button>
                </div>
                <div class="preview-body">

                </div>
            </div>
        </div>
    </div>
</div>
<!-- javascript -->
<script>
    function divi_design_notes_php_data() {
        return {
            admin_url: '<?php echo esc_attr(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo esc_attr($this->nonce); ?>',
            update: <?php echo wp_json_encode($this->update_needed); ?>
        }
    }
</script>