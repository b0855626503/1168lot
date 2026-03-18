@if($status == 0  && $emp_code == 0)
    <button class="btn btn-xs btn-danger icon-only" onclick="delModal({{ $code }})"><i class="fas fa-trash"></i>
    </button>
@endif
