@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('back')
    <a class="nav-link p-2 text-light mx-auto hand-point" href="{{ route('customer.home.index') }}">
        <i class="fas fa-chevron-left"></i> กลับ</a>
@endsection


@section('content')
    <div class="container mt-5">
        <h3 class="text-center text-light">แนะนำเพื่อน</h3>
        <p class="text-center text-color-fixed"> แค่แนะนำเพื่อนมาเล่นกับเราก็ได้รับเงินไปฟรี ๆ</p>
        <div class="row text-light">
            <div class="col-md-10 offset-md-1 col-sm-12">
                <div class="card card-trans">
                    <div class="card-body">
                        <h5 class="content-heading"><i class="fas fa-sack-dollar"></i> ยอดเงินคงเหลือ</h5>
                        <h5 class="text-color-fixed text-right">{{ $profile->balance }} ฿ </h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-10 offset-md-1 col-sm-12">
                <div class="row">
                    <div class="col-6">
                        <div class="card text-light card-trans">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <h5 class="content-heading"><i class="fas fa-users"></i> แนะนำแล้ว</h5>
                                        <h5 class="text-color-fixed text-right">{{ $profile->downs_count }} </h5>
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
                                        <h5 class="content-heading"><i class="fas fa-hand-holding-usd"></i> รายได้</h5>
                                        <h5 class="text-color-fixed text-right">{{ is_null($profile->payments_promotion_credit_bonus_sum) ? '0.00' : $profile->payments_promotion_credit_bonus_sum }}
                                            ฿</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row text-light">
                    <div class="col-md-12">
                        <div class="card card-trans">
                            <div class="card-body">
                                <h4 class="content-heading">ลิงค์สำหรับแนะนำเพื่อน</h4>
                                @if($config->contributor)
                                    <input id="copy" class="form-control w-100" outsideclick="true"
                                           popover="คัดลอกสำเร็จ"
                                           type="text" value="{{ $config->contributor }}/register/{{ $profile->code }}">
                                @else
                                    <input id="copy" class="form-control w-100" outsideclick="true"
                                           popover="คัดลอกสำเร็จ"
                                           type="text" value="{{ route('customer.session.store',$profile->code) }}">
                                @endif

                                <br>
                                <button class="btn btn-sm btn-theme float-right btn-outline"
                                        onclick="myFunction()">
                                    <i class="fa fa-clone"></i> คัดลอก
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row text-light">
                    <div class="col-md-12">

                        <banks>
                            @foreach($banks as $bank)
                                <bank
                                    :item="{{ json_encode($bank) }}" {{ $loop->first ? ':selected="true"' : '' }}></bank>
                            @endforeach

                        </banks>


                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script type="text/x-template" id="topup-content-top-template">
        <div class="card card-trans">

            <div class="card-body">
                <div class="row section nav nav-tabs nav-fill" role="tablist">
                    <a role="tab" v-for="(bank, index) in banks" @click="selectTab(bank)" :key="index"
                       class="nav-item nav-link pointer" :class="{ 'img-select': bank.isActive }" :title="bank.name"
                       v-text="bank.name"></a>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                                <input type="text" class="form-control form-control-sm float-right"
                                       id="search_date" readonly>
                                <input type="hidden" class="form-control float-right" id="startDate"
                                       name="startDate" v-model="startDate">
                                <input type="hidden" class="form-control float-right" id="endDate"
                                       name="endDate" v-model="endDate">

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <slot></slot>

        </div>
    </script>

    <script type="text/x-template" id="topup-content-down-template">
        <div class="card-body p-2" v-show="isActive" :id="`accordian_${tabname}`">
            <div class="my-1 owl-expansion-panel" v-for="(item, index) in list" :key="index">

                <div class="card-header">
                    <p class="my-auto align-middle owl-expansion-panel-header-title with-arrow text-sm"
                       data-toggle="collapse" :href="`#contributor_${index}`" v-if="tabname == 'contributor'">

                        <strong class="m-0 my-auto align-middle" v-text="item.date_regis"></strong>
                        <span
                            class="align-middle my-auto text-right text-danger owl-expansion-panel-header-description">@{{ item.name }} </span>

                    </p>

                    <p class="my-auto align-middle owl-expansion-panel-header-title with-arrow text-sm top-0"
                       data-toggle="collapse" :href="`contributor_income_${index}`"
                       v-else-if="tabname == 'contributor_income'">

                        <strong class="m-0 my-auto align-middle" v-text="item.date_topup"></strong>
                        <span
                            class="align-middle my-auto text-right text-danger owl-expansion-panel-header-description">@{{ item.name }} </span>


                    </p>


                </div>

                {{--                <div :id="`contributor_${index}`" class="collapse" data-parent="#accordian_contributor">--}}
                {{--                    <div class="card-body img100">--}}

                {{--                    </div>--}}
                {{--                </div>--}}

                <div :id="`contributor_income_${index}`" class="collapse" data-parent="#accordian_contributor_income">
                    <div class="card-body img100">
                        <ul class="list-group text-sm">
                            <li class="list-group-item d-flex justify-content-between align-items-center">ยอดฝาก : <span
                                    class="float-right">@{{ item.amount }} ฿</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">วันที่สมัคร :
                                <span class="float-right">@{{ item.date_regis }} </span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                วันเวลาที่ฝากเงิน :
                                <span class="float-right">@{{ item.date_topup }} </span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
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





