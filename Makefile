.SILENT:

test:
	vendor/bin/codecept run

test-unit:
	vendor/bin/codecept run unit

fix-cs:
	docker exec jirhub_php php vendor/friendsofphp/php-cs-fixer/php-cs-fixer fix

clear-cache:
	docker exec jirhub_php php bin/console cache:clear

sh:
	docker exec -it jirhub_php sh