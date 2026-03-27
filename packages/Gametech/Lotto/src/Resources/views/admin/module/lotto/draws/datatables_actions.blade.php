@php
    $canEdit = bouncer()->hasPermission('lotto_draws.edit');
    $canOpen = bouncer()->hasPermission('lotto_draws.open');
    $canClose = bouncer()->hasPermission('lotto_draws.close');
    $canSettle = bouncer()->hasPermission('lotto_draws.settle');
    $canDryRun = bouncer()->hasPermission('lotto_draws.auto_result_test_fetch');
    $canRetry = bouncer()->hasPermission('lotto_draws.auto_result_manual_retry');
    $canViewLogs = bouncer()->hasPermission('lotto_draws.auto_result_metrics');
@endphp

<div class="d-flex flex-wrap justify-content-center">
    @if($status !== 'resulted' && $canEdit)
        <button type="button" class="btn btn-info btn-xs btn-block mr-1 mb-1" onclick="editModal({{ $id }})">
            <i class="fas fa-edit"></i> แก้ไข
        </button>
    @endif

    @if(($status === 'draft' || $status === 'closed') && $canOpen)
        <button type="button" class="btn btn-primary btn-xs btn-block mr-1 mb-1" onclick="openDraw({{ $id }})">
            <i class="fas fa-play"></i> เปิดรับ
        </button>
    @endif

    @if($status === 'open' && $canClose)
        <button type="button" class="btn btn-secondary btn-xs btn-block mr-1 mb-1" onclick="closeDraw({{ $id }})">
            <i class="fas fa-stop"></i> ปิดรับ
        </button>
    @endif

    @if($status === 'closed' && $canSettle)
        <button type="button" class="btn btn-success btn-xs btn-block mr-1 mb-1" onclick="settleModal({{ $id }})">
            <i class="fas fa-check-circle"></i> ประกาศผล
        </button>
    @endif

    @if(($status === 'closed' || $status === 'resulted') && $canDryRun)
        <button type="button" class="btn btn-warning btn-xs btn-block mr-1 mb-1" onclick="runAutoResultTestFetch({{ $id }})">
            <i class="fas fa-vial"></i> Dry-run
        </button>
    @endif

    @if($status === 'closed' && $canRetry)
        <button type="button" class="btn btn-dark btn-xs btn-block mr-1 mb-1" onclick="runAutoResultManualRetry({{ $id }})">
            <i class="fas fa-redo"></i> Retry
        </button>
    @endif

    @if(($status === 'closed' || $status === 'resulted') && $canViewLogs)
        <button type="button" class="btn btn-secondary btn-xs btn-block mr-1 mb-1" onclick="showAutoResultLogs({{ $id }})">
            <i class="fas fa-stream"></i> Logs
        </button>
    @endif
</div>
