.PHONY: init after-env

init:
	docker compose up -d --build
	docker compose exec php composer install
	docker compose exec php cp .env.example .env
	@echo "✔ .env を作成しました。ここで .env に以下の環境変数を追加してください。"
	@echo ""
	@cat << 'EOF'
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

# 管理者用ユーザ
ADMIN_EMAIL=         #メールアドレス
ADMIN_PASSWORD=      #パスワード
EOF
	@echo ""
	@echo "✔ 編集後に `make after-env` を実行してください。"

after-env:
	docker compose exec php php artisan key:generate
	docker compose exec php php artisan migrate --seed

