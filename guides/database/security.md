# 🔒 Guia de Segurança MySQL + PHP + ActiveRecord

Este guia aborda práticas essenciais de segurança para proteger aplicações PHP com MySQL e ActiveRecord contra vulnerabilidades comuns.

## 📋 Índice
- [SQL Injection](#sql-injection)
- [Autenticação e Autorização](#autenticação-e-autorização)
- [Criptografia de Dados](#criptografia-de-dados)
- [Configurações Seguras](#configurações-seguras)
- [Auditoria e Logs](#auditoria-e-logs)
- [Backup e Recovery](#backup-e-recovery)
- [Monitorização de Segurança](#monitorização-de-segurança)
- [Hardening do MySQL](#hardening-do-mysql)
- [Boas Práticas PHP](#boas-práticas-php)
- [Validação e Sanitização](#validação-e-sanitização)

---

## 🛡️ SQL Injection

### **Prevenção com ActiveRecord**

```php
// ❌ VULNERÁVEL - Nunca fazer isso
class VulnerableModel extends ActiveRecord\Model {
    public static function findByName($name) {
        // PERIGO: SQL Injection
        return static::find('all', [
            'conditions' => "name = '$name'"
        ]);
    }
    
    public static function searchPosts($query) {
        // PERIGO: SQL Injection
        return static::find_by_sql("SELECT * FROM posts WHERE title LIKE '%$query%'");
    }
}

// ✅ SEGURO - Usar parâmetros
class SecureModel extends ActiveRecord\Model {
    public static function findByName($name) {
        return static::find('all', [
            'conditions' => ['name = ?', $name]
        ]);
    }
    
    public static function searchPosts($query) {
        return static::find_by_sql(
            "SELECT * FROM posts WHERE title LIKE ?", 
            ["%$query%"]
        );
    }
    
    public static function findByMultipleFields($name, $email, $status) {
        return static::find('all', [
            'conditions' => [
                'name = ? AND email = ? AND status = ?', 
                $name, $email, $status
            ]
        ]);
    }
    
    public static function findByNamedParams($params) {
        return static::find('all', [
            'conditions' => [
                'name = :name AND email = :email AND status = :status',
                $params
            ]
        ]);
    }
}
```

### **Sanitização Avançada**

```php
class InputSanitizer {
    
    public static function sanitizeString($input, $maxLength = 255) {
        if (!is_string($input)) {
            throw new InvalidArgumentException("Input deve ser string");
        }
        
        // Remover caracteres nulos
        $input = str_replace("\0", "", $input);
        
        // Limitar tamanho
        $input = substr($input, 0, $maxLength);
        
        // Trim espaços
        $input = trim($input);
        
        return $input;
    }
    
    public static function sanitizeEmail($email) {
        $email = self::sanitizeString($email);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Email inválido");
        }
        
        return strtolower($email);
    }
    
    public static function sanitizeInt($input, $min = null, $max = null) {
        $int = filter_var($input, FILTER_VALIDATE_INT);
        
        if ($int === false) {
            throw new InvalidArgumentException("Valor deve ser inteiro");
        }
        
        if ($min !== null && $int < $min) {
            throw new InvalidArgumentException("Valor menor que o mínimo permitido");
        }
        
        if ($max !== null && $int > $max) {
            throw new InvalidArgumentException("Valor maior que o máximo permitido");
        }
        
        return $int;
    }
    
    public static function sanitizeArray($input, $allowedKeys) {
        if (!is_array($input)) {
            throw new InvalidArgumentException("Input deve ser array");
        }
        
        $sanitized = [];
        foreach ($allowedKeys as $key) {
            if (isset($input[$key])) {
                $sanitized[$key] = $input[$key];
            }
        }
        
        return $sanitized;
    }
    
    public static function sanitizeHtml($input) {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

// Uso com ActiveRecord
class User extends ActiveRecord\Model {
    
    public function setName($name) {
        $this->name = InputSanitizer::sanitizeString($name, 100);
    }
    
    public function setEmail($email) {
        $this->email = InputSanitizer::sanitizeEmail($email);
    }
    
    static $before_save = ['validate_and_sanitize'];
    
    public function validate_and_sanitize() {
        if (isset($this->name)) {
            $this->name = InputSanitizer::sanitizeString($this->name);
        }
        
        if (isset($this->email)) {
            $this->email = InputSanitizer::sanitizeEmail($this->email);
        }
    }
}
```

---

## 🔐 Autenticação e Autorização

### **Sistema de Autenticação Seguro**

```php
class SecureAuth {
    
    public static function hashPassword($password) {
        // Usar password_hash com custo adequado
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterações
            'threads' => 3,         // 3 threads
        ]);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateSecureToken();
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function rateLimitLogin($identifier, $maxAttempts = 5, $windowMinutes = 15) {
        $key = "login_attempts_" . hash('sha256', $identifier);
        
        if (extension_loaded('apcu')) {
            $attempts = apcu_fetch($key) ?: 0;
            
            if ($attempts >= $maxAttempts) {
                throw new Exception("Muitas tentativas de login. Tente novamente em $windowMinutes minutos.");
            }
            
            apcu_store($key, $attempts + 1, $windowMinutes * 60);
        }
    }
    
    public static function clearRateLimit($identifier) {
        $key = "login_attempts_" . hash('sha256', $identifier);
        if (extension_loaded('apcu')) {
            apcu_delete($key);
        }
    }
}

class User extends ActiveRecord\Model {
    static $attr_protected = ['id', 'password_hash', 'created_at', 'updated_at'];
    static $validates_presence_of = [['email'], ['password']];
    static $validates_uniqueness_of = [['email']];
    
    static $before_save = ['hash_password_if_changed'];
    
    public function hash_password_if_changed() {
        if (isset($this->password) && !empty($this->password)) {
            $this->password_hash = SecureAuth::hashPassword($this->password);
            unset($this->password);
        }
    }
    
    public function authenticate($password) {
        return SecureAuth::verifyPassword($password, $this->password_hash);
    }
    
    public static function login($email, $password) {
        // Rate limiting
        SecureAuth::rateLimitLogin($email);
        
        $user = self::find_by_email($email);
        
        if ($user && $user->authenticate($password)) {
            // Login bem-sucedido
            SecureAuth::clearRateLimit($email);
            
            // Atualizar último login
            $user->last_login_at = new DateTime();
            $user->login_count = ($user->login_count ?? 0) + 1;
            $user->save();
            
            // Log de segurança
            SecurityLogger::logLogin($user->id, $_SERVER['REMOTE_ADDR']);
            
            return $user;
        }
        
        // Log de tentativa falhada
        SecurityLogger::logFailedLogin($email, $_SERVER['REMOTE_ADDR']);
        
        throw new Exception("Credenciais inválidas");
    }
    
    public function hasPermission($permission) {
        // Implementar sistema de permissões
        return in_array($permission, $this->getPermissions());
    }
    
    public function getPermissions() {
        // Buscar permissões do usuário
        return UserPermission::find('all', [
            'conditions' => ['user_id = ?', $this->id],
            'select' => 'permission_name'
        ])->to_array();
    }
}

// Sistema de sessões seguras
class SecureSession {
    
    public static function start() {
        // Configurações seguras de sessão
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
        
        // Regenerar ID da sessão
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
    }
    
    public static function login($user) {
        // Regenerar sessão ao fazer login
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['login_time'] = time();
        $_SESSION['csrf_token'] = SecureAuth::generateSecureToken();
    }
    
    public static function logout() {
        // Limpar dados da sessão
        $_SESSION = [];
        
        // Invalidar cookie de sessão
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    public static function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        // Verificar expiração da sessão
        if (isset($_SESSION['login_time']) && 
            (time() - $_SESSION['login_time']) > 3600) { // 1 hora
            self::logout();
            return null;
        }
        
        return User::find($_SESSION['user_id']);
    }
}
```

---

## 🔒 Criptografia de Dados

### **Criptografia de Campos Sensíveis**

```php
class FieldEncryption {
    private static $key;
    
    public static function setKey($key) {
        if (strlen($key) !== 32) {
            throw new InvalidArgumentException("Chave deve ter 32 bytes");
        }
        self::$key = $key;
    }
    
    public static function encrypt($data) {
        if (empty(self::$key)) {
            throw new Exception("Chave de criptografia não definida");
        }
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', self::$key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    public static function decrypt($encryptedData) {
        if (empty(self::$key)) {
            throw new Exception("Chave de criptografia não definida");
        }
        
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', self::$key, 0, $iv);
    }
}

// Trait para campos encriptados
trait EncryptedFields {
    private static $encryptedFields = [];
    
    public static function encrypts($field) {
        self::$encryptedFields[] = $field;
    }
    
    public function __set($name, $value) {
        if (in_array($name, self::$encryptedFields)) {
            $value = FieldEncryption::encrypt($value);
        }
        parent::__set($name, $value);
    }
    
    public function __get($name) {
        $value = parent::__get($name);
        
        if (in_array($name, self::$encryptedFields) && !empty($value)) {
            return FieldEncryption::decrypt($value);
        }
        
        return $value;
    }
}

// Uso em models
class Patient extends ActiveRecord\Model {
    use EncryptedFields;
    
    public static function init() {
        self::encrypts('ssn');          // CPF
        self::encrypts('medical_record'); // Prontuário
        self::encrypts('phone');        // Telefone
    }
    
    static $validates_presence_of = [['name'], ['ssn']];
}

// Inicializar criptografia
FieldEncryption::setKey(hash('sha256', $_ENV['ENCRYPTION_KEY'], true));
Patient::init();
```

### **Hash de Dados Sensíveis**

```php
class SecureHashing {
    
    public static function hashSensitiveData($data, $salt = null) {
        if ($salt === null) {
            $salt = random_bytes(16);
        }
        
        $hash = hash_pbkdf2('sha256', $data, $salt, 10000, 32, true);
        
        return base64_encode($salt . $hash);
    }
    
    public static function verifySensitiveData($data, $hashedData) {
        $decoded = base64_decode($hashedData);
        $salt = substr($decoded, 0, 16);
        $hash = substr($decoded, 16);
        
        $newHash = hash_pbkdf2('sha256', $data, $salt, 10000, 32, true);
        
        return hash_equals($hash, $newHash);
    }
    
    public static function tokenizeData($data) {
        // Gerar token irreversível para dados sensíveis
        return hash('sha256', $data . $_ENV['TOKEN_SALT']);
    }
}
```

---

## ⚙️ Configurações Seguras

### **Configuração Segura do MySQL**

```ini
# my.cnf - Configurações de segurança

[mysqld]
# Desabilitar funções perigosas
local_infile = 0
skip_show_database = 1

# SSL/TLS
ssl_ca = /path/to/ca.pem
ssl_cert = /path/to/server-cert.pem
ssl_key = /path/to/server-key.pem
require_secure_transport = ON

# Logging de segurança
log_error = /var/log/mysql/error.log
general_log = 1
general_log_file = /var/log/mysql/general.log

# Configurações de conexão
max_connect_errors = 10
max_user_connections = 50

# Validação de senhas
validate_password_policy = STRONG
validate_password_length = 12
validate_password_number_count = 2
validate_password_special_char_count = 1
validate_password_mixed_case_count = 2

# Firewall SQL (MySQL Enterprise)
mysql_firewall_mode = ON
```

### **Configuração Segura do PHP**

```ini
; php.ini - Configurações de segurança

; Ocultar informações do PHP
expose_php = Off

; Desabilitar funções perigosas
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

; Configurações de sessão
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = "Strict"
session.gc_maxlifetime = 3600
session.regenerate_id = 1

; Upload de arquivos
file_uploads = On
upload_max_filesize = 10M
max_file_uploads = 5
upload_tmp_dir = /tmp/uploads

; Logs de erro
log_errors = On
error_log = /var/log/php/error.log
display_errors = Off
display_startup_errors = Off

; Limites de recursos
max_execution_time = 30
max_input_time = 30
memory_limit = 256M
```

### **Configuração Segura do ActiveRecord**

```php
// config/security.php
class SecurityConfig {
    
    public static function configure() {
        // Configurar conexão com SSL
        ActiveRecord\Config::initialize(function($cfg) {
            $cfg->set_connections([
                'production' => [
                    'dsn' => 'mysql:host=localhost;dbname=app_prod;charset=utf8mb4',
                    'username' => $_ENV['DB_USER'],
                    'password' => $_ENV['DB_PASS'],
                    'options' => [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_STRINGIFY_FETCHES => false,
                        PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca.pem',
                        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
                    ]
                ]
            ]);
            
            // Não logar queries em produção
            $cfg->set_logging(false);
        });
        
        // Configurar headers de segurança
        self::setSecurityHeaders();
        
        // Configurar validação global
        self::setupGlobalValidation();
    }
    
    private static function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'');
    }
    
    private static function setupGlobalValidation() {
        // Validação global de entrada
        foreach ($_GET as $key => $value) {
            $_GET[$key] = InputSanitizer::sanitizeString($value);
        }
        
        foreach ($_POST as $key => $value) {
            if (is_string($value)) {
                $_POST[$key] = InputSanitizer::sanitizeString($value);
            }
        }
    }
}
```

---

## 📝 Auditoria e Logs

### **Sistema de Auditoria**

```php
class AuditLogger {
    
    public static function logAction($action, $model, $modelId, $changes = [], $userId = null) {
        $audit = new AuditLog();
        $audit->action = $action;
        $audit->model_type = get_class($model);
        $audit->model_id = $modelId;
        $audit->user_id = $userId ?? self::getCurrentUserId();
        $audit->changes = json_encode($changes);
        $audit->ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $audit->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $audit->created_at = new DateTime();
        
        return $audit->save();
    }
    
    public static function logCreate($model) {
        self::logAction('CREATE', $model, $model->id, $model->attributes());
    }
    
    public static function logUpdate($model, $oldAttributes) {
        $changes = [];
        foreach ($model->attributes() as $key => $value) {
            if (isset($oldAttributes[$key]) && $oldAttributes[$key] != $value) {
                $changes[$key] = [
                    'old' => $oldAttributes[$key],
                    'new' => $value
                ];
            }
        }
        
        if (!empty($changes)) {
            self::logAction('UPDATE', $model, $model->id, $changes);
        }
    }
    
    public static function logDelete($model) {
        self::logAction('DELETE', $model, $model->id, $model->attributes());
    }
    
    private static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}

// Trait para auditoria automática
trait Auditable {
    static $after_create = ['audit_create'];
    static $after_update = ['audit_update'];
    static $after_destroy = ['audit_delete'];
    
    private $originalAttributes = [];
    
    public function after_find() {
        $this->originalAttributes = $this->attributes();
    }
    
    public function audit_create() {
        AuditLogger::logCreate($this);
    }
    
    public function audit_update() {
        AuditLogger::logUpdate($this, $this->originalAttributes);
    }
    
    public function audit_delete() {
        AuditLogger::logDelete($this);
    }
}

// Model de auditoria
class AuditLog extends ActiveRecord\Model {
    static $validates_presence_of = [['action'], ['model_type'], ['model_id']];
    
    public static function getHistory($modelType, $modelId) {
        return self::find('all', [
            'conditions' => ['model_type = ? AND model_id = ?', $modelType, $modelId],
            'order' => 'created_at DESC'
        ]);
    }
    
    public static function getUserActions($userId, $days = 30) {
        $since = date('Y-m-d', strtotime("-$days days"));
        
        return self::find('all', [
            'conditions' => ['user_id = ? AND created_at >= ?', $userId, $since],
            'order' => 'created_at DESC'
        ]);
    }
}

// Uso
class User extends ActiveRecord\Model {
    use Auditable;
}
```

### **Log de Segurança**

```php
class SecurityLogger {
    private static $logFile = '/var/log/app/security.log';
    
    public static function logLogin($userId, $ipAddress) {
        self::log('LOGIN_SUCCESS', [
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    public static function logFailedLogin($email, $ipAddress) {
        self::log('LOGIN_FAILED', [
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    public static function logLogout($userId, $ipAddress) {
        self::log('LOGOUT', [
            'user_id' => $userId,
            'ip_address' => $ipAddress
        ]);
    }
    
    public static function logSuspiciousActivity($type, $details) {
        self::log('SUSPICIOUS_ACTIVITY', [
            'type' => $type,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'user_id' => $_SESSION['user_id'] ?? null
        ]);
    }
    
    public static function logPermissionDenied($userId, $resource) {
        self::log('PERMISSION_DENIED', [
            'user_id' => $userId,
            'resource' => $resource,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }
    
    private static function log($event, $data) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'data' => $data
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents(self::$logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Também salvar no banco para consultas
        $securityLog = new SecurityLog();
        $securityLog->event_type = $event;
        $securityLog->event_data = json_encode($data);
        $securityLog->created_at = new DateTime();
        $securityLog->save();
    }
    
    public static function analyzeSuspiciousPatterns() {
        $connection = ActiveRecord\Connection::instance();
        
        // Detectar múltiplas tentativas de login falhadas
        $suspiciousIPs = $connection->query("
            SELECT event_data->>'$.ip_address' as ip, COUNT(*) as attempts
            FROM security_logs 
            WHERE event_type = 'LOGIN_FAILED' 
              AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY event_data->>'$.ip_address'
            HAVING attempts >= 5
        ");
        
        foreach ($suspiciousIPs as $ip) {
            self::logSuspiciousActivity('BRUTE_FORCE_DETECTED', [
                'ip_address' => $ip['ip'],
                'attempts' => $ip['attempts']
            ]);
        }
        
        return $suspiciousIPs;
    }
}
```

---

## 💾 Backup e Recovery

### **Sistema de Backup Automatizado**

```php
class DatabaseBackup {
    private $config;
    private $backupPath;
    
    public function __construct($config, $backupPath = '/backups/') {
        $this->config = $config;
        $this->backupPath = $backupPath;
    }
    
    public function createBackup($compress = true) {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_{$this->config['database']}_{$timestamp}.sql";
        $filepath = $this->backupPath . $filename;
        
        // Comando mysqldump
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
            escapeshellarg($this->config['host']),
            escapeshellarg($this->config['username']),
            escapeshellarg($this->config['password']),
            escapeshellarg($this->config['database']),
            escapeshellarg($filepath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Erro ao criar backup: " . implode("\n", $output));
        }
        
        // Comprimir se solicitado
        if ($compress) {
            $compressedFile = $filepath . '.gz';
            exec("gzip $filepath", $output, $returnCode);
            
            if ($returnCode === 0) {
                $filepath = $compressedFile;
            }
        }
        
        // Verificar integridade
        $this->verifyBackup($filepath);
        
        // Log do backup
        $this->logBackup($filepath, filesize($filepath));
        
        return $filepath;
    }
    
    public function restoreBackup($backupFile) {
        if (!file_exists($backupFile)) {
            throw new Exception("Arquivo de backup não encontrado: $backupFile");
        }
        
        // Descomprimir se necessário
        if (pathinfo($backupFile, PATHINFO_EXTENSION) === 'gz') {
            $tempFile = tempnam(sys_get_temp_dir(), 'restore_');
            exec("gunzip -c $backupFile > $tempFile");
            $backupFile = $tempFile;
        }
        
        // Comando mysql restore
        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s',
            escapeshellarg($this->config['host']),
            escapeshellarg($this->config['username']),
            escapeshellarg($this->config['password']),
            escapeshellarg($this->config['database']),
            escapeshellarg($backupFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Erro ao restaurar backup: " . implode("\n", $output));
        }
        
        // Limpar arquivo temporário
        if (isset($tempFile)) {
            unlink($tempFile);
        }
        
        $this->logRestore($backupFile);
        
        return true;
    }
    
    private function verifyBackup($filepath) {
        // Verificar se arquivo não está vazio
        if (filesize($filepath) < 1000) {
            throw new Exception("Backup muito pequeno, possivelmente corrompido");
        }
        
        // Verificar estrutura básica do SQL
        $content = file_get_contents($filepath, false, null, 0, 1000);
        if (strpos($content, 'CREATE TABLE') === false) {
            throw new Exception("Backup não contém estrutura de tabelas");
        }
    }
    
    private function logBackup($filepath, $size) {
        $log = new BackupLog();
        $log->backup_file = basename($filepath);
        $log->file_size = $size;
        $log->backup_type = 'FULL';
        $log->status = 'SUCCESS';
        $log->created_at = new DateTime();
        $log->save();
    }
    
    private function logRestore($filepath) {
        $log = new BackupLog();
        $log->backup_file = basename($filepath);
        $log->backup_type = 'RESTORE';
        $log->status = 'SUCCESS';
        $log->created_at = new DateTime();
        $log->save();
    }
    
    public function cleanOldBackups($keepDays = 30) {
        $cutoffDate = date('Y-m-d', strtotime("-$keepDays days"));
        $pattern = $this->backupPath . "backup_*";
        
        $files = glob($pattern);
        $deleted = 0;
        
        foreach ($files as $file) {
            $fileDate = date('Y-m-d', filemtime($file));
            
            if ($fileDate < $cutoffDate) {
                unlink($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }
}

// Agendamento de backups
class BackupScheduler {
    
    public static function dailyBackup() {
        $config = [
            'host' => $_ENV['DB_HOST'],
            'username' => $_ENV['DB_USER'],
            'password' => $_ENV['DB_PASS'],
            'database' => $_ENV['DB_NAME']
        ];
        
        $backup = new DatabaseBackup($config);
        
        try {
            $file = $backup->createBackup(true);
            echo "Backup criado: $file\n";
            
            // Limpar backups antigos
            $deleted = $backup->cleanOldBackups(30);
            echo "Removidos $deleted backups antigos\n";
            
        } catch (Exception $e) {
            error_log("Erro no backup: " . $e->getMessage());
            
            // Notificar administradores
            mail('admin@exemplo.com', 'Erro no Backup', $e->getMessage());
        }
    }
}
```

---

## 🔍 Monitorização de Segurança

### **Monitor de Intrusões**

```php
class IntrusionDetection {
    
    public static function detectSQLInjection($input) {
        $patterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC)\b)/i',
            '/(\b(UNION|OR|AND)\b.*\b(SELECT|INSERT|UPDATE|DELETE)\b)/i',
            '/(\b(SCRIPT|JAVASCRIPT|VBSCRIPT)\b)/i',
            '/(--|#|\*|\/\*|\*\/)/i',
            '/(\b(XP_|SP_)\w+)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                self::alertSuspiciousActivity('SQL_INJECTION_ATTEMPT', [
                    'input' => $input,
                    'pattern' => $pattern,
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT']
                ]);
                return true;
            }
        }
        
        return false;
    }
    
    public static function detectXSS($input) {
        $patterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe[^>]*>.*?<\/iframe>/is'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                self::alertSuspiciousActivity('XSS_ATTEMPT', [
                    'input' => $input,
                    'pattern' => $pattern
                ]);
                return true;
            }
        }
        
        return false;
    }
    
    public static function detectBruteForce($identifier) {
        $attempts = apcu_fetch("login_attempts_$identifier") ?: 0;
        
        if ($attempts >= 10) {
            self::alertSuspiciousActivity('BRUTE_FORCE_DETECTED', [
                'identifier' => $identifier,
                'attempts' => $attempts,
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            
            // Bloquear IP temporariamente
            self::blockIP($_SERVER['REMOTE_ADDR'], 3600); // 1 hora
            
            return true;
        }
        
        return false;
    }
    
    public static function detectAnomalousActivity($userId) {
        $connection = ActiveRecord\Connection::instance();
        
        // Verificar logins de IPs diferentes em pouco tempo
        $recentLogins = $connection->query("
            SELECT event_data->>'$.ip_address' as ip
            FROM security_logs 
            WHERE event_type = 'LOGIN_SUCCESS' 
              AND event_data->>'$.user_id' = ?
              AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY event_data->>'$.ip_address'
        ", [$userId]);
        
        if (count($recentLogins) > 3) {
            self::alertSuspiciousActivity('MULTIPLE_IP_LOGIN', [
                'user_id' => $userId,
                'ip_count' => count($recentLogins),
                'ips' => array_column($recentLogins, 'ip')
            ]);
        }
        
        // Verificar atividade fora do horário normal
        $hour = date('H');
        if ($hour < 6 || $hour > 23) {
            self::alertSuspiciousActivity('OFF_HOURS_ACTIVITY', [
                'user_id' => $userId,
                'hour' => $hour,
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
        }
    }
    
    private static function alertSuspiciousActivity($type, $details) {
        SecurityLogger::logSuspiciousActivity($type, $details);
        
        // Enviar alerta para administradores
        $alert = new SecurityAlert();
        $alert->alert_type = $type;
        $alert->alert_data = json_encode($details);
        $alert->severity = self::getSeverity($type);
        $alert->created_at = new DateTime();
        $alert->save();
        
        // Notificação imediata para alertas críticos
        if ($alert->severity === 'CRITICAL') {
            self::sendImmediateAlert($type, $details);
        }
    }
    
    private static function getSeverity($type) {
        $severities = [
            'SQL_INJECTION_ATTEMPT' => 'CRITICAL',
            'XSS_ATTEMPT' => 'HIGH',
            'BRUTE_FORCE_DETECTED' => 'HIGH',
            'MULTIPLE_IP_LOGIN' => 'MEDIUM',
            'OFF_HOURS_ACTIVITY' => 'LOW'
        ];
        
        return $severities[$type] ?? 'LOW';
    }
    
    private static function sendImmediateAlert($type, $details) {
        $message = "ALERTA DE SEGURANÇA: $type\n";
        $message .= "Detalhes: " . json_encode($details, JSON_PRETTY_PRINT) . "\n";
        $message .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
        
        // Enviar email
        mail('security@exemplo.com', "Alerta de Segurança: $type", $message);
        
        // Ou enviar via webhook/Slack
        // self::sendSlackAlert($message);
    }
    
    private static function blockIP($ip, $duration) {
        $blockedIP = new BlockedIP();
        $blockedIP->ip_address = $ip;
        $blockedIP->blocked_until = date('Y-m-d H:i:s', time() + $duration);
        $blockedIP->reason = 'Suspicious activity detected';
        $blockedIP->save();
    }
}

// Middleware de segurança
class SecurityMiddleware {
    
    public static function checkRequest() {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // Verificar se IP está bloqueado
        if (self::isIPBlocked($ip)) {
            http_response_code(403);
            die('Access denied');
        }
        
        // Verificar rate limiting
        if (self::isRateLimited($ip)) {
            http_response_code(429);
            die('Too many requests');
        }
        
        // Verificar entrada por SQL injection
        foreach ($_GET as $key => $value) {
            if (IntrusionDetection::detectSQLInjection($value)) {
                http_response_code(400);
                die('Malicious input detected');
            }
        }
        
        foreach ($_POST as $key => $value) {
            if (is_string($value) && IntrusionDetection::detectSQLInjection($value)) {
                http_response_code(400);
                die('Malicious input detected');
            }
        }
    }
    
    private static function isIPBlocked($ip) {
        return BlockedIP::exists([
            'conditions' => [
                'ip_address = ? AND blocked_until > NOW()', 
                $ip
            ]
        ]);
    }
    
    private static function isRateLimited($ip) {
        $key = "rate_limit_$ip";
        $requests = apcu_fetch($key) ?: 0;
        
        if ($requests >= 100) { // 100 requests por minuto
            return true;
        }
        
        apcu_store($key, $requests + 1, 60);
        return false;
    }
}
```

---

## 🛡️ Hardening do MySQL

### **Script de Hardening**

```sql
-- Hardening do MySQL

-- 1. Remover usuários anônimos
DELETE FROM mysql.user WHERE User='';

-- 2. Remover banco de dados de teste
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';

-- 3. Desabilitar root remoto
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

-- 4. Criar usuário para aplicação com privilégios mínimos
CREATE USER 'app_user'@'localhost' IDENTIFIED BY 'senha_forte_aqui';
GRANT SELECT, INSERT, UPDATE, DELETE ON app_database.* TO 'app_user'@'localhost';

-- 5. Criar usuário apenas para leitura
CREATE USER 'readonly_user'@'localhost' IDENTIFIED BY 'senha_forte_readonly';
GRANT SELECT ON app_database.* TO 'readonly_user'@'localhost';

-- 6. Configurar política de senhas
SET GLOBAL validate_password_policy = 'STRONG';
SET GLOBAL validate_password_length = 12;

-- 7. Configurar timeouts
SET GLOBAL wait_timeout = 600;
SET GLOBAL interactive_timeout = 600;

-- 8. Aplicar mudanças
FLUSH PRIVILEGES;
```

### **Monitoramento MySQL**

```php
class MySQLSecurityMonitor {
    
    public static function checkSecurity() {
        $connection = ActiveRecord\Connection::instance();
        $issues = [];
        
        // Verificar usuários sem senha
        $emptyPasswords = $connection->query("
            SELECT User, Host FROM mysql.user WHERE Password = ''
        ");
        
        if (!empty($emptyPasswords)) {
            $issues[] = "Usuários sem senha encontrados: " . count($emptyPasswords);
        }
        
        // Verificar privilégios excessivos
        $superUsers = $connection->query("
            SELECT User, Host FROM mysql.user WHERE Super_priv = 'Y'
        ");
        
        foreach ($superUsers as $user) {
            if ($user['User'] !== 'root') {
                $issues[] = "Usuário com privilégios de super admin: {$user['User']}@{$user['Host']}";
            }
        }
        
        // Verificar configurações SSL
        $sslConfig = $connection->query("SHOW VARIABLES LIKE 'have_ssl'")[0];
        if ($sslConfig['Value'] !== 'YES') {
            $issues[] = "SSL não está habilitado";
        }
        
        // Verificar log de consultas lentas
        $slowLog = $connection->query("SHOW VARIABLES LIKE 'slow_query_log'")[0];
        if ($slowLog['Value'] !== 'ON') {
            $issues[] = "Log de consultas lentas não está ativo";
        }
        
        return $issues;
    }
    
    public static function generateSecurityReport() {
        $issues = self::checkSecurity();
        
        $report = "=== Relatório de Segurança MySQL ===\n";
        $report .= "Data: " . date('Y-m-d H:i:s') . "\n\n";
        
        if (empty($issues)) {
            $report .= "✅ Nenhum problema de segurança detectado.\n";
        } else {
            $report .= "⚠️  Problemas detectados:\n";
            foreach ($issues as $issue) {
                $report .= "- $issue\n";
            }
        }
        
        return $report;
    }
}
```

---

## 💡 Resumo de Boas Práticas

### ✅ **Fazer**
- Usar sempre prepared statements
- Validar e sanitizar todas as entradas
- Implementar autenticação forte
- Criptografar dados sensíveis
- Fazer auditoria de todas as ações
- Manter backups seguros e testados
- Monitorar atividades suspeitas
- Configurar SSL/TLS
- Usar senhas fortes e rotacionamento
- Implementar rate limiting

### ❌ **Evitar**
- Concatenar strings em queries SQL
- Armazenar senhas em texto plano
- Usar conexões não criptografadas
- Dar privilégios excessivos
- Ignorar logs de segurança
- Deixar dados sensíveis em logs
- Usar configurações padrão
- Expor informações do sistema
- Negligenciar validação de entrada
- Não fazer backup regular

### 🎯 **Checklist de Segurança**
- [ ] Prepared statements implementados
- [ ] Validação de entrada ativa
- [ ] Sistema de autenticação robusto
- [ ] Criptografia de dados sensíveis
- [ ] Logs de auditoria funcionando
- [ ] Backups automáticos configurados
- [ ] Monitoramento de segurança ativo
- [ ] SSL/TLS configurado
- [ ] Hardening do MySQL aplicado
- [ ] Rate limiting implementado
- [ ] Headers de segurança configurados
- [ ] Política de senhas forte

---

**⚠️ Importante:** A segurança é um processo contínuo. Mantenha-se atualizado sobre novas vulnerabilidades e pratique revisões regulares de segurança!
