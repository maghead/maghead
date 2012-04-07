#!/bin/bash
phpunit tests && ( 
    bash scripts/compile.sh
    VERSION=$(cat package.ini | grep "^version" | perl -pe 's/version\s*=\s*//i;')
    onion build
    git commit -a -m "Build package.xml and phar file for $VERSION"
    git tag $VERSION -m "Release $VERSION"
    git push origin --tags
    git push origin HEAD
    sudo pear install -f package.xml
)
