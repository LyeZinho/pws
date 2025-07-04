# Guia de Monitoramento - MySQL, PHP & ActiveRecord

## Índice
- [Monitoramento de Performance](#monitoramento-de-performance)
- [Métricas Importantes](#métricas-importantes)
- [Alertas e Notificações](#alertas-e-notificações)
- [Ferramentas de Monitoramento](#ferramentas-de-monitoramento)
- [Dashboards](#dashboards)
- [Logs e Auditoria](#logs-e-auditoria)
- [Monitoramento de ActiveRecord](#monitoramento-de-activerecord)
- [Scripts de Monitoramento](#scripts-de-monitoramento)
- [Troubleshooting](#troubleshooting)

## Monitoramento de Performance

### 1. Monitoramento de MySQL

```sql
-- Status geral do servidor
SHOW STATUS;

-- Variáveis de configuração
SHOW VARIABLES;

-- Processos em execução
SHOW PROCESSLIST;

-- Status das engines
SHOW ENGINE INNODB STATUS;

-- Informações sobre queries lentas
SELECT * FROM information_schema.PROCESSLIST 
WHERE TIME > 10 AND COMMAND = 'Query';

-- Top queries por tempo de execução
SELECT 
    digest_text,
    count_star,
    avg_timer_wait/1000000000 as avg_time_sec,
    sum_timer_wait/1000000000 as total_time_sec
FROM performance_schema.events_statements_summary_by_digest 
ORDER BY avg_timer_wait DESC 
LIMIT 10;

-- Uso de índices
SELECT 
    table_schema,
    table_name,
    index_name,
    cardinality,
    column_name
FROM information_schema.statistics 
WHERE table_schema = 'minha_app'
ORDER BY cardinality DESC;

-- Fragmentação de tabelas
SELECT 
    table_name,
    engine,
    table_rows,
    data_length,
    index_length,
    data_free,
    (data_free / (data_length + index_length)) * 100 as fragmentation_percent
FROM information_schema.tables 
WHERE table_schema = 'minha_app' 
AND data_free > 0;
```

### 2. Sistema de Monitoramento PHP

```php
<?php
class MySQLMonitor {
    private $connection;
    private $config;
    private $alerts;
    
    public function __construct($config) {
        $this->config = $config;
        $this->connection = new PDO(
            "mysql:host={$config['host']};dbname={$config['database']}", 
            $config['user'], 
            $config['password']
        );
        $this->alerts = [];
    }
    
    public function collectMetrics() {
        $metrics = [
            'timestamp' => date('Y-m-d H:i:s'),
            'connections' => $this->getConnectionMetrics(),
            'performance' => $this->getPerformanceMetrics(),
            'storage' => $this->getStorageMetrics(),
            'replication' => $this->getReplicationMetrics(),
            'queries' => $this->getQueryMetrics(),
            'locks' => $this->getLockMetrics()
        ];
        
        $this->checkThresholds($metrics);
        $this->storeMetrics($metrics);
        
        return $metrics;
    }
    
    private function getConnectionMetrics() {
        $stmt = $this->connection->query("SHOW STATUS LIKE 'Connections'");
        $connections = $stmt->fetch()['Value'];
        
        $stmt = $this->connection->query("SHOW STATUS LIKE 'Threads_connected'");
        $connected = $stmt->fetch()['Value'];
        
        $stmt = $this->connection->query("SHOW VARIABLES LIKE 'max_connections'");
        $maxConnections = $stmt->fetch()['Value'];
        
        return [
            'total_connections' => $connections,
            'current_connections' => $connected,
            'max_connections' => $maxConnections,
            'connection_usage_percent' => ($connected / $maxConnections) * 100
        ];
    }
    
    private function getPerformanceMetrics() {
        $queries = [];
        
        // Query cache
        $stmt = $this->connection->query("SHOW STATUS LIKE 'Qcache%'");
        while ($row = $stmt->fetch()) {
            $queries['query_cache'][$row['Variable_name']] = $row['Value'];
        }
        
        // Slow queries
        $stmt = $this->connection->query("SHOW STATUS LIKE 'Slow_queries'");
        $queries['slow_queries'] = $stmt->fetch()['Value'];
        
        // Uptime
        $stmt = $this->connection->query("SHOW STATUS LIKE 'Uptime'");
        $queries['uptime'] = $stmt->fetch()['Value'];
        
        // Queries per second
        $stmt = $this->connection->query("SHOW STATUS LIKE 'Questions'");
        $totalQueries = $stmt->fetch()['Value'];
        $queries['queries_per_second'] = $totalQueries / $queries['uptime'];
        
        return $queries;
    }
    
    private function getStorageMetrics() {
        $storage = [];
        
        // Database size
        $stmt = $this->connection->query("
            SELECT 
                table_schema,
                SUM(data_length + index_length) as size_bytes,
                SUM(data_free) as free_bytes
            FROM information_schema.tables 
            WHERE table_schema = '{$this->config['database']}'
            GROUP BY table_schema
        ");
        
        if ($row = $stmt->fetch()) {
            $storage['database_size_mb'] = $row['size_bytes'] / 1024 / 1024;
            $storage['free_space_mb'] = $row['free_bytes'] / 1024 / 1024;
        }
        
        // InnoDB buffer pool
        $stmt = $this->connection->query("SHOW STATUS LIKE 'Innodb_buffer_pool%'");
        while ($row = $stmt->fetch()) {
            $storage['innodb'][$row['Variable_name']] = $row['Value'];
        }
        
        return $storage;
    }
    
    private function getLockMetrics() {
        $locks = [];
        
        // Table locks
        $stmt = $this->connection->query("SHOW STATUS LIKE 'Table_locks%'");
        while ($row = $stmt->fetch()) {
            $locks[$row['Variable_name']] = $row['Value'];
        }
        
        // Current locks
        $stmt = $this->connection->query("
            SELECT 
                r.trx_id waiting_trx_id,
                r.trx_mysql_thread_id waiting_thread,
                r.trx_query waiting_query,
                b.trx_id blocking_trx_id,
                b.trx_mysql_thread_id blocking_thread,
                b.trx_query blocking_query
            FROM information_schema.innodb_lock_waits w
            INNER JOIN information_schema.innodb_trx b ON b.trx_id = w.blocking_trx_id
            INNER JOIN information_schema.innodb_trx r ON r.trx_id = w.requesting_trx_id
        ");
        
        $locks['current_lock_waits'] = $stmt->fetchAll();
        
        return $locks;
    }
    
    private function checkThresholds($metrics) {
        $thresholds = $this->config['thresholds'];
        
        // Connection usage
        if ($metrics['connections']['connection_usage_percent'] > $thresholds['connection_usage']) {
            $this->addAlert('high_connection_usage', 
                "Uso de conexões alto: {$metrics['connections']['connection_usage_percent']}%");
        }
        
        // Slow queries
        if ($metrics['performance']['slow_queries'] > $thresholds['slow_queries']) {
            $this->addAlert('slow_queries', 
                "Muitas queries lentas: {$metrics['performance']['slow_queries']}");
        }
        
        // Database size
        if (isset($metrics['storage']['database_size_mb']) && 
            $metrics['storage']['database_size_mb'] > $thresholds['database_size_mb']) {
            $this->addAlert('database_size', 
                "Tamanho do banco alto: {$metrics['storage']['database_size_mb']} MB");
        }
        
        // Lock waits
        if (count($metrics['locks']['current_lock_waits']) > 0) {
            $this->addAlert('lock_waits', 
                "Locks detectados: " . count($metrics['locks']['current_lock_waits']) . " waits");
        }
    }
    
    private function addAlert($type, $message) {
        $this->alerts[] = [
            'type' => $type,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'severity' => $this->getAlertSeverity($type)
        ];
    }
    
    private function getAlertSeverity($type) {
        $severities = [
            'high_connection_usage' => 'warning',
            'slow_queries' => 'warning',
            'database_size' => 'info',
            'lock_waits' => 'critical'
        ];
        
        return $severities[$type] ?? 'info';
    }
    
    public function getAlerts() {
        return $this->alerts;
    }
    
    public function sendAlerts() {
        foreach ($this->alerts as $alert) {
            if ($alert['severity'] === 'critical') {
                $this->sendImmediateNotification($alert);
            } else {
                $this->queueNotification($alert);
            }
        }
    }
    
    private function storeMetrics($metrics) {
        // Armazenar métricas em banco ou arquivo
        $logFile = $this->config['metrics_log'] ?? './metrics.log';
        file_put_contents($logFile, json_encode($metrics) . "\n", FILE_APPEND);
        
        // Opcional: Enviar para sistema de métricas externo
        if (isset($this->config['external_metrics'])) {
            $this->sendToExternalSystem($metrics);
        }
    }
}
?>
```

### 3. Monitor de ActiveRecord

```php
<?php
class ActiveRecordMonitor {
    private static $queryLog = [];
    private static $performanceData = [];
    
    public static function startMonitoring() {
        // Hook para capturar queries
        ActiveRecord\Connection::$query_log = true;
        
        // Hook personalizado para timing
        ActiveRecord\Model::$before_save = function($model) {
            $model->__start_time = microtime(true);
        };
        
        ActiveRecord\Model::$after_save = function($model) {
            if (isset($model->__start_time)) {
                $duration = microtime(true) - $model->__start_time;
                self::logPerformance(get_class($model), 'save', $duration);
            }
        };
    }
    
    public static function logPerformance($model, $operation, $duration) {
        self::$performanceData[] = [
            'timestamp' => microtime(true),
            'model' => $model,
            'operation' => $operation,
            'duration' => $duration,
            'memory_usage' => memory_get_usage(true)
        ];
    }
    
    public static function getSlowOperations($threshold = 1.0) {
        return array_filter(self::$performanceData, function($item) use ($threshold) {
            return $item['duration'] > $threshold;
        });
    }
    
    public static function getQueryStats() {
        $connection = ActiveRecord\ConnectionManager::get_connection();
        $queries = $connection->query_log;
        
        $stats = [
            'total_queries' => count($queries),
            'unique_queries' => count(array_unique(array_column($queries, 'sql'))),
            'avg_duration' => 0,
            'slow_queries' => [],
            'most_frequent' => []
        ];
        
        if (!empty($queries)) {
            $durations = array_column($queries, 'duration');
            $stats['avg_duration'] = array_sum($durations) / count($durations);
            
            // Queries lentas (>1s)
            $stats['slow_queries'] = array_filter($queries, function($q) {
                return $q['duration'] > 1.0;
            });
            
            // Queries mais frequentes
            $sqlCounts = array_count_values(array_column($queries, 'sql'));
            arsort($sqlCounts);
            $stats['most_frequent'] = array_slice($sqlCounts, 0, 10, true);
        }
        
        return $stats;
    }
    
    public static function analyzeNPlusOne() {
        $queries = ActiveRecord\ConnectionManager::get_connection()->query_log;
        $nPlusOnePatterns = [];
        
        foreach ($queries as $i => $query) {
            if (preg_match('/SELECT.*FROM.*WHERE.*=.*\?/', $query['sql'])) {
                // Verificar se há múltiplas queries similares em sequência
                $similarCount = 1;
                for ($j = $i + 1; $j < count($queries); $j++) {
                    if (self::queriesAreSimilar($query['sql'], $queries[$j]['sql'])) {
                        $similarCount++;
                    } else {
                        break;
                    }
                }
                
                if ($similarCount > 5) { // Threshold para N+1
                    $nPlusOnePatterns[] = [
                        'pattern' => $query['sql'],
                        'count' => $similarCount,
                        'total_duration' => array_sum(array_slice(array_column($queries, 'duration'), $i, $similarCount))
                    ];
                }
            }
        }
        
        return $nPlusOnePatterns;
    }
    
    private static function queriesAreSimilar($sql1, $sql2) {
        // Remove parâmetros e compare estrutura
        $normalized1 = preg_replace('/\?\s*/', '?', $sql1);
        $normalized2 = preg_replace('/\?\s*/', '?', $sql2);
        
        return $normalized1 === $normalized2;
    }
    
    public static function generateReport() {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'performance_data' => self::$performanceData,
            'query_stats' => self::getQueryStats(),
            'slow_operations' => self::getSlowOperations(),
            'n_plus_one_issues' => self::analyzeNPlusOne(),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_current' => memory_get_usage(true)
        ];
    }
}
?>
```

## Dashboard de Monitoramento

```php
<?php
// Simple monitoring dashboard
class MonitoringDashboard {
    private $monitor;
    
    public function __construct($monitor) {
        $this->monitor = $monitor;
    }
    
    public function generateHTML() {
        $metrics = $this->monitor->collectMetrics();
        $alerts = $this->monitor->getAlerts();
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Database Monitor</title>
            <meta http-equiv="refresh" content="30">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .metric-card { 
                    border: 1px solid #ddd; 
                    padding: 15px; 
                    margin: 10px; 
                    border-radius: 5px; 
                    display: inline-block; 
                    min-width: 200px; 
                }
                .alert { 
                    padding: 10px; 
                    margin: 5px 0; 
                    border-radius: 3px; 
                }
                .alert.critical { background-color: #ffebee; border-left: 4px solid #f44336; }
                .alert.warning { background-color: #fff3e0; border-left: 4px solid #ff9800; }
                .alert.info { background-color: #e3f2fd; border-left: 4px solid #2196f3; }
                .status-ok { color: green; }
                .status-warning { color: orange; }
                .status-critical { color: red; }
            </style>
        </head>
        <body>
            <h1>Database Monitoring Dashboard</h1>
            <p>Last updated: <?= $metrics['timestamp'] ?></p>
            
            <?php if (!empty($alerts)): ?>
            <div class="alerts-section">
                <h2>Active Alerts</h2>
                <?php foreach ($alerts as $alert): ?>
                    <div class="alert <?= $alert['severity'] ?>">
                        <strong><?= ucfirst($alert['severity']) ?>:</strong> 
                        <?= htmlspecialchars($alert['message']) ?>
                        <small>(<?= $alert['timestamp'] ?>)</small>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="metrics-section">
                <h2>Current Metrics</h2>
                
                <div class="metric-card">
                    <h3>Connections</h3>
                    <p>Current: <?= $metrics['connections']['current_connections'] ?></p>
                    <p>Max: <?= $metrics['connections']['max_connections'] ?></p>
                    <p>Usage: <span class="<?= $this->getStatusClass($metrics['connections']['connection_usage_percent'], 80, 90) ?>">
                        <?= number_format($metrics['connections']['connection_usage_percent'], 1) ?>%
                    </span></p>
                </div>
                
                <div class="metric-card">
                    <h3>Performance</h3>
                    <p>QPS: <?= number_format($metrics['performance']['queries_per_second'], 2) ?></p>
                    <p>Slow Queries: <?= $metrics['performance']['slow_queries'] ?></p>
                    <p>Uptime: <?= $this->formatUptime($metrics['performance']['uptime']) ?></p>
                </div>
                
                <?php if (isset($metrics['storage']['database_size_mb'])): ?>
                <div class="metric-card">
                    <h3>Storage</h3>
                    <p>DB Size: <?= number_format($metrics['storage']['database_size_mb'], 2) ?> MB</p>
                    <p>Free Space: <?= number_format($metrics['storage']['free_space_mb'], 2) ?> MB</p>
                </div>
                <?php endif; ?>
                
                <div class="metric-card">
                    <h3>Locks</h3>
                    <p>Current Waits: <span class="<?= $this->getStatusClass(count($metrics['locks']['current_lock_waits']), 1, 5) ?>">
                        <?= count($metrics['locks']['current_lock_waits']) ?>
                    </span></p>
                </div>
            </div>
            
            <script>
                // Auto-refresh para dados dinâmicos
                setInterval(function() {
                    fetch('?ajax=1')
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById('dynamic-metrics').innerHTML = data.html;
                        });
                }, 5000);
            </script>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    private function getStatusClass($value, $warningThreshold, $criticalThreshold) {
        if ($value >= $criticalThreshold) return 'status-critical';
        if ($value >= $warningThreshold) return 'status-warning';
        return 'status-ok';
    }
    
    private function formatUptime($seconds) {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return "{$days}d {$hours}h {$minutes}m";
    }
}
?>
```

## Scripts de Alertas

```php
<?php
class AlertManager {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function sendEmailAlert($alert) {
        $to = $this->config['email']['to'];
        $subject = "Database Alert: {$alert['type']}";
        $message = "Alert Details:\n\n";
        $message .= "Type: {$alert['type']}\n";
        $message .= "Severity: {$alert['severity']}\n";
        $message .= "Message: {$alert['message']}\n";
        $message .= "Timestamp: {$alert['timestamp']}\n";
        
        $headers = "From: {$this->config['email']['from']}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        mail($to, $subject, $message, $headers);
    }
    
    public function sendSlackAlert($alert) {
        $webhookUrl = $this->config['slack']['webhook_url'];
        
        $color = [
            'critical' => 'danger',
            'warning' => 'warning',
            'info' => 'good'
        ][$alert['severity']] ?? 'good';
        
        $payload = [
            'text' => 'Database Alert',
            'attachments' => [
                [
                    'color' => $color,
                    'fields' => [
                        [
                            'title' => 'Type',
                            'value' => $alert['type'],
                            'short' => true
                        ],
                        [
                            'title' => 'Severity',
                            'value' => $alert['severity'],
                            'short' => true
                        ],
                        [
                            'title' => 'Message',
                            'value' => $alert['message'],
                            'short' => false
                        ]
                    ],
                    'ts' => strtotime($alert['timestamp'])
                ]
            ]
        ];
        
        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        curl_exec($ch);
        curl_close($ch);
    }
    
    public function sendSMSAlert($alert) {
        // Implementação para SMS (usando serviço como Twilio)
        if ($alert['severity'] === 'critical') {
            $message = "CRITICAL DB Alert: {$alert['message']}";
            // Implementar envio de SMS
        }
    }
}
?>
```

## Configuração de Exemplo

```php
<?php
// monitor_config.php
return [
    'host' => 'localhost',
    'database' => 'minha_app',
    'user' => 'monitor_user',
    'password' => 'senha_segura',
    
    'thresholds' => [
        'connection_usage' => 80,      // % de uso de conexões
        'slow_queries' => 10,          // número de queries lentas
        'database_size_mb' => 1000,    // tamanho em MB
        'query_duration' => 2.0,       // duração em segundos
        'memory_usage_mb' => 500       // uso de memória em MB
    ],
    
    'notifications' => [
        'email' => [
            'to' => 'admin@empresa.com',
            'from' => 'monitor@empresa.com'
        ],
        'slack' => [
            'webhook_url' => 'https://hooks.slack.com/services/...'
        ]
    ],
    
    'metrics_log' => './logs/metrics.log',
    'alert_log' => './logs/alerts.log',
    
    'monitoring_interval' => 60, // segundos
    'alert_cooldown' => 300      // segundos entre alertas do mesmo tipo
];
?>
```

## Script Principal de Monitoramento

```php
<?php
// monitor.php
require_once 'MySQLMonitor.php';
require_once 'ActiveRecordMonitor.php';
require_once 'AlertManager.php';

$config = require 'monitor_config.php';

// Inicializar monitores
$mysqlMonitor = new MySQLMonitor($config);
$alertManager = new AlertManager($config);

// Ativar monitoramento do ActiveRecord
ActiveRecordMonitor::startMonitoring();

// Loop principal de monitoramento
while (true) {
    try {
        // Coletar métricas
        $metrics = $mysqlMonitor->collectMetrics();
        $alerts = $mysqlMonitor->getAlerts();
        
        // Enviar alertas se necessário
        foreach ($alerts as $alert) {
            $alertManager->sendEmailAlert($alert);
            
            if ($alert['severity'] === 'critical') {
                $alertManager->sendSlackAlert($alert);
                $alertManager->sendSMSAlert($alert);
            }
        }
        
        // Log de status
        echo "[" . date('Y-m-d H:i:s') . "] Monitoring cycle completed. " . 
             count($alerts) . " alerts generated.\n";
        
    } catch (Exception $e) {
        error_log("Monitor error: " . $e->getMessage());
    }
    
    // Aguardar próximo ciclo
    sleep($config['monitoring_interval']);
}
?>
```

---

**Nota**: Este sistema de monitoramento deve ser adaptado às necessidades específicas do seu ambiente. Considere usar ferramentas especializadas como Prometheus, Grafana, ou New Relic para ambientes de produção complexos.
