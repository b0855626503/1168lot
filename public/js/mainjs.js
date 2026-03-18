var swiper = new Swiper(".alertslide", {
    lazy: true,
    effect: "fade",
    autoplay: {
        delay: 5000,
        disableOnInteraction: false,
    },
    pagination: {
        el: ".swiper-pagination",
        clickable: true,
    },
    navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
    },
});





// LOGIN FOOTER

if ($('#account-actions-mobile')[0]) {

    $('.copyright').addClass('user');
}

// LOGIN FOOTER


// Copy---------------------------------------------------------
$(document).ready(function(){
    $(".copybtn").click(function(event){
        var $tempElement = $("<input>");
        $("body").append($tempElement);
        $tempElement.val($(this).closest(".copybtn").find("span").text()).select();
        document.execCommand("Copy");
        $tempElement.remove();

    });
});
function copylink(){
    $(".myAlert-top").show();
    setTimeout(function(){
        $(".myAlert-top").hide();
    }, 1500);
}

$(document).on('click','.searchbychar', function(event) {
    event.preventDefault();
    var target = "#" + this.getAttribute('data-target');
    $('html, body').animate({
        scrollTop: $(target).offset().top
    }, 2000);
});


$(".copylink").click(function(event){
    var copyText = document.getElementById("friendlink");
    copyText.select();
    copyText.setSelectionRange(0, 99999)
    document.execCommand("copy");

});


// Copy---------------------------------------------------------

// Promotions
var acc = document.getElementsByClassName("accordion");
var i;

for (i = 0; i < acc.length; i++) {
    acc[i].addEventListener("click", function() {
        this.classList.toggle("active");
        var panel = this.nextElementSibling;
        if (panel.style.maxHeight) {
            panel.style.maxHeight = null;
        } else {
            panel.style.maxHeight = panel.scrollHeight + "px";
        }
    });
}

// Promotions



function opentabgame(evt, cityName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(cityName).style.display = "block";
    evt.currentTarget.className += " active";
}


var swiperOptions = {
    loop: true,
    autoplay: {
        delay: 1,
        disableOnInteraction: false
    },
    slidesPerView: 'auto',
    speed: 2000,
    grabCursor: true,
    mousewheelControl: true,
    keyboardControl: true,
};

var swiper = new Swiper(".swiper-container-free-mode", swiperOptions);


$('.-balance-container').on('click', function () {
    $('.reloadcredit').addClass('fa-spin');
    setTimeout(function() {
        $('.reloadcredit').removeClass('fa-spin');
    }, 2000);
});

$('.overlaysidebar').on('click', function () {
    $('.insidebarleft').toggleClass('active');
    $('.overlaysidebar').toggleClass('active');
    $('.sidebarCollapse').toggleClass('open');
});
$('.sidebarCollapse').on('click', function () {
    $('.insidebarleft').toggleClass('active');
    $('.overlaysidebar').toggleClass('active');
    $('.sidebarCollapse').toggleClass('open');
});

