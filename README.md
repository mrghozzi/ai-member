# AI Member Plugin for MYADS (v1.4.0)

**AI Member** is an advanced autonomous bot plugin for the MYADS platform (fully compatible with v4.6.x+). It leverages Google Gemini AI to act as a living, breathing member of your community. The bot publishes engaging content, responds intelligently to messages and mentions, respects nested threaded comments, and provides a real-time Ajax management dashboard.

## 🚀 Key Features

- **Full MYADS v4.6.x Architecture Compatibility**:
  - **Threaded Nested Comments (`parent_id`)**: Bot comment replies connect directly to parent comments in the discussion tree.
  - **Comment Reply Notifications**: Dispatches real-time notifications (`user_replied_to_your_comment`) to authors.
  - **3-Tier Privacy & Anti-Enumeration**: Automatically provisions `UserPrivacySetting` with public visibility and secure `public_uid`.
  - **Atomic Concurrency Locking**: Multi-process worker calls and client triggers are guarded by `Cache::lock` to avoid duplicate API calls and race conditions.
- **High-Speed AJAX Admin Experience**:
  - **Instant Settings Persistence**: Save and synchronize the bot with non-blocking Ajax feedback and floating alerts.
  - **Interactive Task Queue**: Execute tasks on-demand or cancel/ignore them with zero page reloads.
  - **Gemini AI Verification Console (`/admin/ai-member/test-ai`)**: Dedicated sandbox to test prompt completion and inspect response latency (in milliseconds).
  - **Dynamic Model Fetcher (`/admin/ai-member/list-models`)**: Fetches supported models dynamically from Google Generative Language API.
  - **1-Click Cooldown Reset (`/admin/ai-member/reset-cooldown`)**: Wake up the bot from mandatory resting periods on-demand.
  - **Live Activity Log (`/admin/ai-member/clear-logs`)**: Rolling event history tracking the last 20 actions completed by the bot.
- **Prioritized Interaction Queue**:
  - 4 Priority Tiers: Tier 1: Private Messages [Urgent], Tier 2: Mentions [High], Tier 3: Comments [Normal], Tier 4: Scheduled Background Actions [Routine].
  - Permanent task ignore and dismissal tracking.
- **Autonomous Posting & Multimodal Generation**:
  - Feed auto-posting with customizable frequency.
  - AI Image Gallery Posts with Gemini/Imagen multimodal generation and automatic text-only fallback.
- **Group Management & Strict Moderation**:
  - Bot can own and manage a community group, posting topics and comments.
  - AI-powered content moderation: Automatically deletes rule violations or kicks offending members with automated notification notices.
- **Client JS Rate Limiting & Performance Protection**:
  - 10-minute browser throttling via `localStorage` and `throttle:10,1` server-side rate limiting.
  - Bypasses script injection if the server cooldown is active.
- **Duralux Dark Mode Support**: Full compatibility with `html.app-skin-dark`.
- **100% Arabic & English Bilingual Parity**.

## 🛠️ Installation & Setup

1. Place the `ai-member` folder in your `/plugins` directory.
2. Go to **Admin Panel > Plugins** and activate "AI Member".
3. Navigate to **Admin Panel > AI Member** (Cpu icon in sidebar).
4. Enter your **Google Gemini API Key**.
5. Choose your preferred text model (e.g., `gemini-flash-latest`, `gemini-2.5-flash`).
6. Configure the persona, frequency, and optional group management settings.
7. Save and watch your community come to life!

## 🤖 Admin Commands

As an administrator, you can order the bot to post via Private Messages:
- Send: `Can you post about the benefits of SEO?`
- The bot detects the request and publishes a post to the community feed.
- Advanced: You can use the internal command syntax `[CMD_POST: topic]`.

---
Created by [MrGhozzi](https://github.com/mrghozzi)
