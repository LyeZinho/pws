# 📚 Guia de Migrações e Versionamento de Schema

Este guia aborda estratégias para gerenciar mudanças no schema do banco de dados de forma controlada e segura.

## 📋 Índice
- [Conceitos de Migrações](#conceitos-de-migrações)
- [Sistema de Migrações Simples](#sistema-de-migrações-simples)
- [Versionamento de Schema](#versionamento-de-schema)
- [Rollback e Recovery](#rollback-e-recovery)
- [Migrações de Dados](#migrações-de-dados)
- [Estratégias Zero-Downtime](#estratégias-zero-downtime)
- [Ambientes e Deploy](#ambientes-e-deploy)
- [Backup antes de Migrações](#backup-antes-de-migrações)
- [Testes de Migrações](#testes-de-migrações)
- [Automação e CI/CD](#automação-e-cicd)

---

## 📖 Conceitos de Migrações

### **O que são Migrações**

Migrações são scripts que modificam o schema do banco de dados de forma controlada e versionada, permitindo:

- **Controle de versão** do schema
- **Sincronização** entre ambientes
- **Rollback** de mudanças
- **Histórico** de alterações
- **Colaboração** em equipe

### **Estrutura de uma Migração**

```php
// migrations/001_create_users_table.php
class CreateUsersTable extends Migration {
    
    public function up() {
        // Aplicar mudanças
        $this->execute("
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_email (email),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ");
    }
    
    public function down() {
        // Reverter mudanças
        $this->execute("DROP TABLE IF EXISTS users");
    }
}
```

---

## 🔧 Sistema de Migrações Simples

### **Classe Base de Migração**

```php
abstract class Migration {
    protected $connection;
    
    public function __construct() {
        $this->connection = ActiveRecord\Connection::instance();
    }
    
    abstract public function up();
    abstract public function down();
    
    protected function execute($sql) {
        try {
            echo "Executando: " . substr($sql, 0, 100) . "...\n";
            $this->connection->query($sql);
            echo "✅ Sucesso\n";
        } catch (Exception $e) {
            echo "❌ Erro: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    protected function createTable($tableName, $columns, $options = []) {
        $columnsSql = [];
        
        foreach ($columns as $name => $definition) {
            $columnsSql[] = "$name $definition";
        }
        
        $sql = "CREATE TABLE $tableName (\n";
        $sql .= "    " . implode(",\n    ", $columnsSql) . "\n";
        $sql .= ")";
        
        if (!empty($options)) {
            $sql .= " " . implode(" ", $options);
        }
        
        $this->execute($sql);
    }
    
    protected function addColumn($tableName, $columnName, $definition) {
        $sql = "ALTER TABLE $tableName ADD COLUMN $columnName $definition";
        $this->execute($sql);
    }
    
    protected function dropColumn($tableName, $columnName) {
        $sql = "ALTER TABLE $tableName DROP COLUMN $columnName";
        $this->execute($sql);
    }
    
    protected function addIndex($tableName, $indexName, $columns) {
        $columnsList = is_array($columns) ? implode(', ', $columns) : $columns;
        $sql = "ALTER TABLE $tableName ADD INDEX $indexName ($columnsList)";
        $this->execute($sql);
    }
    
    protected function dropIndex($tableName, $indexName) {
        $sql = "ALTER TABLE $tableName DROP INDEX $indexName";
        $this->execute($sql);
    }
    
    protected function addForeignKey($tableName, $keyName, $column, $referencedTable, $referencedColumn) {
        $sql = "ALTER TABLE $tableName ADD CONSTRAINT $keyName 
                FOREIGN KEY ($column) REFERENCES $referencedTable($referencedColumn)";
        $this->execute($sql);
    }
    
    protected function dropForeignKey($tableName, $keyName) {
        $sql = "ALTER TABLE $tableName DROP FOREIGN KEY $keyName";
        $this->execute($sql);
    }
    
    protected function insertData($tableName, $data) {
        foreach ($data as $row) {
            $columns = implode(', ', array_keys($row));
            $placeholders = implode(', ', array_fill(0, count($row), '?'));
            $sql = "INSERT INTO $tableName ($columns) VALUES ($placeholders)";
            
            $this->connection->query($sql, array_values($row));
        }
    }
}
```

### **Gerenciador de Migrações**

```php
class MigrationManager {
    private $migrationsPath;
    private $connection;
    
    public function __construct($migrationsPath = 'migrations') {
        $this->migrationsPath = $migrationsPath;
        $this->connection = ActiveRecord\Connection::instance();
        $this->initializeSchemaTable();
    }
    
    private function initializeSchemaTable() {
        $this->connection->query("
            CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(255) PRIMARY KEY,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    public function migrate($targetVersion = null) {
        $pendingMigrations = $this->getPendingMigrations($targetVersion);
        
        if (empty($pendingMigrations)) {
            echo "Nenhuma migração pendente.\n";
            return;
        }
        
        echo "Executando " . count($pendingMigrations) . " migrações...\n";
        
        foreach ($pendingMigrations as $migration) {
            $this->executeMigration($migration, 'up');
        }
        
        echo "Migrações concluídas!\n";
    }
    
    public function rollback($steps = 1) {
        $executedMigrations = $this->getExecutedMigrations();
        $toRollback = array_slice(array_reverse($executedMigrations), 0, $steps);
        
        if (empty($toRollback)) {
            echo "Nenhuma migração para reverter.\n";
            return;
        }
        
        echo "Revertendo " . count($toRollback) . " migrações...\n";
        
        foreach ($toRollback as $version) {
            $migration = $this->loadMigration($version);
            if ($migration) {
                $this->executeMigration($migration, 'down');
                $this->removeMigrationRecord($version);
            }
        }
        
        echo "Rollback concluído!\n";
    }
    
    public function status() {
        $allMigrations = $this->getAllMigrations();
        $executedMigrations = $this->getExecutedMigrations();
        
        echo "Status das Migrações:\n";
        echo str_repeat("=", 50) . "\n";
        
        foreach ($allMigrations as $version => $filename) {
            $status = in_array($version, $executedMigrations) ? "✅ Executada" : "⏳ Pendente";
            echo sprintf("%-20s %s %s\n", $version, $status, $filename);
        }
    }
    
    private function executeMigration($migration, $direction) {
        $version = $this->getVersionFromClass($migration);
        
        echo "\n" . str_repeat("-", 50) . "\n";
        echo "Migração: $version ($direction)\n";
        echo str_repeat("-", 50) . "\n";
        
        try {
            $this->connection->transaction(function() use ($migration, $direction, $version) {
                $instance = new $migration();
                
                if ($direction === 'up') {
                    $instance->up();
                    $this->recordMigration($version);
                } else {
                    $instance->down();
                }
            });
            
            echo "✅ Migração $version ($direction) executada com sucesso!\n";
            
        } catch (Exception $e) {
            echo "❌ Erro na migração $version: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function getPendingMigrations($targetVersion = null) {
        $allMigrations = $this->getAllMigrations();
        $executedMigrations = $this->getExecutedMigrations();
        
        $pending = [];
        foreach ($allMigrations as $version => $filename) {
            if (!in_array($version, $executedMigrations)) {
                if ($targetVersion === null || $version <= $targetVersion) {
                    $pending[$version] = $this->loadMigration($version);
                }
            }
        }
        
        return $pending;
    }
    
    private function getAllMigrations() {
        $migrations = [];
        $files = glob($this->migrationsPath . '/*.php');
        
        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/^(\d+)_/', $filename, $matches)) {
                $version = $matches[1];
                $migrations[$version] = $filename;
            }
        }
        
        ksort($migrations);
        return $migrations;
    }
    
    private function getExecutedMigrations() {
        $result = $this->connection->query("
            SELECT version FROM schema_migrations ORDER BY version
        ");
        
        return array_column($result, 'version');
    }
    
    private function loadMigration($version) {
        $files = glob($this->migrationsPath . "/{$version}_*.php");
        
        if (empty($files)) {
            return null;
        }
        
        require_once $files[0];
        
        // Extrair nome da classe do arquivo
        $content = file_get_contents($files[0]);
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    private function recordMigration($version) {
        $this->connection->query(
            "INSERT INTO schema_migrations (version) VALUES (?)",
            [$version]
        );
    }
    
    private function removeMigrationRecord($version) {
        $this->connection->query(
            "DELETE FROM schema_migrations WHERE version = ?",
            [$version]
        );
    }
    
    private function getVersionFromClass($className) {
        $files = glob($this->migrationsPath . '/*.php');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (strpos($content, "class $className") !== false) {
                $filename = basename($file);
                if (preg_match('/^(\d+)_/', $filename, $matches)) {
                    return $matches[1];
                }
            }
        }
        
        return null;
    }
    
    public function create($name) {
        $version = date('YmdHis');
        $className = $this->camelCase($name);
        $filename = "{$version}_{$name}.php";
        $filepath = $this->migrationsPath . '/' . $filename;
        
        $template = $this->getMigrationTemplate($className);
        
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }
        
        file_put_contents($filepath, $template);
        
        echo "Migração criada: $filename\n";
        return $filepath;
    }
    
    private function camelCase($string) {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }
    
    private function getMigrationTemplate($className) {
        return <<<PHP
<?php

class {$className} extends Migration {
    
    public function up() {
        // Implementar mudanças aqui
        
    }
    
    public function down() {
        // Implementar rollback aqui
        
    }
}

PHP;
    }
}
```

---

## 📊 Versionamento de Schema

### **Estratégias de Versionamento**

```php
class SchemaVersioning {
    
    public static function getCurrentVersion() {
        $connection = ActiveRecord\Connection::instance();
        
        $result = $connection->query("
            SELECT MAX(version) as version FROM schema_migrations
        ");
        
        return $result[0]['version'] ?? '0';
    }
    
    public static function getSchemaInfo() {
        $connection = ActiveRecord\Connection::instance();
        
        return [
            'current_version' => self::getCurrentVersion(),
            'total_migrations' => self::getTotalMigrations(),
            'last_migration_date' => self::getLastMigrationDate(),
            'pending_migrations' => self::getPendingMigrationsCount()
        ];
    }
    
    private static function getTotalMigrations() {
        $connection = ActiveRecord\Connection::instance();
        
        $result = $connection->query("
            SELECT COUNT(*) as count FROM schema_migrations
        ");
        
        return $result[0]['count'];
    }
    
    private static function getLastMigrationDate() {
        $connection = ActiveRecord\Connection::instance();
        
        $result = $connection->query("
            SELECT MAX(executed_at) as last_date FROM schema_migrations
        ");
        
        return $result[0]['last_date'];
    }
    
    private static function getPendingMigrationsCount() {
        $manager = new MigrationManager();
        $allMigrations = count(glob('migrations/*.php'));
        $executed = self::getTotalMigrations();
        
        return $allMigrations - $executed;
    }
    
    public static function generateSchemaFile($outputPath = 'schema.sql') {
        $connection = ActiveRecord\Connection::instance();
        
        // Obter estrutura de todas as tabelas
        $tables = $connection->query("SHOW TABLES");
        $schema = "-- Schema gerado em " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            
            if ($tableName === 'schema_migrations') {
                continue;
            }
            
            $createTable = $connection->query("SHOW CREATE TABLE `$tableName`");
            $schema .= $createTable[0]['Create Table'] . ";\n\n";
        }
        
        file_put_contents($outputPath, $schema);
        echo "Schema exportado para: $outputPath\n";
    }
    
    public static function compareSchemas($schema1Path, $schema2Path) {
        $schema1 = file_get_contents($schema1Path);
        $schema2 = file_get_contents($schema2Path);
        
        if ($schema1 === $schema2) {
            echo "Schemas são idênticos.\n";
            return true;
        }
        
        echo "Schemas são diferentes.\n";
        
        // Análise básica de diferenças
        $tables1 = self::extractTableNames($schema1);
        $tables2 = self::extractTableNames($schema2);
        
        $missing1 = array_diff($tables2, $tables1);
        $missing2 = array_diff($tables1, $tables2);
        
        if (!empty($missing1)) {
            echo "Tabelas em schema2 mas não em schema1: " . implode(', ', $missing1) . "\n";
        }
        
        if (!empty($missing2)) {
            echo "Tabelas em schema1 mas não em schema2: " . implode(', ', $missing2) . "\n";
        }
        
        return false;
    }
    
    private static function extractTableNames($schema) {
        preg_match_all('/CREATE TABLE `?(\w+)`?/i', $schema, $matches);
        return $matches[1];
    }
}
```

---

## ↩️ Rollback e Recovery

### **Sistema de Rollback Seguro**

```php
class RollbackManager {
    private $connection;
    private $backupPath;
    
    public function __construct($backupPath = 'backups/migrations') {
        $this->connection = ActiveRecord\Connection::instance();
        $this->backupPath = $backupPath;
        
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }
    
    public function createPreMigrationBackup($migrationVersion) {
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = "{$this->backupPath}/pre_migration_{$migrationVersion}_{$timestamp}.sql";
        
        echo "Criando backup antes da migração...\n";
        
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s --single-transaction %s > %s',
            escapeshellarg($_ENV['DB_HOST']),
            escapeshellarg($_ENV['DB_USER']),
            escapeshellarg($_ENV['DB_PASS']),
            escapeshellarg($_ENV['DB_NAME']),
            escapeshellarg($backupFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Falha ao criar backup: " . implode("\n", $output));
        }
        
        echo "Backup criado: $backupFile\n";
        return $backupFile;
    }
    
    public function rollbackToBackup($backupFile) {
        if (!file_exists($backupFile)) {
            throw new Exception("Arquivo de backup não encontrado: $backupFile");
        }
        
        echo "Restaurando backup: $backupFile\n";
        
        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s',
            escapeshellarg($_ENV['DB_HOST']),
            escapeshellarg($_ENV['DB_USER']),
            escapeshellarg($_ENV['DB_PASS']),
            escapeshellarg($_ENV['DB_NAME']),
            escapeshellarg($backupFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Falha ao restaurar backup: " . implode("\n", $output));
        }
        
        echo "Backup restaurado com sucesso!\n";
    }
    
    public function smartRollback($targetVersion) {
        $currentVersion = SchemaVersioning::getCurrentVersion();
        
        if ($targetVersion >= $currentVersion) {
            echo "Versão alvo deve ser menor que a atual.\n";
            return;
        }
        
        echo "Rollback inteligente da versão $currentVersion para $targetVersion\n";
        
        // Verificar se existem backups disponíveis
        $availableBackups = $this->getAvailableBackups();
        $suitableBackup = null;
        
        foreach ($availableBackups as $backup) {
            if ($this->getBackupVersion($backup) === $targetVersion) {
                $suitableBackup = $backup;
                break;
            }
        }
        
        if ($suitableBackup) {
            echo "Backup encontrado para versão $targetVersion\n";
            $this->rollbackToBackup($suitableBackup);
        } else {
            echo "Nenhum backup encontrado, usando rollback por migrações\n";
            $manager = new MigrationManager();
            
            $migrationVersions = $this->getMigrationsBetween($targetVersion, $currentVersion);
            $stepsToRollback = count($migrationVersions);
            
            $manager->rollback($stepsToRollback);
        }
    }
    
    private function getAvailableBackups() {
        return glob($this->backupPath . '/pre_migration_*.sql');
    }
    
    private function getBackupVersion($backupFile) {
        $filename = basename($backupFile);
        if (preg_match('/pre_migration_(\d+)_/', $filename, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    private function getMigrationsBetween($startVersion, $endVersion) {
        $result = $this->connection->query("
            SELECT version FROM schema_migrations 
            WHERE version > ? AND version <= ?
            ORDER BY version DESC
        ", [$startVersion, $endVersion]);
        
        return array_column($result, 'version');
    }
    
    public function validateRollback($migrationClass) {
        echo "Validando rollback da migração: $migrationClass\n";
        
        try {
            // Executar em uma transação de teste
            $this->connection->transaction(function() use ($migrationClass) {
                $instance = new $migrationClass();
                
                // Testar up e down
                $instance->up();
                $instance->down();
                
                // Forçar rollback da transação
                throw new Exception("Teste concluído");
            });
        } catch (Exception $e) {
            if ($e->getMessage() === "Teste concluído") {
                echo "✅ Rollback validado com sucesso\n";
                return true;
            } else {
                echo "❌ Erro na validação: " . $e->getMessage() . "\n";
                return false;
            }
        }
    }
}
```

---

## 📦 Migrações de Dados

### **Migração de Dados Complexa**

```php
// migrations/20250101120000_migrate_user_profiles.php
class MigrateUserProfiles extends Migration {
    
    public function up() {
        echo "Migrando dados de perfis de usuário...\n";
        
        // Criar nova tabela
        $this->createTable('user_profiles_new', [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'INT NOT NULL',
            'first_name' => 'VARCHAR(50)',
            'last_name' => 'VARCHAR(50)', 
            'bio' => 'TEXT',
            'avatar_url' => 'VARCHAR(255)',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ], [
            'ENGINE=InnoDB',
            'CHARACTER SET utf8mb4',
            'COLLATE utf8mb4_unicode_ci'
        ]);
        
        // Migrar dados em lotes
        $this->migrateProfileData();
        
        // Renomear tabelas
        $this->execute("RENAME TABLE user_profiles TO user_profiles_old");
        $this->execute("RENAME TABLE user_profiles_new TO user_profiles");
        
        // Adicionar índices
        $this->addIndex('user_profiles', 'idx_user_id', 'user_id');
        $this->addForeignKey('user_profiles', 'fk_profile_user', 'user_id', 'users', 'id');
        
        echo "Migração de dados concluída!\n";
    }
    
    public function down() {
        // Restaurar tabela original
        $this->execute("DROP TABLE IF EXISTS user_profiles");
        $this->execute("RENAME TABLE user_profiles_old TO user_profiles");
    }
    
    private function migrateProfileData() {
        $batchSize = 1000;
        $offset = 0;
        
        do {
            // Buscar lote de dados
            $profiles = $this->connection->query("
                SELECT 
                    user_id,
                    SUBSTRING_INDEX(full_name, ' ', 1) as first_name,
                    SUBSTRING_INDEX(full_name, ' ', -1) as last_name,
                    bio,
                    avatar_url,
                    created_at,
                    updated_at
                FROM user_profiles_old 
                LIMIT $batchSize OFFSET $offset
            ");
            
            if (empty($profiles)) {
                break;
            }
            
            // Inserir dados migrados
            foreach ($profiles as $profile) {
                $this->connection->query("
                    INSERT INTO user_profiles_new 
                    (user_id, first_name, last_name, bio, avatar_url, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ", [
                    $profile['user_id'],
                    $profile['first_name'],
                    $profile['last_name'] !== $profile['first_name'] ? $profile['last_name'] : '',
                    $profile['bio'],
                    $profile['avatar_url'],
                    $profile['created_at'],
                    $profile['updated_at']
                ]);
            }
            
            $offset += $batchSize;
            echo "Migrado $offset registros...\n";
            
        } while (count($profiles) === $batchSize);
    }
}

// Migração com validação de dados
class ValidatedDataMigration extends Migration {
    
    public function up() {
        $this->validateDataBeforeMigration();
        $this->performMigration();
        $this->validateDataAfterMigration();
    }
    
    private function validateDataBeforeMigration() {
        echo "Validando dados antes da migração...\n";
        
        // Verificar duplicatas
        $duplicates = $this->connection->query("
            SELECT email, COUNT(*) as count 
            FROM users 
            GROUP BY email 
            HAVING count > 1
        ");
        
        if (!empty($duplicates)) {
            throw new Exception("Emails duplicados encontrados: " . count($duplicates));
        }
        
        // Verificar dados inválidos
        $invalidEmails = $this->connection->query("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'
        ");
        
        if ($invalidEmails[0]['count'] > 0) {
            throw new Exception("Emails inválidos encontrados: " . $invalidEmails[0]['count']);
        }
        
        echo "✅ Validação de dados aprovada\n";
    }
    
    private function performMigration() {
        // Implementar migração aqui
    }
    
    private function validateDataAfterMigration() {
        echo "Validando dados após migração...\n";
        
        // Verificar integridade referencial
        $orphanedRecords = $this->connection->query("
            SELECT COUNT(*) as count 
            FROM posts p 
            LEFT JOIN users u ON p.user_id = u.id 
            WHERE u.id IS NULL
        ");
        
        if ($orphanedRecords[0]['count'] > 0) {
            throw new Exception("Registros órfãos encontrados: " . $orphanedRecords[0]['count']);
        }
        
        echo "✅ Validação pós-migração aprovada\n";
    }
}
```

---

## ⚡ Estratégias Zero-Downtime

### **Deploy sem Interrupção**

```php
class ZeroDowntimeMigration extends Migration {
    
    public function up() {
        // Estratégia 1: Adicionar coluna com valor padrão
        $this->addColumnWithDefault();
        
        // Estratégia 2: Migração em fases
        $this->phaseOneMigration();
        
        // Dar tempo para aplicação atualizar
        echo "Aguarde a aplicação ser atualizada antes de executar a fase 2...\n";
    }
    
    private function addColumnWithDefault() {
        // Adicionar nova coluna sem quebrar aplicação existente
        $this->addColumn('users', 'status', 'VARCHAR(20) DEFAULT "active"');
        
        // Popular coluna com dados baseados na lógica existente
        $this->execute("
            UPDATE users 
            SET status = CASE 
                WHEN is_deleted = 1 THEN 'deleted'
                WHEN email_verified = 0 THEN 'pending'
                ELSE 'active'
            END
        ");
    }
    
    private function phaseOneMigration() {
        // Criar nova tabela paralela
        $this->createTable('posts_v2', [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'title' => 'VARCHAR(200) NOT NULL',
            'content' => 'TEXT',
            'user_id' => 'INT NOT NULL',
            'category_id' => 'INT',
            'status' => 'ENUM("draft", "published", "archived") DEFAULT "draft"',
            'slug' => 'VARCHAR(200) UNIQUE',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ]);
        
        // Copiar dados existentes
        $this->execute("
            INSERT INTO posts_v2 (id, title, content, user_id, category_id, status, created_at, updated_at)
            SELECT id, title, content, user_id, category_id, 
                   CASE WHEN is_published = 1 THEN 'published' ELSE 'draft' END,
                   created_at, updated_at
            FROM posts
        ");
        
        // Criar trigger para manter sincronização
        $this->createSyncTriggers();
    }
    
    private function createSyncTriggers() {
        // Trigger para INSERT
        $this->execute("
            CREATE TRIGGER posts_insert_sync 
            AFTER INSERT ON posts 
            FOR EACH ROW 
            INSERT INTO posts_v2 (id, title, content, user_id, category_id, status, created_at, updated_at)
            VALUES (NEW.id, NEW.title, NEW.content, NEW.user_id, NEW.category_id, 
                   CASE WHEN NEW.is_published = 1 THEN 'published' ELSE 'draft' END,
                   NEW.created_at, NEW.updated_at)
        ");
        
        // Trigger para UPDATE
        $this->execute("
            CREATE TRIGGER posts_update_sync 
            AFTER UPDATE ON posts 
            FOR EACH ROW 
            UPDATE posts_v2 SET 
                title = NEW.title,
                content = NEW.content,
                user_id = NEW.user_id,
                category_id = NEW.category_id,
                status = CASE WHEN NEW.is_published = 1 THEN 'published' ELSE 'draft' END,
                updated_at = NEW.updated_at
            WHERE id = NEW.id
        ");
        
        // Trigger para DELETE
        $this->execute("
            CREATE TRIGGER posts_delete_sync 
            AFTER DELETE ON posts 
            FOR EACH ROW 
            DELETE FROM posts_v2 WHERE id = OLD.id
        ");
    }
    
    public function phaseTwo() {
        // Executar após aplicação atualizada
        echo "Executando fase 2 da migração...\n";
        
        // Remover triggers
        $this->execute("DROP TRIGGER IF EXISTS posts_insert_sync");
        $this->execute("DROP TRIGGER IF EXISTS posts_update_sync");
        $this->execute("DROP TRIGGER IF EXISTS posts_delete_sync");
        
        // Renomear tabelas
        $this->execute("RENAME TABLE posts TO posts_old");
        $this->execute("RENAME TABLE posts_v2 TO posts");
        
        // Adicionar índices finais
        $this->addIndex('posts', 'idx_user_id', 'user_id');
        $this->addIndex('posts', 'idx_category_id', 'category_id');
        $this->addIndex('posts', 'idx_status', 'status');
        $this->addIndex('posts', 'idx_slug', 'slug');
        
        echo "Fase 2 concluída!\n";
    }
    
    public function down() {
        // Reverter mudanças
        $this->execute("DROP TRIGGER IF EXISTS posts_insert_sync");
        $this->execute("DROP TRIGGER IF EXISTS posts_update_sync");
        $this->execute("DROP TRIGGER IF EXISTS posts_delete_sync");
        
        $this->execute("DROP TABLE IF EXISTS posts_v2");
        $this->dropColumn('users', 'status');
    }
}
```

---

## 🌍 Ambientes e Deploy

### **Configuração Multi-Ambiente**

```php
class EnvironmentMigration {
    private $environment;
    private $environments = ['development', 'staging', 'production'];
    
    public function __construct($environment = null) {
        $this->environment = $environment ?? ($_ENV['APP_ENV'] ?? 'development');
    }
    
    public function deployToEnvironment($targetEnvironment, $version = null) {
        if (!in_array($targetEnvironment, $this->environments)) {
            throw new Exception("Ambiente inválido: $targetEnvironment");
        }
        
        echo "Fazendo deploy para: $targetEnvironment\n";
        
        // Configurar conexão para ambiente alvo
        $this->switchToEnvironment($targetEnvironment);
        
        // Validações específicas por ambiente
        $this->validateEnvironment($targetEnvironment);
        
        // Executar migrações
        $manager = new MigrationManager();
        
        if ($targetEnvironment === 'production') {
            // Produção: backup automático
            $backup = new RollbackManager();
            $backupFile = $backup->createPreMigrationBackup($version ?? 'deploy');
            
            try {
                $manager->migrate($version);
                echo "Deploy para produção concluído!\n";
            } catch (Exception $e) {
                echo "Erro no deploy, restaurando backup...\n";
                $backup->rollbackToBackup($backupFile);
                throw $e;
            }
        } else {
            // Desenvolvimento/Staging: execução normal
            $manager->migrate($version);
        }
    }
    
    private function switchToEnvironment($environment) {
        $configs = [
            'development' => [
                'dsn' => 'mysql://root:@localhost/app_dev',
            ],
            'staging' => [
                'dsn' => 'mysql://staging_user:staging_pass@staging-db/app_staging',
            ],
            'production' => [
                'dsn' => 'mysql://prod_user:prod_pass@prod-db/app_production',
            ]
        ];
        
        ActiveRecord\Config::initialize(function($cfg) use ($configs, $environment) {
            $cfg->set_connections([
                'default' => $configs[$environment]['dsn']
            ]);
        });
    }
    
    private function validateEnvironment($environment) {
        switch ($environment) {
            case 'production':
                $this->validateProduction();
                break;
            case 'staging':
                $this->validateStaging();
                break;
            case 'development':
                $this->validateDevelopment();
                break;
        }
    }
    
    private function validateProduction() {
        // Verificações rigorosas para produção
        $connection = ActiveRecord\Connection::instance();
        
        // Verificar se backup está funcionando
        if (!$this->testBackupSystem()) {
            throw new Exception("Sistema de backup não está funcionando");
        }
        
        // Verificar espaço em disco
        $freeSpace = disk_free_space('/');
        $requiredSpace = 1024 * 1024 * 1024; // 1GB
        
        if ($freeSpace < $requiredSpace) {
            throw new Exception("Espaço em disco insuficiente");
        }
        
        // Verificar se há transações ativas
        $activeTransactions = $connection->query("SHOW PROCESSLIST");
        $longRunning = array_filter($activeTransactions, function($process) {
            return $process['Time'] > 300; // 5 minutos
        });
        
        if (!empty($longRunning)) {
            throw new Exception("Transações de longa duração detectadas");
        }
        
        echo "✅ Validações de produção aprovadas\n";
    }
    
    private function validateStaging() {
        echo "✅ Validações de staging aprovadas\n";
    }
    
    private function validateDevelopment() {
        echo "✅ Validações de desenvolvimento aprovadas\n";
    }
    
    private function testBackupSystem() {
        try {
            $backup = new RollbackManager();
            $testFile = $backup->createPreMigrationBackup('test');
            
            if (file_exists($testFile) && filesize($testFile) > 0) {
                unlink($testFile);
                return true;
            }
        } catch (Exception $e) {
            return false;
        }
        
        return false;
    }
    
    public function syncSchemas($sourceEnv, $targetEnv) {
        echo "Sincronizando schema de $sourceEnv para $targetEnv\n";
        
        // Exportar schema do ambiente fonte
        $this->switchToEnvironment($sourceEnv);
        SchemaVersioning::generateSchemaFile("/tmp/schema_$sourceEnv.sql");
        
        // Importar no ambiente alvo
        $this->switchToEnvironment($targetEnv);
        
        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s',
            $_ENV['DB_HOST'],
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'],
            $_ENV['DB_NAME'],
            "/tmp/schema_$sourceEnv.sql"
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Erro na sincronização: " . implode("\n", $output));
        }
        
        echo "Schema sincronizado com sucesso!\n";
    }
}
```

---

## 🧪 Testes de Migrações

### **Framework de Testes**

```php
class MigrationTester {
    private $testConnection;
    private $originalConnection;
    
    public function setUp() {
        // Configurar banco de teste
        $this->setupTestDatabase();
        
        // Salvar conexão original
        $this->originalConnection = ActiveRecord\Connection::instance();
        
        // Configurar conexão de teste
        ActiveRecord\Config::initialize(function($cfg) {
            $cfg->set_connections([
                'test' => 'mysql://root:@localhost/migration_test'
            ]);
            $cfg->set_default_connection('test');
        });
        
        $this->testConnection = ActiveRecord\Connection::instance();
    }
    
    public function tearDown() {
        // Limpar banco de teste
        $this->testConnection->query("DROP DATABASE migration_test");
        
        // Restaurar conexão original
        ActiveRecord\Config::initialize(function($cfg) {
            $cfg->set_default_connection('development');
        });
    }
    
    private function setupTestDatabase() {
        $connection = new PDO('mysql://root:@localhost');
        $connection->query("DROP DATABASE IF EXISTS migration_test");
        $connection->query("CREATE DATABASE migration_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    
    public function testMigration($migrationClass) {
        echo "Testando migração: $migrationClass\n";
        
        try {
            // Testar UP
            $this->testUp($migrationClass);
            
            // Testar DOWN
            $this->testDown($migrationClass);
            
            // Testar UP novamente
            $this->testUp($migrationClass);
            
            echo "✅ Teste da migração $migrationClass passou\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Teste da migração $migrationClass falhou: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function testUp($migrationClass) {
        $migration = new $migrationClass();
        
        // Capturar estado antes
        $beforeTables = $this->getTables();
        
        // Executar UP
        $migration->up();
        
        // Verificar mudanças
        $afterTables = $this->getTables();
        
        if (count($afterTables) <= count($beforeTables)) {
            throw new Exception("Migração UP não criou novas tabelas");
        }
    }
    
    private function testDown($migrationClass) {
        $migration = new $migrationClass();
        
        // Capturar estado antes
        $beforeTables = $this->getTables();
        
        // Executar DOWN
        $migration->down();
        
        // Verificar rollback
        $afterTables = $this->getTables();
        
        if (count($afterTables) >= count($beforeTables)) {
            throw new Exception("Migração DOWN não removeu tabelas");
        }
    }
    
    private function getTables() {
        $result = $this->testConnection->query("SHOW TABLES");
        return array_column($result, 'Tables_in_migration_test');
    }
    
    public function testAllMigrations() {
        $this->setUp();
        
        $migrations = glob('migrations/*.php');
        $passed = 0;
        $failed = 0;
        
        foreach ($migrations as $file) {
            require_once $file;
            
            $content = file_get_contents($file);
            if (preg_match('/class\s+(\w+)/', $content, $matches)) {
                $className = $matches[1];
                
                if ($this->testMigration($className)) {
                    $passed++;
                } else {
                    $failed++;
                }
                
                // Reset do banco para próximo teste
                $this->tearDown();
                $this->setUp();
            }
        }
        
        $this->tearDown();
        
        echo "\n=== Resultado dos Testes ===\n";
        echo "Passou: $passed\n";
        echo "Falhou: $failed\n";
        echo "Total: " . ($passed + $failed) . "\n";
        
        return $failed === 0;
    }
    
    public function benchmarkMigration($migrationClass, $iterations = 5) {
        echo "Fazendo benchmark da migração: $migrationClass\n";
        
        $times = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $this->setUp();
            
            $start = microtime(true);
            
            $migration = new $migrationClass();
            $migration->up();
            $migration->down();
            
            $end = microtime(true);
            $times[] = $end - $start;
            
            $this->tearDown();
        }
        
        $avgTime = array_sum($times) / count($times);
        $minTime = min($times);
        $maxTime = max($times);
        
        echo "Tempo médio: " . round($avgTime, 4) . "s\n";
        echo "Tempo mínimo: " . round($minTime, 4) . "s\n";
        echo "Tempo máximo: " . round($maxTime, 4) . "s\n";
        
        return [
            'average' => $avgTime,
            'min' => $minTime,
            'max' => $maxTime,
            'iterations' => $iterations
        ];
    }
}

// CLI para executar testes
class MigrationTestCLI {
    
    public static function run($args) {
        $tester = new MigrationTester();
        
        if (empty($args)) {
            echo "Uso: php test_migrations.php [test_all|test_migration <class>|benchmark <class>]\n";
            return;
        }
        
        $command = $args[0];
        
        switch ($command) {
            case 'test_all':
                $tester->testAllMigrations();
                break;
                
            case 'test_migration':
                if (isset($args[1])) {
                    $tester->setUp();
                    $tester->testMigration($args[1]);
                    $tester->tearDown();
                } else {
                    echo "Especifique o nome da classe da migração\n";
                }
                break;
                
            case 'benchmark':
                if (isset($args[1])) {
                    $iterations = isset($args[2]) ? (int)$args[2] : 5;
                    $tester->benchmarkMigration($args[1], $iterations);
                } else {
                    echo "Especifique o nome da classe da migração\n";
                }
                break;
                
            default:
                echo "Comando desconhecido: $command\n";
        }
    }
}
```

---

## 🤖 Automação e CI/CD

### **Integração com CI/CD**

```php
// scripts/ci_migrations.php
class CIMigrations {
    
    public static function validateMigrations() {
        echo "Validando migrações para CI/CD...\n";
        
        $errors = [];
        
        // Verificar sintaxe das migrações
        $syntaxErrors = self::checkSyntax();
        if (!empty($syntaxErrors)) {
            $errors = array_merge($errors, $syntaxErrors);
        }
        
        // Testar migrações
        $testErrors = self::runTests();
        if (!empty($testErrors)) {
            $errors = array_merge($errors, $testErrors);
        }
        
        // Verificar conflitos
        $conflictErrors = self::checkConflicts();
        if (!empty($conflictErrors)) {
            $errors = array_merge($errors, $conflictErrors);
        }
        
        if (!empty($errors)) {
            echo "❌ Validação falhou:\n";
            foreach ($errors as $error) {
                echo "  - $error\n";
            }
            exit(1);
        }
        
        echo "✅ Todas as validações passaram\n";
    }
    
    private static function checkSyntax() {
        $errors = [];
        $migrations = glob('migrations/*.php');
        
        foreach ($migrations as $file) {
            $output = [];
            $returnCode = 0;
            
            exec("php -l $file", $output, $returnCode);
            
            if ($returnCode !== 0) {
                $errors[] = "Erro de sintaxe em $file: " . implode("\n", $output);
            }
        }
        
        return $errors;
    }
    
    private static function runTests() {
        $tester = new MigrationTester();
        
        if (!$tester->testAllMigrations()) {
            return ["Alguns testes de migração falharam"];
        }
        
        return [];
    }
    
    private static function checkConflicts() {
        $errors = [];
        $migrations = glob('migrations/*.php');
        $versions = [];
        
        foreach ($migrations as $file) {
            $filename = basename($file);
            if (preg_match('/^(\d+)_/', $filename, $matches)) {
                $version = $matches[1];
                
                if (isset($versions[$version])) {
                    $errors[] = "Conflito de versão: $version está duplicada";
                }
                
                $versions[$version] = $file;
            }
        }
        
        return $errors;
    }
    
    public static function generateDeployScript($environment) {
        $script = "#!/bin/bash\n";
        $script .= "# Script de deploy gerado automaticamente\n\n";
        
        $script .= "set -e\n\n";
        
        $script .= "echo 'Iniciando deploy de migrações...'\n\n";
        
        if ($environment === 'production') {
            $script .= "# Backup antes da migração\n";
            $script .= "php scripts/backup.php\n\n";
        }
        
        $script .= "# Executar migrações\n";
        $script .= "php scripts/migrate.php\n\n";
        
        $script .= "# Verificar integridade\n";
        $script .= "php scripts/verify_integrity.php\n\n";
        
        $script .= "echo 'Deploy concluído com sucesso!'\n";
        
        $filename = "deploy_$environment.sh";
        file_put_contents($filename, $script);
        chmod($filename, 0755);
        
        echo "Script de deploy gerado: $filename\n";
        return $filename;
    }
    
    public static function deployEnvironment($environment) {
        echo "Fazendo deploy para $environment...\n";
        
        // Validar antes do deploy
        self::validateMigrations();
        
        // Gerar e executar script
        $script = self::generateDeployScript($environment);
        
        exec("./$script", $output, $returnCode);
        
        if ($returnCode !== 0) {
            echo "❌ Deploy falhou:\n";
            echo implode("\n", $output);
            exit(1);
        }
        
        echo "✅ Deploy para $environment concluído\n";
    }
}

// GitHub Actions workflow example
/*
# .github/workflows/migrations.yml
name: Database Migrations

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  validate-migrations:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: test
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.0'
        extensions: pdo, pdo_mysql
    
    - name: Install dependencies
      run: composer install
    
    - name: Validate migrations
      run: php scripts/ci_migrations.php validate
      
    - name: Run migration tests
      run: php scripts/ci_migrations.php test_all
      env:
        DB_HOST: 127.0.0.1
        DB_USER: root
        DB_PASS: root
        DB_NAME: test

  deploy-staging:
    needs: validate-migrations
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/develop'
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Deploy to staging
      run: php scripts/ci_migrations.php deploy staging
      env:
        DB_HOST: ${{ secrets.STAGING_DB_HOST }}
        DB_USER: ${{ secrets.STAGING_DB_USER }}
        DB_PASS: ${{ secrets.STAGING_DB_PASS }}
        DB_NAME: ${{ secrets.STAGING_DB_NAME }}

  deploy-production:
    needs: validate-migrations
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Deploy to production
      run: php scripts/ci_migrations.php deploy production
      env:
        DB_HOST: ${{ secrets.PROD_DB_HOST }}
        DB_USER: ${{ secrets.PROD_DB_USER }}
        DB_PASS: ${{ secrets.PROD_DB_PASS }}
        DB_NAME: ${{ secrets.PROD_DB_NAME }}
*/
```

---

## 💡 Resumo de Boas Práticas

### ✅ **Fazer**
- Sempre criar migrações para mudanças de schema
- Testar migrações em ambiente de desenvolvimento
- Fazer backup antes de migrações em produção
- Usar transações para operações atômicas
- Validar dados antes e depois das migrações
- Documentar mudanças complexas
- Implementar rollback para todas as migrações
- Usar versionamento sequencial
- Testar migrações automaticamente no CI/CD

### ❌ **Evitar**
- Editar migrações já executadas
- Fazer mudanças diretas no banco de produção
- Migrações sem rollback
- Operações que bloqueiam por muito tempo
- Migrações de dados sem validação
- Deploy sem backup
- Ignorar erros de migração
- Versões de migração duplicadas

### 🎯 **Checklist de Migração**
- [ ] Migração testada localmente
- [ ] Rollback implementado e testado
- [ ] Backup automático configurado
- [ ] Validação de dados implementada
- [ ] Documentação atualizada
- [ ] CI/CD validando migrações
- [ ] Estratégia zero-downtime para produção
- [ ] Monitoramento de performance
- [ ] Plan de contingência preparado

---

**📝 Nota:** As migrações são uma ferramenta poderosa mas devem ser usadas com cuidado. Sempre teste em ambientes não críticos antes de aplicar em produção!
