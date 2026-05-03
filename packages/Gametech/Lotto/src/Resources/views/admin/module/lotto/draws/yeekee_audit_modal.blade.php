@if(bouncer()->hasPermission('lotto.yeekee.audit.view'))
<div id="yeekeeAuditModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title mb-0">ตรวจสอบยี่กี่ (Yeekee Audit)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-2" id="yeekeeAuditModalBody">
                <div id="yeekeeAuditRounds"></div>
                <div id="yeekeeAuditDetail" class="mt-3" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var _loadRoundsUrl = @json(route('admin.lotto.yeekee.audit.rounds'));
    var _showUrlTemplate = @json(route('admin.lotto.yeekee.audit.show', ['roundId' => '__ROUND_ID__']));

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function httpErrorMessage(status, body) {
        if (status === 403) { return 'ไม่มีสิทธิ์เข้าถึง (403)'; }
        if (status === 404) { return 'ไม่พบข้อมูล (404)'; }
        if (status === 401) { return 'กรุณาเข้าสู่ระบบใหม่ (401)'; }
        if (body && body.message) { return String(body.message); }
        return 'เกิดข้อผิดพลาด' + (status ? ' (HTTP ' + status + ')' : '');
    }

    function requestJson(url) {
        var headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
        if (window.axios) {
            return window.axios.get(url, { headers: headers, timeout: 15000 })
                .then(function (r) { return r.data; })
                .catch(function (err) {
                    var status = err && err.response ? err.response.status : 0;
                    var body = err && err.response ? (err.response.data || {}) : {};
                    throw new Error(httpErrorMessage(status, body));
                });
        }
        if (window.fetch) {
            return window.fetch(url, { headers: headers }).then(function (r) {
                var status = r.status;
                if (!r.ok) {
                    return r.json().catch(function () { return {}; }).then(function (body) {
                        throw new Error(httpErrorMessage(status, body));
                    });
                }
                return r.json().catch(function () {
                    throw new Error('Response ไม่ใช่ JSON ที่ถูกต้อง');
                });
            });
        }
        return Promise.reject(new Error('ไม่พบ HTTP client (axios/fetch)'));
    }

    function toRoundId(value) {
        var roundId = parseInt(String(value || ''), 10);
        return Number.isFinite(roundId) && roundId > 0 ? roundId : 0;
    }

    function setRoundsHtml(html) {
        var el = document.getElementById('yeekeeAuditRounds');
        if (el) { el.innerHTML = html; }
    }

    window.openYeekeeAuditModal = function (drawId) {
        var modalEl = document.getElementById('yeekeeAuditModal');
        if (!modalEl) {
            console.error('[YeekeeAudit] #yeekeeAuditModal not found — modal not rendered for this user');
            return;
        }

        if (typeof window.$ === 'undefined' || typeof window.$.fn === 'undefined' || typeof window.$.fn.modal === 'undefined') {
            console.error('[YeekeeAudit] jQuery หรือ Bootstrap modal ไม่พร้อมใช้งาน');
            alert('เกิดข้อผิดพลาด: ระบบ modal ไม่พร้อม กรุณา refresh หน้าเว็บ');
            return;
        }

        var detailDiv = document.getElementById('yeekeeAuditDetail');
        if (detailDiv) {
            detailDiv.style.display = 'none';
            detailDiv.innerHTML = '';
        }

        setRoundsHtml('<p class="text-muted p-2"><i class="fas fa-spinner fa-spin mr-1"></i>กำลังโหลดรอบ...</p>');
        window.$('#yeekeeAuditModal').modal('show');

        var url = _loadRoundsUrl + '?lotto_draw_id=' + encodeURIComponent(drawId);
        requestJson(url).then(function (data) {
            var rounds = Array.isArray(data.rounds) ? data.rounds : [];
            if (rounds.length === 0) {
                setRoundsHtml('<p class="text-muted p-2">ไม่พบรอบ Yeekee สำหรับงวดนี้</p>');
                return;
            }
            var html = '<table class="table table-sm table-bordered mb-0"><thead class="thead-light"><tr>'
                + '<th>รอบที่</th><th>สถานะ</th><th>ยิงแล้ว</th><th>ปิดยิงเมื่อ</th><th>Snapshot</th><th></th>'
                + '</tr></thead><tbody>';
            rounds.forEach(function (r) {
                var safeRoundId = toRoundId(r.id);
                if (safeRoundId <= 0) { return; }
                html += '<tr>'
                    + '<td>' + escapeHtml(r.round_no) + '</td>'
                    + '<td>' + escapeHtml(r.status) + '</td>'
                    + '<td>' + escapeHtml(r.shoot_count) + '</td>'
                    + '<td>' + escapeHtml(r.shoot_closed_at || '-') + '</td>'
                    + '<td>' + (r.has_snapshot ? '<span class="badge badge-success">มี</span>' : '<span class="badge badge-secondary">ไม่มี</span>') + '</td>'
                    + '<td><button class="btn btn-xs btn-info js-load-yeekee-audit-detail" data-round-id="' + safeRoundId + '" type="button">ดู Audit</button></td>'
                    + '</tr>';
            });
            html += '</tbody></table>';
            setRoundsHtml(html);
        }).catch(function (err) {
            var msg = (err && err.message) ? err.message : 'โหลดรายการรอบล้มเหลว';
            setRoundsHtml('<p class="text-danger p-2"><i class="fas fa-exclamation-circle mr-1"></i>' + escapeHtml(msg) + '</p>');
        });
    };

    function loadYeekeeAuditDetail(roundId) {
        var safeRoundId = toRoundId(roundId);
        if (safeRoundId <= 0) { return; }

        var detailDiv = document.getElementById('yeekeeAuditDetail');
        if (!detailDiv) { return; }

        var btn = document.querySelector('.js-load-yeekee-audit-detail[data-round-id="' + safeRoundId + '"]');
        if (btn) { btn.disabled = true; }

        detailDiv.style.display = 'block';
        detailDiv.innerHTML = '<p class="text-muted p-2"><i class="fas fa-spinner fa-spin mr-1"></i>กำลังโหลด Audit...</p>';

        var url = _showUrlTemplate.replace('__ROUND_ID__', String(safeRoundId));
        requestJson(url).then(function (data) {
            var round = data.round || {};
            var shoots = Array.isArray(data.shoots) ? data.shoots : [];
            var isSensitive = !data._sensitive_permission_required;
            var sensitiveNote = data._sensitive_permission_required
                ? '<div class="alert alert-warning py-1 mb-2 text-sm">ข้อมูล masked — ต้องใช้ permission <code>' + escapeHtml(data._sensitive_permission_required) + '</code> เพื่อดูข้อมูลจริง</div>'
                : '';

            var shootHeaders = isSensitive
                ? '<th>#</th><th>Position</th><th>เลข</th><th>Member ID</th><th>IP</th><th>User Agent</th><th>เวลา</th>'
                : '<th>#</th><th>Position</th><th>เลข (masked)</th><th>เวลา</th>';

            var shootRows = '';
            shoots.forEach(function (s, idx) {
                if (isSensitive) {
                    shootRows += '<tr><td>' + escapeHtml(idx + 1) + '</td><td>' + escapeHtml(s.position) + '</td>'
                        + '<td><strong>' + escapeHtml(s.number_text) + '</strong></td>'
                        + '<td>' + escapeHtml(s.member_id || '-') + '</td>'
                        + '<td>' + escapeHtml(s.ip_address || '-') + '</td>'
                        + '<td>' + escapeHtml(s.user_agent || '-') + '</td>'
                        + '<td>' + escapeHtml(s.submitted_at || '-') + '</td></tr>';
                } else {
                    shootRows += '<tr><td>' + escapeHtml(idx + 1) + '</td><td>' + escapeHtml(s.position) + '</td>'
                        + '<td><strong>' + escapeHtml(s.number_text) + '</strong></td>'
                        + '<td>' + escapeHtml(s.submitted_at || '-') + '</td></tr>';
                }
            });

            var snapshotHtml = '';
            if (data.has_snapshot && data.snapshot) {
                snapshotHtml = '<div class="mt-2"><strong>Snapshot Hash:</strong> <code class="text-break">' + escapeHtml(data.snapshot_hash || '') + '</code></div>'
                    + '<pre class="bg-light border rounded p-2 mt-1" style="max-height:300px;overflow:auto;font-size:11px;white-space:pre-wrap;">'
                    + escapeHtml(JSON.stringify(data.snapshot, null, 2)) + '</pre>';
            }

            var colspan = isSensitive ? '7' : '4';
            detailDiv.innerHTML = sensitiveNote
                + '<div class="card card-outline card-info">'
                + '<div class="card-header py-2"><h6 class="mb-0">รอบที่ ' + escapeHtml(round.round_no) + ' — ' + escapeHtml(round.market_name) + ' (วันที่ ' + escapeHtml(round.round_date) + ')</h6></div>'
                + '<div class="card-body p-2">'
                + '<p class="mb-1 text-muted">ยิง: ' + escapeHtml(round.shoot_count) + ' | last_position: ' + escapeHtml(round.last_shoot_position) + ' | hash: <code>' + escapeHtml(round.shoot_snapshot_hash || '-') + '</code></p>'
                + '<div class="table-responsive" style="max-height:350px;overflow-y:auto;">'
                + '<table class="table table-sm table-bordered mb-0"><thead class="thead-light"><tr>' + shootHeaders + '</tr></thead>'
                + '<tbody>' + (shootRows || '<tr><td colspan="' + colspan + '" class="text-center text-muted">ไม่มีข้อมูลการยิง</td></tr>') + '</tbody></table>'
                + '</div>'
                + snapshotHtml
                + '</div></div>';

            if (btn) { btn.disabled = false; }
        }).catch(function (err) {
            var msg = (err && err.message) ? err.message : 'โหลด Audit ล้มเหลว';
            detailDiv.innerHTML = '<p class="text-danger p-2"><i class="fas fa-exclamation-circle mr-1"></i>' + escapeHtml(msg) + '</p>';
            if (btn) { btn.disabled = false; }
        });
    }

    var roundsContainer = document.getElementById('yeekeeAuditRounds');
    if (roundsContainer) {
        roundsContainer.addEventListener('click', function (event) {
            var target = event.target;
            if (!(target instanceof Element)) { return; }
            var button = target.closest('.js-load-yeekee-audit-detail');
            if (!button) { return; }
            loadYeekeeAuditDetail(button.getAttribute('data-round-id'));
        });
    }
}());
</script>
@endpush
@endif
