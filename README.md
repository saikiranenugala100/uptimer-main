# Laravel Application Setup

This repository contains automated setup scripts to get your Laravel application running quickly on any platform.

## Quick Start

### Mac/Linux
```bash
./setup.sh
```

### Windows
```bash
# Use Git Bash or WSL
./setup.sh
```

### Service Management
```bash
# Check service status
./setup.sh status

# Stop all services
./setup.sh stop

# Restart services
./setup.sh restart

# View recent logs
./setup.sh logs

# Run monitoring check
./setup.sh monitor
```

## What the scripts do

The setup scripts will automatically:

1. **Install Dependencies**
   - PHP (with required extensions)
   - Composer
   - Node.js & NPM
   - MySQL (optional)

2. **Configure Environment**
   - Copy `.env.example` to `.env`
   - Prompt for database configuration
   - Update environment variables

3. **Setup Laravel**
   - Install PHP dependencies via Composer
   - Install Node.js dependencies via NPM
   - Generate application key
   - Generate Wayfinder routes
   - Build frontend assets (Vite)
   - Run database migrations
   - Seed the database
   - Clear all caches

4. **Start All Services**
   - Launch Laravel development server on `http://localhost:8000`
   - Start queue worker for background jobs
   - Start website monitoring (checks every 15 minutes)
   - Start Laravel task scheduler
   - Monitor service health and auto-restart if needed

## Manual Requirements (if scripts fail)

### Prerequisites
- **PHP 8.1+** with extensions: `mbstring`, `xml`, `ctype`, `json`, `bcmath`, `gd`, `pdo_mysql`
- **Composer** - PHP dependency manager
- **Node.js 18+** - JavaScript runtime
- **MySQL/MariaDB** - Database server

### Platform-specific Installation

#### Mac (Homebrew)
```bash
# Install Homebrew
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Install dependencies
brew install php composer node mysql
brew services start mysql
```

#### Ubuntu/Debian
```bash
sudo apt update
sudo apt install php php-cli php-fpm php-json php-common php-mysql php-zip php-gd php-mbstring php-curl php-xml php-pear php-bcmath nodejs npm mysql-server

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### Windows
1. Install [PHP](https://windows.php.net/download/)
2. Install [Composer](https://getcomposer.org/Composer-Setup.exe)
3. Install [Node.js](https://nodejs.org/)
4. Install [MySQL](https://dev.mysql.com/downloads/installer/) or use XAMPP/WAMP

## Manual Setup (if needed)

If the automated scripts don't work, follow these steps:

1. **Clone and navigate to project**
   ```bash
   cd /path/to/your/project
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Setup environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database in `.env`**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Generate routes and build assets**
   ```bash
   php artisan wayfinder:generate
   npm run build
   ```

6. **Setup database**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

7. **Start the server**
   ```bash
   php artisan serve
   ```

## Troubleshooting

### Common Issues

**PHP Extensions Missing**
```bash
# Ubuntu/Debian
sudo apt install php-extension-name

# Mac
brew install php
```

**Node.js/NPM Issues**
```bash
# Clear npm cache
npm cache clean --force

# Delete node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
```

**Database Connection Issues**
- Verify MySQL is running
- Check database credentials in `.env`
- Ensure database exists
- Check firewall/port access

**Permission Issues (Linux/Mac)**
```bash
sudo chown -R $USER:$USER .
chmod -R 755 storage bootstrap/cache
```

**Build Failures**
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Rebuild assets
npm run build
```

## Development

After setup, your application will be available at:
- **Local**: http://localhost:8000
- **Network**: Check console output for network IP

### Available Commands

- `php artisan serve` - Start development server
- `npm run dev` - Start Vite development server (hot reload)
- `npm run build` - Build production assets
- `php artisan migrate` - Run database migrations
- `php artisan db:seed` - Seed database with sample data

## Production Deployment

For production, use a proper web server (Apache/Nginx) instead of `php artisan serve`, and ensure:

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false` in `.env`
3. Run `php artisan config:cache`
4. Run `php artisan route:cache`
5. Run `php artisan view:cache`
6. Set proper file permissions
7. Use HTTPS

## Support

If you encounter issues:

1. Check the troubleshooting section above
2. Ensure all prerequisites are installed
3. Try running the setup script again
4. Check Laravel logs in `storage/logs/`