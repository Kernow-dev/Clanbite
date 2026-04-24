#!/usr/bin/env python3
"""Second-pass renames: Clanbite core procedural APIs, hooks, CSS prefixes, upload paths."""
from __future__ import annotations

import os
from pathlib import Path

PLUGINS_PARENT = Path(__file__).resolve().parents[2]

TARGETS = [
    PLUGINS_PARENT / "clanbite-forums",
    PLUGINS_PARENT / "clanbite-points",
    PLUGINS_PARENT / "clanbite-ranks",
    PLUGINS_PARENT / "clanbite-social-kit",
]

SKIP_DIRS = {"node_modules", "vendor", ".git", ".svn"}

TEXT_EXT = {
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
}


REPLACEMENTS: list[tuple[str, str]] = [
    ("clanspress_social_feed_profile_user_id", "clanbite_social_feed_profile_user_id"),
    ("clanspress_social_feed_team_id", "clanbite_social_feed_team_id"),
    ("clanspress_social_feed_before_post_engagement_actions", "clanbite_social_feed_before_post_engagement_actions"),
    ("clanspress_social_feed_post_engagement_actions", "clanbite_social_feed_post_engagement_actions"),
    ("clanspress_group_profile_context_group_id", "clanbite_group_profile_context_group_id"),
    ("clanspress_group_profile_route_current_slug", "clanbite_group_profile_route_current_slug"),
    ("clanspress_get_group_subpages", "clanbite_get_group_subpages"),
    ("clanspress_group_profile_home_label", "clanbite_group_profile_home_label"),
    ("clanspress_group_profile_nav_show_settings", "clanbite_group_profile_nav_show_settings"),
    ("clanspress_groups_user_can_manage", "clanbite_groups_user_can_manage"),
    ("clanspress_groups_get_manage_url", "clanbite_groups_get_manage_url"),
    ("clanspress_render_block_markup_file", "clanbite_render_block_markup_file"),
    ("clanspress_players_get_display_avatar", "clanbite_players_get_display_avatar"),
    ("clanspress_players_get_default_avatar", "clanbite_players_get_default_avatar"),
    ("clanspress_players_get_display_cover", "clanbite_players_get_display_cover"),
    ("clanspress_get_player_profile_url", "clanbite_get_player_profile_url"),
    ("clanspress_player_profile_context_user_id", "clanbite_player_profile_context_user_id"),
    ("clanspress_notify", "clanbite_notify"),
    ("clanspress_notifications_extension_active", "clanbite_notifications_extension_active"),
    ("clanspress_notification_types", "clanbite_notification_types"),
    ("clanspress_wordban_mask_html_content", "clanbite_wordban_mask_html_content"),
    ("clanspress_wordban_mask_plain_text", "clanbite_wordban_mask_plain_text"),
    ("clanspress_get_team", "clanbite_get_team"),
    ("clanspress_teams_get_member_role", "clanbite_teams_get_member_role"),
    ("clanspress_teams_get_default_cover_url", "clanbite_teams_get_default_cover_url"),
    ("clanspress_teams_get_display_team_avatar", "clanbite_teams_get_display_team_avatar"),
    ("clanspress_teams_get_default_avatar_url", "clanbite_teams_get_default_avatar_url"),
    ("clanspress_events_are_globally_enabled", "clanbite_events_are_globally_enabled"),
    ("clanspress_events_parse_entity_enabled_meta", "clanbite_events_parse_entity_enabled_meta"),
    ("clanspress_team_created", "clanbite_team_created"),
    ("clanspress_team_roster_updated", "clanbite_team_roster_updated"),
    ("clanspress_event_rsvp_updated", "clanbite_event_rsvp_updated"),
    ("'clanspress_event'", "'clanbite_event'"),
    ('"clanspress_event"', '"clanbite_event"'),
    ("clanspress_registered_extensions", "clanbite_registered_extensions"),
    ("clanspress_official_registered_extensions", "clanbite_official_registered_extensions"),
    ("clanspress_admin_icon_packs", "clanbite_admin_icon_packs"),
    ("clanspress_player_avatar_updated", "clanbite_player_avatar_updated"),
    ("clanspress_player_cover_updated", "clanbite_player_cover_updated"),
    ("clanspress/playerId", "clanbite/playerId"),
    ("requires_clanspress", "requires_clanbite"),
    ("clanspressForumsManage", "clanbiteForumsManage"),
    ("clanspressForumsBoard", "clanbiteForumsBoard"),
    ("clanspress/forums", "clanbite/forums"),
    (".wp-block-clanspress-", ".wp-block-clanbite-"),
    ("clanspress//", "clanbite//"),
    ("clanspress.notifications.received", "clanbite.notifications.received"),
    ("clanspress-group-", "clanbite-group-"),
    ("clanspress-social-", "clanbite-social-"),
]


def should_skip(name: str) -> bool:
    return name in SKIP_DIRS or name.startswith(".")


def process(root: Path) -> None:
    for dirpath, dirnames, filenames in os.walk(root, topdown=True):
        dirnames[:] = [d for d in dirnames if not should_skip(d)]
        for name in filenames:
            p = Path(dirpath) / name
            if p.suffix.lower() not in TEXT_EXT and p.name not in ("composer.json", "package.json", "package-lock.json"):
                continue
            try:
                raw = p.read_bytes()
            except OSError:
                continue
            if b"\0" in raw:
                continue
            try:
                text = raw.decode("utf-8")
            except UnicodeDecodeError:
                continue
            orig = text
            for old, new in REPLACEMENTS:
                text = text.replace(old, new)
            if text != orig:
                p.write_text(text.replace("\r\n", "\n"), encoding="utf-8")


def main() -> None:
    for root in TARGETS:
        if root.is_dir():
            print("Wave2:", root)
            process(root)


if __name__ == "__main__":
    main()
