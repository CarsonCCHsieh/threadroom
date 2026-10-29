# THREADROOM

A little more considered. A wardrobe decision workspace for comparing a possible purchase with something you already own.

THREADROOM combines two-look virtual try-on with user-entered price, expected wears and personal notes. Its intended workflow is simple: choose two garments, use the same consented person photo, compare the generated looks, and decide whether the new piece deserves a place in your wardrobe. Cost per wear is arithmetic based on your inputs, not an AI prediction.

**Development status — 30 October 2026:** the [hosted workspace](https://threadroom.microusada.com/), build `threadroom-12c5c77-r3`, is deployed with live generation disabled. Public HTTPS checks passed 19/19 at 03:43:28 Asia/Taipei. They verified selected asset bytes/MIME types, CSP, private-path 404s, API routing and the unconfigured health/session-cookie state. Browser interaction and real YouCam generation remain unverified. No API key, redeemed activity units, genuine try-on result, video or final submission is established. Sample inputs are not YouCam outputs.

Application source is published at [12c5c77](https://github.com/CarsonCCHsieh/threadroom/commit/12c5c775f05de44866547db7c36ea5253b7fa65a), with [successful CI](https://github.com/CarsonCCHsieh/threadroom/actions/runs/40942475438) covering offline asset receipts, syntax, 21 fake-provider cases and isolated no-key HTTP smoke. A read-only comparison verified that all 23 application files in deployed build `threadroom-12c5c77-r3` are byte-identical to that commit. This documentation revision is a later evidence update, not part of the linked application-source commit. Browser/live API verification, the video and the final tested release remain pending. Public reachability does not yet meet the hackathon's working YouCam integration requirement; see [verification evidence](docs/VERIFICATION.md), including historical evidence for the earlier build.

The deployed application includes bounded recovery of a completed task's result and releases generation capacity after a documented task-create rejection. All lifetime unit reservations remain in place, and chargeable POSTs are never automatically retried. These paths have mock regression coverage in the linked CI; no real provider request was made for that coverage, deployment or public HTTP checks.

## What is in this build

| Area | Current evidence |
| --- | --- |
| Full-window fitting workspace | Interface source is present; browser and device review remain to be recorded. |
| Hosted entry | [HTTPS workspace](https://threadroom.microusada.com/) deployed; r3 public HTTP checks pass 19/19. Live API mode is disabled. |
| English, Traditional Chinese and Japanese | Translation key parity and bundled CJK glyph checks pass. Browser rendering and language review remain pending. |
| Two garments: considering / owned | Interface and selection workflow are present in source. No real product partnership or stock feed is claimed. |
| Price, expected wears and notes | Client-side decision tools; do not infer fit, clothing usage or savings. Runtime verification is pending. |
| A/B and original/result comparison | Designed for genuine results from the same input photo. Live comparison has not yet been verified. |
| Notes and comparison downloads | Explicit local-download code is present; comparison export requires at least one real completed result and labels any remaining original input. Browser verification is pending. |
| YouCam `cloth-v3` integration | Server-side upload, task, polling and result handling are implemented in source. Provider authentication and execution remain unverified. |
| Private results and deletion | Session ownership, private storage and cleanup requests exist in source. Provider erasure is not guaranteed. |

The backend fails closed when live mode, credentials, cost verification or available units are missing. It does not produce simulated success states. Backend tests use a fake provider transport; they cannot prove real YouCam compatibility.

## Run locally

Requires PHP 8.2+ with cURL, fileinfo, JSON and sessions. No Node server, database or GD extension is required by the PHP proxy. Run from the project directory:

```powershell
$env:THREADROOM_SECURE_COOKIE = 'false'
php -d upload_max_filesize=10M -d post_max_size=21M -S 127.0.0.1:31783 -t public router.php
```

Open `http://127.0.0.1:31783`. Without credentials the application should allow interface exploration and report live try-on as unavailable. Merely copying `.env.example` to `.env` does not load configuration: use process environment variables or the ignored `config/local.php` mechanism. Keep live mode disabled until the account, host and unit cost are verified.

On Windows, `powershell.exe -NoProfile -File .\tools\configure-provider.ps1` provides a hidden-input setup helper when script execution is permitted. It writes ignored, access-restricted configuration and leaves live mode off. Its syntax has been checked; its interactive input and ACL behavior still require runtime verification. Do not place a key in a command argument or commit the resulting file.

[Run, test and deploy](docs/RUNNING.md) covers configuration, the read-only provider check, operational limits and the release checklist. [Backend details](server/README.md) describe the API contract and current safety implementation.

## Boundaries

- A generated look is a visual impression, not a size, comfort, fabric or physical-fit guarantee.
- Prices and expected wears are entered by the user. Sample prices are illustrative, not live offers.
- There is no checkout, public gallery, account system, purchase recommendation score or measured claim about returns, savings or environmental impact.
- Choosing a photo prepares it in the browser. Starting a live try-on sends selected images through this server to YouCam after consent.
- Clear removes the current page's data and requests server cleanup. It cannot undo a third party's applicable retention policy.

See [privacy and retention](docs/PRIVACY.md), [asset sources](docs/ASSETS.md), and [verification status](docs/VERIFICATION.md).

## Hackathon materials

- [Submission checklist](docs/SUBMISSION_CHECKLIST.md)
- [English judge guide](docs/JUDGE_GUIDE.md)
- [English Devpost draft](docs/DEVPOST_DRAFT.md)
- [Two-minute demo storyboard](docs/DEMO_STORYBOARD.md)
- [Full English narration and video description template](docs/DEMO_NARRATION.md)
- [Draft English subtitles — retime after real recording](docs/DEMO_SUBTITLES.srt)

The repository and hosted entry are public. Registration, live API access, a video upload and final submission remain pending. The YouCam event requires a working integration, code access, English materials and a 1–3 minute public demonstration; a deployed workspace with generation disabled does not complete that requirement. [Official rules](https://youcam-api-skin-ai-ecommerce.devpost.com/rules) take precedence over this checklist.

Public project support: [GitHub Issues](https://github.com/CarsonCCHsieh/threadroom/issues). Report a nonsensitive description and browser/version only. Do not attach photos, API keys, signed URLs, task/session identifiers, account details or private logs. This is a public issue tracker, not a private channel for photo or account requests; see [privacy and operator cleanup](docs/PRIVACY.md).

## License

Original application code and documentation are available under the [MIT License](LICENSE). Bundled fonts retain their SIL Open Font License. Generated, photographic and user-supplied assets have separate provenance and rights conditions in [ASSETS.md](docs/ASSETS.md); the MIT license does not automatically relicense those assets or the YouCam service.
