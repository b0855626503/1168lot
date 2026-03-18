@extends('wallet::layouts.wheel')

{{-- page title --}}
@section('title','')


@section('content')
    <div id="main__content" data-bgset="/assets/wm356/images/index-bg.jpg?v=2"
         class="lazyload x-bg-position-center x-bg-index lazyload" style="background-image: url(&quot;/assets/wm356/images/index-bg.jpg?v=2&quot;);">
        
        
        <div class="x-index-content-main-container -logged">
            
            <div class="x-category-total-game -v2">
                <div class="container-fluid">
                    
                    <div class="ctpersonal" id="app">
                        <wheel :items="{{ json_encode($spins) }}" :spincount="{{ $profile->diamond }}"></wheel>
                    </div>
                </div>
            </div>
            
            <div class="x-provider-category -provider_casinos">
                <div class="container-fluid">
                    
                    <div class="x-account-coupon">
                        <div data-animatable="fadeInModal" class="-coupon-container animated fadeInModal">
                            <a href="javascript:void(0)" onclick="openPopup('BONUS','โบนัส')">
                                <div class="-coupon-member-detail mb-3 mt-3">
                                    <div class="-coupon-box d-flex">
                                        <img alt="คูปอง เว็บไซต์พนันออนไลน์ คาสิโนออนไลน์"
                                             class="img-fluid -ic-coupon m-auto"
                                             src="\assets\wm356\web\ezl-wm-356\img\ic-menu-promotion.png"/>
                                    </div>
                                    <div class="-text-box-container">
                                        <div class="-title-member">{{ __('app.home.sum_bonus') }}</div>
                                        <div class="-text-member">{{ $userdata->bonus }}</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                
                </div>
            </div>
        
        </div>
    
    </div>




@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/latest/TweenMax.min.js"></script>
@endpush





