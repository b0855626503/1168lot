@section('css')
    @include('admin::layouts.datatables_css')
@endsection

{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}

@push('scripts')
    @include('admin::layouts.datatables_js')
    <script>
        $(document).ready(function () {
            $("#frmsearch").submit(function () {
                window.LaravelDataTables["dataTableBuilder"].draw(true);
            });
        });
    </script>
    {!! $dataTable->scripts() !!}
@endpush
