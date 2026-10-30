# Run, test and deploy

Current status: three genuine local `cloth-v3` tasks completed after strict-TLS credentialed preflight, and a bounded desktop browser review exercised comparisons, three languages and exports. The [public workspace](https://threadroom.microusada.com/) is now live-enabled; one public two-look action completed two more tasks with both loaded outputs at 1024 x 1536, bringing the total to five. The 140-second English film has a verified [YouTube upload](https://youtu.be/LzYA8Om5eT8) with Studio publication/public selection confirmed. The published watch page displayed 2:20 and played 0:00 to 0:02 in the existing signed-in Chrome session before being paused. Complete/anonymous playback, full media review, browser Clear confirmation and real mobile hardware/comprehensive accessibility remain pending. [Formal Devpost submission](https://devpost.com/software/threadroom) was confirmed on 30 October 2026. Limited English/Japanese full-page and comparison checks passed at measured clientWidth = scrollWidth = 375px, including before/after and slider keyboard 50 to 51; the viewport override was reset. [VERIFICATION.md](VERIFICATION.md) separates this later local evidence from the recorded source/CI/r3 HTTP baseline. Judges should start with [JUDGE_GUIDE.md](JUDGE_GUIDE.md); this page is for operators and developers.

## Requirements and local start

Use PHP 8.2+ with cURL, fileinfo, JSON and sessions. The backend uses file locks and private files; it does not require Node, Docker, a database or GD. Serve only the `public/` directory. From the project root:

```powershell
$env:THREADROOM_SECURE_COOKIE = 'false'
php -d upload_max_filesize=10M -d post_max_size=21M -S 127.0.0.1:31783 -t public router.php
```

Open `http://127.0.0.1:31783`. Local HTTP needs Secure cookies disabled; production HTTPS must use Secure cookies. The PHP built-in server and `router.php` are development conveniences, not the production hosting strategy. Choose another unused port if this one is occupied.

## Configuration

`.env.example` lists supported configuration names. There is no automatic dotenv loader. Export process/FPM environment variables or create ignored `config/local.php` returning an associative array. Do not commit that file or print its contents. Public frontend variables must never contain the provider key.

| Setting | Purpose |
| --- | --- |
| `YOUCAM_API_KEY` | Server-only account credential; empty by default. |
| `YOUCAM_API_BASE_URL` | Exact account-confirmed API host. Accepted hosts are the historic global host and current documented makeupar host; do not automatically retry a paid POST on another host. |
| `YOUCAM_LIVE_ENABLED` | Defaults to false. Enable only after authorized preflight and a confirmed unit budget. |
| `THREADROOM_RUNTIME_DIR` | Private writable storage outside `public/`. Never expose it through a static route. |
| `THREADROOM_GLOBAL_UNIT_BUDGET` | Local lifetime reservation ceiling; default 300 units. Reservations survive errors and Clear. |
| `THREADROOM_JUDGING_RESERVE_UNITS` | Protected remaining provider units; at least 300 in the current implementation. |
| `THREADROOM_SESSION_TASK_LIMIT` / `THREADROOM_GLOBAL_TASK_LIMIT` | Source defaults are 12 / 150. The activated public pool overrides the session cap to 2; the global cap remains 150. |
| `THREADROOM_SECURE_COOKIE` | True for production HTTPS; false only for explicit local HTTP development. |

Only account-confirmed activity units may be used. This project must not purchase credits, enable top-ups or add a subscription. Keep enough units and free judge access through the event's judging period: **21 November 2026, 00:45 Asia/Taipei**, according to the rules reviewed for the brief.

## Verify the provider before generation

With the user's authorized account check and a privately configured credential:

```sh
php tools/check-provider.php
```

This tool performs balance and feature-cost GET requests only. It does not create a generation task or intentionally print credentials, account IDs, balances or signed URLs. A successful read-only check is not a real try-on result. Do not treat example prices or an empty account as sufficient proof.

The current implementation requires an account-confirmed `cloth-v3` SKU at exactly two units per result with the expected output shape. If the account's host, unit cost or SKU differs, investigate the official console and code before enabling live mode. Do not silently fall back to another provider, fake image or unsupported endpoint.

For a fresh authorized installation, begin with one cleared synthetic task and record its actual result before a two-look action. Capture only release/date/engine/outcome and separately observed balance snapshots; never invent a debit from the cost schedule. Keep credentials, signed storage URLs, raw responses and personal photos out of public evidence.

The coordinating local phase has already completed its one-task smoke and two-look pair. Its ceiling is 3 tasks / 6 scheduled units, with reserve 300; no extra generation is authorized for that phase. A later read-only API snapshot at 07:11:01 UTC reported 1034 available units, without a pre-generation API balance snapshot. This observation predates public activation and its two additional tasks; no post-public balance was measured.

On the coordinating Windows PHP runtime, cURL initially failed certificate verification. Supplying an existing trusted CA bundle through a process-specific `curl.cainfo` setting allowed strict TLS and the authenticated preflight. Configure an appropriate trusted bundle for your runtime if needed; never disable certificate or host verification as a workaround. This is an environment requirement, not a reason to repeat a chargeable POST.

## Test commands and meaning

```sh
php tests/run.php
php tests/http-smoke.php
```

The backend suite uses an explicitly fake provider transport. Its 21 cases pass locally and in the linked application CI. It exercises configuration honesty, consent/CSRF, input validation, metadata removal, ownership, host policy, budget/cost failures, duplicate prevention, timeout recovery, cleanup and ledger locking. Regression cases also cover bounded recovery of an existing completed task, in-flight leases, stale-worker fencing and documented task-create refusals while retaining lifetime unit reservations. A passing suite does not establish credentials, credits, real output quality, production routing, browser behavior or deployment.

The HTTP smoke test starts an isolated local server/runtime with an empty key and live mode disabled, then checks the same-origin API paths over HTTP. It does not contact YouCam or spend provider units. Frontend syntax checks can be run with `node --check public/app.mjs` and `node --check public/i18n.mjs`; Node is a development check, not a hosting dependency. Current reported results are recorded in [VERIFICATION.md](VERIFICATION.md).

Before a release, also inspect in a real browser:

- Missing key: show unavailable, no simulated queue or completed output.
- English, Traditional Chinese and Japanese: text, glyphs, consent and error messages; keyboard and mobile layout.
- Authorized image upload, two different garments, genuine task status, partial failure and checking the same job after a timeout.
- A/B and before/after only after real output exists; correct source labels and no fake fit score.
- Notes JSON excludes images and result URLs; comparison download is deliberate and marked as AI visual reference.
- Verify that Clear removes browser state and private local result access, with any pending cleanup accurately disclosed; this check remains incomplete.
- A second browser session cannot retrieve the first session's result.

Record outcomes in [VERIFICATION.md](VERIFICATION.md). A checklist describes required work, not a passing test.

## Deployment status and release procedure

The isolated [public entry](https://threadroom.microusada.com/) is deployed on existing resources, using static frontend files plus a same-origin PHP-FPM proxy. Current build `threadroom-12c5c77-r3` has verified DNS/public HTTPS reachability and 23/23 application-file equality with commit `12c5c77`. Public checks at 03:43:28 Asia/Taipei on 30 October 2026 passed 19/19: selected asset integrity/MIME, CSP/nosniff, private-path 404s, fixed API routing, no-store and the unconfigured secure-cookie state. Earlier r2 origin checks at 02:59:02 and public checks at 03:00:14 are historical evidence, not a fresh origin-only round for r3. Deployment and those historical HTTP/source checks made zero provider calls. At 08:28:23 UTC on 30 October 2026, live activation succeeded with all 23 application files unchanged, configured/liveAvailable true, secure session cookies and a pool live override while the configuration default remained false. Limits were two tasks/session, 150 tasks globally, 300 reserved units and provider reserve 300. Activation itself created no task. A later public browser pair returned both Ready results at 1024 x 1536. An independent post-activation public HTTPS check passed 19/19 at that point in time: configured/liveAvailable true, ten HTML/assets matching SHA/MIME/size, seven private-path 404s, API-controller 404/no-store and secure session-cookie flags. Ongoing access through the entire judging period remains to be maintained.

Use the following procedure for a future release or provider activation. The recorded HTTP checks do not make every step below complete. Keep other applications intact; do not add a Node daemon, database, paid host or container merely for this app. Build assets locally.

1. Recheck actual server capacity and PHP extensions; historical capacity observations are not a current guarantee.
2. Configure a separate hostname, document root, vhost and minimal FPM pool. Serve only `public/`; route `/api/*` to `public/api/index.php`. Do not reuse unrelated site credentials or settings.
3. Put secrets outside the release and runtime outside webroot. Restrict runtime ownership and permissions; retain the ledger across deploys so a release does not reset spending limits.
4. Enable HTTPS and Secure, HttpOnly, SameSite session cookies. Apply appropriate request/upload limits. Do not cache API responses or user results at a CDN/reverse proxy.
5. Verify the actual hostname certificate, routing, health response, private-file protection and no-key behavior. The appearance of a DNS record is not a successful deployment receipt.
6. Enable live generation only after preflight and budget review. Verify a real task, polling, same-session result, foreign-session denial, Clear, error handling and unit reserve on the deployed release.
7. Save a redacted release receipt with commit/build hashes, URL, time and check outcomes. Keep a rollback release. Never erase the budget ledger as a rollback shortcut.

The public cleared-sample try-on path is now verified and requires no visitor account, provider key or payment, subject to its stated capacity and reserve. It does not establish continuous judging-period availability, completed browser Clear, all error paths or final release reconciliation. The [Devpost entry](https://devpost.com/software/threadroom) was formally submitted, confirmed by the actual project page at 08:44:19 UTC on 30 October 2026; the cover, five-image gallery/captions, story, additional information and public video were saved. [GitHub Issues](https://github.com/CarsonCCHsieh/threadroom/issues) is the public project support channel, with no photos, keys, identifiers or private logs in issues. It is not a private intake channel. Before accepting public personal photos, establish a suitable private request channel and record an actual cleanup owner, cadence and completed verification. None is activated by this guide. Use cleared synthetic samples until those conditions are met. Follow the [manual cleanup runbook](PRIVACY.md#operator-cleanup-runbook--manual-not-a-scheduler); no cron or automatic app TTL is claimed.
