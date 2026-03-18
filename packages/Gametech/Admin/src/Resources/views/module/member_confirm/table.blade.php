@section('css')
    @include('admin::layouts.datatables_css')
@endsection

{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}

@section('script')
    @include('admin::layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    @include('admin::layouts.script')
@endsection

