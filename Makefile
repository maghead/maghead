
phpci:
	phpci print -R --reference=PHP5 src

test:
	phpunit --coverage-html build tests
