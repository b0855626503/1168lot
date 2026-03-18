@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('back')
    <a class="nav-link p-2 text-light mx-auto hand-point" href="{{ route('customer.home.index') }}">
        <i class="fas fa-chevron-left"></i> กลับ</a>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
    <style>
        .section {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .img-select {
            border: 2px solid #fff;
            border-radius: 10%;
            background-color: #ffffff3a;
        }

        .img-bank {
            width: 40px;
            height: 40px;
        }

        .hidden {
            display: none;
        }

        a {
            color: #007bff;
            text-decoration: none;
            background-color: transparent;
        }
    </style>
@endpush

@section('content')
    <div class="container text-light mt-5">
        <h3 class="text-center text-light">ฝากเงิน</h3>
        <p class="text-center text-color-fixed">Deposit</p>
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">

                @php
                    $tw = false;
                @endphp
                <banks>
                    @foreach($banks as $bank)

                        @php
                            if($bank['shortcode'] == 'TW'){

                                $account_code = $bank['banks_account'][0]['code'];
                            }
                        @endphp
                        <bank :item="{{ json_encode($bank) }}" {{ $loop->first ? ':selected="true"' : '' }}></bank>
                    @endforeach
                </banks>


                {{--                @if($tw)--}}
                {{--                    <section class="content mt-3">--}}

                {{--                        <div class="card card-trans">--}}
                {{--                            <div class="card-header text-center">--}}
                {{--                                <p class="text-center text-color-fixed m-0">แจ้งการฝากเงิน ผ่านช่องทาง TrueWallet (ใช้เบอร์โทรศัพท์ที่สมัคร อ้างอิงตอนฝากเข้ามา)</p>--}}
                {{--                            </div>--}}
                {{--                            <div class="card-body">--}}

                {{--                                <div class="row">--}}
                {{--                                    <div class="col-12">--}}

                {{--                                        <form method="POST" action="{{ route('customer.topup.tw') }}"--}}
                {{--                                              @submit.stop.prevent="onSubmit">--}}
                {{--                                            @csrf--}}
                {{--                                            <input type="hidden" id="account_code" name="account_code" value="{{ $account_code }}"/>--}}
                {{--                                            <input type="hidden" id="bank_time" name="bank_time"/>--}}
                {{--                                            <div class="col-12">--}}
                {{--                                                <div class="row">--}}

                {{--                                                    <div class="form-group col-sm-6">--}}
                {{--                                                        <label>ยอดเงินที่ฝาก:</label>--}}
                {{--                                                        <div class="input-group mb-3">--}}
                {{--                                                            <input--}}
                {{--                                                                required--}}
                {{--                                                                type="number"--}}
                {{--                                                                min="1"--}}
                {{--                                                                class="form-control"--}}
                {{--                                                                :class="[errors.has('amount') ? 'is-invalid' : '']"--}}
                {{--                                                                id="amount" name="amount"--}}
                {{--                                                                data-vv-as="&quot;Amount&quot;"--}}
                {{--                                                                placeholder="จำนวนเงิน" autocomplete="off">--}}
                {{--                                                            <div class="input-group-prepend">--}}
                {{--                                                                <span class="input-group-text">฿</span>--}}
                {{--                                                            </div>--}}
                {{--                                                        </div>--}}
                {{--                                                    </div>--}}

                {{--                                                    <div class="form-group col-sm-6">--}}
                {{--                                                        <label>วันเวลาที่ฝาก:</label>--}}
                {{--                                                        <div class="input-group">--}}
                {{--                                                            <input type="text" class="form-control" id="topup_time"/>--}}
                {{--                                                            <div class="input-group-append">--}}
                {{--                                                                <div class="input-group-text"><i--}}
                {{--                                                                        class="fa fa-calendar"></i></div>--}}
                {{--                                                            </div>--}}
                {{--                                                        </div>--}}
                {{--                                                    </div>--}}
                {{--                                                </div>--}}

                {{--                                                <button class="btn btn-primary btn-block shadow-box">ดำเนินการต่อ--}}
                {{--                                                </button>--}}
                {{--                                            </div>--}}
                {{--                                        </form>--}}

                {{--                                    </div>--}}
                {{--                                </div>--}}
                {{--                            </div>--}}
                {{--                        </div>--}}

                {{--                    </section>--}}
                {{--                @endif--}}

                <div class="card card-trans">
                    <div class="card-header text-center">
                        <p class="text-center text-color-fixed m-0">กรุณาโอนมาจากบัญชีที่ลงทะเบียนเท่านั้น</p>
                    </div>
                    <div class="card-body p-2">
                        <ul class="list-group text-dark bg-light">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {!! (!is_null($profile->bank) ? core()->showImg($profile->bank->filepic,'bank_img','','','img-bank img-fluid rounded-circle text-center bg-light d-block mx-auto ng-star-inserted') : '') !!}
                                <br>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <p class="text-dark m-0">ชื่อบัญชี :</p>
                                <span class="float-right"> {{ $profile->name }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"> เลขบัญชี:
                                <span class="float-right"> {{ $profile->acc_no }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center"> ธนาคาร:
                                <span
                                    class="float-right"> {{ (!is_null($profile->bank) ? $profile->bank->name_th : '') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('vendor/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>

        <script type="text/x-template" id="topup-content-top-template">
            <div class="card card-trans">
                <div class="card-header text-center"> บัญชีฝากเงิน</div>
                <div class="card-body">
                    <div class="row section">

                        <div class="col-3" v-for="(bank, index) in banks" @click="selectTab(bank)" :key="index">
                            <div class="section" :class="{ 'img-select': bank.isActive }" :title="bank.filepic">

                                <img class="rounded-circle transfer-slide-img m-2 d-block mx-auto img-bank pointer"
                                     :src="bank.filepic">
                            </div>
                        </div>

                    </div>
                </div>

                <slot></slot>

            </div>
        </script>

        <script type="text/x-template" id="topup-content-down-template">
            <div class="card-body p-2" v-show="isActive">
                <ul class="list-group text-dark bg-light mb-2" v-for="sub in subs">
                    <li class="list-group-item text-center">
                        <strong v-text="sub.acc_name"></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        เลขบัญชี:<span :id="sub.code" class="float-right" v-text="sub.acc_no"> </span>
                    </li>

                    <li class="list-group-item d-flex justify-content-between align-items-center pb-2">
                                <span class="text-center d-block mx-auto">
                                    <button class="btn btn-sm btn-info float-right btn-outline" :data-id="sub.code"
                                            onclick="myFunction(this)"
                                            popover="คัดลอกสำเร็จ">
                                        <i class="fa fa-clone"></i> คัดลอกเลขบัญชี
                                    </button>
                                </span>
                    </li>
                </ul>
            </div>
        </script>

        <script type="text/javascript">
            $(function () {

                $('#topup_time').daterangepicker({
                    singleDatePicker: true,
                    showDropdowns: true,
                    timePicker: true,
                    timePicker24Hour: true,
                    timePickerSeconds: false,
                    autoApply: true,
                    autoUpdateInput: true,
                    startDate: moment(),
                    minDate: moment().subtract(1, 'days').startOf('day'),
                    maxDate: moment().endOf('day'),
                    locale: {
                        format: 'DD/MM/YYYY HH:mm'
                    }, function(start, end, label) {
                        console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')');
                    }
                });
                $('#bank_time').val(moment().format('YYYY-MM-DD HH:mm'));
                $('#topup_time').on('apply.daterangepicker', function (ev, picker) {
                    var start = picker.startDate.format('YYYY-MM-DD HH:mm');
                    $('#bank_time').val(start);
                });
            })

            function myFunction(e) {
                /* Get the text field */
                var val = $(e).attr('data-id');
                console.log(val);
                var copyText = document.getElementById(val).textContent;

                var aux = document.createElement("input");

                // Assign it the value of the specified element
                aux.setAttribute("value", copyText);

                // Append it to the body
                document.body.appendChild(aux);

                // Highlight its content
                aux.select();
                aux.setSelectionRange(0, 99999); /* For mobile devices */

                /* Copy the text inside the text field */
                document.execCommand("copy");
                document.body.removeChild(aux);

                /* Alert the copied text */
                // alert("Copied the text: " + copyText.value);
            }
        </script>
        <script type="module">


            Vue.component('banks', {
                'template': '#topup-content-top-template',
                data: function () {
                    return {
                        banks: []
                    }
                },
                created() {
                    this.banks = this.$children;
                },

                methods: {
                    selectTab(selectedTab) {
                        this.banks.forEach(bank => {
                            bank.isActive = (bank.shortcode == selectedTab.shortcode);
                        });
                    }
                }
            })

            Vue.component('bank', {
                'template': '#topup-content-down-template',
                props: {
                    item: {},
                    selected: {
                        default: false
                    }
                },

                data() {
                    return {
                        isActive: false,
                        subs: {}

                    };
                },

                mounted() {
                    this.isActive = this.selected;
                    this.shortcode = this.item.shortcode;
                    this.filepic = this.item.filepic;
                    this.subs = this.item.banks_account;

                }
            })


        </script>
    @endpush

@endsection





