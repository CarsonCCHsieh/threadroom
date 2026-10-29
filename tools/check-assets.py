"""Offline check of fixed THREADROOM asset receipts and provenance.

Run from any directory: python tools/check-assets.py
Reads only the six fixed asset/license paths and three public JSON receipts.
Does not fetch source URLs, rewrite receipts, rebuild fonts or alter assets.
An OK result confirms local receipt consistency; the DM Sans receipt separately
records the one-time comparison with the immutable official upstream bytes.
"""
from __future__ import annotations

import hashlib
import json
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
ASSETS = {
    'public/assets/model-original.png',
    'public/assets/linen-shirt.png',
    'public/assets/navy-overshirt.png',
    'tests/fixtures/result.jpg',
}
DM_SANS = {
    'public/assets/fonts/DMSans-Variable.ttf',
    'public/assets/fonts/DMSans-OFL.txt',
}
RECEIPTS = {
    'public/assets/asset-receipt.json',
    'public/assets/fonts/dm-sans-receipt.json',
    'public/assets/provenance.json',
}


def read_fixed(relative: str) -> bytes:
    if relative not in ASSETS | DM_SANS | RECEIPTS:
        raise ValueError('unapproved_relative_path')
    path = ROOT.joinpath(*relative.split('/'))
    current = path
    while current != ROOT:
        metadata = current.lstat()
        if current.is_symlink() or getattr(metadata, 'st_file_attributes', 0) & 0x400:
            raise ValueError('filesystem_link')
        current = current.parent
    if not path.is_file() or path.stat().st_size > 16 * 1024 * 1024:
        raise ValueError('invalid_file_or_size')
    return path.read_bytes()


def fail(errors: list[dict], relative: str, code: str) -> None:
    errors.append({'file': relative, 'error': code})


def verify_file(record: dict, allowed: set[str], errors: list[dict], *, upstream: bool = False) -> None:
    relative = record.get('file')
    if not isinstance(relative, str) or relative not in allowed:
        fail(errors, 'receipt', 'unexpected_asset_path')
        return
    try:
        data = read_fixed(relative)
    except (OSError, ValueError):
        fail(errors, relative, 'missing_or_unsafe_file')
        return
    digest = hashlib.sha256(data).hexdigest()
    if not isinstance(record.get('sha256'), str) or not re.fullmatch('[a-f0-9]{64}', record['sha256']) or record['sha256'] != digest:
        fail(errors, relative, 'sha256_mismatch')
    if type(record.get('bytes')) is not int or record['bytes'] != len(data):
        fail(errors, relative, 'byte_length_mismatch')
    if not isinstance(record.get('role'), str) or not record['role'].strip():
        fail(errors, relative, 'missing_role')
    if upstream:
        blob = hashlib.sha1(b'blob ' + str(len(data)).encode('ascii') + b'\0' + data).hexdigest()
        if record.get('byte_identical') is not True or record.get('source_sha256') != digest or record.get('source_bytes') != len(data) or record.get('source_git_blob_sha1') != blob:
            fail(errors, relative, 'upstream_receipt_inconsistent')


def load_receipt(relative: str) -> dict:
    value = json.loads(read_fixed(relative))
    if not isinstance(value, dict):
        raise ValueError('receipt_object_required')
    return value


def records_for(receipt: dict, key: str, expected: set[str]) -> list[dict]:
    records = receipt.get(key)
    if not isinstance(records, list) or any(not isinstance(item, dict) for item in records):
        raise ValueError('invalid_receipt_records')
    names = [item.get('file') for item in records]
    if len(names) != len(expected) or any(not isinstance(name, str) for name in names) or set(names) != expected:
        raise ValueError('missing_duplicate_or_unexpected_records')
    return records


def main() -> int:
    errors = []
    try:
        asset_records = records_for(load_receipt('public/assets/asset-receipt.json'), 'assets', ASSETS)
        dm_records = records_for(load_receipt('public/assets/fonts/dm-sans-receipt.json'), 'files', DM_SANS)
        provenance = load_receipt('public/assets/provenance.json')
        for record in asset_records:
            verify_file(record, ASSETS, errors)
        for record in dm_records:
            verify_file(record, DM_SANS, errors, upstream=True)
        expected_images = {record['file'].removeprefix('public/assets/'): record for record in asset_records if record['file'].startswith('public/')}
        image_records = records_for(provenance, 'assets', set(expected_images))
        for record in image_records:
            expected = expected_images[record['file']]
            if any(record.get(key) != expected[key] for key in ['sha256', 'bytes']):
                fail(errors, 'public/assets/' + record['file'], 'provenance_receipt_mismatch')
    except (OSError, ValueError, KeyError, TypeError):
        fail(errors, 'receipt', 'missing_or_invalid_receipt')
    result = {
        'ok': not errors,
        'network_used': False,
        'assets_modified': False,
        'checked_files': len(ASSETS | DM_SANS),
        'scope': 'Local hashes, byte lengths, roles, image provenance and recorded DM Sans blob consistency; no new upstream fetch',
    }
    if errors:
        result['errors'] = errors
    print(json.dumps(result, indent=2))
    return 1 if errors else 0


if __name__ == '__main__':
    sys.exit(main())
