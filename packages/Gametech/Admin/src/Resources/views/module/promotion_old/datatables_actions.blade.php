<button type="button" class="btn-xs btn  btn-info" onclick="editModal({{ $code }})"><i class="fa fa-edit"></i> แก้ไข
</button>
@if($admin = auth()->guard('admin')->user()->superadmin === 'Y')
    <button type="button" class="btn-xs btn  btn-danger" onclick="delModal({{ $code }})"><i class="fa fa-trash"></i> ลบ
    </button>
@endif
