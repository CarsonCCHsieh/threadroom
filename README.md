# THREADROOM

A little more considered. A wardrobe decision workspace for comparing a possible purchase with something you already own.

THREADROOM combines two-look virtual try-on with user-entered price, expected wears and personal notes. Its intended workflow is simple: choose two garments, use the same consented person photo, compare the generated looks, and decide whether the new piece deserves a place in your wardrobe. Cost per wear is arithmetic based on your inputs, not an AI prediction.

**Submitted to the YouCam API Hackathon:** [THREADROOM on Devpost](https://devpost.com/software/threadroom). **[Try the live workspace](https://threadroom.microusada.com/)** using the included cleared synthetic samples, give processing consent and choose Try these two looks once. No visitor account, provider key or payment is required; session/global limits and the protected reserve apply. [Judge guide](docs/JUDGE_GUIDE.md).

**Verified locally, 30 October 2026:** one native smoke and a single two-look browser action completed three genuine YouCam `cloth-v3` tasks using cleared synthetic inputs. Both browser results loaded at 1024 x 1536. Desktop checks exercised A/B and before/after comparison, English/Traditional Chinese/Japanese controls, price/expected-wear arithmetic, and explicit JSON/PNG downloads. See [the bounded verification record](docs/VERIFICATION.md).

The [public workspace](https://threadroom.microusada.com/) is **live-enabled**. After activation on 30 October 2026, one public two-look action with cleared synthetic samples returned both Ready results, loaded at 1024 x 1536. Together with the three local tasks, five genuine tasks have completed. The limited sample path requires no account, provider key or payment from the visitor; use cleared synthetic samples while private support and retention/cleanup verification remain incomplete.

The [140-second English film](https://youtu.be/Snyos1LJcaY) is publicly published. In the existing signed-in Chrome session, the watch page showed 2:20 and played from 0:00 to 0:02 before being paused. Anonymous access, complete playback and the full listening review are not established. The film uses the earlier local UI screenshots and comparison download, not a full recording of the later public-site test. A later 375px-wide browser viewport passed bounded English/Japanese visual comparison checks; real mobile hardware, comprehensive accessibility and browser Clear remain unverified.

Application source [12c5c77](https://github.com/CarsonCCHsieh/threadroom/commit/12c5c775f05de44866547db7c36ea5253b7fa65a) has [successful CI](https://github.com/CarsonCCHsieh/threadroom/actions/runs/40942475438), including 21 fake-provider cases and isolated no-key HTTP smoke. The recorded r3 deployment matched its 23 application files and passed public HTTPS checks 19/19 with live disabled. These historical delivery checks are separate from the later local real-provider/browser evidence and this documentation update.

## What is in this build

| Area | Current evidence |
| --- | --- |
| Full-window fitting workspace | Desktop flow and limited 375px English/Japanese full-page/comparison review verified; no horizontal overflow in that measured viewport. Real mobile hardware and comprehensive accessibility remain unverified. |
| Hosted entry | Live activation/public two-look action verified. Independent post-activation HTTPS checks passed 19/19 at that point in time, with configured/liveAvailable true. |
| English, Traditional Chinese and Japanese | Actual desktop language switching and DOM checked; static key/glyph checks are separate evidence. |
| Two garments: considering / owned | Local and public two-look actions each returned both results. Bundled garments are fictional scenario inputs, with no retailer partnership. |
| Price, expected wears and notes | Browser input 68 / 50 displayed 1.36 per wear. This is arithmetic, with no usage or savings prediction. |
| A/B and original/result comparison | Actual desktop A/B and before/after views exercised on the generated results. No physical-fit claim. |
| Notes and comparison downloads | Actual PNG comparison downloaded; 749-byte JSON contained no images or URLs. Export integrity is recorded in the verification page. |
| YouCam `cloth-v3` integration | Strict-TLS preflight, three real local tasks and two real public tasks completed. Current balance has not been rechecked after the public pair. |
| Private results and deletion | Foreign-session result access returned 404. Browser Clear completion is pending; this is not a deletion/erasure pass. |

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
- Clear is designed to remove current page data and request server cleanup. The latest browser attempt did not visibly complete; completion remains unverified. Provider retention and user downloads remain separate.

See [privacy and retention](docs/PRIVACY.md), [asset sources](docs/ASSETS.md), and [verification status](docs/VERIFICATION.md).

## Hackathon materials

- [Submission checklist](docs/SUBMISSION_CHECKLIST.md)
- [English judge guide](docs/JUDGE_GUIDE.md)
- [English submission story and evidence](docs/DEVPOST_DRAFT.md)
- [Frozen 140-second demo timeline](docs/DEMO_STORYBOARD.md)
- [Full English narration and video description template](docs/DEMO_NARRATION.md)
- [English captions matching the frozen film](docs/DEMO_SUBTITLES.srt)

The repository and live sample path are public. Registration, local and public real-API workflows, video publication and bounded watch-page playback are verified. **Formal submission was confirmed on 30 October 2026 at 08:44:19 UTC**: the project page showed Project submitted!, Submitted to and Edit hackathon submission. The [public entry](https://devpost.com/software/threadroom) contains the saved cover, five-image gallery with captions, story, additional information and public 140-second video. This success confirmation is authoritative; no separate completion-step count is asserted. Final video review, anonymous/full playback, browser Clear, real mobile hardware/comprehensive accessibility and availability through judging remain to be confirmed. The YouCam event requires a working integration, code access, English materials and a 1–3 minute public demonstration; initial success is not a guarantee of continuous availability. [Official rules](https://youcam-api-skin-ai-ecommerce.devpost.com/rules) take precedence over this checklist.

Public project support: [GitHub Issues](https://github.com/CarsonCCHsieh/threadroom/issues). Report a nonsensitive description and browser/version only. Do not attach photos, API keys, signed URLs, task/session identifiers, account details or private logs. This is a public issue tracker, not a private channel for photo or account requests; see [privacy and operator cleanup](docs/PRIVACY.md).

## License

Original application code and documentation are available under the [MIT License](LICENSE). Bundled fonts retain their SIL Open Font License. Generated, photographic and user-supplied assets have separate provenance and rights conditions in [ASSETS.md](docs/ASSETS.md); the MIT license does not automatically relicense those assets or the YouCam service.
