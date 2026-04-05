<script>
    (function () {
        if (typeof window.__adminLoadCntTriggered === 'undefined') {
            window.__adminLoadCntTriggered = false;
        }

        const applyBadgeValue = function (key, value) {
            const numericValue = Number(value || 0);

            if (Number.isFinite(numericValue) && numericValue > 0) {
                if (typeof window.updateBadge === 'function') {
                    window.updateBadge(key, numericValue);
                    return;
                }
            }

            if (typeof window.update === 'function') {
                window.update(key, Number.isFinite(numericValue) ? numericValue : 0);
                return;
            }

            const el = document.getElementById('badge_' + key);
            if (el) {
                el.textContent = Number.isFinite(numericValue) ? numericValue : 0;
            }
        };

        const runLoadCnt = async function () {
            if (window.__adminLoadCntTriggered || typeof window.axios === 'undefined') {
                return;
            }

            window.__adminLoadCntTriggered = true;

            try {
                const response = await window.axios.get("{{ route('admin.home.loadcnt') }}");
                const res = response && response.data ? response.data : {};

                applyBadgeValue('bank_in', res.bank_in_today);
                applyBadgeValue('bank_in_old', res.bank_in);
                applyBadgeValue('withdraw', res.withdraw);
                applyBadgeValue('withdraw_free', res.withdraw_free);
                applyBadgeValue('lotto_tickets', res.lotto_tickets);

                const confirmWalletEl = document.getElementById('badge_confirm_wallet');
                if (confirmWalletEl) {
                    confirmWalletEl.textContent = res.payment_waiting || 0;
                }

                const memberConfirmEl = document.getElementById('badge_member_confirm');
                if (memberConfirmEl) {
                    memberConfirmEl.textContent = res.member_confirm || 0;
                }
            } catch (_error) {
                window.__adminLoadCntTriggered = false;
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runLoadCnt, { once: true });
        } else {
            runLoadCnt();
        }
    })();
</script>
