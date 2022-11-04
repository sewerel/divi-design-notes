function $20b4a97a61b3fccb$export$af631764ddc44097(event, callback, element = document) {
    return element.addEventListener(event, callback);
}
function $20b4a97a61b3fccb$export$8c8705df4a2dcec9(event, callback, element = document) {
    return element.removeEventListener(event, callback);
}
function $20b4a97a61b3fccb$export$2e6c959c16ff56b8(selector, element = document, all = false) {
    return all ? element.querySelectorAll(selector) : element.querySelector(selector);
}
function $20b4a97a61b3fccb$export$c8a8987d4410bf2d(tag, args = {}, content) {
    const e = {
        tag: tag || "div",
        class: args.class || "",
        content: content || "",
        attributes: ""
    };
    if (args.attributes) {
        let attributes = "";
        args.attributes.forEach((item)=>{
            attributes += ` ${item.name}="${item.value}"`;
        });
        e.attributes = attributes;
    }
    if (e.class) e.class = ` class="${e.class}"`;
    return `<${e.tag}${e.class}${e.attributes}>${e.content}</${e.tag}>`;
}
function $20b4a97a61b3fccb$export$d544df0d2baa9f2c(element, parent, position = "beforeend") {
    let property = "lastElementChild";
    switch(position){
        case "beforebegin":
            property = "previousElementSibling";
            break;
        case "afterbegin":
            property = "firstElementChild";
            break;
        case "beforebegin":
            property = "previousElementSibling";
            break;
        default:
            break;
    }
    parent.insertAdjacentHTML(position, element);
    return parent[property];
}



function $04cb2e1132857924$export$2e2bcd8739ae039(marker, element) {
    const self = {
        element: element,
        marker: marker,
        active: false,
        textArea: null,
        ajaxing: false,
        mensions: [],
        openClose () {
            if (self.active) self.close();
            else self.open();
        },
        open () {
            diviDesignNotesAPI.closeDropdowns();
            self.element.classList.add("open");
            self.active = true;
            self.setPosition();
        },
        close () {
            self.element.classList.remove("open");
            self.active = false;
        },
        checkMensions: function(string) {
            self.mensions = [];
            diviDesignNotesAPI.data.users.forEach((user)=>{
                if (string.includes(`@${user.display_name}`)) {
                    if (!self.mensions.indexOf(user.user_email) + 1) self.mensions.push(user.user_email);
                }
            });
            return self.mensions;
        },
        setPosition () {
            if (!self.active) return;
            const rects = self.marker.element.getBoundingClientRect();
            self.element.style.top = `${rects.bottom}px`;
            self.element.style.left = `${rects.x}px`;
            return self;
        },
        clicked (e) {
            if (!e.target.dataset.action || self.ajaxing) return;
            self.ajaxing = true;
            if (e.target.dataset.action === "cancel") {
                self.close();
                self.ajaxing = false;
                return;
            }
            if (e.target.dataset.action === "resolve") {
                self.resolve();
                return;
            }
            if (e.target.dataset.action === "post") {
                console.log("posting");
                self.post();
                return;
            }
            if (e.target.dataset.action === "delete") {
                self.close();
                return;
            }
        },
        resolve () {
            self.ajaxing = true;
            const data = new FormData();
            data.append("type", "resolve");
            data.append("id", self.marker.id);
            diviDesignNotesAPI.ajax(data).then((res)=>{
                if (res.ok) return res.json();
            }).then((json)=>{
                console.log(json);
                self.ajaxing = false;
            });
            self.element.querySelector("[data-action=resolve]").remove();
        },
        post () {
            if (!self.textArea.value.trim()) {
                self.textArea.value = "";
                return;
            }
            self.ajaxing = true;
            const data = new FormData();
            if (self.checkMensions(self.textArea.value)) data.append("mensions", self.mensions.join(","));
            data.append("type", "post");
            data.append("parent_id", self.marker.id);
            data.append("content", self.textArea.value.trim());
            data.append("post_id", diviDesignNotesAPI.data.post_id);
            diviDesignNotesAPI.ajax(data).then((res)=>{
                if (res.ok) return res.json();
            }).then((obj)=>{
                console.log(obj);
                if (obj.success) {
                    const body = self.element.querySelector(".design_note_dropdown_body");
                    body.insertAdjacentHTML("beforeend", obj.html);
                    self.textArea.value = "";
                }
                self.ajaxing = false;
            });
        },
        init () {
            self.textArea = self.element.querySelector("textarea");
            document.body.appendChild(self.element);
            (0, $20b4a97a61b3fccb$export$af631764ddc44097)("click", self.clicked, self.element);
        }
    };
    self.init();
    return self;
}



function $f264c4e0c2cd7c0b$export$2e2bcd8739ae039(element) {
    const self = {
        element: element,
        id: element.id.split("-")[1],
        data: JSON.parse(element.dataset.params),
        relatedElement: "null",
        relativeToElement: false,
        dropDown: null,
        position: {
            x: 0,
            y: 0
        },
        getRelativity: function() {
            self.relativeToElement = [
                "relative",
                "absolute",
                "fixed"
            ].includes(getComputedStyle(self.relatedElement).position);
            return self.relativeToElement;
        },
        setRelPosition: function() {
            self.element.style.position = "absolute";
            self.element.style.top = `${self.position.y / self.relatedElement.offsetHeight * 100}%`;
            self.element.style.left = `${self.position.x / self.relatedElement.offsetWidth * 100}%`;
            self.element.style.transform = `translate(-15px,-30px)`;
        },
        setFixPosition: function() {
            const rects = self.relatedElement.getBoundingClientRect();
            self.element.style.transform = `translate3d(${self.position.x + rects.x}px,${self.position.y + rects.y}px,0)`;
        },
        setPosition: function() {
            if (self.relativeToElement) return;
            self.setFixPosition();
        },
        openClose: function(e) {
            self.dropDown.openClose(self.dropDown);
        },
        init: function() {
            self.relatedElement = self.data.el ? (0, $20b4a97a61b3fccb$export$2e6c959c16ff56b8)(self.data.el) : (0, $20b4a97a61b3fccb$export$2e6c959c16ff56b8)("#page-container");
            self.position = self.data.pos;
            if (self.getRelativity()) {
                self.setRelPosition();
                self.relatedElement.appendChild(self.element);
            } else {
                self.element.style.position = "fixed";
                self.setFixPosition();
                document.body.appendChild(self.element);
            }
            self.dropDown = (0, $04cb2e1132857924$export$2e2bcd8739ae039)(self, (0, $20b4a97a61b3fccb$export$2e6c959c16ff56b8)(`#notedropdown-${self.id}`));
            (0, $20b4a97a61b3fccb$export$af631764ddc44097)("click", self.openClose, self.element);
            return self;
        }
    };
    return self;
}



function $446359f2ef3c174b$export$2e2bcd8739ae039(marker, element) {
    const self = {
        element: element,
        marker: marker,
        active: false,
        textArea: null,
        ajaxing: false,
        mensions: [],
        openClose: ()=>{
            if (self.active) self.close();
            else self.open();
        },
        open: ()=>{
            diviDesignNotesAPI.closeDropdowns();
            self.element.classList.add("open");
            self.active = true;
            self.setPosition();
        },
        close: ()=>{
            self.element.classList.remove("open");
            self.active = false;
        },
        checkMensions: (string)=>{
            self.mensions = [];
            diviDesignNotesAPI.data.users.forEach((user)=>{
                if (string.includes(`@${user.display_name}`)) {
                    if (!self.mensions.indexOf(user.user_email) + 1) self.mensions.push(user.user_email);
                }
            });
            return self.mensions;
        },
        setPosition: ()=>{
            if (!self.active) return;
            const rects = self.marker.element.getBoundingClientRect();
            self.element.style.top = `${rects.bottom}px`;
            self.element.style.left = `${rects.x}px`;
        },
        clear () {
            self.textArea.value = "";
        },
        clicked: (e)=>{
            if (!e.target.dataset.action || self.ajaxing) return;
            self.ajaxing = true;
            if (e.target.dataset.action === "cancel") {
                self.clear();
                self.marker.reset();
                self.ajaxing = false;
            }
            if (e.target.dataset.action === "create") {
                self.create();
                return;
            }
        },
        create: ()=>{
            if (!self.textArea.value.trim()) {
                self.textArea.value = "";
                self.ajaxing = false;
                return;
            }
            const data = new FormData();
            if (self.checkMensions(self.textArea.value)) data.append("mensions", self.mensions.join(","));
            data.append("type", "create");
            data.append("x", self.marker.position.x);
            data.append("y", self.marker.position.y);
            data.append("el", self.marker.getElSelector());
            data.append("content", self.textArea.value.trim());
            data.append("post_id", diviDesignNotesAPI.data.post_id);
            diviDesignNotesAPI.ajax(data).then((res)=>{
                if (res.ok) return res.json();
            }).then((obj)=>{
                if (obj.success) {
                    self.clear();
                    self.marker.reset();
                    diviDesignNotesAPI.createNote(obj.html);
                }
                self.ajaxing = false;
            });
        },
        init: ()=>{
            self.textArea = self.element.querySelector("textarea");
            document.body.appendChild(self.element);
            (0, $20b4a97a61b3fccb$export$af631764ddc44097)("click", self.clicked, self.element);
        }
    };
    self.init();
    return self;
}



function $6dc5cde899f2ffd0$export$2e2bcd8739ae039(element) {
    const self = {
        element: element,
        relatedElement: null,
        relativeToElement: false,
        dropDown: null,
        active: false,
        position: {
            x: 0,
            y: 0
        },
        getElSelector () {
            return "." + Array.from(self.relatedElement.classList).join(".");
        },
        pinOnPage (relatedElement, position) {
            self.position = position;
            self.relatedElement = relatedElement;
            self.active = true;
            self.element.classList.remove("active");
            if (self.getRelativity()) self.setRelPosition();
            else self.setFixPosition();
            self.openClose();
        },
        setPosition () {
            if (!self.active) return;
            if (self.relativeToElement) return;
            self.setFixPosition();
        },
        getRelativity () {
            self.relativeToElement = [
                "relative",
                "absolute",
                "fixed"
            ].includes(getComputedStyle(self.relatedElement).position);
            return self.relativeToElement;
        },
        setRelPosition () {
            self.relatedElement.appendChild(self.element);
            self.element.style.position = "absolute";
            self.element.style.top = `${self.position.y / self.relatedElement.offsetHeight * 100}%`;
            self.element.style.left = `${self.position.x / self.relatedElement.offsetWidth * 100}%`;
            self.element.style.transform = `translate(-15px,-30px)`;
        // self.element.style.position = 'absolute';
        // self.element.style.transform = `translate3d(${self.position.x}px,${self.position.y}px,0)`;
        },
        setFixPosition () {
            const rects = self.relatedElement.getBoundingClientRect();
            self.element.style.transform = `translate3d(${self.position.x + rects.x}px,${self.position.y + rects.y}px,0)`;
        },
        openClose () {
            self.dropDown.openClose();
        },
        activate (element) {
            self.element.classList.add("active");
            element.appendChild(self.element);
        },
        reset () {
            self.relatedElement = null;
            self.active = false;
            self.dropDown.close();
            self.element.style = "";
            self.element.remove();
        },
        init () {
            self.dropDown = (0, $446359f2ef3c174b$export$2e2bcd8739ae039)(self, (0, $20b4a97a61b3fccb$export$2e6c959c16ff56b8)("#shadowdropdown"));
            (0, $20b4a97a61b3fccb$export$af631764ddc44097)("click", self.openClose, self.element);
            return self;
        }
    };
    return self;
}


function $b43c1ccb12885cba$export$2e2bcd8739ae039(button, dataElement) {
    const self = {
        button: (0, $20b4a97a61b3fccb$export$2e6c959c16ff56b8)("#wp-admin-bar-design_notes"),
        pageContainer: (0, $20b4a97a61b3fccb$export$2e6c959c16ff56b8)("#page-container"),
        templatesContainer: (0, $20b4a97a61b3fccb$export$2e6c959c16ff56b8)("#design_notes_template"),
        data: JSON.parse((0, $20b4a97a61b3fccb$export$2e6c959c16ff56b8)("#divi_design_notes_json").textContent),
        notes: [],
        hoveredElement: false,
        user: "",
        markerActive: false,
        shadowMarker: null,
        timeout: 0,
        currenMatch: null,
        inputTarget: null,
        caret: null,
        usersNode: document.createElement("ul"),
        buttonClicked (e) {
            e.preventDefault();
            if (self.markerActive) self.stopMarker();
            else self.startMarker();
            return self;
        },
        startMarker () {
            self.closeDropdowns();
            self.shadowMarker.reset();
            self.shadowMarker.activate(self.pageContainer);
            (0, $20b4a97a61b3fccb$export$af631764ddc44097)("mousemove", self.followMarker, self.pageContainer);
            self.markerActive = true;
            (0, $20b4a97a61b3fccb$export$af631764ddc44097)("click", self.markerClicked, self.pageContainer);
        },
        stopMarker () {
            (0, $20b4a97a61b3fccb$export$8c8705df4a2dcec9)("mousemove", self.followMarker, self.pageContainer);
            self.shadowMarker.element.remove();
            self.markerActive = false;
            (0, $20b4a97a61b3fccb$export$8c8705df4a2dcec9)("click", self.markerClicked, self.pageContainer);
            if (self.hoveredElement) self.hoveredElement.style.outline = "";
        },
        followMarker (e) {
            let module = e.target.matches(".et_pb_module") ? e.target : e.target.closest(".et_pb_module,.et_pb_row,.et_pb_section,header,#page-container");
            if (module) {
                if (module !== self.hoveredElement) {
                    if (self.hoveredElement) self.hoveredElement.style.outline = "";
                    self.hoveredElement = module;
                    self.hoveredElement.style.outline = "3px solid blue";
                }
            } else if (self.hoveredElement) {
                self.hoveredElement.style.outline = "";
                self.hoveredElement = false;
            }
            self.shadowMarker.element.style.transform = `translate3d(${e.clientX}px,${e.clientY}px,0)`;
        },
        markerClicked (e) {
            e.preventDefault();
            (0, $20b4a97a61b3fccb$export$8c8705df4a2dcec9)("mousemove", self.followMarker, self.pageContainer);
            (0, $20b4a97a61b3fccb$export$8c8705df4a2dcec9)("click", self.markerClicked, self.pageContainer);
            const rects = self.hoveredElement.getBoundingClientRect();
            const position = {
                x: e.x - rects.x,
                y: e.y - rects.y
            };
            self.shadowMarker.pinOnPage(self.hoveredElement, position);
            self.markerActive = false;
            if (self.hoveredElement) self.hoveredElement.style.outline = "";
        },
        createNote (html) {
            const node = (0, $20b4a97a61b3fccb$export$d544df0d2baa9f2c)(html, self.templatesContainer, "afterbegin");
            const newNote = (0, $f264c4e0c2cd7c0b$export$2e2bcd8739ae039)(node).init();
            self.notes.push(newNote);
            newNote.openClose();
        },
        inputHandler (e) {
            if (e.target.matches(".design_note_textarea")) {
                const input = self.inputTarget = e.target;
                const textToCaret = input.value.substring(0, input.selectionStart);
                const match = textToCaret.match(/@[a-z]{0,3}$/i);
                if (match) {
                    self.currenMatch = match;
                    input.parentElement.appendChild(self.usersNode);
                } else self.usersNode.remove();
            }
        },
        closeDropdowns () {
            self.notes.forEach((marker)=>{
                marker.dropDown.close();
            });
            self.shadowMarker.dropDown.close();
        },
        chooseUser (e) {
            const user = e.target;
            const input = self.inputTarget;
            const rejex = new RegExp(`${self.currenMatch}$`, "g");
            input.value = input.value.replace(rejex, `@${user.dataset.userName} `);
            input.focus();
            input.setSelectionRange(input.value.length + 1, input.value.length + 1);
            self.usersNode.remove();
        },
        setPositions () {
            self.notes.forEach((item)=>{
                item.setPosition();
                item.dropDown.setPosition();
            });
            self.shadowMarker.setPosition();
            self.shadowMarker.dropDown.setPosition();
        },
        maybeSetTimeout (e) {
            if (self.timeout) clearTimeout(self.timeout);
            self.timeout = setTimeout(()=>{
                self.setPositions();
                self.timeout = 0;
            }, 100);
        },
        ajax (data) {
            data.append("action", "divi_design_notes_ajax");
            data.append("diviDesignNotesNonce", self.data.nonce);
            return fetch(self.data.ajaxurl, {
                method: "POST",
                body: data
            });
        },
        setMarkers () {
            const selectedMarkers = (0, $20b4a97a61b3fccb$export$2e6c959c16ff56b8)("[id^=notemarker]", self.templatesContainer, true);
            selectedMarkers.forEach((node)=>{
                self.notes.push((0, $f264c4e0c2cd7c0b$export$2e2bcd8739ae039)(node).init());
                return;
            });
        },
        init () {
            //Users related
            self.usersNode.id = "design_notes_user_list";
            self.data.users.forEach((item)=>{
                const li = document.createElement("li");
                li.dataset.userId = item.id;
                li.dataset.userName = item.display_name;
                li.textContent = item.display_name;
                self.usersNode.appendChild(li);
            });
            //Set Markers
            const selectedMarkers = (0, $20b4a97a61b3fccb$export$2e6c959c16ff56b8)("[id^=notemarker]", self.templatesContainer, true);
            selectedMarkers.forEach((node)=>{
                self.notes.push((0, $f264c4e0c2cd7c0b$export$2e2bcd8739ae039)(node).init());
                return;
            });
            //ShadowMarker
            const elem = (0, $20b4a97a61b3fccb$export$2e6c959c16ff56b8)("#shadowmarker", self.templatesContainer);
            self.shadowMarker = (0, $6dc5cde899f2ffd0$export$2e2bcd8739ae039)(elem).init();
            (0, $20b4a97a61b3fccb$export$af631764ddc44097)("click", self.chooseUser, self.usersNode);
            (0, $20b4a97a61b3fccb$export$af631764ddc44097)("click", self.buttonClicked, self.button);
            (0, $20b4a97a61b3fccb$export$af631764ddc44097)("scroll", self.maybeSetTimeout);
            (0, $20b4a97a61b3fccb$export$af631764ddc44097)("resize", self.maybeSetTimeout, window);
            (0, $20b4a97a61b3fccb$export$af631764ddc44097)("input", self.inputHandler);
            (0, $20b4a97a61b3fccb$export$af631764ddc44097)("transitionend", self.maybeSetTimeout, self.pageContainer);
        }
    };
    return self;
}



function $040bacab4c74f57c$export$2e2bcd8739ae039(relatedElement, coor) {
    const self = {
        relatedElement: typeof relatedElement !== "string" ? relatedElement : (0, $20b4a97a61b3fccb$export$2e6c959c16ff56b8)(relatedElement),
        relativeToElement: false,
        position: {
            x: coor.x,
            y: coor.y,
            top: coor.top ? coor.top : 0,
            left: coor.left ? coor.left : 0
        },
        marker: "",
        dropDown: "",
        calculatePosition () {
            if (self.relativeToElement) return;
            const rects = self.relatedElement.getBoundingClientRect();
            self.position.top = scrollY + rects.top;
            self.position.left = scrollX + rects.left;
        },
        setPosition () {
            if (self.relativeToElement) {
                self.marker.style.transform = `translate3d(${self.position.x}px,${self.position.y}px,0)`;
                return;
            }
            self.marker.style.transform = `translate3d(${self.position.x + self.position.left}px,${self.position.y + self.position.top}px,0)`;
        },
        showHideDropDown: ()=>{
            self.marker.classList.add("open");
        },
        reset: (relatedElement, coor)=>{
            self.relatedElement = relatedElement;
            self.position = {
                x: coor.x,
                y: coor.y,
                top: coor.top ? coor.top : 0,
                left: coor.left ? coor.left : 0
            };
            self.calculatePosition();
            if (self.getRelativity()) self.relatedElement.appendChild(self.marker);
            else document.body.appendChild(self.marker);
            self.textArea.textContent = "";
            return self;
        },
        getRelativity: ()=>{
            self.relativeToElement = [
                "relative",
                "absolute",
                "fixed"
            ].includes(getComputedStyle(self.relatedElement).position);
            return self.relativeToElement;
        }
    };
    self.getRelativity();
    self.marker = (()=>{
        let marker = (0, $20b4a97a61b3fccb$export$c8a8987d4410bf2d)("span", {
            class: "design_note_marker absolute"
        });
        if (self.relativeToElement) marker = (0, $20b4a97a61b3fccb$export$d544df0d2baa9f2c)(marker, self.relatedElement);
        else marker = (0, $20b4a97a61b3fccb$export$d544df0d2baa9f2c)(marker, document.body);
        return marker;
    })();
    self.textArea = (0, $20b4a97a61b3fccb$export$c8a8987d4410bf2d)("span", {
        class: "design_note_textarea",
        attributes: [
            {
                name: "contenteditable",
                value: true
            }
        ]
    });
    self.dropDown = (0, $20b4a97a61b3fccb$export$c8a8987d4410bf2d)("", {
        class: "design_note_dropdown"
    });
    self.dropDown = (0, $20b4a97a61b3fccb$export$d544df0d2baa9f2c)(self.dropDown, self.marker);
    self.textArea = (0, $20b4a97a61b3fccb$export$d544df0d2baa9f2c)(self.textArea, self.dropDown);
    (0, $20b4a97a61b3fccb$export$af631764ddc44097)("click", self.showHideDropDown, self.marker);
    return self;
}




window.addEventListener("load", function() {
    window.diviDesignNotesAPI = (0, $b43c1ccb12885cba$export$2e2bcd8739ae039)();
    window.diviDesignNotesAPI.init();
});


//# sourceMappingURL=main.js.map
