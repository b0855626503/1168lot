# One-shot Agent Prompt: Generate Payment Provider V3

ใช้ prompt นี้กับ Codex/Agent:

TASK:
Create a new payment provider from API documentation using Payment MCP Generator V3.

INPUT:
- Provider name: {{provider_name}}
- API document: {{doc_url_or_file}}
- Reference provider: smkpay

RULES:
- Do not assume provider supports withdraw/callback/balance.
- Analyze document first.
- If withdraw/callback/balance/auth is missing or unclear, ask user before writing files.
- Default mode must be dry_run.
- Write files only after validation passes.
- Do not modify .env.
- Do not deploy.
- Do not run migration.
- Do not git push.
- Do not overwrite existing provider files.

COMMAND:
php artisan payment:provider-generate --provider={{provider_name}} --doc-url="{{doc_url}}" --mode=dry_run --package

IF validation passes and user confirms:
php artisan payment:provider-generate --provider={{provider_name}} --doc-url="{{doc_url}}" --mode=write_files --package

OUTPUT:
- Show analysis.json summary
- Show plan.json summary
- Show validation.json
- Show package path
- List manual route/config/logging patches required
