"""Sync Elementor kit colors and base typography to Pakan Mental palette."""
from __future__ import annotations

import json
import urllib.request

AUTH = "Basic bWhtOkNaT1p3U2RRMU1PMmhmMzJwM0FSWXNEOA=="
MCP = "https://www.pakanmental.com/wp-json/elementor/mcp/"

# Logo palette only
COLORS = [
    ("PM Primary Mint", "#4aa88e"),
    ("PM Mint Light", "#e8f6f1"),
    ("PM Teal Dark", "#1e4339"),
    ("PM Teal", "#2a5f52"),
    ("PM Cream", "#faf7f2"),
    ("PM Sand", "#e5d4bc"),
    ("PM Brown", "#8b5e3c"),
    ("PM Background", "#fafaf9"),
]


def _session() -> str:
    body = json.dumps(
        {
            "jsonrpc": "2.0",
            "id": 1,
            "method": "initialize",
            "params": {
                "protocolVersion": "2024-11-05",
                "capabilities": {},
                "clientInfo": {"name": "pm-deploy", "version": "1"},
            },
        }
    ).encode()
    req = urllib.request.Request(
        MCP,
        data=body,
        headers={"Authorization": AUTH, "Content-Type": "application/json"},
        method="POST",
    )
    with urllib.request.urlopen(req) as res:
        sid = res.headers.get("Mcp-Session-Id") or res.headers.get("mcp-session-id")
    return sid.strip() if sid else ""


def _call(session: str, name: str, arguments: dict) -> dict:
    body = json.dumps(
        {"jsonrpc": "2.0", "id": 2, "method": "tools/call", "params": {"name": name, "arguments": arguments}}
    ).encode()
    req = urllib.request.Request(
        MCP,
        data=body,
        headers={
            "Authorization": AUTH,
            "Content-Type": "application/json",
            "Mcp-Session-Id": session,
        },
        method="POST",
    )
    return json.loads(urllib.request.urlopen(req).read())


def sync() -> None:
    session = _session()
    if not session:
        print("elementor mcp: no session")
        return

    ops = [
        {
            "action": "create",
            "type": "global-color-variable",
            "label": label,
            "value": value,
        }
        for label, value in COLORS
    ]
    r = _call(session, "elementor-manage-global-variable", {"operations": ops})
    print("elementor colors", "ok" if r.get("result") and not r["result"].get("isError") else r)

    link_css = (
        "color: #2a5f52; text-decoration: none; text-underline-offset: 0;"
        "&:hover { color: #4aa88e; text-decoration: none; }"
    )
    body_css = "color: #1e4339; background-color: #fafaf9;"
    styles = [
        {"action": "update", "tag": "a", "css": link_css, "mode": "replace"},
        {"action": "update", "tag": "p", "css": "color: #1e4339;", "mode": "replace"},
        {"action": "update", "tag": "h1", "css": "color: #1e4339;", "mode": "replace"},
        {"action": "update", "tag": "h2", "css": "color: #1e4339;", "mode": "replace"},
        {"action": "update", "tag": "button", "css": "background-color: #4aa88e; color: #faf7f2; border: none;", "mode": "replace"},
        {
            "action": "update",
            "tag": "input",
            "css": "accent-color: #4aa88e;",
            "mode": "replace",
        },
    ]
    r2 = _call(session, "elementor-manage-default-styles", {"operations": styles})
    print("elementor default styles", "ok" if r2.get("result") else r2)


if __name__ == "__main__":
    sync()
