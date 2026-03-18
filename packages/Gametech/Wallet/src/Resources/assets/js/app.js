require('./bootstrap');
window.Winwheel = require('./Winwheel.js');
require('malihu-custom-scrollbar-plugin');
window.moment = window.Moment = require('moment');
require('./daterangepicker/daterangepicker.js');
window.Pusher = require('pusher-js');

global.$ = global.jQuery = require('jquery');

import Vue from 'vue';
import Echo from "laravel-echo";
// import VueToast from 'vue-toast-notification';
// import 'vue-toast-notification/dist/theme-sugar.css';
import th from 'vee-validate/dist/locale/th';
import VeeValidate from 'vee-validate';
import Swal from 'sweetalert2';
import AOS from 'aos';
import {BootstrapVue, IconsPlugin} from 'bootstrap-vue';
// import VueI18n from 'vue-i18n';
import _ from 'lodash';
// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: 'app-key',
//     wsHost: window.location.hostname,
//     encrypted: false,
//     wsPort: 80, // Yor http port
//     forceTLS: true,
//     disableStats: true,
//     enabledTransports: ['ws', 'wss'],
//     authEndpoint: '/member/broadcasting/auth'
// });

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'app-key',
    wsHost: window.location.hostname,
    disableStats: true,
    authEndpoint: '/member/broadcasting/auth'
});

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    animation: true,
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});


window.Vue = Vue;
window.VeeValidate = VeeValidate;
window.Toast = Toast;
window.Swal = Swal;

Vue.prototype.$http = axios;
Vue.prototype.__ = str => _.get(window.i18n, str);
// Vue.use(VueToast,{
//     position: 'top-right',
//     duration: 30000
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
// Vue.use(VueI18n);

Vue.component('game-list', require('./components/game-list').default);
Vue.component('gameseamless-list', require('./components/gameseamless-list').default);
Vue.component('gamefree-list', require('./components/gamefree-list').default);
Vue.component('slide', require('./components/Slide').default);

Vue.component('carousel', require('./components/owlcarousel').default);
Vue.component('carousel-free', require('./components/owlcarousel-free').default);
Vue.component('game', require('./components/game').default);
Vue.component('gamefree', require('./components/gamefree').default);
Vue.component('wheel', require('./components/wheel').default);
Vue.component('wallet', require('./components/wallet').default);
Vue.component('cashback', require('./components/cashback').default);
Vue.component('credit', require('./components/credit').default);
Vue.component('seamless', require('./components/seamless').default);
Vue.component('seamlessfree', require('./components/seamlessfree').default);
Vue.component('wheel', require('./components/wheel').default);
Vue.component('change-pass', require('./components/changepass').default);
Vue.component('profile', require('./components/profile').default);
Vue.component('profile-min', require('./components/profilemin').default);

Vue.component('profilefree', require('./components/profilefree').default);
Vue.component('profilefree-min', require('./components/profilefreemin').default);

Vue.component('checkin', require('./components/checkin').default);
Vue.component('checkinlog', require('./components/checkinlog').default);
Vue.component('checkinlog-item', require('./components/checkinlog-item').default);
Vue.component('recapcha', require('./components/recapcha').default);
Vue.component('reward-list', require('./components/reward-list').default);
Vue.component('reward-item', require('./components/reward-item').default);
Vue.component('window-portal', require('./components/window-portal').default);


window.eventBus = new Vue();

$(document).ready(function () {

    AOS.init({
        once: true
    });

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
                modalIds: {},
                showPageLoader: false,
            }
        },

        created: function () {
            setTimeout(() => {
                document.body.classList.remove("modal-open");
            }, 0);

            window.addEventListener('click', () => {
                let modals = document.getElementsByClassName('sensitive-modal');

                Array.from(modals).forEach(modal => {
                    modal.classList.add('hide');
                });
            });
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

            showModal: function (id) {
                this.$set(this.modalIds, id, true);
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

            showLoader: function () {
                $('#loader').show();
                $('.overlay-loader').show();

                document.body.classList.add("modal-open");
            },

            hideLoader: function () {
                $('#loader').hide();
                $('.overlay-loader').hide();

                document.body.classList.remove("modal-open");
            },

            topFunction: function () {
                document.body.scrollTop = 0;
                document.documentElement.scrollTop = 0;
            },

            onOpen: function (route) {
                window.open(route);
            }

        }
    });

    Vue.component('vnode-injector', {
        functional: true,
        props: ['nodes'],
        render(h, {props}) {
            return props.nodes;
        }
    });

});
