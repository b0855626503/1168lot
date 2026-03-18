<section class="content mt-3">
    @foreach($promotions as $i => $item)
        <form method="POST" action="{{ route('customer.promotion.store') }}"
              @submit.stop.prevent="onSubmit">
            @csrf
            <input type="hidden" name="promotion" id="promotion" value="{{ $item->code }}">
            <div class="card card-trans">
                <div class="card-body">
                    {!! core()->showImg($item->filepic,'promotion_img','','','w-100') !!}

                    <h5 class="content-heading text-color-fixed p-2 text-center">{{ $item->name_th }}</h5>
                    <div class="text-main">{!! $item->content !!}</div>
                </div>
                @if($pro_limit > 0)
                    <div class="card-footer text-muted text-center">
                        <button class="btn btn-outline-dark btn-block btn-circle"><i class="fa fa-gift"></i>
                            รับโปรโมชั่นนี้
                        </button>
                    </div>
                @endif
            </div>
        </form>
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
