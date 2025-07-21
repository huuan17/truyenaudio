# 🚀 Hosting Deployment Guide

## Chrome/Puppeteer Setup for Different Hosting Environments

### 🔍 Quick Chrome Detection

Run this command to detect Chrome on your hosting environment:

```bash
node node_scripts/setup-chrome.js
```

This will:
- Detect available Chrome installations
- Generate environment variable setup
- Provide hosting-specific recommendations

### 🌐 Hosting Environment Setup

#### 1. **Shared Hosting (Limited Support)**

⚠️ **Most shared hosting providers don't support Puppeteer/Chrome**

**Check if supported:**
```bash
# SSH into your hosting account and run:
which google-chrome
which chromium-browser
```

**If Chrome is available:**
```bash
# Add to .env
PUPPETEER_EXECUTABLE_PATH="/usr/bin/google-chrome"
```

**Alternative solutions:**
- Use external crawling services
- Migrate to VPS hosting
- Use headless browser services (ScrapingBee, Puppeteer Cloud)

#### 2. **VPS/Cloud Hosting (Recommended)**

**Install Chrome:**
```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install -y google-chrome-stable

# CentOS/RHEL
sudo yum install -y google-chrome-stable

# Or install Chromium
sudo apt-get install -y chromium-browser
```

**Environment setup:**
```bash
# Add to .env
PUPPETEER_EXECUTABLE_PATH="/usr/bin/google-chrome-stable"
# or
PUPPETEER_EXECUTABLE_PATH="/usr/bin/chromium-browser"
```

**Memory requirements:**
- Minimum: 1GB RAM
- Recommended: 2GB+ RAM
- Chrome uses ~200-500MB per instance

#### 3. **Docker Deployment**

**Dockerfile example:**
```dockerfile
FROM node:18-slim

# Install Chrome
RUN apt-get update && apt-get install -y \
    wget \
    gnupg \
    ca-certificates \
    && wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | apt-key add - \
    && echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" > /etc/apt/sources.list.d/google-chrome.list \
    && apt-get update \
    && apt-get install -y google-chrome-stable \
    && rm -rf /var/lib/apt/lists/*

# Set Chrome path
ENV PUPPETEER_EXECUTABLE_PATH="/usr/bin/google-chrome"
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true

# Copy application
COPY . /app
WORKDIR /app

# Install dependencies
RUN npm install
RUN composer install --no-dev --optimize-autoloader

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0"]
```

#### 4. **Heroku Deployment**

**Add buildpacks:**
```bash
heroku buildpacks:add heroku/nodejs
heroku buildpacks:add heroku/php
heroku buildpacks:add https://github.com/jontewks/puppeteer-heroku-buildpack
```

**Environment variables:**
```bash
heroku config:set PUPPETEER_EXECUTABLE_PATH="/app/.apt/usr/bin/google-chrome-stable"
heroku config:set PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
```

#### 5. **DigitalOcean/Linode/AWS EC2**

**Install Chrome:**
```bash
# Ubuntu 20.04/22.04
curl -fsSL https://dl.google.com/linux/linux_signing_key.pub | sudo apt-key add -
echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" | sudo tee /etc/apt/sources.list.d/google-chrome.list
sudo apt-get update
sudo apt-get install -y google-chrome-stable
```

**Environment setup:**
```bash
# Add to .env
PUPPETEER_EXECUTABLE_PATH="/usr/bin/google-chrome"
```

### 📝 Deployment Steps

#### 1. **Prepare Local Environment**

```bash
# Test Chrome detection
node node_scripts/setup-chrome.js

# Test crawling locally
node node_scripts/crawl-production.js "https://example.com/chapter-" 1 1 "./test-output"
```

#### 2. **Upload to Hosting**

```bash
# Upload files (exclude node_modules, vendor)
rsync -av --exclude 'node_modules' --exclude 'vendor' ./ user@server:/path/to/app/

# Or use Git
git clone https://github.com/your-repo/audio-lara.git
```

#### 3. **Install Dependencies**

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies
npm install --production

# Install Chrome (if not available)
sudo apt-get install google-chrome-stable
```

#### 4. **Configure Environment**

```bash
# Copy environment file
cp .env.example .env

# Edit .env with hosting-specific settings
nano .env

# Set Chrome path
echo 'PUPPETEER_EXECUTABLE_PATH="/usr/bin/google-chrome"' >> .env
```

#### 5. **Setup Laravel**

```bash
# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 6. **Test Crawling**

```bash
# Test Chrome detection on hosting
node node_scripts/setup-chrome.js

# Test crawling functionality
php artisan crawl:stories --story_id=1
```

### 🔧 Troubleshooting

#### **Chrome Not Found**
```bash
# Check available browsers
which google-chrome
which chromium-browser
which chromium

# Install Chrome manually
wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | sudo apt-key add -
echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" | sudo tee /etc/apt/sources.list.d/google-chrome.list
sudo apt-get update
sudo apt-get install google-chrome-stable
```

#### **Memory Issues**
```bash
# Check available memory
free -h

# Add swap if needed (1GB)
sudo fallocate -l 1G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
```

#### **Permission Issues**
```bash
# Fix storage permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Fix Chrome permissions
sudo chmod +x /usr/bin/google-chrome
```

#### **Puppeteer Errors**
```bash
# Install missing dependencies
sudo apt-get install -y \
    libnss3 \
    libatk-bridge2.0-0 \
    libdrm2 \
    libxkbcommon0 \
    libxcomposite1 \
    libxdamage1 \
    libxrandr2 \
    libgbm1 \
    libxss1 \
    libasound2
```

### 🌟 Best Practices

1. **Use production-ready script:** `node_scripts/crawl-production.js`
2. **Set environment variables** in hosting control panel
3. **Monitor memory usage** during crawling
4. **Implement error handling** for failed crawls
5. **Use queue system** for large crawling jobs
6. **Regular cleanup** of temp files
7. **Backup storage** before major changes

### 📊 Performance Optimization

```bash
# Optimize Chrome for hosting
export PUPPETEER_ARGS="--no-sandbox --disable-setuid-sandbox --disable-dev-shm-usage --disable-gpu --single-process"

# Limit concurrent crawling
export MAX_CONCURRENT_CRAWLS=1

# Set memory limits
export NODE_OPTIONS="--max-old-space-size=1024"
```

### 🆘 Support

If you encounter issues:

1. **Check Chrome installation:** `node node_scripts/setup-chrome.js`
2. **Verify environment variables:** `echo $PUPPETEER_EXECUTABLE_PATH`
3. **Test basic crawling:** `node node_scripts/crawl-production.js`
4. **Check hosting logs** for specific error messages
5. **Contact hosting support** for Chrome/Puppeteer compatibility

### 📞 Hosting Provider Specific

- **cPanel/Shared:** Usually not supported
- **VPS/Dedicated:** Full support with Chrome installation
- **Heroku:** Supported with buildpacks
- **DigitalOcean:** Full support
- **AWS EC2:** Full support
- **Google Cloud:** Full support
- **Vercel/Netlify:** Limited (use external services)
