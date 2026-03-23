<div class="d-flex flex-wrap justify-content-center">
    <button type="button" class="btn btn-info btn-xs mr-1 mb-1" onclick="editModal({{ $id }})">
        <i class="fas fa-edit"></i> แก้ไข
    </button>
    @if($status === 'draft' || $status === 'closed')
        <button type="button" class="btn btn-primary btn-xs mr-1 mb-1" onclick="openDraw({{ $id }})">
            <i class="fas fa-play"></i> เปิดรับ
        </button>
    @endif
    @if($status === 'open')
        <button type="button" class="btn btn-secondary btn-xs mr-1 mb-1" onclick="closeDraw({{ $id }})">
            <i class="fas fa-stop"></i> ปิดรับ
        </button>
    @endif
    @if($status === 'closed')
        <button type="button" class="btn btn-success btn-xs mr-1 mb-1" onclick="settleModal({{ $id }})">
            <i class="fas fa-check-circle"></i> ประกาศผล
        </button>
    @endif
</div>
