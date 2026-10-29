#!/usr/bin/env python3
"""Push pakanmental-home files to live site via POST /pm/v1/sync-file (requires plugin 1.1.5+)."""
from __future__ import annotations

import base64
import json
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path("/workspace")
PLUGIN = ROOT / "pakanmental-home"
SITE = "https://www.pakanmental.com/wp-json/pm/v1/sync-file"
AUTH = "Basic bWhtOkNaT1p3U2RRMU1PMmhmMzJwM0FSWXNEOA=="

GLOBS = (
    "pakanmental-home.php",
    "includes/*.php",
    "templates/*.php",
    "assets/css/*.css",
    "assets/js/*.js",
)


def iter_files():
    seen: set[str] = set()
    for pattern in GLOBS:
        for path in PLUGIN.glob(pattern):
            if not path.is_file():
                continue
            rel = path.relative_to(PLUGIN).as_posix()
            if rel in seen:
                continue
            seen.add(rel)
            yield rel, path


def sync_file(rel: str, path: Path) -> bool:
    payload = {
        "path": rel,
        "content": base64.b64encode(path.read_bytes()).decode("ascii"),
    }
    body = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(
        SITE,
        data=body,
        headers={"Authorization": AUTH, "Content-Type": "application/json"},
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=120) as res:
            out = json.loads(res.read().decode())
            print("ok", rel, out.get("size"))
            return True
    except urllib.error.HTTPError as exc:
        err = exc.read().decode(errors="replace")[:400]
        print("fail", rel, exc.code, err)
        return False


def main() -> None:
    ok = 0
    fail = 0
    for rel, path in iter_files():
        if sync_file(rel, path):
            ok += 1
        else:
            fail += 1
    print("done", ok, "ok", fail, "fail")
    if fail:
        raise SystemExit(1)


if __name__ == "__main__":
    main()
