# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Your Role

You are backend-god described in following document: @/Users/linporu/Documents/world-of-code/evoprompt/prompts/backend-god.md

## Project Overview

This is a When2Meet clone built with Laravel 12, featuring event scheduling functionality where users can create events, share links, and mark their availability on time grids. The project serves as a learning exercise for backend development with Laravel and Blade templates.

## Development Commands

### Local Development

```bash
# Start development server (runs all services concurrently)
composer run dev

# Individual services:
php artisan serve           # Laravel server
php artisan queue:listen --tries=1  # Queue worker
php artisan pail --timeout=0        # Log monitoring
npm run dev                 # Vite dev server

# Build frontend assets
npm run build
```

### Testing

```bash
# Run all tests
composer run test
# Or directly:
php artisan test

# Code formatting (Laravel Pint)
./vendor/bin/pint
```

### Database Operations

```bash
php artisan migrate         # Run migrations
php artisan migrate:fresh   # Fresh migration
php artisan tinker         # Interactive REPL
```

## Architecture

### Backend Structure

-   **Framework**: Laravel 12 with Blade templates
-   **Database**: SQLite (local), designed for AWS deployment
-   **Testing**: Pest PHP testing framework
-   **Queue System**: Laravel queues for background processing

### Frontend Structure

-   **Build Tool**: Vite with Laravel plugin
-   **CSS Framework**: Tailwind CSS v4
-   **Assets**: Located in `resources/css/` and `resources/js/`
-   **Views**: Blade templates in `resources/views/`

### Key Directories

-   `app/Http/Controllers/` - Request handling logic
-   `app/Models/` - Eloquent models and database interactions
-   `database/migrations/` - Database schema definitions
-   `routes/web.php` - Web route definitions
-   `config/` - Application configuration files

## Core Features (from PRD)

The application implements these key features:

1. **Event Creation** - Users create events with date/time selection modes
2. **Event Participation** - Join via shared links with optional passwords
3. **Availability Marking** - Time grid interface for selecting available slots
4. **Group Visualization** - Color-coded availability overlap display

## Deployment Target

Designed for AWS EC2 deployment with SELinux, transitioning from SQLite to a production database (MySQL/PostgreSQL).
