var swiper  = new Swiper("#regForm", {
    allowTouchMove: false,
    spaceBetween: 100,
    pagination: {
        el: ".swiper-pagination",
        clickable: false,
        type: 'progressbar'
    },
    navigation: {
        nextEl: ".nextregis",
        prevEl: ".preregis",
    },
    autoHeight: true,
});


$('.nextregis,.preregis').on('click', function () {
    if ($(".regis02,.regis03").hasClass("swiper-slide-active")) {
        $(".preregis").show();
    } else{
        $(".preregis").hide();
    }
    if (!$(".regis03").hasClass("swiper-slide-active")) {
        $(".nextregis").show();
        $(".regisbtn").hide();
    } else{
        $(".nextregis").hide();
        $(".regisbtn").show();
    }

});
