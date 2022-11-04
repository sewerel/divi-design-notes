import DropDown from "./dropDown";
import { on, select } from "./helpers";

export default function Marker( element ){
    const self = {
        element: element,
        id: element.id.split('-')[1],
        data: JSON.parse(element.dataset.params),
        relatedElement: 'null',
        relativeToElement: false,
        dropDown: null,
        position: {
            x:0,
            y:0
        },
        getRelativity : function() {
            self.relativeToElement = ['relative','absolute','fixed'].includes(getComputedStyle(self.relatedElement).position);
            return self.relativeToElement;
        },
        setRelPosition: function(){
            self.element.style.position = 'absolute';
            self.element.style.top = `${(self.position.y/self.relatedElement.offsetHeight)*100}%`;
            self.element.style.left = `${(self.position.x/self.relatedElement.offsetWidth)*100}%`;
            self.element.style.transform = `translate(-15px,-30px)`;
        },
        setFixPosition: function(){
            const rects = self.relatedElement.getBoundingClientRect();
            self.element.style.transform = `translate3d(${self.position.x + rects.x}px,${self.position.y+rects.y}px,0)`;
        },
        setPosition: function(){
            if(self.relativeToElement) return;
            self.setFixPosition();
        },
        openClose: function(e){
            self.dropDown.openClose(self.dropDown)
        },
        init: function() {
            self.relatedElement = self.data.el ? select(self.data.el) : select('#page-container');
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