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
                showDropdowns: true,
                timePicker: true,
                timePicker24Hour: true,
                timePickerSeconds: true,
                autoApply: true,
                startDate: moment().startOf('day'),
                endDate: moment().endOf('day'),
                locale: {
                    format: 'DD/MM/YYYY HH:mm:ss'
                },
                ranges: {
                    'วันนี้': [moment().startOf('day'), moment().endOf('day')],
                    'เมื่อวาน': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                    '7 วันที่ผ่านมา': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
                    '30 วันที่ผ่านมา': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
                    'เดือนนี้': [moment().startOf('month').startOf('day'), moment().endOf('month').endOf('day')],
                    'เดือนที่ผ่านมา': [moment().subtract(1, 'month').startOf('month').startOf('day'), moment().subtract(1, 'month').endOf('month').endOf('day')]
                }
            }, function (start, end, label) {
                // $('#startDate').val(start.format('YYYY-MM-DD HH:mm:ss'));
                // $('#endDate').val(end.format('YYYY-MM-DD HH:mm:ss'));
            });

            $('#startDate').val(moment().startOf('day').format('YYYY-MM-DD HH:mm:ss'));
            $('#endDate').val(moment().endOf('day').format('YYYY-MM-DD HH:mm:ss'));

            $('#search_date').on('apply.daterangepicker', function (ev, picker) {
                var start = picker.startDate.format('YYYY-MM-DD HH:mm:ss');
                var end = picker.endDate.format('YYYY-MM-DD HH:mm:ss');
                $('#startDate').val(start);
                $('#endDate').val(end);
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

            var promotion = @json($pros->toArray());
            var table = window.LaravelDataTables["dataTableBuilder"];
            window.LaravelDataTables["dataTableBuilder"].on('draw', function () {
                $("#customfooter tbody").html('');

                let html = '';
                html += '<tr>';
                html += '<th style="text-align:right;width:80%;color:darkorange">รวม</th><th style="text-align:right;color:darkorange;">' + table.ajax.json().count + '</th>';
                html += '</tr>';
                html += '<tr>';
                html += '<th style="text-align:right;width:80%;color:darkorange">ยอดเงินรวม</th><th style="text-align:right;color:darkorange;">' + table.ajax.json().sum + '</th>';
                html += '</tr>';
                // html += '<tr>';

                // $.each(promotion, function (index, value) {
                //     let pro = table.ajax.json();
                //     console.log(pro['p' + index]);
                //     // let p = 'pro.p'+index;
                //     html += '<tr>';
                //     html += '<th style="text-align:right;width:80%;color:darkorange">รวม ' + value + '</th><th style="text-align:right;color:darkorange;">' + pro['p' + index] + '</th>';
                //     html += '</tr>';
                //     html += '<tr>';
                // });


                $("#customfooter tbody").append(html);


            });


        });
    </script>
@endpush
