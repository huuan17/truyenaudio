#!/bin/bash

# Try to fetch and pull from remote to get all content
echo "=== Fetching from remote ==="
git fetch origin

echo "=== Pulling latest changes ==="
git pull origin main

echo "=== Checking all remote branches ==="
git branch -r

echo "=== Trying to checkout other branches if they exist ==="
for branch in $(git branch -r | grep -v HEAD | sed 's/origin\///'); do
    echo "Checking branch: $branch"
    git checkout $branch 2>/dev/null || echo "Could not checkout $branch"
    ls -la
    echo "---"
done

echo "=== Back to main branch ==="
git checkout main

echo "=== Final status ==="
ls -la
find . -name "*.php" -o -name "*.js" -o -name "composer.json" -o -name "package.json" 2>/dev/null