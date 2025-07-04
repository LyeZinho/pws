# Guia de Deploy e Configuração de Produção - MySQL, PHP & ActiveRecord

## Índice
- [Preparação para Produção](#preparação-para-produção)
- [Configuração de Servidor](#configuração-de-servidor)
- [Deploy Automatizado](#deploy-automatizado)
- [Configuração de MySQL](#configuração-de-mysql)
- [PHP em Produção](#php-em-produção)
- [ActiveRecord em Produção](#activerecord-em-produção)
- [Load Balancing](#load-balancing)
- [Monitoramento](#monitoramento)
- [Rollback e Recovery](#rollback-e-recovery)
- [Checklist de Deploy](#checklist-de-deploy)

## Preparação para Produção

### 1. Estrutura de Ambientes

```
environments/
├── development/
│   ├── config.php
│   ├── database.php
│   └── .env
├── staging/
│   ├── config.php
│   ├── database.php
│   └── .env
└── production/
    ├── config.php
    ├── database.php
    └── .env
```

### 2. Configuração de Ambiente

```php
<?php
// config/environment.php
class Environment {
    private static $configs = [];
    
    public static function load($env = null) {
        $env = $env ?: self::detectEnvironment();
        
        if (!isset(self::$configs[$env])) {
            $configFile = __DIR__ . "/environments/{$env}/config.php";
            
            if (!file_exists($configFile)) {
                throw new Exception("Configuration file not found for environment: {$env}");
            }
            
            self::$configs[$env] = require $configFile;
        }
        
        return self::$configs[$env];
    }
    
    private static function detectEnvironment() {
        // Detectar ambiente por variável de servidor
        if (isset($_ENV['APP_ENV'])) {
            return $_ENV['APP_ENV'];
        }
        
        // Detectar por hostname
        $hostname = gethostname();
        if (strpos($hostname, 'prod') !== false) {
            return 'production';
        } elseif (strpos($hostname, 'stage') !== false) {
            return 'staging';
        }
        
        return 'development';
    }
    
    public static function isProduction() {
        return self::detectEnvironment() === 'production';
    }
    
    public static function isDevelopment() {
        return self::detectEnvironment() === 'development';
    }
}

// environments/production/config.php
return [
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'name' => $_ENV['DB_NAME'] ?? 'production_db',
        'user' => $_ENV['DB_USER'] ?? 'prod_user',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    ],
    
    'logging' => [
        'level' => 'error',
        'file' => '/var/log/app/application.log',
        'max_files' => 30,
        'rotation' => 'daily'
    ],
    
    'cache' => [
        'driver' => 'redis',
        'host' => $_ENV['REDIS_HOST'] ?? 'localhost',
        'port' => $_ENV['REDIS_PORT'] ?? 6379,
        'ttl' => 3600
    ],
    
    'security' => [
        'csrf_protection' => true,
        'session_secure' => true,
        'session_httponly' => true,
        'session_samesite' => 'Strict',
        'force_https' => true
    ],
    
    'performance' => [
        'gzip_compression' => true,
        'opcache_enabled' => true,
        'query_cache' => true,
        'static_cache_ttl' => 86400
    ]
];
?>
```

### 3. Script de Deploy

```php
<?php
// deploy.php
class Deployer {
    private $config;
    private $logger;
    
    public function __construct($config) {
        $this->config = $config;
        $this->logger = new Logger($config['log_file']);
    }
    
    public function deploy($version = null) {
        $this->logger->info("Iniciando deploy para produção");
        
        try {
            // 1. Validações pré-deploy
            $this->preDeployChecks();
            
            // 2. Backup do estado atual
            $this->createBackup();
            
            // 3. Atualizar código
            $this->updateCode($version);
            
            // 4. Executar migrações
            $this->runMigrations();
            
            // 5. Otimizações
            $this->optimizeApplication();
            
            // 6. Testes pós-deploy
            $this->postDeployTests();
            
            // 7. Warmup
            $this->warmupApplication();
            
            $this->logger->info("Deploy concluído com sucesso");
            
        } catch (Exception $e) {
            $this->logger->error("Erro durante deploy: " . $e->getMessage());
            $this->rollback();
            throw $e;
        }
    }
    
    private function preDeployChecks() {
        $this->logger->info("Executando verificações pré-deploy");
        
        // Verificar espaço em disco
        $freeSpace = disk_free_space('/');
        if ($freeSpace < 1024 * 1024 * 1024) { // 1GB
            throw new Exception("Espaço em disco insuficiente");
        }
        
        // Verificar conectividade com banco
        $this->checkDatabaseConnection();
        
        // Verificar dependências
        $this->checkDependencies();
        
        // Verificar permissões
        $this->checkPermissions();
    }
    
    private function checkDatabaseConnection() {
        try {
            $pdo = new PDO(
                "mysql:host={$this->config['db']['host']};dbname={$this->config['db']['name']}",
                $this->config['db']['user'],
                $this->config['db']['password']
            );
            $pdo->query('SELECT 1');
        } catch (Exception $e) {
            throw new Exception("Falha na conexão com banco de dados: " . $e->getMessage());
        }
    }
    
    private function createBackup() {
        $this->logger->info("Criando backup");
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupDir = $this->config['backup_dir'] . "/deploy_backup_{$timestamp}";
        
        // Backup do código
        $this->executeCommand("cp -r {$this->config['app_dir']} {$backupDir}");
        
        // Backup do banco
        $dbBackupFile = "{$backupDir}/database.sql";
        $this->executeCommand(
            "mysqldump --host={$this->config['db']['host']} " .
            "--user={$this->config['db']['user']} " .
            "--password={$this->config['db']['password']} " .
            "{$this->config['db']['name']} > {$dbBackupFile}"
        );
        
        $this->config['backup_path'] = $backupDir;
    }
    
    private function updateCode($version) {
        $this->logger->info("Atualizando código");
        
        // Modo manutenção
        $this->enableMaintenanceMode();
        
        try {
            if ($version) {
                $this->executeCommand("git checkout {$version}");
            } else {
                $this->executeCommand("git pull origin main");
            }
            
            // Instalar/atualizar dependências
            $this->executeCommand("composer install --no-dev --optimize-autoloader");
            
            // Limpar cache
            $this->clearCache();
            
        } finally {
            $this->disableMaintenanceMode();
        }
    }
    
    private function runMigrations() {
        $this->logger->info("Executando migrações");
        
        $migrationRunner = new MigrationRunner($this->config);
        $migrationRunner->runPendingMigrations();
    }
    
    private function optimizeApplication() {
        $this->logger->info("Otimizando aplicação");
        
        // OPcache reset
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Precompile templates se usando Twig/Smarty
        $this->precompileTemplates();
        
        // Gerar cache de configuração
        $this->generateConfigCache();
        
        // Otimizar autoloader
        $this->executeCommand("composer dump-autoload --optimize --no-dev");
    }
    
    private function postDeployTests() {
        $this->logger->info("Executando testes pós-deploy");
        
        // Smoke tests
        $this->runSmokeTests();
        
        // Health check
        $this->healthCheck();
    }
    
    private function runSmokeTests() {
        $endpoints = $this->config['smoke_test_endpoints'] ?? [];
        
        foreach ($endpoints as $endpoint) {
            $response = $this->makeHttpRequest($endpoint);
            if ($response['status'] !== 200) {
                throw new Exception("Smoke test falhou para {$endpoint}: {$response['status']}");
            }
        }
    }
    
    private function healthCheck() {
        // Verificar conectividade com serviços essenciais
        $this->checkDatabaseConnection();
        
        // Verificar cache
        if ($this->config['cache']['driver'] === 'redis') {
            $this->checkRedisConnection();
        }
        
        // Verificar logs
        $this->checkLogWritability();
    }
    
    private function warmupApplication() {
        $this->logger->info("Aquecendo aplicação");
        
        // Fazer requests para páginas principais
        $warmupUrls = $this->config['warmup_urls'] ?? [];
        
        foreach ($warmupUrls as $url) {
            $this->makeHttpRequest($url);
        }
    }
    
    private function rollback() {
        $this->logger->info("Executando rollback");
        
        if (isset($this->config['backup_path'])) {
            // Restaurar código
            $this->executeCommand("rm -rf {$this->config['app_dir']}/*");
            $this->executeCommand("cp -r {$this->config['backup_path']}/* {$this->config['app_dir']}/");
            
            // Restaurar banco se necessário
            if ($this->config['rollback_database']) {
                $dbBackupFile = $this->config['backup_path'] . "/database.sql";
                $this->executeCommand(
                    "mysql --host={$this->config['db']['host']} " .
                    "--user={$this->config['db']['user']} " .
                    "--password={$this->config['db']['password']} " .
                    "{$this->config['db']['name']} < {$dbBackupFile}"
                );
            }
        }
    }
    
    private function executeCommand($command) {
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        $this->logger->debug("Command: {$command}");
        $this->logger->debug("Output: " . implode("\n", $output));
        
        if ($returnCode !== 0) {
            throw new Exception("Command failed: {$command}\nOutput: " . implode("\n", $output));
        }
        
        return $output;
    }
    
    private function enableMaintenanceMode() {
        file_put_contents($this->config['app_dir'] . '/maintenance.lock', time());
    }
    
    private function disableMaintenanceMode() {
        $lockFile = $this->config['app_dir'] . '/maintenance.lock';
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }
}
?>
```

## Configuração de MySQL para Produção

### 1. Configuração my.cnf Otimizada

```ini
# /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]

# Configurações básicas
user = mysql
pid-file = /var/run/mysqld/mysqld.pid
socket = /var/run/mysqld/mysqld.sock
port = 3306
datadir = /var/lib/mysql

# Networking
bind-address = 127.0.0.1
max_connections = 200
max_connect_errors = 100000
connect_timeout = 10
wait_timeout = 28800
interactive_timeout = 28800

# Buffer sizes
innodb_buffer_pool_size = 2G  # 70-80% da RAM disponível
innodb_buffer_pool_instances = 8
innodb_log_file_size = 256M
innodb_log_buffer_size = 16M
innodb_flush_log_at_trx_commit = 2

# Query cache
query_cache_type = 1
query_cache_size = 128M
query_cache_limit = 2M

# Thread cache
thread_cache_size = 50
thread_stack = 256K

# Table cache
table_open_cache = 4000
table_definition_cache = 1000

# Temp tables
tmp_table_size = 256M
max_heap_table_size = 256M

# Logging
log_error = /var/log/mysql/error.log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
log_queries_not_using_indexes = 1

# Security
local_infile = 0
sql_mode = STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO

# Character set
character_set_server = utf8mb4
collation_server = utf8mb4_unicode_ci

# InnoDB settings
innodb_file_per_table = 1
innodb_stats_on_metadata = 0
innodb_read_io_threads = 4
innodb_write_io_threads = 4
innodb_io_capacity = 1000
innodb_flush_method = O_DIRECT
```

### 2. Script de Setup do MySQL

```bash
#!/bin/bash
# setup_mysql_production.sh

echo "Configurando MySQL para produção..."

# Backup da configuração atual
cp /etc/mysql/mysql.conf.d/mysqld.cnf /etc/mysql/mysql.conf.d/mysqld.cnf.backup

# Aplicar nova configuração
cp mysql-production.cnf /etc/mysql/mysql.conf.d/mysqld.cnf

# Criar usuários de produção
mysql -u root -p << EOF
-- Usuário da aplicação (apenas DML)
CREATE USER 'app_user'@'localhost' IDENTIFIED BY 'senha_super_segura';
GRANT SELECT, INSERT, UPDATE, DELETE ON production_db.* TO 'app_user'@'localhost';

-- Usuário de backup (apenas leitura)
CREATE USER 'backup_user'@'localhost' IDENTIFIED BY 'senha_backup_segura';
GRANT SELECT, LOCK TABLES, SHOW VIEW, EVENT, TRIGGER ON production_db.* TO 'backup_user'@'localhost';

-- Usuário de monitoramento
CREATE USER 'monitor_user'@'localhost' IDENTIFIED BY 'senha_monitor_segura';
GRANT SELECT ON performance_schema.* TO 'monitor_user'@'localhost';
GRANT SELECT ON information_schema.* TO 'monitor_user'@'localhost';
GRANT SHOW DATABASES, PROCESS ON *.* TO 'monitor_user'@'localhost';

FLUSH PRIVILEGES;
EOF

# Reiniciar MySQL
systemctl restart mysql

# Verificar status
systemctl status mysql

echo "MySQL configurado para produção!"
```

## Configuração do PHP para Produção

### 1. php.ini Otimizado

```ini
; php.ini production settings

; Error handling
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT

; Security
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source

; Memory & Performance
memory_limit = 256M
max_execution_time = 30
max_input_time = 60
post_max_size = 32M
upload_max_filesize = 32M
max_file_uploads = 20

; OPcache
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.max_wasted_percentage = 5
opcache.use_cwd = 1
opcache.validate_timestamps = 0
opcache.revalidate_freq = 0
opcache.save_comments = 1
opcache.enable_file_override = 1

; Session
session.use_strict_mode = 1
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = "Strict"
session.gc_maxlifetime = 3600

; Date
date.timezone = "America/Sao_Paulo"

; Realpath cache
realpath_cache_size = 4096K
realpath_cache_ttl = 600
```

### 2. Configuração do ActiveRecord para Produção

```php
<?php
// config/activerecord_production.php
ActiveRecord\Config::initialize(function($cfg) {
    // Conexão com pool de conexões
    $cfg->set_connections([
        'production' => [
            'connection' => "mysql://app_user:senha@localhost/production_db",
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        ],
        'production_read' => [
            'connection' => "mysql://app_user:senha@read-replica/production_db",
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        ]
    ]);
    
    $cfg->set_default_connection('production');
    
    // Cache de modelos
    $cfg->set_cache('redis://localhost:6379', [
        'expire' => 3600,
        'namespace' => 'ar_cache'
    ]);
    
    // Logging apenas para erros
    $cfg->set_logging(false);
    
    // Disable query log in production
    ActiveRecord\Connection::$query_log = false;
});

// Configuração de cache para queries específicas
class CachedModel extends ActiveRecord\Model {
    
    public static function find_cached($id, $ttl = 3600) {
        $cache_key = static::class . ":{$id}";
        
        $cached = Cache::get($cache_key);
        if ($cached !== null) {
            return $cached;
        }
        
        $model = static::find($id);
        if ($model) {
            Cache::set($cache_key, $model, $ttl);
        }
        
        return $model;
    }
    
    public function save($validate = true) {
        $result = parent::save($validate);
        
        if ($result) {
            // Invalidar cache
            $cache_key = static::class . ":{$this->id}";
            Cache::delete($cache_key);
        }
        
        return $result;
    }
}
?>
```

## Load Balancing e Alta Disponibilidade

### 1. Configuração do HAProxy

```
# /etc/haproxy/haproxy.cfg
global
    daemon
    chroot /var/lib/haproxy
    stats socket /run/haproxy/admin.sock mode 660 level admin
    stats timeout 30s
    user haproxy
    group haproxy

defaults
    mode http
    timeout connect 5000ms
    timeout client 50000ms
    timeout server 50000ms
    option httplog
    option dontlognull
    option redispatch
    retries 3

frontend web_frontend
    bind *:80
    bind *:443 ssl crt /etc/ssl/certs/app.pem
    redirect scheme https if !{ ssl_fc }
    
    # Health check endpoint
    acl health_check path_beg /health
    use_backend health_backend if health_check
    
    default_backend web_servers

backend web_servers
    balance roundrobin
    option httpchk GET /health
    
    server web1 192.168.1.10:80 check
    server web2 192.168.1.11:80 check
    server web3 192.168.1.12:80 check

backend health_backend
    server health 127.0.0.1:8080

listen stats
    bind *:8404
    stats enable
    stats uri /stats
    stats refresh 30s
```

### 2. Health Check Endpoint

```php
<?php
// health.php
class HealthCheck {
    
    public function check() {
        $status = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'checks' => []
        ];
        
        // Database check
        try {
            $pdo = new PDO(/* configuração */);
            $pdo->query('SELECT 1');
            $status['checks']['database'] = 'ok';
        } catch (Exception $e) {
            $status['checks']['database'] = 'error';
            $status['status'] = 'error';
        }
        
        // Cache check
        try {
            $redis = new Redis();
            $redis->connect('localhost', 6379);
            $redis->ping();
            $status['checks']['cache'] = 'ok';
        } catch (Exception $e) {
            $status['checks']['cache'] = 'error';
            $status['status'] = 'degraded';
        }
        
        // Disk space check
        $freeSpace = disk_free_space('/');
        $totalSpace = disk_total_space('/');
        $usagePercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;
        
        if ($usagePercent > 90) {
            $status['checks']['disk'] = 'critical';
            $status['status'] = 'error';
        } elseif ($usagePercent > 80) {
            $status['checks']['disk'] = 'warning';
            if ($status['status'] === 'ok') {
                $status['status'] = 'degraded';
            }
        } else {
            $status['checks']['disk'] = 'ok';
        }
        
        $status['checks']['disk_usage'] = $usagePercent;
        
        http_response_code($status['status'] === 'ok' ? 200 : 503);
        header('Content-Type: application/json');
        echo json_encode($status);
    }
}

$healthCheck = new HealthCheck();
$healthCheck->check();
?>
```

## Checklist de Deploy

### ✅ Pré-Deploy
- [ ] Código testado em staging
- [ ] Backup do banco de dados criado
- [ ] Backup do código atual criado
- [ ] Verificação de espaço em disco
- [ ] Notificação da equipe sobre deploy
- [ ] Verificação de dependências

### ✅ Deploy
- [ ] Modo manutenção ativado
- [ ] Código atualizado
- [ ] Dependências instaladas
- [ ] Migrações executadas
- [ ] Cache limpo
- [ ] Configurações atualizadas
- [ ] Modo manutenção desativado

### ✅ Pós-Deploy
- [ ] Smoke tests executados
- [ ] Health checks passando
- [ ] Logs verificados
- [ ] Performance verificada
- [ ] Rollback plan confirmado
- [ ] Monitoring ativo
- [ ] Equipe notificada sobre sucesso

### ✅ Monitoramento Contínuo
- [ ] Alertas configurados
- [ ] Dashboards atualizados
- [ ] Backup schedule verificado
- [ ] Log rotation configurado
- [ ] Security patches aplicados

---

**Importante**: Sempre teste procedures de deploy em ambiente de staging antes de aplicar em produção. Mantenha documentação atualizada e tenha sempre um plano de rollback pronto.
