import { select, on, off } from "./helpers";

export default function Menu(pageContainer){
    const self = {
        pageContainer: pageContainer,
        element: select('#divi_design_notes_menu'),
        moveButton:null,
        toggleButton:null,
        move(e){
            // const x = self.element.style.left || 100;
            // const y = self.element.style.top || 100;

            self.element.style.transform = `translate3d(${e.clientX}px,${e.clientY}px,0)`;

            // self.element.style.left = `${parseInt(x)+e.movementX}px`;
            // self.element.style.top = `${parseInt(y)+e.movementY}px`;
        },
        stopMove(e){
            off('mousemove', self.move);
            off('mouseup', self.stopMove);
            off('mouseleave',self.stopMove,self.element);
            if(e.type === 'mouseleave'){
                self.element.style.transform = '';
            }
        },
        clicked(e){
            if(!e.target.dataset.action){return}
            const action = e.target.dataset.action;
            if(action === 'toggle'){
                self.element.classList.toggle('open')
            }
            if(action === 'new'){
                window[Symbol.for('diviDesignNotesAPI')].buttonClicked(e)
            }
            
        },
        input(e){
            if(e.target.id === 'resolved_notes'){
                if(e.target.checked){
                    document.body.classList.remove('hide-resolved');
                }else{
                    document.body.classList.add('hide-resolved');
                }
            }
            if(e.target.id === 'active_notes'){
                if(e.target.checked){
                    document.body.classList.remove('hide-active');
                }else{
                    document.body.classList.add('hide-active');
                }
            }
        },
        init(){
            self.moveButton = select('span[data-action="move"]');

            self.addButton = select('span[data-action="toggle"]');
            on('mousedown',()=>{
                on('mousemove',self.move);
                on('mouseup',self.stopMove);
                on('mouseleave',self.stopMove,self.element);
            },self.moveButton);

            on('click',self.clicked,self.element)
            on('input',self.input,self.element)
        }
    };
    return self;
}