# Frontend API V1 - Edge Cases

อัปเดตล่าสุด: 2026-04-30

- `GET /api/v1/lotto/navbar-config`: fallback locale แบบ deterministic
- `POST /api/v1/deposit/loadbank` และ `/deposit/loadbank/random`: fallback `deposit_min` และ `qr_pic` empty-string behavior
- Lotto latest draw selection: ข้าม `draft` ตาม policy
- Yeekee proof endpoint: ก่อน reveal จะไม่ expose proof/reveal payload ครบ
