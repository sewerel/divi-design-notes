import shadowDropDown from "./shadowDropDown";
import { on, select } from "./helpers";

export default function shadowMarker( element ){
    const self = {
        element: element,
        relatedElement: null,
        relativeToElement: false,
        dropDown: null,
        active: false,
        position: {
            x:0,
            y:0
        },
        getElSelector(){
            if(self.relatedElement.id){
                return '#' + self.relatedElement.id;
            }
            if(self.relatedElement.classList){
                return '.' + Array.from(self.relatedElement.classList).join('.');
            }
            return '#page-container';
        },
        pinOnPage( relatedElement,position ) {
            self.position = position;
            self.relatedElement = relatedElement;
            self.active = true;
            self.element.classList.remove('active')
            if(self.getRelativity()){
                self.setRelPosition();
            }else{
                self.setFixPosition();
            }
            self.openClose();
        },
        setPosition(){
            if(!self.active) return;
            if(self.relativeToElement) return;  
            self.setFixPosition();

        },
        getRelativity(){
            self.relativeToElement = ['relative','absolute','fixed'].includes(getComputedStyle(self.relatedElement).position);
            return self.relativeToElement;
        },
        setRelPosition(){
            self.relatedElement.appendChild(self.element)
            self.element.style.position = 'absolute';
            self.element.style.top = `${(self.position.y/self.relatedElement.offsetHeight)*100}%`;
            self.element.style.left = `${(self.position.x/self.relatedElement.offsetWidth)*100}%`;
            self.element.style.transform = `translate(-20px,-40px)`;
            // self.element.style.position = 'absolute';
            // self.element.style.transform = `translate3d(${self.position.x}px,${self.position.y}px,0)`;
        },
        setFixPosition(){
            const rects = self.relatedElement.getBoundingClientRect();
            self.element.style.transform = `translate3d(${self.position.x + rects.x}px,${self.position.y+rects.y}px,0)`;
        },
        openClose(){
            self.dropDown.openClose()
        },
        activate(element){
            self.element.classList.add('active');
            element.appendChild(self.element)
        },
        reset(){
            self.relatedElement = null;
            self.active = false;
            self.dropDown.close();
            self.element.style = '';
            self.element.remove();
        },
        init(){
            self.dropDown = shadowDropDown(self, select('#shadowdropdown'));
            on('click', self.openClose, self.element);
            return self;
        },


    };
    
    return self;
}