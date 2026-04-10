# Admin Query Audit Runtime Report

- Generated at: `2026-04-10 18:39:29`
- Source log: `storage/logs/slow-requests.log`
- Database: `1168lot_wallet`
- Filters: `only-admin=1`, `since=`

## Top Routes

| Route | Menu Key(s) | SQL Count | Unique SQL | Duplicate SQL | SQL ms |
|---|---|---:|---:|---:|---:|
| `admin.dashboard.activity` | `-` | 338 | 7 | 331 | 1188 |
| `admin.dashboard.alerts` | `-` | 490 | 7 | 483 | 941 |
| `admin.dashboard.loadlogin` | `-` | 294 | 5 | 289 | 905 |
| `admin.dashboard.conversion` | `-` | 336 | 5 | 331 | 831 |
| `admin.dashboard.summary` | `-` | 151 | 3 | 148 | 470 |
| `admin.dashboard.loadbank` | `-` | 119 | 4 | 115 | 464 |
| `admin.dashboard.trends` | `-` | 144 | 3 | 141 | 384 |
| `admin.dashboard.funnel` | `-` | 144 | 3 | 141 | 378 |
| `admin.dashboard.loadsum` | `-` | 49 | 1 | 48 | 281 |

## Route: `admin.dashboard.activity`
- Menu key(s): `-`
- SQL count: `338`, unique: `7`, duplicate: `331`, total ms: `1188`

### Repeated SQL #1
- repeat count: `48`, duplicate count: `47`, total ms: `286`, max ms: `69`
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
- repeat count: `48`, duplicate count: `47`, total ms: `177`, max ms: `61`
- tables: `members`
- sql:
```sql
select * from `members` where `date_regis` >= ? and `date_regis` < ? and `members`.`code` > ? order by `date_regis` desc limit 10
```
- bindings: `["2026-04-10 00:00:00","2026-04-11 00:00:00",0]`
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
                        "member_all_index",
                        "idx_members_date_regis",
                        "idx_members_date_regis_campaign",
                        "idx_members_date_regis_upline"
                    ],
                    "key": "idx_members_date_regis",
                    "key_length": "7",
                    "used_key_parts": [
                        "date_regis",
                        "code"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "attached_condition": "members.date_regis >= '2026-04-10 00:00:00' and members.date_regis < '2026-04-11 00:00:00' and members.`code` > 0"
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
  - `members_referral_code_unique`: `referral_code`
  - `members_team_id_foreign`: `team_id`
  - `members_upline_code_user_name_date_create_index`: `upline_code, user_name, date_create`
  - `members_user_name_index`: `user_name`
  - `member_all_index`: `code, firstname, lastname, user_name, user_pass, acc_no, tel, lineid, wallet_id, date_create`
  - `member_confirm`: `confirm, date_create`
  - `member_index`: `upline_code, date_create, user_name`
  - `PRIMARY`: `code`

### Repeated SQL #3
- repeat count: `48`, duplicate count: `47`, total ms: `172`, max ms: `111`
- tables: `bank_payment`
- sql:
```sql
select * from `bank_payment` where `bank_payment`.`value` > ? and `bank_payment`.`enable` = ? and `status` in (?, ?) and `date_create` >= ? and `date_create` < ? and `channel` = ? order by `date_create` desc limit 100
```
- bindings: `[0,"Y",0,1,"2026-04-10 00:00:00","2026-04-11 00:00:00","MANUAL"]`
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
                        "idx_bp_channel",
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
                    "attached_condition": "bank_payment.`channel` <=> 'MANUAL' and bank_payment.`value` > 0 and bank_payment.`enable` = 'Y' and bank_payment.`status` in (0,1) and bank_payment.date_create >= '2026-04-10 00:00:00' and bank_payment.date_create < '2026-04-11 00:00:00' and bank_payment.`channel` = 'MANUAL'"
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
- repeat count: `49`, duplicate count: `48`, total ms: `170`, max ms: `14`
- tables: `lotto_tickets`, `lotto_draws`, `lotto_markets`, `lotto_groups`, `members`
- sql:
```sql
select `t`.`id`, `t`.`member_id`, `t`.`status`, `t`.`total_net_amount`, `t`.`total_win_amount`, `t`.`created_at`, `m`.`name` as `market_name`, `g`.`name` as `group_name`, t.bet_type_summary as bet_type_summary, `member`.`user_name` as `member_username` from `lotto_tickets` as `t` inner join `lotto_draws` as `d` on `d`.`id` = `t`.`draw_id` inner join `lotto_markets` as `m` on `m`.`id` = `d`.`market_id` inner join `lotto_groups` as `g` on `g`.`id` = `m`.`group_id` left join `members` as `member` on `member`.`code` = `t`.`member_id` order by `t`.`created_at` desc, `t`.`id` desc limit 20
```
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "filesort": {
            "sort_key": "t.created_at desc, t.`id` desc",
            "temporary_table": {
                "nested_loop": [
                    {
                        "table": {
                            "table_name": "g",
                            "access_type": "ALL",
                            "possible_keys": [
                                "PRIMARY"
                            ],
                            "rows": 4,
                            "filtered": 100
                        }
                    },
                    {
                        "table": {
                            "table_name": "m",
                            "access_type": "ref",
                            "possible_keys": [
                                "PRIMARY",
                                "lotto_markets_group_id_foreign"
                            ],
                            "key": "lotto_markets_group_id_foreign",
                            "key_length": "8",
                            "used_key_parts": [
                                "group_id"
                            ],
                            "ref": [
                                "1168lot_wallet.g.id"
                            ],
                            "rows": 7,
                            "filtered": 100
                        }
                    },
                    {
                        "table": {
                            "table_name": "d",
                            "access_type": "ref",
                            "possible_keys": [
                                "PRIMARY",
                                "lotto_draws_market_id_draw_date_index"
                            ],
                            "key": "lotto_draws_market_id_draw_date_index",
                            "key_length": "8",
                            "used_key_parts": [
                                "market_id"
                            ],
                            "ref": [
                                "1168lot_wallet.m.id"
                            ],
                            "rows": 3,
                            "filtered": 100,
                            "using_index": true
                        }
                    },
                    {
                        "table": {
                            "table_name": "t",
                            "access_type": "ref",
                            "possible_keys": [
                                "lotto_tickets_draw_id_status_index"
                            ],
                            "key": "lotto_tickets_draw_id_status_index",
                            "key_length": "8",
                            "used_key_parts": [
                                "draw_id"
                            ],
                            "ref": [
                                "1168lot_wallet.d.id"
                            ],
                            "rows": 2,
                            "filtered": 100
                        }
                    },
                    {
                        "table": {
                            "table_name": "member",
                            "access_type": "eq_ref",
                            "possible_keys": [
                                "PRIMARY",
                                "member_all_index"
                            ],
                            "key": "PRIMARY",
                            "key_length": "4",
                            "used_key_parts": [
                                "code"
                            ],
                            "ref": [
                                "1168lot_wallet.t.member_id"
                            ],
                            "rows": 1,
                            "filtered": 100
                        }
                    }
                ]
            }
        }
    }
}
```
- indexes on primary table:
  - `idx_lotto_tickets_confirmed_id`: `bet_confirmed_at, id`
  - `idx_lotto_tickets_recent_feed`: `created_at, id`
  - `idx_lotto_tickets_status_confirmed_id`: `status, bet_confirmed_at, id`
  - `lotto_tickets_draw_id_status_index`: `draw_id, status`
  - `lotto_tickets_member_id_draw_id_index`: `member_id, draw_id`
  - `PRIMARY`: `id`

### Repeated SQL #5
- repeat count: `48`, duplicate count: `47`, total ms: `163`, max ms: `24`
- tables: `bank_payment`
- sql:
```sql
select * from `bank_payment` where `bank_payment`.`value` > ? and `bank_payment`.`enable` = ? and `status` in (?, ?) and `date_create` >= ? and `date_create` < ? order by `date_create` desc limit 100
```
- bindings: `[0,"Y",0,1,"2026-04-10 00:00:00","2026-04-11 00:00:00"]`
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
                    "attached_condition": "bank_payment.`value` > 0 and bank_payment.`enable` = 'Y' and bank_payment.`status` in (0,1) and bank_payment.date_create >= '2026-04-10 00:00:00' and bank_payment.date_create < '2026-04-11 00:00:00'"
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

### Repeated SQL #6
- repeat count: `48`, duplicate count: `47`, total ms: `135`, max ms: `30`
- tables: `withdraws`
- sql:
```sql
select * from `withdraws` where `enable` = ? and `status` in (?, ?) and `date_create` >= ? and `date_create` < ? and `code` <> ? order by COALESCE(date_approve, date_create, date_update) DESC limit 10
```
- bindings: `["Y",0,1,"2026-04-10 00:00:00","2026-04-11 00:00:00",0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "read_sorted_file": {
                    "filesort": {
                        "sort_key": "coalesce(withdraws.date_approve,withdraws.date_create,withdraws.date_update) desc",
                        "table": {
                            "table_name": "withdraws",
                            "access_type": "range",
                            "possible_keys": [
                                "PRIMARY",
                                "idx_wd_status_enable_approve",
                                "idx_wd_status_enable_create"
                            ],
                            "key": "idx_wd_status_enable_approve",
                            "key_length": "5",
                            "used_key_parts": [
                                "status",
                                "enable"
                            ],
                            "rows": 1,
                            "filtered": 100,
                            "index_condition": "withdraws.`enable` = 'Y' and withdraws.`status` in (0,1) and withdraws.`code` <> 0",
                            "attached_condition": "withdraws.date_create >= '2026-04-10 00:00:00' and withdraws.date_create < '2026-04-11 00:00:00'"
                        }
                    }
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `idx_wd_status_enable_approve`: `status, enable, date_approve`
  - `idx_wd_status_enable_create`: `status, enable, date_create`
  - `PRIMARY`: `code`
  - `unique`: `member_code, amount, date_create`

### Repeated SQL #7
- repeat count: `49`, duplicate count: `48`, total ms: `85`, max ms: `14`
- tables: `members_credit_log`
- sql:
```sql
select * from `members_credit_log` where `members_credit_log`.`enable` = ? and `kind` = ? and `date_create` >= ? and `date_create` < ? order by `date_create` desc limit 10
```
- bindings: `["Y","SETWALLET","2026-04-10 00:00:00","2026-04-11 00:00:00"]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "read_sorted_file": {
                    "filesort": {
                        "sort_key": "members_credit_log.date_create desc",
                        "table": {
                            "table_name": "members_credit_log",
                            "access_type": "ref",
                            "possible_keys": [
                                "members_credit_log_index",
                                "idx_mcl_kind_credit_type_date"
                            ],
                            "key": "idx_mcl_kind_credit_type_date",
                            "key_length": "42",
                            "used_key_parts": [
                                "kind"
                            ],
                            "ref": [
                                "const"
                            ],
                            "rows": 35,
                            "filtered": 100,
                            "index_condition": "members_credit_log.kind = 'SETWALLET' and members_credit_log.date_create >= '2026-04-10 00:00:00' and members_credit_log.date_create < '2026-04-11 00:00:00'",
                            "attached_condition": "members_credit_log.kind <=> 'SETWALLET' and members_credit_log.`enable` = 'Y'"
                        }
                    }
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `idx_mcl_kind_credit_type_date`: `kind, credit_type, date_create`
  - `members_credit_log_index`: `kind, member_code, date_create`
  - `PRIMARY`: `code`

## Route: `admin.dashboard.alerts`
- Menu key(s): `-`
- SQL count: `490`, unique: `7`, duplicate: `483`, total ms: `941`

### Repeated SQL #1
- repeat count: `49`, duplicate count: `48`, total ms: `180`, max ms: `63`
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
- repeat count: `98`, duplicate count: `97`, total ms: `169`, max ms: `18`
- tables: `members_credit_free_log`
- sql:
```sql
select count(*) as aggregate from `members_credit_free_log` where `members_credit_free_log`.`enable` = ? and `kind` = ? and `credit_type` = ? and `date_create` >= ? and `date_create` < ?
```
- bindings: `["Y","SETWALLET","D","2026-04-10 00:00:00","2026-04-11 00:00:00"]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "members_credit_free_log",
                    "access_type": "ref",
                    "possible_keys": [
                        "members_credit_free_log_index"
                    ],
                    "key": "members_credit_free_log_index",
                    "key_length": "42",
                    "used_key_parts": [
                        "kind"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "index_condition": "members_credit_free_log.kind = 'SETWALLET' and members_credit_free_log.date_create >= '2026-04-10 00:00:00' and members_credit_free_log.date_create < '2026-04-11 00:00:00'",
                    "attached_condition": "members_credit_free_log.`enable` = 'Y' and members_credit_free_log.credit_type = 'D'"
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `members_credit_free_log_index`: `kind, member_code, date_create`
  - `PRIMARY`: `code`

### Repeated SQL #3
- repeat count: `49`, duplicate count: `48`, total ms: `144`, max ms: `30`
- tables: `members`, `bank_payment`
- sql:
```sql
select count(*) as aggregate from `members` inner join (select member_topup as member_key from `bank_payment` where `bank_payment`.`value` > ? and `bank_payment`.`enable` = ? and `status` = ? and `value` > ? and `member_topup` is not null and `member_topup` > ? group by `member_topup` having COUNT(*) >= 2) as `life_repeat` on `life_repeat`.`member_key` = `members`.`code` where `members`.`date_regis` >= ? and `members`.`date_regis` < ? and exists (select 1 from `bank_payment` as `bp_range` where `bp_range`.`member_topup` = `members`.`code` and `bp_range`.`enable` = ? and `bp_range`.`status` = ? and `bp_range`.`value` > ? and `bp_range`.`date_create` >= ? and `bp_range`.`date_create` < ?) and `members`.`code` > ?
```
- bindings: `[0,"Y",1,0,0,"2026-04-10 00:00:00","2026-04-11 00:00:00","Y",1,0,"2026-04-10 00:00:00","2026-04-11 00:00:00",0]`
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
                        "member_all_index",
                        "idx_members_date_regis",
                        "idx_members_date_regis_campaign",
                        "idx_members_date_regis_upline"
                    ],
                    "key": "idx_members_date_regis",
                    "key_length": "7",
                    "used_key_parts": [
                        "date_regis",
                        "code"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "attached_condition": "members.date_regis >= '2026-04-10 00:00:00' and members.date_regis < '2026-04-11 00:00:00' and members.`code` > 0",
                    "using_index": true
                }
            },
            {
                "table": {
                    "table_name": "<subquery3>",
                    "access_type": "eq_ref",
                    "possible_keys": [
                        "distinct_key"
                    ],
                    "key": "distinct_key",
                    "key_length": "4",
                    "used_key_parts": [
                        "member_topup"
                    ],
                    "ref": [
                        "func"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "attached_condition": "members.`code` = bp_range.member_topup",
                    "materialized": {
                        "unique": 1,
                        "query_block": {
                            "select_id": 3,
                            "nested_loop": [
                                {
                                    "table": {
                                        "table_name": "bp_range",
                                        "access_type": "range",
                                        "possible_keys": [
                                            "bankcheck",
                                            "idx_bp_date_create",
                                            "idx_bp_date_status_enable",
                                            "idx_bp_member_status_enable_date",
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
                                        "index_condition": "bp_range.date_create >= '2026-04-10 00:00:00' and bp_range.date_create < '2026-04-11 00:00:00'",
                                        "attached_condition": "bp_range.`status` = 1 and bp_range.`enable` = 'Y' and bp_range.`value` > 0"
                                    }
                                }
                            ]
                        }
                    }
                }
            },
            {
                "table": {
                    "table_name": "<derived2>",
                    "access_type": "ref",
                    "possible_keys": [
                        "key0"
                    ],
                    "key": "key0",
                    "key_length": "4",
                    "used_key_parts": [
                        "member_key"
                    ],
                    "ref": [
                        "1168lot_wallet.members.code"
                    ],
                    "rows": 2,
                    "filtered": 100,
                    "attached_condition": "life_repeat.member_key = members.`code`",
                    "materialized": {
                        "query_block": {
                            "select_id": 2,
                            "having_condition": "count(0) >= 2",
                            "filesort": {
                                "sort_key": "bank_payment.member_topup",
                                "temporary_table": {
                                    "nested_loop": [
                                        {
                                            "table": {
                                                "table_name": "bank_payment",
                                                "access_type": "ref",
                                                "possible_keys": [
                                                    "bankcheck",
                                                    "idx_bp_member_status_enable_date",
                                                    "idx_bp_status_enable_date",
                                                    "idx_enable_status_date_value"
                                                ],
                                                "key": "idx_bp_status_enable_date",
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
                                                "index_condition": "bank_payment.`enable` = 'Y'",
                                                "attached_condition": "bank_payment.`status` <=> 1 and bank_payment.`enable` <=> 'Y' and bank_payment.`value` > 0 and bank_payment.`value` > 0 and bank_payment.member_topup is not null and bank_payment.member_topup > 0"
                                            }
                                        }
                                    ]
                                }
                            }
                        }
                    }
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
  - `members_referral_code_unique`: `referral_code`
  - `members_team_id_foreign`: `team_id`
  - `members_upline_code_user_name_date_create_index`: `upline_code, user_name, date_create`
  - `members_user_name_index`: `user_name`
  - `member_all_index`: `code, firstname, lastname, user_name, user_pass, acc_no, tel, lineid, wallet_id, date_create`
  - `member_confirm`: `confirm, date_create`
  - `member_index`: `upline_code, date_create, user_name`
  - `PRIMARY`: `code`

### Repeated SQL #4
- repeat count: `98`, duplicate count: `97`, total ms: `143`, max ms: `17`
- tables: `members_credit_free_log`
- sql:
```sql
select sum(`amount`) as aggregate from `members_credit_free_log` where `members_credit_free_log`.`enable` = ? and `kind` = ? and `credit_type` = ? and `date_create` >= ? and `date_create` < ?
```
- bindings: `["Y","SETWALLET","D","2026-04-10 00:00:00","2026-04-11 00:00:00"]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "members_credit_free_log",
                    "access_type": "ref",
                    "possible_keys": [
                        "members_credit_free_log_index"
                    ],
                    "key": "members_credit_free_log_index",
                    "key_length": "42",
                    "used_key_parts": [
                        "kind"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "index_condition": "members_credit_free_log.kind = 'SETWALLET' and members_credit_free_log.date_create >= '2026-04-10 00:00:00' and members_credit_free_log.date_create < '2026-04-11 00:00:00'",
                    "attached_condition": "members_credit_free_log.`enable` = 'Y' and members_credit_free_log.credit_type = 'D'"
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `members_credit_free_log_index`: `kind, member_code, date_create`
  - `PRIMARY`: `code`

### Repeated SQL #5
- repeat count: `98`, duplicate count: `97`, total ms: `137`, max ms: `16`
- tables: `dashboard_summary_daily`
- sql:
```sql
select `summary_date`, `metric_version` from `dashboard_summary_daily` where `web_code` = ? and `summary_date` between ? and ?
```
- bindings: `["1168lot","2026-04-10","2026-04-10"]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "dashboard_summary_daily",
                    "access_type": "range",
                    "possible_keys": [
                        "uk_dashboard_summary_daily_date_web",
                        "idx_dashboard_summary_daily_web_date"
                    ],
                    "key": "uk_dashboard_summary_daily_date_web",
                    "key_length": "261",
                    "used_key_parts": [
                        "summary_date",
                        "web_code"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "index_condition": "dashboard_summary_daily.web_code = '1168lot' and dashboard_summary_daily.summary_date between '2026-04-10' and '2026-04-10'"
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `idx_dashboard_summary_daily_web_date`: `web_code, summary_date`
  - `PRIMARY`: `id`
  - `uk_dashboard_summary_daily_date_web`: `summary_date, web_code`

### Repeated SQL #6
- repeat count: `49`, duplicate count: `48`, total ms: `118`, max ms: `43`
- tables: `withdraws`
- sql:
```sql
select count(*) as aggregate from `withdraws` where `enable` = ? and `status` = ? and `date_create` < ? and `code` <> ?
```
- bindings: `["Y",0,"2026-04-10 16:57:10",0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "withdraws",
                    "access_type": "range",
                    "possible_keys": [
                        "PRIMARY",
                        "idx_wd_status_enable_approve",
                        "idx_wd_status_enable_create"
                    ],
                    "key": "idx_wd_status_enable_create",
                    "key_length": "10",
                    "used_key_parts": [
                        "status",
                        "enable",
                        "date_create"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "attached_condition": "withdraws.`status` = 0 and withdraws.`enable` = 'Y' and withdraws.date_create < '2026-04-10 16:57:10' and withdraws.`code` <> 0",
                    "using_index": true
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `idx_wd_status_enable_approve`: `status, enable, date_approve`
  - `idx_wd_status_enable_create`: `status, enable, date_create`
  - `PRIMARY`: `code`
  - `unique`: `member_code, amount, date_create`

### Repeated SQL #7
- repeat count: `49`, duplicate count: `48`, total ms: `50`, max ms: `6`
- tables: `bank_payment`
- sql:
```sql
select count(*) as aggregate from `bank_payment` where `bank_payment`.`status` = ? and `bank_payment`.`enable` = ? and `date_create` < ?
```
- bindings: `[0,"Y","2026-04-10 16:57:11"]`
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
                        "idx_bp_date_create",
                        "idx_bp_date_status_enable",
                        "idx_bp_status_enable_date",
                        "idx_enable_status_date_value"
                    ],
                    "key": "idx_bp_date_status_enable",
                    "key_length": "5",
                    "used_key_parts": [
                        "date_create"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "attached_condition": "bank_payment.`status` = 0 and bank_payment.`enable` = 'Y' and bank_payment.date_create < '2026-04-10 16:57:11'",
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

## Route: `admin.dashboard.loadlogin`
- Menu key(s): `-`
- SQL count: `294`, unique: `5`, duplicate: `289`, total ms: `905`

### Repeated SQL #1
- repeat count: `98`, duplicate count: `97`, total ms: `428`, max ms: `37`
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
- repeat count: `49`, duplicate count: `48`, total ms: `179`, max ms: `106`
- tables: `employees`
- sql:
```sql
select * from `employees` where `employees`.`code` in (?, ?) and `code` <> ?
```
- bindings: `[1,23,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "employees",
                    "access_type": "range",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "rows": 2,
                    "filtered": 100,
                    "attached_condition": "employees.`code` in (1,23) and employees.`code` <> 0"
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

### Repeated SQL #3
- repeat count: `49`, duplicate count: `48`, total ms: `125`, max ms: `14`
- tables: `members_log`
- sql:
```sql
select * from `members_log` where `mode` = ? and `members_log`.`code` > ? order by `code` desc limit 10
```
- bindings: `["LOGOUT",0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "members_log",
                    "access_type": "range",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "8",
                    "used_key_parts": [
                        "code"
                    ],
                    "rows": 32,
                    "filtered": 100,
                    "attached_condition": "members_log.`mode` = 'LOGOUT' and members_log.`code` > 0"
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

### Repeated SQL #4
- repeat count: `49`, duplicate count: `48`, total ms: `101`, max ms: `21`
- tables: `members_log`
- sql:
```sql
select * from `members_log` where `mode` = ? and `member_code` > ? and `members_log`.`code` > ? order by `code` desc limit 10
```
- bindings: `["LOGIN",0,0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "members_log",
                    "access_type": "range",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "8",
                    "used_key_parts": [
                        "code"
                    ],
                    "rows": 32,
                    "filtered": 100,
                    "attached_condition": "members_log.`mode` = 'LOGIN' and members_log.member_code > 0 and members_log.`code` > 0"
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

### Repeated SQL #5
- repeat count: `49`, duplicate count: `48`, total ms: `72`, max ms: `13`
- tables: `employees`
- sql:
```sql
select * from `employees` where `employees`.`code` in (?) and `code` <> ?
```
- bindings: `[24,0]`
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

## Route: `admin.dashboard.conversion`
- Menu key(s): `-`
- SQL count: `336`, unique: `5`, duplicate: `331`, total ms: `831`

### Repeated SQL #1
- repeat count: `48`, duplicate count: `47`, total ms: `229`, max ms: `35`
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
- repeat count: `48`, duplicate count: `47`, total ms: `194`, max ms: `73`
- tables: `members`, `bank_payment`
- sql:
```sql
select count(*) as aggregate from `members` inner join (select member_topup as member_key from `bank_payment` where `bank_payment`.`value` > ? and `bank_payment`.`enable` = ? and `status` = ? and `value` > ? and `member_topup` is not null and `member_topup` > ? group by `member_topup` having COUNT(*) >= 2) as `life_repeat` on `life_repeat`.`member_key` = `members`.`code` where `members`.`date_regis` >= ? and `members`.`date_regis` < ? and exists (select 1 from `bank_payment` as `bp_range` where `bp_range`.`member_topup` = `members`.`code` and `bp_range`.`enable` = ? and `bp_range`.`status` = ? and `bp_range`.`value` > ? and `bp_range`.`date_create` >= ? and `bp_range`.`date_create` < ?) and `members`.`code` > ?
```
- bindings: `[0,"Y",1,0,0,"2026-04-10 00:00:00","2026-04-11 00:00:00","Y",1,0,"2026-04-10 00:00:00","2026-04-11 00:00:00",0]`
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
                        "member_all_index",
                        "idx_members_date_regis",
                        "idx_members_date_regis_campaign",
                        "idx_members_date_regis_upline"
                    ],
                    "key": "idx_members_date_regis",
                    "key_length": "7",
                    "used_key_parts": [
                        "date_regis",
                        "code"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "attached_condition": "members.date_regis >= '2026-04-10 00:00:00' and members.date_regis < '2026-04-11 00:00:00' and members.`code` > 0",
                    "using_index": true
                }
            },
            {
                "table": {
                    "table_name": "<subquery3>",
                    "access_type": "eq_ref",
                    "possible_keys": [
                        "distinct_key"
                    ],
                    "key": "distinct_key",
                    "key_length": "4",
                    "used_key_parts": [
                        "member_topup"
                    ],
                    "ref": [
                        "func"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "attached_condition": "members.`code` = bp_range.member_topup",
                    "materialized": {
                        "unique": 1,
                        "query_block": {
                            "select_id": 3,
                            "nested_loop": [
                                {
                                    "table": {
                                        "table_name": "bp_range",
                                        "access_type": "range",
                                        "possible_keys": [
                                            "bankcheck",
                                            "idx_bp_date_create",
                                            "idx_bp_date_status_enable",
                                            "idx_bp_member_status_enable_date",
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
                                        "index_condition": "bp_range.date_create >= '2026-04-10 00:00:00' and bp_range.date_create < '2026-04-11 00:00:00'",
                                        "attached_condition": "bp_range.`status` = 1 and bp_range.`enable` = 'Y' and bp_range.`value` > 0"
                                    }
                                }
                            ]
                        }
                    }
                }
            },
            {
                "table": {
                    "table_name": "<derived2>",
                    "access_type": "ref",
                    "possible_keys": [
                        "key0"
                    ],
                    "key": "key0",
                    "key_length": "4",
                    "used_key_parts": [
                        "member_key"
                    ],
                    "ref": [
                        "1168lot_wallet.members.code"
                    ],
                    "rows": 2,
                    "filtered": 100,
                    "attached_condition": "life_repeat.member_key = members.`code`",
                    "materialized": {
                        "query_block": {
                            "select_id": 2,
                            "having_condition": "count(0) >= 2",
                            "filesort": {
                                "sort_key": "bank_payment.member_topup",
                                "temporary_table": {
                                    "nested_loop": [
                                        {
                                            "table": {
                                                "table_name": "bank_payment",
                                                "access_type": "ref",
                                                "possible_keys": [
                                                    "bankcheck",
                                                    "idx_bp_member_status_enable_date",
                                                    "idx_bp_status_enable_date",
                                                    "idx_enable_status_date_value"
                                                ],
                                                "key": "idx_bp_status_enable_date",
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
                                                "index_condition": "bank_payment.`enable` = 'Y'",
                                                "attached_condition": "bank_payment.`status` <=> 1 and bank_payment.`enable` <=> 'Y' and bank_payment.`value` > 0 and bank_payment.`value` > 0 and bank_payment.member_topup is not null and bank_payment.member_topup > 0"
                                            }
                                        }
                                    ]
                                }
                            }
                        }
                    }
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
  - `members_referral_code_unique`: `referral_code`
  - `members_team_id_foreign`: `team_id`
  - `members_upline_code_user_name_date_create_index`: `upline_code, user_name, date_create`
  - `members_user_name_index`: `user_name`
  - `member_all_index`: `code, firstname, lastname, user_name, user_pass, acc_no, tel, lineid, wallet_id, date_create`
  - `member_confirm`: `confirm, date_create`
  - `member_index`: `upline_code, date_create, user_name`
  - `PRIMARY`: `code`

### Repeated SQL #3
- repeat count: `96`, duplicate count: `95`, total ms: `157`, max ms: `37`
- tables: `members_credit_free_log`
- sql:
```sql
select count(*) as aggregate from `members_credit_free_log` where `members_credit_free_log`.`enable` = ? and `kind` = ? and `credit_type` = ? and `date_create` >= ? and `date_create` < ?
```
- bindings: `["Y","SETWALLET","D","2026-04-10 00:00:00","2026-04-11 00:00:00"]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "members_credit_free_log",
                    "access_type": "ref",
                    "possible_keys": [
                        "members_credit_free_log_index"
                    ],
                    "key": "members_credit_free_log_index",
                    "key_length": "42",
                    "used_key_parts": [
                        "kind"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "index_condition": "members_credit_free_log.kind = 'SETWALLET' and members_credit_free_log.date_create >= '2026-04-10 00:00:00' and members_credit_free_log.date_create < '2026-04-11 00:00:00'",
                    "attached_condition": "members_credit_free_log.`enable` = 'Y' and members_credit_free_log.credit_type = 'D'"
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `members_credit_free_log_index`: `kind, member_code, date_create`
  - `PRIMARY`: `code`

### Repeated SQL #4
- repeat count: `48`, duplicate count: `47`, total ms: `129`, max ms: `16`
- tables: `dashboard_summary_daily`
- sql:
```sql
select `summary_date`, `metric_version` from `dashboard_summary_daily` where `web_code` = ? and `summary_date` between ? and ?
```
- bindings: `["1168lot","2026-04-10","2026-04-10"]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "dashboard_summary_daily",
                    "access_type": "range",
                    "possible_keys": [
                        "uk_dashboard_summary_daily_date_web",
                        "idx_dashboard_summary_daily_web_date"
                    ],
                    "key": "uk_dashboard_summary_daily_date_web",
                    "key_length": "261",
                    "used_key_parts": [
                        "summary_date",
                        "web_code"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "index_condition": "dashboard_summary_daily.web_code = '1168lot' and dashboard_summary_daily.summary_date between '2026-04-10' and '2026-04-10'"
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `idx_dashboard_summary_daily_web_date`: `web_code, summary_date`
  - `PRIMARY`: `id`
  - `uk_dashboard_summary_daily_date_web`: `summary_date, web_code`

### Repeated SQL #5
- repeat count: `96`, duplicate count: `95`, total ms: `123`, max ms: `12`
- tables: `members_credit_free_log`
- sql:
```sql
select sum(`amount`) as aggregate from `members_credit_free_log` where `members_credit_free_log`.`enable` = ? and `kind` = ? and `credit_type` = ? and `date_create` >= ? and `date_create` < ?
```
- bindings: `["Y","SETWALLET","D","2026-04-10 00:00:00","2026-04-11 00:00:00"]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "members_credit_free_log",
                    "access_type": "ref",
                    "possible_keys": [
                        "members_credit_free_log_index"
                    ],
                    "key": "members_credit_free_log_index",
                    "key_length": "42",
                    "used_key_parts": [
                        "kind"
                    ],
                    "ref": [
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "index_condition": "members_credit_free_log.kind = 'SETWALLET' and members_credit_free_log.date_create >= '2026-04-10 00:00:00' and members_credit_free_log.date_create < '2026-04-11 00:00:00'",
                    "attached_condition": "members_credit_free_log.`enable` = 'Y' and members_credit_free_log.credit_type = 'D'"
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `members_credit_free_log_index`: `kind, member_code, date_create`
  - `PRIMARY`: `code`

## Route: `admin.dashboard.summary`
- Menu key(s): `-`
- SQL count: `151`, unique: `3`, duplicate: `148`, total ms: `470`

### Repeated SQL #1
- repeat count: `48`, duplicate count: `47`, total ms: `246`, max ms: `50`
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
- repeat count: `96`, duplicate count: `95`, total ms: `220`, max ms: `34`
- tables: `dashboard_summary_daily`
- sql:
```sql
select `summary_date`, `metric_version` from `dashboard_summary_daily` where `web_code` = ? and `summary_date` between ? and ?
```
- bindings: `["1168lot","2026-04-10","2026-04-10"]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "dashboard_summary_daily",
                    "access_type": "range",
                    "possible_keys": [
                        "uk_dashboard_summary_daily_date_web",
                        "idx_dashboard_summary_daily_web_date"
                    ],
                    "key": "uk_dashboard_summary_daily_date_web",
                    "key_length": "261",
                    "used_key_parts": [
                        "summary_date",
                        "web_code"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "index_condition": "dashboard_summary_daily.web_code = '1168lot' and dashboard_summary_daily.summary_date between '2026-04-10' and '2026-04-10'"
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `idx_dashboard_summary_daily_web_date`: `web_code, summary_date`
  - `PRIMARY`: `id`
  - `uk_dashboard_summary_daily_date_web`: `summary_date, web_code`

### Repeated SQL #3
- repeat count: `7`, duplicate count: `6`, total ms: `4`, max ms: `1`
- tables: `roles`
- sql:
```sql
select * from `roles` where `roles`.`code` = ? limit 1
```
- bindings: `[1]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "roles",
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

## Route: `admin.dashboard.loadbank`
- Menu key(s): `-`
- SQL count: `119`, unique: `4`, duplicate: `115`, total ms: `464`

### Repeated SQL #1
- repeat count: `98`, duplicate count: `97`, total ms: `433`, max ms: `69`
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
- repeat count: `7`, duplicate count: `6`, total ms: `17`, max ms: `12`
- tables: `banks`
- sql:
```sql
select * from `banks` where `banks`.`code` in (2, 18) and `banks`.`code` > ?
```
- bindings: `[0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "banks",
                    "access_type": "range",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "rows": 2,
                    "filtered": 100,
                    "attached_condition": "banks.`code` in (2,18) and banks.`code` > 0"
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `PRIMARY`: `code`

### Repeated SQL #3
- repeat count: `7`, duplicate count: `6`, total ms: `9`, max ms: `2`
- tables: `banks_account`, `banks`
- sql:
```sql
select * from `banks_account` where `banks_account`.`bank_type` = ? and `banks_account`.`enable` = ? and exists (select * from `banks` where `banks_account`.`banks` = `banks`.`code` and `banks`.`code` > ?)
```
- bindings: `[2,"Y",0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "banks_account",
                    "access_type": "ref",
                    "possible_keys": [
                        "banks",
                        "idx_bank_type_enable"
                    ],
                    "key": "idx_bank_type_enable",
                    "key_length": "6",
                    "used_key_parts": [
                        "bank_type",
                        "enable"
                    ],
                    "ref": [
                        "const",
                        "const"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "index_condition": "banks_account.`enable` = 'Y'"
                }
            },
            {
                "table": {
                    "table_name": "banks",
                    "access_type": "eq_ref",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "ref": [
                        "1168lot_wallet.banks_account.banks"
                    ],
                    "rows": 1,
                    "filtered": 97.05882263,
                    "attached_condition": "banks.`code` > 0 and banks_account.banks = banks.`code`",
                    "using_index": true
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `banks`: `banks`
  - `banks_account_date_end_time_end_index`: `date_end, time_end`
  - `banks_account_date_start_time_start_index`: `date_start, time_start`
  - `banks_account_end_at_index`: `end_at`
  - `banks_account_start_at_index`: `start_at`
  - `idx_bank_type_enable`: `bank_type, enable`
  - `PRIMARY`: `code`

### Repeated SQL #4
- repeat count: `7`, duplicate count: `6`, total ms: `6`, max ms: `1`
- tables: `banks_account`, `banks`
- sql:
```sql
select * from `banks_account` where `banks_account`.`bank_type` = ? and `banks_account`.`enable` = ? and `status_auto` = ? and exists (select * from `banks` where `banks_account`.`banks` = `banks`.`code` and `banks`.`code` > ?)
```
- bindings: `[1,"Y","Y",0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "banks_account",
                    "access_type": "ref",
                    "possible_keys": [
                        "banks",
                        "idx_bank_type_enable"
                    ],
                    "key": "idx_bank_type_enable",
                    "key_length": "6",
                    "used_key_parts": [
                        "bank_type",
                        "enable"
                    ],
                    "ref": [
                        "const",
                        "const"
                    ],
                    "rows": 2,
                    "filtered": 100,
                    "index_condition": "banks_account.`enable` = 'Y'",
                    "attached_condition": "banks_account.status_auto = 'Y'"
                }
            },
            {
                "table": {
                    "table_name": "banks",
                    "access_type": "eq_ref",
                    "possible_keys": [
                        "PRIMARY"
                    ],
                    "key": "PRIMARY",
                    "key_length": "4",
                    "used_key_parts": [
                        "code"
                    ],
                    "ref": [
                        "1168lot_wallet.banks_account.banks"
                    ],
                    "rows": 1,
                    "filtered": 97.05882263,
                    "attached_condition": "banks.`code` > 0 and banks_account.banks = banks.`code`",
                    "using_index": true
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `banks`: `banks`
  - `banks_account_date_end_time_end_index`: `date_end, time_end`
  - `banks_account_date_start_time_start_index`: `date_start, time_start`
  - `banks_account_end_at_index`: `end_at`
  - `banks_account_start_at_index`: `start_at`
  - `idx_bank_type_enable`: `bank_type, enable`
  - `PRIMARY`: `code`

## Route: `admin.dashboard.trends`
- Menu key(s): `-`
- SQL count: `144`, unique: `3`, duplicate: `141`, total ms: `384`

### Repeated SQL #1
- repeat count: `48`, duplicate count: `47`, total ms: `210`, max ms: `46`
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
- repeat count: `48`, duplicate count: `47`, total ms: `90`, max ms: `28`
- tables: `bank_payment`
- sql:
```sql
select HOUR(date_create) as h, SUM(value) as v from `bank_payment` where `bank_payment`.`value` > ? and `date_create` >= ? and `date_create` < ? and `enable` = ? and `status` not in (?, ?) group by `h`
```
- bindings: `[0,"2026-04-10 00:00:00","2026-04-11 00:00:00","Y",2,3]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "filesort": {
            "sort_key": "hour(bank_payment.date_create)",
            "temporary_table": {
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
                            "key": "idx_enable_status_date_value",
                            "key_length": "5",
                            "used_key_parts": [
                                "enable",
                                "status"
                            ],
                            "rows": 1,
                            "filtered": 100,
                            "attached_condition": "bank_payment.`value` > 0 and bank_payment.date_create >= '2026-04-10 00:00:00' and bank_payment.date_create < '2026-04-11 00:00:00' and bank_payment.`enable` = 'Y' and bank_payment.`status` not in (2,3)",
                            "using_index": true
                        }
                    }
                ]
            }
        }
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
- repeat count: `48`, duplicate count: `47`, total ms: `84`, max ms: `11`
- tables: `withdraws`
- sql:
```sql
select HOUR(date_approve) as h, SUM(amount) as v from `withdraws` where `enable` = ? and `status` = ? and `date_approve` >= ? and `date_approve` < ? and `code` <> ? group by `h`
```
- bindings: `["Y",1,"2026-04-10 00:00:00","2026-04-11 00:00:00",0]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "filesort": {
            "sort_key": "hour(withdraws.date_approve)",
            "temporary_table": {
                "nested_loop": [
                    {
                        "table": {
                            "table_name": "withdraws",
                            "access_type": "ref",
                            "possible_keys": [
                                "PRIMARY",
                                "idx_wd_status_enable_approve",
                                "idx_wd_status_enable_create"
                            ],
                            "key": "idx_wd_status_enable_create",
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
                            "index_condition": "withdraws.`enable` = 'Y' and withdraws.`code` <> 0",
                            "attached_condition": "withdraws.`status` <=> 1 and withdraws.`enable` <=> 'Y' and withdraws.date_approve >= '2026-04-10 00:00:00' and withdraws.date_approve < '2026-04-11 00:00:00'"
                        }
                    }
                ]
            }
        }
    }
}
```
- indexes on primary table:
  - `idx_wd_status_enable_approve`: `status, enable, date_approve`
  - `idx_wd_status_enable_create`: `status, enable, date_create`
  - `PRIMARY`: `code`
  - `unique`: `member_code, amount, date_create`

## Route: `admin.dashboard.funnel`
- Menu key(s): `-`
- SQL count: `144`, unique: `3`, duplicate: `141`, total ms: `378`

### Repeated SQL #1
- repeat count: `48`, duplicate count: `47`, total ms: `184`, max ms: `23`
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
- repeat count: `48`, duplicate count: `47`, total ms: `130`, max ms: `23`
- tables: `members`, `bank_payment`
- sql:
```sql
select count(*) as aggregate from `members` inner join (select member_topup as member_key from `bank_payment` where `bank_payment`.`value` > ? and `bank_payment`.`enable` = ? and `status` = ? and `value` > ? and `member_topup` is not null and `member_topup` > ? group by `member_topup` having COUNT(*) >= 2) as `life_repeat` on `life_repeat`.`member_key` = `members`.`code` where `members`.`date_regis` >= ? and `members`.`date_regis` < ? and exists (select 1 from `bank_payment` as `bp_range` where `bp_range`.`member_topup` = `members`.`code` and `bp_range`.`enable` = ? and `bp_range`.`status` = ? and `bp_range`.`value` > ? and `bp_range`.`date_create` >= ? and `bp_range`.`date_create` < ?) and `members`.`code` > ?
```
- bindings: `[0,"Y",1,0,0,"2026-04-10 00:00:00","2026-04-11 00:00:00","Y",1,0,"2026-04-10 00:00:00","2026-04-11 00:00:00",0]`
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
                        "member_all_index",
                        "idx_members_date_regis",
                        "idx_members_date_regis_campaign",
                        "idx_members_date_regis_upline"
                    ],
                    "key": "idx_members_date_regis",
                    "key_length": "7",
                    "used_key_parts": [
                        "date_regis",
                        "code"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "attached_condition": "members.date_regis >= '2026-04-10 00:00:00' and members.date_regis < '2026-04-11 00:00:00' and members.`code` > 0",
                    "using_index": true
                }
            },
            {
                "table": {
                    "table_name": "<subquery3>",
                    "access_type": "eq_ref",
                    "possible_keys": [
                        "distinct_key"
                    ],
                    "key": "distinct_key",
                    "key_length": "4",
                    "used_key_parts": [
                        "member_topup"
                    ],
                    "ref": [
                        "func"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "attached_condition": "members.`code` = bp_range.member_topup",
                    "materialized": {
                        "unique": 1,
                        "query_block": {
                            "select_id": 3,
                            "nested_loop": [
                                {
                                    "table": {
                                        "table_name": "bp_range",
                                        "access_type": "range",
                                        "possible_keys": [
                                            "bankcheck",
                                            "idx_bp_date_create",
                                            "idx_bp_date_status_enable",
                                            "idx_bp_member_status_enable_date",
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
                                        "index_condition": "bp_range.date_create >= '2026-04-10 00:00:00' and bp_range.date_create < '2026-04-11 00:00:00'",
                                        "attached_condition": "bp_range.`status` = 1 and bp_range.`enable` = 'Y' and bp_range.`value` > 0"
                                    }
                                }
                            ]
                        }
                    }
                }
            },
            {
                "table": {
                    "table_name": "<derived2>",
                    "access_type": "ref",
                    "possible_keys": [
                        "key0"
                    ],
                    "key": "key0",
                    "key_length": "4",
                    "used_key_parts": [
                        "member_key"
                    ],
                    "ref": [
                        "1168lot_wallet.members.code"
                    ],
                    "rows": 2,
                    "filtered": 100,
                    "attached_condition": "life_repeat.member_key = members.`code`",
                    "materialized": {
                        "query_block": {
                            "select_id": 2,
                            "having_condition": "count(0) >= 2",
                            "filesort": {
                                "sort_key": "bank_payment.member_topup",
                                "temporary_table": {
                                    "nested_loop": [
                                        {
                                            "table": {
                                                "table_name": "bank_payment",
                                                "access_type": "ref",
                                                "possible_keys": [
                                                    "bankcheck",
                                                    "idx_bp_member_status_enable_date",
                                                    "idx_bp_status_enable_date",
                                                    "idx_enable_status_date_value"
                                                ],
                                                "key": "idx_bp_status_enable_date",
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
                                                "index_condition": "bank_payment.`enable` = 'Y'",
                                                "attached_condition": "bank_payment.`status` <=> 1 and bank_payment.`enable` <=> 'Y' and bank_payment.`value` > 0 and bank_payment.`value` > 0 and bank_payment.member_topup is not null and bank_payment.member_topup > 0"
                                            }
                                        }
                                    ]
                                }
                            }
                        }
                    }
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
  - `members_referral_code_unique`: `referral_code`
  - `members_team_id_foreign`: `team_id`
  - `members_upline_code_user_name_date_create_index`: `upline_code, user_name, date_create`
  - `members_user_name_index`: `user_name`
  - `member_all_index`: `code, firstname, lastname, user_name, user_pass, acc_no, tel, lineid, wallet_id, date_create`
  - `member_confirm`: `confirm, date_create`
  - `member_index`: `upline_code, date_create, user_name`
  - `PRIMARY`: `code`

### Repeated SQL #3
- repeat count: `48`, duplicate count: `47`, total ms: `64`, max ms: `11`
- tables: `dashboard_summary_daily`
- sql:
```sql
select `summary_date`, `metric_version` from `dashboard_summary_daily` where `web_code` = ? and `summary_date` between ? and ?
```
- bindings: `["1168lot","2026-04-10","2026-04-10"]`
- explain (FORMAT=JSON):
```json
{
    "query_block": {
        "select_id": 1,
        "nested_loop": [
            {
                "table": {
                    "table_name": "dashboard_summary_daily",
                    "access_type": "range",
                    "possible_keys": [
                        "uk_dashboard_summary_daily_date_web",
                        "idx_dashboard_summary_daily_web_date"
                    ],
                    "key": "uk_dashboard_summary_daily_date_web",
                    "key_length": "261",
                    "used_key_parts": [
                        "summary_date",
                        "web_code"
                    ],
                    "rows": 1,
                    "filtered": 100,
                    "index_condition": "dashboard_summary_daily.web_code = '1168lot' and dashboard_summary_daily.summary_date between '2026-04-10' and '2026-04-10'"
                }
            }
        ]
    }
}
```
- indexes on primary table:
  - `idx_dashboard_summary_daily_web_date`: `web_code, summary_date`
  - `PRIMARY`: `id`
  - `uk_dashboard_summary_daily_date_web`: `summary_date, web_code`

## Route: `admin.dashboard.loadsum`
- Menu key(s): `-`
- SQL count: `49`, unique: `1`, duplicate: `48`, total ms: `281`

### Repeated SQL #1
- repeat count: `49`, duplicate count: `48`, total ms: `281`, max ms: `38`
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
