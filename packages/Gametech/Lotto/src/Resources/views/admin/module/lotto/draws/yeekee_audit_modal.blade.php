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

<script>
(function () {
    var _loadRoundsUrl = @json(route('admin.lotto.yeekee.audit.rounds'));
    var _showBaseUrl = @json(route('admin.lotto.yeekee.audit.show', ['roundId' => 0])).replace(/\/0\/audit$/, '');

    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function requestJson(url) {
        var headers = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
        if (window.axios) {
            return window.axios.get(url, { headers: headers, timeout: 15000 }).then(function (r) { return r.data; });
        }
        return window.fetch(url, { headers: headers }).then(function (r) {
            if (!r.ok) { throw new Error('HTTP ' + r.status); }
            return r.json();
        });
    }

    window.openYeekeeAuditModal = function (drawId) {
        var roundsDiv = document.getElementById('yeekeeAuditRounds');
        var detailDiv = document.getElementById('yeekeeAuditDetail');
        roundsDiv.innerHTML = '<p class="text-muted p-2">กำลังโหลดรอบ...</p>';
        detailDiv.style.display = 'none';
        detailDiv.innerHTML = '';
        $('#yeekeeAuditModal').modal('show');

        var url = _loadRoundsUrl + '?lotto_draw_id=' + drawId;
        requestJson(url).then(function (data) {
            var rounds = Array.isArray(data.rounds) ? data.rounds : [];
            if (rounds.length === 0) {
                roundsDiv.innerHTML = '<p class="text-muted p-2">ไม่พบรอบ Yeekee สำหรับงวดนี้</p>';
                return;
            }
            var html = '<table class="table table-sm table-bordered mb-0"><thead class="thead-light"><tr>'
                + '<th>รอบที่</th><th>สถานะ</th><th>ยิงแล้ว</th><th>ปิดยิงเมื่อ</th><th>Snapshot</th><th></th>'
                + '</tr></thead><tbody>';
            rounds.forEach(function (r) {
                html += '<tr>'
                    + '<td>' + escapeHtml(r.round_no) + '</td>'
                    + '<td>' + escapeHtml(r.status) + '</td>'
                    + '<td>' + escapeHtml(r.shoot_count) + '</td>'
                    + '<td>' + escapeHtml(r.shoot_closed_at || '-') + '</td>'
                    + '<td>' + (r.has_snapshot ? '<span class="badge badge-success">มี</span>' : '<span class="badge badge-secondary">ไม่มี</span>') + '</td>'
                    + '<td><button class="btn btn-xs btn-info" onclick="loadYeekeeAuditDetail(' + escapeHtml(r.id) + ')">ดู Audit</button></td>'
                    + '</tr>';
            });
            html += '</tbody></table>';
            roundsDiv.innerHTML = html;
        }).catch(function () {
            roundsDiv.innerHTML = '<p class="text-danger p-2">โหลดรายการรอบล้มเหลว</p>';
        });
    };

    window.loadYeekeeAuditDetail = function (roundId) {
        var detailDiv = document.getElementById('yeekeeAuditDetail');
        detailDiv.style.display = 'block';
        detailDiv.innerHTML = '<p class="text-muted p-2">กำลังโหลด Audit...</p>';

        var url = _showBaseUrl + '/' + roundId + '/audit';
        requestJson(url).then(function (data) {
            var round = data.round || {};
            var shoots = Array.isArray(data.shoots) ? data.shoots : [];
            var isSensitiveResponse = !data._sensitive_permission_required;
            var sensitiveNote = data._sensitive_permission_required
                ? '<div class="alert alert-warning py-1 mb-2 text-sm">ข้อมูล masked — ต้องใช้ permission <code>' + escapeHtml(data._sensitive_permission_required) + '</code> เพื่อดูข้อมูลจริง</div>'
                : '';

            var shootHeaders = isSensitiveResponse
                ? '<th>#</th><th>Position</th><th>เลข</th><th>Member ID</th><th>IP</th><th>User Agent</th><th>เวลา</th>'
                : '<th>#</th><th>Position</th><th>เลข (masked)</th><th>เวลา</th>';

            var shootRows = '';
            shoots.forEach(function (s, idx) {
                if (isSensitiveResponse) {
                    shootRows += '<tr><td>' + escapeHtml(idx + 1) + '</td><td>' + escapeHtml(s.position) + '</td><td><strong>' + escapeHtml(s.number_text) + '</strong></td>'
                        + '<td>' + escapeHtml(s.member_id || '-') + '</td><td>' + escapeHtml(s.ip_address || '-') + '</td><td>' + escapeHtml(s.user_agent || '-') + '</td>'
                        + '<td>' + escapeHtml(s.submitted_at || '-') + '</td></tr>';
                } else {
                    shootRows += '<tr><td>' + escapeHtml(idx + 1) + '</td><td>' + escapeHtml(s.position) + '</td><td><strong>' + escapeHtml(s.number_text) + '</strong></td>'
                        + '<td>' + escapeHtml(s.submitted_at || '-') + '</td></tr>';
                }
            });

            var snapshotHtml = '';
            if (data.has_snapshot && data.snapshot) {
                snapshotHtml = '<div class="mt-2"><strong>Snapshot Hash:</strong> <code class="text-break">' + escapeHtml(data.snapshot_hash || '') + '</code></div>'
                    + '<pre class="bg-light border rounded p-2 mt-1" style="max-height:300px;overflow:auto;font-size:11px;white-space:pre-wrap;">'
                    + escapeHtml(JSON.stringify(data.snapshot, null, 2)) + '</pre>';
            }

            detailDiv.innerHTML = sensitiveNote
                + '<div class="card card-outline card-info"><div class="card-header py-2"><h6 class="mb-0">รอบที่ ' + escapeHtml(round.round_no) + ' — ' + escapeHtml(round.market_name) + ' (วันที่ ' + escapeHtml(round.round_date) + ')</h6></div>'
                + '<div class="card-body p-2">'
                + '<p class="mb-1 text-muted">ยิง: ' + escapeHtml(round.shoot_count) + ' | last_position: ' + escapeHtml(round.last_shoot_position) + ' | hash: <code>' + escapeHtml(round.shoot_snapshot_hash || '-') + '</code></p>'
                + '<div class="table-responsive" style="max-height:350px;overflow-y:auto;">'
                + '<table class="table table-sm table-bordered mb-0"><thead class="thead-light"><tr>' + shootHeaders + '</tr></thead>'
                + '<tbody>' + (shootRows || '<tr><td colspan="' + (isSensitiveResponse ? '7' : '4') + '" class="text-center text-muted">ไม่มีข้อมูลการยิง</td></tr>') + '</tbody></table>'
                + '</div>'
                + snapshotHtml
                + '</div></div>';
        }).catch(function () {
            detailDiv.innerHTML = '<p class="text-danger p-2">โหลด Audit ล้มเหลว</p>';
        });
    };
}());
</script>
@endif
