# Asset sources and licenses

This is a provenance register, not a blanket license grant. Original application code/docs are MIT. Fonts, generated images, photographic inputs, provider outputs and user uploads retain their own rights conditions.

## Bundled fonts

| File | Source / attribution | License | Verification |
| --- | --- | --- | --- |
| `public/assets/fonts/DMSans-Variable.ttf` | DM Sans Project Authors; [project upstream](https://github.com/googlefonts/dm-fonts), [pinned Google Fonts distribution](https://github.com/google/fonts/tree/9862abc066c9b83bf84c3e8ed1fc2d8d48f9bbf0/ofl/dmsans) | SIL OFL 1.1; preserve `DMSans-OFL.txt` | [DM Sans receipt](../public/assets/fonts/dm-sans-receipt.json) records exact font/notice byte identity with the pinned official source, SHA-256 and version 4.004; it does not claim a historical acquisition date. |
| `public/assets/fonts/NotoSansTC-UI-Variable.woff2` | Noto Sans Traditional Chinese; supplied notice credits Adobe, with Reserved Font Name “Source”; [Google Fonts source](https://github.com/google/fonts/tree/main/ofl/notosanstc) | SIL OFL 1.1; preserve `NotoSansTC-OFL.txt` | Version-pinned source, output hash and zero missing required UI codepoints recorded in the receipt. |
| `public/assets/fonts/NotoSansJP-UI-Variable.woff2` | Noto Sans Japanese; supplied notice credits Adobe, with Reserved Font Name “Source”; [Google Fonts source](https://github.com/google/fonts/tree/main/ofl/notosansjp) | SIL OFL 1.1; preserve `NotoSansJP-OFL.txt` | Version-pinned source, output hash and zero missing required UI codepoints recorded in the receipt. |

Keep the actual accompanying notices. Font files are not relicensed as MIT. [The CJK font receipt](../public/assets/fonts/font-receipt.json) records immutable Google Fonts source URLs, SHA-256 hashes, 400–700 weight ranges, and 470 Traditional Chinese / 420 Japanese required codepoints with none missing. This checks the current UI character set, not arbitrary user text or actual browser rendering. Verify fallback behavior. If regenerating subsets with `python tools/rebuild-fonts.py`, update the receipt and preserve applicable naming conditions; `--check` verifies the existing fonts without downloading or writing files.

## Image register

| Asset / intended role | Origin | Rights / public-use evidence | Actual status |
| --- | --- | --- | --- |
| `public/assets/model-original.png` | Created with OpenAI's built-in image generation tool; fictional adult, no real person's photograph used | Project-created synthetic input; prompt/date/tool recorded in the linked provenance. Subject to applicable generation-service terms; not automatically MIT. | Supplied and visually reviewed by the coordinating work; original input only, never a YouCam output |
| `public/assets/linen-shirt.png` | Project-created synthetic ivory linen shirt | Same provenance; fictional/sample garment, no retailer partnership or purchasable-product claim | Supplied and visually reviewed; garment input only |
| `public/assets/navy-overshirt.png` | Project-created synthetic navy cotton overshirt | Same provenance; fictional/sample garment, no retailer partnership or purchasable-product claim | Supplied and visually reviewed; garment input only |
| Real try-on A/B outputs | Must come from completed YouCam tasks using cleared inputs | Apply the API-terms review below and document public-demo permission for any input person; no automatic MIT grant | Not generated / verified |
| `tests/fixtures/result.jpg` | Backend test fixture: generated plain-color 512×512 image | Original nonpersonal test data; a testing fixture, not a fashion/API result | Source described in backend docs; local hash recorded below |
| Inline app SVG marks/icons | Project source | Original code under MIT to the extent created for this project | Review final source and ensure no copied brand marks |

Do not infer image rights from an absent watermark, a search result or availability on a retail site. Do not claim a sample is a purchasable product or affiliated with a shop unless that is true and documented. Sample prices are fictional examples, not offers.

[Image provenance](../public/assets/provenance.json) records the creation date, tool, full prompts and input-only roles for all three original sample assets. No external source URL is invented for these project-generated files. The person and garments are fictional: an “owned alternative” in narration is a scenario role, not a claim of real ownership, retail availability or affiliation. Keep that disclosure in the final video.

### Local-file integrity record — observed 30 October 2026

These SHA-256 values identify the observed bytes. They do not establish a license, prove an upstream source, or turn a synthetic input into a provider output. Recheck changed files when preparing the final release; a later output needs its own receipt.

| Relative file | SHA-256 |
| --- | --- |
| `public/assets/model-original.png` | `befee3a3e686e7fd62328847e967a66e002093dd92fd07aba154f9ee5dd88c82` |
| `public/assets/linen-shirt.png` | `f5dd7e94c1f4cb9c939e8e2a8660eb6dffc625d3c2b203859774b27ab6b7abce` |
| `public/assets/navy-overshirt.png` | `8d37d53e3c6c530dab5484686224cf119ed627e7564f6806d11e0b644e405d9b` |
| `tests/fixtures/result.jpg` | `8c0274493dcb1d6fba0043fb9a4ce3021111554245478555f3a5febe07beaddb` |
| `public/assets/fonts/DMSans-Variable.ttf` | `8cd08d97e89c24d0aa92edd2f0f4c8ee6195eee9b7c9f154865a58b02f0c1c0d` |

The [asset receipt](../public/assets/asset-receipt.json) records the four image/fixture files, and the [DM Sans receipt](../public/assets/fonts/dm-sans-receipt.json) pins the font and adjacent OFL notice to official Google Fonts commit `9862abc066c9b83bf84c3e8ed1fc2d8d48f9bbf0`. Its recorded comparison establishes current byte identity, not an invented download history. The CJK font receipt separately pins its source and output hashes. `python tools/check-assets.py` verifies the local image, DM Sans and provenance receipts offline; it does not fetch sources, alter assets or establish output rights. Its result is not live API or browser evidence.

## YouCam outputs, input permission and public use

The [YouCam API Terms of Service](https://www.perfectcorp.com/perfectbeauty/youcam/terms-of-service-api), version labelled September 2026 reviewed for this package, distinguish service access from rights in content. Section 3.4 requires appropriate authorization for person photos; section 11.2 addresses submitted-content rights; section 3.5.3 says Perfect Corp does not claim ownership of AI output; section 11.3 states that commercial use of AI output is not restricted while leaving input/output intellectual-property responsibility with the user. Section 12 addresses lawful personal-data processing and consent. Confirm the account's actual applicable terms before generation and publication.

Those provisions are not a guarantee that every input/output is free of third-party rights, an automatic MIT license for an image, or permission to publish a real person's likeness. Separately verify input rights, processing consent and permission for each intended public use: app sample, repository redistribution and demo video. Keep any real person's consent evidence private. Do not copy retailer product photos merely because they are publicly reachable.

After actual generation, create a separate redacted output receipt with release/date, engine, actual completion, corresponding input hashes and output hash, observed unit-cost outcome and permitted public uses. Exclude provider task IDs, signed URLs, credentials, account details, raw responses and private personal input photos. The three bundled sample files and plain-color fixture cannot substitute for that receipt.

## Per-image receipt template

```text
Relative filename:
SHA-256:
Role: original person input / garment input / genuine provider output / decorative / test fixture
Origin: project-created / generated / licensed source / user-supplied
Source URL or nonprivate receipt reference:
Creator / tool / model / relevant version:
Created or obtained date:
Generation/edit notes (if applicable):
License or applicable terms:
Allowed uses: app demo / repository redistribution / public video
Person consent status (if applicable; do not publish the private consent document):
Attribution required:
Verification status / reviewer:
```

For generated assets, retain an appropriate receipt and disclose that origin; do not automatically extend the code's MIT license to images. For real YouCam outputs, record enough redacted provenance to distinguish actual tasks from mock fixtures without exposing task IDs, signed URLs, credentials or personal input photos. Keep private consent records outside the public repository.
