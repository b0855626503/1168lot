const baseUrl = document.getElementById("mainscript").getAttribute('baseUrl');

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

function opentabaccount(evt, cityName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("nav-link");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(cityName).style.display = "block";
    evt.currentTarget.className += " active";
}

function copylink() {
    $(".myAlert-top").show();
    setTimeout(function () {
        $(".myAlert-top").hide();
    }, 1000);
}

function LoadHistory(method) {
    let url = baseUrl + '/member/history';
    axios.post(url, {id: method})
        .then(response => {
            // console.log(response.data.data);
            $('.historydata').html('');
            $.each(response.data.data, function(index,data) {

                let $element = $("#history-theme").html();

                $element = $element.replace("{method}", data.method)
                    .replaceAll("{billid}", data.id)
                    .replaceAll("{status}", data.status_display)
                    .replaceAll("{image}", data.image)
                    .replaceAll("{amount}", data.amount)
                    .replaceAll("{datetime}", data.date_create);

                $('.historydata').append($element);
            });



        })
        .catch(response => {

        });
}

function LoadHistory2(method) {
    let url = baseUrl + '/member/credit/history';
    axios.post(url, {id: method})
        .then(response => {
            // console.log(response.data.data);
            $('.historydata').html('');
            $.each(response.data.data, function(index,data) {

                let $element = $("#history-theme").html();

                $element = $element.replace("{method}", data.method)
                    .replaceAll("{billid}", data.id)
                    .replaceAll("{status}", data.status_display)
                    .replaceAll("{image}", data.image)
                    .replaceAll("{amount}", data.amount)
                    .replaceAll("{datetime}", data.date_create);

                $('.historydata').append($element);
            });



        })
        .catch(response => {

        });
}

function trans(key, replace = {}) {
    var translation = key.split('.').reduce((t, i) => t[i] || null, window.i18n);

    for (var placeholder in replace) {
        translation = translation.replace(`:${placeholder}`, replace[placeholder]);
    }
    return translation;
}

function openPopup(id, msg) {
    let url = baseUrl + '/member/transfer/bonus/confirm';

    Swal.fire({
        title: trans('app.bonus.word') + msg + trans('app.bonus.word2'),
        html: trans('app.bonus.confirm'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: trans('app.bonus.yes'),
        cancelButtonText: trans('app.bonus.no'),
        allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(url, {
                id: id
            }).then(response => {
                if (response.data.success) {
                    Swal.fire(
                        trans('app.bonus.success'),
                        response.data.message,
                        'success'
                    );
                    setTimeout(() => {
                        window.location.href = window.location;
                    }, 2000);
                } else {
                    Swal.fire(
                        trans('app.bonus.fail'),
                        response.data.message,
                        'error'
                    );
                }

            }).catch(err => [err]);
        }
        setTimeout(function () {
            $('button.js-promotion-apply').prop("disabled", false);
        }, 2000);
    })
}

$('.-btn-balance-normal').click(function (e) {
    $('.-btn-balance .fa-sync-alt').addClass("fa-spin");
    let url = baseUrl + '/member/loadcredit';
    const getResponse = async () => {
        try {
            const response = await axios.get(url);
            $('.wallet_amount').text(response.data.profile.balance);
            $('#frmwithdraw').find('[name="amount"]').val(response.data.profile.balance);
            setTimeout(function () {
                $('.-btn-balance .fa-sync-alt').removeClass("fa-spin");
            }, 2000);

        } catch (err) {
            console.log('err')
        }
    }
    getResponse();

});

$('.-btn-balance-free').click(function () {
    $('.-btn-balance .fa-sync-alt').addClass("fa-spin");
    let url = baseUrl + '/member/loadcredit';
    const getResponse = async () => {
        try {
            const response = await axios.get(url);
            $('.wallet_amount').text(response.data.profile.balance_free);
            setTimeout(function () {
                $('.-btn-balance .fa-sync-alt').removeClass("fa-spin");
            }, 2000);

        } catch (err) {
            console.log('err')
        }
    }
    getResponse();

});

function getBonus(code){
    let url = baseUrl + '/member/getbonus';
    axios.post(url, { id : code})
        .then(response => {
            if (response.data.success) {
                Swal.fire(
                    trans('app.bonus.success'),
                    response.data.message,
                    'success'
                );
            } else {

                Swal.fire(
                    trans('app.bonus.wrong'),
                    response.data.message,
                    'error'
                );
            }
        })
        .catch(response => {

            Swal.fire(
                trans('app.bonus.wrong'),
                response.data.message,
                'error'
            );
        });

}

bonusModal = function (event) {
    (async () => {
        const ipAPI = baseUrl + '/member/bonuslist';
        const response = await fetch(ipAPI);
        const data = await response.json();
        const htmls = data.html;
        await Swal.fire({
            title: trans('app.bonus.coupon'),
            html:htmls,
            showConfirmButton : false,
            willOpen: () => {


            }
        });
        setTimeout(function () {
            $('button.js-promotion-apply').prop("disabled", false);
        }, 2000);
    })()
};
$('.-btn-balance-free').click(function () {
    $('.-btn-balance .fa-sync-alt').addClass("fa-spin");
    let url = baseUrl + '/member/loadcredit';
    const getResponse = async () => {
        try {
            const response = await axios.get(url);
            $('.wallet_amount').text(response.data.profile.balance_free);
            setTimeout(function () {
                $('.-btn-balance .fa-sync-alt').removeClass("fa-spin");
            }, 2000);

        } catch (err) {
            console.log('err')
        }
    }
    getResponse();

});

$("form#frmwithdraw").submit(function (event) {

    event.target.submit();
});

$("form#frmcoupon").submit(function (event) {
    // event.preventDefault();
    var input = $("#frmcoupon :input[name='coupon']").val();
    let url = baseUrl + '/member/redeem';
    const getResponse = async (input) => {
        try {
            const response = await axios.post(url,{ coupon : input});
            if (response.data.success) {
                Swal.fire(
                    trans('app.bonus.success'),
                    response.data.message,
                    'success'
                )

            } else {
                Swal.fire(
                    trans('app.bonus.wrong'),
                    response.data.message,
                    'error'
                )
            }
            setTimeout(function () {
                $("form#frmcoupon").find('input').val('');
                $("form#frmcoupon").find('button').prop("disabled", false);
            }, 2000);

        } catch (err) {
            setTimeout(function () {
                $("form#frmcoupon").find('input').val('');
                $("form#frmcoupon").find('button').prop("disabled", false);
            }, 2000);
        }
    }
    getResponse(input);
});

$("form#frmcoupon2").on('click', 'button[type="button"]', function() {
    // event.preventDefault();
    var input = $("#frmcoupon2 :input[name='coupon']").val();
    let url = baseUrl + '/member/redeem';

    // console.log(url);
    const getResponse = async (input) => {
        try {

            console.log(url);
            const response = await axios.post(url,{ coupon : input});
            if (response.data.success) {
                Swal.fire(
                    trans('app.bonus.success'),
                    response.data.message,
                    'success'
                )

            } else {
                Swal.fire(
                    trans('app.bonus.wrong'),
                    response.data.message,
                    'error'
                )
            }
            setTimeout(function () {
                $("form#frmcoupon2").find('input').val('');
                $("form#frmcoupon2").find('button').prop("disabled", false);
            }, 2000);

        } catch (err) {
            setTimeout(function () {
                $("form#frmcoupon2").find('input').val('');
                $("form#frmcoupon2").find('button').prop("disabled", false);
            }, 2000);
        }
    }
    getResponse(input);
});

// $("form.qrscan").submit(async function (event) {
//     event.preventDefault(); // ป้องกันการ submit ปกติ
//
//     const form = $(this);
//     const amount = form.find("input[name='amount']").val(); // ดึงค่า amount จาก input
//     const button = form.find('button');
//     button.prop("disabled", true); // ปิดปุ่มป้องกันกดซ้ำ
//     let url = baseUrl + '/member/payment/deposit/create';
//     try {
//         const res = await axios.post(url, {
//             amount: amount
//         });
//
//         if (res.data.success) {
//             window.Toast.fire({
//                 icon: 'success',
//                 title: res.data.msg || 'ทำรายการสำเร็จ'
//             });
//
//             setTimeout(function () {
//                 window.open(res.data.url, '_blank');
//             }, 5000);
//         } else {
//             window.Toast.fire({
//                 icon: 'error',
//                 title: res.data.msg || 'เกิดข้อผิดพลาด'
//             });
//         }
//     } catch (error) {
//         window.Toast.fire({
//             icon: 'error',
//             title: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้'
//         });
//         console.error(error);
//     } finally {
//         // เปิดปุ่มอีกครั้งหลัง 0.1 วิ ไม่ว่าจะ success หรือ error
//         setTimeout(() => {
//             button.prop("disabled", false);
//         }, 100);
//     }
// });

// $("form.qrscan").on('click', '#btnqrsubmit', async function () {
//     event.preventDefault();
//
//     const form = $(this);
//     const amount = form.find("input[name='amount']").val();
//     const button = form.find('button[type="button"]');
//
//     button.prop("disabled", true);
//
//     // 🔥 จองหน้าต่างไว้ก่อน user gesture หายไป
//     let popupWindow = window.open('', '_blank');
//
//     try {
//         const res = await axios.post(form.attr('action'), {
//             amount: amount
//         });
//
//         if (res.data.success) {
//             window.Toast.fire({
//                 icon: 'success',
//                 title: res.data.msg || 'ทำรายการสำเร็จ'
//             });
//
//             setTimeout(() => {
//                 // ✅ เปลี่ยน URL หลังเปิด popup สำเร็จแล้ว
//                 if (popupWindow) {
//                     popupWindow.location.href = res.data.url;
//                 }
//             }, 5000);
//         } else {
//             window.Toast.fire({
//                 icon: 'error',
//                 title: res.data.msg || 'เกิดข้อผิดพลาด'
//             });
//
//             // ❌ ถ้า error ให้ปิด popup ที่เปิดไว้
//             if (popupWindow) {
//                 popupWindow.close();
//             }
//         }
//     } catch (error) {
//         window.Toast.fire({
//             icon: 'error',
//             title: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์'
//         });
//
//         if (popupWindow) {
//             popupWindow.close();
//         }
//         console.error(error);
//     } finally {
//         setTimeout(() => {
//             button.prop("disabled", false);
//         }, 100);
//     }
// });

$(document).on('click', '#btnqrsubmit', async function (event) {
    event.preventDefault();

    const button = $(this);
    const form = button.closest("form.qrscan");
    const amount = form.find("input[name='amount']").val();

    if (!amount || isNaN(amount)) {
        window.Toast.fire({
            icon: 'error',
            title: trans('app.withdraw.wrong_amount')
        });
        return;
    }

    button.prop("disabled", true);

    const submitRequest = async (force = false) => {
        try {
            const res = await axios.post(form.attr('action'), {
                amount: amount,
                force: force
            });

            if (res.data.success) {
                window.Toast.fire({
                    icon: 'success',
                    title: res.data.msg || 'ทำรายการสำเร็จ'
                });

                setTimeout(() => {
                    window.location.href = res.data.url;
                    // window.open(res.data.url, '_blank');
                }, 3000);

            } else if (res.data.status === 'has_pending') {
                // 🔥 พบว่ามีรายการเก่า → แสดง popup confirm
                const d = res.data.data;
                Swal.fire({
                    title: trans('app.topup.dup_topic'),
                    html: `
        <p>${trans('app.topup.amount')} <strong>${d.amount}</strong></p>
        <p>${trans('app.topup.amount_pay')} <strong>${d.payamount}</strong></p>
        <p>${trans('app.topup.txnid')} <strong>${d.txid}</strong></p>
        <p>${trans('app.topup.dup_detail')}</p>
        <p><small>${trans('app.topup.dup_detail_2')}</small></p>
    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: trans('app.topup.confirm_new'), // เพิ่มในภาษา
                    cancelButtonText: trans('app.topup.view_old'),    // เพิ่มในภาษา
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        // 🔁 กดยืนยัน → ส่งใหม่พร้อม force = true
                        submitRequest(true);
                    } else {
                        // 👁‍🗨 ไปหน้ารายการเดิม
                        window.location.href = d.url;
                        // window.open(d.url, '_blank');
                    }
                });

            } else {
                // ⚠️ กรณี error อื่น ๆ
                window.Toast.fire({
                    icon: 'error',
                    title: res.data.msg || 'เกิดข้อผิดพลาด'
                });
            }

        } catch (error) {
            window.Toast.fire({
                icon: 'error',
                title: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์'
            });
            console.error(error);
        } finally {
            setTimeout(() => {
                button.prop("disabled", false);
            }, 100);
        }
    };

    // 🔁 เรียกครั้งแรก
    submitRequest(false);
});

$(document).on('click', '#btnqrsubmit_khr', async function (event) {
    event.preventDefault();

    const button = $(this);
    const form = button.closest("form.qrscan");
    const amount = form.find("input[name='amount']").val();

    if (!amount || isNaN(amount)) {
        window.Toast.fire({
            icon: 'error',
            title: trans('app.withdraw.wrong_amount')
        });
        return;
    }

    button.prop("disabled", true);

    const submitRequest = async (force = false) => {
        try {
            const res = await axios.post(form.attr('action'), {
                amount: amount,
                force: force
            });

            if (res.data.success) {
                window.Toast.fire({
                    icon: 'success',
                    title: res.data.msg || 'ทำรายการสำเร็จ'
                });

                setTimeout(() => {
                    window.open(res.data.url, '_blank');
                }, 3000);

            } else if (res.data.status === 'has_pending') {
                // 🔥 พบว่ามีรายการเก่า → แสดง popup confirm
                const d = res.data.data;
                Swal.fire({
                    title: trans('app.topup.dup_topic'),
                    html: `
        <p>${trans('app.topup.amount')} <strong>${d.amount}</strong></p>
        <p>${trans('app.topup.amount_pay')} <strong>${d.payamount}</strong></p>
        <p>${trans('app.topup.txnid')} <strong>${d.txid}</strong></p>
        <p>${trans('app.topup.dup_detail')}</p>
        <p><small>${trans('app.topup.dup_detail_2')}</small></p>
    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: trans('app.topup.confirm_new'), // เพิ่มในภาษา
                    cancelButtonText: trans('app.topup.view_old'),    // เพิ่มในภาษา
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        // 🔁 กดยืนยัน → ส่งใหม่พร้อม force = true
                        submitRequest(true);
                    } else {
                        // 👁‍🗨 ไปหน้ารายการเดิม
                        window.open(d.url, '_blank');
                    }
                });

            } else {
                // ⚠️ กรณี error อื่น ๆ
                window.Toast.fire({
                    icon: 'error',
                    title: res.data.msg || 'เกิดข้อผิดพลาด'
                });
            }

        } catch (error) {
            window.Toast.fire({
                icon: 'error',
                title: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์'
            });
            console.error(error);
        } finally {
            setTimeout(() => {
                button.prop("disabled", false);
            }, 100);
        }
    };

    // 🔁 เรียกครั้งแรก
    submitRequest(false);
});


$('#depositModal').on('shown.bs.modal', function () {
    $('.-deposit-form-inner-wrapper').css('display', 'none');
    $('.btn-for-deposit').prop("disabled", false);
})


function topupSelect(type){
    $('.btn-for-deposit').prop("disabled", false);
    $('.-deposit-form-inner-wrapper').css('display', 'none');
    $('#'+type).css('display', 'block');
    // setTimeout(function(){ // Delay for Chrome
    //     $('.btn-for-deposit').prop("disabled", false);
    // }, 100);

}


$(".copybtn").click(function (event) {
    var $tempElement = $("<input>");
    $("body").append($tempElement);
    $tempElement.val($(this).closest(".copybtn").find("span").text()).select();
    document.execCommand("Copy");
    $tempElement.remove();
});

$(".copylink").click(function (event) {
    var copyText = document.getElementById("friendlink");
    copyText.select();
    copyText.setSelectionRange(0, 99999)
    document.execCommand("copy");

});


$('#topup_select').on('change', function () {
    if ($('#topup_select option:selected').val() === 'topup_bank') {
        // $('#acc_no_tw').prop('required',true);
        $('#topup_bank').css('display', 'block');
        $('#topup_qr').css('display', 'none');
        $('#topup_hengpay').css('display', 'none');
        $('#topup_luckypay').css('display', 'none');
        $('#topup_papayapay').css('display', 'none');
    }else if ($('#topup_select option:selected').val() === 'topup_qr') {
        $('#topup_bank').css('display', 'none');
        $('#topup_qr').css('display', 'block');
        $('#topup_hengpay').css('display', 'none');
        $('#topup_luckypay').css('display', 'none');
        $('#topup_papayapay').css('display', 'none');
    }else if ($('#topup_select option:selected').val() === 'topup_hengpay') {
        $('#topup_bank').css('display', 'none');
        $('#topup_qr').css('display', 'none');
        $('#topup_hengpay').css('display', 'block');
        $('#topup_luckypay').css('display', 'none');
        $('#topup_papayapay').css('display', 'none');
    }else if ($('#topup_select option:selected').val() === 'topup_papayapay') {
        $('#topup_bank').css('display', 'none');
        $('#topup_qr').css('display', 'none');
        $('#topup_hengpay').css('display', 'none');
        $('#topup_luckypay').css('display', 'none');
        $('#topup_papayapay').css('display', 'block');
    } else {
        $('#topup_bank').css('display', 'none');
        $('#topup_qr').css('display', 'none');
        $('#topup_hengpay').css('display', 'none');
        $('#topup_luckypay').css('display', 'block');
        $('#topup_papayapay').css('display', 'none');
    }
});

$(document).ready(function () {

    var clipboard = new ClipboardJS('.btncopy', {
        container: document.getElementById('depositModal')
    });

    $('.btnbalance').trigger('click');
    $('#topup_select').trigger('change');

    $("#changePasswordModal").on('show.bs.modal', function () {
        $(".modal").modal("hide");
    });
    $("#changePasswordModal").on('hide.bs.modal', function () {
        // $('.modal-backdrop').removeClass('show');
        $('.modal-backdrop').remove();
    });

    // $('.-profile-container').on('click', function () {
    //     $("#accountModal").modal("show");
    // });

    $('.js-adjust-amount-by-operator').on('click', function () {
        var dummy = 0;
        var operator = $(this).attr('data-operator');
        var amount = $(this).attr('data-value');
        var value = $('form.qrscan').find('input[name=amount]').val();
        if(value === ''){
            value = 0;
        }

        console.log('value '+ value);
        if(operator === '+'){
            console.log('+');
            console.log('amount '+ amount);
            dummy = (parseFloat(value) + parseFloat(amount));
        }else{
            console.log('-');
            console.log('amount '+ amount);
            dummy = (parseFloat(value) - parseFloat(amount));
            if(dummy < 0){
                dummy = 0;
            }
        }

        console.log('dummy '+ dummy);

        $('form.qrscan').find('input[name=amount]').val(dummy);
    });

    $('.-btn-select-amount').on('click', function () {
        $('.-btn-select-amount').removeClass('active');
        $(this).addClass('active');
        var amount = $(this).attr('data-amount');
        $('form.qrscan').find('input[name=amount]').val(amount);
    });


});
