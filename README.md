# Mini Wallet - High-Performance Digital Wallet Application

A simplified digital wallet application built with Laravel 12 and Vue.js 3, designed to handle high-traffic financial transactions with real-time updates.

## ⚠️ Project status

This project is still in progress.
Core architecture, models, migrations, architecture tests and code quality standards, and business logic foundations are completed.

## Requirements

- PHP 8.4+
- Laravel 12+
- Vue 3+
- MySQL 8.0+
- Redis 7+
- Pusher Account (for real-time features)

## Installation

### Option 1: Using Laravel Sail (Recommended)

```bash
# Clone the repository
git clone https://github.com/olivsinz/mini-wallet mini-wallet

cd mini-wallet

# Install Composer dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Start Docker containers
./vendor/bin/sail up -d

# Run migrations
./vendor/bin/sail artisan migrate

# Seed database with test users
./vendor/bin/sail artisan db:seed

# Install NPM dependencies
./vendor/bin/sail npm install

# Build frontend assets
./vendor/bin/sail npm run dev
```

### Option 2: Manual Installation

```bash
# Clone the repository
git clone <repository-url> mini-wallet
cd mini-wallet

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mini_wallet
DB_USERNAME=root
DB_PASSWORD=your_password

# Configure Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Run migrations and seeders
php artisan migrate
php artisan db:seed

# Build assets and start application
composer run dev
```

## 🔑 Pusher Configuration

1. Create a free account at [pusher.com](https://pusher.com)
2. Create a new app/channel
3. Copy credentials to your `.env`:

```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

4. Restart your development server
