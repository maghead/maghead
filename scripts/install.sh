#!/bin/bash
# onion bundle
php scripts/lazy.php build-schema
php scripts/lazy.php build-sql
phpunit tests && (
    bash scripts/compile.sh
    onion build
    pear -v install -f package.xml
)
