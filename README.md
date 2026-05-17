<div align="center">

# <span style="color:#c0325a">Afor</span>

**Plan better. Work smarter.**

A Kanban-based project management and collaboration tool built with Laravel and Vue.

[![License: MIT](https://img.shields.io/badge/License-MIT-pink.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue-3-42b883?logo=vue.js&logoColor=white)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-3-9553e9?logo=inertia&logoColor=white)](https://inertiajs.com)

</div>

---

## Features

- **Boards** — create and manage multiple Kanban boards per workspace
- **Lists & Cards** — organize work with drag-and-drop board lists and cards
- **Workspaces** — invite registered users to collaborate in shared workspaces
- **Customization** — change board list colors, upload a profile picture
- **Themes** — light, dark, and system theme support
- **Authentication** — secure email and password login with session management

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | [Laravel](https://laravel.com) |
| Realtime | [Laravel Reverb](https://reverb.laravel.com) |
| Frontend | [Vue 3](https://vuejs.org) + [Inertia.js](https://inertiajs.com) |
| UI Components | [shadcn-vue](https://www.shadcn-vue.com) + [Tailwind CSS v4](https://tailwindcss.com) |
| Database | MySQL |
| File Storage | Cloudflare R2 (production) / Local disk (development) |
| Testing | [Pest](https://pestphp.com) |

---

## Requirements

- PHP 8.4
- Composer
- Node.js 22
- MySQL

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
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=afor
DB_USERNAME=root
DB_PASSWORD=

# Use local disk for development — no cloud storage needed
FILESYSTEM_DISK=local
```

### 5. Run migrations

```bash
php artisan migrate
```

### 6. Start the development server

```bash
composer run dev
```

This starts the Laravel server, queue worker, and Vite dev server concurrently.

### 7. Start the Reverb Websocket server

```bash
php artisan reverb:start
```

Keep this running in a separate terminal alongside `composer run dev`.

---

## Running Tests

```bash
php artisan test
```

Or with Pest directly:

```bash
./vendor/bin/pest
```

---

## License

Afor is open source software licensed under the [MIT License](LICENSE).
