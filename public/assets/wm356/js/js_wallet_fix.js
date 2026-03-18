$(document).ready(function () {


    $('#mobilebtn').on('click', function () {
        $('div.js-ez-logged-sidebar').addClass('-open');
        $('div.x-menu-account-list-sidebar').addClass('-open');
    });

    $('.js-close-account-sidebar').on('click', function () {
        $('div.js-ez-logged-sidebar').removeClass('-open');
        $('div.x-menu-account-list-sidebar').removeClass('-open');
    });

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
