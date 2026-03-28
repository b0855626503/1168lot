@php
    $canAutoResultSources = bouncer()->hasPermission('lotto_settings.auto_result_sources');
@endphp

<button type="button" class="btn btn-info btn-xs" onclick="editModal({{ $id }})">
    <i class="fas fa-edit"></i> แก้ไข
</button>
@if($canAutoResultSources)
    <button type="button" class="btn btn-warning btn-xs mt-1" onclick="openAutoSourcesModal({{ $id }})">
        <i class="fas fa-bolt"></i> Auto
    </button>
@endif
