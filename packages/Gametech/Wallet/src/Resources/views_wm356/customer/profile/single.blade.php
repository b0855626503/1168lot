<section class="content mt-3">
    <div class="card card-trans">

        <div class="card-body text-center">
            <div class="row">
                @foreach($games as $i => $item)
                    <game-list :product="{{ json_encode($item) }}" pass="password"></game-list>
                @endforeach

            </div>
        </div>
    </div>
</section>
