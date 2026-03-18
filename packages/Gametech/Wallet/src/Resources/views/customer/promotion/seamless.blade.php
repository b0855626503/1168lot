<div class="p-1">
    <div class="headsecion">
        <i class="far fa-gift"></i> {{ __('app.home.promotion') }}
    </div>
    <div class="ctpersonal promotion">

        <div class="gridingame full">
            @foreach($promotions as $i => $item)
                <div class="ingridgame">
                    <div class="iningridgame pro">
                        <img class="accordion" src="{{  Storage::url('promotion_img/'.$item->filepic)  }}">
                        <div class="panel" style="">
                            <div class="inpanel">

                                {!! $item->content !!}

                                <form method="POST" action="{{ route('customer.promotion.store') }}"
                                      @submit.stop.prevent="onSubmit">
                                    @csrf
                                    <input type="hidden" name="promotion" id="promotion" value="{{ $item->code }}">
                                    @if($pro_limit > 0)
                                        <button class="btnLogin my-3"><span>รับโปรโมชั่น</span></button>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            @foreach($pro_contents as $i => $item)
                    <div class="ingridgame">
                        <div class="iningridgame pro">
                            <img class="accordion" src="{{  Storage::url('procontent_img/'.$item->filepic)  }}">
                            <div class="panel" style="">
                                <div class="inpanel">

                                    {!! $item->content !!}

                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

        </div>


    </div>

</div>
