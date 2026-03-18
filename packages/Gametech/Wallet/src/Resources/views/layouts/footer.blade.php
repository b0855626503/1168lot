</div>
<footer class="x-footer -anon mt-auto bg-black">

    <div class="copyright mt-0">
        COPYRIGHT©2022, GAMETECH
    </div>
</footer>

<div class="myAlert-top alertcopy">
    <i class="fal fa-check-circle"></i>
    <br>
    <strong>
        คัดลอกเรียบร้อยแล้ว </strong>
</div>

<div class="x-button-actions" id="account-actions-mobile">
    <div class="-outer-wrapper">
        <div class="-left-wrapper">
      <span class="-item-wrapper">
        <span class="-ic-img">
          <span class="-text d-block">{{ __('app.home.refill') }}</span>
          <a href="{{ route('customer.topup.index') }}">
            <img src="/images/icon/deposit.png">
          </a>
        </span>
      </span>
            <span class="-item-wrapper">
        <span class="-ic-img">
          <span class="-text d-block">{{ __('app.home.withdraw') }}</span>
          <a href="{{ route('customer.withdraw.index') }}">
            <img src="/images/icon/withdraw.png">
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
            <img src="/images/icon/tab_promotion.png">
          </a>
        </span>
      </span>
            <span class="-item-wrapper">
        <span class="-ic-img">
          <span class="-text d-block">{{ __('app.home.contact') }}</span>
          <a href="javascript:void(0)">
            <img src="/images/icon/support-mobile.webp">
          </a>
        </span>
      </span>
        </div>
        <div class="-fully-overlay js-footer-lobby-overlay"></div>
    </div>
</div>


<div class="overlay"></div>
</div>

<script type="text/javascript">
    window.flashMessages = [];
    window.serverErrors = [];

    @foreach (['success', 'warning', 'error', 'info'] as $key)
    @if ($value = session($key))
    window.flashMessages.push({'type': '{{ $key }}', 'message': "{{ $value }}"});
    @endif
        @endforeach

        @if (isset($errors))
        @if (count($errors))
        window.serverErrors = @json($errors->getMessages());
    @endif
    @endif

</script>
{{--<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>--}}
<script type="text/javascript" src="{{ mix('js/manifest.js') }}"></script>
<script type="text/javascript" src="{{ mix('js/vendor.js') }}"></script>
{{--<script src="https://unpkg.com/vue@2.4.2"></script>--}}
<script type="text/javascript" src="{{ mix('js/app.js') }}"></script>
<script type="module" id="mainscript" baseUrl="{{ url()->to('/') }}" src="{{ mix('js/web.js') }}"></script>
@stack('scripts')
{{--<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>--}}
{{--<script--}}
{{--    src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>--}}
{{--<script type="text/javascript" src="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>--}}
{{--<!-- jarallax -->--}}
{{--<script src="https://unpkg.com/jarallax@1/dist/jarallax.min.js"></script>--}}
{{--<!-- AOSJS -->--}}
{{--<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>--}}
{{--<!-- Swiper -->--}}
{{--<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>--}}
{{--<script>--}}
{{--    AOS.init({--}}
{{--        once: true--}}
{{--    });--}}
{{--</script>--}}
{{--<script src="{{ asset('js/js.js') }}"></script>--}}

</body>
</html>

