> สถานะ: ACTIVE
> วันที่: 2026-03-30
> โดเมน/เรื่อง: Lotto / Browser Runtime Operational Follow-ups
> แทนแผนเก่า: -
> อ้างอิง Linear Project: `Browser Runtime Operational Follow-ups` (https://linear.app/boatjunior/project/browser-runtime-operational-follow-ups-ed558389ed10)

# Summary

แผนนี้เป็น operational follow-up หลัง Browser Runtime Phase 2 เสร็จแล้ว โดยโฟกัส 3 งานหลักเพื่อป้องกัน regression และเพิ่มความพร้อม on-call:

1. CI guardrail สำหรับ AutoResultV2
2. Scheduled artifact retention cleanup
3. Incident runbook ตาม reason codes

# Scope (Current Track)

## Ops-01 CI Guardrail for AutoResultV2 Browser Runtime

- อ้างอิง issue: `BOA-15`
- เป้าหมาย:
  - รัน `tests/Unit/Lotto/AutoResultV2` ในทุก PR
  - fail-fast เมื่อ policy/trace contract regression
  - เก็บ log/junit artifact เพื่อ debug

## Ops-02 Scheduled Artifact Cleanup for Browser Runtime

- อ้างอิง issue: `BOA-16`
- เป้าหมาย:
  - cleanup artifacts ใน `storage/app/lotto/browser-runtime` ตาม retention
  - รองรับ dry-run และสรุปจำนวนที่ลบ
  - scheduler ทำงานแบบ non hot-path

## Ops-03 Incident Runbook for Browser Runtime Reason Codes

- อ้างอิง issue: `BOA-17`
- เป้าหมาย:
  - map reason code -> first response
  - rollback decision tree (global flag / whitelist)
  - evidence checklist สำหรับ post-incident closure

# Acceptance Criteria

1. มี CI guardrail ทำงานบน PR path และ debug ได้จาก artifacts
2. มี retention cleanup ที่ปลอดภัย (dry-run ได้, ไม่กระทบ hot path)
3. มี runbook ที่ใช้ triage ได้จริงตาม reason code
4. สถานะงานใน docs สอดคล้องกับแผนใน Linear project ปัจจุบัน

# Notes

- แผนนี้ครอบเฉพาะงาน operational hardening เท่านั้น
- งาน implementation baseline ของ Browser Runtime Phase 2 ถือว่าอยู่ในแผน `2026-03-28_lotto-browser-worker-hardening.md` (DONE)
