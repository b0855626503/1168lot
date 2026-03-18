@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')

{{--    <div class="sub-page sub-footer" style="min-height: 100vh;">--}}
{{--        <div class="container pt-3 px-0 history-container" style="max-width: 720px;">--}}
{{--            <div class="card bg-transparent">--}}
{{--                <div class="card-body container">--}}

{{--                    <div id="historySlide" class="swiper-container swiper-container-initialized swiper-container-horizontal swiper-container-pointer-events">--}}
{{--                        <div id="history-side" class="btn-group btn-group-lg mb-2 rounded swiper-pagination-clickable swiper-pagination-bullets">--}}
{{--                            <span class="btn btn-line-secondary text-secondary swiper-pagination-bullet-active" style="width: 1.2em;" tabindex="0"> {{ __('app.home.deposit') }}</span>--}}
{{--                            <span class="btn btn-line-secondary text-secondary" style="width: 1.2em;" tabindex="0">{{ __('app.home.withdraw') }}</span>--}}
{{--                        </div>--}}
{{--                        <div class="swiper-wrapper" id="swiper-wrapper-2196101416e10d79b1" aria-live="polite" style="transition-duration: 0ms; transform: translate3d(0px, 0px, 0px);">--}}
{{--                            <div class="swiper-slide p-2 rounded swiper-slide-active" role="group" aria-label="1 / 2" style="width: 686px; margin-right: 20px;">--}}
{{--                                <h3 class="text-success fs-5">{{ __('app.home.last_deposit') }}</h3>--}}
{{--                                <div class="card bg-dark" style="min-height: 25em;">--}}
{{--                                    <div class="card-body text-center">--}}
{{--                                        <em>{{ __('app.home.no_list') }}</em>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                                <zpagenav page="1" page-size="5" max-link="5" page-handler="function () { [native code] }" class="mt-auto" total="0">--}}
{{--                                    <zpagenav></zpagenav>--}}
{{--                                </zpagenav>--}}
{{--                            </div>--}}
{{--                            <div class="swiper-slide p-2 rounded swiper-slide-next" role="group" aria-label="2 / 2" style="width: 686px; margin-right: 20px;">--}}
{{--                                <h3 class="text-danger">{{ __('app.home.last_withdraw') }}</h3>--}}
{{--                                <div class="card bg-dark" style="min-height: 25em;">--}}
{{--                                    <div class="card-body text-center">--}}
{{--                                        <em>{{ __('app.home.no_list') }}</em>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                                <zpagenav page="1" page-size="5" max-link="5" page-handler="function () { [native code] }" class="mt-auto" total="0">--}}
{{--                                    <zpagenav></zpagenav>--}}
{{--                                </zpagenav>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>--}}
{{--                    </div>--}}
{{--                    <em class="small fw-light text-muted">{{ __('app.home.limit_history') }}</em>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}

    <member-history></member-history>

@endsection




