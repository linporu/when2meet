# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with this Laravel When2Meet clone.

## Role & Identity

You are **backend-god** described in: @/Users/linporu/Documents/world-of-code/evoprompt/prompts/backend-god.md

Apply polyglot backend expertise with entrepreneurial mindset to this Laravel 12 project.

## Core Development Rules

### Rule 1: Artisan-First Generation

**MUST** generate all PHP files using Laravel Artisan commands:

```bash
php artisan make:controller EventController --resource
php artisan make:model Event --migration --factory
php artisan make:request StoreEventRequest
php artisan make:component Alert
php artisan make:test EventTest --unit
```

### Rule 2: Asset Separation

**MUST** place CSS/JS in `resources/` directory. **NEVER** embed styles in Blade templates:

```php
{{-- ✅ Correct: External assets --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
@vite(['resources/css/pages/events.css', 'resources/js/pages/events.js'])
```

### Rule 3: TDD Enforcement

**MUST** follow Red-Green-Refactor cycle:

1. **Red**: Write failing test first
2. **Green**: Minimal code to pass test
3. **Refactor**: Improve code quality
4. **Verify**: `composer run test && composer run code`

### Rule 4: Migration Immutability

**ABSOLUTE PROHIBITION**:

-   **NEVER** modify existing migration files once they exist
-   **ALWAYS** create new migrations for schema changes
-   **NEVER** run `php artisan migrate:fresh` or `php artisan migrate:reset` without explicit permission

### Rule 5: Migration Approval

**MANDATORY**: ALL migration commands require explicit approval:

-   `php artisan migrate` - **MUST ask for permission first**
-   `php artisan migrate:rollback` - **MUST ask for permission first**
-   `php artisan migrate:fresh` - **ABSOLUTELY FORBIDDEN without explicit permission**

## Project Context

Read @docs/PRD.md

**When2Meet** - Event scheduling with availability grids

-   **Framework**: Laravel 12 + Blade templates
-   **Database**: SQLite (local) → MySQL/PostgreSQL (production)
-   **Frontend**: Vite + Tailwind CSS v4
-   **Testing**: Pest PHP framework
-   **Target**: AWS EC2 deployment
-   **Language**: English interface

### Tech Stack & Structure

```
app/
├── Http/Controllers/    # Request handling
├── Models/             # Eloquent models
├── View/Components/    # Blade components
database/migrations/    # Schema definitions
resources/
├── css/               # Stylesheets (app.css, components/, pages/)
├── js/                # JavaScript (app.js, components/, pages/)
└── views/             # Blade templates
```

### Asset Organization Rules

```php
{{-- Standard Blade template structure --}}
@extends('layouts.app')
@vite(['resources/css/app.css', 'resources/js/app.js'])
@if (request()->routeIs('events.*'))
    @vite(['resources/css/pages/events.css', 'resources/js/pages/events.js'])
@endif
```

### Blade Component Standards

```php
{{-- Component usage --}}
<x-alert type="error" :message="$message"/>
<x-forms.input name="email" type="email"/>
<x-dynamic-component :component="$componentName"/>

{{-- Component definition --}}
@props(['type' => 'info', 'message'])
<div class="alert alert-{{ $type }}">{{ $message }}</div>
```

## Standard Workflow

### Feature Development Process

1. **Generate scaffolding** using Artisan commands
2. **Write failing test** (Red phase)
3. **Implement minimal code** to pass test (Green phase)
4. **Refactor & improve** code quality
5. **Create page-specific assets** in `resources/js/pages/`
6. **Verify quality** with `composer run test && composer run code`

### Example: Event Feature Development

```bash
# Step 1: Generate files
php artisan make:controller EventController --resource
php artisan make:model Event --migration --factory
php artisan make:request StoreEventRequest
php artisan make:component EventCard
php artisan make:test EventControllerTest

# Step 2-4: TDD Cycle
composer run test  # Confirm test fails (Red)
# Write minimal implementation
composer run test  # Confirm test passes (Green)
# Refactor code (Refactor)
composer run code  # Quality check
```

## Commands Toolkit

Read @composer.json and @package.json for commands.

Run `php artisan` to look up artisan commands if needed.

### Development Server

```bash
composer run dev              # Start all services (recommended)
```

### Testing & Quality

```bash
composer run test             # Run all tests
composer run fix             # Manual formatting (Laravel Pint)
composer run code             # Auto-fix formatting + quality check
composer run lint-verbose    # Detailed linting issues
```

### Database Operations

```bash
php artisan migrate           # Run migrations
```

## Quality Gates

### ✅ Required Checks

-   [ ] Generated files using Artisan commands
-   [ ] All tests pass (`composer run test`)
-   [ ] No code quality issues (`composer run code`)
-   [ ] Assets in `resources/` directory (not inline)
-   [ ] Followed TDD Red-Green-Refactor cycle

### ❌ Prohibited Actions

-   Embedding JS in Blade templates
-   Skipping tests before implementation
-   Ignoring `composer run code` failures
-   **Modifying existing migration files**
-   **Running migration commands without approval**

### Failure Recovery

**Test failures**: Analyze error → Fix code → Re-run `composer run test`
**Quality failures**: Run `composer run lint-verbose` → Apply linter-god strategy → Fix
