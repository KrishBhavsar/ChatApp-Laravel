# 💬 ChatMessage

A real-time one-to-one chat application built with **Laravel 11**. It exposes a token-authenticated REST API and broadcasts new messages over WebSockets (Pusher / Laravel Reverb), so conversations update live without polling. A complete, dependency-free chat UI ships in [`public/app.html`](public/app.html).

## Features

- **Token auth** — register / login with Laravel Sanctum bearer tokens.
- **1:1 messaging** — send messages and load conversation history.
- **Real-time delivery** — messages are broadcast on private channels; the UI listens with Laravel Echo + Pusher and refreshes instantly.
- **Contacts list** — conversations with last-message previews and unread counts.
- **User search** — find users by name/email to start a new conversation.
- **Roles & admin view** — `admin` users can view all chats system-wide (guarded by an `admin` middleware).
- **API docs** — auto-generated with [Scribe](https://scribe.knuckles.wtf/) and a Postman collection under [`Postman/`](Postman/).

## Architecture

The backend follows an **Interface → Repository → Service → Controller** pattern:

| Layer | Location | Responsibility |
| --- | --- | --- |
| Controller | `app/Http/Controllers` | Thin HTTP entrypoints, delegate to services |
| Service | `app/Services` | Orchestrates business logic + broadcasting |
| Repository | `app/Repository` | Eloquent data access (implements the interfaces) |
| Interface | `app/Interface` | Contracts bound in the service provider |
| Event | `app/Events/MessageSentEvent.php` | Broadcasts new messages on private channels |
| Resource | `app/Http/Resources` | Shapes JSON responses |
| Request | `app/Http/Requests` | Validation (register, login, send message) |

Custom Artisan generators are included: `make:interface`, `make:repository`, `make:service`, and `make:admin` (see `app/Console/Commands`).

## Requirements

- PHP **8.2+**
- Composer
- Node.js & npm
- MySQL (the app defaults to a `chat_message` database)
- A Pusher account **or** a local Laravel Reverb server for real-time broadcasting

## Getting Started

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Configure .env — set your DB_* credentials and broadcasting keys
#    (see "Configuration" below), then create the database.

# 4. Run migrations
php artisan migrate

# 5. Build frontend assets
npm run dev
```

### Run everything at once

The `composer dev` script boots the server, queue worker, log tailer, and Vite together:

```bash
composer dev
```

Or run pieces individually:

```bash
php artisan serve                       # HTTP server
php artisan queue:listen --tries=1      # queued broadcasts
php artisan reverb:start                # if using Laravel Reverb for WebSockets
```

## Configuration

Broadcasting is driven by `BROADCAST_CONNECTION=pusher`. Set these in `.env`:

```env
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=your-cluster
```

The frontend picks up `VITE_PUSHER_*` values automatically. If you use the standalone `public/app.html`, update the `PUSHER` config block near the top of its `<script>` with your public **key** and **cluster**.

> **Note:** private channels are authorized through `/broadcasting/auth`, which is protected by `auth:sanctum`. The UI sends the bearer token via a custom Echo authorizer — see the comments in `public/app.html`.

## Using the UI

Serve the app and open the chat client at:

```
http://localhost:8000/app.html
```

Register or log in, search a user by name to start a conversation, and send messages — they appear in real time for both participants. Admin accounts see a **"view all chats"** link in the sidebar.

## API Reference

Base URL: `/api`. All chat routes require an `Authorization: Bearer <token>` header.

### Auth

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/api/register` | Create an account (`name`, `email`, `password`, `phone_number`) |
| `POST` | `/api/login` | Log in, returns a Sanctum token |
| `GET`  | `/api/user` | Current user profile *(auth)* |
| `GET`  | `/api/logout` | Revoke token *(auth)* |

### Chat *(all require auth)*

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET`  | `/api/users/search?search=<term>` | Search users to start a chat |
| `POST` | `/api/chat/send` | Send a message |
| `GET`  | `/api/chat/history/{sender_id}/{receiver_id}` | Conversation history |
| `GET`  | `/api/chat/contacts/{sender_id}` | List conversations |
| `GET`  | `/api/chat/all` | All messages system-wide *(admin only)* |

### Send message payload

```json
{
  "sender_id": 1,
  "receiver_id": 2,
  "message_text": "Hello!",
  "message_type": "text",
  "attachment_url": null,
  "entity_type": "project",
  "entity_id": 1
}
```

- `message_type`: `text`
- `receiver_id` must differ from `sender_id`.

Full generated documentation is available via Scribe (see `.scribe/`) and as a Postman collection in [`Postman/`](Postman/).

## Data Model

`chat_messages` (primary key `message_id`):

| Column | Type | Notes |
| --- | --- | --- |
| `sender_id` / `receiver_id` | FK → `users.id` | cascade on delete |
| `message_text` | text | nullable |
| `message_type` | enum | text |
| `attachment_url` | string | nullable |
| `is_read`, `read_at`, `sent_at` | bool / timestamps | delivery state |



## Tech Stack

Laravel 11 · Sanctum · Reverb · Pusher · Laravel Echo · Scribe · Maatwebsite/Excel · Tailwind CSS · Vite

## License

Released under the [MIT License](LICENSE).
