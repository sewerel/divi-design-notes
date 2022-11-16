import DesignNotesMain from './designNotesMain.js'
import DesignNote from './designNote.js'
import Marker from './marker'
import {on, select} from './helpers.js'
window.addEventListener('load', function(){

    const button = select('#wp-admin-bar-design_notes');
    on('click', function(){
        document.body.classList.toggle('show_design_notes');
        window[Symbol.for('diviDesignNotesAPI')].init();
    },button)

    window[Symbol.for('diviDesignNotesAPI')] = DesignNotesMain();

});
