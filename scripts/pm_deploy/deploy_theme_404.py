#!/usr/bin/env python3
"""
Upload hello-pakan theme zip and activate child theme (404 → page 404-pm).
No pakanmental-home plugin update required.
"""
from __future__ import annotations

import json
import subprocess
import urllib.error
import urllib.parse
import urllib.request

AUTH = "Basic bWhtOkNaT1p3U2RRMU1PMmhmMzJwM0FSWXNEOA=="
SITE = "https://www.pakanmental.com"
MEDIA = f"{SITE}/wp-json/wp/v2/media"
THEMES = f"{SITE}/wp-json/wp/v2/themes"
ZIP_PATH = "/workspace/artifacts/hello-pakan.zip"


def upload_zip() -> str:
	with open(ZIP_PATH, "rb") as f:
		data = f.read()
	req = urllib.request.Request(
		MEDIA,
		data=data,
		headers={
			"Authorization": AUTH,
			"Content-Disposition": 'attachment; filename="hello-pakan.zip"',
			"Content-Type": "application/zip",
		},
		method="POST",
	)
	with urllib.request.urlopen(req, timeout=120) as res:
		out = json.loads(res.read().decode())
	return out["source_url"]


def try_install_from_url(zip_url: str) -> bool:
	endpoints = [
		f"{SITE}/wp-json/wc-admin/onboarding/themes/install?themes={urllib.parse.quote(zip_url, safe='')}",
		f"{SITE}/wp-json/wc-admin/plugins/install?plugins={urllib.parse.quote(zip_url, safe='')}",
	]
	for url in endpoints:
		req = urllib.request.Request(url, headers={"Authorization": AUTH}, method="POST")
		try:
			with urllib.request.urlopen(req, timeout=180) as res:
				body = json.loads(res.read().decode())
				print("install try", url, body.get("success", body))
				if body.get("success"):
					return True
		except urllib.error.HTTPError as exc:
			print("install fail", url, exc.code, exc.read()[:200])
	return False


def activate_theme(stylesheet: str = "hello-pakan") -> bool:
	body = json.dumps({"status": "active"}).encode()
	req = urllib.request.Request(
		f"{THEMES}/{urllib.parse.quote(stylesheet, safe='')}",
		data=body,
		headers={"Authorization": AUTH, "Content-Type": "application/json"},
		method="POST",
	)
	try:
		with urllib.request.urlopen(req, timeout=60) as res:
			out = json.loads(res.read().decode())
			print("theme active", out.get("stylesheet"), out.get("status"))
			return out.get("status") == "active"
	except urllib.error.HTTPError as exc:
		print("activate fail", exc.code, exc.read()[:300].decode(errors="replace"))
		return False


def theme_installed(stylesheet: str = "hello-pakan") -> bool:
	req = urllib.request.Request(
		f"{THEMES}/{urllib.parse.quote(stylesheet, safe='')}",
		headers={"Authorization": AUTH},
		method="GET",
	)
	try:
		with urllib.request.urlopen(req, timeout=30) as res:
			return True
	except urllib.error.HTTPError:
		return False


def main() -> None:
	subprocess.run(
		["python3", "-m", "pm_deploy.build_theme_404"],
		cwd="/workspace/scripts",
		env={"PYTHONPATH": "/workspace/scripts"},
		check=True,
	)
	zip_url = upload_zip()
	print("uploaded", zip_url)
	if not theme_installed():
		try_install_from_url(zip_url)
	if not theme_installed():
		print(
			"MANUAL: Appearance → Themes → Add New → Upload →",
			zip_url,
			"then activate «Hello Pakan».",
		)
		raise SystemExit(1)
	if activate_theme():
		print("OK: child theme hello-pakan active — all 404s use page 404-pm")
	else:
		print("Theme installed but not active — activate «Hello Pakan» in wp-admin.")
		raise SystemExit(1)


if __name__ == "__main__":
	main()
