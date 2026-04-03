@php
    $canEdit = bouncer()->hasPermission('lotto_draws.edit');
    $canSettle = bouncer()->hasPermission('lotto_draws.settle');
    $canRetry = bouncer()->hasPermission('lotto_draws.auto_result_manual_retry');
    $canCancelAllRefund = bouncer()->hasPermission('lotto_draws.settle') || bouncer()->hasPermission('lotto_settings.draws.settle');
@endphp

<div class="d-flex flex-wrap justify-content-center">
    @if($status !== 'resulted' && $canEdit)
        <button type="button" class="btn btn-info btn-xs btn-block  mr-1 mb-1" onclick="editModal({{ $id }})">
            <i class="fas fa-edit"></i> แก้ไข
        </button>
    @endif

    @if($status === 'closed' && ($canSettle || $canRetry))
        <button type="button" class="btn btn-success btn-block btn-xs mr-1 mb-1" onclick="settleModal({{ $id }})">
            <i class="fas fa-check-circle"></i> ออกผล
        </button>
    @endif

    @if(!empty($can_cancel_all_refund) && $canCancelAllRefund)
        <button type="button" class="btn btn-danger btn-block btn-xs mr-1 mb-1" onclick="cancelAllTicketsAndRefund({{ $id }})">
            <i class="fas fa-undo-alt"></i> ยกเลิกโพย+คืนเงิน
        </button>
    @endif
</div>
