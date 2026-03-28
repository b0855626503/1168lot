@php
    $canEdit = bouncer()->hasPermission('lotto_draws.edit');
    $canSettle = bouncer()->hasPermission('lotto_draws.settle');
    $canRetry = bouncer()->hasPermission('lotto_draws.auto_result_manual_retry');
@endphp

<div class="d-flex flex-wrap justify-content-center">
    @if($status !== 'resulted' && $canEdit)
        <button type="button" class="btn btn-info btn-xs  mr-1 mb-1" onclick="editModal({{ $id }})">
            <i class="fas fa-edit"></i> แก้ไข
        </button>
    @endif

    @if($status === 'closed' && ($canSettle || $canRetry))
        <button type="button" class="btn btn-success btn-xs mr-1 mb-1" onclick="settleModal({{ $id }})">
            <i class="fas fa-check-circle"></i> ออกผล
        </button>
    @endif
</div>
