# Verification record

Evidence boundary: documentation prepared on 30 October 2026 from the current source and the API/design briefs. This table separates code from proof. Update it only with actual outcomes and a redacted evidence reference.

| Milestone | Status | Evidence / remaining work |
| --- | --- | --- |
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
| Sample image provenance | Present | Three original synthetic inputs are recorded in `public/assets/provenance.json`; not YouCam outputs. Final release hashes remain to be archived. |
| Font sources and UI codepoints | CJK receipt present; browser rendering pending | Version-pinned Google Fonts sources and output hashes recorded; zero missing required UI codepoints in both CJK subsets. See `public/assets/fonts/font-receipt.json`. DM Sans version/hash receipt remains to be completed. |
| Public deployment | Not performed / not verified | No published URL or uptime claim. |
| Judge access through end of judging | Not verified | Need deployed access or a genuinely reproducible test build and enough remaining activity units. |
| Final video / public repository | Not published / not verified | No upload or repository creation was performed by this documentation task. |
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
