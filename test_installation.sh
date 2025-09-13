#!/bin/bash

# =================================================================
# 🔧 CRM ASContabilmente - Test & Validation Suite
# =================================================================
# Questo script testa l'installazione e valida il funzionamento
# =================================================================

set -e

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configurazione
CRM_DIR="/var/www/html/crm"
DB_NAME="crm_ascontabilmente"
DB_USER="crmuser"
DB_PASS="Admin123!"
NODE_PORT="3001"

# =================================================================
# FUNZIONI UTILITY
# =================================================================

print_header() {
    echo -e "\n${BLUE}=== $1 ===${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️ $1${NC}"
}

check_command() {
    if command -v $1 &> /dev/null; then
        print_success "$1 is installed"
        return 0
    else
        print_error "$1 is not installed"
        return 1
    fi
}

test_mysql_connection() {
    if mysql -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SELECT 1;" &> /dev/null; then
        print_success "MySQL connection successful"
        return 0
    else
        print_error "MySQL connection failed"
        return 1
    fi
}

test_web_service() {
    local url=$1
    local name=$2
    
    if curl -s -o /dev/null -w "%{http_code}" "$url" | grep -q "200\|302"; then
        print_success "$name is responding"
        return 0
    else
        print_error "$name is not responding"
        return 1
    fi
}

# =================================================================
# TEST SYSTEM REQUIREMENTS
# =================================================================

test_system_requirements() {
    print_header "Testing System Requirements"
    
    local errors=0
    
    # Test commands
    check_command "apache2" || ((errors++))
    check_command "php" || ((errors++))
    check_command "mysql" || ((errors++))
    check_command "node" || ((errors++))
    check_command "npm" || ((errors++))
    check_command "curl" || ((errors++))
    
    # Test PHP version
    php_version=$(php -r "echo PHP_VERSION;" 2>/dev/null || echo "0")
    if [[ $(echo "$php_version" | cut -d. -f1) -ge 7 ]]; then
        print_success "PHP version $php_version is supported"
    else
        print_error "PHP version $php_version is not supported (requires >= 7.4)"
        ((errors++))
    fi
    
    # Test Node version
    node_version=$(node --version 2>/dev/null | sed 's/v//' || echo "0")
    if [[ $(echo "$node_version" | cut -d. -f1) -ge 14 ]]; then
        print_success "Node.js version $node_version is supported"
    else
        print_error "Node.js version $node_version is not supported (requires >= 14)"
        ((errors++))
    fi
    
    return $errors
}

# =================================================================
# TEST PHP CONFIGURATION
# =================================================================

test_php_configuration() {
    print_header "Testing PHP Configuration"
    
    local errors=0
    
    # Test PHP extensions
    php_extensions=("mysqli" "pdo" "pdo_mysql" "json" "curl" "mbstring" "zip" "xml")
    
    for ext in "${php_extensions[@]}"; do
        if php -m | grep -q "$ext"; then
            print_success "PHP extension $ext is loaded"
        else
            print_error "PHP extension $ext is missing"
            ((errors++))
        fi
    done
    
    # Test PHP settings
    upload_max=$(php -r "echo ini_get('upload_max_filesize');")
    post_max=$(php -r "echo ini_get('post_max_size');")
    memory_limit=$(php -r "echo ini_get('memory_limit');")
    
    print_info "upload_max_filesize: $upload_max"
    print_info "post_max_size: $post_max"
    print_info "memory_limit: $memory_limit"
    
    return $errors
}

# =================================================================
# TEST DATABASE
# =================================================================

test_database() {
    print_header "Testing Database"
    
    local errors=0
    
    # Test MySQL connection
    test_mysql_connection || ((errors++))
    
    if [[ $errors -eq 0 ]]; then
        # Test database structure
        tables=("utenti" "clienti" "task" "task_clienti" "documenti" "email_log" "chat_conversations" "chat_messages")
        
        for table in "${tables[@]}"; do
            if mysql -u"$DB_USER" -p"$DB_PASS" -D"$DB_NAME" -e "DESCRIBE $table;" &> /dev/null; then
                print_success "Table $table exists"
            else
                print_error "Table $table is missing"
                ((errors++))
            fi
        done
        
        # Test default users
        user_count=$(mysql -u"$DB_USER" -p"$DB_PASS" -D"$DB_NAME" -se "SELECT COUNT(*) FROM utenti;" 2>/dev/null || echo "0")
        if [[ $user_count -gt 0 ]]; then
            print_success "Default users are present ($user_count users)"
        else
            print_error "No users found in database"
            ((errors++))
        fi
    fi
    
    return $errors
}

# =================================================================
# TEST WEB SERVICES
# =================================================================

test_web_services() {
    print_header "Testing Web Services"
    
    local errors=0
    
    # Test Apache
    if systemctl is-active --quiet apache2; then
        print_success "Apache2 service is running"
    else
        print_error "Apache2 service is not running"
        ((errors++))
    fi
    
    # Test MySQL
    if systemctl is-active --quiet mysql; then
        print_success "MySQL service is running"
    else
        print_error "MySQL service is not running"
        ((errors++))
    fi
    
    # Test CRM web interface
    test_web_service "http://localhost/crm/" "CRM Web Interface" || ((errors++))
    test_web_service "http://localhost/crm/login.php" "CRM Login Page" || ((errors++))
    
    # Test APIs
    test_web_service "http://localhost/crm/api/test.php" "CRM API" || ((errors++))
    
    return $errors
}

# =================================================================
# TEST NODE.JS SERVICES
# =================================================================

test_nodejs_services() {
    print_header "Testing Node.js Services"
    
    local errors=0
    
    # Check if Node.js service is running
    if pgrep -f "node.*socket.js" > /dev/null; then
        print_success "Node.js chat service is running"
    else
        print_warning "Node.js chat service is not running"
        print_info "Attempting to start chat service..."
        
        cd "$CRM_DIR"
        if [[ -f "package.json" ]]; then
            npm install &> /dev/null
            nohup node socket.js > /dev/null 2>&1 &
            sleep 3
            
            if pgrep -f "node.*socket.js" > /dev/null; then
                print_success "Node.js chat service started successfully"
            else
                print_error "Failed to start Node.js chat service"
                ((errors++))
            fi
        else
            print_error "package.json not found"
            ((errors++))
        fi
    fi
    
    # Test Node.js service endpoint
    if curl -s "http://localhost:$NODE_PORT" | grep -q "Cannot GET" || curl -s "http://localhost:$NODE_PORT" | grep -q "socket.io"; then
        print_success "Node.js service is responding on port $NODE_PORT"
    else
        print_error "Node.js service is not responding on port $NODE_PORT"
        ((errors++))
    fi
    
    return $errors
}

# =================================================================
# TEST FILE PERMISSIONS
# =================================================================

test_file_permissions() {
    print_header "Testing File Permissions"
    
    local errors=0
    
    # Test CRM directory permissions
    if [[ -d "$CRM_DIR" ]]; then
        print_success "CRM directory exists: $CRM_DIR"
        
        # Test write permissions for upload directories
        upload_dirs=("local_drive" "logs" "uploads")
        
        for dir in "${upload_dirs[@]}"; do
            full_path="$CRM_DIR/$dir"
            if [[ -d "$full_path" ]]; then
                if [[ -w "$full_path" ]]; then
                    print_success "Directory $dir is writable"
                else
                    print_error "Directory $dir is not writable"
                    ((errors++))
                fi
            else
                print_warning "Directory $dir does not exist, creating..."
                mkdir -p "$full_path"
                chown www-data:www-data "$full_path"
                chmod 755 "$full_path"
                print_success "Directory $dir created"
            fi
        done
    else
        print_error "CRM directory does not exist: $CRM_DIR"
        ((errors++))
    fi
    
    return $errors
}

# =================================================================
# TEST CHAT SYSTEM
# =================================================================

test_chat_system() {
    print_header "Testing Chat System"
    
    local errors=0
    
    # Test chat database tables
    chat_tables=("chat_conversations" "chat_messages")
    
    for table in "${chat_tables[@]}"; do
        if mysql -u"$DB_USER" -p"$DB_PASS" -D"$DB_NAME" -e "DESCRIBE $table;" &> /dev/null; then
            print_success "Chat table $table exists"
        else
            print_error "Chat table $table is missing"
            ((errors++))
        fi
    done
    
    # Test chat API endpoints
    test_web_service "http://localhost/crm/api/chat_get_conversations.php" "Chat Conversations API" || ((errors++))
    test_web_service "http://localhost/crm/api/chat_get_messages.php" "Chat Messages API" || ((errors++))
    
    # Test chat widget
    if [[ -f "$CRM_DIR/chat-widget-complete.php" ]]; then
        print_success "Chat widget file exists"
    else
        print_error "Chat widget file is missing"
        ((errors++))
    fi
    
    return $errors
}

# =================================================================
# PERFORMANCE TESTS
# =================================================================

test_performance() {
    print_header "Testing Performance"
    
    local errors=0
    
    # Test database performance
    print_info "Testing database query performance..."
    query_time=$(mysql -u"$DB_USER" -p"$DB_PASS" -D"$DB_NAME" -e "SELECT BENCHMARK(1000, (SELECT COUNT(*) FROM utenti));" 2>/dev/null | tail -1 || echo "failed")
    
    if [[ "$query_time" != "failed" ]]; then
        print_success "Database queries are responding"
    else
        print_warning "Database performance test failed"
    fi
    
    # Test web response time
    print_info "Testing web response time..."
    response_time=$(curl -o /dev/null -s -w "%{time_total}" "http://localhost/crm/")
    
    if (( $(echo "$response_time < 5" | bc -l) )); then
        print_success "Web response time: ${response_time}s (good)"
    else
        print_warning "Web response time: ${response_time}s (slow)"
    fi
    
    # Test disk space
    disk_usage=$(df /var/www/html | awk 'NR==2 {print $5}' | sed 's/%//')
    if [[ $disk_usage -lt 90 ]]; then
        print_success "Disk usage: ${disk_usage}% (good)"
    else
        print_warning "Disk usage: ${disk_usage}% (high)"
    fi
    
    return $errors
}

# =================================================================
# SECURITY TESTS
# =================================================================

test_security() {
    print_header "Testing Security"
    
    local errors=0
    
    # Test file permissions
    sensitive_files=("includes/config.php" "includes/db.php")
    
    for file in "${sensitive_files[@]}"; do
        full_path="$CRM_DIR/$file"
        if [[ -f "$full_path" ]]; then
            perms=$(stat -c "%a" "$full_path")
            if [[ "$perms" == "644" ]] || [[ "$perms" == "640" ]]; then
                print_success "File $file has secure permissions ($perms)"
            else
                print_warning "File $file has permissions $perms (consider 644 or 640)"
            fi
        else
            print_error "Sensitive file $file is missing"
            ((errors++))
        fi
    done
    
    # Test directory listing
    if curl -s "http://localhost/crm/includes/" | grep -q "Index of"; then
        print_error "Directory listing is enabled for /includes/"
        ((errors++))
    else
        print_success "Directory listing is disabled for /includes/"
    fi
    
    # Test SQL injection protection
    if curl -s "http://localhost/crm/login.php" -d "email=admin'&password=test" | grep -q "SQL"; then
        print_error "Possible SQL injection vulnerability detected"
        ((errors++))
    else
        print_success "SQL injection protection appears to be working"
    fi
    
    return $errors
}

# =================================================================
# GENERATE REPORT
# =================================================================

generate_report() {
    print_header "Test Report Summary"
    
    total_errors=$(($1 + $2 + $3 + $4 + $5 + $6 + $7 + $8 + $9))
    
    echo -e "\n${BLUE}📊 Test Results:${NC}"
    echo "├── System Requirements: $(($1 > 0 && echo "❌ Failed" || echo "✅ Passed"))"
    echo "├── PHP Configuration: $(($2 > 0 && echo "❌ Failed" || echo "✅ Passed"))"
    echo "├── Database: $(($3 > 0 && echo "❌ Failed" || echo "✅ Passed"))"
    echo "├── Web Services: $(($4 > 0 && echo "❌ Failed" || echo "✅ Passed"))"
    echo "├── Node.js Services: $(($5 > 0 && echo "❌ Failed" || echo "✅ Passed"))"
    echo "├── File Permissions: $(($6 > 0 && echo "❌ Failed" || echo "✅ Passed"))"
    echo "├── Chat System: $(($7 > 0 && echo "❌ Failed" || echo "✅ Passed"))"
    echo "├── Performance: $(($8 > 0 && echo "❌ Failed" || echo "✅ Passed"))"
    echo "└── Security: $(($9 > 0 && echo "❌ Failed" || echo "✅ Passed"))"
    
    echo -e "\n${BLUE}📈 Overall Status:${NC}"
    if [[ $total_errors -eq 0 ]]; then
        print_success "All tests passed! System is ready for production."
    elif [[ $total_errors -le 3 ]]; then
        print_warning "Minor issues detected ($total_errors errors). System is mostly functional."
    else
        print_error "Major issues detected ($total_errors errors). System requires attention."
    fi
    
    # Generate detailed log
    echo -e "\n${BLUE}📄 Detailed log saved to: /tmp/crm_test_report.log${NC}"
    
    return $total_errors
}

# =================================================================
# MAIN EXECUTION
# =================================================================

main() {
    echo -e "${BLUE}"
    echo "==================================================================="
    echo "🧪 CRM ASContabilmente - Test & Validation Suite"
    echo "==================================================================="
    echo -e "${NC}"
    
    # Redirect output to log file as well
    exec > >(tee /tmp/crm_test_report.log)
    
    # Run all tests
    test_system_requirements; req_errors=$?
    test_php_configuration; php_errors=$?
    test_database; db_errors=$?
    test_web_services; web_errors=$?
    test_nodejs_services; node_errors=$?
    test_file_permissions; perm_errors=$?
    test_chat_system; chat_errors=$?
    test_performance; perf_errors=$?
    test_security; sec_errors=$?
    
    # Generate final report
    generate_report $req_errors $php_errors $db_errors $web_errors $node_errors $perm_errors $chat_errors $perf_errors $sec_errors
    exit_code=$?
    
    echo -e "\n${BLUE}Test completed at $(date)${NC}"
    exit $exit_code
}

# Run main function
main "$@"
