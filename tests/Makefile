
.PHONY: test stop testhub

test: stop
	rm -f composer.lock
	docker-compose up --build -d --remove-orphans
	docker logs -f behat

testhub: stop
	docker-compose -f run-tests.yml up --build -d

stop:
	docker-compose down --remove-orphans || true
	docker-compose -f run-tests.yml down --remove-orphans || true
	docker stop chrome mailpit dbhost phplist || true
	docker rm chrome mailpit dbhost phplist || true
	docker network rm tests_phplistbehatnet || true


