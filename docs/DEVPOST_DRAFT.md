# Devpost draft — development version

**Not ready for final submission.** This English draft reflects a local implementation. API access, activity units, genuine try-on, browser verification and deployment have not yet been demonstrated. Replace this status only after collecting actual evidence; remove no limitation merely to make the pitch sound complete.

## Project name

THREADROOM

## Tagline

Compare something new with something you already love.

## Inspiration

Virtual try-on often ends with an interesting image. A buying decision needs more context: what the item might look like on the same person, how it compares with something already owned, and whether the expected use justifies the price.

THREADROOM explores that decision in a small fitting workspace. It keeps the original photo visible, makes the owned alternative part of the comparison, and leaves the final choice with the user.

## What it does

The interface is organized around one person photo and two garment choices. Garments can be marked as owned or under consideration. The user supplies a price, expected wears and a note; cost per wear is price divided by expected wears. The English, Traditional Chinese and Japanese interface includes consent and privacy explanations.

The integration is designed to send consented person and garment images through a private PHP proxy to Perfect Corp's YouCam `cloth-v3` API, then retrieve asynchronous task results for A/B and original/result comparison. This source has been implemented locally, but a real authenticated task has not yet been verified. The current sample imagery represents original inputs, not completed YouCam results.

The export code provides an explicit local notes download and, after at least one genuine output exists, a labelled comparison image. Any side without a result remains labelled as an original input. These downloads still require browser verification. There is no checkout, public gallery or automatic social posting.

## How we built it

The client uses HTML, CSS and JavaScript, with self-hosted DM Sans and Noto Sans typefaces. The backend is a small PHP service with server-only credentials, session-owned jobs and private result storage. It verifies available units and the account's feature cost before creating a task, reserves budget in a locked ledger and avoids automatically repeating an uncertain chargeable POST.

The work uses Codex for implementation assistance and OpenAI's built-in image generation tool for the three original synthetic sample inputs. Their prompts, creation date and input-only roles are documented in the asset provenance; they are not YouCam outputs. The original design combines a full-window fitting workspace, two-look comparison and a user-controlled decision panel. It does not reuse an existing completed YouCam submission. Exact contribution dates and final stack versions will be added from the release evidence rather than guessed.

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
| Session ownership, budget reservations and fake-provider tests | Real deployment and end-to-end privacy/error verification |
| Draft English documentation and asset register | Finished real demo, public code access and final submission receipt |

## What we learned

The implementation makes a practical distinction between a result existing in code and a result being proven with a real provider. It also exposed the need to reserve units for judging and to explain that clearing local data cannot promise immediate third-party deletion.

Do not turn this technical observation into an invented personal satisfaction score or survey answer. Any required first-person experience question must use the entrant's actual answer.

## What's next

Finish the genuine two-look workflow, verify English/Traditional Chinese/Japanese behavior, complete the image provenance register, and record an honest end-to-end demonstration. Broader wardrobe history, retailer integrations, durable accounts and measured impact studies are future directions; they are not current features.

## Intended consumer / eCommerce value

A small retailer or shopper could use a controlled comparison to discuss two garments with a clear original-photo baseline and user-supplied price context. Comparing with an owned item may support a more considered choice. No claim is made that the app has already reduced returns, increased sales, saved money or reduced emissions.

## Links — fill only when real

- Working demo or test-build instructions: pending
- Source repository: pending
- 1–3 minute public video: pending
- Final commit / release: pending

## Submission editing notes

Before publication, reconcile every sentence with [VERIFICATION.md](VERIFICATION.md), replace pending links and describe only the features demonstrated by the final build. Provide English testing instructions. Keep private account details and test credentials out of public prose; use an appropriate private testing field if one is necessary. Do not select a student or women-in-tech award category without the entrant's confirmed eligibility.
