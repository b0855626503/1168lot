@foreach($games as $i => $game)
    <div class="card card-trans">
        <div class="card-body">
            <h5 class="content-heading">{{ ucfirst($i) }}</h5>
            <div class="row text-center">

                @foreach($games[$i] as $k => $item)

                    <div class="col-4 mb-4 col-md-3">
                        <a class="btn btn-link p-0 mx-auto" href="{{ route('customer.game.list', ['id' => Str::lower($item->id)]) }}">
                        <img
                            loading="lazy"
                            alt="{{ $item->name }}"
                            src="{{ Storage::url('game_img/' . $item->filepic).'?'.microtime() }}"
                            data-src="{{ Storage::url('game_img/' . $item->filepic).'?'.microtime() }}"
                            class="d-block mx-auto rounded-circle transfer-slide-img h-90 w-90"/>
                        <p class="text-main text-center mb-0 cut-text text-small">{{ $item->name }}</p>
                        <p class="mb-0"></p>
                        </a>
                    </div>
                @endforeach

            </div>
        </div>
    </div>
@endforeach
