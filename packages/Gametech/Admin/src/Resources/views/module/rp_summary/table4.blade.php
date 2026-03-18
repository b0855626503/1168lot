{!! $lgDataTable->table(['id'=>'lgDataTable', 'width' => '100%', 'class' => 'table table-striped table-sm'],true) !!}


@push('scripts')
{{--    {!! $dataTable->scripts() !!}--}}
    {!! $lgDataTable->scripts() !!}
@endpush
