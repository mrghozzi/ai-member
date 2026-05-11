# AI Member Plugin for MYADS

**AI Member** is an advanced autonomous bot plugin for the MYADS platform. It leverages Google Gemini AI to act as a living, breathing member of your community. The bot doesn't just sit there; it interacts, engages, and even helps moderate your groups.

## 🚀 Key Features

- **Autonomous Posting**: The bot creates high-quality, engaging community posts based on its defined persona.
- **Private Messaging**: Responds to private messages from users in a conversational manner.
- **Smart Mentions**: Automatically replies to users who mention it using `@username` in comments.
- **Engagement**: Comments on its own posts and others' to keep discussions alive.
- **Group Management**: 
    - Can own and manage a specific community group.
    - Posts group-exclusive content.
    - Moderates content: Automatically deletes posts or kicks members who violate group rules (AI-powered analysis).
- **Custom Persona**: Define exactly who the bot is—a friendly helper, a tech enthusiast, or a strict moderator.
- **Admin Commands**: Send a DM to the bot with `[CMD_POST: topic]` to force it to publish a post immediately.
- **Optimized Performance**: Uses a probabilistic "tick" system and global cooldowns to ensure minimal server impact.

## 🛠️ Installation

1. Upload the `ai-member` folder to your `/plugins` directory.
2. Go to **Admin Panel > Plugins** and activate "AI Member".
3. Navigate to **Admin Panel > AI Member** (Cpu icon in sidebar).
4. Enter your **Google Gemini API Key**.
5. Configure the persona, frequency, and optional group management settings.
6. Save and watch your community come to life!

## 🤖 Admin Commands

As an administrator, you can "order" the bot to post via Private Messages:
- Send: `Can you post about the benefits of SEO?`
- The bot will detect the request and publish a post to the community feed.
- Advanced: You can use the internal format `[CMD_POST: topic]` for precise control.

## ⚙️ Technical Details

- **AI Engine**: Google Gemini 3 Flash.
- **Trigger**: Asynchronous JS tick injected into the frontend (20% trigger rate by default).
- **Cooldown**: Customizable resting period between actions.

---
Created by [MrGhozzi](https://github.com/mrghozzi)
