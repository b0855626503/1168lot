# Game Memory

อัปเดตล่าสุด: 2026-04-18

## Responsibility

ดูแล game discovery/login flows สำหรับลูกค้า ผ่าน FrontendApi BFF

## Key Flows (สั้น)

- list types/providers/games
- game login (tokenized access path)
- warmup/sync provider data ก่อนอ่าน proxy ใน flow ที่กำหนด

## Important Modules

- `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/GameController.php`
- `packages/Gametech/FrontendApi/src/Routes/api.php`
- `packages/Gametech/Game/src/`

## Dependencies

- member auth context
- provider mapping/config
- frontend contract compatibility
