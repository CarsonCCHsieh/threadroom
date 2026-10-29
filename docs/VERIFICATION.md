# Verification record

Evidence boundary: updated on 30 October 2026 from the current source, published-source/CI evidence, applied deployment and completed origin/public HTTPS checks. This table separates source, HTTP proof, browser behavior and genuine provider execution. The deployed working-tree build `6a62a16-r2` contains 23 application files based on the initial public baseline plus later asset receipts. Its complete final Git commit and subsequent documentation inclusion remain to be recorded; do not label the entire deployed tree as identical to the initial commit.

| Milestone | Status | Evidence / remaining work |
| --- | --- | --- |
| Public repository | Published | [CarsonCCHsieh/threadroom](https://github.com/CarsonCCHsieh/threadroom), public; initial `main` [6a62a1602821340278cac4e42e72dd8ba7551e19](https://github.com/CarsonCCHsieh/threadroom/commit/6a62a1602821340278cac4e42e72dd8ba7551e19), committed 30 October 2026, 02:16:42 Asia/Taipei. |
| CI for initial baseline | Success recorded | [Run 40929341613](https://github.com/CarsonCCHsieh/threadroom/actions/runs/40929341613). Mock/provider-isolated checks do not establish live generation, browser quality or deployment. |
| Interface / translations | Source and static checks present; runtime review pending | JavaScript syntax, 127-key parity across three languages and DOM reference checks pass. Local HTTP asset bytes match source. Record browser/device and language review separately. |
| PHP integration and controls | Source present | See `server/README.md`; code inspection is not a live provider test. |
| Backend fake-provider suite | 15 cases passing, independently rerun | `php tests/run.php` uses a mock provider. Covers budget, ownership, timeout recovery, atomic writing, clearing during upload, abandoned known-task reconciliation and uncertain cleanup. No real YouCam call is exercised. |
| Local HTTP smoke / PHP syntax | HTTP smoke independently rerun and passing; syntax checks passing | `php tests/http-smoke.php` uses isolated no-key local HTTP with zero provider calls. This is not public deployment or a real-provider check. |
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
| Public deployment / DNS | Applied; HTTPS entry reachable | [https://threadroom.microusada.com/](https://threadroom.microusada.com/), working-tree build `6a62a16-r2`, 23 application files, live mode disabled. Deployment and HTTP checks made zero provider calls. No uptime guarantee or working-provider claim. |
| Origin HTTPS checks | 19/19 passed | 30 October 2026, 02:59:02 Asia/Taipei: selected asset hashes/sizes/MIME, exact expected CSP and nosniff, selected private paths/direct PHP 404, fixed API-controller JSON 404/no-store, unconfigured health and secure session-cookie flags. HTTP checks only; no browser execution or provider task. |
| Public HTTPS checks | 19/19 passed | 30 October 2026, 03:00:14 Asia/Taipei, at the public URL: the same checks passed, including `configured=false`, `liveAvailable=false`, CSRF-token shape and Secure/HttpOnly/SameSite=Strict cookie flags. An earlier attempt before DNS resolved failed; the completed later round is the current reachability evidence. |
| Judge access through end of judging | Working API access not verified | The public entry is reachable with generation disabled. Still need real provider access, sufficient activity units and functioning free access through the judging period. |
| English judge and recording documents | Prepared; actual use unverified | [JUDGE_GUIDE.md](JUDGE_GUIDE.md), [DEMO_NARRATION.md](DEMO_NARRATION.md) and [DEMO_SUBTITLES.srt](DEMO_SUBTITLES.srt) describe planned actions. No real recording or runtime pass is implied. |
| Public support / cleanup operations | Public channel documented; operations pending | [GitHub Issues](https://github.com/CarsonCCHsieh/threadroom/issues) is for nonsensitive reports only. [PRIVACY.md](PRIVACY.md) documents manual cleanup limits. No private intake, assigned cleanup cadence, cron or automatic TTL is asserted. |
| Final video | Not recorded / uploaded / verified | No playable public URL. Script and provisional captions are not a video. |
| Final tested release | Pending | Public HTTP evidence is recorded for working-tree build `6a62a16-r2`. Still record its complete commit provenance, tested target device/browser and genuine live flow. The hosted entry alone does not satisfy the working YouCam API requirement. |
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
