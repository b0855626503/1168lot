@extends('wallet::layouts.master')


@section('title','')



@section('content')
    <div class="container text-light mt-5">
        <h3 class="text-center text-light">ฝากเงิน QR HengPay</h3>
        <p class="text-center text-color-fixed">Deposit by QR HengPay</p>
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">
                <div class="card card-trans text-center" id="paymentsection">
                    <div class="card-header">
                        <p class="text-center text-color-fixed m-0">โปรดสแกนเพื่อชำระเงินก่อนหมดเวลา</p>
                    </div>
                    <div class="card-body p-2">
                        <p id="clock"></p>
                        <div style="max-width:500px;position:relative;margin: 0 auto;padding: 10px">
                            <img class="img-fluid" src="data:image/png;base64, {{ $url }}" alt="Red dot"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
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
