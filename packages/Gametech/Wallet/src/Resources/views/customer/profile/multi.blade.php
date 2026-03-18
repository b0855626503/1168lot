<section class="content mt-3">
    <div class="card card-trans">
        <div class="card-header">Wallet</div>
        <div class="card-body">
            <div class="row">
                @foreach($games as $i => $item)
                    <game-list :product="{{ json_encode($item) }}" pass="password"></game-list>
                @endforeach

            </div>
        </div>
    </div>

    <btn-reset></btn-reset>

</section>

@if($config->freecredit_open == 'Y')
    <section class="content mt-3">
        <div class="card card-trans">
            <div class="card-header">Cashback</div>
            <div class="card-body">
                <div class="row">
                    @foreach($gamesfree as $i => $item)
                        <gamefree-list :product="{{ json_encode($item) }}"
                                       pass="password"></gamefree-list>
                    @endforeach

                </div>
            </div>
        </div>

        <btnfree-reset></btnfree-reset>
    </section>
@endif
