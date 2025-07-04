# Guia de Backup e Recuperação - MySQL, PHP & ActiveRecord

## Índice
- [Estratégias de Backup](#estratégias-de-backup)
- [Backup Automatizado](#backup-automatizado)
- [Recuperação de Dados](#recuperação-de-dados)
- [Backup Incremental](#backup-incremental)
- [Sincronização e Replicação](#sincronização-e-replicação)
- [Validação de Backups](#validação-de-backups)
- [Disaster Recovery](#disaster-recovery)
- [Scripts Utilitários](#scripts-utilitários)
- [Monitoramento](#monitoramento)
- [Checklist](#checklist)

## Estratégias de Backup

### 1. Tipos de Backup

```sql
-- Backup completo
mysqldump --user=root --password --single-transaction --routines --triggers --all-databases > backup_completo.sql

-- Backup de uma base específica
mysqldump --user=root --password --single-transaction --routines --triggers minha_app > backup_app.sql

-- Backup apenas estrutura
mysqldump --user=root --password --no-data minha_app > estrutura_app.sql

-- Backup apenas dados
mysqldump --user=root --password --no-create-info minha_app > dados_app.sql

-- Backup de tabelas específicas
mysqldump --user=root --password minha_app users posts comments > backup_principais.sql
```

### 2. Backup com Compressão

```bash
# Windows PowerShell
mysqldump --user=root --password --single-transaction --routines --triggers minha_app | gzip > backup_$(Get-Date -Format "yyyyMMdd_HHmmss").sql.gz

# Linux/Mac
mysqldump --user=root --password --single-transaction --routines --triggers minha_app | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

### 3. Configuração PHP para Backup

```php
<?php
class DatabaseBackup {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function createBackup($databases = null) {
        $timestamp = date('Y-m-d_H-i-s');
        $backupDir = $this->config['backup_dir'] ?? './backups';
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $databases = $databases ?? $this->config['databases'];
        
        foreach ($databases as $database) {
            $filename = "{$backupDir}/backup_{$database}_{$timestamp}.sql";
            $command = $this->buildMysqldumpCommand($database, $filename);
            
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                $this->compressBackup($filename);
                $this->logBackup($database, $filename, true);
            } else {
                $this->logBackup($database, $filename, false, implode("\n", $output));
            }
        }
    }
    
    private function buildMysqldumpCommand($database, $filename) {
        $host = $this->config['host'];
        $user = $this->config['user'];
        $password = $this->config['password'];
        
        return "mysqldump --host={$host} --user={$user} --password={$password} " .
               "--single-transaction --routines --triggers {$database} > {$filename}";
    }
    
    private function compressBackup($filename) {
        if (file_exists($filename)) {
            $compressed = $filename . '.gz';
            $data = file_get_contents($filename);
            file_put_contents($compressed, gzcompress($data, 9));
            unlink($filename);
        }
    }
    
    private function logBackup($database, $filename, $success, $error = null) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => $database,
            'filename' => $filename,
            'success' => $success,
            'size' => file_exists($filename . '.gz') ? filesize($filename . '.gz') : 0,
            'error' => $error
        ];
        
        file_put_contents(
            $this->config['log_file'] ?? './backup.log',
            json_encode($logEntry) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
    
    public function cleanOldBackups($daysToKeep = 7) {
        $backupDir = $this->config['backup_dir'] ?? './backups';
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        
        $files = glob($backupDir . '/backup_*.sql.gz');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $this->logBackup('cleanup', $file, true);
            }
        }
    }
}

// Uso
$config = [
    'host' => 'localhost',
    'user' => 'backup_user',
    'password' => 'senha_segura',
    'databases' => ['minha_app', 'sistema_logs'],
    'backup_dir' => './backups',
    'log_file' => './backup.log'
];

$backup = new DatabaseBackup($config);
$backup->createBackup();
$backup->cleanOldBackups(30); // Manter por 30 dias
?>
```

## Backup Automatizado

### 1. Script de Backup Agendado

```php
<?php
// backup_scheduler.php
class BackupScheduler {
    private $config;
    private $lockFile;
    
    public function __construct($config) {
        $this->config = $config;
        $this->lockFile = $config['lock_file'] ?? './backup.lock';
    }
    
    public function run() {
        if ($this->isRunning()) {
            $this->log("Backup já está em execução");
            return false;
        }
        
        $this->createLock();
        
        try {
            $this->performBackup();
            $this->notifySuccess();
        } catch (Exception $e) {
            $this->notifyError($e->getMessage());
        } finally {
            $this->removeLock();
        }
    }
    
    private function performBackup() {
        $backup = new DatabaseBackup($this->config);
        
        // Backup completo
        $backup->createBackup();
        
        // Backup diferencial (se configurado)
        if ($this->config['differential_backup']) {
            $this->createDifferentialBackup();
        }
        
        // Sincronizar com armazenamento remoto
        if ($this->config['remote_sync']) {
            $this->syncToRemote();
        }
        
        // Limpar backups antigos
        $backup->cleanOldBackups($this->config['retention_days'] ?? 30);
    }
    
    private function createDifferentialBackup() {
        $lastFullBackup = $this->getLastFullBackupTime();
        $binlogDir = $this->config['binlog_dir'] ?? '/var/log/mysql';
        
        $command = "mysqlbinlog --start-datetime='{$lastFullBackup}' {$binlogDir}/mysql-bin.* > " .
                   $this->config['backup_dir'] . "/differential_" . date('Y-m-d_H-i-s') . ".sql";
        
        exec($command);
    }
    
    private function syncToRemote() {
        $remoteConfig = $this->config['remote'];
        $backupDir = $this->config['backup_dir'];
        
        switch ($remoteConfig['type']) {
            case 'ftp':
                $this->syncToFTP($backupDir, $remoteConfig);
                break;
            case 's3':
                $this->syncToS3($backupDir, $remoteConfig);
                break;
            case 'rsync':
                $this->syncWithRsync($backupDir, $remoteConfig);
                break;
        }
    }
    
    private function syncToFTP($localDir, $config) {
        $conn = ftp_connect($config['host']);
        ftp_login($conn, $config['user'], $config['password']);
        
        $files = glob($localDir . '/backup_*.gz');
        foreach ($files as $file) {
            $remoteName = $config['remote_dir'] . '/' . basename($file);
            ftp_put($conn, $remoteName, $file, FTP_BINARY);
        }
        
        ftp_close($conn);
    }
    
    private function notifySuccess() {
        $this->sendNotification('Backup realizado com sucesso', 'success');
    }
    
    private function notifyError($message) {
        $this->sendNotification("Erro no backup: {$message}", 'error');
    }
    
    private function sendNotification($message, $type) {
        if (isset($this->config['notifications']['email'])) {
            $this->sendEmail($message, $type);
        }
        
        if (isset($this->config['notifications']['slack'])) {
            $this->sendSlackMessage($message, $type);
        }
        
        $this->log($message);
    }
    
    private function isRunning() {
        return file_exists($this->lockFile);
    }
    
    private function createLock() {
        file_put_contents($this->lockFile, getmypid());
    }
    
    private function removeLock() {
        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }
    
    private function log($message) {
        error_log("[" . date('Y-m-d H:i:s') . "] {$message}\n", 3, $this->config['log_file']);
    }
}
?>
```

### 2. Configuração de Cron/Task Scheduler

```bash
# Linux Crontab
# Backup diário às 02:00
0 2 * * * /usr/bin/php /path/to/backup_scheduler.php

# Backup semanal completo aos domingos às 01:00
0 1 * * 0 /usr/bin/php /path/to/full_backup.php

# Windows Task Scheduler (PowerShell)
Register-ScheduledTask -TaskName "MySQL Backup" -Trigger (New-ScheduledTaskTrigger -Daily -At 2:00AM) -Action (New-ScheduledTaskAction -Execute "php.exe" -Argument "C:\path\to\backup_scheduler.php")
```

## Recuperação de Dados

### 1. Restauração Completa

```php
<?php
class DatabaseRestore {
    private $config;
    
    public function restoreFromBackup($backupFile, $targetDatabase = null) {
        $this->validateBackupFile($backupFile);
        
        // Decomprimir se necessário
        if (pathinfo($backupFile, PATHINFO_EXTENSION) === 'gz') {
            $backupFile = $this->decompressBackup($backupFile);
        }
        
        // Criar database se especificado
        if ($targetDatabase) {
            $this->createDatabase($targetDatabase);
        }
        
        // Executar restauração
        $command = $this->buildRestoreCommand($backupFile, $targetDatabase);
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->log("Restauração concluída com sucesso: {$backupFile}");
            return true;
        } else {
            $this->log("Erro na restauração: " . implode("\n", $output));
            return false;
        }
    }
    
    private function buildRestoreCommand($backupFile, $database = null) {
        $host = $this->config['host'];
        $user = $this->config['user'];
        $password = $this->config['password'];
        
        $dbParam = $database ? $database : '';
        
        return "mysql --host={$host} --user={$user} --password={$password} {$dbParam} < {$backupFile}";
    }
    
    public function restoreTable($backupFile, $tableName, $targetDatabase) {
        // Extrair apenas a tabela específica
        $tempFile = tempnam(sys_get_temp_dir(), 'table_restore_');
        
        $command = "grep -A 1000000 'CREATE TABLE.*{$tableName}' {$backupFile} | " .
                   "grep -B 1000000 'CREATE TABLE' | head -n -1 > {$tempFile}";
        
        exec($command);
        
        return $this->restoreFromBackup($tempFile, $targetDatabase);
    }
    
    public function pointInTimeRecovery($fullBackupFile, $binlogFiles, $stopDateTime) {
        // Restaurar backup completo
        $this->restoreFromBackup($fullBackupFile);
        
        // Aplicar logs binários até o ponto especificado
        foreach ($binlogFiles as $binlogFile) {
            $command = "mysqlbinlog --stop-datetime='{$stopDateTime}' {$binlogFile} | " .
                       "mysql --host={$this->config['host']} --user={$this->config['user']} " .
                       "--password={$this->config['password']}";
            
            exec($command);
        }
    }
}
?>
```

### 2. Recuperação com ActiveRecord

```php
<?php
// Utilitário para recuperação específica com ActiveRecord
class ActiveRecordRecovery {
    
    public static function recoverDeletedRecords($modelClass, $deletedSince) {
        // Assumindo soft deletes
        $connection = ActiveRecord\ConnectionManager::get_connection();
        
        $tableName = $modelClass::table_name();
        $sql = "SELECT * FROM {$tableName} WHERE deleted_at >= ?";
        
        $results = $connection->query($sql, [$deletedSince]);
        
        $recoveredRecords = [];
        foreach ($results as $row) {
            $record = new $modelClass($row);
            $record->deleted_at = null;
            if ($record->save()) {
                $recoveredRecords[] = $record;
            }
        }
        
        return $recoveredRecords;
    }
    
    public static function createEmergencyBackup($modelClass) {
        $records = $modelClass::all();
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "./emergency_backup_{$modelClass}_{$timestamp}.json";
        
        $data = array_map(function($record) {
            return $record->to_array();
        }, $records);
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        
        return $filename;
    }
    
    public static function restoreFromEmergencyBackup($filename, $modelClass) {
        if (!file_exists($filename)) {
            throw new Exception("Arquivo de backup não encontrado: {$filename}");
        }
        
        $data = json_decode(file_get_contents($filename), true);
        $restored = [];
        
        foreach ($data as $recordData) {
            $record = new $modelClass($recordData);
            if ($record->save()) {
                $restored[] = $record;
            }
        }
        
        return $restored;
    }
}
?>
```

## Scripts Utilitários

### 1. Script de Verificação de Integridade

```php
<?php
class DatabaseIntegrityChecker {
    
    public function checkDatabaseIntegrity($database) {
        $connection = ActiveRecord\ConnectionManager::get_connection();
        
        // Verificar tabelas corrompidas
        $corruptedTables = $this->checkTableIntegrity($connection, $database);
        
        // Verificar referential integrity
        $orphanedRecords = $this->checkReferentialIntegrity($connection, $database);
        
        // Verificar índices
        $indexIssues = $this->checkIndexIntegrity($connection, $database);
        
        return [
            'corrupted_tables' => $corruptedTables,
            'orphaned_records' => $orphanedRecords,
            'index_issues' => $indexIssues,
            'overall_status' => empty($corruptedTables) && empty($orphanedRecords) && empty($indexIssues) ? 'OK' : 'ISSUES_FOUND'
        ];
    }
    
    private function checkTableIntegrity($connection, $database) {
        $sql = "CHECK TABLE " . implode(', ', $this->getAllTables($connection, $database));
        $results = $connection->query($sql);
        
        $corrupted = [];
        foreach ($results as $result) {
            if ($result['Msg_text'] !== 'OK') {
                $corrupted[] = [
                    'table' => $result['Table'],
                    'status' => $result['Msg_text']
                ];
            }
        }
        
        return $corrupted;
    }
    
    private function getAllTables($connection, $database) {
        $sql = "SHOW TABLES FROM {$database}";
        $results = $connection->query($sql);
        
        return array_column($results, "Tables_in_{$database}");
    }
    
    public function repairDatabase($database) {
        $connection = ActiveRecord\ConnectionManager::get_connection();
        
        $tables = $this->getAllTables($connection, $database);
        $repairResults = [];
        
        foreach ($tables as $table) {
            $sql = "REPAIR TABLE {$table}";
            $result = $connection->query($sql);
            $repairResults[$table] = $result[0]['Msg_text'];
        }
        
        return $repairResults;
    }
}
?>
```

## Checklist de Backup e Recuperação

### ✅ Planejamento
- [ ] Estratégia de backup definida (full, incremental, diferencial)
- [ ] Frequência de backup estabelecida
- [ ] Política de retenção definida
- [ ] Locais de armazenamento configurados (local + remoto)
- [ ] Testes de recuperação agendados

### ✅ Implementação
- [ ] Scripts de backup automatizados
- [ ] Compressão configurada
- [ ] Criptografia implementada (se necessário)
- [ ] Notificações configuradas
- [ ] Logs de auditoria ativados

### ✅ Monitoramento
- [ ] Alertas para falhas de backup
- [ ] Verificação de integridade regular
- [ ] Monitoramento de espaço em disco
- [ ] Validação de backups automatizada

### ✅ Disaster Recovery
- [ ] Plano de recuperação documentado
- [ ] RTO (Recovery Time Objective) definido
- [ ] RPO (Recovery Point Objective) definido
- [ ] Procedimentos de failover testados
- [ ] Equipe treinada nos procedimentos

### ✅ Segurança
- [ ] Backups criptografados
- [ ] Acesso restrito aos backups
- [ ] Auditoria de acesso aos backups
- [ ] Backup de chaves de criptografia
- [ ] Teste de integridade regular

## Comandos de Emergência

```bash
# Verificar status do MySQL
systemctl status mysql  # Linux
net start mysql         # Windows

# Backup de emergência rápido
mysqldump --single-transaction --all-databases > emergency_backup.sql

# Verificar espaço em disco
df -h                    # Linux
Get-WmiObject -Class Win32_LogicalDisk | Select-Object Size,FreeSpace,DeviceID  # Windows

# Verificar processos MySQL
ps aux | grep mysql      # Linux
tasklist | findstr mysql # Windows

# Reparar tabelas corrompidas
mysqlcheck --auto-repair --all-databases

# Verificar logs de erro
tail -f /var/log/mysql/error.log  # Linux
Get-Content C:\ProgramData\MySQL\MySQL Server 8.0\Data\*.err -Wait  # Windows
```

---

**Nota Importante**: Sempre teste seus procedimentos de backup e recuperação em ambiente de desenvolvimento antes de implementar em produção. Mantenha documentação atualizada e treine a equipe regularmente.
