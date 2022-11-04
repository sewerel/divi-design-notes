import { on } from "./helpers";


export default function DropDown(marker, element){
    const self = {
        element: element,
        marker: marker,
        active: false,
        textArea: null,
        ajaxing: false,
        mensions: [],
        openClose(){
            if(self.active){
                self.close()
            }else{
                self.open()
            }
        },
        open(){
            diviDesignNotesAPI.closeDropdowns();
            self.element.classList.add('open');
            self.active = true;
            self.setPosition()
        },
        close(){
            self.element.classList.remove('open');
            self.active = false;
        },
        checkMensions:function(string){
            self.mensions = [];
            diviDesignNotesAPI.data.users.forEach(user=>{
                if(string.includes(`@${user.display_name}`)){
                    if(!self.mensions.indexOf(user.user_email)+1){
                        self.mensions.push(user.user_email)
                    }
                }
            })
            return self.mensions;
        },
        setPosition(){
            if(!self.active) return;
            const rects = self.marker.element.getBoundingClientRect();
            self.element.style.top = `${rects.bottom}px`;
            self.element.style.left = `${rects.x}px`;
            return self;
        },
        clicked(e){
            if(!e.target.dataset.action || self.ajaxing) return;
            self.ajaxing = true;
            if(e.target.dataset.action === 'cancel'){
                self.close();
                self.ajaxing = false;
                return;
            }
            if(e.target.dataset.action === 'resolve'){
                self.resolve();
                return;
            }
            if(e.target.dataset.action === 'post'){
                console.log('posting')
                self.post();
                return;
            }
            if(e.target.dataset.action === 'delete'){
                self.close();
                return;
            }
        },
        resolve(){
            self.ajaxing = true;
            const data = new FormData();
            data.append('type', 'resolve')
            data.append('id', self.marker.id)
            diviDesignNotesAPI.ajax(data)
            .then(res=>{if(res.ok)return res.json()})
            .then(json => {
                console.log(json)
                self.ajaxing =  false;
            })
            self.element.querySelector('[data-action=resolve]').remove()
            
        },
        post(){
            if(!self.textArea.value.trim()){
                self.textArea.value = '';
                return;
            }
            self.ajaxing = true;
            const data = new FormData();
            if(self.checkMensions(self.textArea.value)){
                data.append('mensions', self.mensions.join(','))
            }
            data.append('type', 'post')
            data.append('parent_id', self.marker.id)
            data.append('content', self.textArea.value.trim())
            data.append('post_id', diviDesignNotesAPI.data.post_id)
            diviDesignNotesAPI.ajax(data)
            .then(res=>{if(res.ok)return res.json()})
            .then(obj => {
                console.log(obj)
                if(obj.success){
                    const body = self.element.querySelector('.design_note_dropdown_body')
                    body.insertAdjacentHTML('beforeend',obj.html)
                    self.textArea.value = '';
                }
                self.ajaxing =  false;
            })
        },
        init(){
            self.textArea = self.element.querySelector('textarea');
            document.body.appendChild(self.element);
            on('click', self.clicked, self.element);
        }

        
    };
    self.init();
    
    return self;


}