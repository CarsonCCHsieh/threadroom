# Run, test and deploy

Current status: application source [a7a7a05](https://github.com/CarsonCCHsieh/threadroom/commit/a7a7a051111ee57f87985a3721320c559b75cfcf), [successful CI](https://github.com/CarsonCCHsieh/threadroom/actions/runs/40937515854), and the [hosted workspace](https://threadroom.microusada.com/) are recorded. Build `6a62a16-r2` is deployed with live generation disabled; origin/public HTTPS checks each passed 19/19. A read-only comparison verified all 23 deployed application files as byte-identical to that commit. This documentation revision is a later evidence update, not part of the linked commit. Provider credentials, activity units, genuine generation, browser/device behavior and the final tested release remain unverified. Commands below are instructions unless an actual outcome is separately recorded in [VERIFICATION.md](VERIFICATION.md). Judges should start with the [English judge guide](JUDGE_GUIDE.md); this page is for operators and developers.

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
| `THREADROOM_SESSION_TASK_LIMIT` / `THREADROOM_GLOBAL_TASK_LIMIT` | Default reservation limits of 12 / 150. |
| `THREADROOM_SECURE_COOKIE` | True for production HTTPS; false only for explicit local HTTP development. |

Only account-confirmed activity units may be used. This project must not purchase credits, enable top-ups or add a subscription. Keep enough units and free judge access through the event's judging period: **21 November 2026, 00:45 Asia/Taipei**, according to the rules reviewed for the brief.

## Verify the provider before generation

With the user's authorized account check and a privately configured credential:

```sh
php tools/check-provider.php
```

This tool performs balance and feature-cost GET requests only. It does not create a generation task or intentionally print credentials, account IDs, balances or signed URLs. A successful read-only check is not a real try-on result. Do not treat example prices or an empty account as sufficient proof.

The current implementation requires an account-confirmed `cloth-v3` SKU at exactly two units per result with the expected output shape. If the account's host, unit cost or SKU differs, investigate the official console and code before enabling live mode. Do not silently fall back to another provider, fake image or unsupported endpoint.

Next, use an authorized, cleared sample pair for one real upper-body generation. Capture a redacted evidence record: commit, date, engine, completion status, observed unit change and reproducible steps. Do not record the key, signed storage URL, full raw response or personal photo. Confirm expected behavior before attempting two-look comparison.

## Test commands and meaning

```sh
php tests/run.php
php tests/http-smoke.php
```

The backend suite uses an explicitly fake provider transport. It exercises configuration honesty, consent/CSRF, input validation, metadata removal, ownership, host policy, budget/cost failures, duplicate prevention, timeout recovery, cleanup and ledger locking. A passing suite does not establish credentials, credits, real output quality, production routing, browser behavior or deployment.

The HTTP smoke test starts an isolated local server/runtime with an empty key and live mode disabled, then checks the same-origin API paths over HTTP. It does not contact YouCam or spend provider units. Frontend syntax checks can be run with `node --check public/app.mjs` and `node --check public/i18n.mjs`; Node is a development check, not a hosting dependency. Current reported results are recorded in [VERIFICATION.md](VERIFICATION.md).

Before a release, also inspect in a real browser:

- Missing key: show unavailable, no simulated queue or completed output.
- English, Traditional Chinese and Japanese: text, glyphs, consent and error messages; keyboard and mobile layout.
- Authorized image upload, two different garments, genuine task status, partial failure and checking the same job after a timeout.
- A/B and before/after only after real output exists; correct source labels and no fake fit score.
- Notes JSON excludes images and result URLs; comparison download is deliberate and marked as AI visual reference.
- Clear removes browser state and private local result access; provider pending cleanup remains accurately disclosed.
- A second browser session cannot retrieve the first session's result.

Record outcomes in [VERIFICATION.md](VERIFICATION.md). A checklist describes required work, not a passing test.

## Deployment status and release procedure

The isolated [public entry](https://threadroom.microusada.com/) is deployed on existing resources, using static frontend files plus a same-origin PHP-FPM proxy. DNS and public HTTPS reachability are verified. Origin checks at 02:59:02 and public checks at 03:00:14 Asia/Taipei on 30 October 2026 each passed 19/19: selected asset integrity/MIME, CSP/nosniff, private-path 404s, fixed API routing, no-store and the unconfigured secure-cookie state. These were HTTP checks with zero provider calls; browser execution, real generation and ongoing judge access are still unverified.

Use the following procedure for a future release or provider activation. The initial deployed state does not make every step below complete. Keep other applications intact; do not add a Node daemon, database, paid host or container merely for this app. Build assets locally.

1. Recheck actual server capacity and PHP extensions; historical capacity observations are not a current guarantee.
2. Configure a separate hostname, document root, vhost and minimal FPM pool. Serve only `public/`; route `/api/*` to `public/api/index.php`. Do not reuse unrelated site credentials or settings.
3. Put secrets outside the release and runtime outside webroot. Restrict runtime ownership and permissions; retain the ledger across deploys so a release does not reset spending limits.
4. Enable HTTPS and Secure, HttpOnly, SameSite session cookies. Apply appropriate request/upload limits. Do not cache API responses or user results at a CDN/reverse proxy.
5. Verify the actual hostname certificate, routing, health response, private-file protection and no-key behavior. The appearance of a DNS record is not a successful deployment receipt.
6. Enable live generation only after preflight and budget review. Verify a real task, polling, same-session result, foreign-session denial, Clear, error handling and unit reserve on the deployed release.
7. Save a redacted release receipt with commit/build hashes, URL, time and check outcomes. Keep a rollback release. Never erase the budget ledger as a rollback shortcut.

The public deployment is confirmed only to the HTTP-check scope above. It is currently unconfigured for YouCam generation and does not yet meet the working API requirement. [GitHub Issues](https://github.com/CarsonCCHsieh/threadroom/issues) is the public project support channel, with no photos, keys, identifiers or private logs in issues. It is not a private intake channel. Before accepting public personal photos, establish a suitable private request channel and record an actual cleanup owner, cadence and completed verification. None is activated by this guide. Use cleared synthetic samples until those conditions are met. Follow the [manual cleanup runbook](PRIVACY.md#operator-cleanup-runbook--manual-not-a-scheduler); no cron or automatic app TTL is claimed.
