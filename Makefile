.PHONY: init after-env clear

init:
	docker compose build --no-cache
	docker compose up -d
	docker compose exec php composer install
	docker compose exec php cp .env.example .env

after-env:
	docker compose exec php php artisan key:generate
	docker compose exec php php artisan migrate --seed

clear:
	docker compose exec php php artisan cache:clear
	docker compose exec php php artisan config:clear
	docker compose exec php php artisan route:clear
	docker compose exec php php artisan view:clear
	docker compose exec php composer dump-autoload
