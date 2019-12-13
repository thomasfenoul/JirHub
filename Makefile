.SILENT:

test:
	vendor/bin/codecept run

test-unit:
	vendor/bin/codecept run unit

fix-cs:
	docker exec -it jirhub_php php vendor/friendsofphp/php-cs-fixer/php-cs-fixer fix