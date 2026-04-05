<footer class="x-footer -ezl -anon">
    <div class="-inner-wrapper lazyload x-bg-position-center"
         data-bgset="https://asset.cloudigame.co/build/admin/img/wt_theme/ezl/footer-inner-bg.png">
        <div class="container -inner-title-wrapper">
            {!! $webconfig->content_detail !!}
        </div>


    </div>

    <div class="text-center -copy-right-container">
        <p class="mb-0 -copy-right-text">
            Copyright © 2023 {{ $webconfig->sitename }}. All Rights Reserved.
        </p>
    </div>
</footer>
@include('wallet::layouts.contact')
@auth
    <div class="x-button-actions" id="account-actions-mobile">

        <div class="-outer-wrapper">

            <div class="-left-wrapper">
            <span class="-item-wrapper">
                <span class="-ic-img">
                  <span class="-text d-block">{{ __('app.home.refill') }}</span>
                  <a href="#deposit" data-toggle="modal" data-target="#depositModal">
                    <img src="/images/icon/deposit.png"/>
                  </a>
                </span>
            </span>

                <span class="-item-wrapper">
                <span class="-ic-img">
                  <span class="-text d-block">{{ __('app.home.withdraw') }}</span>
                  <a href="#withdraw" data-toggle="modal" data-target="#withdrawModal">
                    <img src="/images/icon/withdraw.png"/>
                  </a>
                </span>
            </span>

            </div>

            <a href="{{ route('customer.home.index') }}">
            <span class="-center-wrapper js-footer-lobby-selector js-menu-mobile-container">
        <div class="-selected">
          <img src="/images/icon/menu.png">
          <h5>{{ __('app.home.playgame') }}</h5>
        </div>
      </span>
            </a>
            <div class="-fake-center-bg-wrapper">
                <svg viewBox="-10 -1 30 12">
                    <defs>
                        <linearGradient id="rectangleGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#225db9"></stop>
                            <stop offset="100%" stop-color="#041d4a"></stop>
                        </linearGradient>
                    </defs>
                    <path d="M-10 -1 H30 V12 H-10z M 5 5 m -5, 0 a 5,5 0 1,0 10,0 a 5,5 0 1,0 -10,0z"></path>
                </svg>
            </div>

            <div class="-right-wrapper">
            <span class="-item-wrapper">
                <span class="-ic-img">
                    <span class="-text d-block">{{ __('app.home.promotion') }}</span>
                      <a href="{{ route('customer.promotion.index') }}">
                        <img src="/images/icon/tab_promotion.png"/>
                      </a>
                 </span>
            </span>
                <span class="-item-wrapper">
                  <span class="-ic-img">
                      <span class="-text d-block">{{ __('app.home.contact') }}</span>
                      <a href="{{ $webconfig->linelink }}">
                        <img src="/images/icon/support-mobile.webp?v=2"/>
                      </a>
                    </span>
            </span>
            </div>

            <div class="-fully-overlay js-footer-lobby-overlay"></div>
        </div>
    </div>
@endauth
