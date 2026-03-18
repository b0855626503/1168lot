<!-- SECTION2 -->
<section class="section01" id="promotion">
    <div class="p-1">
        <div class="headsecion">
            <i class="far fa-gift"></i> โปรโมชั่นมากมาย
        </div>
        <div class="ctpersonal promotion">

            <div class="gridingame full">
                @foreach($promotions as $i => $item)
                    <div class="ingridgame">
                        <div class="iningridgame pro">
                            <img class="accordion" src="{{  Storage::url('promotion_img/'.$item['filepic'])  }}">
                            <div class="panel" style="">
                                <div class="inpanel">

                                    {!! $item['content'] !!}

                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach


            </div>


        </div>
    </div>
</section>
<!-- SECTION2 -->
<hr class="x-hr-border-glow my-0">

<hr class="x-hr-border-glow my-0">
