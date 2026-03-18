<!-- Refer Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('refer_code', 'Refer Code:') !!}
    {!! Form::number('refer_code', null, ['class' => 'form-control']) !!}
</div>

<!-- Bank Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('bank_code', 'Bank Code:') !!}
    {!! Form::number('bank_code', null, ['class' => 'form-control']) !!}
</div>

<!-- Upline Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('upline_code', 'Upline Code:') !!}
    {!! Form::number('upline_code', null, ['class' => 'form-control']) !!}
</div>

<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control','maxlength' => 191,'maxlength' => 191]) !!}
</div>

<!-- Firstname Field -->
<div class="form-group col-sm-6">
    {!! Form::label('firstname', 'Firstname:') !!}
    {!! Form::text('firstname', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Lastname Field -->
<div class="form-group col-sm-6">
    {!! Form::label('lastname', 'Lastname:') !!}
    {!! Form::text('lastname', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- User Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('user_name', 'User Name:') !!}
    {!! Form::text('user_name', null, ['class' => 'form-control','maxlength' => 50,'maxlength' => 50]) !!}
</div>

<!-- User Pass Field -->
<div class="form-group col-sm-6">
    {!! Form::label('user_pass', 'User Pass:') !!}
    {!! Form::text('user_pass', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Password Field -->
<div class="form-group col-sm-6">
    {!! Form::label('password', 'Password:') !!}
    {!! Form::password('password', ['class' => 'form-control','maxlength' => 191,'maxlength' => 191]) !!}
</div>

<!-- User Pin Field -->
<div class="form-group col-sm-6">
    {!! Form::label('user_pin', 'User Pin:') !!}
    {!! Form::text('user_pin', null, ['class' => 'form-control','maxlength' => 6,'maxlength' => 6]) !!}
</div>

<!-- Check Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('check_status', 'Check Status:') !!}
    {!! Form::text('check_status', null, ['class' => 'form-control']) !!}
</div>

<!-- Acc No Field -->
<div class="form-group col-sm-6">
    {!! Form::label('acc_no', 'Acc No:') !!}
    {!! Form::text('acc_no', null, ['class' => 'form-control','maxlength' => 15,'maxlength' => 15]) !!}
</div>

<!-- Acc Check Field -->
<div class="form-group col-sm-6">
    {!! Form::label('acc_check', 'Acc Check:') !!}
    {!! Form::text('acc_check', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Acc Bay Field -->
<div class="form-group col-sm-6">
    {!! Form::label('acc_bay', 'Acc Bay:') !!}
    {!! Form::text('acc_bay', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Acc Kbank Field -->
<div class="form-group col-sm-6">
    {!! Form::label('acc_kbank', 'Acc Kbank:') !!}
    {!! Form::text('acc_kbank', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Tel Field -->
<div class="form-group col-sm-6">
    {!! Form::label('tel', 'Tel:') !!}
    {!! Form::text('tel', null, ['class' => 'form-control','maxlength' => 200,'maxlength' => 200]) !!}
</div>

<!-- Birth Day Field -->
<div class="form-group col-sm-6">
    {!! Form::label('birth_day', 'Birth Day:') !!}
    {!! Form::text('birth_day', null, ['class' => 'form-control','id'=>'birth_day']) !!}
</div>

@push('scripts')
    <script type="text/javascript">
        $('#birth_day').datetimepicker({
            format: 'YYYY-MM-DD HH:mm:ss',
            useCurrent: true,
            sideBySide: true
        })
    </script>
@endpush

<!-- Age Field -->
<div class="form-group col-sm-6">
    {!! Form::label('age', 'Age:') !!}
    <label class="checkbox-inline">
        {!! Form::hidden('age', 0) !!}
        {!! Form::checkbox('age', '1', null) !!}
    </label>
</div>


<!-- Lineid Field -->
<div class="form-group col-sm-6">
    {!! Form::label('lineid', 'Lineid:') !!}
    {!! Form::text('lineid', null, ['class' => 'form-control','maxlength' => 20,'maxlength' => 20]) !!}
</div>

<!-- Confirm Field -->
<div class="form-group col-sm-6">
    {!! Form::label('confirm', 'Confirm:') !!}
    {!! Form::text('confirm', null, ['class' => 'form-control']) !!}
</div>

<!-- Refer Field -->
<div class="form-group col-sm-6">
    {!! Form::label('refer', 'Refer:') !!}
    {!! Form::text('refer', null, ['class' => 'form-control','maxlength' => 10,'maxlength' => 10]) !!}
</div>

<!-- Point Deposit Field -->
<div class="form-group col-sm-6">
    {!! Form::label('point_deposit', 'Point Deposit:') !!}
    {!! Form::number('point_deposit', null, ['class' => 'form-control']) !!}
</div>

<!-- Count Deposit Field -->
<div class="form-group col-sm-6">
    {!! Form::label('count_deposit', 'Count Deposit:') !!}
    {!! Form::number('count_deposit', null, ['class' => 'form-control']) !!}
</div>

<!-- Diamond Field -->
<div class="form-group col-sm-6">
    {!! Form::label('diamond', 'Diamond:') !!}
    {!! Form::number('diamond', null, ['class' => 'form-control']) !!}
</div>

<!-- Upline Field -->
<div class="form-group col-sm-6">
    {!! Form::label('upline', 'Upline:') !!}
    {!! Form::text('upline', null, ['class' => 'form-control','maxlength' => 10,'maxlength' => 10]) !!}
</div>

<!-- Credit Field -->
<div class="form-group col-sm-6">
    {!! Form::label('credit', 'Credit:') !!}
    {!! Form::number('credit', null, ['class' => 'form-control']) !!}
</div>

<!-- Balance Field -->
<div class="form-group col-sm-6">
    {!! Form::label('balance', 'Balance:') !!}
    {!! Form::number('balance', null, ['class' => 'form-control']) !!}
</div>

<!-- Balance Free Field -->
<div class="form-group col-sm-6">
    {!! Form::label('balance_free', 'Balance Free:') !!}
    {!! Form::number('balance_free', null, ['class' => 'form-control']) !!}
</div>

<!-- Date Regis Field -->
<div class="form-group col-sm-6">
    {!! Form::label('date_regis', 'Date Regis:') !!}
    {!! Form::text('date_regis', null, ['class' => 'form-control date','id'=>'date_regis']) !!}
</div>

@push('scripts')
    <script type="text/javascript">
        $('#date_regis').datetimepicker({
            format: 'YYYY-MM-DD HH:mm:ss',
            useCurrent: true,
            sideBySide: true
        })
    </script>
@endpush

<!-- Pro Field -->
<div class="form-group col-sm-6">
    {!! Form::label('pro', 'Pro:') !!}
    <label class="checkbox-inline">
        {!! Form::hidden('pro', 0) !!}
        {!! Form::checkbox('pro', '1', null) !!}
    </label>
</div>


<!-- Status Pro Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status_pro', 'Status Pro:') !!}
    <label class="checkbox-inline">
        {!! Form::hidden('status_pro', 0) !!}
        {!! Form::checkbox('status_pro', '1', null) !!}
    </label>
</div>


<!-- Acc Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('acc_status', 'Acc Status:') !!}
    {!! Form::text('acc_status', null, ['class' => 'form-control']) !!}
</div>

<!-- Otp Field -->
<div class="form-group col-sm-6">
    {!! Form::label('otp', 'Otp:') !!}
    {!! Form::text('otp', null, ['class' => 'form-control','maxlength' => 6,'maxlength' => 6]) !!}
</div>

<!-- Pic Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('pic_id', 'Pic Id:') !!}
    {!! Form::number('pic_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Scode Field -->
<div class="form-group col-sm-6">
    {!! Form::label('scode', 'Scode:') !!}
    {!! Form::number('scode', null, ['class' => 'form-control']) !!}
</div>

<!-- Ip Field -->
<div class="form-group col-sm-6">
    {!! Form::label('ip', 'Ip:') !!}
    {!! Form::text('ip', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Lastlogin Field -->
<div class="form-group col-sm-6">
    {!! Form::label('lastlogin', 'Lastlogin:') !!}
    {!! Form::text('lastlogin', null, ['class' => 'form-control','id'=>'lastlogin']) !!}
</div>

@push('scripts')
    <script type="text/javascript">
        $('#lastlogin').datetimepicker({
            format: 'YYYY-MM-DD HH:mm:ss',
            useCurrent: true,
            sideBySide: true
        })
    </script>
@endpush

<!-- Remark Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('remark', 'Remark:') !!}
    {!! Form::textarea('remark', null, ['class' => 'form-control']) !!}
</div>

<!-- Sms Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('sms_status', 'Sms Status:') !!}
    {!! Form::text('sms_status', null, ['class' => 'form-control','maxlength' => 200,'maxlength' => 200]) !!}
</div>

<!-- Promotion Field -->
<div class="form-group col-sm-6">
    {!! Form::label('promotion', 'Promotion:') !!}
    {!! Form::text('promotion', null, ['class' => 'form-control']) !!}
</div>

<!-- Pro Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('pro_status', 'Pro Status:') !!}
    {!! Form::text('pro_status', null, ['class' => 'form-control']) !!}
</div>

<!-- Hottime2 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('hottime2', 'Hottime2:') !!}
    {!! Form::text('hottime2', null, ['class' => 'form-control']) !!}
</div>

<!-- Hottime3 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('hottime3', 'Hottime3:') !!}
    {!! Form::text('hottime3', null, ['class' => 'form-control']) !!}
</div>

<!-- Hottime4 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('hottime4', 'Hottime4:') !!}
    {!! Form::text('hottime4', null, ['class' => 'form-control']) !!}
</div>

<!-- Prefix Field -->
<div class="form-group col-sm-6">
    {!! Form::label('prefix', 'Prefix:') !!}
    {!! Form::text('prefix', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

<!-- Gender Field -->
<div class="form-group col-sm-6">
    {!! Form::label('gender', 'Gender:') !!}
    {!! Form::text('gender', null, ['class' => 'form-control']) !!}
</div>

<!-- Deposit Field -->
<div class="form-group col-sm-6">
    {!! Form::label('deposit', 'Deposit:') !!}
    {!! Form::number('deposit', null, ['class' => 'form-control']) !!}
</div>

<!-- Allget Downline Field -->
<div class="form-group col-sm-6">
    {!! Form::label('allget_downline', 'Allget Downline:') !!}
    {!! Form::number('allget_downline', null, ['class' => 'form-control']) !!}
</div>

<!-- Aff Get Field -->
<div class="form-group col-sm-6">
    {!! Form::label('aff_get', 'Aff Get:') !!}
    {!! Form::text('aff_get', null, ['class' => 'form-control']) !!}
</div>

<!-- Oldmember Field -->
<div class="form-group col-sm-6">
    {!! Form::label('oldmember', 'Oldmember:') !!}
    {!! Form::text('oldmember', null, ['class' => 'form-control']) !!}
</div>

<!-- Freecredit Field -->
<div class="form-group col-sm-6">
    {!! Form::label('freecredit', 'Freecredit:') !!}
    {!! Form::text('freecredit', null, ['class' => 'form-control']) !!}
</div>

<!-- User Delay Field -->
<div class="form-group col-sm-6">
    {!! Form::label('user_delay', 'User Delay:') !!}
    {!! Form::number('user_delay', null, ['class' => 'form-control']) !!}
</div>

<!-- Session Ip Field -->
<div class="form-group col-sm-6">
    {!! Form::label('session_ip', 'Session Ip:') !!}
    {!! Form::text('session_ip', null, ['class' => 'form-control','maxlength' => 200,'maxlength' => 200]) !!}
</div>

<!-- Session Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('session_id', 'Session Id:') !!}
    {!! Form::text('session_id', null, ['class' => 'form-control','maxlength' => 200,'maxlength' => 200]) !!}
</div>

<!-- Session Page Field -->
<div class="form-group col-sm-6">
    {!! Form::label('session_page', 'Session Page:') !!}
    {!! Form::text('session_page', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

<!-- Session Limit Field -->
<div class="form-group col-sm-6">
    {!! Form::label('session_limit', 'Session Limit:') !!}
    {!! Form::text('session_limit', null, ['class' => 'form-control','id'=>'session_limit']) !!}
</div>

@push('scripts')
    <script type="text/javascript">
        $('#session_limit').datetimepicker({
            format: 'YYYY-MM-DD HH:mm:ss',
            useCurrent: true,
            sideBySide: true
        })
    </script>
@endpush

<!-- Payment Task Field -->
<div class="form-group col-sm-6">
    {!! Form::label('payment_task', 'Payment Task:') !!}
    {!! Form::text('payment_task', null, ['class' => 'form-control','maxlength' => 20,'maxlength' => 20]) !!}
</div>

<!-- Payment Token Field -->
<div class="form-group col-sm-6">
    {!! Form::label('payment_token', 'Payment Token:') !!}
    {!! Form::text('payment_token', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

<!-- Payment Level Field -->
<div class="form-group col-sm-6">
    {!! Form::label('payment_level', 'Payment Level:') !!}
    {!! Form::number('payment_level', null, ['class' => 'form-control']) !!}
</div>

<!-- Payment Game Field -->
<div class="form-group col-sm-6">
    {!! Form::label('payment_game', 'Payment Game:') !!}
    {!! Form::number('payment_game', null, ['class' => 'form-control']) !!}
</div>

<!-- Payment Pro Field -->
<div class="form-group col-sm-6">
    {!! Form::label('payment_pro', 'Payment Pro:') !!}
    {!! Form::number('payment_pro', null, ['class' => 'form-control']) !!}
</div>

<!-- Payment Balance Field -->
<div class="form-group col-sm-6">
    {!! Form::label('payment_balance', 'Payment Balance:') !!}
    {!! Form::number('payment_balance', null, ['class' => 'form-control']) !!}
</div>

<!-- Payment Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('payment_amount', 'Payment Amount:') !!}
    {!! Form::number('payment_amount', null, ['class' => 'form-control']) !!}
</div>

<!-- Payment Limit Field -->
<div class="form-group col-sm-6">
    {!! Form::label('payment_limit', 'Payment Limit:') !!}
    {!! Form::text('payment_limit', null, ['class' => 'form-control','id'=>'payment_limit']) !!}
</div>

@push('scripts')
    <script type="text/javascript">
        $('#payment_limit').datetimepicker({
            format: 'YYYY-MM-DD HH:mm:ss',
            useCurrent: true,
            sideBySide: true
        })
    </script>
@endpush

<!-- Payment Delay Field -->
<div class="form-group col-sm-6">
    {!! Form::label('payment_delay', 'Payment Delay:') !!}
    {!! Form::text('payment_delay', null, ['class' => 'form-control','id'=>'payment_delay']) !!}
</div>

@push('scripts')
    <script type="text/javascript">
        $('#payment_delay').datetimepicker({
            format: 'YYYY-MM-DD HH:mm:ss',
            useCurrent: true,
            sideBySide: true
        })
    </script>
@endpush

<!-- Payment Mac Field -->
<div class="form-group col-sm-6">
    {!! Form::label('payment_mac', 'Payment Mac:') !!}
    {!! Form::text('payment_mac', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Payment Browser Field -->
<div class="form-group col-sm-6">
    {!! Form::label('payment_browser', 'Payment Browser:') !!}
    {!! Form::text('payment_browser', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Payment Device Field -->
<div class="form-group col-sm-6">
    {!! Form::label('payment_device', 'Payment Device:') !!}
    {!! Form::text('payment_device', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Enable Field -->
<div class="form-group col-sm-6">
    {!! Form::label('enable', 'Enable:') !!}
    {!! Form::text('enable', null, ['class' => 'form-control']) !!}
</div>

<!-- User Create Field -->
<div class="form-group col-sm-6">
    {!! Form::label('user_create', 'User Create:') !!}
    {!! Form::text('user_create', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- User Update Field -->
<div class="form-group col-sm-6">
    {!! Form::label('user_update', 'User Update:') !!}
    {!! Form::text('user_update', null, ['class' => 'form-control','maxlength' => 100,'maxlength' => 100]) !!}
</div>

<!-- Remember Token Field -->
<div class="form-group col-sm-6">
    {!! Form::label('remember_token', 'Remember Token:') !!}
    {!! Form::text('remember_token', null, ['class' => 'form-control','maxlength' => 191,'maxlength' => 191]) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('wallet.members.index') }}" class="btn btn-secondary">Cancel</a>
</div>
