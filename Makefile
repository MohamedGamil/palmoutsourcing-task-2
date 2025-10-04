SHELL := /bin/bash
DC := docker compose

# Point Sail at the root compose file & project so it "execs" into the
# laravel service managed by the root docker-compose.yml.
SAIL_DIR := apps/api
SAIL_CMD := cd $(SAIL_DIR) && SAIL_FILES=../../docker-compose.yml SAIL_PROJECT=palmoutsourcing-task ./vendor/bin/sail

.PHONY: up up-build down ps logs install migrate fresh seed tinker swagger composer composer-dump artisan scrape scrape-products queue-work api-make api-sh web-install web-sh reset-db clean prune


# --- Lifecycle ---------------------------------------------------------------

up:
	$(SAIL_CMD) up -d

up-build:
	$(SAIL_CMD) up -d --build

down:
	$(SAIL_CMD) down

ps:
	$(DC) ps

logs:
	$(DC) logs -f --tail=100

prune:
	$(DC) down -v --remove-orphans

clean: down
	$(SAIL_CMD) down -v --rmi all
	rm -f ./apps/api/public/storage || true
	rm -rf ./apps/api/vendor || true
	rm -rf ./apps/api/node_modules || true
	rm -rf ./apps/web/node_modules || true
	rm -rf ./data/sail-mysql/* || true


# --- One-shot bootstrap ------------------------------------------------------

## One command to set up everything for local dev:
## - builds & starts containers
## - installs PHP deps (Laravel) via Sail
## - generates app key & storage link
## - runs migrations (+ optional seed)
## - generates Swagger docs
## - installs Node deps for Next.js
install:
	mkdir -p ./data/sail-mysql
	docker run --rm -v $(PWD)/apps/api:/var/www/html -w /var/www/html composer install --no-interaction --prefer-dist
	$(SAIL_CMD) up -d --build
	# Ensure API dependencies are installed (Composer via Sail)
	$(SAIL_CMD) composer install --no-interaction --prefer-dist
	# App key + storage symlink
	$(SAIL_CMD) artisan key:generate --force
	$(SAIL_CMD) artisan storage:link || true
	# Database migrations (uncomment seed if needed)
	$(SAIL_CMD) artisan migrate --force
	# $(SAIL_CMD) artisan db:seed --force
	# Swagger docs
	$(SAIL_CMD) artisan l5-swagger:generate
	# Frontend dependencies
	$(DC) run --rm web sh -c "npm ci || npm install"


# --- DB helpers --------------------------------------------------------------

migrate:
	$(SAIL_CMD) artisan migrate --seed --force

fresh:
	$(SAIL_CMD) artisan migrate:fresh --seed --force

seed:
	$(SAIL_CMD) artisan db:seed --force

reset-db: ## Danger: drop + recreate
	$(SAIL_CMD) artisan migrate:fresh --force


# --- API helpers -------------------------------------------------------------

swagger:
	$(SAIL_CMD) artisan l5-swagger:generate

tinker:
	$(SAIL_CMD) artisan tinker

composer:
	$(SAIL_CMD) composer $(c)

composer-dump:
	$(SAIL_CMD) composer dump-autoload

artisan:
	$(SAIL_CMD) artisan $(t)

scrape:
	$(SAIL_CMD) artisan scrape $(filter-out $@,$(MAKECMDGOALS))

scrape-products:
	$(SAIL_CMD) artisan scrape:products

queue-work:
	$(SAIL_CMD) artisan queue:work

api-make:
	$(SAIL_CMD) artisan make:$(m)

api-sh:
	$(SAIL_CMD) bash


# --- Web helpers -------------------------------------------------------------

web-install:
	$(DC) run --rm web sh -c "npm ci || npm install"

web-sh:
	$(DC) exec web sh

web-dev:
	cd apps/web && npm run dev && cd ../../

web-build:
	cd apps/web && npm run build && cd ../../

%:
	@: