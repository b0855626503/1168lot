<section class="main-menu">
    <div class="card card-trans">
        <div class="card-body py-1">
            <div class="row">
                <div class="col-4 main-menu-item px-0">
                    <a href="{{ route('customer.credit.transfer.game.index') }}"><i
                            class="fas fa-exchange fa-2x"></i><br>
                        <span class="text-main"> โยกเงิน</span>
                    </a>
                </div>
                <div class="col-4 main-menu-item px-0">
                    <a href="{{ route('customer.credit.withdraw.index') }}"><i
                            class="fas fa-hand-holding-usd fa-2x"></i><br>
                        <span class="text-main"> ถอนเงิน</span>
                    </a>
                </div>
                <div class="col-4 main-menu-item px-0">
                    <a href="{{ route('customer.credit.history.index') }}"><i
                            class="fal fa-history fa-2x"></i><br>
                        <span class=" text-main"> ประวัติ</span>
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
                <h5 class="content-heading">{{ ucfirst($i) }}</h5>
                <div class="row">

                    @foreach($games[$i] as $k => $item)
                        <gamefree-list :product="{{ json_encode($item) }}"></gamefree-list>
                    @endforeach

                </div>
            </div>
        </div>
    @endforeach

</section>
