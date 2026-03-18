@if($gen === 'N')
    <button type="button" class="btn-xs btn  btn-info" onclick="editModal({{ $code }})"><i class="fa fa-edit"></i> Edit
    </button>
@else
    <button type="button" class="btn-xs btn  btn-primary" onclick="ViewModal({{ $code }})"><i class="fa fa-eye"></i> View
    </button>
@endif
{{--<button type="button" class="btn-xs btn  btn-danger" onclick="delModal({{ $code }})"><i class="fa fa-trash"></i> ลบ--}}
{{--</button>--}}
