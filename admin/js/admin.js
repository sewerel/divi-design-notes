(function () {
    let data;
    let filter = {};
    //Variables
    let isAjaxing = false;
    let page = 1;
    let pages = 0;
    let perPage = 10;

    //HTMLElemenst
    const {
        admin_url,
        nonce,
        update
    } = divi_design_notes_php_data();
    if (update) {
        return;
    }
    const container = document.querySelector('#design-notes-container');
    const screen = document.querySelector('#design-notes-screen');
    const table = container.querySelector('#design-notes-table');
    const pageFilter = container.querySelector('#filter_page');
    const authorFilter = container.querySelector('#filter_author');
    const statusFilter = container.querySelector('#filter_status');
    const preview = container.querySelector('#design-notes-preview');
    const previewBody = preview.querySelector('.preview-body');
    const tableBody = table.querySelector('tbody');
    const pagination = table.querySelector('#note_pagination');

    //Listeners
    window.addEventListener('resize', screenSizing);
    table.addEventListener('change', tableChanged);
    tableBody.addEventListener('click', bodyTableClicked);
    preview.addEventListener('click', previewClicked);
    //Init
    screenSizing();
    fetchData(render);

    //Methods
    function tableChanged(e) {
        const name = e.target.name;
        const value = e.target.value;
        if (name && value) {
            switch (name) {
                case 'page':
                    filter.page_id = value;
                    page = 1;
                    break;
                case 'author':
                    filter.post_author = value;
                    page = 1;
                    break;
                case 'status':
                    filter.post_status = value;
                    page = 1;
                    break;
                case 'per_page':
                    perPage = parseInt(value);
                    page = 1;
                    break;
                case 'pagination':
                    if (parseInt(value) !== NaN && parseInt(value) > 0) {
                        page = Math.max(Math.min(parseInt(value), parseInt(pages)), 1);
                    } else {
                        e.target.value = page;
                        return;
                    }
                    break;
            }
            render();
        }
    }

    function bodyTableClicked(ev) {
        const target = ev.target;
        if (target.dataset.action) {
            if (target.dataset.action == 'view') {
                let obj = data.notes.find(note => note.ID == target.dataset.id);
                if (!obj) {
                    return;
                }
                const text = previwTmp(obj)
                previewBody.insertAdjacentHTML('beforeend', text);
                if (data.children[obj.ID]) {
                    for (const child of data.children[obj.ID]) {
                        const text = previwChildTmp(child)
                        previewBody.insertAdjacentHTML('beforeend', text);
                    }
                }
                screen.dataset.screen = 'view';
                screen.dataset.note = obj.ID;
                screen.dataset.status = obj.post_status;
            } else if (target.dataset.action == 'delete') {
                target.classList.add('ajaxing');
                deleteNote(target.dataset.id).then(result => {
                    if (result.parent) {
                        const tr = target.closest('tr');
                        const animation = tr.animate([{
                            opacity: 1,
                            transform: 'scaleX(1)'
                        },
                        {
                            opacity: 0,
                            transform: 'scaleX(1)'
                        },
                        {
                            opacity: 0,
                            transform: 'scaleX(0)'
                        }

                        ], {
                            duration: 1000
                        });
                        animation.onfinish = () => {
                            tr.remove()
                        };
                        data.notes = data.notes.filter(note => note.ID != target.dataset.id);
                    } else {
                        window.location.reload();
                    }
                })
            }
        }
    }

    function previewClicked(ev) {
        if (ev.target.dataset.action) {
            const action = ev.target.dataset.action;
            switch (action) {
                case 'table':
                    previewBody.innerHTML = '';
                    screen.dataset.screen = 'table';
                    break;
                case 'delete':
                    const button = ev.target;
                    const id = button.dataset.id;
                    const parent_id = screen.dataset.note;
                    button.classList.add('ajaxing');
                    deleteNote(id).then(result => {
                        if (result.parent) {
                            button.classList.remove('ajaxing');
                            const child = button.closest('.preview-note');
                            const animation = child.animate([{
                                opacity: 1,
                                transform: 'scaleX(1)'
                            },
                            {
                                opacity: 0,
                                transform: 'scaleX(1)'
                            },
                            {
                                opacity: 0,
                                transform: 'scaleX(0)'
                            }

                            ], {
                                duration: 1000
                            });
                            animation.onfinish = () => {
                                child.remove()
                            };
                            data.children[parent_id] = data.children[parent_id].filter(note => note.ID != id);

                        } else {
                            button.classList.remove('ajaxing');
                            location.reload();
                        }
                    });
                    break;
                case 'delete_parent':
                    preview.firstElementChild.classList.add('ajaxing');
                    deleteNote(screen.dataset.note).then(result => {
                        if (result.parent) {
                            preview.firstElementChild.classList.remove('ajaxing');
                            screen.dataset.screen = 'table';
                            data.notes = data.notes.filter(note => note.ID != screen.dataset.note);
                            displayContent(applyFilters());
                        } else {
                            preview.firstElementChild.classList.remove('ajaxing');
                            location.reload();
                        }
                    });
                    break;
                case 'resolve':
                    preview.firstElementChild.classList.add('ajaxing');
                    resolveNote().then(result => {
                        if (result.resolved && result.resolved != false) {
                            screen.dataset.status = 'resolved';
                            preview.firstElementChild.classList.remove('ajaxing');
                            data.notes.forEach(note => {
                                if (note.ID == screen.dataset.note) {
                                    note.post_status = 'resolved';
                                }
                            });

                            displayContent(applyFilters())
                        } else {
                            preview.firstElementChild.classList.remove('ajaxing');
                            location.reload();
                        }
                    });

                    break;
            }
        }
    }

    function deleteNote(id) {
        const formData = new FormData();
        formData.append('action', 'divi_design_notes_ajax');
        formData.append('type', 'delete');
        formData.append('note_id', parseInt(id));
        formData.append('diviDesignNotesNonce', nonce);

        return fetch(admin_url, {
            method: 'POST',
            body: formData
        }).then(res => res.json());
    }

    function resolveNote() {
        const formData = new FormData();
        formData.append('action', 'divi_design_notes_ajax');
        formData.append('type', 'resolve');
        formData.append('id', parseInt(screen.dataset.note));
        formData.append('diviDesignNotesNonce', nonce);

        return fetch(admin_url, {
            method: 'POST',
            body: formData
        }).then(res => res.json());
    }

    function parentTmp(obj, i = 0) {
        return `<tr style=" animation-delay:${i * 100}ms;">
                        <td>${linkToPostTmp(obj)}</td>
                        <td>${obj.author_name}</td>
                        <td>${obj.post_date}</td>
                        <td>${obj.post_status}</td>
                        <td>
                        <button data-id="${obj.ID}" data-action="view" class="view">View</button>
                        <button data-id="${obj.ID}" data-action="delete" class="delete">Delete</button>
                        </td>
                        <td>${data.children[obj.ID] ? data.children[obj.ID].length : '-'}</td>
                        </tr>`;
    }

    function previwTmp(obj, i) {
        return `<div class="preview-note">
                        <div class="preview-note-header">
                            <span class="author">${obj.author_name}</span>
                            <span class="time">${obj.post_date}</span>
                        </div>
                        <div class="preview-note-body">
                            <p class="note-content">${obj.post_content}</p>
                        </div>
                        <div class="preview-note-footer">
                        </div>
                    </div>`;
    }

    function previwChildTmp(obj, i) {
        return `<div class="preview-note">
                        <div class="preview-note-header">
                            <span class="author">${obj.author_name}</span>
                            <span class="time">${obj.post_date}</span>
                        </div>
                        <div class="preview-note-body">
                            <p class="note-content">${obj.post_content}</p>
                        </div>
                        <div class="preview-note-footer">
                            <p class="actions">
                                <button data-id="${obj.ID}" data-action="delete" class="delete">Delete</button>
                            </p>
                        </div>
                    </div>`;
    }

    function linkToPostTmp(obj) {
        if (obj.page != 'Unassigned') {
            return `<a target=_blank href="${data.home_url}?p=${obj.page_id}">${obj.page}</a>`;
        }
        return 'Unassigned';
    }

    function screenSizing() {
        screen.style.maxHeight = `${window.innerHeight - (screen.getBoundingClientRect().top + 30)}px`;
    }

    function fetchData(func = null) {
        const formData = new FormData();
        formData.append('action', 'divi_design_notes_ajax');
        formData.append('type', 'get_all');
        formData.append('diviDesignNotesNonce', nonce);

        const ajaxed = fetch(admin_url, {
            method: 'POST',
            body: formData
        }).then(res => res.json());
        const content = ajaxed.then(json => {
            data = json;
            setData(data)
            if (func) {
                func()
            }
        });
    }

    function setData(data) {

        //notes
        if (data.notes && data.notes.length) {
            for (const note of data.notes) {
                note.page = 'Unassigned';
                if (note.page_id != 0) {
                    for (const page of data.pages) {
                        if (note.page_id == page.ID) {
                            note.page = page.post_title;
                            break;
                        }
                    }
                }
            }
            data.notes.sort((a, b) => {
                return new Date(b.post_date) - new Date(a.post_date)
            });
        }
        //pages
        if (pageFilter && data.pages && data.pages.length) {
            let hasPageFilter = (filter.page_id) ? filter.page_id : 0;
            let hasPageMatch = 0;
            while (pageFilter.firstChild) {
                pageFilter.firstChild.remove();
            }
            pageFilter.insertAdjacentHTML('beforeend', `<option value="all">Posts</option>`);
            for (const page of data.pages) {
                if (page.ID == hasPageFilter) {
                    hasPageMatch = 1;
                }
                const text = `<option value="${page.ID}">${page.post_title}</option>`;
                pageFilter.insertAdjacentHTML('beforeend', text);
            }
            if (!hasPageMatch) {
                pageFilter.value = 'all';
                filter.page_id = 'all';
            }
        }
        //authors
        if (authorFilter && data.users && data.users.length) {
            let hasUserFilter = (filter.post_author) ? filter.post_author : 0;
            let hasUserMatch = 0;
            while (authorFilter.firstChild) {
                authorFilter.firstChild.remove();
            }
            authorFilter.insertAdjacentHTML('beforeend', `<option value="all">Author</option>`);
            for (const user of data.users) {
                if (user.ID == hasUserFilter) {
                    hasUserMatch = 1;
                }
                const text = `<option value="${user.ID}">${user.display_name}</option>`;
                authorFilter.insertAdjacentHTML('beforeend', text);
            }
            if (!hasUserMatch) {
                authorFilter.value = 'all';
                filter.post_author = 'all';
            }
        }
    }

    function applyFilters() {
        let filtered = data.notes;
        for (prop in filter) {
            if (filter[prop] !== 'all') {
                filtered = filtered.filter(note => note[prop] == filter[prop]);
            }

        }
        pages = Math.ceil(filtered.length / perPage);
        if (pages > 1) {
            filtered = filtered.slice((page * perPage - perPage), (page * perPage));
        } else {
            page = 1;
        }
        return filtered;
    }

    function displayContent(notes) {
        if (notes) {
            while (tableBody.firstChild) {
                tableBody.firstChild.remove();
            }
            if (notes.length) {
                notes.forEach(
                    (item, i) => {
                        const text = parentTmp(item, i);
                        tableBody.insertAdjacentHTML('beforeend', text)
                    }

                );
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan=6>No Design notes maching current criteria...</td>
                    </tr>`;
            }

            if (pages) {
                pagination.innerHTML = `
                    Page:
                    <input size="3" name="pagination" type="number" step="1" min="1" max="${pages}" value="${page}"/>
                    of ${pages}
                    `;
            } else {
                pagination.innerHTML = '';
            }
        }
    }

    function render() {
        const filteredNotes = applyFilters();
        displayContent(filteredNotes);
    }
}());