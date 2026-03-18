// Copy---------------------------------------------------------
$(document).ready(function () {
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

    if ($('#account-actions-mobile')[0]) {
        $('.copyright').addClass('user');
    }

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

    var acc = document.getElementsByClassName("accordion");
    var i;

    for (i = 0; i < acc.length; i++) {
        acc[i].addEventListener("click", function () {
            this.classList.toggle("active");
            var panel = this.nextElementSibling;
            if (panel.style.maxHeight) {
                panel.style.maxHeight = null;
            } else {
                panel.style.maxHeight = panel.scrollHeight + "px";
            }
        });
    }
});

function copylink() {
    $(".myAlert-top").show();
    setTimeout(function () {
        $(".myAlert-top").hide();
    }, 1500);
}

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
