# Admin Query Audit Runtime Report

- Generated at: `2026-03-21 11:29:06`
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
| `admin.bank_in_old.index` | `bank_in_old` | 4 | 3 | 1 | 6 |
| `admin.bank_account_in.loadbank` | `-` | 2 | 2 | 0 | 5 |
| `admin.withdraw_seamless.loadbank` | `-` | 2 | 2 | 0 | 4 |
| `admin.member.loadrefer` | `-` | 2 | 2 | 0 | 3 |
| `admin.game_user.loadpromotion` | `-` | 2 | 2 | 0 | 3 |
| `admin.member.loadbank` | `-` | 1 | 1 | 0 | 2 |
| `admin.bank_account_out.loadbank` | `-` | 1 | 1 | 0 | 2 |

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

### Repeated SQL #4
- repeat count: `9`, duplicate count: `8`, total ms: `8`, max ms: `2`
- tables: `bank_payment`
- sql:
```sql
select count(*) as aggregate from `bank_payment` where `bank_payment`.`value` < ? and `bank_payment`.`enable` = ? and `bank_payment`.`status` = ? and `autocheck` = ? and `date_create` between ? and ?
```
- bindings: `[0,"Y",0,"N","2026-03-21 00:00:00","2026-03-21 23:59:59"]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "bank_payment",
                    "access_type": "range",
                    "possible_keys": [
                        "bankcheck",
                        "idx_bp_date_create",
                        "idx_bp_date_status_enable",
                        "idx_bp_status_enable_date",
                        "idx_enable_status_date_value"
                    ],
                    "key": "idx_bp_date_create",
                    "key_length": "5",
                    "used_key_parts": [
                        "date_create"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "index_condition": "bank_payment.date_create between '2026-03-21 00:00:00.000000' and '2026-03-21 23:59:59.000000'",
                    "attached_condition": "bank_payment.`status` = 0 and bank_payment.`value` < 0 and bank_payment.`enable` = 'Y' and bank_payment.autocheck = 'N'"
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

### Repeated SQL #5
- repeat count: `9`, duplicate count: `8`, total ms: `8`, max ms: `2`
- tables: `withdraws_seamless`
- sql:
```sql
select count(*) as aggregate from `withdraws_seamless` where `enable` = ? and `status` = ? and `code` <> ?
```
- bindings: `["Y",0,0]`
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
                    "attached_condition": "withdraws_seamless.`enable` = 'Y' and withdraws_seamless.`code` <> 0",
                    "using_index": true
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

### Repeated SQL #6
- repeat count: `9`, duplicate count: `8`, total ms: `7`, max ms: `2`
- tables: `withdraws_seamless_free`
- sql:
```sql
select count(*) as aggregate from `withdraws_seamless_free` where `enable` = ? and `status` = ? and `code` <> ?
```
- bindings: `["Y",0,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "withdraws_seamless_free",
                    "access_type": "ALL",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "attached_condition": "withdraws_seamless_free.`status` = 0 and withdraws_seamless_free.`enable` = 'Y' and withdraws_seamless_free.`code` <> 0"
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

### Repeated SQL #7
- repeat count: `9`, duplicate count: `8`, total ms: `7`, max ms: `2`
- tables: `payments_waiting`
- sql:
```sql
select count(*) as aggregate from `payments_waiting` where date(`date_create`) > ? and `enable` = ? and `confirm` = ? and `code` <> ?
```
- bindings: `["2021-04-05","Y","X",0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "payments_waiting",
                    "access_type": "ref",
                    "possible_keys": [
                        "PRIMARY",
                        "idx_enable_confirm_date"
                    ],
                    "key": "idx_enable_confirm_date",
                    "key_length": "3",
                    "used_key_parts": [
                        "enable",
                        "confirm"
                    ],
                    "ref": [
                        "const",
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "attached_condition": "cast(payments_waiting.date_create as date) > '2021-04-05' and payments_waiting.`enable` = 'Y' and payments_waiting.confirm = 'X' and payments_waiting.`code` <> 0",
                    "using_index": true
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `idx_enable_confirm_date`: `enable, confirm, date_create`
  - `member_code`: `member_code`
  - `PRIMARY`: `code`

### Repeated SQL #8
- repeat count: `9`, duplicate count: `8`, total ms: `7`, max ms: `2`
- tables: `members`
- sql:
```sql
select count(*) as aggregate from `members` where `members`.`enable` = ? and `members`.`confirm` = ? and `members`.`code` > ?
```
- bindings: `["Y","N",0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "members",
                    "access_type": "range",
                    "possible_keys": [
                        "PRIMARY",
                        "member_confirm",
                        "members_confirm_date_create_index",
                        "member_all_index",
                        "idx_enable_confirm_code_date"
                    ],
                    "key": "idx_enable_confirm_code_date",
                    "key_length": "6",
                    "used_key_parts": [
                        "enable",
                        "confirm",
                        "code"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "attached_condition": "members.`enable` = 'Y' and members.confirm = 'N' and members.`code` > 0",
                    "using_index": true
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `idx_enable_confirm_code_date`: `enable, confirm, code, date_create`
  - `idx_members_date_regis`: `date_regis`
  - `idx_members_date_regis_campaign`: `date_regis, campaign_id`
  - `idx_members_date_regis_upline`: `date_regis, upline_code`
  - `members_campaign_id_foreign`: `campaign_id`
  - `members_confirm_date_create_index`: `confirm, date_create`
  - `members_team_id_foreign`: `team_id`
  - `members_upline_code_user_name_date_create_index`: `upline_code, user_name, date_create`
  - `members_user_name_index`: `user_name`
  - `member_all_index`: `code, firstname, lastname, user_name, user_pass, acc_no, tel, lineid, wallet_id, date_create`
  - `member_confirm`: `confirm, date_create`
  - `member_index`: `upline_code, date_create, user_name`
  - `PRIMARY`: `code`

## Route: `admin.promotion.loadpromotion`
- Menu key(s): `-`
- SQL count: `3`, unique: `2`, duplicate: `1`, total ms: `15`
- No repeated SQL above threshold.

## Route: `admin.promotion.index`
- Menu key(s): `top.promotion`
- SQL count: `4`, unique: `3`, duplicate: `1`, total ms: `15`
- No repeated SQL above threshold.

## Route: `admin.check_case.index`
- Menu key(s): `check_case`
- SQL count: `5`, unique: `4`, duplicate: `1`, total ms: `12`
- No repeated SQL above threshold.

## Route: `admin.game_user.index`
- Menu key(s): `wallet.game_user`
- SQL count: `4`, unique: `3`, duplicate: `1`, total ms: `12`
- No repeated SQL above threshold.

## Route: `admin.withdraw_seamless.index`
- Menu key(s): `withdraw_seamless`
- SQL count: `8`, unique: `5`, duplicate: `3`, total ms: `11`

### Repeated SQL #1
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
- No repeated SQL above threshold.

## Route: `admin.bank_account_out.index`
- Menu key(s): `ats.bank_account_out`
- SQL count: `4`, unique: `3`, duplicate: `1`, total ms: `10`
- No repeated SQL above threshold.

## Route: `admin.bank_in.index`
- Menu key(s): `bank_in`
- SQL count: `8`, unique: `7`, duplicate: `1`, total ms: `9`
- No repeated SQL above threshold.

## Route: `admin.rp_alllog.index`
- Menu key(s): `mon`, `mon.rp_alllog`
- SQL count: `6`, unique: `4`, duplicate: `2`, total ms: `9`
- No repeated SQL above threshold.

## Route: `admin.member.index`
- Menu key(s): `wallet`, `wallet.member`
- SQL count: `4`, unique: `3`, duplicate: `1`, total ms: `7`
- No repeated SQL above threshold.

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

## Route: `admin.bank_in_old.index`
- Menu key(s): `bank_in_old`
- SQL count: `4`, unique: `3`, duplicate: `1`, total ms: `6`
- No repeated SQL above threshold.

## Route: `admin.bank_account_in.loadbank`
- Menu key(s): `-`
- SQL count: `2`, unique: `2`, duplicate: `0`, total ms: `5`
- No repeated SQL above threshold.

## Route: `admin.withdraw_seamless.loadbank`
- Menu key(s): `-`
- SQL count: `2`, unique: `2`, duplicate: `0`, total ms: `4`
- No repeated SQL above threshold.

## Route: `admin.member.loadrefer`
- Menu key(s): `-`
- SQL count: `2`, unique: `2`, duplicate: `0`, total ms: `3`
- No repeated SQL above threshold.

## Route: `admin.game_user.loadpromotion`
- Menu key(s): `-`
- SQL count: `2`, unique: `2`, duplicate: `0`, total ms: `3`
- No repeated SQL above threshold.

## Route: `admin.member.loadbank`
- Menu key(s): `-`
- SQL count: `1`, unique: `1`, duplicate: `0`, total ms: `2`
- No repeated SQL above threshold.

## Route: `admin.bank_account_out.loadbank`
- Menu key(s): `-`
- SQL count: `1`, unique: `1`, duplicate: `0`, total ms: `2`
- No repeated SQL above threshold.
