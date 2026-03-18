@extends('wallet::layouts.master')

{{-- page title --}}
@section('title')



@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">
                <div class="card text-light card-trans">
                    <div class="card-body py-3 px-2">
                        <div class="row">
                            <div class="col-sm-12 wallet">
                                <h4 class="wallet-heading">MY WALLET <a class="float-right"> &nbsp;<i
                                            class="fas fa-sync-alt text-color-fixed fa-2x"></i></a></h4>
                                <div style="opacity: 1;">
                                    <span class="wallet-money">฿</span>
                                    <span class="wallet-balance text-color-fixed"> 0.00</span>
                                    <div class="text-right">
                                        <span class="point">
                                            <i class="fas fa-coins"></i> แต้มสะสม <span
                                                class="text-color-fixed "> 0.00</span> แต้ม </span><span
                                            class="diamond"><i class="fas fa-gem"></i> เพชรสะสม <span
                                                class="text-color-fixed "> 0.00</span> เพชร </span>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                    <a href="/member/promotions"><i class="fal fa-gift fa-2x"></i><br>
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
                                    <a href="/member/rewardhis"><i class="fas fa-history fa-2x"></i><br>
                                        <span class="text-main"> ประวัติวงล้อ</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="content mt-3">

                    <div class="card card-trans">
                        <div class="card-body">
                            <h3 class="content-heading">Slot</h3>
                            <div class="row">

                                <div class="col-4  mb-4 col-md-3 slider">
                                    <img class="d-block mx-auto rounded-circle transfer-slide-img"
                                         style="height: 90px;width: 90px;"
                                         src="https://wallet.dumbovip.com/assets/images/icn-game/918kiss.png">
                                    <p class="text-main text-center mb-0 cut-text">918Kiss</p>
                                    <p class="mb-0">

                                    </p>
                                    <p class="text-color-fixed text-center  mb-0"> 0.00 ฿ </p>
                                </div>
                                <div class="col-4  mb-4 col-md-3 slider">
                                    <img class="d-block mx-auto rounded-circle transfer-slide-img"
                                         style="height: 90px;width: 90px;"
                                         src="https://wallet.dumbovip.com/assets/images/icn-game/pussy888.png">
                                    <p class="text-main text-center mb-0 cut-text">Pussy888</p>
                                    <p class="mb-0"></p>
                                    <p class="text-color-fixed text-center  mb-0">
                                        0.00 ฿ </p></div>
                                <div class="col-4  mb-4 col-md-3 slider"><img
                                        class="d-block mx-auto rounded-circle transfer-slide-img"
                                        style="height: 90px;width: 90px;"
                                        src="https://wallet.dumbovip.com/assets/images/icn-game/joker.png">
                                    <p class="text-main text-center mb-0 cut-text">Joker</p>
                                    <p class="mb-0"></p>
                                    <p class="text-color-fixed text-center  mb-0">
                                        0.00 ฿ </p></div>
                                <div class="col-4  mb-4 col-md-3 slider"><img
                                        class="d-block mx-auto rounded-circle transfer-slide-img"
                                        style="height: 90px;width: 90px;"
                                        src="https://wallet.dumbovip.com/assets/images/icn-game/slotxo.png">
                                    <p class="text-main text-center mb-0 cut-text">Slot XO</p>
                                    <p class="mb-0"></p>
                                    <p class="text-color-fixed text-center  mb-0">
                                        0.00 ฿ </p></div>
                                <div class="col-4  mb-4 col-md-3 slider"><img
                                        class="d-block mx-auto rounded-circle transfer-slide-img"
                                        style="height: 90px;width: 90px;"
                                        src="https://wallet.dumbovip.com/assets/images/icn-game/live22.png">
                                    <p class="text-main text-center mb-0 cut-text">Live22</p>
                                    <p class="mb-0"></p>
                                    <button class="btn btn-link p-0 mx-auto"><i
                                            class="fas fa-user-plus text-light"></i></button>
                                </div>
                                <div class="col-4  mb-4 col-md-3 slider"><img
                                        class="d-block mx-auto rounded-circle transfer-slide-img"
                                        style="height: 90px;width: 90px;"
                                        src="https://wallet.dumbovip.com/assets/images/icn-game/slotx.png">
                                    <p class="text-main text-center mb-0 cut-text">SLOTX</p>
                                    <p class="mb-0"></p>
                                    <button class="btn btn-link p-0 mx-auto"><i
                                            class="fas fa-user-plus text-light"></i></button>
                                </div>
                                <div class="col-4  mb-4 col-md-3 slider"><img
                                        class="d-block mx-auto rounded-circle transfer-slide-img"
                                        style="height: 90px;width: 90px;"
                                        src="https://wallet.dumbovip.com/assets/images/icn-game/pgslot.png">
                                    <p class="text-main text-center mb-0 cut-text">PGSlot</p>
                                    <p class="mb-0"></p>
                                    <button class="btn btn-link p-0 mx-auto"><i
                                            class="fas fa-user-plus text-light"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

@endsection

