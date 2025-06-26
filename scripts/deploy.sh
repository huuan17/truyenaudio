#!/bin/bash

# Audio Lara Deployment Script
# Usage: ./scripts/deploy.sh [environment]

ENVIRONMENT=${1:-production}

echo "🚀 Deploying Audio Lara to $ENVIRONMENT environment..."

# 1. Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# 2. Install/Update dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

# 3. Install NPM dependencies (if needed)
if [ -f "package.json" ]; then
    echo "📦 Installing NPM dependencies..."
    npm ci --production
    npm run build
fi

# 4. Environment configuration
echo "⚙️ Configuring environment..."
if [ ! -f ".env" ]; then
    echo "❌ .env file not found! Please create it from .env.example"
    exit 1
fi

# 5. Generate application key if needed
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    php artisan key:generate
fi

# 6. Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 7. Clear and cache configuration
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo "📝 Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Set proper permissions
echo "🔒 Setting permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 9. Create required directories
echo "📁 Creating required directories..."
mkdir -p storage/app/truyen
mkdir -p storage/app/video_assets
mkdir -p storage/app/videos
mkdir -p public/images/stories

# 10. Optimize for production
if [ "$ENVIRONMENT" = "production" ]; then
    echo "⚡ Optimizing for production..."
    php artisan optimize
fi

echo "✅ Deployment completed successfully!"
echo ""
echo "📋 Post-deployment checklist:"
echo "1. Verify APP_URL in .env matches your domain"
echo "2. Check file permissions for storage directories"
echo "3. Test video generation functionality"
echo "4. Verify SSL certificate is working"
echo "5. Check all routes are accessible"
