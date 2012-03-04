#!/bin/bash
# onion bundle
phpunit tests && ( 
    php scripts/lazy.php build-schema
    php scripts/lazy.php build-sql
) && (
    bash scripts/compile.sh
    onion build
    sudo pear -v install -f package.xml
)
