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
- `GET /api/v1/slides`
- `GET /api/v1/meta/online-members`
- `GET /api/v1/meta/contact-channels`
- `GET /api/v1/meta/site`
- `GET /api/v1/realtime/config`
- `GET /api/v1/wheel/list`
- `POST /api/v1/wheel/spin`
- `GET /api/v1/wheel/history`
- `GET /api/v1/lotto/draws`
- `GET /api/v1/lotto/draws/{id}`
- `GET /api/v1/lotto/markets/latest`
- `GET /api/v1/lotto/markets/{marketId}/betting-context`
- `GET /api/v1/lotto/markets/{marketId}/results`
- `GET /api/v1/lotto/markets/{marketId}/draws/{drawId}/result`
- `GET /api/v1/lotto/results/by-date`
- `GET /api/v1/lotto/navbar-config`
- `POST /api/v1/lotto/bet`
- `GET /api/v1/lotto/groups/{groupId}/packages`
- `POST /api/v1/lotto/groups/{groupId}/select-package`
- `GET /api/v1/lotto/groups/{groupId}/selected-package`
- `GET /api/v1/lotto/tickets`
- `GET /api/v1/lotto/tickets/{id}`
- `POST /api/v1/lotto/tickets/{id}/cancel`
- `POST /api/v1/lotto/yeekee/rounds/{roundId}/shoot`
- `GET /api/v1/lotto/yeekee/rounds`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}`
- `GET /api/v1/lotto/yeekee/markets/{marketId}/current-round`
- `GET /api/v1/lotto/yeekee/markets/{marketId}/rounds`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/shoots`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/reward-status`
- `GET /api/v1/lotto/yeekee/rounds/{roundId}/result-proof`

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
