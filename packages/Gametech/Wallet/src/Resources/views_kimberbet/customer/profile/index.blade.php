@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')

    <div class="sub-page sub-footer profile">
        <div class="container profile-container">
            <div class="card mt-2 bg-transparent" style="width: 100%;">
                <div class="card-body p-2">
                    <div class="credit-box" style="max-width: 32em;">
                        <img src="/assets/kimberbet/images/icon/bg_card_profile.png" class="w-100">
                        <div class="profile-content container position-absolute top-0 bottom-0">
                            <div class="row w-100 g-0">
                                <div class="col-4">
                                    <span>{{ __('app.profile.profile') }}</span>
                                </div>
                                <div class="col-8 d-inline-flex">
                                    <div class="text-end pe-2" style="flex: 1 1 0%;">
                                        <span class="d-block lh-1">{{ __('app.profile.bank') }}</span>
                                        <span class="d-block  lh-1 fs-bold">{{ $profile->bank->name_th }}</span>
                                    </div>
                                    <div>
                                        <img src="{{ Storage::url('bank_img/' . $profile->bank->filepic) }}"
                                             style="width: 2.7em; object-fit: contain;">
                                    </div>
                                </div>
                            </div>
                            <div class="profile-center-content row w-100 g-0 d-flex flex-column align-items-center justify-content-center">
                                <div class="bank-number fw-bold w-fit-content lh-1 position-relative">{{ $profile->acc_no }}</div>
                                <div class="bank-name w-fit-content lh-1 position-relative">{{ $profile->name }}</div>
                            </div>
                            <div class="profile-content-footer d-flex flex-column w-100">
                                <div class="w-100 lh-2 fw-light member_primary_text_color">{{ __('app.profile.username') }}
                                    : {{ $profile->user_name}}</div>
                                <div class="w-100 lh-1 fw-light member_primary_text_color">{{ __('app.profile.register_date') }}
                                    : {{ $profile->date_regis->format('d/m/Y')}}</div>
                            </div>
                        </div>
                    </div>

                    <change-password-form></change-password-form>

                </div>
            </div>
        </div>
    </div>

@endsection




