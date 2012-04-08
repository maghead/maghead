#!/bin/bash
bash scripts/compile.sh
onion build
VERSION=$(cat package.ini | grep "^version" | perl -pe 's/version\s*=\s*//i;')
git commit -a -m "Build package.xml and phar file for $VERSION"
