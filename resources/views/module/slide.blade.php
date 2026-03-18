@if($slides)

<!-- SECTION01 -->
<section class="section01">
    <div style="--swiper-navigation-color: #fff; --swiper-pagination-color: #fff" class="swiper alertslide">
        <div class="swiper-wrapper">
            @foreach($slides as $i => $item)
            <div class="swiper-slide">
                <img src="{{  Storage::url('slide_img/'.$item['filepic'])  }}" />
            </div>
            @endforeach
        </div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-pagination"></div>
    </div>
</section>
<!-- SECTION01 -->
<hr class="x-hr-border-glow my-0">
@endif
