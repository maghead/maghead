#!/bin/bash
# onion bundle
bash scripts/compile.sh
onion build
sudo pear install -f package.xml
