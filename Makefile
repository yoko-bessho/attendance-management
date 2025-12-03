.PHONY: init after-env clear

init:
	docker compose build --no-cache
	docker compose up -d
	docker compose exec php composer install
	docker compose exec php cp .env.example .env
	@echo "✔ .env を作成しました。ここで .env に以下の環境変数を追加してください。"
	@echo ""
	@echo "DB_CONNECTION=mysql"
	@echo "DB_HOST=mysql"
	@echo "DB_PORT=3306"
	@echo "DB_DATABASE=laravel_db"
	@echo "DB_USERNAME=laravel_user"
	@echo "DB_PASSWORD=laravel_pass"
	@echo ""
	@echo "# 管理者用ユーザ"
	@echo "ADMIN_EMAIL=				#メールアドレス"
	@echo "ADMIN_PASSWORD=		#パスワード"
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
