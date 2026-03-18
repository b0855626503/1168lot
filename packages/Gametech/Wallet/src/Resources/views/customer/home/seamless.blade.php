
<!-- SECTION2 -->
<section class="section02">
    <div class="contenttabsec02">
        <div class="tabctsec02">
            <div class="row m-0 ">
                @foreach($games as $i => $game)
                    <div class="col px-1">
                        <div class="tablinks {{ $loop->first ? 'active' : '' }}" onclick="opentabgame(event, '{{ strtolower($i) }}tab')">
                            <button>
                                <img src="/images/icon/{{ strtolower($i) }}.png">
                                <span>{{ ucfirst($i) }}</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <hr class="x-hr-border-glow my-0">
    </div>
    <div class="contain02">  @include('wallet::customer.home.gametab') </div>
</section>
<!-- SECTION2 -->
<hr class="x-hr-border-glow my-0">
