#!/bin/bash
# onion bundle
phpunit tests && (
    bash scripts/compile.sh
    onion build
    sudo pear install -f package.xml
)
