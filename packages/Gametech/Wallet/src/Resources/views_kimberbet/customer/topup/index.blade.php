@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')

    <div class="sub-page min-h-100">
        <div class="container custom-max-width-container deposit-container">
            <div class="mt-3 text-center">
                <div class="header-block-content d-block rounded-top py-2">
                    <h5 class="text-center mb-0 text-dark lh-1">ฝากเงิน</h5>
                </div>
                <div class="bg-dark-2" style="min-height: 60vh;">
                    <div class="fs-6 text-content pt-2 px-2 lh-1">ลูกค้าต้องใช้บัญชีที่ทำการลงทะเบียนไว้เท่านั้นในการฝากเงิน</div>
                    <div class="group-bank_user-wrapper">
                        <img src="images/bank/scb.svg" class="user-bank-icon" style="width: 100%; max-width: 45px;">
                        <div class="group-bank_user">
                            <div class="fs-6 fw-lighter text-start">กสิกรไทย <i class="bi bi-check-circle-fill text-custom-success"></i>
                            </div>
                            <div class="fs-5 text-start lh-1">ทดสอบระบบ ทดสอบ</div>
                            <div class="fs-5 text-start text-custom-primary lh-1">1234567890</div>
                        </div>
                    </div>
                    <div class="group-bank_user-wrapper">
                        <span class="text-danger px-2 lh-1 py-1 text-center"></span>
                    </div>
                    <div class="card bg-dark bank-deposit-item">
                        <div class="card-body bank-item-container container">
                            <div class="bank-info d-flex">
                                <div class="bank-icon">
                                    <img src="images/bank/kbank.svg" style="width: 100%; max-width: 5em;">
                                </div>
                                <div class="bank-detail ps-4">
                                    <div class="fs-6 text-start fw-light member_primary_text_color">ไทยพาณิชย์</div>
                                    <div class="fs-6 text-start mt-auto pt-2 member_primary_text_color">วรรณธงไชย นามโคตร</div>
                                    <div class="text-warning fs-5 text-start lh-1">4311144915</div>
                                </div>
                                <div class="btn-copy-bank d-flex flex-column">
                                    <!-- COPY TEXT IN DIV -->
                                    <button class="btn_copy_bankcode py-1 shadow rounded-pill btn btn-outline-secondary btn-custom-secondary text-white fw-light d-flex" style="min-width: unset;" onclick="copyToClipboard('bankaccount')">
                                        <!---->
                                        <span class="w-100 flex-row-center-xy">
                                <i class="bi bi-clipboard-check text-light fw-light"></i> คัดลอก
                                            <!-- COPY THIS -->
                                <b>4311144915</b>
                                            <!-- COPY THIS -->
                                <input tabindex="-1" aria-hidden="true" class="ip-copyfrom modal-deposit">
                              </span>
                                    </button>
                                    <!-- COPY TEXT IN DIV -->
                                    <!---->
                                </div>
                            </div>
                        </div>
                    </div>
                    <!---->
                </div>
            </div>
            <div class="small text-danger mt-3 fw-light">* หากต้องการเปลี่ยนข้อมูลบัญชี สามารถแจ้งเปลี่ยนกับเจ้าหน้าที่ได้</div>
        </div>
    </div>

@endsection




