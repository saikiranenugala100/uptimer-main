#!/bin/bash

# Laravel Uptime Monitor - All-in-One Script
# Usage: ./setup.sh [command]
# Commands: install, start, stop, restart, status, logs, monitor, help

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Load service status
load_status() {
    if [[ -f .services_status ]]; then
        source .services_status
        return 0
    else
        return 1
    fi
}

# Check if services are running
check_services() {
    if ! load_status; then
        print_error "No services running. Use './setup.sh start' to start services."
        return 1
    fi

    local running=0
    local total=4

    echo "Checking service status..."

    if kill -0 $LARAVEL_SERVER_PID 2>/dev/null; then
        print_success "✅ Laravel Server (PID: $LARAVEL_SERVER_PID) - $APP_URL"
        ((running++))
    else
        print_error "❌ Laravel Server (PID: $LARAVEL_SERVER_PID) - Not running"
    fi

    if kill -0 $QUEUE_WORKER_PID 2>/dev/null; then
        print_success "✅ Queue Worker (PID: $QUEUE_WORKER_PID) - Processing jobs"
        ((running++))
    else
        print_error "❌ Queue Worker (PID: $QUEUE_WORKER_PID) - Not running"
    fi

    if kill -0 $MONITOR_LOOP_PID 2>/dev/null; then
        print_success "✅ Monitor Loop (PID: $MONITOR_LOOP_PID) - Checking websites"
        ((running++))
    else
        print_error "❌ Monitor Loop (PID: $MONITOR_LOOP_PID) - Not running"
    fi

    if kill -0 $SCHEDULER_PID 2>/dev/null; then
        print_success "✅ Task Scheduler (PID: $SCHEDULER_PID) - Running scheduled tasks"
        ((running++))
    else
        print_error "❌ Task Scheduler (PID: $SCHEDULER_PID) - Not running"
    fi

    echo "Services running: $running/$total"
    echo "Started at: $STARTED_AT"

    return $((total - running))
}

# Stop all services
stop_services() {
    print_status "Stopping Laravel Uptime Monitor services..."

    # Find and stop all PHP artisan processes
    local pids=$(pgrep -f "php artisan")
    
    if [[ -n "$pids" ]]; then
        print_status "Found PHP artisan processes: $pids"
        
        # Graceful shutdown first
        kill $pids 2>/dev/null || true
        sleep 3
        
        # Check if any are still running and force kill
        local remaining_pids=$(pgrep -f "php artisan")
        if [[ -n "$remaining_pids" ]]; then
            print_status "Force killing remaining processes: $remaining_pids"
            kill -9 $remaining_pids 2>/dev/null || true
        fi
    else
        print_status "No PHP artisan processes found"
    fi

    # Also try to stop using status file if it exists
    if load_status; then
        print_status "Stopping services from status file..."
        kill $LARAVEL_SERVER_PID $QUEUE_WORKER_PID $MONITOR_LOOP_PID $SCHEDULER_PID 2>/dev/null || true
        sleep 2
        kill -9 $LARAVEL_SERVER_PID $QUEUE_WORKER_PID $MONITOR_LOOP_PID $SCHEDULER_PID 2>/dev/null || true
    fi

    # Always clean up status file
    if [[ -f .services_status ]]; then
        print_status "Removing services status file..."
        rm -f .services_status
    fi

    # Verify all services are stopped
    local remaining=$(pgrep -f "php artisan")
    if [[ -n "$remaining" ]]; then
        print_warning "Some processes may still be running: $remaining"
        print_warning "You can manually kill them with: kill -9 $remaining"
    else
        print_success "All services stopped and status file removed."
    fi
}

# Show recent logs
show_logs() {
    print_status "Recent application logs..."
    if [[ -f storage/logs/laravel.log ]]; then
        tail -n 50 storage/logs/laravel.log
    else
        print_warning "No log file found at storage/logs/laravel.log"
    fi
}

# Run monitoring check
run_monitor() {
    print_status "Running website monitoring check..."
    env -i HOME="$HOME" PATH="$PATH" USER="$USER" php artisan monitor:websites
}

# Detect OS
detect_os() {
    if [[ "$OSTYPE" == "darwin"* ]]; then
        echo "macos"
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        echo "linux"
    elif [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "cygwin" ]]; then
        echo "windows"
    else
        echo "unknown"
    fi
}

# Install PHP using php.new
install_php() {
    print_status "Installing PHP 8.4..."

    local os=$(detect_os)

    if command -v php >/dev/null 2>&1; then
        local php_version=$(php -v | head -n1 | cut -d' ' -f2 | cut -d'.' -f1,2)
        if [[ $(echo "$php_version >= 8.1" | bc 2>/dev/null || echo "0") == "1" ]]; then
            print_success "PHP $php_version already installed"
            return 0
        fi
    fi

    case $os in
        "macos")
            if ! command -v brew >/dev/null 2>&1; then
                print_status "Installing Homebrew..."
                /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
            fi
            brew install php@8.4 || brew install php
            ;;
        "linux")
            curl -sSL https://php.new/install/linux | bash
            ;;
        "windows")
            print_status "Please install PHP manually from https://windows.php.net/download/"
            print_status "Or use php.new: curl -sSL https://php.new/install/windows | bash"
            ;;
        *)
            print_error "Unsupported OS. Please install PHP 8.1+ manually."
            exit 1
            ;;
    esac
}

# Install Composer
install_composer() {
    if command -v composer >/dev/null 2>&1; then
        print_success "Composer already installed"
        return 0
    fi

    print_status "Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer 2>/dev/null || mv composer.phar composer

    if ! command -v composer >/dev/null 2>&1; then
        print_warning "Composer not in PATH. Using local composer.phar"
        alias composer='php composer.phar'
    fi
}

# Install Node.js
install_nodejs() {
    if command -v node >/dev/null 2>&1; then
        local node_version=$(node -v | cut -d'v' -f2 | cut -d'.' -f1)
        if [[ $node_version -ge 18 ]]; then
            print_success "Node.js $(node -v) already installed"
            return 0
        fi
    fi

    print_status "Installing Node.js..."
    local os=$(detect_os)

    case $os in
        "macos")
            if command -v brew >/dev/null 2>&1; then
                brew install node
            else
                print_error "Please install Node.js manually from https://nodejs.org/"
            fi
            ;;
        "linux")
            curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
            sudo apt-get install -y nodejs
            ;;
        "windows")
            print_error "Please install Node.js manually from https://nodejs.org/"
            ;;
    esac
}

# Install Docker
install_docker() {
    if command -v docker >/dev/null 2>&1; then
        print_success "Docker already installed"
        return 0
    fi

    print_status "Installing Docker..."
    local os=$(detect_os)

    case $os in
        "macos")
            print_status "Please install Docker Desktop from https://docker.com/products/docker-desktop/"
            ;;
        "linux")
            curl -fsSL https://get.docker.com -o get-docker.sh
            sh get-docker.sh
            sudo usermod -aG docker $USER
            rm get-docker.sh
            ;;
        "windows")
            print_status "Please install Docker Desktop from https://docker.com/products/docker-desktop/"
            ;;
    esac
}

# Setup Docker containers
setup_docker() {
    print_status "Setting up Docker containers..."

    # Generate random passwords
    DOCKER_MYSQL_PASSWORD=$(openssl rand -base64 32 2>/dev/null || date +%s | sha256sum | base64 | head -c 32)
    DOCKER_MYSQL_DATABASE="laravel"

    # Start MySQL container
    if ! docker ps | grep -q uptime-mysql; then
        print_status "Starting MySQL container..."
        docker run -d \
            --name uptime-mysql \
            -e MYSQL_ROOT_PASSWORD="$DOCKER_MYSQL_PASSWORD" \
            -e MYSQL_DATABASE="$DOCKER_MYSQL_DATABASE" \
            -p 3306:3306 \
            mysql:8.0 \
            --default-authentication-plugin=mysql_native_password

        # Wait for MySQL to be ready
        print_status "Waiting for MySQL to be ready..."
        sleep 30
    else
        print_success "MySQL container already running"
    fi

    # Start Redis container
    if ! docker ps | grep -q uptime-redis; then
        print_status "Starting Redis container..."
        docker run -d \
            --name uptime-redis \
            -p 6379:6379 \
            redis:7-alpine
    else
        print_success "Redis container already running"
    fi

    # Export variables for use in env setup
    export DOCKER_MYSQL_PASSWORD
    export DOCKER_MYSQL_DATABASE
}

# Setup environment
setup_env() {
    print_status "Setting up environment configuration..."

    # Copy .env file if it doesn't exist
    if [[ ! -f .env ]]; then
        if [[ -f .env.example ]]; then
            cp .env.example .env
            print_success "Copied .env.example to .env"
        else
            print_error ".env.example not found"
            exit 1
        fi
    else
        print_success ".env already exists"
    fi

    # Update .env file with Docker settings
    if [[ -n "$DOCKER_MYSQL_PASSWORD" && -n "$DOCKER_MYSQL_DATABASE" ]]; then
        print_status "Updating database configuration with Docker settings..."
        
        # Create a temporary file for safer editing
        cp .env .env.tmp
        
        # Update database settings
        cat > .env << 'EOF'
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=password123

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis

CACHE_STORE=redis

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
EOF
        
        # Replace the password with the actual Docker password
        sed -i '' "s/DB_PASSWORD=password123/DB_PASSWORD=$DOCKER_MYSQL_PASSWORD/" .env
        sed -i '' "s/DB_DATABASE=laravel/DB_DATABASE=$DOCKER_MYSQL_DATABASE/" .env
        
        rm -f .env.tmp
        print_success "Updated database configuration"
    else
        print_warning "Docker MySQL password not available, keeping existing .env configuration"
    fi
}

# Setup Laravel application
setup_laravel() {
    print_status "Setting up Laravel application..."

    # Install PHP dependencies
    print_status "Installing PHP dependencies..."
    composer install --no-dev --optimize-autoloader

    # Generate application key if not exists
    if ! grep -q "APP_KEY=base64:" .env; then
        print_status "Generating application key..."
        php artisan key:generate
    fi

    # Install Node.js dependencies
    print_status "Installing Node.js dependencies..."
    npm install

    # Generate Wayfinder routes
    print_status "Generating routes..."
    php artisan wayfinder:generate

    # Build frontend assets
    print_status "Building frontend assets..."
    npm run build

    # Run database migrations
    print_status "Running database migrations..."
    php artisan migrate --force

    # Seed the database
    print_status "Seeding database..."
    php artisan db:seed --force

    # Clear caches
    print_status "Clearing caches..."
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    php artisan route:clear
    print_success "Caches cleared"
}

# Start all services
start_services() {
    if load_status; then
        print_warning "Services already running. Use './setup.sh restart' to restart."
        check_services
        return 0
    fi

    print_success "🎉 Starting Laravel Uptime Monitor services..."
    echo

    # Function to run with clean environment
    run_clean() {
        env -i HOME="$HOME" PATH="$PATH" USER="$USER" "$@"
    }

    # Start Laravel development server
    print_status "Starting Laravel development server..."
    run_clean php artisan serve --port=8000 &
    SERVER_PID=$!

    # Wait a moment for server to start
    sleep 3

    # Check if port 8000 is available, if not try 8001
    if ! curl -s http://127.0.0.1:8000 >/dev/null 2>&1; then
        kill $SERVER_PID 2>/dev/null
        print_status "Port 8000 busy, starting on port 8001..."
        run_clean php artisan serve --port=8001 &
        SERVER_PID=$!
        APP_URL="http://127.0.0.1:8001"
    else
        APP_URL="http://127.0.0.1:8000"
    fi

    # Start queue worker for background jobs
    print_status "Starting queue worker for monitoring jobs..."
    run_clean php artisan queue:work --daemon --tries=3 &
    QUEUE_PID=$!

    # Run initial website monitoring
    print_status "Running initial website monitoring check..."
    sleep 2  # Give services time to start
    run_clean php artisan monitor:websites

    # Start periodic monitoring (every 15 minutes)
    print_status "Setting up periodic monitoring (every 15 minutes)..."
    (
        while true; do
            sleep 900  # 15 minutes
            echo "$(date): Running scheduled website monitoring..."
            run_clean php artisan monitor:websites
        done
    ) &
    MONITOR_PID=$!

    # Start Laravel scheduler (simulates cron job)
    print_status "Starting Laravel task scheduler..."
    (
        while true; do
            run_clean php artisan schedule:run
            sleep 60  # Run every minute like cron
        done
    ) &
    SCHEDULER_PID=$!

    print_success "🌐 Application URL: $APP_URL"
    print_success "📊 Website Monitoring: Active (checking every 15 minutes)"
    print_success "⚡ Queue Worker: Processing background jobs"
    print_success "⏰ Task Scheduler: Running Laravel scheduled tasks"
    echo
    print_status "📋 Service Process IDs:"
    echo "   Laravel Server: $SERVER_PID"
    echo "   Queue Worker: $QUEUE_PID"
    echo "   Monitor Loop: $MONITOR_PID"
    echo "   Task Scheduler: $SCHEDULER_PID"
    echo
    print_warning "Use './setup.sh stop' to stop all services"
    print_warning "Use './setup.sh status' to check service status"
    echo

    # Create a status file for easy monitoring
    cat > .services_status << EOF
# Laravel Uptime Monitor - Service Status
LARAVEL_SERVER_PID=$SERVER_PID
QUEUE_WORKER_PID=$QUEUE_PID
MONITOR_LOOP_PID=$MONITOR_PID
SCHEDULER_PID=$SCHEDULER_PID
APP_URL=$APP_URL
STARTED_AT="$(date)"
EOF

    print_success "All services started successfully!"
    echo
    print_status "🎯 Quick commands:"
    echo "   ./setup.sh status    - Check service status"
    echo "   ./setup.sh logs      - View application logs"
    echo "   ./setup.sh monitor   - Run website check now"
    echo "   ./setup.sh stop      - Stop all services"
}

# Complete installation
install_all() {
    echo "🚀 Starting Laravel Uptime Monitor Installation..."
    echo

    print_status "Installing system dependencies..."
    install_php
    install_composer
    install_nodejs
    install_docker

    print_status "Setting up infrastructure..."
    setup_docker
    setup_env
    setup_laravel

    print_success "✅ Installation completed successfully!"
    echo
    print_status "Starting services..."
    start_services
}

# Restart services
restart_services() {
    print_status "Restarting services..."
    stop_services
    sleep 2
    start_services
}

# Show help
show_help() {
    echo "Laravel Uptime Monitor - All-in-One Script"
    echo
    echo "Usage: $0 [COMMAND]"
    echo
    echo "Commands:"
    echo "  install   Complete installation and setup (default)"
    echo "  start     Start all services"
    echo "  stop      Stop all running services"
    echo "  restart   Stop and start services"
    echo "  status    Show current service status"
    echo "  logs      Show recent application logs"
    echo "  monitor   Run a one-time website monitoring check"
    echo "  help      Show this help message"
    echo
    echo "Examples:"
    echo "  $0            # Complete installation"
    echo "  $0 start      # Start services"
    echo "  $0 status     # Check if services are running"
    echo "  $0 stop       # Stop all services"
    echo
}

# Main logic
case "${1:-install}" in
    "install")
        install_all
        ;;
    "start")
        start_services
        ;;
    "stop")
        stop_services
        ;;
    "restart")
        restart_services
        ;;
    "status")
        check_services
        ;;
    "logs")
        show_logs
        ;;
    "monitor")
        run_monitor
        ;;
    "help"|*)
        show_help
        ;;
esac