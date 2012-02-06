#!/bin/bash
onion -d compile \
    --lib src \
    --lib vendor/pear \
    --classloader \
    --bootstrap scripts/lazy.php \
    --executable \
    --output lazy.phar
mv lazy.phar lazy
chmod +x lazy
