export function on(event, callback, element = document){
    return element.addEventListener(event,callback);
}
export function off(event, callback, element = document){
    return element.removeEventListener(event,callback);
}
export function select(selector,element = document,all = false){
    return (all ? element.querySelectorAll(selector) : element.querySelector(selector));
}
export function createElement(tag,args = {},content){
    const e = { 
        tag : tag || 'div',
        class : args.class || '',
        content : content || '',
        attributes : '',
    }
    if(args.attributes){
        let attributes = '';
        args.attributes.forEach(item=>{
            attributes += ` ${item.name}="${item.value}"`;
        });
        e.attributes = attributes;
    }

    if(e.class){
        e.class = ` class="${e.class}"`; 
    }
    
    return `<${e.tag}${e.class}${e.attributes}>${e.content}</${e.tag}>`;
}
export function renderElement(element, parent, position = 'beforeend'){
    let property = 'lastElementChild';
    switch (position) {
        case 'beforebegin':
            property = 'previousElementSibling';
            break;
        case 'afterbegin':
            property = 'firstElementChild';
            break;
        case 'beforebegin':
            property = 'previousElementSibling';
            break;
        default:
            break;
    }
    parent.insertAdjacentHTML(position, element);
    return parent[property];
}



  

