#!/bin/bash
onion -q compile \
    --lib src \
    --lib vendor/pear \
    --classloader \
    --bootstrap scripts/lazy.emb.php \
    --executable \
    --output lazy.phar
mv lazy.phar lazy
chmod +x lazy
