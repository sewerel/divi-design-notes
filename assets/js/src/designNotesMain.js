import {select, on, off, renderElement} from './helpers.js';
import Marker from './marker';
import shadowMarker from './shadowMarker';
import Menu from './menu';

export default function DesignNotesMain(button, dataElement){
    const self = {
        button : select('#wp-admin-bar-design_notes'),
        pageContainer: select('#page-container'),
        templatesContainer: select('#design_notes_template'),

        data : JSON.parse(select('#divi_design_notes_json').textContent),
        notes: [],
        menu: null,
        hoveredElement : false,
        user : '',
        markerActive : false,
        shadowMarker : null,
        timeout : 0,
        currenMatch : null,
        inputTarget : null,
        caret : null,
        usersNode : document.createElement('ul'),
        buttonClicked (e) {
            e.preventDefault();
            if(self.markerActive){
                self.stopMarker();
            }else{
                self.startMarker();
            }
            return self;

        },
        startMarker() {
            self.closeDropdowns();
            self.shadowMarker.reset();
            self.shadowMarker.activate(self.pageContainer);
            on('mousemove', self.followMarker, self.pageContainer );
            self.markerActive = true;
            document.body.classList.add('marker-active');
            on('click', self.markerClicked, self.pageContainer);
        },
        stopMarker() {
            off('mousemove', self.followMarker, self.pageContainer );
            self.shadowMarker.element.remove();
            self.markerActive = false;
            document.body.classList.remove('marker-active');
            off('click', self.markerClicked, self.pageContainer);
            if(self.hoveredElement) self.hoveredElement.style.outline = '';

        },
        followMarker(e) {
            let module = e.target.matches('.et_pb_module') ? e.target : e.target.closest('.et_pb_module,.et_pb_row,.et_pb_section,header,#page-container');
            if(module){
                if(module !== self.hoveredElement){
                    if(self.hoveredElement){
                        self.hoveredElement.style.outline = '';
                    }
                    self.hoveredElement = module;
                    self.hoveredElement.style.outline = '3px solid blue';
                }
            }else{
                if(self.hoveredElement){
                    self.hoveredElement.style.outline = '';
                    self.hoveredElement = false;
                }
            }

            self.shadowMarker.element.style.transform = `translate3d(${e.clientX}px,${e.clientY}px,0)`;
        },
        markerClicked (e) {
            e.preventDefault();
            off('mousemove', self.followMarker, self.pageContainer );
            off('click', self.markerClicked, self.pageContainer);
            const rects = self.hoveredElement.getBoundingClientRect();
            const position = {
                x: e.x -  rects.x,
                y: e.y -  rects.y,
            }
            self.shadowMarker.pinOnPage(self.hoveredElement,position);
            self.markerActive = false;
            document.body.classList.remove('marker-active');
            if(self.hoveredElement) self.hoveredElement.style.outline = '';
        },
        createNote(html){
            const node = renderElement(html, self.templatesContainer,'afterbegin');
            const newNote = Marker(node).init()
            self.notes.push(newNote);
            newNote.openClose()
        },
        inputHandler(e){
            if(e.target.matches('.design_note_textarea')){
                const input = self.inputTarget = e.target;
                const textToCaret = input.value.substring(0, input.selectionStart);
                const match = textToCaret.match(/@(?<text>[a-z]{0,3})$/i);
                if(match){
                    self.currenMatch = match;
                    input.parentElement.appendChild(self.usersNode);
                    if(match.groups.text){
                        const usersMatched = self.data.users.filter(user => user.display_name.toLowerCase().includes(match.groups.text.toLowerCase()))
                        self.usersNode.innerHTML = self.usersListItems(usersMatched)
                    }else{
                        self.usersNode.innerHTML = self.usersListItems(self.data.users)
                    }
                }else{
                    self.usersNode.remove();
                }
            }
        },
        closeDropdowns(){
            self.notes.forEach(marker=>{
                marker.dropDown.close();
            });
            self.shadowMarker.dropDown.close();
        },
        chooseUser(e) {
            const user = e.target;
            const input = self.inputTarget;
            const rejex = new RegExp(`${self.currenMatch[0]}$`,'g');
            input.value = input.value.replace(rejex, `@${user.dataset.userName} `);
            input.focus();
            input.setSelectionRange(input.value.length + 1, input.value.length + 1);
            self.usersNode.remove();
        },
        setPositions(){
            self.notes.forEach((item) => {
                item.setPosition();
                item.dropDown.setPosition();
            });
            self.shadowMarker.setPosition();
            self.shadowMarker.dropDown.setPosition();
        },
        maybeSetTimeout(e){
            if(self.timeout){
                clearTimeout(self.timeout);
            }
            self.timeout = setTimeout(()=>{
                self.setPositions();
                self.timeout = 0;
            }, 100);
        },
        ajax(data){
            data.append('action', 'divi_design_notes_ajax')
            data.append('diviDesignNotesNonce', self.data.nonce)
            return fetch(self.data.ajaxurl,{
                method: 'POST',
                body: data
            })
        },
        setMarkers(){
            const selectedMarkers = select('[id^=notemarker]', self.templatesContainer, true);
            selectedMarkers.forEach(node => {
                self.notes.push(Marker(node).init());
                return;
            });
        },
        delete($marker){
            $marker.dropDown.element.remove();
            $marker.element.remove();
            self.notes.splice(self.notes.indexOf($marker),1);
        },
        usersListItems(list){
            return html = list.map(user => {
                return `<li data-user-name="${user.display_name}">${user.display_name}<span>${user.user_email}</span></li>`;
            }).join('');
        },
        init(){
            if( self.markerActive ){
                self.stopMarker()
            }
            if(self.menu){return;}
            //Menu
            self.menu = Menu(self.pageContainer);
            self.menu.init();
            //Users related
            self.usersNode.id = 'design_notes_user_list';
            self.usersNode.innerHTML = self.usersListItems(self.data.users)
            // self.data.users.forEach(item =>{
            //     const li = document.createElement('li');
            //     li.dataset.userId = item.id;
            //     li.dataset.userName = item.display_name;
            //     li.textContent = item.display_name;
            //     self.usersNode.appendChild(li);
            // });
            //Set Markers
            const selectedMarkers = select('[id^=notemarker]', self.templatesContainer, true);
            selectedMarkers.forEach(node => {
                self.notes.push(Marker(node).init());
                return;
            });
            //ShadowMarker
            const elem =  select('#shadowmarker', self.templatesContainer);
            self.shadowMarker = shadowMarker(elem).init();

            on('click', self.chooseUser, self.usersNode);
            //on('click', self.buttonClicked, self.button);
            on('scroll', self.maybeSetTimeout);
            on('resize', self.maybeSetTimeout, window);
            on('input', self.inputHandler);
            on('transitionend', self.maybeSetTimeout, self.pageContainer);

        },
        
    };
   

    return self;
}