@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')
    @if(count($slides) > 0)

        <!-- SECTION01 -->
        <div class="container">
            <div class="row">
                <div class="col-md-8 offset-md-2 col-sm-12">
                    <div class="card text-light card-trans">

                        <swiper-container class="swiper alertslide" pagination="true" pagination-clickable="true" navigation="true" space-between="30"
                                          loop="true" entered-slides="true" autoplay-delay="2500" autoplay-disable-on-interaction="false">
                            @foreach($slides as $i => $item)
                            <swiper-slide><img src="{{  Storage::url('slide_img/'.$item['filepic'])  }}"/></swiper-slide>
                            @endforeach

                        </swiper-container>

                    </div>
                </div>
            </div>
        </div>

    @endif


    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">
                <div class="card text-light card-trans">
                    <div class="card-body py-3 px-2">
                        @if($config->seamless == 'Y')
                            <seamless></seamless>
                        @else
                            @if($config->multigame_open == 'Y')
                                <wallet></wallet>
                            @else
                                <credit></credit>
                            @endif
                        @endif
                    </div>
                </div>

                @if($config->seamless == 'Y')
                    @include('wallet::customer.home.seamless')
                @else
                    @if($config->multigame_open == 'Y')
                        @include('wallet::customer.home.multi')
                    @else
                        @include('wallet::customer.home.single')
                    @endif
                @endif
            </div>
        </div>
    </div>

@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-element-bundle.min.js"></script>
@endpush
