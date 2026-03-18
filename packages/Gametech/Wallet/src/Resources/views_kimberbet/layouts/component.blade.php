@include('wallet::components.navbar')
{{--@include('wallet::components.mobile-app')--}}
@include('wallet::components.bonus')
@include('wallet::components.event')
@include('wallet::components.wheel')
@include('wallet::components.coupon')
@include('wallet::components.couponlist')
@include('wallet::components.deposit')
@include('wallet::components.withdraw')
@include('wallet::components.gametab')
@include('wallet::components.member-menu')
@include('wallet::components.member-history')
@include('wallet::components.recent-games')
@include('wallet::components.member-credit')
@include('wallet::components.change-password')
@include('wallet::components.page-slide')
@include('wallet::components.promotion-page')
@include('wallet::components.contributor')
@include('wallet::components.game-credential')

@includeIf('reward::inject')
{{-- เดิมเป็น @push('script') -> แนะนำให้โหลดหลัง app.js ชัดเจน --}}
@push('components')
    <script>
        // === helpers ที่ไม่ต้องอาศัย Vue ขณะประกาศ ===
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        }

        function getUTCISOStringFromThailandTime() {
            const bangkokDate = new Date().toLocaleString("en-US", { timeZone: "Asia/Bangkok" });
            return new Date(bangkokDate).toISOString();
        }

        // === ตัวรอ Vue root ให้พร้อมก่อนใช้งาน (__APP_VM__ หรือ __vue__) ===
        function waitForVueRoot(selector = '#app', timeoutMs = 5000) {
            return new Promise((resolve, reject) => {
                const start = Date.now();
                (function loop() {
                    const el = document.querySelector(selector);
                    const vm = (window.__APP_VM__) || (el && el.__vue__) || null;
                    if (vm) return resolve(vm);
                    if (Date.now() - start > timeoutMs) return reject(new Error('Vue root not ready'));
                    requestAnimationFrame(loop);
                })();
            });
        }

        // === wrappers เรียกฟังก์ชันบน $refs อย่างปลอดภัย ===
        let _creditInflight = false;
        let _creditLastRun = 0;

        async function reLoadCredit() {
            // กันยิงถี่ ๆ ภายใน 500ms (debounce เบา ๆ)
            const now = Date.now();
            if (now - _creditLastRun < 500) return;
            _creditLastRun = now;

            if (_creditInflight) return; // กันชนซ้ำระหว่างโหลด
            _creditInflight = true;

            try {
                const vm = await waitForVueRoot('#app');
                const mc = vm?.$refs?.memberComponent;
                if (!mc?.loadCredit) {
                    console.warn('memberComponent.loadCredit not available');
                    return;
                }

                // เผื่อฝั่งเซิร์ฟเวอร์ตอบช้า: จำกัดเวลาเอง
                const res = await Promise.race([
                    mc.loadCredit(),                               // ต้อง return Promise ที่ resolve ด้วยข้อมูลใหม่
                    new Promise((_, rej) => setTimeout(() => rej(new Error('credit timeout')), 12000))
                ]);

                // ดึงค่าแบบปลอดภัย
                const profile   = mc?.profile   ?? res?.data?.profile   ?? null;
                const deposit   = mc?.deposit   ?? res?.data?.deposit   ?? null;
                const promotion = mc?.promotion ?? res?.data?.promotion ?? null;

                // ยิง event แบบ unified payload (เวอร์ชันล่าสุด)
                vm.$root.$emit?.('credit:update', { profile, deposit, promotion });
                // (ออปชัน) ส่ง DOM event เพื่อให้สคริปต์นอก Vue รับได้ด้วย
                window.dispatchEvent(new CustomEvent('credit:update', { detail: { profile, deposit, promotion } }));

            } catch (e) {
                console.warn('reLoadCredit() skipped:', e.message);
            } finally {
                _creditInflight = false;
            }
        }
        window.reLoadCredit = reLoadCredit;



        async function getBonus() {
            try {
                const vm = await waitForVueRoot('#app');
                if (vm?.$refs?.bonusModalComponent?.getBonus) {
                    vm.$refs.bonusModalComponent.getBonus('IC');
                } else {
                    console.warn('bonusModalComponent.getBonus not available');
                }
            } catch (e) {
                console.warn('getBonus() skipped:', e.message);
            }
        }
    </script>
@endpush

@prepend('components')
    {{-- translations inject (คงของเดิม) --}}
    <script type="module">
        window.translations = @json(__('app.game'));
        window.translations_home = @json(__('app.home'));
    </script>
@endprepend

@prepend('scripts')
    <script>
        // ตัวช่วยรอ Vue root (ใช้ซ้ำกับจุดอื่นได้)
        function waitForVueRoot(selector = '#app', timeoutMs = 5000) {
            return new Promise((resolve, reject) => {
                const start = Date.now();
                (function loop() {
                    const el = document.querySelector(selector);
                    const vm = (window.__APP_VM__) || (el && el.__vue__) || null;
                    if (vm) return resolve(vm);
                    if (Date.now() - start > timeoutMs) return reject(new Error('Vue root not ready'));
                    requestAnimationFrame(loop);
                })();
            });
        }

        document.addEventListener("DOMContentLoaded", async function () {
            // แก้ปัญหา focus ติดค้างตอนปิด modal
            document.addEventListener('hide.bs.modal', function () {
                if (document.activeElement) document.activeElement.blur();
            });

            // เลือกแท็บเกมที่เคยบันทึกไว้ (รองรับ timing)
            const key = localStorage.getItem('selectTabKey');
            if (key) {
                localStorage.removeItem('selectTabKey');
                try {
                    const vm = await waitForVueRoot('#app');
                    if (vm?.$refs?.gameTabComponent?.selectTab) {
                        vm.$refs.gameTabComponent.selectTab(key);
                        const el = document.querySelector('#gametab');
                        if (el) {
                            setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 500);
                        }
                    }
                } catch (e) {
                    console.warn('selectTab skipped:', e.message);
                }
            }

            // เมื่อเปลี่ยนภาษาแบบสด → บังคับ re-render ให้นิ่ง
            window.addEventListener('app:locale:changed', async function(){
                try {
                    const vm = await waitForVueRoot('#app');
                    vm.$forceUpdate();
                } catch (e) {
                    /* เงียบ ๆ ไป ไม่มีผลข้างเคียง */
                }
            });
        });
    </script>
@endprepend
