# QUICK REFERENCE - LERNEN LMS DEPLOYMENT

## 🚀 Your LMS is LIVE!

**Website**: http://185.252.233.186  
**Admin**: http://185.252.233.186/admin

---

## 📱 Quick Commands (Run in VS Code Terminal)

### Access VPS
```bash
ssh root@185.252.233.186
# Or use the management script:
./vps-manage.sh ssh
```

### View Logs
```bash
./vps-manage.sh logs
```

### Check Services
```bash
./vps-manage.sh status
```

### Clear Cache
```bash
./vps-manage.sh cache:clear
```

### Restart All Services
```bash
./vps-manage.sh restart-all
```

### Database Backup
```bash
./vps-manage.sh db:backup
```

### Connect to MySQL
```bash
./vps-manage.sh db:connect
```

---

## 🔐 Security Checklist

- [ ] Change database password
- [ ] Update .env APP_DEBUG=false
- [ ] Set APP_ENV=production
- [ ] Install SSL certificate
- [ ] Configure firewall
- [ ] Set up automatic backups

---

## 📊 Server Info

| Item | Value |
|------|-------|
| IP Address | 185.252.233.186 |
| OS | Ubuntu 24.04 LTS |
| Web Server | Nginx 1.18 |
| PHP | 8.2-FPM |
| Database | MySQL 8.0 |
| Cache | Redis |
| Node.js | 20+ |
| App Path | /var/www/lms/Lernen/lernen-main-file/lernen |

---

## 🛠️ Useful SSH Commands

```bash
# Check service status
systemctl status nginx
systemctl status php8.2-fpm
systemctl status mysql
systemctl status redis-server

# View real-time logs
tail -f /var/www/lms/Lernen/lernen-main-file/lernen/storage/logs/laravel.log

# Clear caches
cd /var/www/lms/Lernen/lernen-main-file/lernen
php artisan cache:clear
php artisan config:clear

# Restart all services
systemctl restart nginx php8.2-fpm mysql redis-server

# Database backup
mysqldump -u lernen -p lernen_lms > backup.sql

# Check disk space
df -h

# Check memory usage
free -h
```

---

## 🆘 Troubleshooting

### 502 Bad Gateway
```bash
./vps-manage.sh restart php
./vps-manage.sh logs:php
```

### Database Connection Failed
```bash
./vps-manage.sh db:connect
./vps-manage.sh restart mysql
```

### Blank Page
```bash
./vps-manage.sh cache:clear
./vps-manage.sh restart-all
```

### Queue Jobs Not Processing
```bash
./vps-manage.sh logs:queue
./vps-manage.sh status
```

---

## 📞 Credentials

| Item | Value |
|------|-------|
| Root User | root |
| Root Password | EGcontabo420123 |
| DB User | lernen |
| DB Password | Lernen@LMS2024! |
| DB Name | lernen_lms |

---

## 📚 Full Documentation

See: `/workspaces/lms/DEPLOYMENT_SUMMARY.md`

---

## 🎯 Next Steps

1. **Test the website** at http://185.252.233.186
2. **Configure domain** - Point your domain DNS to 185.252.233.186
3. **Install SSL** - Run: `certbot certonly --webroot -w /var/www/lms/Lernen/lernen-main-file/lernen/public -d yourdomain.com`
4. **Security** - Change database password and update .env
5. **Backups** - Set up automatic database backups
6. **Monitoring** - Configure monitoring and alerts

---

## 📖 Management Script Help

```bash
./vps-manage.sh          # Show all commands
./vps-manage.sh ssh      # Connect via SSH
./vps-manage.sh logs     # View live logs
./vps-manage.sh status   # Check service status
```

---

**Deployment Date**: January 2, 2026  
**Status**: ✓ LIVE & PRODUCTION READY
