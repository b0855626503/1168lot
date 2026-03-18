@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('content')

    <div class="p-1">
        <div class="headsecion">
            <img src="/images/icon/return.png"> {{ __('app.home.freecredit') }}
        </div>
        <div class="ctpersonal trans main-menu">
            <div class="card card-trans">
                <div class="card-body py-1">
                    <div class="row">

                        <div class="col-6 main-menu-item px-0">
                            <a href="javascript:void(0)" onclick="openPopup('BONUS','โบนัส')">
                                <i class="fas fa-hands-helping fa-2x"></i><br>
                                <span class="text-main">{{ __('app.home.bonus') }}</span><br>
                                <span class="text-main"> {{ $profile->bonus }}</span>
                            </a>
                        </div>
                        <div class="col-6 main-menu-item px-0">
                            <a href="javascript:void(0)" onclick="openPopup('FASTSTART','ค่าแนะนำ')"><i
                                    class="fas fa-hands-helping fa-2x"></i><br>
                                <span class="text-main">{{ __('app.home.suggest') }}</span><br>
                                <span class="text-main"> {{ $profile->faststart }}</span>
                            </a>
                        </div>
                        <div class="col-6 main-menu-item px-0">
                            <a href="javascript:void(0)" onclick="openPopup('CASHBACK','Cashback')"><i
                                    class="fa fa-money-bill fa-2x"></i><br>
                                <span class="text-main">{{ __('app.home.cashback') }}</span><br>
                                <span class="text-main"> {{ $profile->cashback }}</span>
                            </a>
                        </div>
                        <div class="col-6 main-menu-item px-0">
                            <a href="javascript:void(0)" onclick="openPopup('IC','IC')"><i
                                    class="fa fa-user fa-2x"></i><br>
                                <span class="text-main">IC</span><br>
                                <span class=" text-main"> {{ $profile->ic }}</span>
                            </a>
                        </div>

                    </div>
                </div>
            </div>
            <div class="card card-trans">
                <div class="card-body py-1">
                    <div class="row">
                        {{--                <div class="col-sm-12 col-3 main-menu-item px-0">--}}
                        {{--                    <a href="javascript:void(0)"></a>--}}
                        {{--                </div>--}}
                        <div class="col-6 main-menu-item px-0">
                            <a href="{{ route('customer.credit.withdraw.index') }}"><i
                                    class="fas fa-hand-holding-usd fa-2x"></i><br>
                                <span class="text-main"> {{ __('app.home.withdraw') }}</span>
                            </a>
                        </div>
                        <div class="col-6 main-menu-item px-0">
                            <a href="{{ route('customer.credit.history.index') }}"><i
                                    class="fal fa-history fa-2x"></i><br>
                                <span class=" text-main"> {{ __('app.home.history') }}</span>
                            </a>
                        </div>
                        {{--                <div class="col-sm-12 col-3 main-menu-item px-0">--}}
                        {{--                    <a href="javascript:void(0)"></a>--}}
                        {{--                </div>--}}
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        function openPopup(id, msg) {
            Swal.fire({
                title: 'ยืนยันการโยก ' + msg + ' เข้ากระเป๋าฟรี ใช่หรือไม่',
                html: "กด ยืนยัน เพื่อโยกเข้ากระเป๋าฟรี",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.post(`{{ route('customer.transfer.bonus.confirm') }}`, {
                        id: id
                    }).then(response => {
                        if (response.data.success) {
                            Swal.fire(
                                'ดำเนินการสำเร็จ',
                                response.data.message,
                                'success'
                            );
                            setTimeout(() => {
                                window.location.href = window.location;
                            }, 2000);
                        } else {
                            Swal.fire(
                                'พบข้อผิดพลาด',
                                response.data.message,
                                'error'
                            );
                        }

                    }).catch(err => [err]);
                }
            })
        }
    </script>
@endpush

