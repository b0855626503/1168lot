# Lotto Internal Result Sources — Contract Freeze (PR-01)

อัปเดตล่าสุด: 2026-03-30
สถานะ: LOCKED (implementation baseline)

## วัตถุประสงค์

- สรุป behavior จริงจาก mini projects 3 ชุด:
  - `lottery-php`
  - `dowjones-midnight`
  - `dowjones-extra`
- ล็อก contract กลางของระบบหลักก่อนเริ่ม implementation
- ลดการตีความหน้างาน โดยให้ downstream implement แบบ decision-complete

## หลักฐานต้นทาง (zip evidence)

- `C:/Users/b0855/Downloads/lottery-php.zip`
- `C:/Users/b0855/Downloads/dowjones-midnight.zip`
- `C:/Users/b0855/Downloads/dowjones-extra.zip`

ไฟล์ที่ใช้อ้างอิงหลัก:

- `index.php`
- `lottery.php`
- `src/lottery.service.php`
- `src/constants.php`

## Source Matrix (source -> params -> remote -> output)

| source | input params (legacy) | upstream remote | output สำคัญจาก source เดิม |
|---|---|---|---|
| exphuay (`lottery-php`) | `type` (required), `page` (default=1), `date` (optional) | `https://exphuay.com/backward/{type}/__data.json?page={page}&x-sveltekit-invalidated=01&date={Y-m-d}` | `success,type,label,page,date,url,data` |
| dowjones-midnight | `type=dowjones-midnight`, `date` (optional) | `https://api.dowjones-midnight.com/result?date={Y-m-d}` | `success,type,label,date,start_spin,show_result,digit5,url,data` |
| dowjones-extra | `type=dowjones-extra`, `date` (optional) | `https://api.dowjonesextra.com/result?date={Y-m-d}` | `success,type,label,date,start_spin,show_result,digit5,url,data` (+ upstream มี `now`,`update`) |

## Date Contract

- legacy input ที่ต้องรองรับ:
  - `Y-m-d`
  - `d/m/Y`
  - `d-m-Y`
- internal canonical output:
  - `draw_date` ต้องเป็น `Y-m-d` เสมอ

## Internal Endpoint Contract (locked)

- `GET /internal/lottery/results/exphuay/{type}?date=YYYY-MM-DD&page=1`
- `GET /internal/lottery/results/dowjones-midnight?date=YYYY-MM-DD`
- `GET /internal/lottery/results/dowjones-extra?date=YYYY-MM-DD`

หมายเหตุ:

- `page` ใช้กับ exphuay เท่านั้น
- dowjones-midnight/extra ไม่รองรับ `page` ใน contract กลาง

## Canonical Response Schema (locked)

```json
{
  "success": true,
  "source": "dowjones-midnight",
  "type": "dowjones-midnight",
  "draw_date": "2026-03-30",
  "raw_result": {},
  "normalized_result": {
    "first_prize": null,
    "top_3": null,
    "top_2": null,
    "bottom_2": null,
    "digit_4": null,
    "digit_5": null
  },
  "meta": {
    "remote_url": "",
    "request_params": {},
    "fetched_at": "",
    "latency_ms": 0
  },
  "errors": []
}
```

กติกา:

- key ข้างต้นต้องมีครบทุก source
- field ที่ derive ไม่ได้ ให้ `null` (ห้าม drop key)
- `errors` ต้องเป็น array เสมอ
- `success` สะท้อน integration outcome ไม่ใช่เพียง HTTP 200

## Dowjones Supplemental Field Policy (pre-lock for PR-14)

field จาก upstream ที่ไม่ใช่ผลรางวัลตรง:

- `start_spin`
- `show_result`
- `now`
- `update`

policy baseline:

- ห้ามใส่เข้า `normalized_result` ถ้าไม่ใช่เลขผลรางวัลโดยตรง
- อนุญาตเก็บใน `meta` หรือ `raw_result` ตาม policy ราย field ใน PR-14
- หาก field ใดไม่ใช้ใน contract กลาง ให้ระบุ drop ชัดเจน

## Derive Policy (pre-lock)

- derive ได้เมื่อมีข้อมูลต้นทางชัดเจนเท่านั้น
- ถ้าข้อมูลไม่พอหรือกำกวม ให้คืน `null`
- ห้าม heuristic เดาเลขเพื่อเติม canonical fields

## Known Limitations

- mini projects เดิมไม่มี production-grade auth/rate-limit policy
- response shape ของแต่ละ source ต่างกัน จึงต้อง normalize ก่อนใช้ downstream
- behavior ฝั่ง CLI/path-style URL เป็น legacy surface และไม่ใช่ production path ใหม่

## Output ที่ต้องส่งต่อให้ PR ถัดไป

1. matrix source->params->remote->output (เอกสารนี้)
2. canonical schema baseline (เอกสารนี้)
3. รายการ assumption/limitations (เอกสารนี้)
4. field ownership decision รายละเอียดสำหรับ dowjones supplemental fields:
   - `docs/internal/03_DOMAINS/lotto-internal-result-sources-dowjones-extra-fields-policy.md`
5. compatibility lock:
   - `docs/internal/03_DOMAINS/lotto-internal-result-sources-compatibility-matrix.md`
6. migration/backfill plan:
   - `docs/internal/03_DOMAINS/lotto-internal-result-sources-migration-backfill.md`
