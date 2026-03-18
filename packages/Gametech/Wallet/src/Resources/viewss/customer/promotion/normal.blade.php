<section class="content mt-3">
    @foreach($promotions as $i => $item)

        <div class="card card-trans">
            <div class="card-body">
                {!! core()->showImg($item->filepic,'promotion_img','','','w-100') !!}

                <h5 class="content-heading text-color-fixed p-2 text-center">{{ $item->name_th }}</h5>
                <div class="text-main">{!! $item->content !!}</div>
            </div>
        </div>

    @endforeach
    @foreach($pro_contents as $i => $item)

        <div class="card card-trans">
            <div class="card-body">
                {!! core()->showImg($item->filepic,'procontent_img','','','w-100') !!}

                <h5 class="content-heading text-color-fixed p-2 text-center">{{ $item->name_th }}</h5>
                <div class="text-main">{!! $item->content !!}</div>
            </div>
        </div>

    @endforeach
</section>
