# Privacy and data retention

Development disclosure, 30 October 2026. Three real local tasks and two real public-site tasks processed cleared synthetic person/garment inputs. Local desktop comparisons/downloads and the public two-look result path were exercised; no personal photo was needed. The public sample path is live-enabled and limited, with no visitor account, provider key or payment required. Use only cleared synthetic samples until a private support path and retention/cleanup verification are established. Browser Clear did not visibly complete, so end-to-end cleanup remains unverified; a foreign-session 404 establishes ownership isolation only. An independent post-activation HTTPS check passed 19/19, including seven private-path 404s and secure cookie/no-store checks; those observations are not deletion or erasure proof. [VERIFICATION.md](VERIFICATION.md) separates the bounded browser evidence from HTTP checks.

## What happens to a photo

| Stage | Data and location | Lifetime / limitation |
| --- | --- | --- |
| Selecting a photo | The interface prepares a preview in browser memory. Client code is intended to resize and re-encode images, removing EXIF metadata. | No intentional photo persistence in localStorage or an account. Reloading loses unsaved page state. The bounded desktop flow does not establish every storage or browser behavior. |
| Starting a try-on | After the explicit consent checkbox and generation action, person and garment images are sent to this app's same-origin PHP endpoint, then to YouCam. | Images leave the device. This is not an entirely local processing pipeline. |
| PHP upload processing | PHP uses its temporary-upload lifecycle; application code validates the bytes and strips metadata. Original uploads are not copied into the application runtime directory. | Request memory and PHP temporary files are involved. Do not promise that the bytes never touch a server or temporary disk. |
| Provider processing | YouCam receives sanitized person and garment images and creates an asynchronous `cloth-v3` task. | YouCam's service terms and retention apply independently of the browser and app. |
| Completed result | The app downloads a validated result into nonpublic server storage and serves it only to its owning session. | Current code retains the local result until Clear or operator cleanup. There is no automatic app-level TTL yet. |
| Job and budget metadata | Private ledger contains opaque owner/job identifiers, request hashes, timestamps, task IDs, status and unit reservations. | Records persist after Clear to preserve duplicate prevention and budget accounting. No automatic metadata expiry is implemented. |
| Explicit downloads | A notes JSON or comparison image is downloaded only on user action. | The user controls the downloaded file. Clearing the app cannot remove it from the device or from places where it was subsequently shared. |

Cost, garment labels and personal notes are decision inputs, not biometric or fit measurements. The observed 749-byte notes export contained no images or URLs. That one result does not replace review of later exports. Never place sensitive personal details in notes that will be shared or used in a public demo.

## Clear session

In source, Clear removes the current page's photos, notes and result references and requests `DELETE /api/session`. The server revokes access to the session's jobs, attempts local result deletion, and requests deletion of completed provider tasks where their IDs are known.

The local correction tested on 1 November 2026 retains the private filename when deletion fails, returns HTTP 503 with a separate `localCleanupPending` count, and permits the same session to retry Clear. The existing interface then shows that server deletion could not be confirmed and asks for a retry; it does not show the success notice. A missing file is accepted only after successful unlink or a readable parent-directory listing establishes its absence. Failed discarded-worker files also retain private retry references. Later health requests retry at most two due local jobs, including when no provider key is configured; repeated local failures have a ten-second backoff. This is request-driven, not a background process or an automatic retention deadline.

Local and provider cleanup states remain separate. An HTTP 200 Clear establishes local cleanup for that request, while `providerCleanupPending` / `providerCleanupManual` may still disclose outstanding provider work. An absent key, failed transport, unreadable response or rejected provider response cannot set `providerDeleted`; retry references and accounting remain. A provider API acknowledgement is not independent proof of complete provider erasure. The correction has offline and isolated no-key HTTP evidence and was deployed on 1 November 2026; all 23 application files matched the reviewed commit, with private configuration, ledger, reserved units and both existing results unchanged. The user resolved the earlier Chrome endpoint block, and browser health returned the existing session task-limit status. Actual public-session cleanup remains unverified; the existing synthetic demonstration results are being retained. Endpoint access and source deployment do not establish deletion.

The historical browser attempt did not visibly finish; end-to-end Clear is unverified. Retaining the cleared synthetic results is an explicit choice and does not prevent submission or demonstration. A request from a fresh foreign session returned 404, which proves that session could not read the result. It does not prove the original session was revoked, its files were removed or the provider erased data.

Running tasks cannot be promised to disappear immediately. They are marked for cleanup; later health/start requests revisit a bounded number of them. This is request-driven cleanup, not a background worker. If no further requests occur, provider retention still applies. An uncertain provider POST without a returned task ID may not be deletable by this app. Failed cleanup must remain visible rather than being represented as confirmed erasure.

Clearing does not reset the budget ledger, erase user downloads or override provider retention. Browser session expiry also does not establish that all private server files have been erased. An operator must review and implement an appropriate cleanup schedule before accepting public personal photos at scale.

## Provider retention

The [YouCam file-retention documentation](https://docs.perfectcorp.com/develop/file_retention_period) reviewed during API research describes uploads/tasks/results being retained for up to 30 days and result download URLs expiring after two hours. Some endpoint schema text still mentions a shorter task lifetime. These are provider statements with inconsistent details, not an app guarantee. The app therefore downloads results promptly and does not promise immediate or complete provider erasure.

The [YouCam API Terms of Service](https://www.perfectcorp.com/perfectbeauty/youcam/terms-of-service-api), reviewed in the version labelled September 2026, apply separately from this app's code license. Section 3.4 addresses authorization for person photos; section 11.2 addresses rights to submitted content; section 12 addresses lawful personal-data processing, valid privacy notices and retained consent. The terms state that submitted content is not used for service improvement without opt-in; that is not a general claim about all processing, storage locations or subprocessors. Verify the account's actual applicable terms before processing real personal photos. See also [Perfect Corp privacy information](https://www.perfectcorp.com/consumer/pages/privacy).

## Controls in source

Session-owned jobs; CSRF protection; same-origin mutation checks; private no-store responses; server-only credentials; image validation and metadata stripping; approved provider/storage hosts; and no automatic retry of an uncertain chargeable task are implemented in source. They are not a substitute for live verification or an independent security audit.

Production access/error logs, PHP temporary storage, reverse proxies and backups are deployment responsibilities. Do not log image bodies, Authorization headers, signed result URLs or raw provider responses. HTTP infrastructure may still record ordinary request metadata such as IP addresses and times; this project does not claim anonymous networking.

Use only adult photos you are authorized to process. Avoid sensitive images and images of children. The app has no public gallery and does not intentionally publish user photos, but consent to generation is not consent to include a photo in a demo video or public repository.

## Support and data requests

Public project support is available through [GitHub Issues](https://github.com/CarsonCCHsieh/threadroom/issues). Issues are public. Describe the general problem and browser/version without uploading photos, API keys, signed URLs, provider task IDs, session identifiers, account details or private logs. Do not ask a user to prove ownership by posting an image or identifier there.

No private operator inbox or secure photo-request intake has been established in this record. An issue may ask the operator to arrange an appropriate private channel without including personal data, but the issue tracker itself is not that channel. A suitable private contact path and verified request procedure must be supplied before accepting public personal photos. Until then, use only the cleared synthetic sample inputs for demonstrations. A public repository is not permission to publish users' photos or consent records.

## Operator cleanup runbook — manual, not a scheduler

This procedure documents how to operate the existing controls; it does not install a scheduled job, implement an automatic TTL or establish a service-level deletion promise. **No cleanup cadence, assigned operator or enabled cron is verified.** Current app results persist until Clear or actual operator action; private job/budget records do not automatically expire.

1. **Before a demonstration:** confirm that only cleared synthetic inputs will be used, that runtime remains outside the webroot, and that the operator can access private storage and the official provider account securely. Do not print private ledgers or account screens. Record the release/date and a nonsensitive start status in an operational receipt; keep task-level records private.
2. **When cleanup is requested:** use that session's **Clear session**, confirm it and record the actual outcome. Verify removal of page state and revocation of previously obtained local result access. If the server cannot confirm cleanup, retain the failure status and retry only within the requested cleanup scope. Cleared synthetic demonstration results may be retained; cleanup is not a submission or showcase prerequisite.
3. **If known provider tasks remain pending:** with valid provider configuration retained, revisit the normal health flow (`GET /api/health`) to let its bounded reconciliation check known tasks and retry deletion after completion. Health skips provider work when the installation is unconfigured; in the locally corrected source it can still retry due local deletions. Do not create a new try-on to force cleanup, and do not assume a single visit drains all outstanding work. Review the remaining cleanup state privately and confirm the session's actual Clear response; task polling alone is not an app-wide cleanup pass. This is request-driven; no background process is promised.
4. **If a task has no confirmed provider ID:** keep its reservation and uncertain status. Reconcile privately in the official provider account, using the actual operation evidence. Do not guess an ID, blindly repeat a chargeable POST or declare provider erasure. Where the app has no safe administrative command, a code-reviewed maintenance procedure is still required; this document does not supply an untested ledger-edit command.
5. **Review local leftovers privately:** check for retained local result files, completed-but-not-cleared sessions, PHP temporary storage, logs, cache and backups. There is no general admin cleanup tool or cross-session TTL in this build. Do not recursively delete the runtime directory or remove `ledger.json`/its lock: that would destroy ownership, duplicate-prevention and budget evidence. Any future maintenance tool must preserve accounting and be verified before use.
6. **Record an honest result:** capture only release/date, what was actually checked, local cleanup pass/fail, outstanding cleanup count and whether private reconciliation is needed. Keep identities, task IDs, photos and raw responses outside Git. Provider retention may still apply even after a successful local clear. User downloads remain under the user's control.

Before enabling public personal-photo processing, the operator must choose and document an actual retention period and cleanup review frequency, assign responsibility, verify the mechanism on the deployed release and establish a suitable private contact channel. Record those facts when real; a proposed daily review or future cron must not be labelled active. Until then, this build makes no app-wide deletion deadline or automatic-expiry claim.

## Before public use

- Verify the actual provider policy and account terms, the app's data flow and Clear behavior.
- Use the public support channel only for nonsensitive reports; establish a suitable private channel and a verified retention/cleanup schedule before accepting public personal photos. No private inbox or schedule is asserted here.
- Configure upload temporary storage, logs, caching and private runtime access appropriately.
- Use only cleared sample imagery in the public demo; obtain separate permission for any identifiable person's public appearance.

Official references: [event rules](https://youcam-api-skin-ai-ecommerce.devpost.com/rules), [API terms](https://www.perfectcorp.com/perfectbeauty/youcam/terms-of-service-api), [provider retention](https://docs.perfectcorp.com/develop/file_retention_period). These provider statements do not prove the deployed app's cleanup behavior; record actual checks in [VERIFICATION.md](VERIFICATION.md).
