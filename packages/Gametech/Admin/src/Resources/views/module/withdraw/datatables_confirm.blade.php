@if($status == 0 && $emp_code == 0)
    <button class="btn btn-xs btn-secondary icon-only" onclick="editModal({{ $code }})"><i
            class="fas fa-check"></i></button>
@endif
@if($status == 0 && $emp_code != 0)
    <button class="btn btn-xs btn-secondary icon-only" onclick="fixModal({{ $code }})"><i
            class="fas fa-check-double"></i></button>
@endif
