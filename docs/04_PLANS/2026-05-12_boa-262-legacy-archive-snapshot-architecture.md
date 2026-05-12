# BOA-262: Legacy Archive Snapshot Table Architecture

> Status: PENDING MERGE | Linear: BOA-262 | Covers: PRs 01–05

---

## What

`lotto_result_archive_legacy_results` is an **API snapshot table** populated by fetching from an external legacy archive source API. It is the backing store for `GET /api/v1/lotto/result-archive-legacy`.

---

## Populated By

- Command: `lotto:legacy-results:fetch` (`FetchLegacyArchiveResultsCommand`)
- HTTP client: `LegacyArchiveSourceClient` — fetches from the legacy archive source URL (`config('lotto.legacy_archive.base_url')`)
- Each fetch stores one or more rows per `(type, date)` request

---

## What This Table Is NOT

- NOT a settlement source — no payout, ticket, draw lifecycle events read from it
- NOT a draw lifecycle table — no `open_at`, `close_at`, `status`, `result_number` columns
- NOT related to `lotto_draws` — zero FK or JOIN relationship to the draw lifecycle
- NOT a replacement for `lotto_result_archives` (the internal mirror table used by `GET /api/v1/lotto/results/{marketCode}`)

---

## API Endpoint

`GET /api/v1/lotto/result-archive-legacy`

- Controller: `LottoResultArchiveLegacyController` (FrontendApi package)
- Service: `LegacyArchiveResultService`
- Reads from: `lotto_result_archive_legacy_results` — `fetch_status = 'success'` rows only
- Returns legacy-compatible envelope:

```json
{
  "type": "egx30",
  "nameTH": "หุ้นอียิปต์",
  "date": "2026-04-22",
  "page": 1,
  "count": 1,
  "results": [
    {
      "id": 116506,
      "lottosName": "egx30",
      "lottosTH": "อียิปต์",
      "lottosDate": "2026-04-21T17:00:00.000Z",
      "lottosTime": "20:00",
      "lottosNumber": "239",
      "lottosUnder": "95"
    }
  ],
  "errors": []
}
```

### Field Mapping (snapshot → API)

| API field     | Snapshot column       |
|---------------|-----------------------|
| `lottosName`  | `lottos_name`         |
| `lottosDate`  | `lottos_date_raw`     |
| `lottosTime`  | `lottos_time`         |
| `lottosNumber`| `lottos_number`       |
| `lottosUnder` | `lottos_under`        |
| `lottosTH`    | `lottos_th`           |
| `id`          | `source_result_id` (fallback: row `id`) |
| `nameTH`      | `lotto_markets.name` (preferred, for known types) or `name_th` (snapshot-only types, resolved inside cache-closure query) |

---

## `unique_key` Deduplication

Two strategies depending on whether the source returns an ID:

1. **With source ID**: `sha256(type | date | source_result_id)`
2. **Fallback** (source_result_id = null): `sha256(type | date | lottos_name | lottos_date_raw)`

This ensures idempotent re-runs: the same fetch twice produces 1 row, not 2.

---

## `fetch_status` Enum

| Value       | Meaning                                              | Served by API? |
|-------------|------------------------------------------------------|----------------|
| `success`   | Valid result data stored                             | Yes            |
| `not_found` | Source returned 404 or empty results (sentinel row) | No             |
| `failed`    | HTTP error or parse failure (sentinel row)           | No             |

Sentinel rows (`not_found` / `failed`) are stored for operational visibility but excluded from the API by `WHERE fetch_status = 'success'`.

---

## Success-Row Protection

`LegacyArchiveResultRepository::upsertWithResult()` guards existing success rows:

- A `failed` or `not_found` incoming status cannot overwrite an existing `success` row unless `--force` is passed to the command.
- Cache version key (`lotto:archive:{type}:version`) is bumped only when a row is written (created or updated), not when protection skips a write.
