require('./bootstrap');
window.moment = window.Moment = require('moment');
require('admin-lte');
require('tempusdominus-bootstrap-4');
require('datatables.net-bs4');
require('datatables.net-responsive-bs4');
require('datatables.net-buttons-bs4');
require('./toasty/src/toasty.js');
require('./jquery.marquee');

window.Pusher = require('pusher-js');

global.$ = global.jQuery = require('jquery');

import Vue from 'vue';
import Echo from "laravel-echo";
// import VueToast from 'vue-toast-notification';
// import 'vue-toast-notification/dist/theme-sugar.css';
import th from 'vee-validate/dist/locale/th';
import VeeValidate from 'vee-validate';
import Swal from 'sweetalert2';
import {BootstrapVue, IconsPlugin} from 'bootstrap-vue';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'app-key',
    wsHost: window.location.hostname,
    disableStats: true,
    authEndpoint: '/broadcasting/auth'
});

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
})

window.Vue = Vue;
window.VeeValidate = VeeValidate;
window.Toast = Toast;
window.Swal = Swal;


Vue.prototype.$http = axios;


// Vue.use(VueToast,{
//     classname: "toast",
//         transition: "scale",
//         insertBefore: true,
//         duration: 5000,
//         enableSounds: true,
//         autoClose: false,
//         progressBar: true,
//         sounds: {
//         info: "storage/sound/alert.mp3",
//             success: "storage/sound/alert.mp3",
//             warning: "storage/sound/alert.mp3",
//             error: "storage/sound/alert.mp3",
//     }
// });
Vue.use(BootstrapVue);
Vue.use(IconsPlugin);

Vue.use(VeeValidate, {
    dictionary: {
        th: th
    },
    inject: 'true',
    fieldsBagName: 'veeFields'
});


window.Toasty = new Toasty({
    classname: "toast",
    transition: "fade",
    insertBefore: true,
    duration: 5000,
    enableSounds: true,
    autoClose: true,
    progressBar: true,
    sounds: {
        info: "storage/sound/alert.mp3",
        success: "storage/sound/alert.mp3",
        warning: "storage/sound/alert.mp3",
        error: "storage/sound/alert.mp3",
    }
});

window.eventBus = new Vue();

$(document).ready(function () {

    Vue.mixin({
        data: function () {
            return {
                'imageObserver': null,
                'baseUrl': document.getElementById("mainscript").getAttribute('baseUrl')

            }
        },
        methods: {
            redirect: function (route) {
                route ? window.location.href = route : '';
            },


            isMobile: function () {
                if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i | /mobi/i.test(navigator.userAgent)) {
                    if (this.isMaxWidthCrossInLandScape()) {
                        return false;
                    }
                    return true
                } else {
                    return false
                }
            },

            isMaxWidthCrossInLandScape: function () {
                return window.innerWidth > 900;
            },

            getDynamicHTML: function (input) {
                var _staticRenderFns;
                const {render, staticRenderFns} = Vue.compile(input);

                if (this.$options.staticRenderFns.length > 0) {
                    _staticRenderFns = this.$options.staticRenderFns;
                } else {
                    _staticRenderFns = this.$options.staticRenderFns = staticRenderFns;
                }

                try {
                    var output = render.call(this, this.$createElement);
                } catch (exception) {
                    console.log(this.__('error.something_went_wrong'));
                }

                this.$options.staticRenderFns = _staticRenderFns;

                return output;
            },

            getStorageValue: function (key) {
                let value = window.localStorage.getItem(key);

                if (value) {
                    value = JSON.parse(value);
                }

                return value;
            },

            setStorageValue: function (key, value) {
                window.localStorage.setItem(key, JSON.stringify(value));

                return true;
            },
        }
    });

    new Vue({
        el: "#app",

        data: function () {
            return {
                modalIds: {}
            }
        },

        mounted: function () {
            setTimeout(() => {
                this.addServerErrors();
                this.addFlashMessages();
            }, 0);


            this.$validator.localize(document.documentElement.lang);
            this.addIntersectionObserver();
        },

        methods: {

            onSubmit: function (e) {
                this.toggleButtonDisable(true);

                if (typeof tinyMCE !== 'undefined')
                    tinyMCE.triggerSave();

                this.$validator.validateAll().then(result => {
                    if (result) {
                        e.target.submit();
                    } else {
                        this.toggleButtonDisable(false);

                        eventBus.$emit('onFormError')
                    }
                });
            },

            toggleButtonDisable(value) {
                var buttons = document.getElementsByTagName("button");

                for (var i = 0; i < buttons.length; i++) {
                    buttons[i].disabled = value;
                }
            },

            addServerErrors: function (scope = null) {
                for (var key in serverErrors) {
                    var inputNames = [];
                    key.split('.').forEach(function (chunk, index) {
                        if (index) {
                            inputNames.push('[' + chunk + ']')
                        } else {
                            inputNames.push(chunk)
                        }
                    })

                    var inputName = inputNames.join('');

                    const field = this.$validator.fields.find({
                        name: inputName,
                        scope: scope
                    });

                    if (field) {
                        this.$validator.errors.add({
                            id: field.id,
                            field: inputName,
                            msg: serverErrors[key][0],
                            scope: scope
                        });
                    }
                }
            },

            addFlashMessages: function () {
                for (let key in flashMessages) {
                    if (flashMessages[key].message)
                        Toast.fire({
                            icon: flashMessages[key].type,
                            title: flashMessages[key].message
                        })
                }
            },

            showModal: function (refer) {
                console.log(refer);
                this.$nextTick(() => {
                    this.$root.$refs[refer].show();
                });
                // $(id).modal('show');
                // this.$set(this.modalIds, id, true);
                // this.$root.$emit('bv::show::modal', 'modal-1', '#'+refer);
            },

            addIntersectionObserver: function () {
                this.imageObserver = new IntersectionObserver((entries, imgObserver) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            const lazyImage = entry.target
                            lazyImage.src = lazyImage.dataset.src
                        }
                    })
                });
            },

        }
    });

    // for compilation of html coming from server
    Vue.component('vnode-injector', {
        functional: true,
        props: ['nodes'],
        render(h, {props}) {
            return props.nodes;
        }
    });
});


