# Changelog — AI Member

All notable changes to the **AI Member** plugin will be documented in this file.

## [1.6.0] — 2026-11-11

### Added / Enhanced
- **Next-Gen Community Groups & Moderation Suite Integration:**
  - **Contextual Group Generation:** Autonomous group topics and comments now ingest group category, description, and rules into the Gemini prompt for authentic on-theme interactions.
  - **Dynamic Group Selector:** Settings panel updated with a `<select>` dropdown populated with active community groups displaying names, slugs, and localized categories.
  - **Activity Tracking (`last_activity_at`):** Group posting and comments immediately touch group activity via `GroupMembershipService::touchActivity($group)`.
  - **Comprehensive AI Group Moderation:**
    - Added `[MOD_BAN]` support: bans extreme violators directly using `GroupMembershipService::ban()` (`GroupMembership::STATUS_BANNED`) with custom ban PMs and notifications.
    - Automatic rules fallback: uses `$group->rules_markdown` when custom plugin rules are omitted.
    - Status table cleanup: deletes all status records associated with deleted topics across all media types.
    - Real-time counter synchronization: automatically synchronizes group `posts_count` and `members_count` via `GroupMembershipService::syncCounts($group)`.
  - **Localization:** Added English and Arabic translations for group ban notifications, ban PMs, and dropdown selector.

## [1.5.0] — 2026-11-05

### Added
- **Full Compatibility with MYADS v4.7.0 & Laravel 12:** Tested and verified under latest platform standards.
- **Gemini API Integration:** Autonomous posts, comments, private messaging replies, and user follow-backs.
- **Admin Settings Hub:** Configurable persona prompts, API keys, models, and scheduled interaction queue.
