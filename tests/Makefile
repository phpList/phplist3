

createdb:
	mysql -e 'drop database if exists phplistdb'
	mysqladmin create phplistdb
	mysql -e 'grant all on phplistdb.* to phplist@"%" identified by "phplist"; FLUSH PRIVILEGES;'

test: createdb
	../vendor/bin/behat -n -fprogress -p goutte --strict --stop-on-failure --tags=@initialise
	../vendor/bin/behat -n -fprogress -p goutte --strict --stop-on-failure --tags="~@initialise && ~@wip"

verbosetest: createdb
	../vendor/bin/behat -n -p chrome --strict --stop-on-failure --tags=@initialise
	../vendor/bin/behat -n -p chrome --strict --stop-on-failure --tags="~@initialise && ~@wip"
	
test-wip: createdb
	../vendor/bin/behat -n -p chrome --strict --stop-on-failure --tags=@initialise
	../vendor/bin/behat -n -p chrome --strict --stop-on-failure --tags="~@initialise && @wip"

test-all: createdb
	mysql -e 'drop database if exists phplistdb'
	mysqladmin create phplistdb
	mysql -e 'grant all on phplistdb.* to phplist@"%" identified by "phplist"'
	../vendor/bin/behat -n -p chrome --strict --stop-on-failure --tags=@initialise
	../vendor/bin/behat -n -p chrome --strict --stop-on-failure --tags="~@initialise"

