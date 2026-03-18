@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('back')
    <a class="nav-link p-2 text-light mx-auto hand-point" href="{{ route('customer.home.index') }}">
        <i class="fas fa-chevron-left"></i> กลับ</a>
@endsection


@section('content')
    <div class="container text-light mt-5">
        <h3 class="text-center text-light">ประวัติ</h3>
        <p class="text-center text-color-fixed">History</p>
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">

                <banks>
                    @foreach($banks as $bank)
                        <bank :item="{{ json_encode($bank) }}" {{ $loop->first ? ':selected="true"' : '' }}></bank>
                    @endforeach

                </banks>


                <a id="back-to-top" @click.prevent="topFunction" class="btn btn-primary back-to-top" role="button"
                   aria-label="Scroll to top">
                    <i class="fas fa-chevron-up"></i>
                </a>
            </div>
        </div>
    </div>


@endsection

@push('scripts')
    <script type="text/x-template" id="topup-content-top-template">
        <div class="card card-trans">
            <div class="card-header text-center"> ประวัติ</div>

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
                    <p class="my-auto align-middle owl-expansion-panel-header-title with-arrow2 text-sm"
                       data-toggle="collapse" :href="`#transfer_${index}`" v-if="tabname == 'transfer'">
                        <img :src="item.filepic" class="list-img">&nbsp;&nbsp;
                        <strong class="m-0 my-auto align-middle" v-text="item.date_create"></strong>
                        <span class="align-middle my-auto text-right owl-expansion-panel-header-description"
                              :class="item.status">@{{ item.amount }} ฿</span>
                    </p>

                    <p class="my-auto align-middle owl-expansion-panel-header-title with-arrow text-sm top-0"
                       data-toggle="collapse" :href="`#withdraw_${index}`" v-else-if="tabname == 'withdraw'">

                        <span class="badge p-1" :class="item.status" v-text="item.status_display"></span>&nbsp;&nbsp;
                        <strong class="m-0 my-auto align-middle" v-text="item.date_create"></strong>
                        <span
                            class="align-middle my-auto text-right text-danger owl-expansion-panel-header-description">@{{ item.amount }} ฿</span>
                    </p>

                    <p class="my-auto align-middle owl-expansion-panel-header-title with-arrow text-sm top-0"
                       data-toggle="collapse" :href="`#deposit_${index}`" v-else-if="tabname == 'deposit'">

                        <strong class="m-0 my-auto align-middle" v-text="item.date_create"></strong>
                        <span
                            class="align-middle my-auto text-right text-success owl-expansion-panel-header-description">@{{ item.amount }} ฿</span>
                    </p>

                </div>


                <div :id="`transfer_${index}`" class="collapse" data-parent="#accordian_transfer">
                    <div class="card-body img100">
                        <ul class="list-group list-group-flush text-sm">
                            <li class="list-group-item d-flex justify-content-between align-items-center">ประเภท : <span
                                    class="float-right" v-text="item.transfer"></span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">เลขที่บิล :
                                <span
                                    class="float-right" v-text="item.id"></span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">เกม :
                                <span class="float-right" v-text="item.game_name"></span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">โปรโมชั่น
                                : <span class="float-right" v-text="item.promotion_name"></span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">ยอดที่ทำรายการ
                                :
                                <span class="float-right" v-text="item.amount"></span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">ยอดเงิน Wallet
                                ก่อน :
                                <span class="float-right" v-text="item.balance_before"></span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">ยอดเงิน Wallet
                                หลัง :
                                <span class="float-right" v-text="item.balance_after"></span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                ยอดโปรโมชั่น : <span class="float-right" v-text="item.credit_bonus"></span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                ยอดเงินเกม ก่อน : <span class="float-right" v-text="item.credit_before"></span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">ยอดเงินเกม
                                หลัง :
                                <span class="float-right" v-text="item.credit_after"></span></li>

                        </ul>
                    </div>
                </div>

                <div :id="`withdraw_${index}`" class="collapse" data-parent="#accordian_withdraw">
                    <div class="card-body img100">
                        <ul class="list-group text-sm">
                            <li class="list-group-item d-flex justify-content-between align-items-center">เลขที่บิล :
                                <span
                                    class="float-right" v-text="item.id"></span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">ยอดถอน :
                                <span class="float-right" v-text="item.amount"></span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                ยอดก่อนหน้า : <span class="float-right" v-text="item.credit_before"></span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">ยอดคงเหลือ :
                                <span class="float-right" v-text="item.credit_after"></span></li>
                        </ul>
                    </div>
                </div>

                <div :id="`deposit_${index}`" class="collapse" data-parent="#accordian_deposit">

                    <div class="card-body img100">

                        <ul class="list-group text-sm">
                            <li class="list-group-item d-flex justify-content-between align-items-center">ยอดฝาก :
                                <span class="float-right" v-text="item.amount"></span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">ได้รับโบนัส :
                                <span class="float-right" v-text="item.credit_bonus"></span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                ยอดก่อนหน้า : <span class="float-right" v-text="item.credit_before"></span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">ยอดหลัง :
                                <span class="float-right" v-text="item.credit_after"></span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </script>


    <script type="module">


        Vue.component('banks', {
            'template': '#topup-content-top-template',
            data: function () {
                return {
                    banks: [],
                    start: {},
                    end: {},
                    method: null,
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
                // this.$emit('clicked');

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

                    this.$http.post("{{ route('customer.history.store') }}", {
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





