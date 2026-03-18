if (typeof moment !== 'undefined') {
    moment.defaultFormat = "YYYY-MM-DD HH:mm:ss";
    moment.locale('th');
}
if (typeof Vue !== 'undefined') {
    Vue.filter('num', v => intToNum(v));
    Vue.filter('money', v => intToMoney(v));
    Vue.directive('select2', {
        twoWay: true, bind: function (el, binding, vnode) {
            let attr = vnode.data.attrs;
            Vue.nextTick(() => {
                $(el).select2(attr.options).on('select2:select', e => {
                    let data = e.params.data.id;
                    vueDirectiveModel(vnode, data);
                    _.set(vnode.model)
                    attr.model = data;
                }).on('select2:unselecting', e => {
                    vueDirectiveModel(vnode, '');
                }).trigger('change');
            });
        }, update: function (el, binding, vnode) {
            Vue.nextTick(() => {
                $(el).trigger('change');
            });
        }, unbind: function (el) {
            $(el).off().select2('destroy');
        }
    });
}
_.move = function (array, from, to) {
    return array.splice(to, 0, array.splice(from, 1)[0]);
};

function intToMoney(num) {
    if (num === null || isNaN(num)) return 'รอปรับยอด...';
    return Number(num).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})
}

function intToNum(num) {
    if (num === null || isNaN(num)) return 'loading..';
    return Number(num).toLocaleString();
}

function swapPositive(num) {
    return num == 0 ? num : -num;
}

function pad0(num, toString = true) {
    return num < 0 ? '00' : num < 10 ? '0' + num : (toString ? num.toString() : num);
}

async function post(url, data) {
    let r = await fetch(url, {
        method: 'POST',
        headers: {'content-type': 'application/json'},
        body: JSON.stringify(data)
    });
    try {
        return r.json();
    } catch (e) {
        return {success: false, code: e.code, data: e.message};
    }
}

function bytesToSize(bytes) {
    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    if (bytes == 0) return '0 Byte';
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
}

function fileToBase64(file) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        try {
            reader.readAsDataURL(file);
            reader.onload = () => resolve(reader.result);
            reader.onerror = error => resolve(false);
        } catch (e) {
            resolve(false)
        }
    });
}

window.modal = {
    info(msg, title = 'เตือน') {
        return Swal.fire({title, html: msg, icon: 'info', allowOutsideClick: false});
    }, message(msg, title) {
        return Swal.fire({title, html: msg, allowOutsideClick: false});
    }, success(msg, title = 'สำเร็จ', opt = {}) {
        let op = _.merge({title, html: msg, icon: 'success', allowOutsideClick: false}, opt);
        return Swal.fire(op);
    }, error(msg, title = 'Error') {
        return Swal.fire({title, html: msg, icon: 'error', allowOutsideClick: false});
    }, confirm(msg, title = 'ยืนยัน', option) {
        let opt = {title, html: msg, icon: 'warning', showCancelButton: true, allowOutsideClick: false,};
        return new Promise(resolve => {
            Swal.fire(_.merge(opt, option)).then(r => {
                return resolve(r.value);
            })
        });
    }, confirmLoading(msg, title = 'ยืนยัน', option) {
        let opt = {
            title,
            html: msg,
            icon: 'warning',
            showCancelButton: true,
            allowOutsideClick: false,
            showLoaderOnConfirm: true,
        };
        return new Promise(resolve => {
            Swal.fire(_.merge(opt, option)).then(r => {
                return resolve(r.value);
            })
        });
    }
};

function nl2br(str, is_xhtml) {
    if (typeof str === 'undefined' || str === null) {
        return '';
    }
    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br/>' : '<br>';
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}

window.humantime = {
    full_th: function (datetime, with_weekday, format = false) {
        var mm = moment(datetime, format || 'YYYY-MM-DD HH:mm:ss');
        return mm.locale('th').format((with_weekday ? 'dddd ' : '') + 'D MMMM') + ' ' + (parseInt(moment(datetime, format || 'YYYY-MM-DD HH:mm:ss').locale('th').format('YYYY')) + 543);
    }, timeago: function (datetime, bracket, format) {
        var mm = moment(datetime, format || 'YYYY-MM-DD HH:mm:ss');
        return (bracket ? '(' : '') + mm.fromNow() + (bracket ? ')' : '');
    }, time_with_ago: function (datetime, time_ago = true, format) {
        var mm = moment(datetime, format || 'YYYY-MM-DD HH:mm:ss');
        return mm.format('DD/MM/YYYY HH:mm:ss') + (time_ago ? ' (' + mm.fromNow() + ')' : '');
    }, weekday: function () {
    }, clock: function (datetime, with_second, format) {
        var mm = moment(datetime, format || 'YYYY-MM-DD HH:mm:ss');
        return mm.format('HH:mm' + (with_second ? ':ss' : ''));
    }, isToday: function (datetime, format) {
        var mm = moment(datetime, format || 'YYYY-MM-DD HH:mm:ss');
        return mm.isSame(moment(), 'day');
    }, getTimeRemaining(endtime, string = true) {
        const total = Date.parse(endtime) - Date.parse(new Date());
        const s = Math.floor((total / 1000) % 60);
        const m = Math.floor((total / 1000 / 60) % 60);
        const h = Math.floor((total / (1000 * 60 * 60)) % 24);
        const d = Math.floor(total / (1000 * 60 * 60 * 24));
        return {
            total,
            day: d || 0,
            hour: (h < 10 ? '0' : '') + h || 0,
            minute: (m < 10 ? '0' : '') + m || 0,
            second: (s < 10 ? '0' : '') + s || 0,
        };
    }
};
window.func = {
    genPaginate({el = '#pagination', list} = {}, cb) {
        $.jqPaginator(el, {
            wrapper: '',
            first: '<li class="first page-item"><a class="page-link" href="javascript:;">&laquo;</a></li>',
            prev: '<li class="prev page-item"><a class="page-link" href="javascript:;">&lt;</a></li>',
            next: '<li class="next page-item"><a class="page-link" href="javascript:;">&gt;</a></li>',
            last: '<li class="last page-item"><a class="page-link" href="javascript:;">&raquo;</a></li>',
            page: '<li class="page page-item"><a class="page-link" href="javascript:;">{{page}}</a></li>',
            totalPages: +list.totalPages || Math.floor(+list.totalRecords / +list.length) || 0,
            totalCounts: +list.totalRecords || +list.recordsTotal || 0,
            pageSize: +list.length,
            currentPage: +list.page,
            visiblePages: 7,
            onPageChange: (page, type) => {
                if (type !== 'change') return;
                if (typeof cb === 'function') cb(page);
            }
        });
    },
};

function vueDirectiveModel(vnode, setVal) {
    let model = vnode.data.directives.find(o => o.name === 'model');
    if (!model) return null;
    return _.set(vnode.context, model.expression, setVal);
}

window.toast = {
    secondary(msg, title = 'แจ้งเตือน', opt) {
        iziToast.show(_.merge({title, message: msg, icon: 'fa fa-info-circle'}, opt));
    }, info(msg, title = 'แจ้ง', opt) {
        iziToast.show(_.merge({
            color: 'blue',
            layout: 2,
            position: 'topRight',
            title,
            message: msg,
            icon: 'fa fa-info-circle'
        }, opt));
    }, success(msg, title = 'สำเร็จ', opt) {
        iziToast.show(_.merge({
            color: 'green',
            layout: 2,
            position: 'topRight',
            title,
            message: msg,
            icon: 'fa fa-check-circle'
        }, opt));
    }, warning(msg, title = 'เตือน', opt) {
        iziToast.show(_.merge({
            color: 'yellow',
            layout: 2,
            position: 'topRight',
            title,
            message: msg,
            icon: 'fa fa-exclamation-circle'
        }, opt));
    }, error(msg, title = 'Error', opt) {
        iziToast.show(_.merge({
            color: 'red',
            layout: 2,
            position: 'topRight',
            resetOnHover: true,
            title,
            message: msg,
            icon: 'fa fa-times-circle'
        }, opt));
    }, chat(msg, title = 'Chat', opt = {}) {
        iziToast.show(_.merge({
            color: 'blue',
            layout: 2,
            image: 'https://image.flaticon.com/icons/png/512/149/149071.png',
            position: 'topCenter',
            closeOnClick: true,
            balloon: true,
            resetOnHover: true,
            title,
            message: msg
        }, opt));
    }, primary(msg, from, image = null, color = 'yellow', btnSets = null) {
        iziToast.show({
            color,
            layout: 2,
            image: image || 'https://image.flaticon.com/icons/png/512/149/149071.png',
            position: 'topRight',
            buttons: btnSets,
            resetOnHover: true,
            title: from,
            message: msg
        });
    },
};
let component_option = {
    datepicker_option: {
        default: {
            opens: 'left',
            showDropdowns: true,
            timePicker: true,
            timePicker24Hour: true,
            alwaysShowCalendars: true,
            locale: {},
            maxYear: moment(),
            endDate: moment(),
            ranges: {
                'วันนี้': [moment().startOf('day'), moment().endOf('day')],
                'เมื่อวาน': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                'อาทิตย์นี้ (อา.-ส.)': [moment().startOf('week'), moment().endOf('week')],
                '7 วันล่าสุด': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
                '30 วันล่าสุด': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
                'เดือนนี้': [moment().startOf('month').startOf('day'), moment().endOf('month').endOf('day')],
                'เดือนก่อน': [moment().subtract(1, 'month').startOf('month').startOf('day'), moment().subtract(1, 'month').endOf('month').endOf('day')]
            },
        },
        single_date: {
            singleDatePicker: true,
            showDropdowns: true,
            locale: {format: 'YYYY-MM-DD'},
            autoUpdateInput: false,
        },
        range_no_time: {
            opens: 'left',
            showDropdowns: true,
            timePicker: false,
            alwaysShowCalendars: true,
            locale: {format: 'YYYY-MM-DD'},
            maxYear: moment(),
            endDate: moment(),
            ranges: {
                'วันนี้': [moment().startOf('day'), moment().endOf('day')],
                'เมื่อวาน': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                'อาทิตย์นี้ (อา.-ส.)': [moment().startOf('week'), moment().endOf('week')],
                '7 วันล่าสุด': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
                '30 วันล่าสุด': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
                'เดือนนี้': [moment().startOf('month').startOf('day'), moment().endOf('month').endOf('day')],
                'เดือนก่อน': [moment().subtract(1, 'month').startOf('month').startOf('day'), moment().subtract(1, 'month').endOf('month').endOf('day')]
            },
        }
    },
};