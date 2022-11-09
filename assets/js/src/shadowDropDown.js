import { on } from "./helpers";


export default function shadowDropDown(marker, element){
    const self = {
        element: element,
        marker: marker,
        active: false,
        textArea: null,
        is_ajaxing: false,
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
            const fromLeft = rects.x < 175 ? 1 : 0;
            const fromRight = ((innerWidth - rects.right) < 175) ? 3 : 0;
            const fromBottom = ((innerHeight - rects.bottom) < 200) ? 5 : 0;
            let translate = '(-50%, 0, 0)';

            if(fromBottom || fromRight || fromLeft){
                switch(fromBottom + fromLeft + fromRight) {
                    case 1: //Left
                        translate = `(-${rects.x}px,0,0)`
                        break;
                    case 3: //Right
                        translate = `(-${350-(innerWidth - rects.right)}px,0,0)`
                        break;
                    case 4: //Left & Right
                        translate = `(0,0,0)`
                        break;
                    case 5: //Bottom
                        translate = `(-50%,-100%,0) translateY(-30px)`
                        break;
                    case 6: //Left & Bottom
                        translate = `(-${rects.x}px,-100%,0) translateY(-30px)`
                        break;
                    case 8: //Right & Bottom
                        translate = `(-${350-(innerWidth - rects.right)}px,-100%,0) translateY(-30px)`
                        break;
                    case 9: //Left & Right & Bottom
                        translate = `(0,-100%,0) translateY(-30px)`
                        break;
                    }
            }
            
            self.element.style.top = `${rects.bottom}px`;
            self.element.style.left = `${rects.x}px`;
            self.element.style.transform = `translate3d${translate}`;
        },
        clear(){
            self.textArea.value = '';
        },
        clicked: (e)=>{
            if(!e.target.dataset.action || self.is_ajaxing) return;
            self.ajaxing(true);
            if(e.target.dataset.action === 'cancel'){
                self.clear();
                self.marker.reset();
                self.ajaxing(false);
            }
            if(e.target.dataset.action === 'create'){
                self.create();
                return;
            }
        },
        create:()=>{
            if(!self.textArea.value.trim()){
                self.textArea.value = '';
                self.ajaxing(false);
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
                self.ajaxing(false);
            })
            
        },
        ajaxing(flag = null){
            if(flag === null){
                return self.is_ajaxing;
            }
            if(flag){
                self.element.classList.add('ajaxing');
                self.is_ajaxing = true;
            }
            if(!flag){
                self.element.classList.remove('ajaxing');
                self.is_ajaxing = false;
            }
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