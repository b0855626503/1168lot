@section('css')
    @include('admin::layouts.datatables_css')
@endsection
@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}">
@endpush

{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}

<hr>
<table width="100%" class="table table-bordered" id="customfooter" style="font-size: medium">
    <tbody></tbody>
</table>

@push('scripts')
    <script src="{{ asset('vendor/daterangepicker/daterangepicker.js') }}"></script>
    <script>
        $(document).ready(function () {

            $('#search_date').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                timePicker: false,
                timePicker24Hour: false,
                timePickerSeconds: false,
                startDate: moment().subtract(1, 'days'),
                maxDate: moment().subtract(1, 'days'),
                autoApply: true,
                locale: {
                    format: 'DD/MM/YYYY'
                }
            }, function (start, end, label) {
                // $('#startDate').val(start.format('YYYY-MM-DD HH:mm:ss'));
                // $('#endDate').val(end.format('YYYY-MM-DD HH:mm:ss'));
            });

            $('#startDate').val(moment().subtract(1, 'days').format('YYYY-MM-DD'));
            // $('#endDate').val($('#search_date').data('daterangepicker').endDate.format('YYYY-MM-DD HH:mm:ss'));

            $('#search_date').on('apply.daterangepicker', function (ev, picker) {
                var start = picker.startDate.format('YYYY-MM-DD');
                // var end = picker.endDate.format('YYYY-MM-DD HH:mm:ss');
                $('#startDate').val(start);
                // $('#endDate').val(end);
            });


            $("#frmsearch").submit(function () {
                window.LaravelDataTables["dataTableBuilder"].draw(true);
            });

            $('body').addClass('sidebar-collapse');
        });
    </script>
    @include('admin::layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    <script>
        $(function () {

            var table = window.LaravelDataTables["dataTableBuilder"];
            window.LaravelDataTables["dataTableBuilder"].on('draw', function () {
                $("#customfooter tbody").html('');

                let html = '<tr>';
                html += '<th style="text-align:right;width:80%;color:blue">รวมยอดฝาก (ทั้งหมด)</th><th style="text-align:right;color:blue;">' + table.ajax.json().deposit + '</th>';
                html += '</tr>';
                html += '<tr>';
                html += '<th style="text-align:right;width:80%;color:blue">รวมยอดถอน (ทั้งหมด)</th><th style="text-align:right;color:blue;">' + table.ajax.json().withdraw + '</th>';
                html += '</tr>';
                html += '<tr>';
                html += '<th style="text-align:right;width:80%;color:blue">รวมยอดโบนัส (ทั้งหมด)</th><th style="text-align:right;color:blue;">' + table.ajax.json().bonus + '</th>';
                html += '</tr>';
                html += '<tr>';
                html += '<th style="text-align:right;width:80%;color:blue">รวมยอดเสีย (ทั้งหมด)</th><th style="text-align:right;color:blue;">' + table.ajax.json().totals + '</th>';
                html += '</tr>';
                html += '<tr>';
                html += '<th style="text-align:right;width:80%;color:blue">รวม IC (ทั้งหมด)</th><th style="text-align:right;color:blue;">' + table.ajax.json().cashback + '</th>';
                html += '</tr>';

                $("#customfooter tbody").append(html);


            });


        });
    </script>
@endpush
