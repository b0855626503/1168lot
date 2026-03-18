@extends('wallet::layouts.master')


@section('title','')



@section('content')
    <div id="main__content" data-bgset="/assets/wm356/images/index-bg.jpg?v=2"
         class="lazyload x-bg-position-center x-bg-index lazyload text-center">
        <div class="x-index-content-main-container -logged" id="paymentsection">
            <div class="x-title-with-tag-header" data-animatable="fadeInUp" data-delay="150">
                <div class="container">
                    <h1 class="-title">โปรดสแกนเพื่อชำระเงินก่อนหมดเวลา</h1>
                </div>
            </div>
            <p id="clock"></p>
            <div style="max-width:500px;position:relative;margin: 0 auto;padding: 10px">
                <img class="img-fluid" src="data:image/png;base64, {{ $url }}" alt="Red dot"/>
            </div>
        </div>
    </div>

@endsection

@push('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.countdown/2.2.0/jquery.countdown.min.js"></script>
    <script>

        var fiveSeconds = new Date().getTime() + (15 * 60000);
        $('#clock').countdown(fiveSeconds, {elapse: true})
            .on('update.countdown', function (event) {
                var $this = $(this);
                if (event.elapsed) {
                    close();
                } else {
                    $this.html(event.strftime('เหลือเวลาอีก <span>%H:%M:%S</span> นาที'));
                }
            });
    </script>
@endpush
