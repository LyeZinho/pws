# 🚀 Guia de Otimização MySQL + PHP + ActiveRecord

Este guia fornece técnicas avançadas de otimização para melhorar a performance de aplicações PHP com MySQL e ActiveRecord.

## 📋 Índice
- [Otimização de Consultas](#otimização-de-consultas)
- [Índices e Performance](#índices-e-performance)
- [Cache de Consultas](#cache-de-consultas)
- [Connection Pooling](#connection-pooling)
- [Batch Operations](#batch-operations)
- [Lazy Loading vs Eager Loading](#lazy-loading-vs-eager-loading)
- [Particionamento de Tabelas](#particionamento-de-tabelas)
- [Monitorização de Performance](#monitorização-de-performance)
- [Otimização do PHP](#otimização-do-php)
- [Configurações MySQL](#configurações-mysql)

---

## 🔍 Otimização de Consultas

### **Identificação de Consultas Lentas**

```php
// Configurar logging de consultas lentas
class SlowQueryLogger {
    private $threshold = 0.5; // 500ms
    
    public function logSlowQueries() {
        ActiveRecord\Config::initialize(function($cfg) {
            $cfg->set_logging(true);
            $cfg->set_logger(new ActiveRecord\CallbackLogger(function($sql) {
                $start = microtime(true);
                
                // Executar callback após query
                register_shutdown_function(function() use ($sql, $start) {
                    $time = microtime(true) - $start;
                    if ($time > $this->threshold) {
                        error_log("SLOW QUERY ({$time}s): $sql");
                    }
                });
            }));
        });
    }
}
```

### **Otimização com EXPLAIN**

```php
class QueryOptimizer {
    public static function analyzeQuery($model, $conditions = []) {
        $connection = ActiveRecord\Connection::instance();
        
        // Gerar query do ActiveRecord
        $sql = $model::find('all', $conditions)->to_sql();
        
        // Analisar com EXPLAIN
        $explain = $connection->query("EXPLAIN $sql");
        
        echo "=== Análise da Query ===\n";
        echo "SQL: $sql\n\n";
        
        foreach ($explain as $row) {
            echo "Tabela: {$row['table']}\n";
            echo "Tipo: {$row['type']}\n";
            echo "Possíveis índices: {$row['possible_keys']}\n";
            echo "Índice usado: {$row['key']}\n";
            echo "Linhas examinadas: {$row['rows']}\n";
            echo "Extra: {$row['Extra']}\n\n";
            
            // Alertas de performance
            if ($row['type'] === 'ALL') {
                echo "⚠️  ALERTA: Full table scan detectado!\n";
            }
            if ($row['rows'] > 1000) {
                echo "⚠️  ALERTA: Muitas linhas sendo examinadas!\n";
            }
        }
    }
}

// Uso
QueryOptimizer::analyzeQuery(Post::class, [
    'conditions' => ['status = ? AND created_at > ?', 'published', '2025-01-01'],
    'order' => 'created_at DESC'
]);
```

### **Consultas Otimizadas por Tipo**

```php
class OptimizedQueries {
    
    // Busca paginada otimizada
    public static function paginatedPosts($page = 1, $perPage = 20) {
        return Post::find('all', [
            'select' => 'id, title, excerpt, created_at, user_id',
            'conditions' => ['status = ?', 'published'],
            'include' => ['user' => ['select' => 'id, name']],
            'order' => 'created_at DESC',
            'limit' => $perPage,
            'offset' => ($page - 1) * $perPage
        ]);
    }
    
    // Contagem otimizada
    public static function getPostsCount($status = 'published') {
        // Usar COUNT(*) em vez de carregar todos os registros
        return Post::count(['conditions' => ['status = ?', $status]]);
    }
    
    // Busca com agregação
    public static function getPopularTags($limit = 10) {
        return Tag::find_by_sql(
            "SELECT t.*, COUNT(pt.post_id) as post_count 
             FROM tags t 
             JOIN post_tags pt ON t.id = pt.tag_id 
             JOIN posts p ON pt.post_id = p.id 
             WHERE p.status = 'published'
             GROUP BY t.id 
             ORDER BY post_count DESC 
             LIMIT ?",
            [$limit]
        );
    }
    
    // Busca hierárquica otimizada
    public static function getCommentsTree($postId) {
        // Buscar todos os comentários de uma vez
        $comments = Comment::find('all', [
            'conditions' => ['post_id = ?', $postId],
            'include' => ['user'],
            'order' => 'created_at ASC'
        ]);
        
        // Organizar em árvore no PHP
        return self::buildCommentsTree($comments);
    }
    
    private static function buildCommentsTree($comments, $parentId = null) {
        $tree = [];
        foreach ($comments as $comment) {
            if ($comment->parent_id == $parentId) {
                $comment->replies = self::buildCommentsTree($comments, $comment->id);
                $tree[] = $comment;
            }
        }
        return $tree;
    }
}
```

---

## 📊 Índices e Performance

### **Estratégias de Indexação**

```sql
-- Índices básicos essenciais
CREATE INDEX idx_posts_status ON posts(status);
CREATE INDEX idx_posts_user_id ON posts(user_id);
CREATE INDEX idx_posts_created_at ON posts(created_at);

-- Índices compostos para consultas específicas
CREATE INDEX idx_posts_status_created ON posts(status, created_at);
CREATE INDEX idx_posts_user_status ON posts(user_id, status);

-- Índices para LIKE queries
CREATE INDEX idx_posts_title_fulltext ON posts(title) USING FULLTEXT;
CREATE INDEX idx_users_name_prefix ON users(name(10)); -- Prefixo

-- Índices condicionais (MySQL 8.0+)
CREATE INDEX idx_published_posts ON posts(created_at) WHERE status = 'published';
```

### **Análise de Índices**

```php
class IndexAnalyzer {
    public static function analyzeTableIndexes($tableName) {
        $connection = ActiveRecord\Connection::instance();
        
        // Mostrar índices existentes
        $indexes = $connection->query("SHOW INDEX FROM $tableName");
        echo "=== Índices da tabela $tableName ===\n";
        foreach ($indexes as $index) {
            echo "Nome: {$index['Key_name']}, Coluna: {$index['Column_name']}, Único: {$index['Non_unique']}\n";
        }
        
        // Verificar índices não utilizados
        $unused = $connection->query("
            SELECT OBJECT_NAME, INDEX_NAME 
            FROM performance_schema.table_io_waits_summary_by_index_usage 
            WHERE OBJECT_SCHEMA = DATABASE() 
              AND OBJECT_NAME = '$tableName'
              AND INDEX_NAME IS NOT NULL 
              AND COUNT_STAR = 0
        ");
        
        if (!empty($unused)) {
            echo "\n⚠️  Índices não utilizados:\n";
            foreach ($unused as $index) {
                echo "- {$index['INDEX_NAME']}\n";
            }
        }
    }
    
    public static function suggestIndexes($tableName) {
        $connection = ActiveRecord\Connection::instance();
        
        // Analisar consultas do slow log
        $slowQueries = $connection->query("
            SELECT sql_text, exec_count 
            FROM performance_schema.events_statements_summary_by_digest 
            WHERE OBJECT_SCHEMA = DATABASE() 
              AND sql_text LIKE '%$tableName%'
              AND avg_timer_wait > 1000000000
            ORDER BY avg_timer_wait DESC 
            LIMIT 10
        ");
        
        echo "=== Sugestões de índices para $tableName ===\n";
        foreach ($slowQueries as $query) {
            echo "Query lenta: {$query['sql_text']}\n";
            echo "Execuções: {$query['exec_count']}\n\n";
        }
    }
}
```

---

## 💾 Cache de Consultas

### **Cache Simples com APCu**

```php
class QueryCache {
    private static $enabled = true;
    private static $ttl = 3600; // 1 hora
    
    public static function get($key) {
        if (!self::$enabled || !extension_loaded('apcu')) {
            return false;
        }
        return apcu_fetch($key);
    }
    
    public static function set($key, $value, $ttl = null) {
        if (!self::$enabled || !extension_loaded('apcu')) {
            return false;
        }
        return apcu_store($key, $value, $ttl ?? self::$ttl);
    }
    
    public static function delete($key) {
        if (!self::$enabled || !extension_loaded('apcu')) {
            return false;
        }
        return apcu_delete($key);
    }
    
    public static function clear() {
        if (!self::$enabled || !extension_loaded('apcu')) {
            return false;
        }
        return apcu_clear_cache();
    }
}

// Trait para cache em models
trait Cacheable {
    public static function findCached($id, $ttl = 3600) {
        $key = static::class . "_" . $id;
        
        $cached = QueryCache::get($key);
        if ($cached !== false) {
            return $cached;
        }
        
        $record = static::find($id);
        if ($record) {
            QueryCache::set($key, $record, $ttl);
        }
        
        return $record;
    }
    
    public function clearCache() {
        $key = static::class . "_" . $this->id;
        QueryCache::delete($key);
    }
    
    // Hook para limpar cache após save/delete
    public function after_save() {
        $this->clearCache();
    }
    
    public function after_destroy() {
        $this->clearCache();
    }
}

// Uso nos models
class User extends ActiveRecord\Model {
    use Cacheable;
}

// Buscar com cache
$user = User::findCached(1); // Cache por 1 hora
```

### **Cache Redis Avançado**

```php
class RedisCache {
    private $redis;
    private $prefix = 'ar_cache:';
    
    public function __construct($host = 'localhost', $port = 6379) {
        $this->redis = new Redis();
        $this->redis->connect($host, $port);
    }
    
    public function get($key) {
        $data = $this->redis->get($this->prefix . $key);
        return $data ? unserialize($data) : false;
    }
    
    public function set($key, $value, $ttl = 3600) {
        return $this->redis->setex(
            $this->prefix . $key, 
            $ttl, 
            serialize($value)
        );
    }
    
    public function delete($key) {
        return $this->redis->del($this->prefix . $key);
    }
    
    public function flush() {
        $keys = $this->redis->keys($this->prefix . '*');
        if (!empty($keys)) {
            return $this->redis->del($keys);
        }
        return true;
    }
    
    // Cache de consultas complexas
    public function cacheQuery($sql, $params, $callback, $ttl = 1800) {
        $key = 'query:' . md5($sql . serialize($params));
        
        $result = $this->get($key);
        if ($result !== false) {
            return $result;
        }
        
        $result = $callback();
        $this->set($key, $result, $ttl);
        
        return $result;
    }
}

// Integração com ActiveRecord
class CachedModel extends ActiveRecord\Model {
    protected static $cache;
    
    public static function setCache($cache) {
        self::$cache = $cache;
    }
    
    public static function findAllCached($options = [], $ttl = 1800) {
        if (!self::$cache) {
            return static::find('all', $options);
        }
        
        $key = static::class . ':find_all:' . md5(serialize($options));
        
        return self::$cache->cacheQuery(
            'find_all',
            [$options],
            function() use ($options) {
                return static::find('all', $options);
            },
            $ttl
        );
    }
}
```

---

## 🔗 Connection Pooling

### **Pool de Conexões Simples**

```php
class ConnectionPool {
    private static $pool = [];
    private static $maxConnections = 10;
    private static $currentConnections = 0;
    
    public static function getConnection($config) {
        $key = md5(serialize($config));
        
        // Verificar se há conexão disponível no pool
        if (isset(self::$pool[$key]) && !empty(self::$pool[$key])) {
            return array_pop(self::$pool[$key]);
        }
        
        // Criar nova conexão se não atingiu o limite
        if (self::$currentConnections < self::$maxConnections) {
            $connection = new PDO(
                $config['dsn'],
                $config['username'],
                $config['password'],
                $config['options'] ?? []
            );
            self::$currentConnections++;
            return $connection;
        }
        
        throw new Exception("Pool de conexões esgotado");
    }
    
    public static function releaseConnection($connection, $config) {
        $key = md5(serialize($config));
        
        if (!isset(self::$pool[$key])) {
            self::$pool[$key] = [];
        }
        
        // Verificar se conexão ainda está válida
        try {
            $connection->query("SELECT 1");
            self::$pool[$key][] = $connection;
        } catch (Exception $e) {
            // Conexão inválida, descartar
            self::$currentConnections--;
        }
    }
    
    public static function closeAll() {
        self::$pool = [];
        self::$currentConnections = 0;
    }
}
```

---

## 📦 Batch Operations

### **Inserções em Lote**

```php
class BatchOperations {
    
    public static function batchInsert($modelClass, $data, $batchSize = 1000) {
        $connection = ActiveRecord\Connection::instance();
        $tableName = $modelClass::table_name();
        
        if (empty($data)) {
            return 0;
        }
        
        $inserted = 0;
        $chunks = array_chunk($data, $batchSize);
        
        foreach ($chunks as $chunk) {
            $columns = array_keys($chunk[0]);
            $placeholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
            $values = [];
            
            foreach ($chunk as $row) {
                foreach ($columns as $column) {
                    $values[] = $row[$column];
                }
            }
            
            $sql = "INSERT INTO $tableName (" . implode(',', $columns) . ") VALUES " .
                   implode(',', array_fill(0, count($chunk), $placeholders));
            
            $connection->query($sql, $values);
            $inserted += count($chunk);
        }
        
        return $inserted;
    }
    
    public static function batchUpdate($modelClass, $updates, $batchSize = 100) {
        $connection = ActiveRecord\Connection::instance();
        $tableName = $modelClass::table_name();
        $updated = 0;
        
        $chunks = array_chunk($updates, $batchSize);
        
        foreach ($chunks as $chunk) {
            $connection->transaction(function() use ($connection, $tableName, $chunk, &$updated) {
                foreach ($chunk as $update) {
                    $id = $update['id'];
                    unset($update['id']);
                    
                    $setClauses = [];
                    $values = [];
                    
                    foreach ($update as $column => $value) {
                        $setClauses[] = "$column = ?";
                        $values[] = $value;
                    }
                    
                    $values[] = $id;
                    $sql = "UPDATE $tableName SET " . implode(', ', $setClauses) . " WHERE id = ?";
                    
                    $connection->query($sql, $values);
                    $updated++;
                }
            });
        }
        
        return $updated;
    }
    
    public static function batchDelete($modelClass, $ids, $batchSize = 1000) {
        $connection = ActiveRecord\Connection::instance();
        $tableName = $modelClass::table_name();
        $deleted = 0;
        
        $chunks = array_chunk($ids, $batchSize);
        
        foreach ($chunks as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $sql = "DELETE FROM $tableName WHERE id IN ($placeholders)";
            
            $connection->query($sql, $chunk);
            $deleted += count($chunk);
        }
        
        return $deleted;
    }
}

// Exemplo de uso
$users = [
    ['name' => 'João', 'email' => 'joao@exemplo.com'],
    ['name' => 'Maria', 'email' => 'maria@exemplo.com'],
    // ... mais 10000 registros
];

$inserted = BatchOperations::batchInsert(User::class, $users, 500);
echo "Inseridos: $inserted usuários\n";
```

---

## 🔄 Lazy Loading vs Eager Loading

### **Estratégias de Carregamento**

```php
class LoadingStrategies {
    
    // Problema N+1 - EVITAR
    public static function badExample() {
        $posts = Post::find('all', ['limit' => 100]);
        
        foreach ($posts as $post) {
            echo $post->title . " por " . $post->user->name . "\n"; // N+1 queries!
        }
    }
    
    // Solução com Eager Loading
    public static function goodExample() {
        $posts = Post::find('all', [
            'limit' => 100,
            'include' => ['user', 'category']
        ]);
        
        foreach ($posts as $post) {
            echo $post->title . " por " . $post->user->name . "\n"; // Apenas 3 queries total
        }
    }
    
    // Eager loading condicional
    public static function conditionalEagerLoading($includeComments = false) {
        $includes = ['user', 'category'];
        
        if ($includeComments) {
            $includes[] = 'comments';
        }
        
        return Post::find('all', [
            'include' => $includes,
            'conditions' => ['status = ?', 'published']
        ]);
    }
    
    // Lazy loading controlado
    public static function controlledLazyLoading() {
        $posts = Post::find('all', [
            'select' => 'id, title, user_id',
            'conditions' => ['status = ?', 'published']
        ]);
        
        // Carregar usuários apenas quando necessário
        $userIds = array_unique(array_column($posts->to_array(), 'user_id'));
        $users = User::find('all', [
            'conditions' => ['id IN (?)', $userIds]
        ]);
        
        // Mapear usuários
        $userMap = [];
        foreach ($users as $user) {
            $userMap[$user->id] = $user;
        }
        
        // Associar aos posts
        foreach ($posts as $post) {
            $post->user = $userMap[$post->user_id] ?? null;
        }
        
        return $posts;
    }
}

// Trait para carregamento inteligente
trait SmartLoading {
    private static $loadedRelations = [];
    
    public static function withRelations($relations) {
        self::$loadedRelations = is_array($relations) ? $relations : [$relations];
        return new static();
    }
    
    public static function find($type, $options = []) {
        if (!empty(self::$loadedRelations)) {
            $options['include'] = array_merge(
                $options['include'] ?? [],
                self::$loadedRelations
            );
            self::$loadedRelations = [];
        }
        
        return parent::find($type, $options);
    }
}

// Uso
class Post extends ActiveRecord\Model {
    use SmartLoading;
}

$posts = Post::withRelations(['user', 'comments'])->find('all');
```

---

## 🗂️ Particionamento de Tabelas

### **Particionamento por Data**

```sql
-- Particionamento por range (MySQL)
CREATE TABLE posts_partitioned (
    id INT AUTO_INCREMENT,
    title VARCHAR(200),
    content TEXT,
    user_id INT,
    created_at TIMESTAMP,
    PRIMARY KEY (id, created_at)
) PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2023 VALUES LESS THAN (2024),
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Particionamento por hash
CREATE TABLE users_partitioned (
    id INT AUTO_INCREMENT,
    name VARCHAR(100),
    email VARCHAR(150),
    created_at TIMESTAMP,
    PRIMARY KEY (id)
) PARTITION BY HASH(id) PARTITIONS 4;
```

### **Gerenciamento de Partições**

```php
class PartitionManager {
    private $connection;
    
    public function __construct() {
        $this->connection = ActiveRecord\Connection::instance();
    }
    
    public function createMonthlyPartition($table, $year, $month) {
        $partitionName = "p{$year}_{$month}";
        $nextMonth = $month == 12 ? 1 : $month + 1;
        $nextYear = $month == 12 ? $year + 1 : $year;
        
        $sql = "ALTER TABLE {$table} ADD PARTITION (
            PARTITION {$partitionName} VALUES LESS THAN ('{$nextYear}-{$nextMonth:02d}-01')
        )";
        
        try {
            $this->connection->query($sql);
            echo "Partição {$partitionName} criada com sucesso\n";
        } catch (Exception $e) {
            echo "Erro ao criar partição: " . $e->getMessage() . "\n";
        }
    }
    
    public function dropOldPartitions($table, $keepMonths = 12) {
        $cutoffDate = date('Y-m-d', strtotime("-{$keepMonths} months"));
        
        $partitions = $this->connection->query("
            SELECT PARTITION_NAME 
            FROM INFORMATION_SCHEMA.PARTITIONS 
            WHERE TABLE_NAME = '{$table}' 
              AND PARTITION_EXPRESSION LIKE '%created_at%'
        ");
        
        foreach ($partitions as $partition) {
            // Lógica para determinar se a partição é antiga
            // e pode ser removida
        }
    }
    
    public function getPartitionInfo($table) {
        return $this->connection->query("
            SELECT 
                PARTITION_NAME,
                PARTITION_EXPRESSION,
                PARTITION_DESCRIPTION,
                TABLE_ROWS
            FROM INFORMATION_SCHEMA.PARTITIONS 
            WHERE TABLE_NAME = '{$table}'
              AND PARTITION_NAME IS NOT NULL
        ");
    }
}
```

---

## 📈 Monitorização de Performance

### **Monitor de Performance em Tempo Real**

```php
class PerformanceMonitor {
    private static $instance;
    private $queries = [];
    private $startTime;
    private $startMemory;
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function start() {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
        $this->queries = [];
        
        // Hook no ActiveRecord
        ActiveRecord\Config::initialize(function($cfg) {
            $cfg->set_logging(true);
            $cfg->set_logger(new ActiveRecord\CallbackLogger([$this, 'logQuery']));
        });
    }
    
    public function logQuery($sql) {
        $this->queries[] = [
            'sql' => $sql,
            'time' => microtime(true),
            'memory' => memory_get_usage()
        ];
    }
    
    public function getReport() {
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $totalTime = $endTime - $this->startTime;
        $memoryUsed = $endMemory - $this->startMemory;
        $queryCount = count($this->queries);
        
        // Analisar consultas duplicadas
        $sqlCounts = array_count_values(array_column($this->queries, 'sql'));
        $duplicates = array_filter($sqlCounts, function($count) { return $count > 1; });
        
        // Consultas mais lentas (estimativa)
        $queryTimes = [];
        for ($i = 1; $i < count($this->queries); $i++) {
            $queryTimes[] = [
                'sql' => $this->queries[$i]['sql'],
                'time' => $this->queries[$i]['time'] - $this->queries[$i-1]['time']
            ];
        }
        
        usort($queryTimes, function($a, $b) {
            return $b['time'] <=> $a['time'];
        });
        
        return [
            'total_time' => $totalTime,
            'memory_used' => $memoryUsed,
            'query_count' => $queryCount,
            'avg_query_time' => $queryCount > 0 ? $totalTime / $queryCount : 0,
            'duplicate_queries' => $duplicates,
            'slowest_queries' => array_slice($queryTimes, 0, 5),
            'queries_per_second' => $totalTime > 0 ? $queryCount / $totalTime : 0
        ];
    }
    
    public function printReport() {
        $report = $this->getReport();
        
        echo "=== Performance Report ===\n";
        echo "Tempo total: " . round($report['total_time'], 4) . "s\n";
        echo "Memória usada: " . round($report['memory_used'] / 1024 / 1024, 2) . "MB\n";
        echo "Total de queries: " . $report['query_count'] . "\n";
        echo "Tempo médio por query: " . round($report['avg_query_time'], 4) . "s\n";
        echo "Queries por segundo: " . round($report['queries_per_second'], 2) . "\n\n";
        
        if (!empty($report['duplicate_queries'])) {
            echo "⚠️  Queries duplicadas detectadas:\n";
            foreach ($report['duplicate_queries'] as $sql => $count) {
                echo "  ({$count}x) " . substr($sql, 0, 80) . "...\n";
            }
            echo "\n";
        }
        
        if (!empty($report['slowest_queries'])) {
            echo "🐌 Queries mais lentas:\n";
            foreach ($report['slowest_queries'] as $query) {
                echo "  (" . round($query['time'], 4) . "s) " . substr($query['sql'], 0, 80) . "...\n";
            }
        }
    }
}

// Uso
$monitor = PerformanceMonitor::getInstance();
$monitor->start();

// Seu código aqui
$posts = Post::find('all', ['include' => ['user', 'comments']]);
foreach ($posts as $post) {
    echo $post->title . "\n";
}

$monitor->printReport();
```

---

## ⚡ Otimização do PHP

### **Configurações PHP para Performance**

```ini
; php.ini - Configurações recomendadas

; OPcache - Cache de bytecode
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1

; Realpath cache
realpath_cache_size=4096K
realpath_cache_ttl=600

; Memória
memory_limit=512M

; Upload/Post
max_execution_time=60
max_input_time=60
post_max_size=50M
upload_max_filesize=50M
```

### **Otimizações de Código PHP**

```php
// Evitar queries em loops
class OptimizedController {
    
    // ❌ RUIM - N+1 problem
    public function badPostsList() {
        $posts = Post::find('all', ['limit' => 100]);
        
        foreach ($posts as $post) {
            echo $post->title . " - " . $post->user->name . "\n";
            echo "Tags: ";
            foreach ($post->tags as $tag) { // Query por post
                echo $tag->name . " ";
            }
            echo "\n";
        }
    }
    
    // ✅ BOM - Eager loading
    public function goodPostsList() {
        $posts = Post::find('all', [
            'limit' => 100,
            'include' => ['user', 'tags']
        ]);
        
        foreach ($posts as $post) {
            echo $post->title . " - " . $post->user->name . "\n";
            echo "Tags: ";
            foreach ($post->tags as $tag) {
                echo $tag->name . " ";
            }
            echo "\n";
        }
    }
    
    // Cache de contadores
    public function getCachedStats() {
        $cacheKey = 'site_stats_' . date('Y-m-d-H');
        
        $stats = apcu_fetch($cacheKey);
        if ($stats === false) {
            $stats = [
                'total_posts' => Post::count(),
                'total_users' => User::count(),
                'total_comments' => Comment::count(),
                'posts_today' => Post::count(['conditions' => ['DATE(created_at) = ?', date('Y-m-d')]])
            ];
            apcu_store($cacheKey, $stats, 3600); // Cache por 1 hora
        }
        
        return $stats;
    }
}
```

---

## 🔧 Configurações MySQL

### **my.cnf/my.ini Otimizado**

```ini
[mysqld]
# Configurações básicas
port = 3306
socket = /var/run/mysqld/mysqld.sock

# Configurações de buffer
innodb_buffer_pool_size = 1G          # 70-80% da RAM disponível
innodb_log_file_size = 256M
innodb_log_buffer_size = 16M
innodb_flush_log_at_trx_commit = 2     # Performance vs Durabilidade

# Query cache
query_cache_type = 1
query_cache_size = 256M
query_cache_limit = 2M

# Configurações de conexão
max_connections = 500
max_connect_errors = 1000000
max_user_connections = 450

# Configurações de tabela
table_open_cache = 4000
table_definition_cache = 2000

# Configurações de thread
thread_cache_size = 50
thread_stack = 256K

# Configurações de sort/join
sort_buffer_size = 2M
read_buffer_size = 1M
read_rnd_buffer_size = 4M
join_buffer_size = 2M

# Configurações de MyISAM (se usar)
key_buffer_size = 256M

# Configurações de temporárias
tmp_table_size = 128M
max_heap_table_size = 128M

# Slow query log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

# Configurações de segurança
local_infile = 0
```

### **Manutenção Automática**

```php
class DatabaseMaintenance {
    
    public static function optimizeTables() {
        $connection = ActiveRecord\Connection::instance();
        
        // Obter todas as tabelas
        $tables = $connection->query("SHOW TABLES");
        
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            
            echo "Otimizando tabela: $tableName\n";
            
            // Verificar fragmentação
            $status = $connection->query("SHOW TABLE STATUS LIKE '$tableName'")[0];
            $fragmentation = $status['Data_free'] / ($status['Data_length'] + $status['Index_length']);
            
            if ($fragmentation > 0.1) { // 10% de fragmentação
                echo "  Fragmentação detectada: " . round($fragmentation * 100, 2) . "%\n";
                $connection->query("OPTIMIZE TABLE $tableName");
                echo "  Tabela otimizada\n";
            }
        }
    }
    
    public static function updateTableStats() {
        $connection = ActiveRecord\Connection::instance();
        
        // Atualizar estatísticas das tabelas
        $connection->query("ANALYZE TABLE " . implode(', ', [
            User::table_name(),
            Post::table_name(),
            Comment::table_name()
        ]));
        
        echo "Estatísticas das tabelas atualizadas\n";
    }
    
    public static function cleanupOldData($days = 30) {
        $cutoffDate = date('Y-m-d', strtotime("-$days days"));
        
        // Limpar logs antigos
        $deleted = LogEntry::delete_all([
            'conditions' => ['created_at < ?', $cutoffDate]
        ]);
        
        echo "Removidas $deleted entradas de log antigas\n";
        
        // Limpar sessões expiradas
        $deleted = Session::delete_all([
            'conditions' => ['updated_at < ?', $cutoffDate]
        ]);
        
        echo "Removidas $deleted sessões expiradas\n";
    }
}

// Executar manutenção
DatabaseMaintenance::optimizeTables();
DatabaseMaintenance::updateTableStats();
DatabaseMaintenance::cleanupOldData(30);
```

---

## 💡 Resumo de Boas Práticas

### ✅ **Fazer**
- Usar índices apropriados para todas as consultas
- Implementar eager loading para evitar N+1
- Cache de consultas frequentes
- Usar batch operations para grandes volumes
- Monitorar performance regularmente
- Otimizar configurações MySQL
- Usar prepared statements

### ❌ **Evitar**
- Consultas em loops (N+1 problem)
- SELECT * desnecessários
- Índices em excesso ou inadequados
- Transações muito longas
- Consultas sem WHERE em tabelas grandes
- Falta de limits em consultas de listagem
- Ignorar logs de consultas lentas

### 🎯 **Métricas a Monitorar**
- Tempo de resposta das consultas
- Número de consultas por request
- Uso de memória
- Cache hit ratio
- Índices não utilizados
- Fragmentação das tabelas
- Conexões ativas

---

**💡 Lembre-se:** A otimização é um processo contínuo. Meça sempre antes e depois das mudanças para validar as melhorias!
