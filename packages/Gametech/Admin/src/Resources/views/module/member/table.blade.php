@section('css')
    @include('admin::layouts.datatables_css')
@endsection
@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css"
          integrity="sha512-jU/7UFiaW5UBGODEopEqnbIAHOI8fO6T99m7Tsmqs2gkdujByJfkCbbfPSN4Wlqlb9TGnsuC0YgUgWkRBK7B9A=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link rel="stylesheet" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}">
<style>
    /* ให้คลิกทะลุข้อความได้แน่ ๆ */
    .dropzone .dz-message { pointer-events: auto; }

    /* กัน preview ทับพื้นที่คลิก */
    .dropzone .dz-preview { position: relative; z-index: 1; }
    .dropzone .dz-message { position: relative; z-index: 2; }

</style>
@endpush

{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm dataTable-res']) !!}

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"
            integrity="sha512-U2WE1ktpMTuRBPoCFDzomoIorbOyUv0sP8B+INA3EzNAhehbzED1rOJg6bCqPf/Tuposxb5ja/MAUnC8THSbLQ=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ asset('vendor/daterangepicker/daterangepicker.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('#search_date').daterangepicker({
                showDropdowns: true,
                timePicker: true,
                timePicker24Hour: true,
                timePickerSeconds: true,
                autoUpdateInput: true,
                startDate: moment().subtract(10, 'year').startOf('month').startOf('day'),
                endDate: moment().endOf('month').endOf('day'),
                locale: {
                    format: 'DD/MM/YYYY HH:mm:ss',
                    cancelLabel: 'Clear'
                },
                ranges: {
                    'วันนี้': [moment().startOf('day'), moment().endOf('day')],
                    'เมื่อวาน': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                    '7 วันที่ผ่านมา': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
                    '30 วันที่ผ่านมา': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
                    'เดือนนี้': [moment().startOf('month').startOf('day'), moment().endOf('month').endOf('day')],
                    'เดือนที่ผ่านมา': [moment().subtract(1, 'month').startOf('month').startOf('day'), moment().subtract(1, 'month').endOf('month').endOf('day')],
                    'แสดงทั้งหมด': [moment().subtract(10, 'year').startOf('month').startOf('day'), moment().endOf('month').endOf('day')]
                }
            }, function (start, end, label) {
                // $('#startDate').val(start.format('YYYY-MM-DD HH:mm:ss'));
                // $('#endDate').val(end.format('YYYY-MM-DD HH:mm:ss'));
            });

            $('#startDate').val(moment().subtract(10, 'year').startOf('month').startOf('day').format('YYYY-MM-DD HH:mm:ss'));
            $('#endDate').val(moment().endOf('month').endOf('day').format('YYYY-MM-DD HH:mm:ss'));

            $('#search_date').on('apply.daterangepicker', function (ev, picker) {
                var start = picker.startDate.format('YYYY-MM-DD HH:mm:ss');
                var end = picker.endDate.format('YYYY-MM-DD HH:mm:ss');
                $('#startDate').val(start);
                $('#endDate').val(end);
            });


            $("#frmsearch").submit(function () {
                window.LaravelDataTables["dataTableBuilder"].draw(true);
            });

            // $('body').addClass('sidebar-collapse');
        });

    </script>
    @include('admin::layouts.datatables_js')

    {!! $dataTable->scripts() !!}
@endpush
