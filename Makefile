.PHONY: up down logs

up:
	docker-compose up -d --build

down:
	docker-compose down

logs:
	docker-compose logs -f

clean:
	docker-compose down --rmi all --volumes --remove-orphans

setup: ## Configura el proyecto por primera vez (instala dependencias y autoriza Gmail)
	@echo "--- Paso 1: Instalando dependencias de Composer... ---"
	docker-compose run --rm php composer install
	@echo "\n--- Paso 2: Levantando los contenedores... ---"
	docker-compose up -d --build
	@echo "\n--- Paso 3: Ejecutando el script de configuración de Gmail... ---"
	@echo ">>> A continuación, se te pedirá que copies una URL en tu navegador y pegues un código de vuelta. <<<"
	@sleep 3
	docker-compose exec php php api/auth/gmail_api/setup_gmail.php
	chmod 666 srcs/public/config/google_token.json