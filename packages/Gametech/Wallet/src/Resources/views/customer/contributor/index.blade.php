@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')


@section('content')

    <div class="p-1">
        <div class="headsecion">
            <i class="fas fa-user-lock"></i> {{ __('app.con.suggest') }}
        </div>
        <div class="ctpersonal">

            <div class="row">
                <div class="col-6">
                    <div class="card text-light card-trans">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h6 class="content-heading"><i class="fas fa-users"></i> {{ __('app.con.suggest_complete') }}</h6>
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
                                    <h6 class="content-heading"><i class="fas fa-hand-holding-usd"></i> {{ __('app.con.income') }}</h6>

                                    <h6 class="text-color-fixed text-right">{{ is_null($profile->payments_promotion_credit_bonus_sum) ? '0.00' : $profile->payments_promotion_credit_bonus_sum }}
                                        ฿</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row text-light">
                <div class="col-md-12">
                    <div class="card card-trans">
                        <div class="card-body copylink">

                            <div class="form-group my-2">
                                <div>
                                    <div class="el-input my-1">
                                        <i class="fal fa-user"></i>
                                        @if($config->contributor)
                                            <input id="friendlink" class="inputstyle" outsideclick="true"
                                                   data-popover="คัดลอกสำเร็จ"
                                                   type="text"
                                                   value="{{ $config->contributor }}/contributor/{{ $profile->code }}">
                                        @else
                                            <input id="friendlink" class="inputstyle" outsideclick="true"
                                                   data-popover="คัดลอกสำเร็จ"
                                                   type="text"
                                                   value="{{ route('customer.contributor.register',$profile->code) }}">
                                        @endif

                                    </div>
                                </div>
                            </div>


                            <div class="float-right iningriddps">
                                <button onclick="copylink()"><i class="fad fa-copy"></i> {{ __('app.con.copy') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="ctpersonal">
            <div class="smallcontain">
                <historys>
                    @foreach($banks as $bank)
                        <history
                            :item="{{ json_encode($bank) }}" {{ $bank['select'] == 'true' ? ':selected="true"' : '' }}></history>
                    @endforeach

                </historys>
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





