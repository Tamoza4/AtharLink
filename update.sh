#!/usr/bin/env bash
# ========================================================
# AtharLink One-Command Auto Updater
# Usage: ./update.sh OR bash update.sh
# ========================================================

set -e

echo "=========================================="
echo "    Starting AtharLink Auto-Updater       "
echo "=========================================="

# 1. Pull latest code if using Git
if [ -d ".git" ]; then
    echo "[*] Pulling latest code changes from Git repository..."
    git pull origin main
else
    echo "[*] Non-git installation detected (skipping git pull)."
fi

# 2. Run PHP Database Migration & Safety Checks
if command -v php >/dev/null 2>&1; then
    php bin/update.php
else
    echo "[-] Error: PHP CLI executable not found in PATH."
    exit 1
fi
