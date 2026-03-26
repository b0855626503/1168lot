# Admin Query Audit Runtime Report

- Generated at: `2026-03-21 10:59:30`
- Source log: `storage/logs/slow-requests.log`
- Database: `1168lot_wallet`
- Filters: `only-admin=1`, `since=`

## Top Routes

| Route | Menu Key(s) | SQL Count | Unique SQL | Duplicate SQL | SQL ms |
|---|---|---:|---:|---:|---:|
| `admin.home.loadcnt` | `-` | 72 | 8 | 64 | 84 |
| `admin.promotion.loadpromotion` | `-` | 3 | 2 | 1 | 15 |
| `admin.promotion.index` | `top.promotion` | 4 | 3 | 1 | 15 |
| `admin.check_case.index` | `check_case` | 5 | 4 | 1 | 12 |
| `admin.game_user.index` | `wallet.game_user` | 4 | 3 | 1 | 12 |
| `admin.withdraw_seamless.index` | `withdraw_seamless` | 8 | 5 | 3 | 11 |
| `admin.bank_account_in.index` | `ats, ats.bank_account_in` | 4 | 3 | 1 | 10 |
| `admin.bank_account_out.index` | `ats.bank_account_out` | 4 | 3 | 1 | 10 |
| `admin.bank_in.index` | `bank_in` | 8 | 7 | 1 | 9 |
| `admin.rp_alllog.index` | `mon, mon.rp_alllog` | 6 | 4 | 2 | 9 |
| `admin.member.index` | `wallet, wallet.member` | 4 | 3 | 1 | 7 |
| `admin.member.loadbankaccount` | `-` | 3 | 1 | 2 | 7 |

## Route: `admin.home.loadcnt`
- Menu key(s): `-`
- SQL count: `72`, unique: `8`, duplicate: `64`, total ms: `84`

### Repeated SQL #1
- repeat count: `9`, duplicate count: `8`, total ms: `26`, max ms: `7`
- tables: `employees`
- sql:
```sql
select * from `employees` where `code` = ? and `code` <> ? limit 1
```
- bindings: `[1,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "employees",
                    "access_type": "const",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

### Repeated SQL #2
- repeat count: `9`, duplicate count: `8`, total ms: `11`, max ms: `2`
- tables: `bank_payment`
- sql:
```sql
select count(*) as aggregate from `bank_payment` where `bank_payment`.`value` > ? and `bank_payment`.`enable` = ? and `bank_payment`.`status` = ? and date(`date_create`) = ?
```
- bindings: `[0,"Y",0,"2026-03-21"]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "bank_payment",
                    "access_type": "ref",
                    "possible_keys": [
                        "bankcheck",
                        "idx_bp_status_enable_date",
                        "idx_enable_status_date_value"
                    ],
                    "key": "idx_enable_status_date_value",
                    "key_length": "5",
                    "used_key_parts": [
                        "enable",
                        "status"
                    ],
                    "ref": [
                        "const",
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "attached_condition": "bank_payment.`value` > 0 and bank_payment.`enable` = 'Y' and cast(bank_payment.date_create as date) = '2026-03-21'",
                    "using_index": true
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `bankcheck`: `value, date_create`
  - `idx_bp_acc_month_time`: `account_code, bank_time, code`
  - `idx_bp_bank_acct_status_enable`: `bankname, account_code, status, enable`
  - `idx_bp_channel`: `channel`
  - `idx_bp_date_create`: `date_create`
  - `idx_bp_date_status_enable`: `date_create, status, enable`
  - `idx_bp_member_status_enable_date`: `member_topup, status, enable, date_create`
  - `idx_bp_status_enable_date`: `status, enable, date_create`
  - `idx_enable_status_date_value`: `enable, status, date_create, value`
  - `PRIMARY`: `code`
  - `txid`: `txid`
  - `tx_hash`: `tx_hash`

### Repeated SQL #3
- repeat count: `9`, duplicate count: `8`, total ms: `9`, max ms: `2`
- tables: `bank_payment`
- sql:
```sql
select count(*) as aggregate from `bank_payment` where `bank_payment`.`value` > ? and `bank_payment`.`enable` = ? and `bank_payment`.`status` = ? and date(`date_create`) < ?
```
- bindings: `[0,"Y",0,"2026-03-21"]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "bank_payment",
                    "access_type": "ref",
                    "possible_keys": [
                        "bankcheck",
                        "idx_bp_status_enable_date",
                        "idx_enable_status_date_value"
                    ],
                    "key": "idx_enable_status_date_value",
                    "key_length": "5",
                    "used_key_parts": [
                        "enable",
                        "status"
                    ],
                    "ref": [
                        "const",
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "attached_condition": "bank_payment.`value` > 0 and bank_payment.`enable` = 'Y' and cast(bank_payment.date_create as date) < '2026-03-21'",
                    "using_index": true
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `bankcheck`: `value, date_create`
  - `idx_bp_acc_month_time`: `account_code, bank_time, code`
  - `idx_bp_bank_acct_status_enable`: `bankname, account_code, status, enable`
  - `idx_bp_channel`: `channel`
  - `idx_bp_date_create`: `date_create`
  - `idx_bp_date_status_enable`: `date_create, status, enable`
  - `idx_bp_member_status_enable_date`: `member_topup, status, enable, date_create`
  - `idx_bp_status_enable_date`: `status, enable, date_create`
  - `idx_enable_status_date_value`: `enable, status, date_create, value`
  - `PRIMARY`: `code`
  - `txid`: `txid`
  - `tx_hash`: `tx_hash`

## Route: `admin.promotion.loadpromotion`
- Menu key(s): `-`
- SQL count: `3`, unique: `2`, duplicate: `1`, total ms: `15`

### Repeated SQL #1
- repeat count: `2`, duplicate count: `1`, total ms: `11`, max ms: `8`
- tables: `employees`
- sql:
```sql
select * from `employees` where `code` = ? and `code` <> ? limit 1
```
- bindings: `[1,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "employees",
                    "access_type": "const",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

## Route: `admin.promotion.index`
- Menu key(s): `top.promotion`
- SQL count: `4`, unique: `3`, duplicate: `1`, total ms: `15`

### Repeated SQL #1
- repeat count: `2`, duplicate count: `1`, total ms: `11`, max ms: `9`
- tables: `employees`
- sql:
```sql
select * from `employees` where `code` = ? and `code` <> ? limit 1
```
- bindings: `[1,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "employees",
                    "access_type": "const",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

## Route: `admin.check_case.index`
- Menu key(s): `check_case`
- SQL count: `5`, unique: `4`, duplicate: `1`, total ms: `12`

### Repeated SQL #1
- repeat count: `2`, duplicate count: `1`, total ms: `8`, max ms: `5`
- tables: `employees`
- sql:
```sql
select * from `employees` where `code` = ? and `code` <> ? limit 1
```
- bindings: `[1,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "employees",
                    "access_type": "const",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

## Route: `admin.game_user.index`
- Menu key(s): `wallet.game_user`
- SQL count: `4`, unique: `3`, duplicate: `1`, total ms: `12`

### Repeated SQL #1
- repeat count: `2`, duplicate count: `1`, total ms: `9`, max ms: `6`
- tables: `employees`
- sql:
```sql
select * from `employees` where `code` = ? and `code` <> ? limit 1
```
- bindings: `[1,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "employees",
                    "access_type": "const",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

## Route: `admin.withdraw_seamless.index`
- Menu key(s): `withdraw_seamless`
- SQL count: `8`, unique: `5`, duplicate: `3`, total ms: `11`

### Repeated SQL #1
- repeat count: `2`, duplicate count: `1`, total ms: `5`, max ms: `3`
- tables: `employees`
- sql:
```sql
select * from `employees` where `code` = ? and `code` <> ? limit 1
```
- bindings: `[1,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "employees",
                    "access_type": "const",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

### Repeated SQL #2
- repeat count: `3`, duplicate count: `2`, total ms: `2`, max ms: `1`
- tables: `withdraws_seamless`
- sql:
```sql
select sum(`amount`) as aggregate from `withdraws_seamless` where `enable` = ? and `status` = ? and `status` = ? and `code` <> ?
```
- bindings: `["Y",0,0,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "withdraws_seamless",
                    "access_type": "ref",
                    "possible_keys": [
                        "PRIMARY",
                        "idx_wds_status_enable_approve",
                        "idx_wds_status_enable_create",
                        "idx_enable_status_code_date"
                    ],
                    "key": "idx_wds_status_enable_approve",
                    "key_length": "5",
                    "used_key_parts": [
                        "status",
                        "enable"
                    ],
                    "ref": [
                        "const",
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "index_condition": "withdraws_seamless.`enable` = 'Y' and withdraws_seamless.`code` <> 0"
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `idx_enable_status_code_date`: `enable, status, code, date_create`
  - `idx_wds_status_enable_approve`: `status, enable, date_approve`
  - `idx_wds_status_enable_create`: `status, enable, date_create`
  - `PRIMARY`: `code`

## Route: `admin.bank_account_in.index`
- Menu key(s): `ats`, `ats.bank_account_in`
- SQL count: `4`, unique: `3`, duplicate: `1`, total ms: `10`

### Repeated SQL #1
- repeat count: `2`, duplicate count: `1`, total ms: `8`, max ms: `5`
- tables: `employees`
- sql:
```sql
select * from `employees` where `code` = ? and `code` <> ? limit 1
```
- bindings: `[1,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "employees",
                    "access_type": "const",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

## Route: `admin.bank_account_out.index`
- Menu key(s): `ats.bank_account_out`
- SQL count: `4`, unique: `3`, duplicate: `1`, total ms: `10`

### Repeated SQL #1
- repeat count: `2`, duplicate count: `1`, total ms: `8`, max ms: `6`
- tables: `employees`
- sql:
```sql
select * from `employees` where `code` = ? and `code` <> ? limit 1
```
- bindings: `[1,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "employees",
                    "access_type": "const",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

## Route: `admin.bank_in.index`
- Menu key(s): `bank_in`
- SQL count: `8`, unique: `7`, duplicate: `1`, total ms: `9`

### Repeated SQL #1
- repeat count: `2`, duplicate count: `1`, total ms: `4`, max ms: `2`
- tables: `employees`
- sql:
```sql
select * from `employees` where `code` = ? and `code` <> ? limit 1
```
- bindings: `[1,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "employees",
                    "access_type": "const",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

## Route: `admin.rp_alllog.index`
- Menu key(s): `mon`, `mon.rp_alllog`
- SQL count: `6`, unique: `4`, duplicate: `2`, total ms: `9`

### Repeated SQL #1
- repeat count: `2`, duplicate count: `1`, total ms: `4`, max ms: `2`
- tables: `employees`
- sql:
```sql
select * from `employees` where `code` = ? and `code` <> ? limit 1
```
- bindings: `[1,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "employees",
                    "access_type": "const",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

### Repeated SQL #2
- repeat count: `2`, duplicate count: `1`, total ms: `3`, max ms: `2`
- tables: `games`
- sql:
```sql
select * from `games` where `enable` = ? and `games`.`code` > ?
```
- bindings: `["Y",0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "games",
                    "access_type": "range",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "attached_condition": "games.`enable` = 'Y' and games.`code` > 0"
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

## Route: `admin.member.index`
- Menu key(s): `wallet`, `wallet.member`
- SQL count: `4`, unique: `3`, duplicate: `1`, total ms: `7`

### Repeated SQL #1
- repeat count: `2`, duplicate count: `1`, total ms: `5`, max ms: `3`
- tables: `employees`
- sql:
```sql
select * from `employees` where `code` = ? and `code` <> ? limit 1
```
- bindings: `[1,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "employees",
                    "access_type": "const",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

## Route: `admin.member.loadbankaccount`
- Menu key(s): `-`
- SQL count: `3`, unique: `1`, duplicate: `2`, total ms: `7`

### Repeated SQL #1
- repeat count: `3`, duplicate count: `2`, total ms: `7`, max ms: `3`
- tables: `employees`
- sql:
```sql
select * from `employees` where `code` = ? and `code` <> ? limit 1
```
- bindings: `[1,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "employees",
                    "access_type": "const",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`
