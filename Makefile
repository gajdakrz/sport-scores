PHP_CONTAINER=sport-scores-php

# Drop and recreate the test DB inside the Docker container
test-db:
	docker exec -it $(PHP_CONTAINER) php bin/console doctrine:database:drop --force --if-exists --env=test
	docker exec -it $(PHP_CONTAINER) php bin/console doctrine:database:create --env=test
	docker exec -it $(PHP_CONTAINER) php bin/console doctrine:migrations:migrate --no-interaction --env=test
	docker exec -it $(PHP_CONTAINER) php bin/console doctrine:fixtures:load --no-interaction --env=test

# Full test workflow: recreate DB and run PHPUnit
test: test-db
	docker exec -it $(PHP_CONTAINER) sh -c "APP_ENV=test php bin/phpunit"
