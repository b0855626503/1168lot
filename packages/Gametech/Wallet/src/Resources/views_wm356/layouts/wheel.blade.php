<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com/" crossorigin=""/>
    <link rel="dns-prefetch" href="//fonts.googleapis.com/"/>
    
    
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Kanit&display=swap"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit&display=swap">
    </noscript>
    
    <meta charset="UTF-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" href="{!! core()->imgurl($config->favicon,'img') !!}">
    <link rel="icon" type="image/x-icon" href="{!! core()->imgurl($config->favicon,'img') !!}">
    <link rel="apple-touch-icon" sizes="60x60" href="{!! core()->imgurl($config->favicon,'img') !!}">
    <meta name="apple-mobile-web-app-title" content="{{ ucwords($config->sitename) }} - {{ $config->title }}"/>
    <title>{{ ucwords($config->sitename) }} - {{ $config->title }}</title>
    <meta name="description" content="{{ $config->description }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="keywords"
          content="slot, casino, pgslot, joker, บาคาร่าออนไลน์, พนันออนไลน์, เว็บพนันออนไลน์, คาสิโนออนไลน์, บาคาร่า, บอลออนไลน์, สล็อต, ค่าน้ำดีที่สุด, เว็บพนัน, เกมสล็อต, นักพนัน"/>
    
    <meta property="og:title" content="{{ ucwords($config->sitename) }} - {{ $config->title }}"/>
    <meta property="og:description"
          content="{{ $config->description }}"/>
    <meta property="og:locale" content="{{ config('app.locale') }}"/>
    <meta property="og:site_name" content="{{ ucwords($config->sitename) }}"/>
    <meta property="og:url" content="{{ url('') }}"/>
    <meta property="og:image" content="{{ url(core()->imgurl($config->logo,'img')) }}"/>
    
    <link rel="canonical" href="{{ url('/') }}"/>
    
    <meta name="twitter:site" content="@twitter"/>
    <meta name="twitter:card" content="summary"/>
    <meta name="twitter:title" content="{{ ucwords($config->sitename) }} - {{ $config->title }}"/>
    <meta name="twitter:description"
          content="{{ $config->description }}"/>
    <meta name="twitter:image" content="{{ url(core()->imgurl($config->logo,'img')) }}"/>
    
    
    <link preload href="{!! url(core()->imgurl($config->favicon,'img')) !!}" as="style"
          onload="this.onload=null;this.rel='icon'" crossorigin=""/>
    <noscript>
        <link rel="icon" href="{!! url(core()->imgurl($config->favicon,'img')) !!}"/>
    </noscript>
    <meta name="msapplication-TileColor" content="#ffffff"/>
    <meta name="msapplication-TileImage" content="/assets/wm356/images/ms-icon-144x144.png"/>
    <meta name="theme-color" content="#ffffff"/>
    
    <meta name="format-detection" content="telephone=no"/>
    
    
    <link rel="preload" as="style"
          href="/assets/wm356/css/style_wallet.css?v={{ filemtime(public_path('assets/wm356/css/style_wallet.css')) }}"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet"
              href="/assets/wm356/css/style_wallet.css?v={{ filemtime(public_path('assets/wm356/css/style_wallet.css')) }}">
    </noscript>
    
    <link rel="stylesheet"
          href="/assets/wm356/css/addon.css?v={{ filemtime(public_path('assets/wm356/css/addon.css')) }}">
    <link
            rel="preload"
            href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/7.5.0/css/flag-icons.min.css"
            as="style"
            type="text/css"
            integrity="sha512-+WVTaUIzUw5LFzqIqXOT3JVAc5SrMuvHm230I9QAZa6s+QRk8NDPswbHo2miIZj3yiFyV9lAgzO1wVrjdoO4tw=="
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
    />
    
    <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/7.5.0/css/flag-icons.min.css"
            integrity="sha512-+WVTaUIzUw5LFzqIqXOT3JVAc5SrMuvHm230I9QAZa6s+QRk8NDPswbHo2miIZj3yiFyV9lAgzO1wVrjdoO4tw=="
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
    />
    
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin="anonymous"/>
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com"/>
    
    <link
            rel="preload"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
            as="style"
            type="text/css"
            integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
    />
    
    <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
            integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
    />
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css"
          integrity="sha512-jU/7UFiaW5UBGODEopEqnbIAHOI8fO6T99m7Tsmqs2gkdujByJfkCbbfPSN4Wlqlb9TGnsuC0YgUgWkRBK7B9A=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">
    
    <script type="text/javascript">
        window["gif64"] = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";
        window["Bonn"] = {
            boots: [],
            inits: [],
        };
    </script>
    
    <style>
        .x-header {
            background: {{ ($config->wallet_navbar_color? $config->wallet_navbar_color :'#1d1d1d') }}                         !important;
        }
        
        .x-footer.-ezl .-copy-right-container {
            background-color: {{ ($config->wallet_footer_color?$config->wallet_footer_color:'#255b48') }}                         !important;
        }
        
        #account-actions-mobile .-left-wrapper, #account-actions-mobile .-right-wrapper {
            background: linear-gradient(180deg, {{ ($config->wallet_footer_exchange?$config->wallet_footer_exchange:'#0d0d0d') }}, {{ ($config->wallet_footer_exchange?$config->wallet_footer_exchange:'#000000') }});
        }
        
        #account-actions-mobile .-center-wrapper {
            background: linear-gradient(180deg, {{ ($config->wallet_footer_exchange?$config->wallet_footer_exchange:'#0d0d0d') }}, {{ ($config->wallet_footer_exchange?$config->wallet_footer_exchange:'#000000') }});
        }
        
        #account-actions-mobile .-fake-center-bg-wrapper svg path {
            fill: {{ ($config->wallet_footer_exchange?$config->wallet_footer_exchange:'url(#rectangleGradient)') }}
        
        
        
        }
    </style>
    
    @if($config->header_code)
        {!! $config->header_code !!}
    @endif
    
    @stack('styles')
    @stack('script')
    
    <link rel="preload" href="{{ asset('lang-').app()->getLocale() }}.js?v={{ date('Ymdhi') }}" as="script">
    <link rel="preload"
          href="{{ asset('assets/wm356/js/minified_safe_optimized_no_jquery_bundle.js?v='. filemtime(public_path('assets/wm356/js/minified_safe_optimized_no_jquery_bundle.js'))) }}"
          as="script">
    <link rel="preload"
          href="{{ asset('assets/wm356/js/js_wallet.js?v='. filemtime(public_path('assets/wm356/js/js_wallet.js'))) }}"
          as="script">
    <link rel="preload" href="{{ mix('assets/wm356/js/manifest.js') }}" as="script">
    <link rel="preload" href="{{ mix('assets/wm356/js/vendor.js') }}" as="script">
    <link rel="preload" href="{{ mix('assets/wm356/js/app.js') }}" as="script">
    
    
    <script src="{{ mix('assets/wm356/js/manifest.js') }}"></script>
    <script src="{{ mix('assets/wm356/js/vendor.js') }}"></script>
    <script src="{{ mix('assets/wm356/js/app.js') }}" id="mainscript" baseUrl="{{ url()->to('/') }}"></script>
    
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"
            integrity="sha512-U2WE1ktpMTuRBPoCFDzomoIorbOyUv0sP8B+INA3EzNAhehbzED1rOJg6bCqPf/Tuposxb5ja/MAUnC8THSbLQ=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js"></script>
    
    <link rel="preload" href="{{ asset('lang-').app()->getLocale() }}.js?v={{ date('Ymdhi') }}" as="script">
    <link rel="preload"
          href="{{ asset('assets/wm356/js/minified_safe_optimized_no_jquery_bundle.js?v='. filemtime(public_path('assets/wm356/js/minified_safe_optimized_no_jquery_bundle.js')) ) }}"
          as="script">
    <script src="{{ asset('assets/wm356/js/js_wallet.js?v='.filemtime(public_path('assets/wm356/js/js_wallet.js'))) }}"
            defer></script>
    <script src="{{ asset('lang-').app()->getLocale() }}.js?v={{ date('Ymdhi') }}" defer></script>
    <script src="{{ asset('assets/wm356/js/minified_safe_optimized_no_jquery_bundle.js?v='.filemtime(public_path('assets/wm356/js/minified_safe_optimized_no_jquery_bundle.js')) ) }}"
            defer></script>
    
    @laravelPWA
</head>

<body>

@include('wallet::layouts.header_wallet')

@yield('content')

@include('wallet::layouts.modal')

@include('wallet::layouts.footer')

<script></script>


<div class="myAlert-top alertcopy">
    <i class="fa-regular fa-circle-check"></i>
    <br>
    <strong>
        {{ __('app.home.copy') }} </strong>
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


@if (isset($notice_new[Route::currentRouteName()]['messages']) && !empty($notice_new[Route::currentRouteName()]['messages']))
    <div class="modal fade announcement-modal" id="announcementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="swiper mySwiper">
                        <div class="swiper-wrapper">
                            @foreach ($notice_new[Route::currentRouteName()]['messages'] as $item)
                                <div class="swiper-slide">
                                    <div class="p-3 fs-5 text-center">{!! $item !!}</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- สั่งเปิด modal และ init swiper เมื่อโหลดหน้า --}}
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            $(document.getElementById('announcementModal')).modal('show');
            var swiper = new Swiper(".mySwiper", {
                loop: true,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false
                },
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true
                }
            });
            
            // swiper.on('slideChange', function () {
            //     $('#announcementModal').modal('handleUpdate');
            // });
        });
    </script>
@endif

<script type="text/javascript">
    const private_channel = '{{ env('APP_NAME') }}_members.{{ auth()->guard('customer')->user()->code }}';
    
    Echo.private(private_channel)
            .notification((notification) => {
                Toast.fire({
                    icon: 'success',
                    title: notification.message
                });
                $('.-btn-balance-normal').trigger('click');
            });


</script>


@stack('scripts')

<script>
    (function () {
        Dropzone.autoDiscover = false;
        const $dropzoneElement = $('#uploadQr'); // หรือเปลี่ยนตาม ID/CLASS ที่คุณใช้
        
        if ($dropzoneElement.length === 0) return;
        
        const dz = new Dropzone($dropzoneElement[0], {
            url: "{{ route('customer.qr.upload') }}",
            method: 'post',
            maxFiles: 1,
            acceptedFiles: 'image/*',
            addRemoveLinks: true,
            autoProcessQueue: true,
            init: function () {
                this.on('sending', function (file, xhr, formData) {
                    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                });
                
                this.on('success', function (file, response) {
                    this.removeFile(file);
                    
                    if (response.success) {
                        $("#uploadQr").hide();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'ผลการตรวจสอบ',
                            text: 'อัพโหลดสำเร็จ',
                            timer: 2500,
                            showConfirmButton: false
                        });
                        
                        var imgTag = $('<img>', {
                            src: response.img_url,
                            alt: "Uploaded QR",
                            class: "img-fluid",
                            style: "max-width: 200px; display: block; margin: 0 auto;"
                        });
                        
                        // แทรกรูปเข้าไปใน container ของ form
                        $("#uploadQr").parent().append(imgTag);
                        
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'ผลการตรวจสอบ',
                            text: response.message,
                            timer: 2500,
                            showConfirmButton: false
                        });
                    }
                });
                
                this.on('error', function (file, errorMessage) {
                    this.removeFile(file);
                    Swal.fire({
                        icon: 'info',
                        title: 'ผลการตรวจสอบ',
                        text: 'ผิดพลาด',
                        timer: 2500,
                        showConfirmButton: false
                    });
                });
            }
        });
    })();
</script>
<script>
    
    document.addEventListener('DOMContentLoaded', () => {
        // สมมติว่า 'e' คือ context DOM หรือ document ทั้งหมด (ถ้าไม่มีก็ใช้ document)
        setTimeout(() => {
            
            $("[data-animatable]").each(function () {
                var $el = $(this);
                
                // ตั้ง delay เล็กน้อยก่อนสร้าง waypoint (เพื่อให้ DOM พร้อม)
                setTimeout(function () {
                    new Waypoint({
                        element: $el[0], // element DOM จริง
                        handler: function () {
                            // เมื่อ scroll เข้ามาใน viewport
                            setTimeout(function () {
                                // เรียก animateCss ด้วยชื่อ animation จาก data-animatable หรือ default เป็น fadeInUp
                                $el.animateCss($el.data("animatable") || "fadeInUp");
                            }, $el.data("delay") || 50);
                            
                            // ป้องกัน waypoint รันซ้ำหลายครั้ง (destroy หลังทำงาน)
                            this.destroy();
                        },
                        offset: $el.data("offset") || "100%" // ตำแหน่ง trigger
                    });
                }, 100);
            });
            
        }, 1000);
    });

</script>
</body>
</html>