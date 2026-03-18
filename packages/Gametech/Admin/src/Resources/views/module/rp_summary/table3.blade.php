{!! $stDataTable->table(['id'=>'stDataTable', 'width' => '100%', 'class' => 'table table-striped table-sm'],true) !!}


@push('scripts')
{{--    {!! $dataTable->scripts() !!}--}}
    {!! $stDataTable->scripts() !!}
@endpush
