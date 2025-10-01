#!/bin/bash

# News Aggregator Laravel Project Setup Script
# This script sets up the complete News Aggregator project with Docker
# Includes database setup, queue configuration, and news provider initialization

set -e  # Exit on any error

echo "🚀 Starting News Aggregator Project Setup..."
echo "============================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
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

# Check system dependencies
print_status "Checking system dependencies..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is available
if ! command -v docker &> /dev/null || ! docker compose version &> /dev/null; then
    print_error "Docker Compose is not available. Please install Docker Compose."
    exit 1
fi

# Check if Docker is running
print_status "Checking if Docker is running..."
if ! docker info > /dev/null 2>&1; then
    print_error "Docker is not running. Please start Docker and try again."
    exit 1
fi
print_success "Docker and Docker Compose are ready"

# Check and copy .env file
print_status "Checking .env file..."
if [ ! -f ".env" ]; then
    print_warning "No .env file found"
    if [ -f ".env.example" ]; then
        cp .env.example .env
        print_warning "Copied .env.example to .env"
        print_warning "⚠️  IMPORTANT: Please edit the .env file with your specific configuration before proceeding!"
        print_warning "   - Set proper Docker PostgreSQL Settings"
        print_warning "   - Update database credentials"
        print_warning "   - Configure other environment-specific variables"
        exit 1
    else
        print_error "Neither .env nor .env.example file found. Please create a .env file first."
        exit 1
    fi
else
    print_success ".env file exists"
fi

# Stop any existing containers
print_status "Stopping existing Docker containers..."
docker compose down --remove-orphans
print_success "Docker containers stopped"

# Start Docker containers
print_status "Starting Docker containers..."
docker compose up -d
print_success "Docker containers started"

# Wait a moment for containers to be ready
print_status "Waiting for containers to be ready..."
sleep 5

# Check if Docker containers are running
print_status "Checking Docker container status..."
if ! docker compose ps | grep -q "Up"; then
    print_error "Docker containers failed to start properly"
    docker compose logs
    exit 1
fi
print_success "Docker containers are running"

# Install Composer dependencies
print_status "Installing Composer dependencies..."
if ./run.sh composer install --no-interaction --prefer-dist --optimize-autoloader; then
    print_success "Composer dependencies installed"
else
    print_error "Failed to install Composer dependencies"
    exit 1
fi

# Check if APP_KEY exists and generate if needed
print_status "Checking APP_KEY..."
if ! grep -q "^APP_KEY=base64:" .env || [ -z "$(grep '^APP_KEY=' .env | cut -d'=' -f2)" ]; then
    print_status "Generating APP_KEY..."
    if ./run.sh php artisan key:generate --force; then
        print_success "APP_KEY generated"
    else
        print_error "Failed to generate APP_KEY"
        exit 1
    fi
else
    print_success "APP_KEY already exists"
fi

# Generate API documentation
print_status "Generating API documentation..."
if ./run.sh php artisan l5-swagger:generate; then
    print_success "API documentation generated"
else
    print_warning "API documentation generation failed - continuing anyway"
fi

# Clear caches
print_status "Clearing application caches..."
./run.sh php artisan config:clear
./run.sh php artisan cache:clear
./run.sh php artisan route:clear
./run.sh php artisan view:clear
print_success "Application caches cleared"

# Run database migrations
print_status "Running database migrations..."
if ./run.sh php artisan migrate --force; then
    print_success "Database migrations completed"
else
    print_error "Failed to run database migrations"
    exit 1
fi

# Seed the database with initial data
print_status "Seeding database with initial data..."
if ./run.sh php artisan db:seed --force; then
    print_success "Database seeding completed"
else
    print_warning "Database seeding failed - continuing anyway"
fi

# Setup queue worker for background processing
print_status "Setting up queue system..."
if ./run.sh php artisan queue:clear; then
    print_success "Queue cleared"
else
    print_warning "Queue clear failed - continuing anyway"
fi

echo ""
echo "🎉 News Aggregator Setup Completed Successfully!"
echo "==============================================="
print_success "Your News Aggregator application is ready!"
echo ""
print_status "📱 Application URL: http://localhost:8080"
print_status "📚 API Documentation: http://localhost:8080/api/documentation"
echo ""
print_status "📋 Available Commands:"
print_status "  • View logs: docker compose logs -f"
print_status "  • Stop containers: docker compose down"
print_status "  • Restart containers: docker compose restart"
print_status "  • Run queue worker: ./run.sh php artisan queue:work"
print_status "  • Fetch news manually: ./run.sh php artisan app:fetch-news"
print_status "  • Run tests: ./run.sh php artisan test"
echo ""
print_warning "⚠️  Remember to configure your news API keys in .env:"
print_warning "   - NEWSAPI_KEY for NewsAPI.org"
print_warning "   - GUARDIAN_API_KEY for Guardian API"
print_warning "   - NYT_API_KEY for New York Times API"
echo ""