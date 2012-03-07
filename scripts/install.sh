#!/bin/bash
# onion bundle
php scripts/lazy.php build-schema
php scripts/lazy.php build-sql
phpunit tests && (
    bash scripts/compile.sh
    onion build
    sudo pear -v install -f package.xml
)
