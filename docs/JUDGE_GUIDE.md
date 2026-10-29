# Judge guide — English

**Preparation version, 30 October 2026.** The public source, CI and [hosted workspace](https://threadroom.microusada.com/) are available. Origin/public HTTPS checks each passed 19/19, with live generation disabled and unconfigured health verified. Browser/device behavior, genuine YouCam generation and the final video remain pending. This guide separates what can be inspected now from the live workflow that still needs proof.

## Access and evidence

| Item | Current reference |
| --- | --- |
| Hosted entry | [https://threadroom.microusada.com/](https://threadroom.microusada.com/) — deployed, live generation disabled; public HTTP checks passed at 03:00:14 Asia/Taipei on 30 October 2026 |
| Source | [CarsonCCHsieh/threadroom](https://github.com/CarsonCCHsieh/threadroom) — public |
| Initial source baseline | [main 6a62a16](https://github.com/CarsonCCHsieh/threadroom/commit/6a62a1602821340278cac4e42e72dd8ba7551e19), committed 30 October 2026, 02:16:42 Asia/Taipei |
| CI | [Run 40929341613 — success](https://github.com/CarsonCCHsieh/threadroom/actions/runs/40929341613); fake-provider and no-key checks do not demonstrate a live provider task |
| Deployed build / final tested release | Working-tree build `6a62a16-r2`, 23 application files based on the initial baseline plus later asset receipts. Complete final Git commit and actual browser/device verification remain pending; see [VERIFICATION.md](VERIFICATION.md). |
| Public demo video | Pending real recording and upload; [storyboard](DEMO_STORYBOARD.md) and [narration](DEMO_NARRATION.md) are preparation only |

The final entry must provide free, functioning judge access through **21 November 2026, 00:45 Asia/Taipei**. Judges should not have to buy units or supply a personal provider key. The entry is publicly reachable now, but working API access through judging has not been verified. A source review or the currently disconnected hosted workspace cannot satisfy the working YouCam integration requirement by itself. See the [official rules](https://youcam-api-skin-ai-ecommerce.devpost.com/rules).

## What to try

Use the bundled synthetic samples for the first pass. The sample person is a fictional adult; the two garments are fictional inputs with no shop affiliation. “Owned alternative” describes the shopping scenario, not a claim that the entrant owns or sells these items. A result must be labelled as a YouCam output only after an actual completed provider task.

**Current access:** open the hosted entry to inspect the delivered interface and source. HTTP checks verified an unconfigured, live-disabled backend; browser interaction is still to be tested. Do not upload a personal photo or expect a generated result in this state. The generation-dependent steps below are a future test procedure, to run only after the operator verifies provider access and updates this status.

1. Open [the hosted entry](https://threadroom.microusada.com/). The delivered root page is the fitting workspace; its browser rendering still needs review. Select **EN** in the language control. Read **The idea** and **Privacy** if you want context before generating. The connection indicator should truthfully show whether live generation is available. **Try-on not connected** is expected for the current verified backend state; it is not a passing live test. Record the actual browser outcome.
2. Keep the bundled person input and inspect the two selected garments: **A new possibility** and **An owned alternative**. Select look A or B, then choose a garment in **The wardrobe** to change that slot. Two different garments are required. Before generation the central image should say **SAMPLE / ORIGINAL INPUT**, with no completed-result claim.
3. Read the photo-consent checkbox. Continue only with cleared inputs you are entitled to process; no personal photo is needed for the sample test. Check consent and select **Try these two looks**. The app sends the person/garment images through the same-origin PHP server to YouCam `cloth-v3` for upper-body virtual try-on. This is third-party processing, not an entirely local workflow.
4. Wait for the actual task states and completed outputs. Keep the page open. If a task takes longer, use **Check status** to revisit that task rather than repeatedly starting a new one. Do not interpret the unchanged input image, queued state, a timeout or a fixture as a completed result. If one look fails, note the partial outcome and use its available recovery control; do not create unnecessary duplicate jobs.
5. After genuine results arrive, select **A / B compare**, then **Before / after**. Switch the selected look and move the comparison control. Check that original-input and **YOUCAM / AI-GENERATED TRY-ON** labels remain distinct. The output is a visual impression; it does not establish size, comfort, fabric behavior or physical fit.
6. In **Worth a place?**, enter an illustrative price and **Expected wears** for the selected garment. For example, 60 divided by 30 wears should show 2 per wear in the selected currency. These are user inputs and arithmetic, not a live offer, a usage forecast or measured savings. Add a nonsensitive note such as “Compare with the shirt already in the wardrobe.”
7. Select **Save notes**. Inspect the local JSON: it should contain garment labels, estimates and notes, without photo bytes or result URLs. After at least one genuine result exists, try **Save comparison**. The download should identify AI visualisation and any side that is still an original input. Saving is explicit; nothing should be posted publicly.
8. Switch the language control to **繁中** and **日本語**, then return to **EN**. Check that controls, consent and source/result labels remain usable, including keyboard focus and narrow-screen layout. A translation-key check alone is not browser verification.
9. Select **Clear session**, read the confirmation and confirm. The page should remove its photos, notes and result references and request local server cleanup. Observe the actual message. Pending provider cleanup or an unknown task must remain disclosed; Clear does not erase user downloads or guarantee immediate provider deletion.

Every observed result still needs to be recorded against the final release. If a planned control fails, report the failure and update the submission prose instead of assuming the expected outcome.

## Trouble and support

For a nonsensitive bug report, use [GitHub Issues](https://github.com/CarsonCCHsieh/threadroom/issues). Include the public release reference, browser/version, general step and visible error text after checking it for private information. Do not upload photos, keys, account or session identifiers, provider task IDs, signed URLs, raw responses or private logs. GitHub Issues is public and is not a private data-request channel.

For a failed or uncertain generation, stop submitting new tasks and preserve only a nonsensitive description. The operator must reconcile the task privately. Do not change host, buy credits or reset a ledger to force a result. A suitable private contact path for personal-photo requests must be established before opening the service to public personal photos; it is not supplied by this guide.

Developers can inspect the no-key interface using [RUNNING.md](RUNNING.md). [Privacy and cleanup](PRIVACY.md), [asset conditions](ASSETS.md) and the [verification record](VERIFICATION.md) state the current limits.
