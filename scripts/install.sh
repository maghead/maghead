#!/bin/bash
onion build
for p in $(which -a pear) ; do
    $p channel-discover pear.corneltek.com
    echo Installing for $p ...
    $p -v install -f package.xml
done
