.PHONY: help install start stop backend-install frontend-install backend-start frontend-start

help: ## Mostrar ayuda
	@echo "Comandos disponibles:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: backend-install frontend-install ## Instalar todas las dependencias

backend-install: ## Instalar dependencias del backend (Laravel)
	@echo "📦 Instalando dependencias del backend..."
	cd backend && composer install
	cd backend && cp .env.example .env || true
	cd backend && php artisan key:generate
	cd backend && php artisan migrate
	@echo "✅ Backend instalado"

frontend-install: ## Instalar dependencias del frontend (Vue)
	@echo "📦 Instalando dependencias del frontend..."
	cd frontend-web && npm install
	@echo "✅ Frontend instalado"

backend-start: ## Iniciar servidor backend
	@echo "🚀 Iniciando backend en http://localhost:8000"
	cd backend && php artisan serve

frontend-start: ## Iniciar servidor frontend
	@echo "🚀 Iniciando frontend en http://localhost:3000"
	cd frontend-web && npm run dev

start: ## Iniciar todos los servicios con Docker
	@echo "🚀 Iniciando todos los servicios..."
	docker-compose up -d
	@echo "✅ Servicios iniciados"
	@echo "   Backend:  http://localhost:8000"
	@echo "   Frontend: http://localhost:3000"
	@echo "   Admin:    http://localhost:8000/admin"

stop: ## Detener todos los servicios
	@echo "🛑 Deteniendo servicios..."
	docker-compose down
	@echo "✅ Servicios detenidos"

backend-logs: ## Ver logs del backend
	docker-compose logs -f backend

frontend-logs: ## Ver logs del frontend
	docker-compose logs -f frontend-web

clean: ## Limpiar archivos temporales
	@echo "🧹 Limpiando archivos temporales..."
	cd backend && rm -rf vendor/ storage/logs/*.log bootstrap/cache/*.php
	cd frontend-web && rm -rf node_modules/ dist/
	@echo "✅ Limpieza completada"

backend-migrate: ## Ejecutar migraciones del backend
	cd backend && php artisan migrate

backend-seed: ## Ejecutar seeders del backend
	cd backend && php artisan db:seed

backend-fresh: ## Resetear base de datos y ejecutar migraciones
	cd backend && php artisan migrate:fresh --seed

test-backend: ## Ejecutar tests del backend
	cd backend && php artisan test

test-frontend: ## Ejecutar tests del frontend
	cd frontend-web && npm run test
