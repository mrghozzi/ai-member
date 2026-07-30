# AI Member Changelogs

All notable changes to this plugin will be documented in this file.

## [1.3.2] - 2026-07-30

### Fixed
- **Service Singleton Optimization:** Refactored `ai_member_service()` helper function to use static singleton pattern, preventing redundant service instances and unnecessary database config lookups during page load lifecycle.

## [1.3.1] - 2026-07-28

### Fixed
- **Gemini API Connection Failures:** Resolved "AI Member API Error" issues caused by Google deprecating the previous hardcoded `gemini-3-flash-preview` model and the legacy `gemini-2.0-flash-exp` image model. Updated the default text model to `gemini-flash-latest` (an official Google alias that always resolves to the latest available Flash model).
- **Outdated Gemini Request Format:** Migrated from the legacy text-merge pattern (`systemPrompt + "\n\nUser Input: " + userPrompt`) to the official `systemInstruction` field at the request root, which is required by the current Gemini API. Added `generationConfig` (temperature, topP, topK, maxOutputTokens) for stable output.

### Added
- **Configurable Text Model (api_model):** The text-generation model is now configurable from the admin settings panel instead of being hardcoded.
- **Load Available Models Button:** Added a one-click refresh button in the admin settings panel (`/admin/ai-member`) that calls Google's `ListModels` endpoint with the configured API key and dynamically populates the model dropdown with only the models actually available for that key. The button automatically marks the recommended model (latest Flash or Pro) and shows a status message.
- **Detailed Error Reporting:** When a Gemini API call fails, the plugin now logs the full HTTP status code and Google response body to `storage/logs/laravel.log` (search for `AI Member API`) instead of a single short line, making debugging much easier.
- **New `listAvailableModels()` Method:** Added a public service method that any internal controller or future widget can use to fetch the available Gemini models for a given API key.

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
