@if(!$is_published)
    <button type="button" class="btn btn-info btn-xs" onclick="editNavbarModal({{ $id }})">
        <i class="fas fa-edit"></i> แก้ไข
    </button>
    <button type="button" class="btn btn-success btn-xs" onclick="publishNavbar({{ $id }})">
        <i class="fas fa-upload"></i> Publish
    </button>
    <button type="button" class="btn btn-danger btn-xs" onclick="deleteNavbar({{ $id }})">
        <i class="fas fa-trash"></i> ลบ
    </button>
@else
    <button type="button" class="btn btn-warning btn-xs" onclick="unpublishNavbar({{ $id }})" @if(!$is_active) disabled @endif>
        <i class="fas fa-times"></i> Unpublish
    </button>
@endif
