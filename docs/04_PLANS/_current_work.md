> สถานะ: TRACKER
> วันที่: 2026-05-03
> โดเมน/เรื่อง: meta / navigation
> แทนแผนเก่า: -

# Current Work

อัปเดตล่าสุด: 2026-05-03

ไฟล์นี้เป็นดัชนีเร็วสำหรับ agent — ดูแผนงาน active/pending แยกตาม domain
แหล่งข้อมูลหลักและ authoritative: `docs/04_PLANS/README.md`

> **ข้อบังคับ:** ไฟล์นี้คือ index เท่านั้น — ห้ามใส่รายละเอียด plan / implementation notes
> แต่ละ row ไม่เกิน 1 บรรทัด ถ้าต้องการรายละเอียดให้เปิด plan file โดยตรง

---

## Active Plans

| Domain | Plan File | สถานะ |
|--------|-----------|--------|
| policy | `docs/04_PLANS/2026-05-11_member-market-policy-blacklist-audit.md` | ACTIVE (PR-01 audit) |
| laravel | `docs/04_PLANS/2026-04-06_laravel-8-to-9-upgrade.md` | ACTIVE |
| lotto/yeekee | `docs/04_PLANS/2026-05-02_yeekee-shooting-flow-hardening.md` | ACTIVE |

## Pending Plans (by domain)

| Domain | Plan File |
|--------|-----------|
| frontend-api | `docs/04_PLANS/2026-03-21_frontend-api-v1.md` |
| lotto | `docs/04_PLANS/2026-03-31_lotto-group-package-system-readiness.md` |
| lotto | `docs/04_PLANS/2026-03-21_lotto-concord-proxy-cleanup.md` |
| lotto | `docs/04_PLANS/2026-03-21_lotto-dashboard.md` |
| lotto | `docs/04_PLANS/2026-03-21_lotto-execution-phases.md` |
| wallet | `docs/04_PLANS/2026-03-21_wallet-ledger-implementation.md` |
| lotto | `docs/04_PLANS/2026-03-27_lotto-global-config-migration.md` |

## Tracker

- `docs/04_PLANS/2026-03-27_lotto-auto-result-execution-tracker.md` — implementation memory, not a plan

## Archive Rule

Plans ที่ DONE หรือ SUPERSEDED ยังคงอยู่ใน `docs/04_PLANS/` แต่อยู่ใน section DONE/SUPERSEDED ของ README
ไม่ใช่ active retrieval target — อย่าอ่านโดยไม่มีเหตุผล

## Agent Instructions

1. ดู Active Plans ด้านบนก่อน
2. อ่าน plan file ที่เกี่ยวข้องกับ domain งานปัจจุบัน
3. Full index พร้อม history: `docs/04_PLANS/README.md`
