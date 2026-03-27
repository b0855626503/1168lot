กติกากลางที่ให้แปะก่อนทุก PR
Writing

You are working on the Lotto Auto Result implementation in a Laravel codebase.

Global operating rules:

Use subagents for parallel analysis/review only.
Main agent must be the only agent that writes the final integrated patch.
Do not let multiple subagents edit overlapping files.
Preserve existing public/manual settlement flow.
Do not change existing external interfaces unless explicitly required by the locked plan.
Keep backward compatibility.
Use the merged Lotto Auto Result plan as the single source of truth.
Use the execution tracker as implementation memory only; it must not override the merged plan.
Before starting any PR:
read the merged plan
read the execution tracker
summarize locked decisions relevant to this PR
After finishing any PR:
update the execution tracker
list files changed
list decisions applied
list deviations, if any
list risks / follow-up items

Subagent output format:

findings
risks
files likely affected
constraints to preserve

Main agent output requirements:

consolidated implementation plan for this PR
final coherent patch
summary of what changed
validation notes
Prompt 0 — สร้าง merged plan + execution tracker ก่อนเริ่ม
Writing

Use subagents for analysis only.

Subagent A:

inspect docs/04_PLANS and existing Lotto plan files
determine where the merged Lotto Auto Result plan should live
verify naming/header/status conventions

Subagent B:

inspect docs/internal and any execution-tracking conventions already used in the repo
identify the best location/pattern for an implementation tracker

Subagent C:

inspect current Lotto module structure and list the major areas that PR-01 through PR-11 will affect
focus on migrations, models, providers, routes, controllers, services, commands, observers, config, ACL, docs

Main agent:

create the merged plan file if it does not exist:
docs/04_PLANS/2026-03-27_lotto-auto-result-integration.md
ensure status is PENDING
keep existing ACTIVE Lotto plans unchanged
merge master + execution content into the single plan file
include an appendix/archive-notes section inside the same file
create or update an execution tracker document
do not implement app code yet
return:
created/updated documentation files
locked decisions summary
proposed PR order
PR-01 — Schema Foundation
Writing

Use subagents for analysis only, then write code only from the main agent.

PR target: PR-01 Schema Foundation for Lotto Auto Result.

Subagent A:

inspect existing Lotto migrations and database naming conventions
identify where new migrations should live
check foreign key conventions and rollback safety patterns

Subagent B:

inspect Lotto models related to draws, markets, and fetch/log style tables
identify fillable/casts/relations likely needed later
do not propose behavior changes

Subagent C:

inspect the merged plan and execution tracker
extract locked schema decisions for PR-01 only
list acceptance criteria and non-goals

Main agent:

implement PR-01 only
create new tables and draw fields exactly as locked by plan
keep backward compatibility
do not modify runtime behavior
add only safe indexes needed now
update execution tracker
run validation relevant to this PR

Return:

files changed
migration summary
schema decisions applied
any forward-compatibility notes for PR-02+
PR-02 — Result Source Resolver
Writing

Use subagents for analysis only. Main agent writes the final patch.

PR target: PR-02 ResultSourceResolver.

Subagent A:

inspect Lotto models and existing service patterns for config resolution / effective window / priority selection
identify the best package/service namespace and relation usage

Subagent B:

inspect lotto_result_sources schema from PR-01 and LottoDraw/LottoMarket models
propose how source resolution should work without causing stale config issues

Subagent C:

inspect the merged plan and tracker
extract locked rules for:
market_id + is_active + effective window
no-source behavior = return null + clear snapshot
snapshot persistence expectations

Main agent:

implement ResultSourceResolver
return null + clear snapshot when no active source matches
do not fetch externally
persist draw-level source snapshot in the field locked by the plan
keep implementation narrow and backward compatible
update execution tracker

Return:

files changed
no-source behavior confirmation
snapshot behavior confirmation
tests/validation notes
PR-03 — Request Builder + Lookup Date Engine
Writing

Use subagents for analysis only. Main agent writes the final patch.

PR target: PR-03 Request Builder + Lookup Date Engine.

Subagent A:

inspect date/time handling patterns in Lotto and project-wide timezone usage
focus on Asia/Bangkok handling and draw/result date semantics

Subagent B:

inspect templating/config rendering needs for endpoint/query/body/header construction
identify safe implementation patterns for placeholder expansion

Subagent C:

extract locked rules from the merged plan and tracker:
supported lookup_date_mode values
unknown placeholder behavior = fail fast with exception
no external fetch in this PR

Main agent:

implement ResultRequestBuilder
support the locked lookup date modes only
fail fast on unknown placeholders with a dedicated exception
do not silently keep tokens or replace with empty string
do not perform HTTP requests in this PR
update execution tracker

Return:

files changed
supported modes implemented
placeholder failure behavior
validation notes
PR-04 — Fetcher
Writing

Use subagents for analysis only. Main agent writes the final patch.

PR target: PR-04 ResultFetcher.

Subagent A:

inspect existing HTTP client usage patterns in the codebase
identify timeout/error-handling/logging conventions

Subagent B:

inspect Lotto logging patterns and any existing fetch/api log models/tables
identify where fetch-log writes should live

Subagent C:

extract locked rules from plan/tracker:
non-2xx + exception = HTTP_ERROR
log request_url / response_status / response_body / duration_ms
no parsing in this PR

Main agent:

implement ResultFetcher
classify non-2xx and exceptions as HTTP_ERROR
always record fetch log entries
keep parsing out of this PR
keep implementation reusable for later orchestration
update execution tracker

Return:

files changed
HTTP_ERROR behavior confirmation
log fields written
validation notes
PR-05 — Parser Engine
Writing

Use subagents for analysis only. Main agent writes the final patch.

PR target: PR-05 ResultParser.

Subagent A:

inspect parsing/helper utilities already available in the project
identify safe libraries/helpers already in use for JSON traversal, HTML parsing, regex extraction

Subagent B:

inspect plan/tracker and lock parser responsibilities
confirm parser output must be raw extracted tree only
confirm business mapping belongs to PR-06

Subagent C:

inspect likely storage/log fields and fetcher outputs from prior PRs
identify how parser should consume raw response payload without changing fetch contracts

Main agent:

implement ResultParser
support only the locked parser types for phase 1
return raw extracted tree only
do not return final logical field map in this PR
classify parse states per plan
update execution tracker

Return:

files changed
parser output format
supported parser types
parse-state behavior
validation notes
PR-06 — Mapper + Validator
Writing

Use subagents for analysis only. Main agent writes the final patch.

PR target: PR-06 ResultMapper + ResultValidator.

Subagent A:

inspect SettlementService contract and all relevant draw settlement expectations
identify exactly which logical fields must be produced and what must not be changed

Subagent B:

inspect PR-05 parser outputs and propose clean mapping boundaries
identify safe validation rules for first_prize and last_2_digits

Subagent C:

extract locked rules from plan/tracker:
mapper input = extracted payload
mapper output = logical field map
validator enforces numeric/length constraints
invalid = VALIDATION_ERROR

Main agent:

implement ResultMapper and ResultValidator
preserve settlement contract
keep validation strict and explicit
do not apply results yet
update execution tracker

Return:

files changed
mapper output contract
validator rules
validation notes
PR-07 — Apply Result
Writing

Use subagents for analysis only. Main agent writes the final patch.

PR target: PR-07 Apply Result.

Subagent A:

inspect SettlementService invocation patterns and any transaction/idempotency conventions
identify safest integration point for auto-apply without changing manual flow

Subagent B:

inspect LottoDraw fields and result hash storage introduced by prior PRs
identify where canonical applied hash/state should live

Subagent C:

extract locked rules from plan/tracker:
use result_fetch_status as canonical auto-result state
CONFLICT goes into result_fetch_status
if draw already RESULTED and new hash differs, do not overwrite existing settled result/hash
keep latest raw/normalized payload for audit

Main agent:

implement apply flow integration with SettlementService
enforce conflict rule exactly as locked
do not change manual settlement behavior
keep idempotency explicit
update execution tracker

Return:

files changed
conflict behavior confirmation
canonical state behavior confirmation
validation notes
PR-08 — Fetch Orchestration Command
Writing

Use subagents for analysis only. Main agent writes the final patch.

PR target: PR-08 Fetch Orchestration Command.

Subagent A:

inspect existing command/scheduler/locking patterns in the project
identify the safest orchestration pattern for scheduled Lotto work

Subagent B:

inspect services from PR-02 through PR-07 and propose the clean orchestration sequence
identify duplicate-run / idempotency risks

Subagent C:

extract locked rules from plan/tracker:
select eligible draws only
skip if now < result_at
no-source = NO_SOURCE
use resolver → builder → fetch → parse → map → validate → apply
log state transitions safely

Main agent:

implement lotto orchestration command
use locking / safe iteration patterns consistent with the repo
do not create duplicate apply effects
update execution tracker

Return:

files changed
orchestration flow summary
eligibility/skip behavior
validation notes
PR-09 — Retry + Backoff
Writing

Use subagents for analysis only. Main agent writes the final patch.

PR target: PR-09 Retry + Backoff.

Subagent A:

inspect how result_fetch_attempts and fetch logs are currently written
identify where retry decisions should be made cleanly

Subagent B:

inspect command scheduling frequency and timing assumptions
identify how to implement retry/backoff without creating infinite loops or noisy status churn

Subagent C:

extract locked rules from plan/tracker:
every 1 minute x 15
then every 5 minutes x 12
NOT_READY = retryable
TEMPLATE_ERROR = non-retryable by scheduler
EXHAUSTED after policy is exceeded
time-window guard must remain intact

Main agent:

implement retry/backoff policy only as locked
keep retryability classifications explicit
ensure no infinite loop behavior
update execution tracker

Return:

files changed
retry state rules implemented
exhausted behavior summary
validation notes
PR-10 — Admin Tooling
Writing

Use subagents for analysis only. Main agent writes the final patch.

PR target: PR-10 Admin Tooling.

Subagent A:

inspect existing Lotto admin panel/menu/route/controller/page/resource patterns
identify where new auto-result tooling should attach without changing existing interfaces

Subagent B:

inspect existing queue/job polling/UI feedback patterns in the admin module
identify the best way to implement async Test Fetch UX

Subagent C:

extract locked rules from plan/tracker:
Test Fetch must be Async Job
UI polls result/status
dry-run only, never call settlement/apply
reuse production pipeline as much as possible
retain test outputs for audit/debug
protect with ACL

Main agent:

implement admin tooling in the existing admin module pattern
keep Test Fetch async and dry-run only
avoid changing existing manual routes/interfaces
update execution tracker

Return:

files changed
admin entry points added
test fetch behavior summary
ACL/authorization notes
validation notes
PR-11 — Hardening
Writing

Use subagents for analysis only. Main agent writes the final patch.

PR target: PR-11 Auto-Result Hardening.

Important branch constraint:

If the real PR-08/09 pipeline is not yet present in this branch, implement PR-11 as a hardening-ready foundation only.
Do not fake or partially recreate PR-08/09 pipeline behavior.

Subagent A:

inspect existing alerting/logging/rate-limiter infrastructure already available in the repo
identify how to integrate with SendTelegramAlert, structured logs, and RateLimiter safely

Subagent B:

inspect current LottoDraw / fetch-log / config capabilities
identify minimal forward-compatible schema/service additions needed for metrics, alerts, and rate limiting

Subagent C:

extract locked rules from plan/tracker:
exhausted alert = structured log + async Telegram
spam guard per exhausted draw
success_rate = APPLIED only
retry_count = execution-level attempt_no > 1
RATE_LIMITED logged at fetch-log level
draw-level status must not overwrite terminal states
services must degrade gracefully if real pipeline data is incomplete

Main agent:

implement PR-11 as hardening-ready foundation if pipeline is absent
preserve existing manual flow completely
add forward-compatible config/services/command/observer/schema only as needed
keep metrics/alerts/rate-limit usable in isolation
update execution tracker

Return:

files changed
branch-safe hardening summary
locked rule compliance summary
validation notes
Prompt ปิดงานทุก PR
Writing

Before finalizing this PR, do a final self-review.

Check:

merged plan compliance
execution tracker updated
backward compatibility preserved
no accidental interface change
no overlapping responsibilities leaked across parser/mapper/validator/apply layers
no hardcoded per-market logic unless explicitly allowed by the plan
logs/errors/status names consistent with locked taxonomy
migrations/models/providers/routes/ACL updated coherently
tests or validation commands run where appropriate

Return:

concise change summary
files changed
locked decisions applied
risks / follow-ups
exact validation steps run

# PROMPT_PR.md

ให้ยึดกติกานี้ทุกครั้งก่อนทำ PR และหลังจบ PR

## Source of truth
- ต้องอ่าน merged plan ก่อนทุกครั้ง
- ต้องอ่าน execution tracker ก่อนทุกครั้ง
- merged plan = source of truth หลัก
- execution tracker = implementation memory only
- ถ้ามี conflict ให้ยึด merged plan เสมอ

## PR scope discipline
- งานทั้งหมดแยกเป็น PR-01 ถึง PR-11
- แต่ละ PR มีขอบเขตชัดเจน
- ห้ามข้าม layer / ข้าม responsibility ของ PR อื่น
- ห้ามแอบ implement งานของ PR ถัดไปโดยไม่จำเป็น
- ห้ามเปลี่ยน interface เดิม ถ้าแผนไม่ได้ล็อกไว้

## Execution tracking
หลังจบทุก PR ต้องอัปเดต execution tracker เสมอ โดยอย่างน้อยต้องมี:
- completed scope
- files changed
- locked decisions applied
- deviations (if any)
- risks / follow-up items
- validation steps run

## Implementation discipline
- ใช้ subagent เพื่อ analysis/review ได้
- main agent เท่านั้นที่รวมผลและเขียน patch สุดท้าย
- ห้ามให้หลาย subagent แก้ไฟล์ทับกัน
- ต้องรักษา backward compatibility
- ต้อง preserve manual/manual-settle flow เดิม
- ห้าม hardcode per-market logic ถ้าแผนไม่ได้อนุญาต
- ต้องแยก parser / mapper / validator / apply ให้ตรงชั้น

## Important branch-specific rule for PR-11
- ถ้า pipeline ของ PR-08/PR-09 ยังไม่มีอยู่จริงใน branch ปัจจุบัน
  ให้ implement PR-11 แบบ hardening-ready foundation เท่านั้น
- ห้าม fake หรือ partially recreate pipeline PR-08/09
- metrics / alerts / rate limiting ต้องทำงานแบบ isolated ได้
- ต้อง degrade gracefully หาก execution data จริงยังไม่ครบ

## Agent response format
ก่อนเริ่มงาน ให้ตอบสรุปสั้น ๆ ว่าอ่านและจะยึดอะไรบ้าง
โดยต้องสรุปอย่างน้อย:
- ใช้ merged plan + execution tracker เป็น source of truth ก่อน/หลังทุก PR
- แยกขอบเขต PR-01..PR-11 ชัดเจน ห้ามข้ามความรับผิดชอบข้ามชั้น
- ต้องอัปเดต tracker ทุกครั้ง พร้อมรายการไฟล์ / decisions / risks / validation
- PR-11 มีเงื่อนไขสำคัญ: ถ้า pipeline PR-08/09 ยังไม่อยู่ใน branch ให้ทำแบบ hardening-ready foundation เท่านั้น

## Current confirmed context
- สิ่งที่ทำล่าสุดสำหรับ PR-11 สอดคล้องกับเงื่อนไขนี้แล้ว
- PR-11 ถูกทำเป็น hardening foundation โดยไม่ปลอม pipeline
- validation ล่าสุดรันแล้วครบ
