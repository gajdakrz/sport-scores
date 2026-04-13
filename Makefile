PHP_CONTAINER=sport-scores-php

# For terminal use (with TTY)
test-db:
	docker exec -it $(PHP_CONTAINER) php bin/console doctrine:database:drop --force --if-exists --env=test
	docker exec -it $(PHP_CONTAINER) php bin/console doctrine:database:create --env=test
	docker exec -it $(PHP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction --env=test
	docker exec -it $(PHP_CONTAINER) php bin/console doctrine:fixtures:load --no-interaction --purge-with-truncate --env=test

# For IDE use (without TTY)
test-db-ide:
	docker exec $(PHP_CONTAINER) php bin/console doctrine:database:drop --force --if-exists --env=test
	docker exec $(PHP_CONTAINER) php bin/console doctrine:database:create --env=test
	docker exec $(PHP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction --env=test
	docker exec $(PHP_CONTAINER) php bin/console doctrine:fixtures:load --no-interaction --purge-with-truncate --env=test

test: test-db
	docker exec -it $(PHP_CONTAINER) sh -c "APP_ENV=test php bin/phpunit"