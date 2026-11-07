"""Elementor MCP JSON-RPC client for pakanmental.com."""
from __future__ import annotations

import json
import urllib.request

AUTH = "Basic bWhtOkNaT1p3U2RRMU1PMmhmMzJwM0FSWXNEOA=="
MCP = "https://www.pakanmental.com/wp-json/elementor/mcp/"


class ElementorMcp:
    def __init__(self) -> None:
        self._session = self._init_session()

    def _init_session(self) -> str:
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
        with urllib.request.urlopen(req, timeout=120) as res:
            sid = res.headers.get("Mcp-Session-Id") or res.headers.get("mcp-session-id")
        return (sid or "").strip()

    def call(self, name: str, arguments: dict) -> dict:
        body = json.dumps(
            {
                "jsonrpc": "2.0",
                "id": 2,
                "method": "tools/call",
                "params": {"name": name, "arguments": arguments},
            }
        ).encode()
        req = urllib.request.Request(
            MCP,
            data=body,
            headers={
                "Authorization": AUTH,
                "Content-Type": "application/json",
                "Mcp-Session-Id": self._session,
            },
            method="POST",
        )
        raw = json.loads(urllib.request.urlopen(req, timeout=180).read())
        result = raw.get("result") or {}
        if result.get("isError"):
            raise RuntimeError(f"{name} failed: {raw}")
        content = result.get("content") or []
        if not content:
            return result
        text = content[0].get("text", "")
        try:
            return json.loads(text)
        except json.JSONDecodeError:
            return {"raw": text, **result}
