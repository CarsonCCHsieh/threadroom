# Privacy and data retention

Development disclosure, 30 October 2026. This document describes the current source and the intended deployed workflow. Public deployment and real provider processing have not yet been verified. It is not a claim that every operational control has been tested.

## What happens to a photo

| Stage | Data and location | Lifetime / limitation |
| --- | --- | --- |
| Selecting a photo | The interface prepares a preview in browser memory. Client code is intended to resize and re-encode images, removing EXIF metadata. | No intentional photo persistence in localStorage or an account. Reloading loses unsaved page state. Browser behavior still needs verification. |
| Starting a try-on | After the explicit consent checkbox and generation action, person and garment images are sent to this app's same-origin PHP endpoint, then to YouCam. | Images leave the device. This is not an entirely local processing pipeline. |
| PHP upload processing | PHP uses its temporary-upload lifecycle; application code validates the bytes and strips metadata. Original uploads are not copied into the application runtime directory. | Request memory and PHP temporary files are involved. Do not promise that the bytes never touch a server or temporary disk. |
| Provider processing | YouCam receives sanitized person and garment images and creates an asynchronous `cloth-v3` task. | YouCam's service terms and retention apply independently of the browser and app. |
| Completed result | The app downloads a validated result into nonpublic server storage and serves it only to its owning session. | Current code retains the local result until Clear or operator cleanup. There is no automatic app-level TTL yet. |
| Job and budget metadata | Private ledger contains opaque owner/job identifiers, request hashes, timestamps, task IDs, status and unit reservations. | Records persist after Clear to preserve duplicate prevention and budget accounting. No automatic metadata expiry is implemented. |
| Explicit downloads | A notes JSON or comparison image is downloaded only on user action. | The user controls the downloaded file. Clearing the app cannot remove it from the device or from places where it was subsequently shared. |

Cost, garment labels and personal notes are decision inputs, not biometric or fit measurements. Notes exports are intended to contain text and numeric metadata, not photos or provider result URLs. Never place sensitive personal details in notes that will be shared or used in a public demo.

## Clear session

The page clears its current photos, notes and result references and requests `DELETE /api/session`. The server marks the session's jobs deleted, removes its local result files, and requests deletion of completed provider tasks where their IDs are known.

Running tasks cannot be promised to disappear immediately. They are marked for cleanup; later health/start requests revisit a bounded number of them. This is request-driven cleanup, not a background worker. If no further requests occur, provider retention still applies. An uncertain provider POST without a returned task ID may not be deletable by this app. Failed cleanup must remain visible rather than being represented as confirmed erasure.

Clearing does not reset the budget ledger, erase user downloads or override provider retention. Browser session expiry also does not establish that all private server files have been erased. An operator must review and implement an appropriate cleanup schedule before accepting public personal photos at scale.

## Provider retention

The [YouCam file-retention documentation](https://docs.perfectcorp.com/develop/file_retention_period) reviewed during API research describes uploads/tasks/results being retained for up to 30 days and result download URLs expiring after two hours. Some endpoint schema text still mentions a shorter task lifetime. These are provider statements with inconsistent details, not an app guarantee. The app therefore downloads results promptly and does not promise immediate or complete provider erasure.

See [Perfect Corp privacy information](https://www.perfectcorp.com/consumer/pages/privacy) and the account's applicable API terms before using real personal photos. Do not infer a particular training, jurisdiction or subprocessors policy from this document.

## Controls in source

Session-owned jobs; CSRF protection; same-origin mutation checks; private no-store responses; server-only credentials; image validation and metadata stripping; approved provider/storage hosts; and no automatic retry of an uncertain chargeable task are implemented in source. They are not a substitute for live verification or an independent security audit.

Production access/error logs, PHP temporary storage, reverse proxies and backups are deployment responsibilities. Do not log image bodies, Authorization headers, signed result URLs or raw provider responses. HTTP infrastructure may still record ordinary request metadata such as IP addresses and times; this project does not claim anonymous networking.

Use only adult photos you are authorized to process. Avoid sensitive images and images of children. The app has no public gallery and does not intentionally publish user photos, but consent to generation is not consent to include a photo in a demo video or public repository.

## Before public use

- Verify the actual provider policy and account terms, the app's data flow and Clear behavior.
- Set a real operator contact and a documented retention/cleanup schedule. Those are not yet supplied in this development build.
- Configure upload temporary storage, logs, caching and private runtime access appropriately.
- Use only cleared sample imagery in the public demo; obtain separate permission for any identifiable person's public appearance.
