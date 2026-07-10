# ThreadForge API

Headless REST API for repurposing raw tech content into optimized X (Twitter) posts, built with Laravel 13 + the laravel/ai SDK.

## What it does

ThreadForge takes raw technical content (notes, blog markdown, GitHub README) and transforms it into punchy, style-compliant posts for X (Twitter). It separates **style** (Campaign Blueprints) from **writing** (AI generation) and ships with a conversational Ghostwriter agent that can refine posts via natural language.

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13.x / PHP 8.3 |
| Auth | Laravel Sanctum (Bearer Tokens) |
| Database | MySQL 8.0 (via Docker) |
| Queue | Database driver (async jobs) |
| AI | laravel/ai SDK v0.8 + Groq (gpt-oss-20b for structured, llama-3.3-70b for agent chat) |
| Docs | Scribe v5.11 (OpenAPI + Postman) |
| CI | GitHub Actions (PHPUnit) |

## Architecture

```
app/
├── Ai/
│   ├── Agents/
│   │   ├── Repurposing.php      # Structured Output agent
│   │   └── Ghostwriter.php      # Conversational agent with tools + memory
│   └── Tools/
│       ├── GetCampaignRules.php # Tool: fetches Blueprint rules from DB
│       └── GetPostHistory.php   # Tool: fetches post versions from DB
├── Http/
│   ├── Controllers/Api/         # Auth, Campaign, Content, Post, Chat
│   ├── Requests/                # Form Requests (validation)
│   └── Resources/               # API Resources (JSON formatting)
├── Jobs/
│   └── RepurposeContentJob.php  # Async AI generation (202 Accepted)
└── Models/                      # User, Campaign, RawContent, Post, PostVersion
```

## Prerequisites

- PHP 8.3
- Composer
- Docker Desktop (for MySQL + phpMyAdmin)
- Git

## Setup

### 1. Start MySQL + phpMyAdmin with Docker

```bash
docker compose up -d
```

This starts:
- MySQL on port `3307`
- phpMyAdmin on [http://localhost:8082](http://localhost:8082) (login: `root` / `secret`)

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Make sure your `.env` has these lines for Docker:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=threadforge
DB_USERNAME=root
DB_PASSWORD=secret
```

Configure your Groq API key:

```ini
GROQ_API_KEY=your_groq_api_key
AI_TEXT_PROVIDER=groq
AI_TEXT_MODEL=openai/gpt-oss-20b
```

### 4. Migrate and seed

```bash
php artisan migrate --force
php artisan db:seed
```

This creates the database and inserts a demo account with sample data.

### 5. Start the application

You need **two** terminals running:

**Terminal 1 — API server**
```bash
php artisan serve --port=8001
```

**Terminal 2 — Queue worker (REQUIRED for AI generation)**
```bash
php artisan queue:work --tries=2 --timeout=120
```

Without the worker, AI repurposing jobs will pile up in the queue and never execute.

## Demo Account

After seeding, a demo account is available:

- **Email**: `demo@threadforge.dev`
- **Password**: `password`

This account includes a pre-created "Tech Twitter Pro" campaign and a sample post.

## API Endpoints

### Auth

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/register` | Create account + get Bearer token |
| POST | `/api/login` | Login + get Bearer token |
| POST | `/api/logout` | Revoke current token |

### Campaigns / Blueprints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/campaigns` | List all campaigns (+ posts count) |
| POST | `/api/campaigns` | Create a Blueprint |
| GET | `/api/campaigns/{id}` | Campaign detail |

### Content / Repurposing

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/content/repurpose` | Submit raw content (202 Accepted, async) |
| GET | `/api/posts` | List generated posts |
| GET | `/api/posts/{id}` | Post detail |
| PATCH | `/api/posts/{id}/status` | Update status (draft / archived / posted) |

### Ghostwriter Chat

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/posts/{id}/chat` | Chat with the Ghostwriter agent |

## Testing

Run the test suite:

```bash
php artisan test
```

The project includes **24 Feature tests** covering authentication, campaigns, content repurposing, and posts. Tests run on an in-memory SQLite database and do not require MySQL or Groq API calls.

## Continuous Integration

GitHub Actions automatically runs the test suite on every push and pull request.

See `.github/workflows/ci.yml` for the workflow configuration.

## Documentation

Interactive API docs are available at `http://localhost:8001/docs` after running:

```bash
php artisan scribe:generate
```

Postman collection: `storage/app/private/scribe/collection.json`

## Key Design Decisions

- **Structured Output**: The `Repurposing` agent enforces a strict JSON schema (`hook_propose`, `body_points`, `technicalreadabilityscore`, `suggested_hashtags`, `tonecompliancejustification`).
- **Async Processing**: `POST /api/content/repurpose` returns `202 Accepted` immediately. The AI call runs in a queued job (`RepurposeContentJob`).
- **Zero Hallucination**: The Ghostwriter agent uses PHP tools (`GetCampaignRules`, `GetPostHistory`) that query the real database instead of inventing data.
- **Conversation Memory**: The `RemembersConversations` trait persists chat history via the SDK's `agent_conversations` tables.
- **Eloquent Casts**: `body_points` and `suggested_hashtags` are cast as native PHP `array` — no manual `json_encode`/`json_decode`.
- **Provider-Agnostic**: Swap AI provider by changing `.env` (`AI_TEXT_PROVIDER` + `AI_TEXT_MODEL`).

## License

MIT
