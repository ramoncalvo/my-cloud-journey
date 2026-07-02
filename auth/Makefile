# Wrapper fino sobre docker compose: las 3 implementaciones (python,
# nestjs, go) corren siempre en contenedor, para comparar los
# frameworks en igualdad de condiciones.
#
# Por defecto opera sobre todos los stacks. Para uno solo, pasa STACK:
#   make up STACK=python
#   make down STACK=nestjs
#   make logs STACK=go

STACK ?=

.PHONY: build up down restart status logs ps

build:
	@[ -f .env ] || cp .env.example .env
	docker compose build $(STACK)

up: build
	docker compose up -d $(STACK)
ifeq ($(STACK),)
	@echo "python:      http://localhost:8000"
	@echo "nestjs:      http://localhost:8003"
	@echo "go:          http://localhost:8005"
else
	docker compose ps $(STACK)
endif

down:
ifeq ($(STACK),)
	docker compose down
else
	docker compose stop $(STACK)
	docker compose rm -f $(STACK)
endif

restart: down up

status ps:
	docker compose ps $(STACK)

logs:
	docker compose logs -f $(STACK)
