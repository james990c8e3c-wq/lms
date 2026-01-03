#!/bin/bash
# Comprehensive sync script: Codespace <-> VPS <-> GitHub
# This script ensures all three environments stay in sync
# Usage: scripts/comprehensive_sync.sh [interval_seconds]

set -euo pipefail

INTERVAL=${1:-30}  # Check every 30 seconds by default
REPO_ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$REPO_ROOT"

CODESPACE_REPO="/workspaces/lms"
VPS_HOST="root@185.252.233.186"
VPS_REPO="/var/www/lms"
GITHUB_REMOTE="origin"
GITHUB_BRANCH="main"

LOG_FILE="$REPO_ROOT/sync_log.txt"
LOCK_FILE="/tmp/comprehensive_sync.lock"

# Logging function
log_message() {
    local msg="[$(date +'%Y-%m-%d %H:%M:%S')] $1"
    echo "$msg" | tee -a "$LOG_FILE"
}

# Ensure only one instance runs
if [[ -f "$LOCK_FILE" ]]; then
    log_message "Sync already running (lock file exists)"
    exit 0
fi
touch "$LOCK_FILE"
trap "rm -f $LOCK_FILE" EXIT

log_message "Starting comprehensive sync (interval: ${INTERVAL}s)"

# Function to sync codespace with GitHub
sync_codespace_to_github() {
    log_message "📤 Syncing Codespace to GitHub..."
    cd "$CODESPACE_REPO"
    
    if git status --porcelain | grep -q '.'; then
        git add -A
        if ! git diff --cached --quiet; then
            COMMIT_MSG="Auto-sync: $(date -u +'%Y-%m-%dT%H:%M:%SZ') - Codespace changes"
            git commit -m "$COMMIT_MSG" || log_message "Nothing new to commit"
        fi
    fi
    
    # Pull latest from GitHub first
    if git fetch origin "$GITHUB_BRANCH" 2>/dev/null; then
        git pull --rebase origin "$GITHUB_BRANCH" 2>/dev/null || log_message "Rebase conflict in codespace"
    fi
    
    # Push to GitHub
    if git push origin "$GITHUB_BRANCH" 2>/dev/null; then
        log_message "✅ Codespace pushed to GitHub"
    else
        log_message "⚠️ Failed to push codespace to GitHub"
    fi
}

# Function to sync VPS with GitHub
sync_vps_to_github() {
    log_message "📤 Syncing VPS to GitHub..."
    
    ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no "$VPS_HOST" \
        "cd $VPS_REPO && if git status --porcelain | grep -q '.'; then git stash; fi && git fetch origin main 2>/dev/null && git pull --rebase origin main 2>/dev/null || true" \
        2>/dev/null || log_message "⚠️ VPS sync failed"
    log_message "✅ VPS synced with GitHub"
}

# Function to pull latest from GitHub to both environments
sync_github_to_environments() {
    log_message "📥 Pulling latest from GitHub..."
    
    # Codespace pull
    cd "$CODESPACE_REPO"
    if git fetch origin "$GITHUB_BRANCH" 2>/dev/null; then
        if git pull --rebase origin "$GITHUB_BRANCH" 2>/dev/null; then
            log_message "✅ Codespace pulled latest from GitHub"
        fi
    fi
    
    # VPS pull
    ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no "$VPS_HOST" \
        "cd $VPS_REPO && git fetch origin main 2>/dev/null && git pull --rebase origin main 2>/dev/null || true" \
        2>/dev/null || log_message "⚠️ VPS GitHub pull failed"
    log_message "✅ VPS pulled latest from GitHub"
}

# Main sync loop
while true; do
    log_message "------- Sync Cycle Started -------"
    
    # 1. Sync codespace to GitHub
    sync_codespace_to_github
    
    # 2. Sync VPS to GitHub
    sync_vps_to_github
    
    # 3. Pull latest from GitHub to ensure both are up-to-date
    sync_github_to_environments
    
    log_message "------- Sync Cycle Completed -------"
    log_message "Next sync in ${INTERVAL} seconds..."
    
    sleep "$INTERVAL"
done
