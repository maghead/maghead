#!/bin/bash
onion build
for p in $(which -a pear) ; do
    sudo $p channel-discover pear.corneltek.com
    sudo $p install -f package.xml
done
