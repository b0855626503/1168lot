@extends('admin::layouts.marketing')

{{-- page title --}}
@section('title','Campaigns')

@section('campaign_name',$campaign_name)


@section('content')
    <section class="content text-xs">
        <div class="row">
            <div class="form-group col-12">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="far fa-clock"></i></span>
                    </div>
                    <input type="text" class="form-control form-control-sm float-right"
                           id="search_date" readonly>
                    <input type="hidden" id="startDate" name="startDate">
                    <input type="hidden" id="endDate" name="endDate">
                </div>
            </div>
        </div>
        <div class="row">
            @php
                $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.regis_all');
            @endphp
            @if($prem)
                <div class="col-lg-3 col-6">
                    <register-all-slot ref="register-all"></register-all-slot>
                </div>
            @endif

            @php
                $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.regis_today');
            @endphp
            @if($prem)
                <div class="col-lg-3 col-6">
                    <register-today-slot ref="register-today" :selected-date-start="dateRange.start"
                                         :selected-date-end="dateRange.end"></register-today-slot>
                </div>
            @endif

            @php
                $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.deposit_all');
            @endphp
            @if($prem)
                <div class="col-lg-3 col-6">
                    <deposit-all-slot ref="deposit-all"></deposit-all-slot>
                </div>
            @endif

            @php
                $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.deposit_today');
            @endphp
            @if($prem)
                <div class="col-lg-3 col-6">
                    <deposit-today-slot ref="deposit-today" :selected-date-start="dateRange.start"
                                        :selected-date-end="dateRange.end"></deposit-today-slot>
                </div>
            @endif

{{--        </div>--}}
{{--        <div class="row">--}}

            @php
                $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.withdraw_all');
            @endphp
            @if($prem)
                <div class="col-lg-3 col-6">
                    <withdraw-all-slot ref="withdraw-all"></withdraw-all-slot>
                </div>
            @endif


            @php
                $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.withdraw_today');
            @endphp
            @if($prem)
                <div class="col-lg-3 col-6">
                    <withdraw-today-slot ref="withdraw-today" :selected-date-start="dateRange.start"
                                         :selected-date-end="dateRange.end"></withdraw-today-slot>
                </div>
            @endif

            @php
                $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.click_all');
            @endphp
            @if($prem)
                <div class="col-lg-3 col-6">
                    <click-all-slot ref="click-all"></click-all-slot>
                </div>
            @endif

            @php
                $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.click_today');
            @endphp
            @if($prem)
                <div class="col-lg-3 col-6">
                    <click-today-slot ref="click-today" :selected-date-start="dateRange.start"
                                      :selected-date-end="dateRange.end"></click-today-slot>
                </div>
            @endif
{{--        </div>--}}

{{--        <div class="row">--}}

            @php
                $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.bonus_all');
            @endphp
            @if($prem)
                <div class="col-lg-3 col-6">
                    <bonus-all-slot ref="bonus-all"></bonus-all-slot>
                </div>
            @endif


            @php
                $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.bonus_today');
            @endphp
            @if($prem)
                <div class="col-lg-3 col-6">
                    <bonus-today-slot ref="bonus-today" :selected-date-start="dateRange.start"
                                      :selected-date-end="dateRange.end"></bonus-today-slot>
                </div>
            @endif

            @php
                $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.register_deposit');
            @endphp
            @if($prem)
                <div class="col-lg-3 col-6">
                    <register-deposit-slot ref="register-deposit" :selected-date-start="dateRange.start"
                                           :selected-date-end="dateRange.end"></register-deposit-slot>
                </div>
            @endif

            @php
                $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.register_not_deposit');
            @endphp
            @if($prem)
                <div class="col-lg-3 col-6">
                    <register-not-deposit-slot ref="register-not-deposit" :selected-date-start="dateRange.start"
                                               :selected-date-end="dateRange.end"></register-not-deposit-slot>
                </div>
            @endif
{{--        </div>--}}


{{--        <div class="row">--}}
            @php
                $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.register_all_deposit');
            @endphp
            @if($prem)
                <div class="col-lg-3 col-6">
                    <register-all-deposit-slot ref="register-all-deposit" :selected-date-start="dateRange.start"
                                               :selected-date-end="dateRange.end"></register-all-deposit-slot>
                </div>
            @endif

            @php
                $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.member_all_first_deposit');
            @endphp
            @if($prem)
                <div class="col-lg-3 col-6">
                    <member-all-first-deposit-slot ref="member-all-first-deposit" :selected-date-start="dateRange.start"
                                                   :selected-date-end="dateRange.end"></member-all-first-deposit-slot>
                </div>
            @endif

            @php
                $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.deposit_register_today');
            @endphp
            @if($prem)
                <div class="col-lg-3 col-6">
                    <deposit-register-today-slot ref="deposit-register-today" :selected-date-start="dateRange.start"
                                                 :selected-date-end="dateRange.end"></deposit-register-today-slot>
                </div>
            @endif
        </div>



        @php
            $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.regis');
        @endphp
        @if($prem)
            <div class="row">

                <div class="col-lg-12">
                    <regis-slot ref="regis"></regis-slot>
                </div>

            </div>
        @endif
        @php
            $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.income');
        @endphp
        @if($prem)
            <div class="row">

                <div class="col-lg-12">
                    <income-slot ref="income"></income-slot>
                </div>

            </div>
        @endif

        @php
            $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.click');
        @endphp
        @if($prem)
            <div class="row">

                <div class="col-lg-12">
                    <click-slot ref="click"></click-slot>
                </div>

            </div>
        @endif

        @php
            $prem = bouncer()->hasPermission('marketing.marketing_campaign.dashboard.member_list');
        @endphp
        @if($prem)
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary">
                        <form id="frmsearch" method="post" onsubmit="return false;">
                            <div class="card-body">
                                <div class="row">


                                    <div class="form-group col-6">
                                        {!! Form::select('filter', ['all' => 'ทั้งหมด', 'has_deposit' => 'มียอดฝาก','has_withdraw' => 'มียอดถอน' , 'deposit_today' => 'มียอดฝากวันนี้' , 'withdraw_today' => 'มียอดถอนวันนี้' ,'no_deposit' => 'ไม่มียอดฝาก'], '', ['id' => 'filter', 'class' => 'form-control form-control-sm']) !!}
                                    </div>

                                    <div class="form-group col-6"></div>
                                    <div class="form-group col-6"></div>

                                    <div class="form-group col-auto">
                                        <button class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Search
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card">

                        <div class="card-body">
                            @include('admin::module.marketing_member.table')
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.info-box -->
                </div>
            </div>
        @endif
    </section>

@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('/vendor/chart.js/Chart.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('/vendor/chart.js/Chart.js') }}"></script>
    <script src="{{ asset('vendor/daterangepicker/daterangepicker.js') }}"></script>
    <script type="text/x-template" id="regis-slot-template">
        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex justify-content-between">
                    <h3 class="card-title">ยอดสมัคร / วัน</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="position-relative mb-4">
                    <canvas id="regis-chart" height="100"></canvas>
                </div>
            </div>
        </div>

    </script>

    <script type="text/x-template" id="click-slot-template">
        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex justify-content-between">
                    <h3 class="card-title">ยอดคลิ๊กลิงค์ / วัน</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="position-relative mb-4 chart">
                    <canvas id="click-chart" height="100"></canvas>
                </div>
            </div>
        </div>

    </script>

    <script type="text/x-template" id="bonus-all-slot-template">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-gift"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">ยอดรับโบนัสทั้งหมด</span>
                <span class="info-box-number">
                  @{{ sum }}
                  <small>เครดิต</small>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>

    </script>

    <script type="text/x-template" id="bonus-today-slot-template">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-gift"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">ยอดรับโบนัส วันนี้</span>
                <span class="info-box-number">
                  @{{ sum }}
                  <small>เครดิต</small>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>

    </script>

    <script type="text/x-template" id="register-deposit-slot-template">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-dollar-sign"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">สมัครฝาก</span>
                <span class="info-box-number">
                  @{{ sum }}
                  <small>คน</small>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>

    </script>

    <script type="text/x-template" id="register-not-deposit-slot-template">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-dollar-sign"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">สมัครไม่ฝาก</span>
                <span class="info-box-number">
                  @{{ sum }}
                  <small>คน</small>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>

    </script>

    <script type="text/x-template" id="register-all-deposit-slot-template">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-dollar-sign"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">สมาชิกเก่าฝาก วันนี้</span>
                <span class="info-box-number">
                  @{{ sum }}
                  <small>คน</small>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>

    </script>

    <script type="text/x-template" id="deposit-register-today-slot-template">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-dollar-sign"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">สมาชิกสมัครและฝาก วันนี้</span>
                <span class="info-box-number">
                  @{{ sum }}
                  <small>บาท</small>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>

    </script>


    <script type="text/x-template" id="member-all-first-deposit-slot-template">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-dollar-sign"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">สมาชิกทั้งหมด ฝากแรก วันนี้</span>
                <span class="info-box-number">
                  @{{ sum }}
                  <small>บาท</small>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>

    </script>

    <script type="text/x-template" id="register-all-slot-template">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-users"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">ยอดสมัครทั้งหมด</span>
                <span class="info-box-number">
                  @{{ sum }}
                  <small>คน</small>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>

    </script>

    <script type="text/x-template" id="register-today-slot-template">
        <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-user"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">สมัครใหม่ วันนี้</span>
                <span class="info-box-number">
                  @{{ sum }}
                  <small>คน</small>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>

    </script>

    <script type="text/x-template" id="deposit-all-slot-template">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-plus-circle"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">ยอดฝากทั้งหมด</span>
                <span class="info-box-number">
                  @{{ sum }}
                  <small>บาท</small>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>

    </script>

    <script type="text/x-template" id="deposit-today-slot-template">
        <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-plus"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">ยอดฝาก วันนี้</span>
                <span class="info-box-number">
                  @{{ sum }}
                  <small>บาท</small>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>

    </script>

    <script type="text/x-template" id="withdraw-all-slot-template">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-minus-circle"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">ยอดถอนทั้งหมด</span>
                <span class="info-box-number">
                  @{{ sum }}
                  <small>บาท</small>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>

    </script>

    <script type="text/x-template" id="withdraw-today-slot-template">
        <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-minus"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">ยอดถอน วันนี้</span>
                <span class="info-box-number">
                  @{{ sum }}
                  <small>บาท</small>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>

    </script>

    <script type="text/x-template" id="click-all-slot-template">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-hand-point-up"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">ยอดคลิ๊กทั้งหมด</span>
                <span class="info-box-number">
                  @{{ sum }}
                  <small>ครั้ง</small>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>

    </script>

    <script type="text/x-template" id="click-today-slot-template">
        <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-hand-pointer"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">ยอดคลิ๊ก วันนี้</span>
                <span class="info-box-number">
                  @{{ sum }}
                  <small>ครั้ง</small>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>

    </script>

    <script type="text/x-template" id="income-slot-template">
        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex justify-content-between">
                    <h3 class="card-title">เยอดฝาก-ถอน / วัน</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="position-relative mb-4">
                    <canvas id="income-chart" height="200"></canvas>
                </div>
            </div>
        </div>

    </script>

    {{--    <script>--}}
    {{--        $(function () {--}}
    {{--            $('#search_date').daterangepicker({--}}
    {{--                opens: 'right',--}}
    {{--                autoUpdateInput: false,--}}
    {{--                locale: {--}}
    {{--                    format: 'YYYY-MM-DD',--}}
    {{--                    cancelLabel: 'ยกเลิก',--}}
    {{--                    applyLabel: 'ตกลง',--}}
    {{--                    customRangeLabel: 'กำหนดเอง',--}}
    {{--                    daysOfWeek: ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'],--}}
    {{--                    monthNames: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],--}}
    {{--                    firstDay: 0--}}
    {{--                },--}}
    {{--                ranges: {--}}
    {{--                    'วันนี้': [moment(), moment()],--}}
    {{--                    'เมื่อวาน': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],--}}
    {{--                    '7 วันล่าสุด': [moment().subtract(6, 'days'), moment()],--}}
    {{--                    '30 วันล่าสุด': [moment().subtract(29, 'days'), moment()],--}}
    {{--                    'เดือนนี้': [moment().startOf('month'), moment().endOf('month')],--}}
    {{--                    'เดือนที่แล้ว': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]--}}
    {{--                }--}}
    {{--            });--}}

    {{--            $('#search_date').on('apply.daterangepicker', function (ev, picker) {--}}
    {{--                const start = picker.startDate.format('YYYY-MM-DD');--}}
    {{--                const end = picker.endDate.format('YYYY-MM-DD');--}}

    {{--                // ใส่ค่าลงใน input hidden--}}
    {{--                $('#startDate').val(start);--}}
    {{--                $('#endDate').val(end);--}}

    {{--                // แสดงผลช่วงวัน--}}
    {{--                $(this).val(start + ' ถึง ' + end);--}}
    {{--            });--}}

    {{--            $('#search_date').on('cancel.daterangepicker', function () {--}}
    {{--                $(this).val('');--}}
    {{--                $('#startDate, #endDate').val('');--}}
    {{--            });--}}
    {{--        });--}}
    {{--    </script>--}}
    <script type="module">
        import to from "./js/toPromise.js";

        Vue.component('register-deposit-slot', {
            template: '#register-deposit-slot-template',
            props: ['selectedDateStart', 'selectedDateEnd'],
            data: function () {
                return {
                    sum: 0
                }
            },
            watch: {
                selectedDateStart(newVal) {
                    this.checkAndLoad();
                },
                selectedDateEnd(newVal) {
                    this.checkAndLoad();
                }
            },
            mounted() {
                this.loadData();
            },
            methods: {
                checkAndLoad() {
                    if (this.selectedDateStart && this.selectedDateEnd) {
                        this.loadData();
                    }
                },
                async loadData() {
                    let err, result;
                    [err, result] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'register-deposit',
                        id: '{{ $id }}',
                        date_start: this.selectedDateStart,
                        date_end: this.selectedDateEnd
                    }));
                    if (err) {
                        return 0;
                    }
                    this.sum = result.data.sum;
                    return this.sum;

                }
            }
        });

        Vue.component('register-not-deposit-slot', {
            template: '#register-not-deposit-slot-template',
            props: ['selectedDateStart', 'selectedDateEnd'],
            data: function () {
                return {
                    sum: 0
                }
            },
            watch: {
                selectedDateStart(newVal) {
                    this.checkAndLoad();
                },
                selectedDateEnd(newVal) {
                    this.checkAndLoad();
                }
            },
            mounted() {
                this.loadData();
            },
            methods: {
                checkAndLoad() {
                    if (this.selectedDateStart && this.selectedDateEnd) {
                        this.loadData();
                    }
                },
                async loadData() {
                    let err, result;
                    [err, result] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'register-not-deposit',
                        id: '{{ $id }}',
                        date_start: this.selectedDateStart,
                        date_end: this.selectedDateEnd
                    }));
                    if (err) {
                        return 0;
                    }
                    this.sum = result.data.sum;
                    return this.sum;

                }
            }
        });

        Vue.component('register-all-deposit-slot', {
            template: '#register-all-deposit-slot-template',
            props: ['selectedDateStart', 'selectedDateEnd'],
            data: function () {
                return {
                    sum: 0
                }
            },
            watch: {
                selectedDateStart(newVal) {
                    this.checkAndLoad();
                },
                selectedDateEnd(newVal) {
                    this.checkAndLoad();
                }
            },
            mounted() {
                this.loadData();
            },
            methods: {
                checkAndLoad() {
                    if (this.selectedDateStart && this.selectedDateEnd) {
                        this.loadData();
                    }
                },
                async loadData() {
                    let err, result;
                    [err, result] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'register-all-deposit',
                        id: '{{ $id }}',
                        date_start: this.selectedDateStart,
                        date_end: this.selectedDateEnd
                    }));
                    if (err) {
                        return 0;
                    }
                    this.sum = result.data.sum;
                    return this.sum;

                }
            }
        });

        Vue.component('deposit-register-today-slot', {
            template: '#deposit-register-today-slot-template',
            props: ['selectedDateStart', 'selectedDateEnd'],
            data: function () {
                return {
                    sum: 0
                }
            },
            watch: {
                selectedDateStart(newVal) {
                    this.checkAndLoad();
                },
                selectedDateEnd(newVal) {
                    this.checkAndLoad();
                }
            },
            mounted() {
                this.loadData();
            },
            methods: {
                checkAndLoad() {
                    if (this.selectedDateStart && this.selectedDateEnd) {
                        this.loadData();
                    }
                },
                async loadData() {
                    let err, result;
                    [err, result] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'deposit-register-today',
                        id: '{{ $id }}',
                        date_start: this.selectedDateStart,
                        date_end: this.selectedDateEnd
                    }));
                    if (err) {
                        return 0;
                    }
                    this.sum = result.data.sum;
                    return this.sum;

                }
            }
        });


        Vue.component('member-all-first-deposit-slot', {
            template: '#member-all-first-deposit-slot-template',
            props: ['selectedDateStart', 'selectedDateEnd'],
            data: function () {
                return {
                    sum: 0
                }
            },
            watch: {
                selectedDateStart(newVal) {
                    this.checkAndLoad();
                },
                selectedDateEnd(newVal) {
                    this.checkAndLoad();
                }
            },
            mounted() {
                this.loadData();
            },
            methods: {
                checkAndLoad() {
                    if (this.selectedDateStart && this.selectedDateEnd) {
                        this.loadData();
                    }
                },
                async loadData() {
                    let err, result;
                    [err, result] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'member-all-first-deposit',
                        id: '{{ $id }}',
                        date_start: this.selectedDateStart,
                        date_end: this.selectedDateEnd
                    }));
                    if (err) {
                        return 0;
                    }
                    this.sum = Number(result.data.sum.toFixed(2));
                    return this.sum;

                }
            }
        });

        Vue.component('bonus-all-slot', {
            template: '#bonus-all-slot-template',
            data: function () {
                return {
                    sum: 0
                }
            },
            mounted() {
                this.loadData();
            },
            methods: {
                async loadData() {
                    let err, result;
                    [err, result] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'bonus-all',
                        id: '{{ $id }}'
                    }));
                    if (err) {
                        return 0;
                    }
                    this.sum = result.data.sum;
                    return this.sum;

                }
            }
        });

        Vue.component('bonus-today-slot', {
            template: '#bonus-today-slot-template',
            props: ['selectedDateStart', 'selectedDateEnd'],
            data: function () {
                return {
                    sum: 0
                }
            },
            watch: {
                selectedDateStart(newVal) {
                    this.checkAndLoad();
                },
                selectedDateEnd(newVal) {
                    this.checkAndLoad();
                }
            },
            mounted() {
                this.loadData();
            },
            methods: {
                checkAndLoad() {
                    if (this.selectedDateStart && this.selectedDateEnd) {
                        this.loadData();
                    }
                },
                async loadData() {
                    let err, result;
                    [err, result] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'bonus-today',
                        id: '{{ $id }}',
                        date_start: this.selectedDateStart,
                        date_end: this.selectedDateEnd
                    }));
                    if (err) {
                        return 0;
                    }
                    this.sum = result.data.sum;
                    return this.sum;

                }
            }
        });

        Vue.component('register-all-slot', {
            template: '#register-all-slot-template',
            data: function () {
                return {
                    sum: 0
                }
            },
            mounted() {
                this.loadData();
            },
            methods: {
                async loadData() {
                    let err, result;
                    [err, result] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'register-all',
                        id: '{{ $id }}'
                    }));
                    if (err) {
                        return 0;
                    }
                    this.sum = result.data.sum;
                    return this.sum;

                }
            }
        });

        Vue.component('register-today-slot', {
            template: '#register-today-slot-template',
            props: ['selectedDateStart', 'selectedDateEnd'],
            data: function () {
                return {
                    sum: 0
                }
            },
            watch: {
                selectedDateStart(newVal) {
                    this.checkAndLoad();
                },
                selectedDateEnd(newVal) {
                    this.checkAndLoad();
                }
            },
            mounted() {
                this.loadData();
            },
            methods: {
                checkAndLoad() {
                    if (this.selectedDateStart && this.selectedDateEnd) {
                        this.loadData();
                    }
                },
                async loadData() {
                    let err, result;
                    [err, result] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'register-today',
                        id: '{{ $id }}',
                        date_start: this.selectedDateStart,
                        date_end: this.selectedDateEnd
                    }));
                    if (err) {
                        return 0;
                    }
                    this.sum = result.data.sum;
                    return this.sum;

                }
            }
        });

        Vue.component('deposit-all-slot', {
            template: '#deposit-all-slot-template',
            data: function () {
                return {
                    sum: 0
                }
            },
            mounted() {
                this.loadData();
            },
            methods: {
                async loadData() {
                    let err, result;
                    [err, result] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'deposit-all',
                        id: '{{ $id }}'
                    }));
                    if (err) {
                        return 0;
                    }
                    this.sum = result.data.sum;
                    return this.sum;

                }
            }
        });

        Vue.component('deposit-today-slot', {
            template: '#deposit-today-slot-template',
            props: ['selectedDateStart', 'selectedDateEnd'],
            data: function () {
                return {
                    sum: 0
                }
            },
            watch: {
                selectedDateStart(newVal) {
                    this.checkAndLoad();
                },
                selectedDateEnd(newVal) {
                    this.checkAndLoad();
                }
            },
            mounted() {
                this.loadData();
            },
            methods: {
                checkAndLoad() {
                    if (this.selectedDateStart && this.selectedDateEnd) {
                        this.loadData();
                    }
                },
                async loadData() {
                    let err, result;
                    [err, result] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'deposit-today',
                        id: '{{ $id }}',
                        date_start: this.selectedDateStart,
                        date_end: this.selectedDateEnd
                    }));
                    if (err) {
                        return 0;
                    }
                    this.sum = result.data.sum;
                    return this.sum;

                }
            }
        });

        Vue.component('withdraw-all-slot', {
            template: '#withdraw-all-slot-template',
            data: function () {
                return {
                    sum: 0
                }
            },
            mounted() {
                this.loadData();
            },
            methods: {
                async loadData() {
                    let err, result;
                    [err, result] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'withdraw-all',
                        id: '{{ $id }}'
                    }));
                    if (err) {
                        return 0;
                    }
                    this.sum = result.data.sum;
                    return this.sum;

                }
            }
        });

        Vue.component('withdraw-today-slot', {
            template: '#withdraw-today-slot-template',
            props: ['selectedDateStart', 'selectedDateEnd'],
            data: function () {
                return {
                    sum: 0
                }
            },
            watch: {
                selectedDateStart(newVal) {
                    this.checkAndLoad();
                },
                selectedDateEnd(newVal) {
                    this.checkAndLoad();
                }
            },
            mounted() {
                this.loadData();
            },
            methods: {
                checkAndLoad() {
                    if (this.selectedDateStart && this.selectedDateEnd) {
                        this.loadData();
                    }
                },
                async loadData() {
                    let err, result;
                    [err, result] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'withdraw-today',
                        id: '{{ $id }}',
                        date_start: this.selectedDateStart,
                        date_end: this.selectedDateEnd
                    }));
                    if (err) {
                        return 0;
                    }
                    this.sum = result.data.sum;
                    return this.sum;

                }
            }
        });

        Vue.component('click-all-slot', {
            template: '#click-all-slot-template',

            data: function () {
                return {
                    sum: 0
                }
            },
            mounted() {
                this.loadData();
            },
            methods: {
                async loadData() {
                    let err, result;
                    [err, result] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'click-all',
                        id: '{{ $id }}'
                    }));
                    if (err) {
                        return 0;
                    }
                    this.sum = result.data.sum;
                    return this.sum;

                }
            }
        });

        Vue.component('click-today-slot', {
            template: '#click-today-slot-template',
            props: ['selectedDateStart', 'selectedDateEnd'],
            data: function () {
                return {
                    sum: 0
                }
            },
            watch: {
                selectedDateStart(newVal) {
                    this.checkAndLoad();
                },
                selectedDateEnd(newVal) {
                    this.checkAndLoad();
                }
            },
            mounted() {
                this.loadData();
            },
            methods: {
                checkAndLoad() {
                    if (this.selectedDateStart && this.selectedDateEnd) {
                        this.loadData();
                    }
                },
                async loadData() {
                    let err, result;
                    [err, result] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'click-today',
                        id: '{{ $id }}',
                        date_start: this.selectedDateStart,
                        date_end: this.selectedDateEnd
                    }));
                    if (err) {
                        return 0;
                    }
                    this.sum = result.data.sum;
                    return this.sum;

                }
            }
        });

        Vue.component('regis-slot', {
            template: '#regis-slot-template',

            data: function () {
                return {
                    chart: '',
                }
            },
            mounted() {
                this.chart = $('#regis-chart');
                this.loadData();
            },
            methods: {
                async loadData() {
                    let err, res;
                    [err, res] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'register',
                        id: '{{ $id }}'
                    }));
                    if (err) {
                        return 0;
                    }
                    let ctx = this.chart;
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: res.data.label,
                            datasets: [{
                                label: 'สมาชิกใหม่ ',
                                data: res.data.register,
                                backgroundColor: 'rgba(0,51,0,1)',
                                borderColor: 'rgba(60,141,188,0.8)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            datasetFill: false,
                            maintainAspectRatio: true,
                            responsive: true,
                            legend: {
                                display: false
                            },
                            scales: {
                                xAxes: [{
                                    gridLines: {
                                        display: false,
                                    }
                                }],
                                yAxes: [{
                                    gridLines: {
                                        display: false,
                                    }
                                }]
                            }
                        }
                    });
                }
            }
        });

        Vue.component('click-slot', {
            template: '#click-slot-template',

            data: function () {
                return {
                    chart: '',
                }
            },
            mounted() {
                this.chart = $('#click-chart');
                this.loadData();
            },
            methods: {
                async loadData() {
                    let err, res;
                    [err, res] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'click',
                        id: '{{ $id }}'
                    }));
                    if (err) {
                        return 0;
                    }
                    let ctx = this.chart;
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: res.data.label,
                            datasets: [{
                                label: 'ยอดคลิ๊กลิงค์',
                                pointRadius: 2, // ✅ ใช้ตัวเลข ไม่ใช่ false
                                data: res.data.bar,
                                backgroundColor: 'rgba(60,141,188,0.4)', // ✅ สีอ่อนลงเพื่อให้ดูเป็นเส้น
                                borderColor: 'rgba(60,141,188,1)',
                                borderWidth: 2,
                                tension: 0.3, // ✅ เพิ่มความโค้งให้ smooth
                                fill: false,
                            }]
                        },
                        options: {
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        stepSize: 1,
                                        callback: function (value) {
                                            if (Number.isInteger(value)) {
                                                return value;
                                            }
                                        }
                                    }
                                }]
                            },
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            maintainAspectRatio: true,
                            responsive: true
                        }
                    });
                }
            }
        });

        Vue.component('income-slot', {
            template: '#income-slot-template',

            data: function () {
                return {
                    chart: '',
                }
            },
            mounted() {
                this.chart = $('#income-chart');
                this.loadData();
            },
            methods: {
                async loadData() {
                    let err, res;
                    [err, res] = await to(axios.post("{{ route('admin.marketing_campaign.loadReport') }}", {
                        method: 'income',
                        id: '{{ $id }}'
                    }));
                    if (err) {
                        return 0;
                    }
                    let ctx = this.chart;
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: res.data.label,
                            datasets: [{
                                label: 'ยอดฝาก ',
                                data: res.data.deposit,
                                backgroundColor: 'rgba(255,0,0,1)',
                                borderColor: 'rgba(60,141,188,0.8)',
                                borderWidth: 1
                            }, {
                                label: 'ยอดถอน ',
                                data: res.data.withdraw,
                                backgroundColor: 'rgba(255,193,0,1)',
                                borderColor: 'rgba(60,141,188,0.8)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            datasetFill: false,
                            maintainAspectRatio: true,
                            responsive: true,
                            legend: {
                                display: true
                            },
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true
                                    }
                                }]
                            }
                        }
                    });
                }
            }
        });

        Vue.mixin({
            data() {
                return {
                    dateRange: {
                        start: '',
                        end: ''
                    }
                };
            },

            mounted() {
                console.log("Datepicker mounted"); // เพิ่มจุดนี้
                const vm = this;
                const todayRange = moment().format('YYYY-MM-DD') + ' ถึง ' + moment().format('YYYY-MM-DD');
                $('#search_date').daterangepicker({
                    startDate: moment(),
                    endDate: moment(),
                    autoUpdateInput: false,
                    locale: {
                        format: 'YYYY-MM-DD',
                        cancelLabel: 'ล้าง',
                        applyLabel: 'เลือก',
                        daysOfWeek: ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'],
                        monthNames: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
                        firstDay: 0
                    },
                    ranges: {
                        'วันนี้': [moment().startOf('day'), moment().endOf('day')],
                        'เมื่อวาน': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                        '7 วันที่ผ่านมา': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
                        '30 วันที่ผ่านมา': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
                        'เดือนนี้': [moment().startOf('month').startOf('day'), moment().endOf('month').endOf('day')],
                        'เดือนที่ผ่านมา': [moment().subtract(1, 'month').startOf('month').startOf('day'), moment().subtract(1, 'month').endOf('month').endOf('day')]
                    }
                });

                // $('#startDate').val(moment().startOf('day').format('YYYY-MM-DD'));
                // $('#endDate').val(moment().endOf('day').format('YYYY-MM-DD'));
                $('#search_date').val(todayRange);
                $('#search_date').on('apply.daterangepicker', function (ev, picker) {
                    const start = picker.startDate.format('YYYY-MM-DD');
                    const end = picker.endDate.format('YYYY-MM-DD');
                    $(this).val(start + ' ถึง ' + end);
                    console.log('1. เลือกช่วงวันที่:', start, 'ถึง', end);
                    vm.setDateRange(start, end); // ส่งเข้า Vue

                });

                $('#search_date').on('cancel.daterangepicker', function () {
                    $(this).val('');
                    vm.setDateRange('', '');
                });
            },
            methods: {
                setDateRange(start, end) {
                    this.dateRange.start = start;
                    this.dateRange.end = end;
                    console.log('Vue เลือกช่วงวันที่:', start, 'ถึง', end);
                    // ทำงานอื่น ๆ ได้เช่น fetch ข้อมูล
                },
                getToday() {

                    return '{{ now()->toDateString() }}'; // ได้ 'YYYY-MM-DD'
                },
                onDateChange(e) {
                    this.selectedDate = e.target.value;
                    this.$refs['register-deposit'].loadData(); // เรียกโหลดใหม่
                    this.$refs['register-not-deposit'].loadData(); // เรียกโหลดใหม่
                    this.$refs['register-all-deposit'].loadData(); // เรียกโหลดใหม่
                    this.$refs['register-today'].loadData(); // เรียกโหลดใหม่
                    this.$refs['deposit-today'].loadData(); // เรียกโหลดใหม่
                    this.$refs['withdraw-today'].loadData(); // เรียกโหลดใหม่
                    this.$refs['click-today'].loadData(); // เรียกโหลดใหม่
                    this.$refs['bonus-today'].loadData(); // เรียกโหลดใหม่
                }
            }
        });


    </script>
@endpush

