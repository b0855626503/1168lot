@php
    $canAutoResultSources = bouncer()->hasPermission('lotto_settings.auto_result_sources');
@endphp

<span class="d-inline-flex align-items-center">
    <button type="button" class="btn btn-info btn-xs" onclick="editModal({{ $id }})">
        <i class="fas fa-edit"></i> แก้ไข
    </button>
    @if($canAutoResultSources)
        <button type="button" class="btn btn-warning btn-xs ml-1" onclick='openAutoSourcesModal({{ $id }}, @json((string) ($market_name ?? "")))'>
            <i class="fas fa-bolt"></i> Auto
        </button>
    @endif
</span>
