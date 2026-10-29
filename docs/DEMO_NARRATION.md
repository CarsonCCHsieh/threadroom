# Demo narration and publication template

**Not recorded or uploaded.** This is a complete English recording script for a target 2:15 film. The [hosted workspace](https://threadroom.microusada.com/) is deployed and passed origin/public HTTP checks with generation disabled. Live API results, browser verification and a public video URL remain pending. Read this script only over footage of the actual verified workflow: HTTP delivery does not make its generation/comparison claims true. Before recording, complete [the capture gates](DEMO_STORYBOARD.md#capture-checklist); after recording, correct the script and [subtitle template](DEMO_SUBTITLES.srt) to match what actually happened.

## Full narration

The timings are editing targets, not measured generation time. Speak naturally; shorten or retime the cut while keeping the final film between one and three minutes. Never stretch an error or placeholder into a success claim.

| Target time | Read aloud | Required picture / label |
| --- | --- | --- |
| 0:00–0:12 | Consider a shopper deciding whether to buy another shirt. THREADROOM compares that possibility with an owned alternative, in one fitting workspace. | Open directly into the app. Overlay: **Synthetic sample inputs · fictional person and garments**. |
| 0:12–0:28 | These sample garments represent the two choices. Both use the same fictional adult input. The navy shirt stands in for an item already in the wardrobe. No try-on has been generated yet. | Show both garment selections and **SAMPLE / ORIGINAL INPUT**. Do not label the samples as genuine owned products. |
| 0:28–0:43 | With permission to use these images, I confirm consent and start the try-on. The selected photos go through the private PHP server to YouCam's cloth-v3 clothing virtual try-on API. | Show actual consent and actual generation click. Keep keys, account screens and developer tools off screen. |
| 0:43–1:02 | YouCam processes the garments asynchronously. THREADROOM checks each existing task, rather than creating another when a response is slow. This edit shortens the processing wait. What follows must be the actual results from these inputs. | Capture the real queued/running states. Overlay **Processing time shortened** only if a wait is cut. If no time is cut, replace the edit sentence and matching subtitle with the observed wait. |
| 1:02–1:23 | Here are the two genuine YouCam outputs. I can compare A and B, then check either against the original. The labels keep input and AI output distinct. This is a visual impression, not a guarantee of size, comfort or physical fit. | Genuine completed outputs only. Show A/B, selected look, before/after and labels. If either output is missing, do not read this claim. |
| 1:23–1:41 | Now I add an illustrative price of sixty and thirty expected wears. Two per wear is simple division, based on these inputs. A note keeps the decision personal. The app does not predict habits or tell anyone what to buy. | Enter 60 / 30 in the selected currency, show actual result and a short nonsensitive note. No real retailer price claim. |
| 1:41–1:56 | The workspace includes English, Traditional Chinese and Japanese. Saving notes or a comparison image is an explicit local download. It does not publish a photo or create a public gallery. | Switch languages and demonstrate only exports that passed real browser verification. Do not expose a desktop username in a download dialog. |
| 1:56–2:09 | Clear removes this page's data and requests server cleanup. Pending cleanup stays visible. Provider retention still applies, and downloaded files remain on the device. | Show Privacy, then actual Clear confirmation and resulting status. Do not replace a pending/manual message with an erasure claim. |
| 2:09–2:15 | THREADROOM. Compare something new with something you already love. The working demo and source are in the description. | Use the final sentence only after a working demo is verified and its real URL is included. Otherwise do not release the film. |

The [SRT file](DEMO_SUBTITLES.srt) mirrors this script with provisional cues. Do not upload those cues unchanged: retime to the final audio, check each line on a mobile-size player, and remove production notes from final visible subtitles. If the real cut is different, update the script, SRT and Devpost text together.

## Recording settings and read-through

- Capture the verified target browser at 1920×1080 or another clearly legible 16:9 size. Record the actual browser/version, viewport, release and date in the private capture log; add only nonsensitive facts to [VERIFICATION.md](VERIFICATION.md).
- Use EN for the main flow; show the real 繁中 and 日本語 interfaces briefly. Keep application text readable at normal playback size, use a visible pointer and avoid rapid zooming. Confirm the whole window fits before recording.
- Hide notifications, bookmarks, account/avatar menus, terminal history, personal folders and developer tools. Never capture a provider console or full-screen desktop when the application window will do.
- Record the genuine wait once. A cut may shorten that same task; show **Processing time shortened** over the cut. Do not invent elapsed seconds or progress percentages, splice a different input/result pair, or hide a failed result.
- Use clean narration without unlicensed music. Any additional artwork, music or footage needs a documented source and permission. The bundled sample inputs and genuine provider outputs have separate provenance; neither becomes MIT merely because the code is MIT.
- Watch the final render end to end. Confirm English captions, original/output labels, public-use rights, no private details, 1–3 minute duration, real links and agreement with the submitted release before requesting publication.

## Video title

`THREADROOM — Compare before you buy | YouCam API Hackathon`

## Video description — fill after real capture

The following is a template. Replace the bracketed fields with actual verified references; do not publish brackets, a nonexistent demo or a guessed release.

```text
THREADROOM is a wardrobe decision workspace: compare a possible purchase with an owned alternative on the same person, then add your own price, expected wears and notes.

Hosted workspace: https://threadroom.microusada.com/
Working API / free judge access through judging: [VERIFY BEFORE PUBLICATION]
English testing guide: [GUIDE URL IN THE PUBLISHED FINAL RELEASE]
Source: https://github.com/CarsonCCHsieh/threadroom
Recorded release: [ACTUAL COMMIT URL]
Recording date / browser: [ACTUAL NONSENSITIVE DETAILS]

The recording shows genuine YouCam cloth-v3 upper-body virtual try-on results generated from the displayed inputs. The sample person and garments are synthetic, fictional original inputs; they are not retail products or pre-generated YouCam results. [STATE WHETHER A PROCESSING WAIT WAS SHORTENED.]

AI output is a visual impression, not a size, comfort or physical-fit guarantee. Prices and expected wears are illustrative user inputs. No measured savings, return reduction or environmental impact is claimed. There is no checkout or public photo gallery.

Asset and font provenance: [ASSETS.MD URL IN FINAL RELEASE]
Privacy, provider retention and cleanup limits: [PRIVACY.MD URL IN FINAL RELEASE]
Code/docs: MIT. Fonts: SIL OFL 1.1. Images and service outputs retain their separate applicable conditions.

Public project support: https://github.com/CarsonCCHsieh/threadroom/issues
Do not post photos, keys, signed URLs, task/session identifiers, account details or private logs in public issues.
```

YouCam's [official rules](https://youcam-api-skin-ai-ecommerce.devpost.com/rules) call for an actual 1–3 minute public demonstration on YouTube (preferred) or Vimeo, with English material or complete English translation and an explanation of the API use. This document does not authorize uploading a new video. Keep the actual upload confirmation and final playable URL before marking the video complete.
