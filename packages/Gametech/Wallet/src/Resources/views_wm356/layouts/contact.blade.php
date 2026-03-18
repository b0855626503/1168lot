@if(isset($contacts))

<div class="floating-contact">
    @foreach($contacts as $contact)
    <a href="{{ $contact->link }}" target="_blank" class="contact-btn {{ $contact->type }}">
        <div class="icon"><i class="fa-brands fa-{{ $contact->type }}"></i></div>
        <div class="label">{{ $contact->label }}</div>
    </a>
    @endforeach
</div>

<style>
    .floating-contact {
        position: fixed;
        top: 50%;
        right: 0;
        transform: translateY(-50%);
        display: flex;
        flex-direction: column;
        gap: 10px;
        z-index: 9999;
        pointer-events: none;
        align-items: flex-end;
    }

    .contact-btn {
        pointer-events: auto;
        display: flex;
        align-items: center;
        width: 48px;
        height: 48px;
        border-radius: 24px 0 0 24px;
        color: #fff;
        text-decoration: none;
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        background-color: #888;
        overflow: hidden;
    }

    .contact-btn:hover,
    .contact-btn:focus-within {
        width: 160px;
        text-decoration: none !important;

    }

    .contact-btn:hover .label,
    .contact-btn:focus-within .label {
        opacity: 1;
    }

    .contact-btn .icon {
        min-width: 48px;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .contact-btn .label {
        opacity: 0;
        white-space: nowrap;
        padding-left: 12px;
        font-size: 16px;
        height: 48px;
        display: flex;
        align-items: center;
        transition: opacity 0.2s;
        text-decoration: none !important;
    }

    /* ป้องกันขีดเส้นใต้แม้ตอน hover */
    .contact-btn:hover .label,
    .contact-btn:focus-within .label {
        text-decoration: none !important;
    }

    /* สีพื้นหลัง */
    .contact-btn.line     { background-color: #00c300 !important; }
    .contact-btn.telegram { background-color: #0088cc !important; }
    .contact-btn.email    { background-color: #d44638 !important; }
    @media (max-width: 767.98px) {
        .contact-btn,
        .contact-btn:hover,
        .contact-btn:focus-within {
            width: 48px !important;
        }
        .contact-btn .label,
        .contact-btn:hover .label,
        .contact-btn:focus-within .label {
            opacity: 0 !important;
        }
    }

</style>
@endif
@auth('customer')
<div class="notify-popup-wrapper">
    <div class="notify-popup-container">
        <div class="notify-popup-header d-flex">
            <button type="button" class="fw-bolder close-notify">&times;</button>
        </div>
        <div class="notify-popup-body d-flex align-items-center p-3">
            <div class="ic-container  d-flex justify-content-center">
                <img src="{{ url(core()->imgurl($webconfig->logo,'img')) }}" alt="">
            </div>
            <div class="content-detail  d-flex flex-column">
                <div class="txt">
                    <h4>รับการแจ้งเตือนโปรโมชั่นพิเศษ!</h4>
                    <p>ท่านจะได้รับข่าวสารและ โปรโมชั่นพิเศษ หรือ ของรางวัลอื่นๆ</p>
                </div>
                <div class="group-button d-flex w-100">
                    <button type="button" class="btn btn-allow allow-notify">รับข่าวสาร</button>
                    <button type="button" class="btn btn-not-allow not-allow-notify">ไม่รับข่าวสาร</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endauth