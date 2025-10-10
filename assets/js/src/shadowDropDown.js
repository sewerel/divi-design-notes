import { message, on } from "./helpers";


export default function shadowDropDown(marker, element) {
    const self = {
        element: element,
        marker: marker,
        active: false,
        textArea: null,
        is_ajaxing: false,
        mensions: [],
        openClose: () => {
            if (self.active) {
                self.close()
            } else {
                self.open()
            }
        },
        open: () => {
            window[Symbol.for('diviDesignNotesAPI')].closeDropdowns();
            self.element.classList.add('open');
            self.active = true;
            self.setPosition()
        },
        close: () => {
            self.element.classList.remove('open');
            self.active = false;
        },
        checkMensions(string) {
            self.mensions = [];
            let stringMensions = string;
            window[Symbol.for('diviDesignNotesAPI')].data.users.forEach(user => {
                if (string.includes(`@${user.display_name}`)) {
                    stringMensions = stringMensions.replace(`@${user.display_name}`, `<span>@${user.display_name}</span>`);
                    if (!self.mensions.indexOf(user.user_email) + 1) {
                        self.mensions.push(user.user_email);
                    }
                }
            })
            return stringMensions;
        },
        setPosition() {
            if (!self.active) return;
            const rects = self.marker.element.getBoundingClientRect();
            const fromLeft = rects.x < 175 ? 1 : 0;
            const fromRight = ((innerWidth - rects.right) < 175) ? 3 : 0;
            const fromBottom = ((innerHeight - rects.bottom) < 200) ? 5 : 0;
            let translate = '';

            if (fromBottom || fromRight || fromLeft) {
                switch (fromBottom + fromLeft + fromRight) {
                    case 1: //Left
                    case 4: //Left & Right
                        translate = `translateX(${-rects.x}px)`
                        break;
                    case 3: //Right
                        translate = `translateX(-${350 - (innerWidth - rects.right)}px)`
                        break;
                    case 5: //Bottom
                        translate = `translate(-50%,-100%) translate(15px,-40px)`
                        break;
                    case 6: //Left & Bottom
                        translate = `translate(${-rects.x}px,-100%) translateY(-40px)`
                        break;
                    case 8: //Right & Bottom
                        translate = `translate(-${350 - (innerWidth - rects.right)}px,-100%) translateY(-40px)`
                        break;
                    case 9: //Left & Right & Bottom
                        translate = `translate(0,-100%) translateY(-40px)`
                        break;
                }
            }
            self.element.style.top = `${rects.bottom}px`;
            self.element.style.left = `${rects.x}px`;
            self.element.style.transform = translate;
            if (!fromBottom) {
                self.element.style.maxHeight = `${innerHeight - rects.bottom}px`;
            } else {
                self.element.style.maxHeight = '';
            }
        },
        clear() {
            self.textArea.value = '';
        },
        clicked: (e) => {
            if (!e.target.dataset.action || self.is_ajaxing) return;
            self.ajaxing(true);
            if (e.target.dataset.action === 'cancel') {
                self.clear();
                self.marker.reset();
                self.ajaxing(false);
            }
            if (e.target.dataset.action === 'create') {
                self.create();
                return;
            }
        },
        create: () => {
            if (!self.textArea.value.trim()) {
                self.textArea.value = '';
                self.ajaxing(false);
                return;
            }
            const data = new FormData();
            const content = self.checkMensions(self.textArea.value);
            if (self.mensions.length) {
                data.append('mensions', self.mensions.join(','))
            }
            data.append('type', 'create')
            data.append('x', self.marker.position.x)
            data.append('y', self.marker.position.y)
            data.append('el', self.marker.getElSelector())
            data.append('content', content.trim())
            data.append('post_id', window[Symbol.for('diviDesignNotesAPI')].data.post_id)
            data.append('href', window[Symbol.for('diviDesignNotesAPI')].data.href)
            data.append('title', window[Symbol.for('diviDesignNotesAPI')].data.title)
            window[Symbol.for('diviDesignNotesAPI')].ajax(data)
                .then(res => { if (res.ok) return res.json() })
                .then(obj => {
                    if (obj.success) {
                        self.clear();
                        self.marker.reset();
                        window[Symbol.for('diviDesignNotesAPI')].createNote(obj.html)
                        message('New note has been created.');
                    }
                    self.ajaxing(false);
                }).catch(err => {
                    message(`Something went wrong try refreshing the page.`);
                    self.ajaxing(false)
                });

        },
        ajaxing(flag = null) {
            if (flag === null) {
                return self.is_ajaxing;
            }
            if (flag) {
                self.element.classList.add('ajaxing');
                self.is_ajaxing = true;
            }
            if (!flag) {
                self.element.classList.remove('ajaxing');
                self.is_ajaxing = false;
            }
        },
        init() {
            self.textArea = self.element.querySelector('textarea');
            document.body.appendChild(self.element);
            on('click', self.clicked, self.element);
        }


    };
    self.init();

    return self;

}