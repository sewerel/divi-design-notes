import { select, on , off, createElement, renderElement, VanillaCaret } from "./helpers";

export default function DesignNote(relatedElement, coor){
    
    const self = {
        relatedElement : (typeof relatedElement !== 'string')? relatedElement : select(relatedElement),
        relativeToElement : false,
        position : {
            x: coor.x,
            y: coor.y,
            top: coor.top?coor.top:0,
            left: coor.left?coor.left:0,
        },
        marker : '',
        dropDown : '',
        calculatePosition(){
            if(self.relativeToElement){
              return;  
            }
            const rects = self.relatedElement.getBoundingClientRect();
            self.position.top = scrollY + rects.top;
            self.position.left = scrollX + rects.left;
        },
        setPosition(){
            if(self.relativeToElement){
                self.marker.style.transform = `translate3d(${self.position.x}px,${self.position.y}px,0)`;
                return;
            }
            self.marker.style.transform = `translate3d(${self.position.x + self.position.left}px,${self.position.y+ self.position.top}px,0)`;
        },
        showHideDropDown : () => {
            self.marker.classList.add('open');
        },
        reset : (relatedElement, coor) =>{
            self.relatedElement = relatedElement;
            self.position = {
                x: coor.x,
                y: coor.y,
                top: coor.top?coor.top:0,
                left: coor.left?coor.left:0,
            };
            self.calculatePosition();
            if(self.getRelativity()){
                self.relatedElement.appendChild(self.marker);
            }else{
                document.body.appendChild(self.marker);
            }
            self.textArea.textContent = '';
            return self;
        },
        getRelativity : () => {
            self.relativeToElement = ['relative','absolute','fixed'].includes(getComputedStyle(self.relatedElement).position);
            return self.relativeToElement;
        },

    }
    self.getRelativity();
    self.marker = (()=>{
        let marker = createElement('span',{class:"design_note_marker absolute"});
        if(self.relativeToElement){
            marker = renderElement(marker,self.relatedElement);
        }else{
            marker = renderElement(marker,document.body);
        }
        return marker;
    })();
    self.textArea = createElement('span',{class:'design_note_textarea',attributes:[{name:'contenteditable',value:true}]});
    self.dropDown = createElement('',{class:'design_note_dropdown'});
    self.dropDown = renderElement(self.dropDown, self.marker);
    self.textArea = renderElement(self.textArea, self.dropDown);
    on('click',self.showHideDropDown, self.marker);
       
    return self;
}