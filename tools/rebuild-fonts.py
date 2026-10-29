"""Rebuild/check regional UI subsets from pinned, hash-verified Google Fonts.

Requires Python fonttools[woff] and Node for importing the project's translations.
Run from any directory: python tools/rebuild-fonts.py [--check]
This is a development tool; the application has no Python/Node hosting dependency.
"""

from __future__ import annotations

import argparse
from datetime import datetime, timezone
import hashlib
from html.parser import HTMLParser
import io
import json
from pathlib import Path
import re
import subprocess
import tempfile
from urllib.request import urlopen

import fontTools
from fontTools import subset
from fontTools.ttLib import TTFont
from fontTools.varLib.instancer import instantiateVariableFont


PROJECT = Path(__file__).resolve().parents[1]
FONT_DIR = PROJECT / "public" / "assets" / "fonts"
COMMIT = "9862abc066c9b83bf84c3e8ed1fc2d8d48f9bbf0"
SOURCES = {
    "zh-TW": {
        "family": "notosanstc",
        "upstream": "NotoSansTC%5Bwght%5D.ttf",
        "file": "NotoSansTC-UI-Variable.woff2",
        "sha256": "864727d210d54f2537bbe23b3a839436c3992af72de9322af5270897246bd44f",
    },
    "ja": {
        "family": "notosansjp",
        "upstream": "NotoSansJP%5Bwght%5D.ttf",
        "file": "NotoSansJP-UI-Variable.woff2",
        "sha256": "c2f3b4d463500a2ddcd3849cded1fceeb9fd6d1c32e6cbecd568453ba50fc68f",
    },
}


def sha256(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


class TextCollector(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.text: list[str] = []

    def handle_data(self, data: str) -> None:
        self.text.append(data)


def ui_codepoints() -> dict[str, set[int]]:
    module_path = PROJECT / "public" / "i18n.mjs"
    script = (
        "import {pathToFileURL} from 'node:url';"
        "const {messages}=await import(pathToFileURL(process.argv[1]).href);"
        "process.stdout.write(JSON.stringify(messages));"
    )
    result = subprocess.run(
        ["node", "--input-type=module", "-e", script, str(module_path)],
        check=True, capture_output=True, text=True, encoding="utf-8",
    )
    messages = json.loads(result.stdout)
    base_keys = set(messages["en"])
    for locale, strings in messages.items():
        if set(strings) != base_keys:
            raise ValueError(f"Translation key mismatch: {locale}")
        for key, value in strings.items():
            if not value.strip():
                raise ValueError(f"Empty translation: {locale}/{key}")
            if set(re.findall(r"\{[^}]+\}", value)) != set(
                re.findall(r"\{[^}]+\}", messages["en"][key])
            ):
                raise ValueError(f"Translation placeholder mismatch: {locale}/{key}")
    collector = TextCollector()
    collector.feed((PROJECT / "public" / "index.html").read_text(encoding="utf-8"))
    shared = "".join(chr(i) for i in range(32, 127))
    shared += "".join(messages["en"].values()) + "".join(collector.text)
    return {
        locale: {ord(c) for c in shared + "".join(messages[locale].values()) if not c.isspace()}
        for locale in SOURCES
    }


def validate(font_path: Path, required: set[int]) -> list[int]:
    with TTFont(font_path, recalcTimestamp=False) as font:
        cmap = font.getBestCmap()
        missing = sorted(required - set(cmap))
        if missing:
            raise ValueError(
                f"Missing UI codepoints in {font_path.name}: "
                + ", ".join(f"U+{cp:04X}" for cp in missing)
            )
        axis = next((a for a in font["fvar"].axes if a.axisTag == "wght"), None)
        if not axis or (axis.minValue, axis.maxValue) != (400, 700):
            raise ValueError(f"Unexpected weight range in {font_path.name}")
    return missing


def check(required_by_locale: dict[str, set[int]]) -> None:
    receipt = json.loads((FONT_DIR / "font-receipt.json").read_text(encoding="utf-8"))
    entries = {item["language"]: item for item in receipt["outputs"]}
    for locale, source in SOURCES.items():
        path = FONT_DIR / source["file"]
        validate(path, required_by_locale[locale])
        if sha256(path.read_bytes()) != entries[locale]["output_sha256"]:
            raise ValueError(f"Receipt/output hash mismatch: {locale}")
        print(f"{locale}: {len(required_by_locale[locale])} required UI codepoints; missing 0; hash OK")


def rebuild(required_by_locale: dict[str, set[int]]) -> None:
    outputs = []
    with tempfile.TemporaryDirectory(prefix="threadroom-fonts-") as tmp:
        pending: list[tuple[Path, Path]] = []
        for locale, source in SOURCES.items():
            url = f"https://raw.githubusercontent.com/google/fonts/{COMMIT}/ofl/{source['family']}/{source['upstream']}"
            with urlopen(url, timeout=60) as response:
                data = response.read(32 * 1024 * 1024 + 1)
            if len(data) > 32 * 1024 * 1024 or sha256(data) != source["sha256"]:
                raise ValueError(f"Pinned source hash/size check failed: {locale}")
            required = required_by_locale[locale]
            font = TTFont(io.BytesIO(data), recalcTimestamp=False)
            options = subset.Options()
            options.layout_features = ["*"]
            options.recalc_timestamp = False
            subsetter = subset.Subsetter(options=options)
            subsetter.populate(unicodes=required)
            subsetter.subset(font)
            # Subset before instancing: a full CJK font's lazy gvar entries can
            # otherwise refer to glyphs pruned by partial-axis instancing.
            instantiateVariableFont(font, {"wght": (400, 700)}, inplace=True)
            font.flavor = "woff2"
            path = Path(tmp) / source["file"]
            font.save(path)
            font.close()
            validate(path, required)
            output = path.read_bytes()
            outputs.append({
                "language": locale, "file": source["file"], "source_url": url,
                "source_sha256": source["sha256"], "output_sha256": sha256(output),
                "bytes": len(output), "required_codepoints": len(required),
                "missing_required_codepoints": 0, "weight_range": [400, 700],
                "required_codepoints_sha256": sha256(
                    ",".join(str(cp) for cp in sorted(required)).encode("ascii")
                ),
            })
            pending.append((path, FONT_DIR / source["file"]))
        # No bundled font is replaced until both source hashes and outputs pass.
        for source_path, destination in pending:
            destination.write_bytes(source_path.read_bytes())
    receipt = {
        "built_at_utc": datetime.now(timezone.utc).isoformat(),
        "license": "SIL Open Font License 1.1; included adjacent license files",
        "purpose": "THREADROOM current English and regional locale UI strings; not arbitrary user text",
        "builder": "tools/rebuild-fonts.py",
        "fonttools_version": fontTools.__version__,
        "ui_sources": {
            "public/i18n.mjs": sha256((PROJECT / "public" / "i18n.mjs").read_bytes()),
            "public/index.html": sha256((PROJECT / "public" / "index.html").read_bytes()),
        },
        "outputs": outputs,
    }
    (FONT_DIR / "font-receipt.json").write_text(
        json.dumps(receipt, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    check(required_by_locale)


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--check", action="store_true", help="Validate current subsets without network or writes")
    args = parser.parse_args()
    required_by_locale = ui_codepoints()
    check(required_by_locale) if args.check else rebuild(required_by_locale)
