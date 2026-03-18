@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')

    <div class="sub-page min-h-100">
        <div class="container custom-max-width-container withdraw-container">
            <div id="withdrawPanel" class="swiper-container mt-3 swiper-container-initialized swiper-container-horizontal swiper-container-pointer-events">
                <div id="withdrawPagination" class="btn-group btn-group-lg rounded swiper-pagination-clickable swiper-pagination-bullets">
                    <span class="btn btn-line-secondary withdraw-selection-tab swiper-pagination-bullet-active" style="width: 1.2em;" tabindex="0"> ถอนเงิน</span>
                    <span class="btn btn-line-secondary withdraw-selection-tab" style="width: 1.2em;" tabindex="0">ถอนยอดเสีย</span>
                </div>
                <div class="swiper-wrapper" id="swiper-wrapper-679b486d5e38bb12" aria-live="polite">
                    <div class="swiper-slide p-2 rounded bg-dark-2 swiper-slide-active" role="group" aria-label="1 / 2" style="width: 696px; margin-right: 20px;">
                        <div class="fs-6 text-content pt-2 w-100 text-center pt-4">จำนวนเงินที่ถอนได้ปัจจุบันคือ : <span class="fw-bolder text-custom-primary">0.00</span> บาท </div>
                        <hr class="w-75 mx-auto my-1">
                        <div class="fs-6 text-content w-100 text-center">จำนวนสิทธ์การถอนคงเหลือ <span class="fw-bolder text-primary">5/5</span> ครั้ง (รีเซ็ตเวลา 00.00 ทุกวัน) </div>
                        <div class="theme-form mt-4">
                            <div class="input-group input-group-lg mx-auto custom-style-input" style="max-width: 20em;">
                                <span class="input-group-text">฿</span>
                                <input id="reg-tel" type="number" autocomplete="off" placeholder="จำนวนเงินถอน" min="10" max="99999" step="1" required="required" class="form-control">
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-primary btn-custom-primary w-100 rounded-pill" style="max-width: 20em;">ถอนเงิน</button>
                        </div>
                    </div>
                    <div class="swiper-slide p-2 rounded bg-dark-2 swiper-slide-next" role="group" aria-label="2 / 2" style="width: 696px; margin-right: 20px;">
                        <div class="fs-6 text-content pt-2 pb-2 w-100 text-center pt-4 position-relative"> เงินโบนัสยอดเสีย : <span class="fw-bolder text-custom-primary px-2">0</span> บาท
                            <hr class="w-75 mx-auto my-1">
                            <small class="text-mute w-100">ขั้นต่ำในการถอนโบนัสยอดเสีย 1 บาท</small>
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" disabled="disabled" class="btn btn-custom-primary w-100 rounded-pill" style="max-width: 20em;">ถอนยอดเสีย</button>
                        </div>
                        <div class="withdraw-betloss mt-2">
                            <div class="small text-danger">* ท่านจะสามารถถอนคืนยอดเสียได้ เมื่อเครดิตเหลือต่ำกว่า: 100 บาท</div>
                            <div class="small text-danger">* หากท่านทำรายการ "ถอน" โบนัสยอดเสียจะกลายเป็น 0</div>
                        </div>
                        <div class="withdraw-betloss-detail w-100 mt-2">
                            <div class="withdraw-betloss-detail_title mb-2 fs-6 text-content pt-2"></div>
                            <div class="withdraw-betloss-detail_content px-4 text-mute pb-2"></div>
                        </div>
                        <div style="opacity: 0;">{ "is_pending": null, "available": 0, "withdraw_min": 1, "credit_limit": 100, "mm_user": "77vz10107339", "latest_cnt": 0, "in_range": true, "withdraw_range": "EVERY", "cnt_limit": 1 }</div>
                    </div>
                </div>
                <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>
            </div>
            <div class="small text-danger mt-3 fw-light">* หากทำการถอนไม่ได้หรือติดปัญหาใดๆกรุณาติดต่อพนักงาน</div>
        </div>
    </div>

@endsection




