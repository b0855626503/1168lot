@section('css')
    @include('admin::layouts.datatables_css')
@endsection

@push('styles')
    <style>
        /* ====== รายการห้องแชต (ด้านซ้าย) ====== */
        .list-group-item.gt-conv-active {
            background-color: #e7f1ff; /* ฟ้าอ่อนกว่า primary */
            border-color: #b6d4fe;
            color: #0c63e4;
        }

        .list-group-item.gt-conv-active .text-muted,
        .list-group-item.gt-conv-active small {
            color: #0c63e4 !important;
        }

        .list-group-item.gt-conv-active .badge {
            background-color: #0d6efd;
            color: #fff;
        }

        /* ====== bubble ฝั่งทีมงาน (outbound) ====== */
        .gt-msg-agent {
            background-color: #d1e7ff;
            color: #084298;
        }

        .gt-msg-agent .text-muted {
            color: #084298 !important;
        }

        #line-oa-chat-overlay {
            position: fixed;
            inset: 0;
            z-index: 9998;
        }

        .lineoa-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
        }

        .lineoa-popup {
            position: fixed;
            inset: 20px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            z-index: 9999;
            overflow: hidden;
        }

        .list-group-item.gt-conv-active .oa-reg-badge {
            background-color: #ffc107 !important; /* สี warning */
            color: #212529 !important; /* ดำ */
        }

        .chat-line-original {
            white-space: pre-wrap;
            font-size: 14px;
        }

        .chat-line-translated {
            white-space: pre-wrap;
            font-size: 13px;
            border-left: 3px solid #e0e0e0;
            padding-left: 4px;
        }
    </style>
@endpush



@push('scripts')
    @include('admin::layouts.datatables_js')
    {!! $dataTable->scripts() !!}
@endpush
