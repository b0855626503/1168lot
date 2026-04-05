@extends('wallet::layouts.wheel')

{{-- page title --}}
@section('title','')

@push('styles')
    <style>
        .card-trans {
            border-radius: 1em;
            background: rgba(120, 0, 0, .3607843137254902) !important;
            box-shadow: 2px 2px 5px 1px rgba(120, 0, 0, .4) !important;
        }

        .ctpersonal {


            padding: 15px;
            border-radius: 20px;
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
            padding: 10px;
            padding-top: 0;
        }

        .ctpersonal.grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        @media (max-width: 767px) {
            .ctpersonal.grid {
                display: grid;
                grid-template-columns: 1fr;
            }
        }

        .ctpersonal.trans {
            background: transparent;
            border: none;
        }

        .ctpersonal.trans.boxshw {

        }

        .iningriddps{
            text-align: center;
            font-size: 14px;
            color: #efefef;
            font-weight: 100;
            display: flex;flex-direction: row;flex-wrap: nowrap;align-content: center;justify-content: center;}
        .iningriddps span{
            text-align: center;
            font-size: 14px;
            color: white;
            font-weight: 300;
        }
        .iningriddps img{
            width: 90px;
            margin-bottom: 5px;
            margin-right: 25px;}
        .iningriddps button{
            margin-top: 5px;
            border: none;
            font-weight: 300;
            border-radius: 5px;
            color: white;
            background: linear-gradient(180deg, #ffd600 0, #e08700) !important;

            padding: 3px 15px;
        }
        .iningriddps button i{
            color: #ffffff;
        }
        .leftdps{
            box-shadow: 3px -2px 7px #00000052;
            border-radius: 10px;
        }
        .leftdps .nav-pills .nav-link.active, .nav-pills .show>.nav-link {
            color: #fff;
            background: linear-gradient(180deg, #ffd600 0, #e08700) !important;
            font-weight: 500;}
        .leftdps .nav-pills .nav-link {
            padding: 20px 0;
            text-align: center;
            font-size: 13px;

            font-weight: 200;}
        @media (max-width: 440px){
            .iningriddps{
                text-align: center;
                font-size: 3.4vw;
                color: #ffffff;
            }
        }
        @media (max-width: 405px){

            .ingriddps{
                padding-bottom:  15px;
            }
            .iningriddps img{
                width: 67px;
            }

        }
        @media (max-width:380px){
            .griddps{
                grid-template-columns:  1fr;
            }
        }
    </style>
@endpush


@section('content')
    <div id="main__content" data-bgset="/assets/wm356/images/index-bg.jpg?v=2"
         class="lazyload x-bg-position-center x-bg-index lazyload" style="background-image: url(&quot;/assets/wm356/images/index-bg.jpg?v=2&quot;);">


        <div class="x-index-content-main-container -logged">
            <div class="x-quick-transaction-buttons js-quick-transaction-buttons">
                <a class="btn -btn -promotion -vertical" href="{{ route('customer.promotion.index') }}" target="_blank"
                   rel="noopener nofollow">
                <span class="-ic-wrapper"> <img alt="โปรโมชั่นสุดคุ้ม เพื่อลูกค้าคนสำคัญ" class="img-fluid -ic"
                                                width="40" height="40"
                                                src="/assets/wm356/images/ic-quick-transaction-button-promotion.png?v=2"/></span>

                    <span class="-btn-inner-content">
            <span class="-btn-inner-content-title">{{ __('app.home.promotion') }}</span>
        </span>
                </a>

                <button
                    class="btn -btn -deposit x-bg-position-center lazyloaded"
                    data-toggle="modal"
                    data-target="#depositModal"
                    data-bgset="build/images/btn-deposit-bg.png?v=2"
                    style="background-image: url('/assets/wm356/images/btn-deposit-bg.png?v=2');"
                >
                <span class="-ic-wrapper"> <img alt="ฝากเงินง่ายๆ ด้วยระบบออโต้ การันตี 1 นาที" class="img-fluid -ic"
                                                width="40" height="40"
                                                src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-deposit.png"/></span>

                    <span class="-btn-inner-content">
            <span class="-btn-inner-content-title">{{ __('app.home.refill') }}</span>
        </span>
                </button>

                <button
                    class="btn -btn -withdraw x-bg-position-center lazyloaded"
                    data-toggle="modal"
                    data-target="#withdrawModal"
                    data-bgset="build/images/btn-withdraw-bg.png?v=2"
                    style="background-image: url('/assets/wm356/images/btn-withdraw-bg.png?v=2');"
                >
                <span class="-ic-wrapper"> <img alt="ถอนเงินง่ายๆ ด้วยระบบออโต้ การันตี เท่าไหร่ก็จ่าย"
                                                class="img-fluid -ic" width="40" height="40"
                                                src="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/ic-account-withdraw.png"/></span>

                    <span class="-btn-inner-content">
            <span class="-btn-inner-content-title">{{ __('app.home.withdraw') }}</span>
        </span>
                </button>
            </div>

            <div class="x-category-total-game -v2">
                <div class="container-fluid">

                    <div>
                        <div class="ctpersonal">

                            <div class="row">
                                <div class="col-6">
                                    <div class="card text-light card-trans">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <h6 class="content-heading"><i
                                                            class="fas fa-users"></i> {{ __('app.con.suggest_complete') }}
                                                    </h6>
                                                    <h6 class="text-color-fixed text-right">{{ $profile->downs_count }} </h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card text-light card-trans">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <h6 class="content-heading"><i
                                                            class="fas fa-hand-holding-usd"></i> {{ __('app.con.income') }}
                                                    </h6>

                                                    <h6 class="text-color-fixed text-right">{{ is_null($profile->payments_promotion_credit_bonus_sum) ? '0.00' : $profile->payments_promotion_credit_bonus_sum }}
                                                        ฿</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row text-light mt-3">
                                <div class="col-md-12">
                                    <div class="card card-trans">
                                        <div class="card-body copylink">

                                            <div class="form-group my-2">
                                                <div>
                                                    <div class="el-input my-1">

                                                        @if($webconfig->contributor)
                                                            <input id="friendlink" class="form-control"
                                                                   outsideclick="true"
                                                                   data-popover="คัดลอกสำเร็จ"
                                                                   type="text"
                                                                   value="{{ $webconfig->contributor }}/contributor/{{ $profile->code }}">
                                                        @else
                                                            <input id="friendlink" class="form-control"
                                                                   outsideclick="true"
                                                                   data-popover="คัดลอกสำเร็จ"
                                                                   type="text"
                                                                   value="{{ route('customer.contributor.register',$profile->code) }}">
                                                        @endif

                                                    </div>
                                                </div>
                                            </div>


                                            <div class="float-right iningriddps">
                                                <button onclick="copylink()" class="btn"><i
                                                        class="fa fa-copy"></i> {{ __('app.con.copy') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="ctpersonal mt-5">
                            <div class="smallcontain" id="app">
                                <historys>
                                    @foreach($banks as $bank)
                                        <history
                                            :item="{{ json_encode($bank) }}" {{ $bank['select'] == 'true' ? ':selected="true"' : '' }}></history>
                                    @endforeach

                                </historys>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>

    </div>

@endsection

@push('scripts')
    <script type="text/x-template" id="topup-content-top-template">
        <div class="ctpersonal trans boxshw">

            <div class="row mt-3">
                <div class="col-2 p-0 leftdps">
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <a class="nav-link" :class="[{ active: bank.isActive } , bank.tabcolor ]"
                           v-for="(bank, index) in banks" @click="selectTab(bank)" :key="index" :id="bank.tabid"
                           data-toggle="pill" :href="bank.tabhref" role="tab" aria-controls="v-pills-dps"
                           :aria-selected="bank.tabselect" :title="bank.name"
                           v-text="bank.name"></a>
                    </div>
                </div>
                <div class="col-10 p-0 containhislist">
                    <div class="tab-content" id="v-pills-tabContent">
                        <slot></slot>
                    </div>
                </div>
            </div>

        </div>

    </script>

    <script type="text/x-template" id="topup-content-down-template">
        <div class="tab-pane fade" :class="[{ active : isActive } , { show : isActive}]" role="tabpanel"
             aria-labelledby="v-pills-dps-tab" v-show="isActive" :id="tabname">
            <div class="containerhis">
                <!--  Loop list DPS -->
                <div :class="[ tabname === 'deposit' ? 'listhtwd' : 'listht']" v-for="(item, index) in list"
                     :key="index">

                    <table>
                        <tbody>
                        <tr>
                            <td>
                                <span v-text="item.id"></span>
                            </td>
                            <td>
                                <span v-text="item.amount"></span><br>
                                <span class="timehis" v-text="item.date_create"></span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <!--  END Loop list DPS -->
            </div>
        </div>

    </script>

    <script>

        Vue.component('historys', {
            'template': '#topup-content-top-template',
            data: function () {
                return {
                    banks: [],
                    start: {},
                    end: {},
                    method: null,
                    startDate: null,
                    endDate: null,
                    daterangepicker: null,
                    money: 0

                }
            },
            created() {
                this.banks = this.$children;

            },
            // watch: {
            //     startDate: function (event) {
            //         // console.log('startDAte '+event);
            //         this.loadData();
            //     },
            // },
            mounted() {
                this.method = this.banks[0].method;
                this.loadData();
            },
            provide() {
                return {
                    banks: this
                };
            },
            methods: {

                selectTab(selectedTab) {
                    let this_this = this;
                    this.banks.forEach(bank => {
                        bank.isActive = (bank.method == selectedTab.method);
                        if (bank.isActive == true) {
                            this.method = selectedTab.method;
                            this_this.loadData();
                        }
                    });
                },
                loadData: function () {
                    console.log('Clicked evemt');

                    this.$http.post("{{ route('customer.contributor.store') }}", {
                        'id': this.method
                    })
                        .then(response => {
                            if (response.status) {
                                this.banks.forEach(bank => {
                                    bank.isActive = (bank.method == this.method);
                                    if (bank.isActive == true) {
                                        bank.list = response.data.data;
                                        bank.tabname = this.method;
                                    } else {
                                        bank.list = '';
                                    }
                                });

                            }
                        })
                        .catch(exception => {
                            console.log('error');
                        });
                },
            }
        })

        Vue.component('history', {
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
                    list: [],
                    tabname: '',
                    tabid: "",
                    tabhref: "",
                    tabcolor: "",
                    tabselect: "",
                    status: 0,

                };
            },

            mounted() {
                this.isActive = this.selected;
                this.name = this.item.name;
                this.method = this.item.method;
                this.tabname = this.item.method;
                this.tabid = this.item.id;
                this.tabcolor = this.item.color;
                this.tabhref = this.item.href;
                this.tabselect = this.item.select;
                this.status = this.item.status;
            },
            applyFilter: function (field, date) {
                this[field] = date;

                // window.location.href = "?start=" + this.start + '&end=' + this.end;
            }
        })


    </script>




    <script type="text/javascript">
        function myFunction() {
            /* Get the text field */
            var copyText = document.getElementById("copy");

            /* Select the text field */
            copyText.select();
            copyText.setSelectionRange(0, 99999); /* For mobile devices */

            /* Copy the text inside the text field */
            document.execCommand("copy");

            /* Alert the copied text */
            // alert("Copied the text: " + copyText.value);
        }
    </script>

    <script type="module">

        Vue.component('banks', {
            'template': '#topup-content-top-template',
            data: function () {
                return {
                    banks: [],
                    list: {},
                    start: {},
                    end: {},
                    startDate: null,
                    endDate: null,
                    daterangepicker: null

                }
            },
            created() {
                this.banks = this.$children;
            },
            watch: {
                startDate: function (event) {
                    // console.log('startDAte '+event);
                    this.loadData();
                },
                // endDate: function(event) {
                //     console.log('endDate '+event);
                //     this.loadData();
                // }
            },
            mounted() {
                let this_this = this;

                this.method = this.banks[0].method;

                this.daterangepicker = $('#search_date');

                this.daterangepicker.daterangepicker({
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
                        'เมื่อวานนี้': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                        '7 วันที่ผ่านมา': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
                        '30 วันที่ผ่านมา': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
                        'เดือนนี้': [moment().startOf('month').startOf('day'), moment().endOf('month').endOf('day')],
                        'เดือนที่ผ่านมา': [moment().subtract(1, 'month').startOf('month').startOf('day'), moment().subtract(1, 'month').endOf('month').endOf('day')]
                    }
                });


                this_this.startDate = moment().startOf('day').format('YYYY-MM-DD HH:mm:ss');
                this_this.endDate = moment().endOf('day').format('YYYY-MM-DD HH:mm:ss');

                this.daterangepicker.on('apply.daterangepicker', function (ev, picker) {
                    let start = picker.startDate.format('YYYY-MM-DD HH:mm:ss');
                    let end = picker.endDate.format('YYYY-MM-DD HH:mm:ss');
                    this_this.startDate = start;
                    this_this.endDate = end;
                    this_this.loadData();
                    // console.log('start '+this_this.startDate);

                });

            },
            provide() {
                return {
                    banks: this
                };
            },
            methods: {
                selectTab(selectedTab) {
                    let this_this = this;
                    this.banks.forEach(bank => {
                        bank.isActive = (bank.method == selectedTab.method);
                        if (bank.isActive == true) {
                            this.method = selectedTab.method;
                            this_this.loadData();
                        }
                    });
                },
                loadData: function () {
                    console.log('Clicked evemt');

                    this.$http.post("{{ route('customer.contributor.store') }}", {
                        'id': this.method,
                        'date_start': this.startDate,
                        'date_stop': this.endDate
                    })
                        .then(response => {
                            if (response.status) {
                                this.banks.forEach(bank => {
                                    bank.isActive = (bank.method == this.method);
                                    if (bank.isActive == true) {
                                        bank.list = response.data.data;
                                        bank.tabname = this.method;
                                    } else {
                                        bank.list = '';
                                    }
                                });

                            }
                        })
                        .catch(exception => {
                            console.log('error');
                        });
                },

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
                    list: [],
                    tabname: '',
                    start: "",
                    end: "",

                };
            },

            mounted() {
                this.isActive = this.selected;
                this.name = this.item.name;
                this.method = this.item.method;
                this.tabname = this.item.method;
            },
            applyFilter: function (field, date) {
                this[field] = date;

                // window.location.href = "?start=" + this.start + '&end=' + this.end;
            }
        })

    </script>
@endpush





