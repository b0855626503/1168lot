<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>HTML 5 Boilerplate</title>
</head>
<body onload="closethisasap();">
<form id="form" method="POST" action="{{ config('app.pompay_url_payment') }}" style="display:none">
    <input name="clientId" type="hidden" value="{{ $pompay['clientId'] }}"/>
    <input name="transactionId" type="text" value="{{ $pompay['transactionId'] }}"/>
    <input name="custName" type="text" value="{{ $pompay['custName'] }}"/>
    <input name="custSecondaryName" type="text" value="{{ $pompay['custSecondaryName'] }}"/>
    <input name="custBank" type="text" value="{{ $pompay['custBank'] }}"/>
    <input name="custMobile" type="text" value="{{ $pompay['custMobile'] }}"/>
    <input name="custEmail" type="text" value="{{ $pompay['custEmail'] }}"/>
    <input name="amount" type="number" value="{{ $pompay['amount'] }}"/>
    <input name="returnUrl" type="text" value="{{ $pompay['returnUrl'] }}"/>
    <input name="callbackUrl" type="text" value="{{ $pompay['callbackUrl'] }}"/>
    <input name="paymentMethod" type="text" value="{{ $pompay['paymentMethod'] }}"/>
    <input name="bankAcc" type="text" value="{{ $pompay['bankAcc'] }}"/>
    <input name="hashVal" type="hidden" value="{{ $pompay['hashVal'] }}"/>
    <button type="submit">Deposit</button>
</form>
<script>
    function closethisasap() {
        document.forms["form"].submit();
    }
</script>
</body>
</html>

