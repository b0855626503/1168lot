@if($gen === 'Y')
    <button type="button" class="btn-xs btn  btn-info" onclick="listModal({{ $code }})"><i class="fa fa-list-ul"></i> LIST
    </button>
@else
    <button type="button" class="btn-xs btn  btn-success" onclick="genModal({{ $code }})"><i class="fa fa-plus"></i> GEN
    </button>
@endif
{{--<button type="button" class="btn-xs btn  btn-danger" onclick="delModal({{ $code }})"><i class="fa fa-trash"></i> ลบ--}}
{{--</button>--}}
