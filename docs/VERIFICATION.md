# Verification record

Evidence boundary: updated on 30 October 2026 from published application source, completed CI, applied deployment, public HTTPS checks and a read-only deployed-source comparison. This table separates source, HTTP proof, browser behavior and genuine provider execution. All 23 application files in current deployed build `threadroom-12c5c77-r3` are byte-identical to application-source commit [12c5c77](https://github.com/CarsonCCHsieh/threadroom/commit/12c5c775f05de44866547db7c36ea5253b7fa65a). Earlier origin/public evidence for build `6a62a16-r2` is historical and labeled below. This documentation revision is a later evidence update; it is not included in the linked application-source commit. A final browser/live-tested release and video remain pending.

| Milestone | Status | Evidence / remaining work |
| --- | --- | --- |
| Public repository | Published | [CarsonCCHsieh/threadroom](https://github.com/CarsonCCHsieh/threadroom), public; initial `main` [6a62a1602821340278cac4e42e72dd8ba7551e19](https://github.com/CarsonCCHsieh/threadroom/commit/6a62a1602821340278cac4e42e72dd8ba7551e19), committed 30 October 2026, 02:16:42 Asia/Taipei. |
| Application source / deployed bytes | Published; current 23/23 files match | [12c5c775f05de44866547db7c36ea5253b7fa65a](https://github.com/CarsonCCHsieh/threadroom/commit/12c5c775f05de44866547db7c36ea5253b7fa65a). A read-only comparison verified that deployed build `threadroom-12c5c77-r3` contains `public/**` and `server/*.php` application files matching that commit's Git blobs exactly. This does not claim that later documentation revisions are included in that commit. |
| CI for application source | Success recorded | [Run 40942475438](https://github.com/CarsonCCHsieh/threadroom/actions/runs/40942475438), [job 123689152945](https://github.com/CarsonCCHsieh/threadroom/actions/runs/40942475438/job/123689152945): runtime requirements, PHP/browser-module syntax, offline asset receipts, 21 fake-provider cases and isolated no-key HTTP smoke all passed. These checks do not establish live generation or browser quality. |
| Interface / translations | Source and static checks present; runtime review pending | JavaScript syntax, 127-key parity across three languages and DOM reference checks pass. Local HTTP asset bytes match source. Record browser/device and language review separately. |
| PHP integration and controls | Source present | See `server/README.md`; code inspection is not a live provider test. |
| Backend fake-provider suite | 21 cases passing locally and in published CI | `php tests/run.php` uses a mock provider. The deployed application source includes bounded same-task result recovery, leases/stale-worker fencing, Clear cancellation and documented create-rejection classification while retaining budget/idempotency. The linked application CI covers these regressions. No real YouCam call is exercised, so live compatibility remains unverified. |
| Local HTTP smoke / PHP syntax | Passing locally and in published CI | `php tests/http-smoke.php` uses isolated no-key local HTTP with zero provider calls. This local-server check is separate from public deployment checks and genuine provider execution. |
| Windows credential helper | Syntax checked; interactive behavior unverified | Secure input and private ACL setup are implemented, but execution-policy restrictions prevented a runtime check. No credential was collected. |
| Authorized activity registration | Not verified | Keep any confirmation receipt private/redacted as appropriate. |
| API key / correct account host | Not verified | No credential or account identifier belongs in this file. |
| Redeemed activity units | Not verified | Record only a redacted verification outcome, not account details. |
| Read-only balance / feature-cost preflight | Not run / not verified | Confirm account-compatible host, engine and cost before generation. |
| Genuine `cloth-v3` task | Not run / not verified | Need one completed real task with cleared input material. |
| Genuine two-look A/B workflow | Not verified | Do not substitute samples or fixtures for provider outputs. |
| Browser Clear / provider cleanup | Not live verified | Local source behavior and provider deletion requests are distinct; retention caveats apply. |
| Sample image provenance | Prompts and observed hashes present | Three original synthetic inputs are recorded in `public/assets/provenance.json`; not YouCam outputs. [ASSETS.md](ASSETS.md) records observed hashes for them and the plain-color fixture. Recheck the final release and finish actual output/rights receipts separately. |
| Font sources and UI codepoints | CJK and DM Sans receipts present; browser rendering pending | CJK version-pinned sources/output hashes and zero missing current UI codepoints are recorded in `public/assets/fonts/font-receipt.json`. [DM Sans receipt](../public/assets/fonts/dm-sans-receipt.json) records version 4.004 and font/OFL byte identity with pinned official Google Fonts sources, not a historical acquisition date. Actual browser rendering remains unverified. |
| Public deployment / DNS | Current build applied; HTTPS entry reachable | [https://threadroom.microusada.com/](https://threadroom.microusada.com/), build `threadroom-12c5c77-r3`, 23 application files matching application-source commit `12c5c77`, live mode disabled. Deployment, HTTP checks and source comparison made zero provider calls. No uptime guarantee or working-provider claim. |
| Origin HTTPS checks | Historical r2: 19/19 passed | 30 October 2026, 02:59:02 Asia/Taipei, build `6a62a16-r2`: selected asset hashes/sizes/MIME, exact expected CSP and nosniff, selected private paths/direct PHP 404, fixed API-controller JSON 404/no-store, unconfigured health and secure session-cookie flags. This was not rerun as an origin-only round for r3; current public/source checks are separate evidence. No browser execution or provider task was exercised. |
| Public HTTPS checks | Current r3: 19/19 passed | 30 October 2026, 03:43:28 Asia/Taipei, at the public URL: selected asset hashes/sizes/MIME, expected CSP and nosniff, selected private-path/direct-PHP 404s, API-controller JSON 404/no-store, `configured=false`, `liveAvailable=false`, CSRF-token shape and Secure/HttpOnly/SameSite=Strict cookie flags passed. HTTP checks only; no browser execution or provider generation. |
| Historical application / CI / public evidence | Superseded baseline retained | Earlier build `6a62a16-r2` had 23/23 byte equality with [a7a7a05](https://github.com/CarsonCCHsieh/threadroom/commit/a7a7a051111ee57f87985a3721320c559b75cfcf), [CI run 40937515854](https://github.com/CarsonCCHsieh/threadroom/actions/runs/40937515854) and public 19/19 at 03:00:14 Asia/Taipei on 30 October 2026. An earlier public HTTP attempt returned status 0 and was preserved as failed; no specific network cause was established. [Initial-baseline CI](https://github.com/CarsonCCHsieh/threadroom/actions/runs/40929341613) is also historical; neither earlier run proves the later recovery fixes. |
| Judge access through end of judging | Working API access not verified | The public entry is reachable with generation disabled. Still need real provider access, sufficient activity units and functioning free access through the judging period. |
| English judge and recording documents | Prepared; actual use unverified | [JUDGE_GUIDE.md](JUDGE_GUIDE.md), [DEMO_NARRATION.md](DEMO_NARRATION.md) and [DEMO_SUBTITLES.srt](DEMO_SUBTITLES.srt) describe planned actions. No real recording or runtime pass is implied. |
| Public support / cleanup operations | Public channel documented; operations pending | [GitHub Issues](https://github.com/CarsonCCHsieh/threadroom/issues) is for nonsensitive reports only. [PRIVACY.md](PRIVACY.md) documents manual cleanup limits. No private intake, assigned cleanup cadence, cron or automatic TTL is asserted. |
| Final video | Not recorded / uploaded / verified | No playable public URL. Script and provisional captions are not a video. |
| Final tested release | Pending | Application-source provenance, CI and public HTTP evidence are recorded for build `threadroom-12c5c77-r3` / commit `12c5c77`. Still verify the target device/browser and genuine live flow, record the real video, and identify the final release including subsequent documentation. The hosted entry alone does not satisfy the working YouCam API requirement. |
| Final Devpost submission | Not submitted / not verified | A draft is not a submitted entry. Preserve final confirmation before changing status. |

## Evidence entry template

```text
Check:
Date / timezone:
Commit or build:
Environment (local / staging / public):
Mode (fake provider / read-only provider / real generation):
Expected behavior:
Observed behavior:
Result (pass / fail / blocked):
Redacted evidence file or public URL:
Remaining limitation:
```

Keep private photos, keys, signed URLs, account IDs and raw provider payloads out of public evidence. An output screenshot should identify whether it is an original input, a test fixture or a genuine YouCam result. Do not relabel evidence from an earlier commit as validation of later behavior without checking the affected path.
