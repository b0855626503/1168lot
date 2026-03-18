@section('css')
    @include('admin::layouts.datatables_css')
@endsection
@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}">
    <style>
        .dataTables_scrollBody {
            border-top: 1px solid rgba(255,255,255,0.1);
            max-height: 400px !important; /* เผื่อบางธีมไม่ฟัง scrollY */
        }

        /* กล่อง JSON ใน datatable: เตี้ย + เลื่อนในกล่อง */
        .json-pretty {
            height: 100px;
            max-height: 100px;       /* ปรับได้ตามใจ */
            overflow: auto;
            background: #0f172a;     /* โทนดาร์กอ่านง่าย */
            color: #e2e8f0;
            padding: 8px 10px;
            border-radius: 6px;
            line-height: 1.35;
            font-size: 12px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            white-space: pre;         /* คงช่องว่าง/บรรทัด */
            border: 1px solid rgba(148,163,184,.2);
        }

        /* เวลาโฟกัสด้วยคีย์บอร์ด */
        .json-pretty:focus {
            outline: 2px solid #38bdf8;
            outline-offset: 2px;
        }

        /* ป้องกันคอลัมน์ดันสูงเวลา pre กว้างมาก */
        table.dataTable td .json-pretty {
            max-width: 420px;        /* กันหลุดขอบ: ปรับตาม layout คุณ */
        }

    </style>
@endpush

{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}

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
@endpush
