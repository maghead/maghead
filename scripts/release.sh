#!/bin/bash
phpunit tests && ( 
    source scripts/build.sh
    echo "Tagging..."
    git tag $VERSION -f -m "Release $VERSION"
    git push origin --tags
    git push origin -q HEAD
)
