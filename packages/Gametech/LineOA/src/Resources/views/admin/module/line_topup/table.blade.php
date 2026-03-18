@section('css')
    @include('admin::layouts.datatables_css')
@endsection


{!! $dataTable->table(['id' => 'topuptable','width' => '100%', 'class' => 'table table-striped table-sm']) !!}


@push('scripts')
    @include('admin::layouts.datatables_js')

    {!! $dataTable->scripts() !!}
    <script>
        $(function () {
            var table = window.LaravelDataTables["topuptable"];

            window.LaravelDataTables["topuptable"].on('draw', function () {
            });


        });
@endpush
