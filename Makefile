

all: phpci phpunit

phpci:
	phpci print -R --reference=PHP5 src

phpunit:
	phpunit --coverage-html build tests
