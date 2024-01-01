
.PHONY: test stop testhub

test: stop
	docker-compose up --build -d --remove-orphans
	docker logs -f behat

testhub: stop
	docker-compose -f run-tests.yml up --build -d

stop:
	docker-compose down --remove-orphans || true
	docker-compose -f run-tests.yml down --remove-orphans || true
	docker stop chrome mailpit jaegar dbhost selenium-hub phplist pythontest || true
	docker rm chrome mailpit jaegar dbhost selenium-hub phplist pythontest || true
	docker network rm tests_phplistbehatnet || true


