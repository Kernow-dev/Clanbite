#!/usr/bin/env python3
"""
Bulk-rename Clanspress companion plugins to Clanbite (namespaces, hooks, REST, blocks, constants).

Run from repo root:
  python3 tools/rename_companions_to_clanbite.py

Then: rename plugin directories if this script only processes in-place (dirs stay as clanspress-* until mv).
"""
from __future__ import annotations

import os
from pathlib import Path

PLUGINS_PARENT = Path(__file__).resolve().parents[2]

# (old_dir_name, new_dir_name, old_main_file, new_main_file)
COMPANIONS = [
    ("clanspress-forums", "clanbite-forums", "clanspress-forums.php", "clanbite-forums.php"),
    ("clanspress-points", "clanbite-points", "clanspress-points.php", "clanbite-points.php"),
    ("clanspress-ranks", "clanbite-ranks", "clanspress-ranks.php", "clanbite-ranks.php"),
    ("clanspress-social-kit", "clanbite-social-kit", "clanspress-social-kit.php", "clanbite-social-kit.php"),
]

SKIP_DIRS = {
    "node_modules",
    "vendor",
    ".git",
    ".svn",
}

TEXT_EXTENSIONS = {
    ".php",
    ".js",
    ".jsx",
    ".mjs",
    ".cjs",
    ".json",
    ".md",
    ".css",
    ".scss",
    ".html",
    ".txt",
    ".xml",
    ".dist",
    ".pot",
    ".yml",
    ".yaml",
}


def should_skip_dir(name: str) -> bool:
    return name in SKIP_DIRS or name.startswith(".")


REPLACEMENTS: list[tuple[str, str]] = [
    # Namespaces (longest first).
    ("Kernowdev\\ClanspressForums", "Kernowdev\\ClanbiteForums"),
    ("Kernowdev\\ClanspressPoints", "Kernowdev\\ClanbitePoints"),
    ("Kernowdev\\ClanspressRanks", "Kernowdev\\ClanbiteRanks"),
    ("Kernowdev\\ClanspressSocialKit", "Kernowdev\\ClanbiteSocialKit"),
    ("Kernowdev\\Clanspress\\", "Kernowdev\\Clanbite\\"),
    # Constants.
    ("CLANSPRESS_FORUMS_", "CLANBITE_FORUMS_"),
    ("CLANSPRESS_POINTS_", "CLANBITE_POINTS_"),
    ("CLANSPRESS_RANKS_", "CLANBITE_RANKS_"),
    ("CLANSPRESS_SOCIAL_KIT_", "CLANBITE_SOCIAL_KIT_"),
    # Hyphenated slugs / REST / text domains / block namespaces.
    ("clanspress-social-kit", "clanbite-social-kit"),
    ("clanspress-forums", "clanbite-forums"),
    ("clanspress-points", "clanbite-points"),
    ("clanspress-ranks", "clanbite-ranks"),
    ("clanspress-social/", "clanbite-social/"),
    ("kernowdev/clanspress-", "kernowdev/clanbite-"),
    # Underscore keys, hooks, options, table suffixes.
    ("clanspress_social_kit_", "clanbite_social_kit_"),
    ("clanspress_forums_", "clanbite_forums_"),
    ("clanspress_forum_", "clanbite_forum_"),
    ("clanspress_points_", "clanbite_points_"),
    ("clanspress_ranks_", "clanbite_ranks_"),
    # Table / hook bare "clanspress_forums" (not already clanbite_forums_).
    ("clanspress_forums", "clanbite_forums"),
    ("clanspress_points", "clanbite_points"),
    ("manage_clanspress_forums", "manage_clanbite_forums"),
    # PHP / JS identifiers (after hyphenated plugins renamed).
    ("bootstrap_clanspress_extension", "bootstrap_clanbite_extension"),
    ("is_clanspress_available", "is_clanbite_available"),
    ("render_clanspress_missing_notice", "render_clanbite_missing_notice"),
    ("$clanspress_", "$clanbite_"),
    ("Clanspress_Group_Integration", "Clanbite_Group_Integration"),
    ("ClanspressForums", "ClanbiteForums"),
    ("ClanspressPoints", "ClanbitePoints"),
    ("ClanspressRanks", "ClanbiteRanks"),
    ("ClanspressSocialKit", "ClanbiteSocialKit"),
    # Core bootstrap (remaining).
    ("function_exists( 'clanspress' )", "function_exists( 'clanbite' )"),
    ("function_exists(\"clanspress\")", "function_exists(\"clanbite\")"),
    ("\\clanspress()", "\\clanbite()"),
    ("clanspress()", "clanbite()"),
    # Human-readable (late).
    ("Clanspress Forums", "Clanbite Forums"),
    ("Clanspress Points", "Clanbite Points"),
    ("Clanspress Ranks", "Clanbite Ranks"),
    ("Clanspress Social Kit", "Clanbite Social Kit"),
    ("Clanspress plugin", "Clanbite plugin"),
    ("Clanspress is", "Clanbite is"),
    ("Clanspress ", "Clanbite "),
    (" Clanspress", " Clanbite"),
]


def process_file(path: Path) -> bool:
    try:
        raw = path.read_bytes()
    except OSError:
        return False
    if b"\0" in raw:
        return False
    try:
        text = raw.decode("utf-8")
    except UnicodeDecodeError:
        return False
    orig = text
    for old, new in REPLACEMENTS:
        text = text.replace(old, new)
    if text != orig:
        path.write_text(text.replace("\r\n", "\n"), encoding="utf-8")
        return True
    return False


def walk_and_replace(root: Path) -> tuple[int, int]:
    files_changed = 0
    files_seen = 0
    for dirpath, dirnames, filenames in os.walk(root, topdown=True):
        dirnames[:] = [d for d in dirnames if not should_skip_dir(d)]
        for name in filenames:
            p = Path(dirpath) / name
            if p.suffix.lower() not in TEXT_EXTENSIONS and p.name not in {
                "composer.json",
                "package.json",
                "package-lock.json",
            }:
                continue
            files_seen += 1
            if process_file(p):
                files_changed += 1
    return files_seen, files_changed


def rename_paths_inside(root: Path) -> None:
    """Rename files and dirs whose names contain clanspress (deepest first)."""
    all_paths: list[Path] = []
    for dirpath, dirnames, filenames in os.walk(root, topdown=False):
        for name in filenames + dirnames:
            if "clanspress" in name.lower():
                all_paths.append(Path(dirpath) / name)
    for p in sorted(all_paths, key=lambda x: len(x.parts), reverse=True):
        new_name = p.name.replace("clanspress", "clanbite").replace("Clanspress", "Clanbite")
        if new_name == p.name:
            continue
        dest = p.with_name(new_name)
        if dest.exists():
            continue
        p.rename(dest)


def rename_plugin_root(old: Path, new: Path, old_main: str, new_main: str) -> None:
    if not old.is_dir():
        return
    if new.exists():
        print(f"Skip mv (target exists): {new}")
        return
    om = old / old_main
    nm = old / new_main
    if om.exists() and not nm.exists():
        om.rename(nm)
    old.rename(new)


def main() -> None:
    for old_dir, new_dir, old_main, new_main in COMPANIONS:
        root = PLUGINS_PARENT / old_dir
        if not root.is_dir():
            print(f"Missing: {root}")
            continue
        print(f"Processing {root} …")
        walk_and_replace(root)
        rename_paths_inside(root)
        target = PLUGINS_PARENT / new_dir
        rename_plugin_root(root, target, old_main, new_main)
        print(f"Done: {target}")

    print("Finished. Run composer dump-autoload in each companion and npm run build where blocks ship.")


if __name__ == "__main__":
    main()
