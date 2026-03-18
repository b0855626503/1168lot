@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')

    <div class="p-1">
        <div class="headsecion">
            <img src="/images/icon/deposit.png"> {{ __('app.topup.refill') }}
        </div>
        <div class="ctpersonal">

            <div class="smallcontain">
                <div class="row m-0 mt-4">
                    <div class="col-2 p-0 leftdps">
                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <a class="nav-link active" id="v-pills-home-tab" data-toggle="pill" href="#v-pills-home" role="tab" aria-controls="v-pills-home" aria-selected="true"><img class="banktabicon" src="/images/icon/04.png?v=2"> ธนาคาร</a>
                            <a class="nav-link" id="v-pills-profile-tab" data-toggle="pill" href="#v-pills-profile" role="tab" aria-controls="v-pills-profile" aria-selected="false"><img class="banktabicon" src="/images/bank/truewallet.svg?v=1"> TrueWallet</a>
                        </div>
                    </div>
                    <div class="col-10 p-0">
                        <div class="tab-content" id="v-pills-tabContent">
                            <div class="tab-pane fade active show" id="v-pills-home" role="tabpanel" aria-labelledby="v-pills-home-tab">
                                <div class="griddps">
                                    @foreach($banks as $bank)
                                        @if($bank['shortcode'] == 'TW')
                                            @continue
                                        @endif

                                    @foreach($bank['banks_account'] as $item)
                                    <div class="ingriddps">
                                        <div class="iningriddps copybtn">
                                            <img src="{{ $bank['filepic'] }}">
                                            <div>
                                                ธนาคาร{{ $bank['name_th'] }} <br>
                                                <span>{{ $item['acc_no'] }}</span> <br>
                                                {{ $item['acc_name'] }} <br>
                                                <button onclick="copylink()"><i class="fad fa-copy"></i> คัดลอก</button>
                                            </div>
                                        </div>
                                    </div>
                                            @endforeach
                                    @endforeach

                                </div>
                            </div>
                            <div class="tab-pane fade" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab">
                                <div class="griddps">

                                    @foreach($banks as $bank)
                                        @if($bank['shortcode'] != 'TW')
                                            @continue
                                        @endif

                                        @foreach($bank['banks_account'] as $item)
                                            <div class="ingriddps">
                                                <div class="iningriddps copybtn">
                                                    <img src="{{ $bank['filepic'] }}">
                                                    <div>
                                                        {{ $bank['name_th'] }} <br>
                                                        <span>{{ $item['acc_no'] }}</span> <br>
                                                        {{ $item['acc_name'] }} <br>
                                                        <button onclick="copylink()"><i class="fad fa-copy"></i> คัดลอก</button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endforeach

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modalspanbox mt-3">
                    <span>{{ __('app.topup.remark') }}</span>
                </div>
            </div>



        </div>

    </div>

@endsection





