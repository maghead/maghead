#!/bin/bash
# onion bundle
phpunit tests && ( 
    ./lazy build-schema
    ./lazy build-sql
) && (
    bash scripts/compile.sh
    onion build
    sudo pear install -f package.xml
)
