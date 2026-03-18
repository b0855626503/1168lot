{{-- Reward inject entrypoint: will be pushed into @stack('wallet-ext-components') --}}
@include('reward::components.reward')


@push('components')

    {{-- 1) เพิ่ม tab เข้า event modal (ถ้าคุณทำ hook window.__EVENT_TABS__ ไว้แล้ว) --}}
    <script>
        window.__EVENT_TABS__ = window.__EVENT_TABS__ || [];
        window.__EVENT_TABS__.push({
            id: 'reward',
            method: 'REWARD',
            type: 'button',
            titleKey: 'พ้อยแลกรางวัล',     // ต้องมีใน translations_home หรือคุณจะใส่ข้อความตรง ๆ ก็ได้
            icon: '/assets/kimberbet/images/icon/icon-rewardexchange.webp',
            targetModalId: 'rewardModal',
            emitEvent: 'open-reward'
        });
    </script>

    {{-- 2) include ตัว modal reward ที่อยู่ใน package Reward --}}

    {{-- สมมติไฟล์นี้มี <script type="text/x-template" id="reward-modal-template"> ... --}}
    {{-- และมี Vue.component('reward-modal', ...) ที่ bind กับ id="rewardModal" --}}

@endpush

@push('wallet-ext-components')
    <reward-modal ref="rewardModalComponent"></reward-modal>

@endpush
