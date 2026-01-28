#!/bin/bash

# Script to sync your fork's main branch with upstream Invoice Ninja
# This pulls latest from the official repo and merges into your fork

set -e

echo "=========================================="
echo "Syncing with Upstream Invoice Ninja"
echo "=========================================="
echo ""

# Check current branch
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
echo "Current branch: $CURRENT_BRANCH"
echo ""

# Make sure we're on main
if [ "$CURRENT_BRANCH" != "main" ]; then
    echo "⚠️  Not on main branch. Switching to main..."
    git checkout main
fi

# Fetch latest from upstream
echo "📦 Fetching latest from upstream..."
git fetch upstream
echo "✅ Fetched from upstream"
echo ""

# Show what branches are available
echo "Available upstream branches:"
git branch -r | grep upstream | grep -E "(v5-stable|v5-develop|main|master)" | head -5
echo ""

# Ask which branch to sync from
read -p "Which upstream branch to sync from? (default: v5-stable): " UPSTREAM_BRANCH
UPSTREAM_BRANCH=${UPSTREAM_BRANCH:-v5-stable}

echo ""
echo "📦 Merging upstream/$UPSTREAM_BRANCH into main..."

# Merge upstream branch
git merge upstream/$UPSTREAM_BRANCH || {
    echo ""
    echo "⚠️  Merge conflicts detected!"
    echo "Resolve conflicts manually, then:"
    echo "  git add ."
    echo "  git commit -m 'Merge upstream $UPSTREAM_BRANCH'"
    echo "  git push origin main"
    exit 1
}

echo "✅ Merged successfully"
echo ""

# Push to your fork
echo "📤 Pushing to your fork..."
git push origin main

echo ""
echo "=========================================="
echo "✅ Sync Complete!"
echo "=========================================="
echo ""
echo "Your fork's main branch is now up-to-date with upstream/$UPSTREAM_BRANCH"
echo "This will trigger CI/CD and deploy to your VPS automatically! 🚀"
