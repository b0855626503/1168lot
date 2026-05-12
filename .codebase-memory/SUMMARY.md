# Codebase Memory Summary

อัปเดตล่าสุด: 2026-05-12

## Docs Navigation (Token-Efficient)

- ปรับ startup docs ให้ lean และลดข้อมูลซ้ำข้ามไฟล์ (`docs/README.md`, `docs/START-HERE.md`, `docs/internal/01_SYSTEM/startup-digest.md`)
- เส้นทางอ่านหลักเป็น `Memory First -> Domain On-Demand -> Escalation`
- เกณฑ์เปิดไฟล์ใหญ่ถูกล็อกให้ชัด: เปิดเมื่อเปลี่ยน behavior จริง, งานเสี่ยง (financial/auth/retry/queue/cron/schema), หรือเจอ code-doc mismatch
- ยืนยันกติกา sync: เปลี่ยน behavior ต้องอัปเดต `docs + memory + MCP index`

## Admin Dashboard / Status Routing

- Admin root `/` ต้องเข้าสู่ auth flow และ redirect ไป login เมื่อยังไม่ได้ login; ห้าม redirect ไป `/status`
- Status page ใช้ route เฉพาะ `/status` และ ping endpoint `/status/ping` ชื่อ `status.ping`
- Dashboard admin มี request หลักหลายชุด (`summary`, `conversion`, `trends`, `funnel`, `activity`, `alerts`) และข้อมูลรอง (`bank`, `login`, `online`, `loadCnt`)
- Dashboard request หนักต้องเข้าคิวแบบจำกัด concurrency เพื่อไม่เต็มช่อง browser connection
- เมื่อ user กดเมนูออกจาก dashboard ต้อง abort dashboard background requests เพื่อให้ navigation ไม่ถูก block
- Initial dashboard load ต้อง refresh รอบเดียว; datepicker initialization ห้าม trigger refresh ซ้ำกับ `refreshAll(initial)`

## Key Files

- `routes/web.php`
- `resources/views/status/index.blade.php`
- `packages/Gametech/Admin/src/Resources/views/module/dashboard/index.blade.php`
- `tests/Feature/StatusPageTest.php`
- `tests/Unit/Admin/DashboardViewRequestQueueTest.php`

## Realtime

- Lotto `.public.activity.updated` payload แนบ `message` พร้อมแสดงผลสำหรับ draw closed/resulted/reopened และ ticket-list resulted update

## Wallet Ledger

- `MemberCashbackRepository::refillSeamlessDirect()` append `wallet_transactions` แบบ `TRANCB` เมื่อ cashback ถูกเครดิตเข้ากระเป๋าหลัก

## Member Market Policy (Blacklist — PR #83, 2026-05-11)

- เปลี่ยนจาก default-deny เป็น default-allow + blacklist: สมาชิกทุกคนเดิมพันได้ยกเว้นถูก blacklist (`is_allowed=false`)
- Bootstrap/Rollout/Migrate commands ปรับ semantics ใหม่
- Admin UI: `MemberLottoPermissionController@delete` (deactivate via POST), หน้าแสดง "Blocked" แบบ static label
- Tests: `MemberLottoPermissionControllerTest`, `BetServicePermissionTest`
- Route: `POST /admin/lotto/member-permissions/delete` (ใหม่ใน PR #83)
- Key files: `MemberMarketPolicyService`, `BetService`, `MemberLottoPermissionController`

## Frontend Theme

- Endpoint: `GET /api/v1/theme` → `FrontendThemeController::show()` → `frontend.api.v1.theme`
- Provides frontend theme configuration (colors, branding, presets) defined in `packages/Gametech/Lotto/src/Config/frontend-theme-presets.php`
- Read-only public endpoint; no authentication required

## Result Archive (BoA-247, 2026-05-12)

- `lotto_result_archives` read model for historical lottery results — dedicated public API table
- Mirror from resulted draws (Normalizer → Writer → Archive), fill missing from external, reconcile
- 3 commands: `lotto:mirror-result-archives`, `lotto:fill-missing-results`, `lotto:reconcile-result-archive`
- 1 job: `MirrorDrawToArchiveJob` — afterCommit dispatch from ResultApplier, queue `lotto`
- Public API: `GET /api/v1/lotto/results/{marketCode}` — paginated at draw_date level, throttled, cached
- Key files: `ArchiveNormalizerService`, `ArchiveWriterService`, `ArchiveRepository`, `LottoResultArchiveController`
