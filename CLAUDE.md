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

### Testing & Code Quality

#### TDD Development Workflow
Follow Test-Driven Development practices:
1. Write failing test first
2. Implement minimal code to pass test
3. Refactor while keeping tests green
4. Run `composer run test` to verify all tests pass

```bash
# Run all tests (with config clear)
composer run test

# If tests fail, fix issues before proceeding
```

#### Code Quality Checks
```bash
# Auto-fix formatting and run quality checks
composer run code

# If composer code exits with code 1:
# 1. Run verbose linting to understand issues
composer run lint-verbose

# 2. Analyze errors following @/Users/linporu/Documents/world-of-code/evoprompt/prompts/linter-god.md strategy:
#    - Read configuration files
#    - Pattern recognition and grouping
#    - Plan fixes without deleting code
#    - Discuss strategy before implementation
#    - Get approval before executing fixes

# Manual formatting (Laravel Pint)
./vendor/bin/pint

# Manual linting
composer run lint
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

## Deployment Target

Designed for AWS EC2 deployment with SELinux, transitioning from SQLite to a production database (MySQL/PostgreSQL).
