# Devpost draft — development version

**Not ready for final submission.** Public source, successful CI and a deployed [hosted workspace](https://threadroom.microusada.com/) are recorded below. Origin/public HTTPS checks each passed 19/19 with live generation disabled. API access, activity units, genuine try-on, browser verification, a video and final submission remain pending. This is English copy for review, with explicit development limits. Update its verbs and links only after collecting actual evidence.

## Project name

THREADROOM

## Tagline

Compare something new with something you already love.

## Inspiration

Virtual try-on often ends with an interesting image. A buying decision needs more context: what the item might look like on the same person, how it compares with something already owned, and whether the expected use justifies the price.

THREADROOM explores that decision in a small fitting workspace. It keeps the original photo visible, makes the owned alternative part of the comparison, and leaves the final choice with the user.

## What it does

THREADROOM organizes the decision into three steps: choose two garments, compare them on the same person, and add the context that an image alone cannot supply. One garment represents a possible purchase and the other something already owned. The user supplies a price, expected wears and a note; cost per wear is price divided by expected wears. English, Traditional Chinese and Japanese interfaces include consent and privacy explanations.

The integration is designed to send consented person and garment images through a private PHP proxy to Perfect Corp's YouCam `cloth-v3` API, then retrieve asynchronous task results for A/B and original/result comparison. This source is implemented and deployed with generation disabled; a real authenticated task has not yet been verified. Public HTTP checks confirm delivery and the unconfigured backend state, rather than a working provider workflow. The current sample imagery represents original inputs, not completed YouCam results.

The export code provides an explicit local notes download and, after at least one genuine output exists, a labelled comparison image. Any side without a result remains labelled as an original input. These downloads still require browser verification. There is no checkout, public gallery or automatic social posting. The bundled person and garments are synthetic and fictional: the owned alternative is a scenario, not a claim of actual ownership or a retailer relationship.

## How we built it

The client uses HTML, CSS and JavaScript, with self-hosted DM Sans and Noto Sans typefaces. The backend is a small PHP service with server-only credentials, session-owned jobs and private result storage. It verifies available units and the account's feature cost before creating a task, reserves budget in a locked ledger and avoids automatically repeating an uncertain chargeable POST.

The work uses Codex for implementation assistance and OpenAI's built-in image generation tool for the three original synthetic sample inputs. Their prompts, creation date, input-only roles and observed file hashes are documented in the asset register; they are not YouCam outputs. The design combines a full-window fitting workspace, two-look comparison and a user-controlled decision panel. This is a new THREADROOM project, rather than a resubmission of an existing completed YouCam entry. Its initial public source baseline, [6a62a16](https://github.com/CarsonCCHsieh/threadroom/commit/6a62a1602821340278cac4e42e72dd8ba7551e19), was committed on 30 October 2026 at 02:16:42 Asia/Taipei. That timestamp establishes the commit, not that all third-party tools or assets were created during the event. The runtime requirement is PHP 8.2+; the final tested PHP/browser versions and release reference remain to be recorded.

## Challenges we addressed

- Making source images and real outputs visually distinct, so a sample cannot be mistaken for a live result.
- Treating network uncertainty as a billing problem: checking an existing task is safer than blindly creating another one.
- Keeping original uploads out of durable app runtime storage while explaining that a third-party processor still receives them.
- Making the purchase question meaningful without inventing a fit score, usage prediction or sustainability measurement.

The remaining technical challenges include confirming the correct account host, redeeming the activity units, validating real output and maintaining free judge access through the judging period.

## What is implemented, and what remains unverified

| Implemented locally / in source | Still required before final submission |
| --- | --- |
| Fitting interface, translations and decision controls | Full browser/mobile/accessibility review and final asset checks |
| YouCam upload/task/poll/result integration code | Account, host, units and a completed real provider task |
| Deployed workspace, session/budget code and passing origin/public HTTP checks | Real generation plus browser and end-to-end privacy/error verification |
| Public code, successful CI and English preparation documents | Final tested release, real demo/video, complete rights review and final submission receipt |

## What we learned

The implementation makes a practical distinction between a result existing in code and a result being proven with a real provider. It also exposed the need to reserve units for judging and to explain that clearing local data cannot promise immediate third-party deletion.

## What's next

Finish the genuine two-look workflow, verify English/Traditional Chinese/Japanese behavior, complete the remaining source/rights receipts, and record an honest end-to-end demonstration. Broader wardrobe history, retailer integrations, durable accounts and measured impact studies are future directions; they are not current features.

## Intended consumer / eCommerce value

A small retailer or shopper could use a controlled comparison to discuss two garments with a clear original-photo baseline and user-supplied price context. Comparing with an owned item may support a more considered choice. No claim is made that the app has already reduced returns, increased sales, saved money or reduced emissions.

## Links and current evidence

- Hosted workspace: [https://threadroom.microusada.com/](https://threadroom.microusada.com/) — reachable with generation disabled; working YouCam API access remains pending
- Source repository: [CarsonCCHsieh/threadroom — public](https://github.com/CarsonCCHsieh/threadroom)
- Application source: [a7a7a05](https://github.com/CarsonCCHsieh/threadroom/commit/a7a7a051111ee57f87985a3721320c559b75cfcf)
- CI for that application source: [run 40937515854 — success](https://github.com/CarsonCCHsieh/threadroom/actions/runs/40937515854); syntax, offline asset receipts, fake-provider and no-key HTTP checks passed, not live-generation or browser proof
- Deployed build: `6a62a16-r2`; a read-only comparison verified all 23 application files as byte-identical to commit `a7a7a05`; origin/public HTTP checks each passed 19/19 with zero provider calls
- English judge instructions: [JUDGE_GUIDE.md](JUDGE_GUIDE.md), prepared; browser behavior and genuine provider outcomes pending
- 1–3 minute public video: pending
- Final tested commit / release: pending browser/live verification and the actual video; the application-source commit alone is not a verified live release

## Submission editing notes

Before publication, reconcile every sentence with [VERIFICATION.md](VERIFICATION.md), replace pending links and describe only the features demonstrated by the final build. This draft revision is a later evidence update, not part of application-source commit `a7a7a05`. Ensure the final repository includes the guide and subsequent documentation and records their actual release reference. Keep private account details and test credentials out of public prose; use an appropriate private testing field if one is necessary. Do not select a student or women-in-tech award category without the entrant's confirmed eligibility, or turn the technical learning paragraph into an invented personal survey answer. Public support is [GitHub Issues](https://github.com/CarsonCCHsieh/threadroom/issues); do not post photos, keys or private account/task/session details there.

Source requirements: [official rules](https://youcam-api-skin-ai-ecommerce.devpost.com/rules), [free-unit resources](https://youcam-api-skin-ai-ecommerce.devpost.com/resources), [API terms](https://www.perfectcorp.com/perfectbeauty/youcam/terms-of-service-api). [ASSETS.md](ASSETS.md) and [PRIVACY.md](PRIVACY.md) preserve the distinction between input rights, public-video permission, code licensing and provider retention.
