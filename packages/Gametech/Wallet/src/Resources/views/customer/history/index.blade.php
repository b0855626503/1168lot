@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')


@section('content')

    <div class="p-1">
        <div class="headsecion">
            <i class="far fa-history"></i> {{ __('app.home.history') }}
        </div>

        <historys>
            @foreach($banks as $bank)
                <history :item="{{ json_encode($bank) }}" {{ $bank['select'] == 'true' ? ':selected="true"' : '' }}></history>
            @endforeach

        </historys>


    </div>

@endsection



@push('scripts')
    <script type="text/x-template" id="topup-content-top-template">
        <div class="ctpersonal trans boxshw">

            <div class="row mt-3">
                <div class="col-2 p-0 leftdps">
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <a class="nav-link" :class="[{ active: bank.isActive } , bank.tabcolor ]"  v-for="(bank, index) in banks" @click="selectTab(bank)" :key="index" :id="bank.tabid" data-toggle="pill" :href="bank.tabhref" role="tab" aria-controls="v-pills-dps" :aria-selected="bank.tabselect"  :title="bank.name"
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
        <div class="tab-pane fade" :class="[{ active : isActive } , { show : isActive}]"  role="tabpanel" aria-labelledby="v-pills-dps-tab" v-show="isActive" :id="tabname">
            <div class="containerhis">
                <!--  Loop list DPS -->
                <div :class="[ tabname === 'deposit' ? 'listhtwd' : 'listht']" v-for="(item, index) in list" :key="index">
                    <span class="badge rounded-pill" :class="item.status_color" v-text="item.status_display"></span>
                    <table>
                        <tbody>
                        <tr>
                            <td>
                                <span v-text="item.id"></span>
                            </td>
                            <td>
                                <span v-text="item.amount"></span>  {{ __('app.home.withdraw_baht') }}<br>
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
                    money:0

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

                    this.$http.post("{{ route('customer.history.store') }}", {
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
@endpush






