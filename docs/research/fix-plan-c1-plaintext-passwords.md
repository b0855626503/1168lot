# Fix Plan: C1 — Plaintext Password Storage

**Severity**: 🔴🔴 CRITICAL
**Found by**: Security Perspective
**Files**: `packages/Gametech/Wallet/src/Http/Controllers/LoginController.php:326,330`, `packages/Gametech/Member/src/Models/Member.php:239`

---

## Problem

`members.user_pass` field stores plaintext passwords (VARCHAR 20) alongside `members.password` which stores bcrypt hashes. This means:
- Any database compromise exposes ALL user passwords
- Database administrators can read any user's password
- Backups contain plaintext passwords
- Logs or error messages may accidentally expose `user_pass`

## Root Cause

Legacy authentication system used `user_pass` for direct comparison. Newer code uses `Hash::check()` against `password`. Both columns coexist during migration. The migration was never completed — only the `Hash::make()` write path was added; the `user_pass` write path was never removed.

## Fix: Complete the Migration

### Step 1: Audit all `user_pass` reads
```bash
rg "user_pass" packages/ app/ --type php
```
Expected hits:
- `LoginController.php:326-330` — writes both columns
- `Member.php:239` — model attribute
- Possibly legacy auth middleware or API controllers

### Step 2: Verify no active reads
For each read site, determine if it's:
- **Write-only** (registration): safe to remove
- **Read for auth**: must be migrated to `Hash::check()` against `password`
- **Read for display**: must be removed (never display passwords)

### Step 3: Migrate remaining readers
If any code reads `user_pass` for authentication, change to:
```php
// Before (legacy)
if ($input_password === $member->user_pass) { ... }

// After
if (Hash::check($input_password, $member->password)) { ... }
```

### Step 4: Remove write path
In `LoginController@register()` and `register_api()`:
```php
// Remove this line
'user_pass' => $pass,  // DELETE

// Keep this line
'password' => Hash::make($pass),  // KEEP
```

### Step 5: Migration to drop column
```php
// database/migrations/2026_06_24_000001_drop_user_pass_from_members.php
Schema::table('members', function (Blueprint $table) {
    $table->dropColumn('user_pass');
});
```

### Step 6: Password rotation campaign
After dropping `user_pass`, passwords that were previously only stored as plaintext (and never hashed) will fail `Hash::check()`. Options:
- **Option A**: Before dropping, run a script to ensure ALL members have `password` populated from `user_pass` with `Hash::make()`
- **Option B**: Force password reset for all members (disruptive but most secure)

### Step 7: Remove from model
Remove `'user_pass'` from `$fillable`, `$hidden`, and any other model configuration arrays in `Member.php`.

## Affected Files
1. `packages/Gametech/Wallet/src/Http/Controllers/LoginController.php` — remove writes (~4 lines)
2. `packages/Gametech/Member/src/Models/Member.php` — remove attribute (~2 lines)
3. `database/migrations/2026_06_24_000001_drop_user_pass_from_members.php` — new migration
4. Any legacy auth files found in audit

## Estimated Effort
**1-2 days** including audit, migration, testing, and password rotation

## Risks
- Legacy code we didn't find in audit may read `user_pass` and break after column drop
- Password rotation may cause support tickets from users who forgot new passwords
- Need to coordinate with any external systems that authenticate via `user_pass`

## Verification
1. `rg "user_pass"` returns zero results in packages/ and app/
2. All registration tests pass (passwords stored as bcrypt only)
3. Login still works for existing users after password migration
4. Column successfully dropped in staging before production
