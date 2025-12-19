.PHONY: help build up down restart logs shell migrate seed fresh test

# Default target
help:
	@echo "Clinic Booking System - Docker Commands"
	@echo "========================================"
	@echo ""
	@echo "Quick Start:"
	@echo "  make setup     - First time setup (build + start + seed)"
	@echo "  make up        - Start all containers"
	@echo "  make down      - Stop all containers"
	@echo ""
	@echo "Development:"
	@echo "  make build     - Build Docker images"
	@echo "  make restart   - Restart all containers"
	@echo "  make logs      - View container logs"
	@echo "  make shell     - Open shell in app container"
	@echo ""
	@echo "Database:"
	@echo "  make migrate   - Run database migrations"
	@echo "  make seed      - Run database seeders"
	@echo "  make fresh     - Fresh migrate + seed"
	@echo ""
	@echo "Testing:"
	@echo "  make test      - Run test suite"
	@echo ""
	@echo "Utilities:"
	@echo "  make clean     - Remove all containers and volumes"
	@echo "  make status    - Show container status"

# First time setup
setup: build up
	@echo "Waiting for services to be ready..."
	@sleep 10
	@echo "Setup complete! Access the app at http://localhost:$${APP_PORT:-8000}"

# Build Docker images
build:
	docker-compose build

# Start containers
up:
	docker-compose up -d

# Start with logs
up-logs:
	docker-compose up

# Stop containers
down:
	docker-compose down

# Restart containers
restart:
	docker-compose restart

# View logs
logs:
	docker-compose logs -f

# Open shell in app container
shell:
	docker-compose exec app sh

# Run migrations
migrate:
	docker-compose exec app php artisan migrate

# Run seeders
seed:
	docker-compose exec app php artisan db:seed

# Fresh database
fresh:
	docker-compose exec app php artisan migrate:fresh --seed

# Run tests
test:
	docker-compose exec app php artisan test

# Clean everything
clean:
	docker-compose down -v --rmi all

# Show status
status:
	docker-compose ps

# Lite version (SQLite)
lite-up:
	docker-compose -f docker-compose.lite.yml up -d

lite-down:
	docker-compose -f docker-compose.lite.yml down

lite-build:
	docker-compose -f docker-compose.lite.yml build

# With phpMyAdmin
with-tools:
	docker-compose --profile tools up -d
