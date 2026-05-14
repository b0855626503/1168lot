#!/usr/bin/env python3
"""
Backfill lotto_result_archive_legacy_results from api.expalert.cc

Fetches ALL pages from /data/backward/{slug} for every lottery type,
transforms to legacy archive format, and INSERT IGNOREs into the DB.

Usage:
  python3 scripts/lotto/backfill_legacy_from_expalert.py

Env vars (optional):
  EXPALERT_API_KEY  — overrides default API key
  DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD — database connection
"""

import json, subprocess, hashlib, time, os, sys
from datetime import datetime
import pytz

# ── Config ──────────────────────────────────────────────────────────
API_KEY = os.environ.get(
    "EXPALERT_API_KEY",
    "exp_9eb164ed6497cbf18e3d1e13c7cc1b1914fbd92aa687c6cce78855ae7db68bbc",
)

DB_HOST = os.environ.get("DB_HOST", "127.0.0.1")
DB_PORT = os.environ.get("DB_PORT", "3306")
DB_NAME = os.environ.get("DB_DATABASE", "1168lot_wallet")
DB_USER = os.environ.get("DB_USERNAME", "root")
DB_PASS = os.environ.get("DB_PASSWORD", "root")

BANGKOK = pytz.timezone("Asia/Bangkok")
BATCH_SIZE = 200
PAGE_DELAY = 0.3  # seconds between pages (avoid rate limit)

# ── Helpers ─────────────────────────────────────────────────────────

def mysql_query(sql):
    return subprocess.run(
        ["mysql", "-h", DB_HOST, "-P", DB_PORT, "-u", DB_USER, f"-p{DB_PASS}", DB_NAME, "-N", "-e", sql],
        capture_output=True, text=True,
    )

def mysql_query_n(sql):
    """Run query, return stripped lines (no header)."""
    r = mysql_query(sql)
    return [x.strip() for x in r.stdout.strip().split("\n") if x.strip()]


def fetch_slugs():
    """Get all lottery types from lotto_result_sources (remote or local)."""
    lines = mysql_query_n(
        "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(request_query_template_json, '$.type')) "
        "FROM lotto_result_sources WHERE is_active = 1 "
        "AND request_query_template_json LIKE '%\"type\"%'"
    )
    slugs = set(s for s in lines if s and s != "NULL")

    # Fallback: use all types that ever appeared in legacy archive
    if len(slugs) < 20:
        lines2 = mysql_query_n(
            "SELECT DISTINCT type FROM lotto_result_archive_legacy_results"
        )
        slugs.update(s for s in lines2 if s)

    # also official types
    slugs.update(["gsb", "baac", "goverment", "magnum4d", "laosdevelops"])
    return sorted(slugs)


def fetch_backward(slug, page):
    """Fetch one page from expalert backward API. Returns list of entries."""
    url = f"https://api.expalert.cc/data/backward/{slug}?page={page}"
    r = subprocess.run(
        ["curl", "-s", "-H", f"x-api-key: {API_KEY}", url],
        capture_output=True, text=True, timeout=30,
    )
    try:
        data = json.loads(r.stdout)
    except Exception:
        return []
    return data.get("data", [])


def backfill(slugs=None):
    if slugs is None:
        slugs = fetch_slugs()

    print(f"Slugs to process: {len(slugs)}")
    total_inserted = 0
    batch = []

    def flush():
        nonlocal total_inserted, batch
        if not batch:
            return
        vals = ",\n".join(batch)
        sql = (
            "INSERT IGNORE INTO lotto_result_archive_legacy_results "
            "(type, name_th, request_date, page, lottos_name, lottos_th, "
            "lottos_date, lottos_time, lottos_number, lottos_under, "
            "market_code, fetched_at, fetch_status, unique_key, created_at, updated_at) "
            "VALUES\n" + vals
        )
        mysql_query(sql)
        total_inserted += len(batch)
        batch.clear()

    for idx, slug in enumerate(slugs):
        page = 1
        slug_inserted = 0
        while True:
            entries = fetch_backward(slug, page)
            if not entries:
                break  # no more pages for this slug

            for entry in entries:
                if not isinstance(entry, dict):
                    continue
                result = entry.get("result", {})
                number = str(result.get("number", "") or "")
                under = str(result.get("under", "") or "")
                iso_date = str(result.get("date", "") or "")
                if not number or not iso_date:
                    continue

                try:
                    dt = datetime.fromisoformat(iso_date.replace("Z", "+00:00"))
                    request_date = dt.astimezone(BANGKOK).strftime("%Y-%m-%d")
                except Exception:
                    continue

                unique_key = hashlib.sha256(
                    f"{slug}|{request_date}|expalert".encode()
                ).hexdigest()

                th = (
                    str(entry.get("th", "") or "")
                    .replace("\\", "\\\\")
                    .replace("'", "\\'")
                )
                lottos_time = str(entry.get("time", "") or "")
                lottos_date = f"{request_date} {lottos_time or '00:00'}:00"

                batch.append(
                    f"('{slug}','{th}','{request_date}',1,'{slug}','{th}',"
                    f"'{lottos_date}','{lottos_time}','{number}','{under}',"
                    f"'{slug}',NOW(),'success','{unique_key}',NOW(),NOW())"
                )
                slug_inserted += 1

                if len(batch) >= BATCH_SIZE:
                    flush()

            page += 1
            time.sleep(PAGE_DELAY)

        flush()
        if idx % 10 == 0 or slug_inserted > 0:
            print(f"  [{idx+1}/{len(slugs)}] {slug}: +{slug_inserted} (total: {total_inserted})")

    print(f"\nDone. Total inserted: {total_inserted}")

    # Show final stats
    rows = mysql_query_n(
        "SELECT COUNT(*), COUNT(DISTINCT type), COUNT(DISTINCT request_date), "
        "MIN(request_date), MAX(request_date) "
        "FROM lotto_result_archive_legacy_results WHERE lottos_number != ''"
    )
    if rows:
        parts = rows[0].split("\t")
        print(f"Final: {parts[0]} rows, {parts[1]} types, {parts[2]} days ({parts[3]} → {parts[4]})")


if __name__ == "__main__":
    slugs = sys.argv[1:] if len(sys.argv) > 1 else None
    backfill(slugs)
