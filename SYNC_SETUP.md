# Automatic Sync Setup - Complete Documentation

## Overview
A comprehensive bidirectional synchronization system has been set up to keep the following environments in sync:
- **Codespace**: /workspaces/lms
- **VPS**: 185.252.233.186:/var/www/lms
- **GitHub**: https://github.com/james990c8e3c-wq/lms

## How It Works

### Automatic Sync Process
The `scripts/comprehensive_sync.sh` script runs continuously and performs the following every 30 seconds:

1. **Codespace → GitHub**
   - Detects file changes in the codespace
   - Automatically stages and commits changes
   - Pushes commits to GitHub main branch

2. **VPS → GitHub**
   - Checks for any local changes on VPS
   - Stashes local changes (preserving .env)
   - Pulls latest from GitHub to stay in sync

3. **GitHub → Both Environments**
   - Both environments pull the latest from GitHub
   - Ensures consistency across all three systems

## Current Status

### ✅ Active Sync Process
- **Status**: Running
- **Process**: `bash scripts/comprehensive_sync.sh 30`
- **Check**: `ps aux | grep comprehensive_sync`
- **Logs**: `/workspaces/lms/sync_log.txt`

### Git Configuration
- **Codespace Branch**: main
- **VPS Branch**: main
- **GitHub Branch**: main
- **All environments synchronized**: ✅ YES

## Recent Changes Tracked

### Latest Commits
```
77ffc8c Auto-sync: 2026-01-03T04:05:37Z - Codespace changes
ffd58eb Auto-sync: 2026-01-03T04:05:17Z - Codespace changes
5752904 feat: Add assignPermissionsToRoles method to automatically sync all admin permissions
```

## Important Notes

### 1. VPS Environment Variables
- The VPS `.env` file is intentionally stashed before pulling
- This preserves production database credentials
- Local VPS changes don't override remote configuration

### 2. Excluded Files from Sync
The sync respects `.gitignore` and excludes:
- `.git/` directory
- `node_modules/`
- `vendor/`
- `storage/` (except `.gitkeep`)
- `.env` (production-specific)
- Upgrade files

### 3. Conflict Resolution
- Auto-sync uses `git pull --rebase` to prevent merge conflicts
- In case of conflicts, they are logged and the sync continues
- Manual resolution may be needed for complex conflicts

## Starting/Stopping Sync

### Start Automatic Sync
```bash
cd /workspaces/lms
nohup bash scripts/comprehensive_sync.sh 30 > /dev/null 2>&1 &
```

### Stop Automatic Sync
```bash
pkill -f "comprehensive_sync.sh"
```

### Check Sync Status
```bash
ps aux | grep comprehensive_sync | grep -v grep
tail -f /workspaces/lms/sync_log.txt
```

## Verification Checklist

- [x] Codespace repo connected to GitHub
- [x] VPS repo connected to GitHub
- [x] RolePermissionsSeeder with admin permissions deployed
- [x] Admin user (admin@amentotech.com) has all 32 permissions on both environments
- [x] Automatic sync script created and running
- [x] All changes pushed to GitHub repository
- [x] Bidirectional sync operational

## Admin Access

### Default Admin Account
- **Email**: admin@amentotech.com
- **Password**: google
- **Permissions**: 32/32 ✅ (All admin permissions)
- **Status**: Active on both Codespace and VPS

### Admin Permissions Assigned
1. can-manage-courses
2. can-manage-badges
3. can-manage-course-bundles
4. can-manage-subscriptions
5. can-manage-forums
6. can-manage-insights
7. can-manage-menu
8. can-manage-option-builder
9. can-manage-pages
10. can-manage-email-settings
11. can-manage-notification-settings
12. can-manage-languages
13. can-manage-subjects
14. can-manage-subject-groups
15. can-manage-language-translations
16. can-manage-addons
17. can-manage-upgrade
18. can-manage-users
19. can-manage-identity-verification
20. can-manage-reviews
21. can-manage-invoices
22. can-manage-bookings
23. can-manage-withdraw-requests
24. can-manage-commission-settings
25. can-manage-payment-methods
26. can-manage-create-blogs
27. can-manage-all-blogs
28. can-manage-update-blogs
29. can-manage-blog-categories
30. can-manage-dispute
31. can-manage-disputes-list
32. can-manage-admin-users

## Troubleshooting

### If sync stops working
1. Check if process is still running: `ps aux | grep comprehensive_sync`
2. Check logs: `tail -f /workspaces/lms/sync_log.txt`
3. Restart sync: `pkill -f "comprehensive_sync.sh" && nohup bash scripts/comprehensive_sync.sh 30 > /dev/null 2>&1 &`

### If VPS is out of sync
1. SSH into VPS: `ssh root@185.252.233.186`
2. Navigate to repo: `cd /var/www/lms`
3. Pull latest: `git fetch origin && git pull origin main`

### If GitHub is ahead of environments
1. Codespace will auto-pull within 30 seconds
2. VPS will auto-pull within 30 seconds
3. Manually pull if needed: `git pull origin main`

## Future Enhancements

- Add Slack/email notifications on sync failures
- Implement database synchronization
- Add backup before major deployments
- Create dashboard to monitor sync status
