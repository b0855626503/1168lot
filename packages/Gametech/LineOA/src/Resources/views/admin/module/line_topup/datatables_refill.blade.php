@if($status == 0 && $txid == '')
    <button class="btn btn-xs btn-secondary icon-only" onclick="editModal({{ $code }})"><i class="fas fa-plus"></i>
    </button>
@elseif($status == 0 && $txid != '' && $autocheck != 'W')
    <button class="btn btn-xs btn-info icon-only" onclick="approveModal({{ $code }})"><i class="fas fa-check"></i>
    </button>
@endif
