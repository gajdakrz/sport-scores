PHP_CONTAINER=sport-scores-php

.PHONY: up down bash composer-install cache-clear fix db test-db test-db-ide test

up:
	docker compose up -d

down:
	docker compose down

bash:
	docker exec -it $(PHP_CONTAINER) bash

composer-install:
	docker exec $(PHP_CONTAINER) composer install

cache-clear:
	docker exec $(PHP_CONTAINER) php bin/console cache:clear

# Auto-fix code style, incl. removing unused `use` imports (phpcbf exits 1 when it fixed something),
# then report what can't be fixed automatically
fix:
	docker exec $(PHP_CONTAINER) vendor/bin/phpcbf || [ $$? -eq 1 ]
	docker exec $(PHP_CONTAINER) vendor/bin/phpcs

# Dev database: create (if missing) -> migrate -> load fixtures
db:
	docker exec $(PHP_CONTAINER) php bin/console doctrine:database:create --if-not-exists
	docker exec $(PHP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction
	docker exec $(PHP_CONTAINER) timeout 30 php bin/console doctrine:fixtures:load --no-interaction --purge-with-truncate

# For terminal use (with TTY)
test-db:
	docker exec -it $(PHP_CONTAINER) php bin/console doctrine:database:drop --force --if-exists --env=test
	docker exec -it $(PHP_CONTAINER) php bin/console doctrine:database:create --env=test
	docker exec -it $(PHP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction --env=test
	docker exec -it $(PHP_CONTAINER) timeout 30 php bin/console doctrine:fixtures:load --no-interaction --purge-with-truncate --env=test

# For IDE use (without TTY)
test-db-ide:
	docker exec $(PHP_CONTAINER) php bin/console doctrine:database:drop --force --if-exists --env=test
	docker exec $(PHP_CONTAINER) php bin/console doctrine:database:create --env=test
	docker exec $(PHP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction --env=test
	docker exec $(PHP_CONTAINER) timeout 30 php bin/console doctrine:fixtures:load --no-interaction --purge-with-truncate --env=test

test: test-db
	docker exec -it $(PHP_CONTAINER) sh -c "APP_ENV=test php bin/phpunit"