import DesignNotesMain from './designNotesMain.js'
import DesignNote from './designNote.js'
import Marker from './marker'
import {on, select} from './helpers.js'
window.addEventListener('load', function(){

    window.diviDesignNotesAPI = DesignNotesMain();
    window.diviDesignNotesAPI.init();

});
