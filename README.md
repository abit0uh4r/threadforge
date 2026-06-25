# ThreadForge API

Headless REST API for repurposing raw tech content into optimized X (Twitter) posts, built with Laravel 13 + the laravel/ai SDK.

## What it does

ThreadForge takes raw technical content (notes, blog markdown, GitHub README) and transforms it into punchy, style-compliant posts for X (Twitter). It separates **style** (Campaign Blueprints) from **writing** (AI generation) and ships with a conversational Ghostwriter agent that can refine posts via natural language.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 13.x / PHP 8.3 |
| Auth | Laravel Sanctum (Bearer Tokens) |
| Database | MySQL 8.4 (Laragon) |
| Queue | Database driver (async jobs) |
| AI | laravel/ai SDK v0.8 + Groq (llama-3.3-70b-versatile) |
| Docs | Scribe v5.11 (OpenAPI + Postman) |

## Architecture

```
app/
├── Ai/
│   ├── Agents/
│   │   ├── Repurposing.php      # Structured Output agent (US5)
│   │   └── Ghostwriter.php      # Conversational agent with tools + memory (US7-9)
│   └── Tools/
│       ├── GetCampaignRules.php # Tool: fetches Blueprint rules from DB
│       └── GetPostHistory.php   # Tool: fetches post versions from DB
├── Http/
│   ├── Controllers/Api/         # Auth, Campaign, Content, Post, Chat
│   ├── Requests/               # Form Requests (validation)
│   └── Resources/               # API Resources (JSON formatting)
├── Jobs/
│   └── RepurposeContentJob.php  # Async AI generation (202 Accepted)
└── Models/                      # User, Campaign, RawContent, Post, PostVersion
```

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configure MySQL in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=threadforge
DB_USERNAME=root
DB_PASSWORD=

# Configure Groq AI in .env
GROQ_API_KEY=your_groq_api_key
AI_TEXT_PROVIDER=groq
AI_TEXT_MODEL=llama-3.3-70b-versatile

# Migrate + seed
php artisan migrate --force
php artisan db:seed

# Run server + queue worker
php artisan serve
php artisan queue:work
```

## API Endpoints

### Auth (US1)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/register` | Create account + get Bearer token |
| POST | `/api/login` | Login + get Bearer token |
| POST | `/api/logout` | Revoke current token |

### Campaigns / Blueprints (US2-3)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/campaigns` | List all campaigns (+ posts count) |
| POST | `/api/campaigns` | Create a Blueprint |
| GET | `/api/campaigns/{id}` | Campaign detail |

### Content / Repurposing (US4-6)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/content/repurpose` | Submit raw content (202 Accepted, async) |
| GET | `/api/posts` | List generated posts |
| GET | `/api/posts/{id}` | Post detail |
| PATCH | `/api/posts/{id}/status` | Update status (draft/archived/posted) |

### Ghostwriter Chat (US7-9)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/posts/{id}/chat` | Chat with the Ghostwriter agent |

## Key Design Decisions

- **Structured Output**: The `Repurposing` agent enforces a strict JSON schema with the exact keys required by the cahier des charges (`hook_propose`, `body_points`, `technicalreadabilityscore`, `suggested_hashtags`, `tonecompliancejustification`).
- **Async Processing**: `POST /api/content/repurpose` returns `202 Accepted` immediately. The AI call runs in a queued job (`RepurposeContentJob`).
- **Zero Hallucination**: The Ghostwriter agent uses PHP tools (`GetCampaignRules`, `GetPostHistory`) that query the real database instead of inventing data.
- **Conversation Memory**: The `RemembersConversations` trait persists chat history via the SDK's `agent_conversations` tables.
- **Eloquent Casts**: `body_points` and `suggested_hashtags` are cast as native PHP `array` — no manual `json_encode`/`json_decode`.
- **Provider-Agnostic**: Swap AI provider by changing `.env` (`AI_TEXT_PROVIDER` + `AI_TEXT_MODEL`). xAI/Grok is supported via `XAI_API_KEY`.

## Documentation

Interactive API docs are available at `http://localhost/docs` (Scribe) after running `php artisan scribe:generate`.

Postman collection: `storage/app/private/scribe/collection.json`

## Testing

```bash
php artisan test
```

## License

MIT