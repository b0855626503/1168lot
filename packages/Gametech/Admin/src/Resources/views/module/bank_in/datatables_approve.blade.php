@if($status == 0)
    <button class="btn btn-xs btn-warning icon-only" onclick="approveModal({{ $code }})"><i class="fas fa-check"></i>
    </button>
@endif
