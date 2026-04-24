#!/usr/bin/env python3
"""One-off bulk rename: Clanspress → Clanbite (core). Preserves companion API strings."""
from __future__ import annotations

import os
import re
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SKIP_DIRS = {"vendor", "node_modules", ".git", ".cursor", "tools"}
TEXT_EXT = {
    ".php",
    ".json",
    ".js",
    ".jsx",
    ".ts",
    ".tsx",
    ".md",
    ".txt",
    ".html",
    ".cjs",
    ".scss",
    ".css",
    ".svg",
    ".xml",
    ".distignore",
    ".neon",
    ".yml",
    ".pot",
    ".lock",
    ".sample",
}

# Placeholders must survive all replacement waves.
PLACEHOLDERS: list[tuple[str, str]] = [
    ("Kernowdev\\ClanspressForums", "<<<NS_FORUMS>>>"),
    ("Kernowdev\\ClanspressPoints", "<<<NS_POINTS>>>"),
    ("Kernowdev\\ClanspressRanks", "<<<NS_RANKS>>>"),
    ("Kernowdev\\ClanspressSocialKit", "<<<NS_SOCIALKIT>>>"),
    ("clanspress_social_kit", "<<<PHP_SOCIALKIT>>>"),
    ("clanspress-forums", "<<<BLOCK_FORUMS>>>"),
    ("clanspress-social", "<<<BLOCK_SOCIAL>>>"),
    ("clanspress-points", "<<<BLOCK_POINTS>>>"),
    ("clanspress-ranks", "<<<BLOCK_RANKS>>>"),
    ("manage_clanspress_forums", "<<<CAP_FORUMS>>>"),
    ("CLANSPRESS_POINTS_", "<<<CONST_POINTS>>>"),
    ("CLANSPRESS_RANKS_", "<<<CONST_RANKS>>>"),
]


def should_process(path: str) -> bool:
    rel = os.path.relpath(path, ROOT)
    parts = rel.split(os.sep)
    for p in parts:
        if p in SKIP_DIRS:
            return False
    base = os.path.basename(path)
    if base in ("rename_to_clanbite.py",):
        return False
    ext = os.path.splitext(path)[1].lower()
    if ext in TEXT_EXT:
        return True
    if base in ("phpcs.xml.dist", "phpstan.neon.dist", "phpstan-bootstrap.php"):
        return True
    return False


def transform(text: str) -> str:
    for a, b in PLACEHOLDERS:
        text = text.replace(a, b)

    # Namespace (avoid breaking <<<NS_FORUMS>>> etc.)
    text = text.replace("Kernowdev\\Clanspress\\", "Kernowdev\\Clanbite\\")
    text = text.replace("namespace Kernowdev\\Clanspress;", "namespace Kernowdev\\Clanbite;")

    ordered = [
        ("clanspress/v1", "clanbite/v1"),
        ("clanspress//", "clanbite//"),
        ("clanspress-", "clanbite-"),
        ("clanspress_", "clanbite_"),
        ("Clanspress", "Clanbite"),
        ("CLANSPRESS_", "CLANBITE_"),
        ("clanspress.com", "clanbite.com"),
    ]
    for a, b in ordered:
        text = text.replace(a, b)

    text = re.sub(r"\bclanspress\b", "clanbite", text)

    for a, b in reversed(PLACEHOLDERS):
        text = text.replace(b, a)

    return text


def main() -> int:
    changed = 0
    for dirpath, dirnames, filenames in os.walk(ROOT):
        dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS and not d.startswith(".")]
        for fn in filenames:
            path = os.path.join(dirpath, fn)
            if not should_process(path):
                continue
            try:
                raw = open(path, "rb").read()
            except OSError:
                continue
            if b"\x00" in raw[:8000]:
                continue
            try:
                text = raw.decode("utf-8")
            except UnicodeDecodeError:
                continue
            new = transform(text)
            if new != text:
                with open(path, "w", encoding="utf-8", newline="") as f:
                    f.write(new)
                changed += 1
    print(f"Modified {changed} files", file=sys.stderr)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
