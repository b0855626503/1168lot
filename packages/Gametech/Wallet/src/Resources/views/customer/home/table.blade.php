@extends('wallet::layouts.master')

{{-- page title --}}
@section('title')



@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">
                <div class="card text-light card-trans">
                    <div class="card-body py-3 px-2">

                        <wallet-header></wallet-header>
                        @push('scripts')
                            <script type="text/x-template" id="wallet-header-template">
                                <div class="row">
                                    <div class="col-sm-12 wallet">
                                        <h4 class="wallet-heading">MY WALLET <a class="float-right"> &nbsp;<i
                                                    class="fas fa-sync-alt text-color-fixed fa-2x"></i></a></h4>
                                        <div style="opacity: 1;">
                                            <span class="wallet-money">฿ </span>
                                            <span class="wallet-balance text-color-fixed" v-text="wallet_amount"></span>
                                            <div class="text-right">
                                            <span class="point"><i class="fas fa-coins"></i> แต้มสะสม
                                                <span class="text-color-fixed"
                                                      v-text="point_amount"></span> แต้ม </span>
                                                <span class="diamond"><i class="fas fa-gem"></i> เพชรสะสม
                                                <span class="text-color-fixed"
                                                      v-text="diamond_amount"></span> เพชร </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </script>

                            <script type="text/javascript">
                                (() => {
                                    Vue.component('wallet-header', {
                                        'template': '#wallet-header-template',

                                        data: function () {
                                            return {
                                                'wallet_amount': '0.00',
                                                'point_amount': '0.00',
                                                'diamond_amount': '0.00'

                                            }
                                        },

                                        created: function () {
                                            this.updateWalletHeader();
                                        },

                                        mounted: function () {
                                            setInterval(() => {
                                                this.updateWalletHeader();
                                            }, 15000);

                                        },

                                        methods: {
                                            'updateWalletHeader': function () {
                                                this.$http.get(`${this.$root.baseUrl}/member/loadprofile`)
                                                    .then(response => {
                                                        // console.log(respo1nse);
                                                        this.wallet_amount = response.data.profile.balance;
                                                        this.point_amount = response.data.profile.point_deposit;
                                                        this.diamond_amount = response.data.profile.diamond;
                                                    })
                                                    .catch(exception => {
                                                        console.log(this.__('error.something_went_wrong'));
                                                    });
                                            }
                                        }
                                    })
                                })()
                            </script>
                        @endpush
                    </div>
                </div>

                <section class="main-menu">
                    <div class="card card-trans">
                        <div class="card-body py-1">
                            <div class="row">
                                <div class="col-4 main-menu-item px-0">
                                    <a href="/member/topup"><i class="fal fa-wallet fa-2x"></i><br>
                                        <span class="text-main"> เติมเงิน</span>
                                    </a>
                                </div>
                                <div class="col-4 main-menu-item px-0">
                                    <a href="/member/withdraw"><i class="fas fa-hand-holding-usd fa-2x"></i><br>
                                        <span class="text-main"> ถอนเงิน</span>
                                    </a>
                                </div>
                                <div class="col-4 main-menu-item px-0">
                                    <a href="/member/credit"><i class="fas fa-coins fa-2x"></i><br>
                                        <span class=" text-main"> Cashback</span>
                                    </a>
                                </div>
                            </div>
                            <hr class="m-0">
                            <div class="row">
                                <div class="col-4 main-menu-item px-0">
                                    <a href="/member/history"><i class="fal fa-history fa-2x"></i><br>
                                        <span class="text-main"> ประวัติ</span>
                                    </a>
                                </div>
                                <div class="col-4 main-menu-item px-0">
                                    <a href="/member/promotion"><i class="fal fa-gift fa-2x"></i><br>
                                        <span class="text-main"> โปรโมชั่น</span>
                                    </a>
                                </div>
                                <div class="col-4 main-menu-item px-0">
                                    <a href="/member/download"><i class="fal fal fa-download fa-2x"></i><br>
                                        <span class="text-main"> ดาวน์โหลด</span>
                                    </a>
                                </div>
                            </div>
                            <hr class="m-0">
                            <div class="row">
                                <div class="col-4 main-menu-item px-0">
                                    <a href="/member/profile"><i class="fal fa-user fa-2x"></i><br>
                                        <span class="text-main"> บัญชี</span>
                                    </a>
                                </div>
                                <div class="col-4 main-menu-item px-0">
                                    <a href="/member/contributor"><i class="fas fa-hands-helping fa-2x"></i><br>
                                        <span class="text-main"> แนะนำเพื่อน</span>
                                    </a>
                                </div>
                                <div class="col-4 main-menu-item px-0">
                                    <a href="/member/manual"><i class="fal fa-clipboard-check fa-2x"></i><br>
                                        <span class="text-main"> คู่มือ</span>
                                    </a>
                                </div>
                            </div>
                            <hr class="m-0">
                            <div class="row">
                                <div class="col-4 main-menu-item px-0">
                                    <a href="/member/reward"><i class="fas fa-bullseye fa-2x"></i><br>
                                        <span class="text-main"> หมุนวงล้อ</span>
                                    </a>
                                </div>
                                <div class="col-4 main-menu-item px-0">
                                    <a href="/member/reward/history"><i class="fas fa-history fa-2x"></i><br>
                                        <span class="text-main"> ประวัติวงล้อ</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="content mt-3">

                    @foreach($games as $i => $game)

                        <div class="card card-trans">
                            <div class="card-body">
                                <h3 class="content-heading">{{ ucfirst($i) }}</h3>
                                <div class="row">
                                    @foreach($games[$i] as $k => $item)
                                        <div class="col-6 mb-4 col-md-3">
                                            <img class="d-block mx-auto rounded-circle transfer-slide-img"
                                                 style="height: 90px;width: 90px;"
                                                 src="https://wallet.dumbovip.com/assets/images/icn-game/{!! $item['filepic'] !!}">
                                            <p class="text-main text-center mb-0 cut-text">{!! $item['name'] !!}</p>
                                            <p class="mb-0">

                                            </p>
                                            @if($item['user_code'])
                                                <p class="text-color-fixed text-center mb-0"> {!! $item['balance'] !!} </p>
                                            @else
                                                <div class="text-center mb-0">
                                                    <button class="btn btn-link p-0 mx-auto"><i
                                                            class="fas fa-user-plus text-light"></i></button>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach

                                </div>
                            </div>
                        </div>
                    @endforeach
                </section>
            </div>
        </div>
    </div>

@endsection

