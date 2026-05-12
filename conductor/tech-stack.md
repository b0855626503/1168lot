# Tech Stack — 1168lot

> Detected from: `composer.json`, `package.json`, `config/`, Laravel Boost application-info

## Languages

| Language | Version | Notes |
|----------|---------|-------|
| PHP | 8.2.31 | Primary backend language |
| JavaScript | — | Vue 2.7 frontend |
| SQL | — | MariaDB/MySQL via Eloquent ORM |

## Frameworks

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend | Laravel | 10.50.2 |
| Frontend | Vue.js | 2.7.5 |
| Queue | Laravel Horizon | 5.45.5 |
| WebSocket | Laravel Reverb | 1.10.0 |
| Runtime | Laravel Octane (Swoole) | 2.17.1 |

## Database

| Component | Technology |
|-----------|-----------|
| Primary DB | MySQL / MariaDB |
| Cache & Queue | Redis |
| ORM | Eloquent |

## Infrastructure

| Component | Details |
|-----------|---------|
| Deployment | Self-hosted / Docker |
| Dev Environment | WSL2 (Linux), Bash shell |
| CI | GitHub (via `gh` CLI) |
| Code Formatting | Laravel Pint (`vendor/bin/pint`) |

## Key Dependencies

| Package | Purpose |
|---------|---------|
| `laravel/framework` | Core framework |
| `laravel/horizon` | Queue monitoring and supervision |
| `laravel/octane` | High-performance application server |
| `laravel/reverb` | WebSocket broadcasting |
| `laravel/mcp` | AI agent integration (MCP protocol) |
| `phpunit/phpunit` | Testing framework |
| `laravel/pint` | PHP code style fixer |

## Package Architecture

```
packages/Gametech/
├── Lotto/        # Lottery domain — draws, bets, settlement, results
├── Wallet/       # Financial domain — ledger, transactions
├── Payment/      # Payment gateways — deposit/withdraw
├── FrontendApi/  # BFF layer — customer-facing API
├── Admin/        # Admin panel — back-office management
├── Game/         # Game integration (third-party)
├── Member/       # Member management
└── ...
```
