> สถานะ: ACTIVE
> วันที่: 2026-03-27
> โดเมน/เรื่อง: Lotto / Draw Lifecycle Hardening
> แทนแผนเก่า: docs/internal/05_ARCHIVE/lotto-system-draw-menu-update-plan-base.md

You are implementing Lotto Draw lifecycle hardening. Follow ALL instructions strictly. Do NOT change public routes, status names, or existing behavior outside this scope.

---

# OBJECTIVE

Harden Lotto Draw lifecycle with:

* explicit source context
* strict state transition rules
* idempotent open/close
* reject-based settle idempotency
* audit-safe model updates
* no schedule field mutation

---

# STATUS MODEL (DO NOT CHANGE)

draft → open → closed → resulted

---

# DATA MODEL CHANGES (ADDITIVE ONLY)

Add columns to `lotto_draws`:

* opened_at (nullable datetime)
* closed_at (nullable datetime)
* open_mode (nullable string)
* close_mode (nullable string)

Constraints:

* open_at / close_at = scheduled time (unchanged meaning)
* opened_at / closed_at = actual transition time
* result_at = actual result timestamp (existing)

Do NOT use DB enum. Use string and validate in app layer.

Update LottoDraw model:

* casts: datetime fields properly
* fillable/guarded updated

---

# SERVICE CONTRACT (MANDATORY)

All lifecycle methods MUST receive explicit context:

context = {
source: "scheduled" | "manual",   // REQUIRED
actor_id?: number,
actor_type?: string,
reason?: string
}

NO default/fallback for source anywhere.

Mapping:

* cron → scheduled
* admin action → manual

---

# SERVICE: openDraw(draw, context)

VALIDATIONS:

* context.source required
* draw.open_at MUST NOT be null → else reject
* allow only status IN (draft, closed)
* if status = resulted → reject

LOGIC:

IF status = open:

* return success (NO-OP)
* DO NOT modify opened_at / open_mode

IF now < open_at:

* REQUIRE permission: lotto_draws.force_open
* set open_mode = "manual"

IF now >= open_at:

* set open_mode = context.source

ON TRANSITION:

* status = open
* opened_at = now() (server time only)

RULES:

* DO NOT modify open_at
* DO NOT accept client time

---

# SERVICE: closeDraw(draw, context)

VALIDATIONS:

* context.source required
* allow only status = open

IF status = closed:

* return success (NO-OP)
* DO NOT modify closed_at / close_mode

TIME RULES:

IF now < close_at:
IF context.source = "manual":
allow
close_mode = "manual"
ELSE:
reject (scheduled cannot close early)

IF now >= close_at:
close_mode = context.source

ON TRANSITION:

* status = closed
* closed_at = now() (server time only)

---

# SERVICE: settle(draw, payload)

VALIDATIONS:

* allow only status = closed
* IF status = resulted → reject immediately

RULES:

* DO NOT overwrite existing result
* DO NOT rewrite result_at

INPUT:

* first_prize: string length 5 or 6
* last_2_digits: string length 2

PROCESS:

* normalize and store
* derive:

    * top_3
    * top_2
    * bottom_2

ON SUCCESS:

* status = resulted
* result_at = now()

---

# IDEMPOTENCY LOCK

* openDraw(): status=open → NO-OP success
* closeDraw(): status=closed → NO-OP success
* settle(): status=resulted → REJECT (NOT no-op)

---

# UPDATE RULES (Controller)

Implement explicit allowlist per status:

## draft

* allow full update (market_id, draw_date, open_at, close_at, etc.)

## open

ALLOW ONLY:

* close_at
* remark (if exists)
* display_name (if exists)

REJECT ALL OTHER FIELDS

## closed

ALLOW ONLY:

* remark
* safe metadata

REJECT structural changes

## resulted

* REJECT ALL updates

---

# CONTROLLER REQUIREMENTS

* MUST pass context to service for open/close
* MUST NOT infer source
* MAY pre-check for UX but service is final authority

---

# CRON (lotto:sync-draw-statuses)

* MUST call service methods only (no direct status update)
* MUST pass source = "scheduled"
* MUST be idempotent (no repeated timestamp overwrite)

---

# TIME RULE

* ALL timestamps use server now()
* NEVER accept request time for opened_at / closed_at / result_at

---

# AUDIT RULE

* ALL transitions MUST use model->save() / update()
* DO NOT use query()->update(...) for state change
* ensure observers/logging still trigger

---

# ACCEPTANCE CRITERIA

1. openDraw:

    * reject if open_at is null
    * scheduled before open_at → reject
    * manual before open_at without permission → reject
    * manual before open_at with permission → success (open_mode=manual)
    * calling again when already open → no-op, no timestamp change

2. closeDraw:

    * scheduled before close_at → reject
    * manual before close_at → success (close_mode=manual)
    * scheduled after close_at → success (close_mode=scheduled)
    * calling again when already closed → no-op

3. settle:

    * closed → success
    * resulted → reject and MUST NOT change result_number/result_at

4. cron:

    * repeated runs DO NOT change opened_at/closed_at when no state change

5. update:

    * open status only allows allowlist fields
    * resulted rejects all updates

6. audit:

    * logs show correct old/new status for open/close/settle/update
    * no missing logs due to direct query update

---

# FINAL CONSTRAINTS

* DO NOT change routes
* DO NOT change status names
* DO NOT revert result input format (keep first_prize + last_2_digits)
* DO NOT mutate open_at / close_at during transitions

Implement exactly as specified.
