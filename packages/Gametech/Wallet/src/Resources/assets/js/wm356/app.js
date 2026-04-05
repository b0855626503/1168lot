require('./bootstrap');
window.Winwheel = require('./Winwheel.js');
// window.moment = window.Moment = require('moment');
window.Pusher = require('pusher-js');

global.$ = global.jQuery = require('jquery');

import Vue from 'vue';
import Echo from "laravel-echo";
import th from 'vee-validate/dist/locale/th';
import VeeValidate from 'vee-validate';

const broadcastConfig = window.broadcastConfig || {};
const broadcastScheme = broadcastConfig.scheme || window.location.protocol.replace(':', '') || 'http';
const broadcastPort = Number(broadcastConfig.port || (broadcastScheme === 'https' ? 443 : 8080));

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: broadcastConfig.key || 'app-key',
    wsHost: broadcastConfig.host || window.location.hostname,
    wsPort: broadcastPort,
    wssPort: broadcastPort,
    forceTLS: broadcastScheme === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    authEndpoint: '/member/broadcasting/auth'
});

window.Vue = Vue;
window.VeeValidate = VeeValidate;


Vue.prototype.$http = axios;
Vue.prototype.__ = str => _.get(window.i18n, str);

Vue.component('game-list', require('./components/game-list').default);

Vue.use(VeeValidate, {
    dictionary: {
        th: th
    },
    inject: 'true',
    fieldsBagName: 'veeFields'
});

window.eventBus = new Vue();

