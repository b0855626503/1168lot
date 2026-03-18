document.addEventListener("DOMContentLoaded", function () {
    $('.close-add-to-home').on('click', function () {

        $(".add-home-screen-container").remove();
    });



    $('.btn_copy_bankcode').on('click', function () {

        var textbank = $(this).find("b").text();
        navigator.clipboard.writeText(textbank);

        $(".myAlert-top").show();
        setTimeout(function () {
            $(".myAlert-top").hide();
        }, 1500);
    });

    $(".notify-popup-wrapper").addClass('active');
    $('.close-notify,.btn-allow.allow-notify,.btn-not-allow').on('click', function () {

        $(".notify-popup-wrapper").removeClass('active');
    });


    var swiper = new Swiper("#lastSlide", {
        slidesPerView: 'auto',
        spaceBetween: 15,
        freeMode: true,
    });

});