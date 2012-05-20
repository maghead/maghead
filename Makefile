

init:
	curl -O https://raw.github.com/c9s/Onion/master/onion
	chmod +x onion
	php onion -q bundle

all: 

doc: force
	phpwiki.phar doc doc/html
	git add -v doc
	git commit -a -m "Update doc build"

phpci:
	phpci print -R --reference=PHP5 src

phpunit:
	phpunit --coverage-html build tests

force: ;
