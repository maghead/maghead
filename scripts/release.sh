#!/bin/bash
phpunit tests && ( 
    bash scripts/compile.sh
    VERSION=$(cat package.ini | grep "^version" | perl -pe 's/version\s*=\s*//i;')
    onion build
    git commit -a -m "Build package.xml and phar file for $VERSION"
    git tag $VERSION -m "Release $VERSION"
    git push origin --tags
    git push origin HEAD

    for p in $(which -a pear) ; do
        sudo $p channel-discover pear.corneltek.com
        sudo $p install -f package.xml
    done
)
