# Lavendr

Laravel Vending Machine. Brrr...

This is a simulation software of vending machine based on [Business Requirements Document (BRD)](./BUSINESS_REQUIREMENTS_DOCUMENT.md). The document get from [Laravel Vending Machines API Project: Step-by-Step (incl Filament & Pest) by Laravel Daily](https://youtu.be/aMlWQijuGJM?si=p3Ie4_qqgFEvqMgM). I'm using TALL stack (Tailwind Alpine Laravel Livewire) instead of Filament.

## Software Specifications

- Laravel Herd
- Laravel 13.x
- PHP 8.4
- Bun 1.3.x
- SQLite for database

## Installation

```sh
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed # for seeder
bun install
bun run dev
```

Since I'm using Laravel Herd, I register the project to be accessible with local domain `https://lavendr.test`

Testing accounts:

**superadmin**

```
email: superadmin@example.com
password: password
```

```
email: manager@example.com
password: password
```

```
email: employee1@example.com
OR
email: employee2@example.com
OR
email: employee3@example.com
password: password
```
