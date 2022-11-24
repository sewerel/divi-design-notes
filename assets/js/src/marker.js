import DropDown from "./dropDown";
import { on, select } from "./helpers";

export default function Marker( element ){
    const self = {
        element: element,
        id: element.id.split('-')[1],
        data: JSON.parse(element.dataset.params),
        relatedElement: null,
        relativeToElement: false,
        dropDown: null,
        position: {
            x:0,
            y:0
        },
        getRelativity () {
            self.relativeToElement = ['relative','absolute','fixed'].includes(getComputedStyle(self.relatedElement).position);
            return self.relativeToElement;
        },
        setRelPosition(){
            self.element.style.position = 'absolute';
            self.element.style.top = `${(self.position.y/self.relatedElement.offsetHeight)*100}%`;
            self.element.style.left = `${(self.position.x/self.relatedElement.offsetWidth)*100}%`;
            self.element.style.transform = `translate(-20px,-40px)`;
        },
        setFixPosition(){
            const rects = self.relatedElement.getBoundingClientRect();
            self.element.style.transform = `translate3d(${self.position.x + rects.x}px,${self.position.y+rects.y}px,0)`;
        },
        setPosition(){
            if(self.relativeToElement) return;
            self.setFixPosition();
        },
        openClose(e){
            self.dropDown.openClose(self.dropDown)
        },
        init() {
            try{
                self.relatedElement = select(self.data.el);  
            }catch{}
            if(!self.relatedElement){
                self.relatedElement = select('#page-container');
            }
            self.position = self.data.pos;
            if(self.getRelativity()){
                self.setRelPosition();
                self.relatedElement.appendChild(self.element)
            }else{
                self.element.style.position = 'fixed';
                self.setFixPosition();
                document.body.appendChild(self.element)
            }
            self.dropDown = DropDown(self, select(`#notedropdown-${self.id}`));
            on('click', self.openClose, self.element );
            return self;

        },


    };
    
    return self;
}