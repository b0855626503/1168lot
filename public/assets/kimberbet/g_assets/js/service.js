const userAgent = window.navigator.userAgent.toLowerCase();

function isIpadScreen() {
    if (window.screen.height / window.screen.width == 1024 / 768) {
        if (window.devicePixelRatio == 1) {
            return true;
        } else {
            return true;
        }
    } else if (window.screen.height / window.screen.width == 1112 / 834) {
        return true;
    } else if (window.screen.height / window.screen.width == 1366 / 1024) {
        return true;
    } else {
        return false;
    }
}

function isChrome() {
    return (userAgent).match('crios') || /Google Inc/.test(navigator.vendor) || (!!window.chrome);
}

function isSafari() {
    return /safari/.test(userAgent);
}

function isIosDevice() {
    return /iphone|ipod|ipad/.test(userAgent) || (userAgent).indexOf('mac os x') != -1;
}

function isLaunchedInstalledA2H() {
    return (navigator.standalone || window.matchMedia('(display-mode: standalone)').matches);
}

function isChromeBrowser() {
    return /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
}

function touchMoveSetup(position = 'up', el = false, callback = false) {
    if (!el) return false;
    let _el;
    _el = document.querySelector(el);
    if (!_el) return false;
    let tms;
    _el.addEventListener("touchstart", (e) => {
        tms = e.touches[0].clientY;
    }, false);
    _el.addEventListener("touchend", (e) => {
        let tme = e.changedTouches[0].clientY;
        if (typeof callback === 'function') {
            if ((tms - tme) > 12) {
                if (position === 'up') {
                    callback();
                }
            } else {
                if (position === 'down') {
                    callback();
                }
            }
        }
    }, false);
}

$(document).ready(function () {
    $('.snip-image_slider').each(function (i, obj) {
        let no_paginate = $(obj).data('no_paginate');
        let param = {
            calculateHeight: true,
            autoplay: {delay: $(obj).data('delay') * 1000, disableOnInteraction: false,},
            spaceBetween: 20,
            loop: true,
            pagination: {el: ".swiper-pagination", clickable: true,},
            navigation: {nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev",},
        };
        if (no_paginate) param.pagination = false;
        new Swiper(obj, param);
    });
});