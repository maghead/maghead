#!/bin/bash
phpunit tests && ( 
    source scripts/build.sh
    git tag $VERSION -m "Release $VERSION"
    git push origin --tags
    git push origin HEAD
    source scripts/install.sh
)
