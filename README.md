<div align="center">

<h2>
  <img src="public/favicon.svg" alt="Afor logo" width="32" height="32" align="absmiddle">
  Afor
</h2>

**Plan better. Work smarter.**

A Kanban-based project management and collaboration tool built with Laravel and Vue.

[![License: MIT](https://img.shields.io/badge/License-MIT-pink.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue-3-42b883?logo=vue.js&logoColor=white)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-3-9553e9?logo=inertia&logoColor=white)](https://inertiajs.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06b6d4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-4169E1?logo=postgresql&logoColor=white)](https://www.postgresql.org)

</div>

---

## Features

- **Free, with no limits** — unlimited workspaces, boards, lists, cards, and members. There are no paid tiers and nothing locked behind a plan.
- **Live by default** — when someone moves a card or edits a board, everyone who has it open sees it change instantly.
- **Boards you control** — the workspace owner decides exactly who can open each board, and everyone else never sees it exists.
- **Cards that hold real detail** — a rich-text editor for descriptions, with links, code blocks, and highlights.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | [Laravel 13](https://laravel.com) on PHP 8.4 |
| Authentication | [Laravel Fortify](https://laravel.com/docs/fortify) (login, two-factor authentication) |
| Realtime | [Laravel Reverb](https://reverb.laravel.com) + [Laravel Echo](https://laravel.com/docs/broadcasting) |
| Frontend | [Vue 3](https://vuejs.org) + TypeScript + [Inertia.js 3](https://inertiajs.com) |
| UI Components | [shadcn-vue](https://www.shadcn-vue.com) (Reka UI) + [Tailwind CSS v4](https://tailwindcss.com) + [Lucide](https://lucide.dev) icons |
| Rich Text | [Tiptap 3](https://tiptap.dev) |
| Drag and Drop | [vuedraggable](https://github.com/SortableJS/vue.draggable.next) (SortableJS) |
| Typed Routes | [Laravel Wayfinder](https://github.com/laravel/wayfinder) |
| Database | PostgreSQL |
| File Storage | Cloudflare R2 (production) / Local disk (development) |
| Build Tool | [Vite 8](https://vite.dev) |
| Testing | [Pest 5](https://pestphp.com) with Playwright browser tests |
| Code Quality | Laravel Pint, [Larastan](https://github.com/larastan/larastan), ESLint, Prettier, vue-tsc |
| CI | GitHub Actions |

---

## Requirements

- PHP 8.4
- Composer
- Node.js 22 or newer
- PostgreSQL

---

## Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/msllagas/afor.git
cd afor
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Set up environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure your `.env`

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=afor
DB_USERNAME=root
DB_PASSWORD=

# Use local disk for development — no cloud storage needed
FILESYSTEM_DISK=local
```

Create the `afor` database in PostgreSQL before running migrations.

### 5. Run migrations

```bash
php artisan migrate
```

### 6. Start the development server

```bash
composer run dev
```

This starts the Laravel server, queue worker, log viewer, and Vite dev server concurrently.

### 7. Start the Reverb Websocket server

```bash
php artisan reverb:start
```

Keep this running in a separate terminal alongside `composer run dev`.

---

## Running Tests

Tests run against a PostgreSQL database named `afor-test`, so create it first.

```bash
php artisan test
```

Or with Pest directly:

```bash
./vendor/bin/pest
```

Browser tests need a Playwright browser installed once:

```bash
npx playwright install chromium
```

---

## License

Afor is open source software licensed under the [MIT License](LICENSE).
