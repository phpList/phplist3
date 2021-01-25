

test:
	mysql -e 'drop database if exists phplistdb'
	mysqladmin create phplistdb
	mysql -e 'grant all on phplistdb.* to phplist@"%" identified by "phplist"'
	../vendor/bin/behat -n -fprogress -p chrome --strict --stop-on-failure --tags=@initialise
	../vendor/bin/behat -n -fprogress -p chrome --strict --stop-on-failure --tags="~@initialise && ~@wip"
	
test-wip: 
	mysql -e 'drop database if exists phplistdb'
	mysqladmin create phplistdb
	mysql -e 'grant all on phplistdb.* to phplist@"%" identified by "phplist"'
	../vendor/bin/behat -n -fprogress -p chrome --strict --stop-on-failure --tags=@initialise
	../vendor/bin/behat -n -fprogress -p chrome --strict --stop-on-failure --tags="~@initialise"

test-all:
	mysql -e 'drop database if exists phplistdb'
	mysqladmin create phplistdb
	mysql -e 'grant all on phplistdb.* to phplist@"%" identified by "phplist"'
