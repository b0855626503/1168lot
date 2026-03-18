<section class="main-menu">
    <div class="card card-trans">
        <div class="card-body py-1">
            <div class="row">
                <div class="col-4 main-menu-item px-0">
                    <a href="{{ route('customer.topup.index') }}"><i
                            class="fal fa-wallet fa-2x"></i><br>
                        <span class="text-main"> เติมเงิน</span>
                    </a>
                </div>
                <div class="col-4 main-menu-item px-0">
                    <a href="{{ route('customer.withdraw.index') }}"><i
                            class="fas fa-hand-holding-usd fa-2x"></i><br>
                        <span class="text-main"> ถอนเงิน</span>
                    </a>
                </div>
                @if($config->freecredit_open === 'Y' && $profile->freecredit === 'Y')
                    <div class="col-4 main-menu-item px-0">
                        <a href="{{ route('customer.credit.index') }}"><i
                                class="fas fa-coins fa-2x"></i><br>
                            <span class=" text-main"> Cashback</span>
                        </a>
                    </div>
                @endif
                <div class="col-4 main-menu-item px-0">
                    <a href="{{ route('customer.history.index') }}"><i
                            class="fal fa-history fa-2x"></i><br>
                        <span class="text-main"> ประวัติธุรกรรม</span>
                    </a>
                </div>

                @if($config->pro_onoff === 'Y')
                    <div class="col-4 main-menu-item px-0">
                        <a href="{{ route('customer.promotion.index') }}"><i
                                class="fal fa-gift fa-2x"></i><br>
                            <span class="text-main"> โปรโมชั่น</span>
                        </a>
                    </div>
                @endif

                <div class="col-4 main-menu-item px-0">
                    <a href="{{ route('customer.download.index') }}"><i
                            class="fal fal fa-download fa-2x"></i><br>
                        <span class="text-main"> ดาวน์โหลด</span>
                    </a>
                </div>

                <div class="col-4 main-menu-item px-0">
                    <a href="{{ route('customer.profile.index') }}"><i
                            class="fal fa-user fa-2x"></i><br>
                        <span class="text-main"> บัญชี</span>
                    </a>
                </div>


                <div class="col-4 main-menu-item px-0">
                    <a href="{{ route('customer.contributor.index') }}"><i
                            class="fas fa-hands-helping fa-2x"></i><br>
                        <span class="text-main"> แนะนำเพื่อน</span>
                    </a>
                </div>


                <div class="col-4 main-menu-item px-0">
                    <a href="{{ route('customer.manual.index') }}"><i
                            class="fal fa-clipboard-check fa-2x"></i><br>
                        <span class="text-main"> คู่มือ</span>
                    </a>
                </div>

                @if($config->wheel_open === 'Y')
                    <div class="col-4 main-menu-item px-0">
                        <a href="{{ route('customer.spin.index') }}"><i
                                class="fas fa-bullseye fa-2x"></i><br>
                            <span class="text-main"> หมุนวงล้อ</span>
                        </a>
                    </div>
                    <div class="col-4 main-menu-item px-0">
                        <a href="{{ route('customer.spin_history.index') }}"><i
                                class="fas fa-history fa-2x"></i><br>
                            <span class="text-main"> ประวัติวงล้อ</span>
                        </a>
                    </div>
                @endif

                @if($config->point_open === 'Y' && $config->reward_open === 'Y')
                    <div class="col-4 main-menu-item px-0">
                        <a href="{{ route('customer.reward.index') }}"><i
                                class="fal fa-treasure-chest fa-2x"></i><br>
                            <span class="text-main"> แลกรางวัล</span>
                        </a>
                    </div>
                    <div class="col-4 main-menu-item px-0">
                        <a href="{{ route('customer.reward_history.index') }}"><i
                                class="fal fa-history fa-2x"></i><br>
                            <span class="text-main"> ประวัติการแลก</span>
                        </a>
                    </div>
                @endif

            </div>
        </div>
    </div>
</section>

<section class="content mt-3">

    @foreach($games as $i => $game)

        <div class="card card-trans">
            <div class="card-body">
                <h5 class="content-heading">{{ ucfirst($i) }}</h5>
                <div class="row">

                    @foreach($games[$i] as $k => $item)
                        <game-list :product="{{ json_encode($item) }}"></game-list>
                    @endforeach

                </div>
            </div>
        </div>
    @endforeach

</section>
