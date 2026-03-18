{{--@include('wallet::components.navbar')--}}
{{--@include('wallet::components.mobile-app')--}}
{{--@include('wallet::components.bonus')--}}
{{--@include('wallet::components.event')--}}
{{--@include('wallet::components.deposit')--}}
{{--@include('wallet::components.withdraw')--}}
@include('wallet::components.game-block')
{{--@include('wallet::components.member-menu')--}}
{{--@include('wallet::components.member-history')--}}
{{--@include('wallet::components.recent-games')--}}
{{--@include('wallet::components.member-credit')--}}
{{--@include('wallet::components.change-password')--}}
@include('wallet::components.page-slide')

@push('script')
	<script>
        function reLoadCredit() {
            const vueRoot = document.querySelector('#app'); // หรือ id ที่ครอบ Vue
            const vueInstance = vueRoot.__vue__; // Vue 2 เท่านั้น

            if (vueInstance && vueInstance.$refs && vueInstance.$refs.memberComponent) {
                vueInstance.$refs.memberComponent.loadCredit();

            } else {

            }
        }

        function getBonus() {
            const vueRoot = document.querySelector('#app'); // หรือ id ที่ครอบ Vue
            const vueInstance = vueRoot.__vue__; // Vue 2 เท่านั้น

            if (vueInstance && vueInstance.$refs && vueInstance.$refs.bonusModalComponent) {
                vueInstance.$refs.bonusModalComponent.getBonus('IC');

            } else {
                console.warn('Component not found');
            }
        }

        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) {
                return parts.pop().split(';').shift();
            }
        }

        function getUTCISOStringFromThailandTime() {
            const bangkokDate = new Date().toLocaleString("en-US", {timeZone: "Asia/Bangkok"});
            const date = new Date(bangkokDate);
            return date.toISOString(); // จะได้แบบ "2025-10-05T07:48:00.000Z" (ตรงกับ 14:48 GMT+7)
        }
	
	</script>
@endpush

@prepend('components')
	{{-- components --}}
	<script type="module">
        window.translations = @json(__('app.game'));

        window.translations_home = @json(__('app.home'));
	
	</script>
@endprepend



@prepend('scripts')
	<script>
        
        // document.addEventListener("DOMContentLoaded", function () {
        //
        //     document.addEventListener('hide.bs.modal', function (event) {
        //         if (document.activeElement) {
        //             document.activeElement.blur();
        //         }
        //     });
        //
        //     const key = localStorage.getItem('selectTabKey');
        //     if (key) {
        //         localStorage.removeItem('selectTabKey');
        //
        //         const vueInstance = document.querySelector('#app').__vue__;
        //         if (vueInstance?.$refs?.gameTabComponent) {
        //             vueInstance.$refs.gameTabComponent.selectTab(key);
        //
        //             const el = document.querySelector('#gametab');
        //             if (el) {
        //                 setTimeout(() => {
        //                     el.scrollIntoView({behavior: 'smooth', block: 'start'});
        //                 }, 500); // ให้แน่ใจว่า render เสร็จก่อน scroll
        //             }
        //         }
        //     }
        // });
	</script>
@endprepend

