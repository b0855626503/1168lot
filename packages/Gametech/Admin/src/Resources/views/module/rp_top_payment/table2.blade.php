{!! $wdDataTable->table(['id'=>'wdDataTable', 'width' => '100%', 'class' => 'table table-striped table-sm'],true) !!}


@push('scripts')
{{--    {!! $dataTable->scripts() !!}--}}
    {!! $wdDataTable->scripts() !!}
@endpush
