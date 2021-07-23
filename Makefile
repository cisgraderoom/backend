build:
	@echo "[Makefile] local build"
	docker-compose up

up: build
	@echo "[Makefile] start service"
	docker-compose up -d

logs:
	docker-compose logs -f

sh:
	@echo "[Makefile] shell into service"
	docker exec -it $(app) /bin/sh

down:
	@echo "[Makefile] doown service"
	docker-compose down

test:
	@echo "[Makefile] test service"
	go test -v -cover ./..

clean: down
	@echo "[Makefile] cleaning up"
	# rm -f ecstock-api
	docker system prune -f
	docker volume prune -f
