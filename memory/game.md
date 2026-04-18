# Game Memory

อัปเดตล่าสุด: 2026-04-18

## Responsibility

ดูแล game discovery/login flows ของลูกค้าผ่าน FrontendApi

## Key Endpoints

- `GET /api/v1/games/types`
- `GET /api/v1/games/providers/{type}`
- `GET /api/v1/games/{type}/{provider}`
- `POST /api/v1/games/login`
- `GET /api/v1/games/login/{game}/{code}`

## Module Map

- `packages/Gametech/FrontendApi/src/Routes/api.php`
- `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/GameController.php`
- `packages/Gametech/Game/src/`

## Main Services / Actions

- game list/provider retrieval
- game session/login handoff
- provider warmup/sync ก่อน proxy read

## Important Dependencies

- member auth context
- provider mapping/config
- frontend contract compatibility

## Short Execution Flow

- game endpoint -> GameController -> provider/game domain -> formatted response/login payload

## Source-of-Truth References

- `docs/internal/03_DOMAINS/frontend_api.md`
- `docs/public/api/frontend-v1/03-endpoints.md`
