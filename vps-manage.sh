#!/bin/bash
###############################################################################
# LERNEN LMS - VPS MANAGEMENT HELPER
# Run commands on your VPS from VS Code terminal
###############################################################################

VPS_IP="185.252.233.186"
VPS_USER="root"
VPS_PASS="EGcontabo420123"
APP_PATH="/var/www/lms/Lernen/lernen-main-file/lernen"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

usage() {
    cat <<EOF
${BLUE}LERNEN LMS - VPS Management${NC}

Usage: ./vps-manage.sh [command] [args]

${GREEN}Connection Commands:${NC}
  ssh                 Connect to VPS via SSH
  info                Get VPS information

${GREEN}Service Commands:${NC}
  status              Check all service status
  logs                View live application logs
  logs:nginx          View Nginx error logs
  logs:php            View PHP-FPM logs
  logs:queue          View queue worker logs
  restart-all         Restart all services
  restart [service]   Restart specific service (nginx|php|mysql|redis)

${GREEN}Database Commands:${NC}
  db:connect          Connect to MySQL database
  db:backup           Backup database
  db:list             List all databases

${GREEN}Cache Commands:${NC}
  cache:clear         Clear all Laravel caches
  cache:optimize      Optimize (rebuild) caches

${GREEN}Application Commands:${NC}
  tinker              Start Laravel tinker shell
  migrate             Run pending migrations
  seed                Run database seeders
  queue:work          Start queue worker manually
  storage:link        Create storage symlink

${GREEN}System Commands:${NC}
  disk-usage          Check disk space
  memory              Check memory usage
  update              Update system packages
  firewall-status     Check firewall status

${GREEN}Developer Commands:${NC}
  composer [cmd]      Run Composer command
  npm [cmd]           Run npm command
  artisan [cmd]       Run Laravel Artisan command

Examples:
  ./vps-manage.sh ssh
  ./vps-manage.sh logs
  ./vps-manage.sh cache:clear
  ./vps-manage.sh artisan tinker
  ./vps-manage.sh restart nginx
EOF
}

# Helper to run SSH commands
run_ssh() {
    local cmd="$1"
    echo -e "${BLUE}→${NC} Executing: $cmd"
    ssh -o ConnectTimeout=5 ${VPS_USER}@${VPS_IP} "$cmd" 2>/dev/null || echo "⚠️  SSH timeout or error"
}

# Commands
case "$1" in
    ssh)
        echo -e "${YELLOW}Connecting to ${VPS_IP}...${NC}"
        ssh ${VPS_USER}@${VPS_IP}
        ;;
    
    info)
        echo -e "${BLUE}VPS Information${NC}"
        run_ssh "uname -a && echo '---' && df -h / | tail -1 && echo '---' && free -h | grep Mem"
        ;;
    
    status)
        echo -e "${BLUE}Service Status${NC}"
        run_ssh "for svc in nginx php8.2-fpm mysql redis-server; do systemctl is-active \$svc && echo \"✓ \$svc\" || echo \"✗ \$svc\"; done"
        ;;
    
    logs)
        echo -e "${BLUE}Live Application Logs${NC}"
        echo "Press Ctrl+C to exit"
        run_ssh "tail -f ${APP_PATH}/storage/logs/laravel.log"
        ;;
    
    logs:nginx)
        run_ssh "tail -f /var/log/nginx/error.log"
        ;;
    
    logs:php)
        run_ssh "tail -f /var/log/php8.2-fpm.log"
        ;;
    
    logs:queue)
        run_ssh "tail -f /var/log/lms-worker.log"
        ;;
    
    restart-all)
        echo -e "${YELLOW}Restarting all services...${NC}"
        run_ssh "systemctl restart nginx php8.2-fpm mysql redis-server && echo '✓ All services restarted'"
        ;;
    
    restart)
        if [ -z "$2" ]; then
            echo "Usage: ./vps-manage.sh restart [nginx|php|mysql|redis]"
            exit 1
        fi
        case "$2" in
            nginx|php|mysql|redis)
                run_ssh "systemctl restart $2 && echo '✓ $2 restarted'"
                ;;
            *)
                echo "Unknown service: $2"
                ;;
        esac
        ;;
    
    db:connect)
        echo -e "${BLUE}Connecting to MySQL...${NC}"
        run_ssh "mysql -u lernen -pLernen@LMS2024! lernen_lms"
        ;;
    
    db:backup)
        echo -e "${BLUE}Creating database backup...${NC}"
        BACKUP_FILE="lernen_backup_$(date +%Y%m%d_%H%M%S).sql"
        run_ssh "mysqldump -u lernen -pLernen@LMS2024! lernen_lms > /tmp/${BACKUP_FILE}"
        echo "✓ Backup created: $BACKUP_FILE"
        echo "  Download from: /tmp/$BACKUP_FILE"
        ;;
    
    db:list)
        echo -e "${BLUE}Databases${NC}"
        run_ssh "mysql -u lernen -pLernen@LMS2024! -e 'SHOW DATABASES;'"
        ;;
    
    cache:clear)
        echo -e "${YELLOW}Clearing caches...${NC}"
        run_ssh "cd ${APP_PATH} && php artisan cache:clear && php artisan view:clear && echo '✓ Caches cleared'"
        ;;
    
    cache:optimize)
        echo -e "${YELLOW}Optimizing caches...${NC}"
        run_ssh "cd ${APP_PATH} && php artisan config:cache && php artisan route:cache && php artisan view:cache && echo '✓ Caches optimized'"
        ;;
    
    tinker)
        echo -e "${BLUE}Starting Laravel Tinker...${NC}"
        run_ssh "cd ${APP_PATH} && php artisan tinker"
        ;;
    
    migrate)
        echo -e "${YELLOW}Running migrations...${NC}"
        run_ssh "cd ${APP_PATH} && php artisan migrate --force"
        ;;
    
    seed)
        echo -e "${YELLOW}Running seeders...${NC}"
        run_ssh "cd ${APP_PATH} && php artisan db:seed"
        ;;
    
    queue:work)
        echo -e "${YELLOW}Starting queue worker...${NC}"
        run_ssh "cd ${APP_PATH} && php artisan queue:work redis --tries=3 --timeout=60"
        ;;
    
    storage:link)
        echo -e "${YELLOW}Creating storage symlink...${NC}"
        run_ssh "cd ${APP_PATH} && php artisan storage:link && echo '✓ Storage linked'"
        ;;
    
    disk-usage)
        echo -e "${BLUE}Disk Usage${NC}"
        run_ssh "df -h | grep -E '^/|^Filesystem'"
        ;;
    
    memory)
        echo -e "${BLUE}Memory Usage${NC}"
        run_ssh "free -h"
        ;;
    
    update)
        echo -e "${YELLOW}Updating system...${NC}"
        run_ssh "apt-get update && apt-get upgrade -y && echo '✓ System updated'"
        ;;
    
    firewall-status)
        echo -e "${BLUE}Firewall Status${NC}"
        run_ssh "ufw status"
        ;;
    
    composer)
        echo -e "${BLUE}Running Composer...${NC}"
        run_ssh "cd ${APP_PATH} && composer ${@:2}"
        ;;
    
    npm)
        echo -e "${BLUE}Running npm...${NC}"
        run_ssh "cd ${APP_PATH} && npm ${@:2}"
        ;;
    
    artisan)
        echo -e "${BLUE}Running Artisan...${NC}"
        run_ssh "cd ${APP_PATH} && php artisan ${@:2}"
        ;;
    
    *)
        usage
        ;;
esac
