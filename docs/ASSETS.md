# Asset sources and licenses

This is a provenance register, not a blanket license grant. Original application code/docs are MIT. Fonts, generated images, photographic inputs, provider outputs and user uploads retain their own rights conditions.

## Bundled fonts

| File | Source / attribution | License | Verification |
| --- | --- | --- | --- |
| `public/assets/fonts/DMSans-Variable.ttf` | DM Sans Project Authors; [upstream](https://github.com/googlefonts/dm-fonts) | SIL OFL 1.1; preserve `DMSans-OFL.txt` | Notice present; record source version/hash before release. |
| `public/assets/fonts/NotoSansTC-UI-Variable.woff2` | Noto Sans Traditional Chinese; supplied notice credits Adobe, with Reserved Font Name “Source”; [Google Fonts source](https://github.com/google/fonts/tree/main/ofl/notosanstc) | SIL OFL 1.1; preserve `NotoSansTC-OFL.txt` | Version-pinned source, output hash and zero missing required UI codepoints recorded in the receipt. |
| `public/assets/fonts/NotoSansJP-UI-Variable.woff2` | Noto Sans Japanese; supplied notice credits Adobe, with Reserved Font Name “Source”; [Google Fonts source](https://github.com/google/fonts/tree/main/ofl/notosansjp) | SIL OFL 1.1; preserve `NotoSansJP-OFL.txt` | Version-pinned source, output hash and zero missing required UI codepoints recorded in the receipt. |

Keep the actual accompanying notices. Font files are not relicensed as MIT. [The CJK font receipt](../public/assets/fonts/font-receipt.json) records immutable Google Fonts source URLs, SHA-256 hashes, 400–700 weight ranges, and 470 Traditional Chinese / 420 Japanese required codepoints with none missing. This checks the current UI character set, not arbitrary user text or actual browser rendering. Verify fallback behavior. If regenerating subsets with `python tools/rebuild-fonts.py`, update the receipt and preserve applicable naming conditions; `--check` verifies the existing fonts without downloading or writing files.

## Image register

| Asset / intended role | Origin | Rights / public-use evidence | Actual status |
| --- | --- | --- | --- |
| `public/assets/model-original.png` | Created with OpenAI's built-in image generation tool; fictional adult, no real person's photograph used | Project-created synthetic input; prompt/date/tool recorded in the linked provenance. Subject to applicable generation-service terms; not automatically MIT. | Supplied and visually reviewed by the coordinating work; original input only, never a YouCam output |
| `public/assets/linen-shirt.png` | Project-created synthetic ivory linen shirt | Same provenance; fictional/sample garment, no retailer partnership or purchasable-product claim | Supplied and visually reviewed; garment input only |
| `public/assets/navy-overshirt.png` | Project-created synthetic navy cotton overshirt | Same provenance; fictional/sample garment, no retailer partnership or purchasable-product claim | Supplied and visually reviewed; garment input only |
| Real try-on A/B outputs | Must come from completed YouCam tasks using cleared inputs | Review applicable service/output terms; public-demo permission for the input person | Not generated / verified |
| `tests/fixtures/result.jpg` | Backend test fixture: generated plain-color 512×512 image | Original nonpersonal test data; a testing fixture, not a fashion/API result | Source described in backend docs; record hash if redistributed |
| Inline app SVG marks/icons | Project source | Original code under MIT to the extent created for this project | Review final source and ensure no copied brand marks |

Do not infer image rights from an absent watermark, a search result or availability on a retail site. Do not claim a sample is a purchasable product or affiliated with a shop unless that is true and documented. Sample prices are fictional examples, not offers.

[Image provenance](../public/assets/provenance.json) records the creation date, tool, full prompts and input-only roles for all three original sample assets. No external source URL is invented for these project-generated files. Archive their final hashes with the release receipt. Genuine YouCam outputs must receive separate redacted task provenance after actual generation; these sample files cannot substitute for it.

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
