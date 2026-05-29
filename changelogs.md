# AI Member Changelogs

All notable changes to this plugin will be documented in this file.

## [1.3.0] - 2026-05-29
### Added
- **Auto-Block Feature:** The bot can now automatically detect abusive or harassing messages, mentions, and comments and issue platform or message-level blocks using the `[CMD_BLOCK: duration]` system prompt.

### Changed
- **Superdesign:** Completely redesigned the plugin's admin settings page (`/admin/ai-member`) to adopt the premium Duralux "Superdesign" aesthetic, featuring glassmorphism, responsive grids, and modern UI components.

## [1.2.0] - 2026-05-23
### Added
- **Pending Tasks Dashboard:** A new management section in the admin panel to view all scheduled and event-based tasks.
- **Task Actions:** Ability to cancel pending tasks or execute them immediately (bypassing global cooldowns and probability checks).
- **Persistent Ignore Cache:** Added cache-based tracking for ignored comments, mentions, and group topics to prevent repetitive AI interaction attempts.
- **Global Cooldown Indicator:** Shows real-time bot resting status and remaining cooldown time directly in the dashboard.

## [1.1.0] - 2026-05-21
### Added
- **AI Image Generation:** The bot can now generate and publish image posts (Gallery format) using Gemini API.
- New `callGeminiImageGeneration()` method for Gemini image output via `responseModalities`.
- New `generateImagePost()` method creates Gallery posts (s_type=4) with proper Option and ForumAttachment records.
- Modified `generatePost()` to randomly include AI-generated images based on configurable probability.
- Admin settings: Enable/disable image posts, image model selection, image probability (%), and image style prompt.
- Automatic fallback to text-only posts when image generation fails.
- Full Arabic and English translations for all new settings.

## [1.0.0] - 2026-05-09
### Added
- Initial release of the AI Member plugin.
- Integration with Google Gemini API for content generation.
- Autonomous community posting system.
- Intelligent private message auto-reply.
- @mention detection and contextual replying in comments.
- Automatic reactions (Like/Love/Wow) on community posts.
- Group management system:
    - Auto-posting in groups.
    - AI-powered content moderation (Delete/Kick).
    - Group-specific persona and rules.
- Admin dashboard for configuration and bot user synchronization.
- Performance optimization with probabilistic ticks and cooldowns.
- Admin DM commands for forced posting.
