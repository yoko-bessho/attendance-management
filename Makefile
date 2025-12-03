.PHONY: init after-env clear

init:
	docker compose build --no-cache
	docker compose up -d
	docker compose exec php composer install
	docker compose exec php cp .env.example .env
	@echo "✔ .env を作成しました。ここで .env に以下の環境変数を追加してください。"
	@echo ""
	@cat "DB_CONNECTION=mysql"
	@cat "DB_HOST=mysql"
	@cat "DB_PORT=3306"
	@cat "DB_DATABASE=laravel_db"
	@cat "DB_USERNAME=laravel_user"
	@cat "DB_PASSWORD=laravel_pass"
	@echo ""
	@cat "# 管理者用ユーザ"
	@cat "ADMIN_EMAIL=				#メールアドレス"
	@cat "ADMIN_PASSWORD=		#パスワード"
	@echo ""
	@echo "✔ 編集後に `make after-env` を実行してください。"

after-env:
	docker compose exec php php artisan key:generate
	docker compose exec php php artisan migrate --seed

clear:
	docker compose exec php php artisan cache:clear
	docker compose exec php php artisan config:clear
	docker compose exec php php artisan route:clear
	docker compose exec php php artisan view:clear
	docker compose exec php composer dump-autoload
