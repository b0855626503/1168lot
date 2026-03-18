
@foreach($games as $i => $game)
    <div class="tabcontent" id="{{ strtolower($i) }}tab" style="display: {{ ($loop->first ? 'block' : 'none') }};">
        <div class="gridgame alone">
            @foreach($games[$i] as $k => $item)
            <div class="ingridgame">
                <div class="iningridgame">
                    <a href="{{ route('customer.game.list', ['id' => Str::lower($item->id)]) }}"  title="{{ $item->name }}">
                        <img
                            loading="lazy"
                            src="{{ Storage::url('game_img/' . $item->filepic).'?'.microtime() }}"
                            data-src="{{ Storage::url('game_img/' . $item->filepic).'?'.microtime() }}"
                            alt="{{ $item->name }}"
                            title="{{ $item->name }}"
                        >
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
@endforeach
