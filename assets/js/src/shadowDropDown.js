import { on } from "./helpers";


export default function shadowDropDown(marker, element){
    const self = {
        element: element,
        marker: marker,
        active: false,
        textArea: null,
        ajaxing: false,
        mensions: [],
        openClose:()=>{
            if(self.active){
                self.close()
            }else{
                self.open()
            }
        },
        open: () =>{
            diviDesignNotesAPI.closeDropdowns();
            self.element.classList.add('open');
            self.active = true;
            self.setPosition()
        },
        close: () =>{
            self.element.classList.remove('open');
            self.active = false;
        },
        checkMensions:(string)=>{
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
        setPosition: ()=>{
            if(!self.active) return;
            const rects = self.marker.element.getBoundingClientRect();
            self.element.style.top = `${rects.bottom}px`;
            self.element.style.left = `${rects.x}px`;
        },
        clear(){
            self.textArea.value = '';
        },
        clicked: (e)=>{
            if(!e.target.dataset.action || self.ajaxing) return;
            self.ajaxing = true;
            if(e.target.dataset.action === 'cancel'){
                self.clear();
                self.marker.reset();
                self.ajaxing = false;
            }
            if(e.target.dataset.action === 'create'){
                self.create();
                return;
            }
        },
        create:()=>{
            if(!self.textArea.value.trim()){
                self.textArea.value = '';
                self.ajaxing = false;
                return;
            }
            const data = new FormData();
            if(self.checkMensions(self.textArea.value)){
                data.append('mensions', self.mensions.join(','))
            }
            data.append('type', 'create')
            data.append('x',self.marker.position.x)
            data.append('y',self.marker.position.y)
            data.append('el', self.marker.getElSelector())
            data.append('content', self.textArea.value.trim())
            data.append('post_id', diviDesignNotesAPI.data.post_id)
            diviDesignNotesAPI.ajax(data)
            .then(res=>{if(res.ok)return res.json()})
            .then(obj => {
                if(obj.success){
                    self.clear();
                    self.marker.reset();
                    diviDesignNotesAPI.createNote(obj.html)
                }
                self.ajaxing = false;
            })
            
        },
        init: ()=>{
            self.textArea = self.element.querySelector('textarea');
            document.body.appendChild(self.element);
            on('click', self.clicked, self.element);
        }

        
    };
    self.init();
    
    return self;

}