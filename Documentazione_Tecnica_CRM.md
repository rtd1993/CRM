# 🔧 Documentazione Tecnica - Sistema CRM AS Contabilmente

> **Guida completa per amministratori e sviluppatori del sistema**

---

## 📖 Indice Tecnico

1. [🏗️ Architettura Sistema](#️-architettura-sistema)
2. [🗄️ Struttura Database](#️-struttura-database)
3. [📁 Organizzazione File](#-organizzazione-file)
4. [🔐 Sicurezza e Autenticazione](#-sicurezza-e-autenticazione)
5. [🔌 Integrazioni Esterne](#-integrazioni-esterne)
6. [⚙️ Configurazione Ambiente](#️-configurazione-ambiente)
7. [📧 Sistema Email](#-sistema-email)
8. [🔄 Backup e Recovery](#-backup-e-recovery)
9. [📊 Monitoring e Log](#-monitoring-e-log)
10. [🚀 Deploy e Aggiornamenti](#-deploy-e-aggiornamenti)
11. [🔧 Troubleshooting Avanzato](#-troubleshooting-avanzato)
12. [📈 Performance e Ottimizzazioni](#-performance-e-ottimizzazioni)

---

## 🏗️ Architettura Sistema

### **Stack Tecnologico**
- **Backend**: PHP 8.0+ con PDO MySQL
- **Frontend**: HTML5, CSS3, JavaScript (vanilla + Bootstrap 5)
- **Database**: MySQL/MariaDB 8.0+
- **Server Web**: Apache/Nginx con moduli PHP
- **Dipendenze**: Composer per librerie PHP

### **Struttura MVC Semplificata**
```
includes/
├── auth.php          # Gestione autenticazione e sessioni
├── config.php        # Configurazioni globali sistema
├── db.php            # Connessione database PDO
├── header.php        # Template header comune
├── functions.php     # Funzioni utility condivise
├── email_config.php  # Configurazioni SMTP
└── telegram.php      # Integrazione bot Telegram
```

### **Flusso Request-Response**
1. **Routing**: Ogni pagina PHP è un endpoint diretto
2. **Auth**: `require_once 'includes/auth.php'` in ogni pagina protetta
3. **Database**: Connessione PDO centralizzata in `includes/db.php`
4. **Template**: Header/Footer comuni inclusi in ogni pagina
5. **AJAX**: API endpoints in cartella `api/` per operazioni asincrone

### **Gestione Sessioni**
```php
// File: includes/auth.php
session_start();
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}
```

---

## 🗄️ Struttura Database

### **Tabelle Principali**

#### **Tabella `utenti`**
```sql
CREATE TABLE utenti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    ruolo ENUM('developer','admin','impiegato','guest') DEFAULT 'impiegato',
    telegram_chat_id VARCHAR(50),
    colore_personalizzato VARCHAR(7),
    is_online BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### **Tabella `clienti`**
```sql
CREATE TABLE clienti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    Inizio_rapporto DATE,
    Fine_rapporto DATE,
    Inserito_gestionale BOOLEAN DEFAULT FALSE,
    Codice_ditta VARCHAR(50),
    Colore VARCHAR(7),
    Cognome_Ragione_sociale VARCHAR(200) NOT NULL,
    Nome VARCHAR(100),
    Codice_fiscale VARCHAR(16),
    Partita_IVA VARCHAR(11),
    Qualifica VARCHAR(100),
    -- [continua con tutti i campi analizzati nel crea_cliente.php]
    Link_cartella TEXT,
    completo BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### **Tabella `procedure_crm`**
```sql
CREATE TABLE procedure_crm (
    id INT PRIMARY KEY AUTO_INCREMENT,
    denominazione VARCHAR(200) UNIQUE NOT NULL,
    valida_dal DATE NOT NULL,
    procedura TEXT NOT NULL,
    allegato VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### **Tabella `task`**
```sql
CREATE TABLE task (
    id INT PRIMARY KEY AUTO_INCREMENT,
    descrizione TEXT NOT NULL,
    scadenza DATE NOT NULL,
    assegnato_a INT,
    ricorrenza INT DEFAULT 0, -- giorni per ricorrenza
    fatturabile BOOLEAN DEFAULT FALSE,
    cliente_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assegnato_a) REFERENCES utenti(id),
    FOREIGN KEY (cliente_id) REFERENCES clienti(id)
);
```

#### **Tabella `enea`**
```sql
CREATE TABLE enea (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    anno_fiscale YEAR NOT NULL,
    prima_telefonata DATETIME,
    richiesta_documenti DATE,
    descrizione TEXT,
    copia_fatt_fornitore ENUM('OK','NO','PENDING') DEFAULT 'NO',
    schede_tecniche ENUM('OK','NO','PENDING') DEFAULT 'NO',
    visura_catastale ENUM('OK','NO','PENDING') DEFAULT 'NO',
    firma_notorio ENUM('OK','NO','PENDING') DEFAULT 'NO',
    firma_delega_ag_entr ENUM('OK','NO','PENDING') DEFAULT 'NO',
    firma_delega_enea ENUM('OK','NO','PENDING') DEFAULT 'NO',
    consenso ENUM('OK','NO','PENDING') DEFAULT 'NO',
    ev_atto_notorio ENUM('OK','NO','PENDING') DEFAULT 'NO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clienti(id)
);
```

#### **Tabella `conto_termico`**
```sql
CREATE TABLE conto_termico (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    numero_pratica VARCHAR(50),
    data_presentazione DATE,
    tipo_intervento VARCHAR(200),
    importo_ammissibile DECIMAL(10,2),
    contributo DECIMAL(10,2),
    stato ENUM('bozza','presentata','istruttoria','accettata','respinta','liquidata') DEFAULT 'bozza',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clienti(id)
);
```

#### **Tabelle Sistema Email**
```sql
CREATE TABLE email_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    oggetto VARCHAR(200) NOT NULL,
    corpo TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE email_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT,
    template_id INT,
    oggetto VARCHAR(200),
    corpo TEXT,
    destinatario_email VARCHAR(150),
    destinatario_nome VARCHAR(200),
    stato ENUM('inviata','fallita','in_coda') DEFAULT 'in_coda',
    messaggio_errore TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clienti(id),
    FOREIGN KEY (template_id) REFERENCES email_templates(id)
);
```

### **Indici e Performance**
```sql
-- Indici per performance
CREATE INDEX idx_clienti_cognome ON clienti(Cognome_Ragione_sociale);
CREATE INDEX idx_clienti_cf ON clienti(Codice_fiscale);
CREATE INDEX idx_task_scadenza ON task(scadenza);
CREATE INDEX idx_task_assegnato ON task(assegnato_a);
CREATE INDEX idx_email_log_cliente ON email_log(cliente_id);
CREATE INDEX idx_enea_cliente ON enea(cliente_id);
```

---

## 📁 Organizzazione File

### **Struttura Directory**
```
CRM/
├── index.php                 # Redirect a dashboard/login
├── login.php                 # Autenticazione utente
├── dashboard.php             # Dashboard principale
├── logout.php               # Logout e cleanup sessione
├── profilo.php              # Gestione profilo utente
│
├── clienti.php              # Lista clienti
├── crea_cliente.php         # Form nuovo cliente
├── modifica_cliente.php     # Modifica cliente esistente
├── info_cliente.php         # Dettagli cliente
├── elimina_cliente.php      # Eliminazione cliente
│
├── procedure.php            # Gestione procedure aziendali
├── crea_procedura.php       # Nuova procedura
├── modifica_procedura.php   # Modifica procedura
├── stampa_procedura.php     # Stampa PDF procedura
│
├── richieste.php           # Gestione richieste clienti
├── crea_richiesta.php      # Nuova richiesta
├── modifica_richiesta.php  # Modifica richiesta
├── stampa_richiesta.php    # Stampa PDF richiesta
│
├── enea.php                # Gestione pratiche ENEA
├── crea_enea.php           # Nuova pratica ENEA
├── modifica_enea.php       # Modifica pratica ENEA
├── stampa_enea.php         # Stampa PDF ENEA
│
├── conto_termico.php       # Gestione Conto Termico
├── crea_conto_termico.php  # Nuova pratica CT
├── modifica_conto_termico.php # Modifica pratica CT
├── stampa_conto_termico.php   # Stampa PDF CT
│
├── task.php                # Gestione task
├── task_clienti.php        # Task specifici clienti
├── calendario.php          # Calendario Google integrato
├── calendar_events.php     # Gestione eventi calendario
│
├── email.php               # Invio email massive
├── cronologia_email.php    # Storico invii email
├── gestione_email_template.php # Gestione template
│
├── drive.php               # File manager aziendale
├── upload.php              # Upload file
├── download.php            # Download file
│
├── contatti.php            # Contatti utili
├── link_utili.php          # Collegamenti esterni
├── gestione_utenti.php     # Gestione utenti (admin)
├── info.php               # Informazioni sistema
│
├── includes/               # File comuni
│   ├── auth.php           # Autenticazione
│   ├── config.php         # Configurazioni
│   ├── db.php             # Database
│   ├── header.php         # Template header
│   ├── functions.php      # Utility functions
│   ├── email_config.php   # Config SMTP
│   └── telegram.php       # Bot Telegram
│
├── api/                   # Endpoints API AJAX
│   ├── salva_appunto_cliente.php
│   ├── crea_cartella.php
│   └── [altri endpoint]
│
├── assets/               # Risorse statiche
│   ├── css/
│   │   └── style.css     # Stili personalizzati
│   └── js/
│       └── app.js        # JavaScript applicazione
│
├── local_drive/          # Archiviazione documenti
│   ├── ASContabilmente/  # Documenti aziendali
│   └── [ID_COGNOME.NOME]/ # Cartelle clienti
│
├── logs/                 # File di log
│   ├── error.log
│   ├── access.log
│   └── email.log
│
├── vendor/               # Dipendenze Composer
│   └── autoload.php
│
├── composer.json         # Dipendenze PHP
├── google-calendar.json  # Credenziali Google API
└── README.md            # Documentazione progetto
```

### **Convenzioni Naming**
- **File principali**: `[modulo].php` (es: `clienti.php`)
- **Form creazione**: `crea_[modulo].php`
- **Form modifica**: `modifica_[modulo].php`
- **Stampe PDF**: `stampa_[modulo].php`
- **Cartelle clienti**: `[ID]_[COGNOME].[NOME]`
- **API endpoints**: verbo + sostantivo (es: `salva_appunto_cliente.php`)

---

## 🔐 Sicurezza e Autenticazione

### **Password Hashing**
```php
// Creazione password hash (registrazione)
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Verifica password (login)
if (password_verify($password_input, $stored_hash)) {
    // Login OK
}
```

### **Controllo Sessioni**
```php
// includes/auth.php
function check_session_security() {
    // Rigenera session ID periodicamente
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    }
    
    if (time() - $_SESSION['last_regeneration'] > 300) { // 5 minuti
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}
```

### **Sanitizzazione Input**
```php
// Per output HTML
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// Per query SQL (usa sempre prepared statements)
$stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
$stmt->execute([$user_id]);

// Per validazione email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception("Email non valida");
}
```

### **Controllo Accessi per Ruolo**
```php
function require_role($required_roles) {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
    
    if (!in_array($_SESSION['role'], $required_roles)) {
        header('Location: dashboard.php?error=access_denied');
        exit();
    }
}

// Utilizzo
require_role(['admin', 'developer']); // Solo admin e developer
```

### **Protezione File Upload**
```php
function validate_upload($file) {
    $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_types)) {
        throw new Exception("Tipo file non consentito");
    }
    
    if ($file['size'] > $max_size) {
        throw new Exception("File troppo grande");
    }
    
    // Verifica MIME type reale
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    // Valida MIME type...
}
```

---

## 🔌 Integrazioni Esterne

### **Google Calendar API**
```php
// Configurazione in includes/config.php
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/../google-calendar.json');

// Utilizzo in dashboard.php
function getCalendarEvents($timeMin, $timeMax) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    $client = new Google_Client();
    $client->useApplicationDefaultCredentials();
    $client->addScope(Google_Service_Calendar::CALENDAR);
    
    $service = new Google_Service_Calendar($client);
    // ... resto implementazione
}
```

### **Configurazione Google Calendar**
1. **Crea progetto** in Google Cloud Console
2. **Abilita Calendar API** nel progetto
3. **Crea Service Account** con permissions
4. **Scarica JSON credentials** come `google-calendar.json`
5. **Condividi calendario** con email del service account

### **Telegram Bot Integration**
```php
// File: includes/telegram.php
function sendTelegramMessage($chat_id, $message) {
    $bot_token = 'YOUR_BOT_TOKEN'; // Da configurare
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}
```

### **Setup Bot Telegram**
1. **Crea bot** con @BotFather su Telegram
2. **Ottieni token** dal BotFather
3. **Configura webhook** (opzionale per notifiche avanzate)
4. **Test connessione** dal profilo utente CRM

---

## ⚙️ Configurazione Ambiente

### **Requisiti Server**
- **PHP**: 8.0+ con estensioni PDO, MySQLi, cURL, GD, mbstring
- **Database**: MySQL 8.0+ o MariaDB 10.3+
- **Server Web**: Apache 2.4+ o Nginx 1.18+
- **Spazio Disco**: 1GB+ per documenti
- **RAM**: 512MB+ raccomandati

### **File di Configurazione**
```php
// includes/config.php
<?php
define('SITE_NAME', 'CRM AS Contabilmente');
define('SITE_URL', 'https://your-domain.com/crm/');

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'crm_database');
define('DB_USER', 'crm_user');
define('DB_PASS', 'secure_password');

// Paths
define('UPLOAD_PATH', __DIR__ . '/../local_drive/');
define('LOGS_PATH', __DIR__ . '/../logs/');

// Email SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'app_password');
define('SMTP_FROM', 'noreply@ascontabilmente.com');

// Telegram
define('TELEGRAM_BOT_TOKEN', 'your_bot_token');

// Security
define('SESSION_TIMEOUT', 3600); // 1 ora
define('MAX_LOGIN_ATTEMPTS', 5);
```

### **Configurazione Apache (.htaccess)**
```apache
# Sicurezza base
Options -Indexes
ServerSignature Off

# Redirect HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Protezione file sensibili
<Files "*.json">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>

# Limiti upload
php_value upload_max_filesize 10M
php_value post_max_size 15M
php_value max_execution_time 60

# Headers sicurezza
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

### **Configurazione Nginx**
```nginx
server {
    listen 443 ssl;
    server_name your-domain.com;
    
    root /var/www/crm;
    index index.php;
    
    # Logs
    access_log /var/log/nginx/crm_access.log;
    error_log /var/log/nginx/crm_error.log;
    
    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to sensitive files
    location ~ /\.(ht|git|json)$ {
        deny all;
    }
    
    location ~ /logs/ {
        deny all;
    }
    
    # Max upload size
    client_max_body_size 10M;
}
```

---

## 📧 Sistema Email

### **Configurazione PHPMailer**
```php
// includes/email_config.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

function inviaEmailSMTP($to_email, $to_name, $subject, $body) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM, SITE_NAME);
        $mail->addAddress($to_email, $to_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->CharSet = 'UTF-8';
        
        $mail->send();
        return ['success' => true];
        
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}
```

### **Template Email con Variabili**
```php
function processEmailTemplate($template_body, $cliente_data) {
    $replacements = [
        '{nome_cliente}' => $cliente_data['Nome'] ?? '',
        '{cognome_cliente}' => $cliente_data['Cognome_Ragione_sociale'] ?? '',
        '{ragione_sociale}' => $cliente_data['Cognome_Ragione_sociale'] ?? '',
        '{codice_fiscale}' => $cliente_data['Codice_fiscale'] ?? '',
        '{partita_iva}' => $cliente_data['Partita_IVA'] ?? '',
        '{data_oggi}' => date('d/m/Y'),
        '{anno_corrente}' => date('Y')
    ];
    
    return str_replace(array_keys($replacements), array_values($replacements), $template_body);
}
```

### **Logging Email Avanzato**
```php
function logEmailEvent($cliente_id, $template_id, $email, $status, $error = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO email_log 
        (cliente_id, template_id, destinatario_email, stato, messaggio_errore, sent_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$cliente_id, $template_id, $email, $status, $error]);
    
    // Log anche su file per debug
    $log_entry = sprintf(
        "[%s] EMAIL %s: %s | Cliente: %d | Template: %d | Error: %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($status),
        $email,
        $cliente_id,
        $template_id,
        $error ?? 'none'
    );
    
    file_put_contents(LOGS_PATH . 'email.log', $log_entry, FILE_APPEND | LOCK_EX);
}
```

---

## 🔄 Backup e Recovery

### **Script Backup Automatico**
```bash
#!/bin/bash
# File: backup_crm.sh

# Configurazioni
DB_NAME="crm_database"
DB_USER="crm_user"
DB_PASS="password"
BACKUP_DIR="/var/backups/crm"
DATE=$(date +%Y%m%d_%H%M%S)

# Crea directory backup se non esiste
mkdir -p $BACKUP_DIR

# Backup Database
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Backup File
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /var/www/crm/local_drive/

# Pulizia backup vecchi (>30 giorni)
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

# Log operazione
echo "[$(date)] Backup completato: db_backup_$DATE.sql.gz, files_backup_$DATE.tar.gz" >> $BACKUP_DIR/backup.log
```

### **Crontab per Backup Automatici**
```bash
# Modifica crontab: crontab -e

# Backup giornaliero alle 2:00
0 2 * * * /path/to/backup_crm.sh

# Backup settimanale completo domenica alle 3:00
0 3 * * 0 /path/to/full_backup_crm.sh

# Cleanup log vecchi ogni lunedì
0 4 * * 1 find /var/www/crm/logs -name "*.log" -mtime +7 -delete
```

### **Procedura di Recovery**
```bash
#!/bin/bash
# File: restore_crm.sh

BACKUP_FILE=$1
DB_NAME="crm_database"

if [ -z "$BACKUP_FILE" ]; then
    echo "Uso: $0 <file_backup.sql.gz>"
    exit 1
fi

# Ripristino database
echo "Ripristinando database da $BACKUP_FILE..."
gunzip < $BACKUP_FILE | mysql -u$DB_USER -p$DB_PASS $DB_NAME

# Ripristino file (manuale)
echo "Per ripristinare i file, estrarre manualmente:"
echo "tar -xzf files_backup_YYYYMMDD_HHMMSS.tar.gz -C /"

echo "Recovery completato!"
```

---

## 📊 Monitoring e Log

### **Configurazione Log PHP**
```php
// includes/config.php
ini_set('log_errors', 1);
ini_set('error_log', LOGS_PATH . 'php_error.log');

// Custom logging function
function writeLog($level, $message, $context = []) {
    $log_entry = sprintf(
        "[%s] [%s] %s %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        !empty($context) ? json_encode($context) : ''
    );
    
    file_put_contents(LOGS_PATH . 'application.log', $log_entry, FILE_APPEND | LOCK_EX);
}

// Utilizzo
writeLog('info', 'User login', ['user_id' => $_SESSION['user_id']]);
writeLog('error', 'Database connection failed', ['host' => DB_HOST]);
```

### **Monitoring Performance**
```php
// includes/performance.php
class PerformanceMonitor {
    private static $start_time;
    private static $queries = 0;
    
    public static function start() {
        self::$start_time = microtime(true);
    }
    
    public static function addQuery() {
        self::$queries++;
    }
    
    public static function end($page) {
        $execution_time = microtime(true) - self::$start_time;
        $memory_usage = memory_get_peak_usage(true);
        
        if ($execution_time > 2.0) { // Log pagine lente >2s
            writeLog('warning', 'Slow page', [
                'page' => $page,
                'time' => $execution_time,
                'memory' => $memory_usage,
                'queries' => self::$queries
            ]);
        }
    }
}

// Uso nelle pagine
PerformanceMonitor::start();
// ... codice pagina ...
PerformanceMonitor::end(basename(__FILE__));
```

### **Health Check Endpoint**
```php
// File: api/health_check.php
<?php
require_once '../includes/db.php';

$health = [
    'status' => 'ok',
    'timestamp' => time(),
    'checks' => []
];

// Test database
try {
    $stmt = $pdo->query("SELECT 1");
    $health['checks']['database'] = 'ok';
} catch (Exception $e) {
    $health['checks']['database'] = 'error';
    $health['status'] = 'error';
}

// Test spazio disco
$free_space = disk_free_space('/');
$total_space = disk_total_space('/');
$usage_percent = (($total_space - $free_space) / $total_space) * 100;

if ($usage_percent > 90) {
    $health['checks']['disk'] = 'warning';
    $health['status'] = 'warning';
} else {
    $health['checks']['disk'] = 'ok';
}

// Test upload directory
if (!is_writable(__DIR__ . '/../local_drive/')) {
    $health['checks']['uploads'] = 'error';
    $health['status'] = 'error';
} else {
    $health['checks']['uploads'] = 'ok';
}

header('Content-Type: application/json');
echo json_encode($health);
```

### **Log Rotation**
```bash
# File: /etc/logrotate.d/crm
/var/www/crm/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 644 www-data www-data
    postrotate
        # Restart PHP-FPM se necessario
        systemctl reload php8.0-fpm
    endscript
}
```

---

## 🚀 Deploy e Aggiornamenti

### **Script Deploy Automatico**
```bash
#!/bin/bash
# File: deploy.sh

# Configurazioni
REPO_URL="https://github.com/rtd1993/CRM.git"
DEPLOY_DIR="/var/www/crm"
BACKUP_DIR="/var/backups/crm-deploy"
BRANCH="master"

echo "Starting deployment..."

# Backup corrente
mkdir -p $BACKUP_DIR
DATE=$(date +%Y%m%d_%H%M%S)
tar -czf $BACKUP_DIR/pre-deploy-$DATE.tar.gz $DEPLOY_DIR

# Manutenzione mode
cp $DEPLOY_DIR/maintenance.html $DEPLOY_DIR/index.php

# Pull aggiornamenti
cd $DEPLOY_DIR
git fetch origin
git reset --hard origin/$BRANCH

# Aggiorna dipendenze
composer install --no-dev --optimize-autoloader

# Permissions
chown -R www-data:www-data $DEPLOY_DIR
chmod -R 755 $DEPLOY_DIR
chmod -R 777 $DEPLOY_DIR/local_drive
chmod -R 777 $DEPLOY_DIR/logs

# Ripristina index
git checkout index.php

# Test connessione DB
php -f $DEPLOY_DIR/test_db_connection.php

if [ $? -eq 0 ]; then
    echo "Deploy completed successfully!"
    
    # Log deploy
    echo "[$(date)] Deploy $DATE completed successfully" >> $DEPLOY_DIR/logs/deploy.log
    
    # Notifica Telegram (se configurato)
    # curl -X POST "https://api.telegram.org/bot$BOT_TOKEN/sendMessage" \
    #      -d chat_id=$ADMIN_CHAT_ID \
    #      -d text="✅ CRM Deploy completato con successo"
else
    echo "Deploy failed! Rolling back..."
    
    # Rollback
    tar -xzf $BACKUP_DIR/pre-deploy-$DATE.tar.gz -C /
    
    echo "Rollback completed"
    exit 1
fi
```

### **Migrazione Database**
```php
// File: migrations/migrate.php
<?php
require_once '../includes/db.php';

$migrations = [
    '001_create_users_table.sql',
    '002_create_clienti_table.sql',
    '003_add_telegram_field.sql'
    // ... altre migrazioni
];

function getCurrentVersion() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT version FROM schema_migrations ORDER BY version DESC LIMIT 1");
        return $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        // Tabella non esiste, crea
        $pdo->exec("CREATE TABLE schema_migrations (version INT PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        return 0;
    }
}

function runMigration($file) {
    global $pdo;
    
    $sql = file_get_contents(__DIR__ . '/' . $file);
    $version = (int) substr($file, 0, 3);
    
    try {
        $pdo->exec($sql);
        $pdo->prepare("INSERT INTO schema_migrations (version) VALUES (?)")->execute([$version]);
        echo "✅ Applied: $file\n";
        return true;
    } catch (Exception $e) {
        echo "❌ Failed: $file - " . $e->getMessage() . "\n";
        return false;
    }
}

// Esegui migrazioni
$current_version = getCurrentVersion();
echo "Current version: $current_version\n";

foreach ($migrations as $migration) {
    $version = (int) substr($migration, 0, 3);
    if ($version > $current_version) {
        if (!runMigration($migration)) {
            echo "Migration failed, stopping.\n";
            exit(1);
        }
    }
}

echo "All migrations completed!\n";
```

---

## 🔧 Troubleshooting Avanzato

### **Debug Database Performance**
```sql
-- Query lente
SELECT * FROM information_schema.PROCESSLIST 
WHERE COMMAND != 'Sleep' 
ORDER BY TIME DESC;

-- Indici mancanti
SELECT 
    t.TABLE_NAME,
    t.TABLE_ROWS,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
FROM information_schema.TABLES t
WHERE t.TABLE_SCHEMA = 'crm_database'
ORDER BY (data_length + index_length) DESC;

-- Analisi query lente (abilita slow query log)
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

### **Debug PHP Memory Issues**
```php
// Monitoring memoria
function checkMemoryUsage($point) {
    $memory = memory_get_usage(true);
    $peak = memory_get_peak_usage(true);
    error_log("Memory at $point: " . formatBytes($memory) . " (peak: " . formatBytes($peak) . ")");
}

function formatBytes($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, 2) . $units[$i];
}

// Uso
checkMemoryUsage('start');
// ... codice ...
checkMemoryUsage('after query');
```

### **Debug Sessions**
```php
// File: debug_session.php (solo per admin)
<?php
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'developer'])) {
    die('Access denied');
}

echo "<h2>Session Debug</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "\nSession File: " . session_save_path() . "/sess_" . session_id() . "\n";

if (file_exists(session_save_path() . "/sess_" . session_id())) {
    echo "Session File Contents:\n";
    echo file_get_contents(session_save_path() . "/sess_" . session_id());
}
echo "</pre>";
```

### **Network Diagnostics**
```php
// Test connessioni esterne
function testExternalConnections() {
    $tests = [
        'Google API' => 'https://www.googleapis.com',
        'Telegram API' => 'https://api.telegram.org',
        'SMTP Gmail' => 'smtp.gmail.com:587'
    ];
    
    foreach ($tests as $name => $url) {
        $start = microtime(true);
        
        if (strpos($url, 'smtp') !== false) {
            // Test SMTP
            list($host, $port) = explode(':', $url);
            $result = @fsockopen($host, $port, $errno, $errstr, 5);
            $status = $result ? 'OK' : "FAIL ($errno: $errstr)";
            if ($result) fclose($result);
        } else {
            // Test HTTP
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            
            $result = curl_exec($ch);
            $status = $result ? 'OK' : 'FAIL (' . curl_error($ch) . ')';
            curl_close($ch);
        }
        
        $time = round((microtime(true) - $start) * 1000, 2);
        echo "$name: $status ({$time}ms)\n";
    }
}
```

---

## 📈 Performance e Ottimizzazioni

### **Configurazione MySQL Ottimizzata**
```sql
-- File: /etc/mysql/mysql.conf.d/crm-optimizations.cnf

[mysqld]
# InnoDB Settings
innodb_buffer_pool_size = 512M
innodb_log_file_size = 64M
innodb_log_buffer_size = 16M
innodb_flush_log_at_trx_commit = 2

# Query Cache (MySQL 5.7)
query_cache_type = 1
query_cache_size = 64M
query_cache_limit = 2M

# Connection Settings
max_connections = 100
max_user_connections = 50

# Slow Query Log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
log_queries_not_using_indexes = 1
```

### **Caching Strategie**
```php
// Simple file-based cache
class SimpleCache {
    private static $cache_dir = '/tmp/crm_cache/';
    
    public static function get($key) {
        $file = self::$cache_dir . md5($key) . '.cache';
        if (file_exists($file) && time() - filemtime($file) < 300) { // 5 min TTL
            return unserialize(file_get_contents($file));
        }
        return null;
    }
    
    public static function set($key, $data) {
        if (!is_dir(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
        }
        $file = self::$cache_dir . md5($key) . '.cache';
        file_put_contents($file, serialize($data));
    }
}

// Uso
$cache_key = "clienti_list_" . md5(serialize($_GET));
$clienti = SimpleCache::get($cache_key);

if ($clienti === null) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clienti = $stmt->fetchAll();
    SimpleCache::set($cache_key, $clienti);
}
```

### **Database Connection Pooling**
```php
// Singleton pattern per connessione DB
class DatabaseConnection {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_PERSISTENT => true // Connection pooling
        ];
        
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new DatabaseConnection();
        }
        return self::$instance->pdo;
    }
}

// Uso in includes/db.php
$pdo = DatabaseConnection::getInstance();
```

### **Frontend Optimizations**
```html
<!-- Lazy loading immagini -->
<img src="placeholder.jpg" data-src="real-image.jpg" loading="lazy" class="lazyload">

<!-- Preload risorse critiche -->
<link rel="preload" href="assets/css/critical.css" as="style">
<link rel="preload" href="assets/js/app.js" as="script">

<!-- CDN per librerie -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Minificazione e compressione -->
<script src="assets/js/app.min.js?v=1.2.3"></script>
```

---

## 🎯 Best Practices Sviluppo

### **Coding Standards**
```php
// PSR-12 compliant
<?php

declare(strict_types=1);

namespace CRM\Services;

use Exception;
use PDO;

class ClientService
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clienti WHERE id = ?');
        $stmt->execute([$id]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
```

### **Error Handling Standard**
```php
// Gestione errori centralizzata
function handleError($exception) {
    $error_id = uniqid();
    
    // Log completo per sviluppatori
    error_log("ERROR [$error_id]: " . $exception->getMessage() . "\n" . $exception->getTraceAsString());
    
    // Messaggio user-friendly
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        return $exception->getMessage();
    } else {
        return "Si è verificato un errore. ID: $error_id";
    }
}

// Uso
try {
    // ... codice che può fallire
} catch (Exception $e) {
    $error_message = handleError($e);
    // Mostra errore all'utente
}
```

### **Testing Guidelines**
```php
// Test database connection
// File: tests/database_test.php
function testDatabaseConnection() {
    try {
        require_once '../includes/db.php';
        $stmt = $pdo->query("SELECT 1");
        return $stmt->fetchColumn() === 1;
    } catch (Exception $e) {
        echo "DB Test Failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Test email configuration
function testEmailConfig() {
    require_once '../includes/email_config.php';
    return inviaEmailSMTP('test@example.com', 'Test', 'Test Subject', 'Test Body');
}

// Run tests
if (php_sapi_name() === 'cli') {
    $tests = [
        'Database Connection' => testDatabaseConnection(),
        'Email Configuration' => testEmailConfig(),
    ];
    
    foreach ($tests as $test => $result) {
        echo $result ? "✅" : "❌";
        echo " $test\n";
    }
}
```

---

> **Nota per Amministratori**: Questa documentazione deve essere mantenuta aggiornata ad ogni modifica del sistema. Utilizza il versioning per tracciare le modifiche importanti.

**Ultimo aggiornamento**: Settembre 2024  
**Versione documentazione**: 1.0  
**Compatibilità sistema**: CRM v2.0+