# THREADROOM private PHP proxy

Published application source: [a7a7a05](https://github.com/CarsonCCHsieh/threadroom/commit/a7a7a051111ee57f87985a3721320c559b75cfcf), with [CI success](https://github.com/CarsonCCHsieh/threadroom/actions/runs/40937515854). A read-only comparison verified that all 23 application files in the [hosted workspace](https://threadroom.microusada.com/), build `6a62a16-r2`, match that commit's Git blobs. This documentation revision is a later evidence update. Live mode is disabled; deployment, HTTP checks and source comparison made zero provider calls. Origin/public checks each passed 19/19, including API routing, no-store, unconfigured health and secure session-cookie flags. These checks do not establish browser behavior, account access, redeemed activity units, genuine generation or hackathon submission. Provider tests still use an explicitly fake in-memory transport; browser/live verification, the video and the final tested release remain pending. See [VERIFICATION.md](../docs/VERIFICATION.md).

PHP 8.2+ with curl, fileinfo, JSON, sessions and standard image-header functions; no GD, Node, database, Docker or packages. Production document root **must be `threadroom/public`**. Never publish `server`, `config`, `var`, tests, or tools as static files. The built-in `router.php` is for local development; route `/api/*` to `public/api/index.php` in the existing FPM host.

## Configuration

Use process/FPM environment variables listed in `.env.example`. The file is a template, not an automatically loaded dotenv file. Alternatively create ignored `config/local.php` outside `public` returning an array of those variable names and values. Do not put credentials in client code or in variables prefixed with `VITE_` / `NEXT_PUBLIC_`.

The live flag defaults to false. The host defaults to the historic global host. Current official docs unexpectedly list the makeupar host while event resources distinguish its India console. Set the host confirmed in the account's official console; only `yce-api-01.perfectcorp.com` and `yce-api-01.makeupar.com` are accepted. A read-only dynamic SKU lookup must match this host, `cloth-v3`, `result_image`, one output per charge, and exactly two units before generation can start. Do not switch hosts and retry a chargeable POST automatically.

`php tools/check-provider.php` performs balance and feature-cost GETs only; it never creates a paid task and never prints credentials, account IDs, balances or signed storage URLs. **Do not run it unless a read-only account check is authorized.** No live check was run for this implementation.

Default private runtime is `var/private`, outside public. Override `THREADROOM_RUNTIME_DIR` to another private writable directory; PHP-FPM should own it and only that user should have access. Directory permissions are 0700 and files 0600 where supported. Session cookies are HttpOnly, SameSite=Strict, and Secure by default. For local HTTP only, explicitly set `THREADROOM_SECURE_COOKIE=false`.

Local startup in PowerShell (no key required):

```powershell
$env:THREADROOM_SECURE_COOKIE = 'false'
php -d upload_max_filesize=10M -d post_max_size=21M -S 127.0.0.1:31783 -t public router.php
```

Open `http://127.0.0.1:31783`; choose another unused port if necessary. Set matching upload limits in FPM. A health response without a key honestly reports `configured: false` and `liveAvailable: false`. Missing configuration or uncertain credits never produce a successful pretend result.

## API contract

All responses are `Cache-Control: no-store, private` and `nosniff`. The browser must send same-origin session cookies. Start with `GET /api/health` and retain its `csrf`. Send `X-CSRF-Token` on every POST/DELETE. If present, Origin must match the app host. No CORS access is granted.

| Route | Request | Response |
|---|---|---|
| `GET /api/health` | No body | `configured`, `provider: "YouCam"`, `liveAvailable`, safe `message`, `csrf` |
| `POST /api/tryons` | multipart `person`, `garment`, `category=upper_body`, `consent=true`, `idempotency_key` UUID; CSRF header | 202 for an accepted operation, or 200 for the same existing request; `id`, normalized `status`, `message`; error state includes `error` |
| `GET /api/tryons/{id}` | Same session | `id`, `status: queued/running/success/error`, `message`; success includes same-origin `resultUrl` |
| `GET /api/tryons/{id}/result` | Same session | Private `image/jpeg`; unknown, deleted or foreign session gets 404 |
| `DELETE /api/session` | CSRF header | `ok`, `csrf`, `providerCleanupPending`, `providerCleanupManual`, honest cleanup `message` |

Errors use a matching HTTP status and `{ "error": "stable_code", "message": "safe English text" }`. A provider error after reservation returns a job in error state (202), since an unknown chargeable request must retain its idempotency record. Retrying that same UUID returns the same outcome; it does not submit again. Reusing a UUID for different sanitized inputs returns 409. The client must not automatically invent a new UUID and retry on `task_unconfirmed`.

Images are JPEG/PNG under 10,000,000 bytes; both dimensions at least 384, longest edge at least 512 and at most 4096. Client-side canvas resizing is encouraged, then the server independently verifies MIME, magic/header dimensions and file structure. The parser strips JPEG APP/COM metadata, PNG nonessential metadata, and trailing bytes without GD. PNG CRCs are checked and APNG rejected. This is not a full image decoder, so malformed compressed pixels may still fail at the provider. Input images remain in request memory and PHP's temporary-upload lifecycle; they are not copied into runtime. Results are structurally validated JPEGs with metadata stripped; PNG provider output is explicitly unsupported, never relabeled JPEG.

## Safety and lifecycle

- A flock-protected ledger and atomic replacement coordinate all FPM workers. The same owner/idempotency key is reserved once. A corrupt ledger fails closed; it is never silently reset.
- Maximum two active/uncertain tasks globally; twelve reservations per session, 150 globally by default. Three starts per minute per session. Reservations survive deletion and session changes. The 300-unit lifetime development budget is conservative: even failed attempts retain their reservation to avoid undercounting unclear billing. Do not reset the ledger to bypass it.
- Every new operation performs read-only balance and cost verification. At least 300 units remain protected for judging, including active reservations. No subscription, top-up or payment call exists. Health may cache verified preflight facts for 30 seconds; creation always rechecks.
- Provider POSTs are never automatically retried. A connection failure after the chargeable POST starts is `task_unconfirmed`; its slot remains reserved until an operator reconciles it in the official account. The local ledger does not know a missing provider task ID and cannot promise deletion of such a task.
- Provider status is polled no faster than once per ten seconds per task with a cross-worker lease. Health and new creation also reconcile at most two due known task IDs, so completion can release capacity after their clients leave. Health reflects active/uncertain slots, session/rate limits and the protected balance. Slow tasks surface a timeout after five minutes, but subsequent checks can recover that original task. No extra chargeable task is created. A confirmed finished task with an invalid result returns an error and releases its generation slot; the unsafe/failed result is not fetched repeatedly.
- API hosts are exact allowlisted. Signed storage URLs must use HTTPS and approved provider/S3 host patterns, no credentials/fragments/nonstandard ports. DNS is checked for public-only IPs and pinned into curl to prevent rebinding. Redirects and environment proxies are disabled. Authorization is never sent to signed storage.
- Clear removes local results immediately and asks the provider to delete finished tasks and associated files. Running tasks are marked deleted; later health/start requests check known tasks and make bounded deletion attempts after completion. `providerCleanupPending` counts outstanding cleanup; its subset `providerCleanupManual` counts unknown/creating tasks without task IDs. Those require operator reconciliation and cannot be deleted automatically. Unknown tasks never release their slots merely by age. Provider retention still applies if cleanup fails or no further requests occur; this is not a background worker.
- No token, raw provider response, account identifier, signed storage URL or original photo is logged or returned. Provider task IDs stay in private ledger. Provider retains uploads/results under its own policy (currently documented as up to 30 days); do not claim this service guarantees immediate provider erasure.

After an app restart the ledger remains authoritative. Back it up privately if needed; do not publish it. It contains task IDs, private-session owner IDs, request hashes and local result filenames. Budget changes require deliberate operator review; the configured reserve cannot go below 300.

The [operator cleanup runbook](../docs/PRIVACY.md#operator-cleanup-runbook--manual-not-a-scheduler) covers Clear, pending/unknown tasks and evidence handling. It is a manual procedure, not an installed cron job or a new automatic TTL. Never remove the runtime directory or reset the ledger to make cleanup or budget status look successful.

## Verification

```powershell
php tests/run.php
php tests/http-smoke.php
```

Tests never instantiate the live curl transport. They cover no-key honesty, CSRF/consent, metadata removal, SSRF policy, credit/cost/timeout preflight failures, unknown POST idempotency, cross-session concurrency/budgets, poll timing, private JPEG access, deletion, malicious result URLs, timeout recovery, corrupt ledgers and concurrent ledger writers. `tests/fixtures/result.jpg` is a newly generated plain-color 512x512 test fixture, with no third-party photo or embedded personal data.

The separate HTTP smoke starts an isolated temporary copy of the backend with an empty key and live disabled, checks the actual session cookie, CSRF/Origin checks, multipart upload and honest unavailable response, then stops it and removes its temporary files. It cannot read the real project's private config.

Official references: [event rules](https://youcam-api-skin-ai-ecommerce.devpost.com/rules), [free-unit resources](https://youcam-api-skin-ai-ecommerce.devpost.com/resources), [clothes OpenAPI](https://docs.perfectcorp.com/_bundle/reference/ai_clothes.json?download=), [unit/cost OpenAPI](https://docs.perfectcorp.com/_bundle/reference/unit_system.json?download=), [retention](https://docs.perfectcorp.com/develop/file_retention_period). Implementation research is recorded in `reports/devpost-participation/youcam-api-brief.json` in the parent workspace.
