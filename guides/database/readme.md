# 🔧 Guia de Resolução de Problemas MySQL + WAMP + PHP

Este guia contém soluções para os problemas mais comuns ao usar MySQL com WAMP e PHP.

## 📋 Índice
- [Problemas de Conexão](#problemas-de-conexão)
- [Problemas de Configuração](#problemas-de-configuração)
- [Problemas de Performance](#problemas-de-performance)
- [Problemas de Charset/Encoding](#problemas-de-charsetencoding)
- [Problemas de Permissões](#problemas-de-permissões)
- [Problemas de Importação/Exportação](#problemas-de-importaçãoexportação)
- [Problemas do phpMyAdmin](#problemas-do-phpmyadmin)
- [Problemas de ActiveRecord/ORM](#problemas-de-activerecordorm)
- [Comandos Úteis](#comandos-úteis)

---

## 🔌 Problemas de Conexão

### ❌ **Erro: "Can't connect to MySQL server"**

**Sintomas:**
```
SQLSTATE[HY000] [2002] Can't connect to MySQL server
```

**Soluções:**

1. **Verificar se o MySQL está ativo:**
   - Abrir WAMP → Verificar se o ícone está verde
   - Se amarelo/vermelho: clicar → MySQL → Service → Start/Resume Service

2. **Verificar a porta MySQL:**
   ```php
   // Testar conexão
   $host = 'localhost:3306'; // ou 'localhost:3307'
   $connection = mysqli_connect($host, 'root', '', 'test');
   if (!$connection) {
       echo "Erro: " . mysqli_connect_error();
   }
   ```

3. **Configurar no arquivo de configuração:**
   ```php
   // config/database.php
   'host' => 'localhost',
   'port' => '3306', // ou '3307' se alterado
   'username' => 'root',
   'password' => '', // normalmente vazio no WAMP
   ```

### ❌ **Erro: "Access denied for user 'root'@'localhost'"**

**Soluções:**

1. **Resetar senha do root:**
   ```sql
   -- No phpMyAdmin, executar:
   UPDATE mysql.user SET Password=PASSWORD('') WHERE User='root';
   FLUSH PRIVILEGES;
   ```

2. **Criar novo utilizador:**
   ```sql
   CREATE USER 'webapp'@'localhost' IDENTIFIED BY 'senha123';
   GRANT ALL PRIVILEGES ON *.* TO 'webapp'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Verificar configuração do WAMP:**
   - WAMP → MySQL → my.ini
   - Procurar por `skip-grant-tables` e comentar (#)

---

## ⚙️ Problemas de Configuração

### ❌ **MySQL não inicia após mudança de configuração**

**Soluções:**

1. **Verificar arquivo my.ini:**
   ```ini
   # Localização: C:\wamp64\bin\mysql\mysql[versão]\my.ini
   
   [mysqld]
   port = 3306
   socket = /tmp/mysql.sock
   key_buffer_size = 256M
   max_allowed_packet = 64M
   table_open_cache = 256
   sort_buffer_size = 1M
   read_buffer_size = 1M
   read_rnd_buffer_size = 4M
   myisam_sort_buffer_size = 64M
   thread_cache_size = 8
   query_cache_size = 16M
   thread_concurrency = 8
   
   # Logs de erro
   log-error = "C:/wamp64/logs/mysql.log"
   
   # InnoDB settings
   innodb_buffer_pool_size = 256M
   innodb_additional_mem_pool_size = 20M
   innodb_log_file_size = 64M
   innodb_log_buffer_size = 8M
   innodb_flush_log_at_trx_commit = 1
   innodb_lock_wait_timeout = 120
   ```

2. **Restaurar configuração padrão:**
   - Fazer backup do my.ini atual
   - Restaurar my.ini original do WAMP
   - Reiniciar todos os serviços

### ❌ **Conflito de portas**

**Soluções:**

1. **Alterar porta do MySQL:**
   ```ini
   # No my.ini
   [mysqld]
   port = 3307
   
   [mysql]
   port = 3307
   
   [client]
   port = 3307
   ```

2. **Verificar portas em uso:**
   ```cmd
   netstat -an | findstr :3306
   netstat -an | findstr :3307
   ```

3. **Parar serviços conflituosos:**
   ```cmd
   # Como administrador
   net stop mysql
   net start wampmysqld64
   ```

---

## 🚀 Problemas de Performance

### ❌ **Consultas lentas**

**Soluções:**

1. **Ativar log de consultas lentas:**
   ```sql
   SET GLOBAL slow_query_log = 'ON';
   SET GLOBAL long_query_time = 2;
   SET GLOBAL slow_query_log_file = 'C:/wamp64/logs/slow-queries.log';
   ```

2. **Otimizar configurações:**
   ```ini
   # No my.ini
   innodb_buffer_pool_size = 512M    # 70-80% da RAM disponível
   query_cache_size = 128M
   query_cache_type = 1
   tmp_table_size = 128M
   max_heap_table_size = 128M
   ```

3. **Analisar consultas:**
   ```sql
   EXPLAIN SELECT * FROM posts WHERE category_id = 1;
   SHOW PROCESSLIST;
   SHOW STATUS LIKE 'Slow_queries';
   ```

### ❌ **Tabelas sem índices**

**Soluções:**

1. **Identificar tabelas sem índices:**
   ```sql
   SELECT DISTINCT
       TABLE_NAME
   FROM information_schema.TABLES t
   LEFT JOIN information_schema.STATISTICS s 
       ON t.TABLE_SCHEMA = s.TABLE_SCHEMA 
       AND t.TABLE_NAME = s.TABLE_NAME
   WHERE t.TABLE_SCHEMA = 'sua_database' 
       AND s.TABLE_NAME IS NULL
       AND t.TABLE_TYPE = 'BASE TABLE';
   ```

2. **Adicionar índices essenciais:**
   ```sql
   -- Chaves estrangeiras sempre devem ter índices
   ALTER TABLE posts ADD INDEX idx_user_id (user_id);
   ALTER TABLE posts ADD INDEX idx_category_id (category_id);
   
   -- Campos usados em WHERE, ORDER BY, GROUP BY
   ALTER TABLE posts ADD INDEX idx_status (status);
   ALTER TABLE posts ADD INDEX idx_created_at (created_at);
   ```

---

## 🔤 Problemas de Charset/Encoding

### ❌ **Caracteres especiais (acentos) aparecem como ????**

**Soluções:**

1. **Configurar charset na base de dados:**
   ```sql
   -- Criar database com charset correto
   CREATE DATABASE minha_app 
   CHARACTER SET utf8mb4 
   COLLATE utf8mb4_unicode_ci;
   
   -- Alterar database existente
   ALTER DATABASE minha_app 
   CHARACTER SET utf8mb4 
   COLLATE utf8mb4_unicode_ci;
   ```

2. **Configurar charset nas tabelas:**
   ```sql
   -- Alterar tabela existente
   ALTER TABLE users 
   CONVERT TO CHARACTER SET utf8mb4 
   COLLATE utf8mb4_unicode_ci;
   
   -- Verificar charset das tabelas
   SELECT TABLE_NAME, TABLE_COLLATION 
   FROM information_schema.TABLES 
   WHERE TABLE_SCHEMA = 'minha_app';
   ```

3. **Configurar charset na conexão PHP:**
   ```php
   // MySQLi
   mysqli_set_charset($connection, 'utf8mb4');
   
   // PDO
   $pdo = new PDO(
       'mysql:host=localhost;dbname=minha_app;charset=utf8mb4',
       'username',
       'password',
       [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"]
   );
   
   // ActiveRecord (no config)
   ActiveRecord\Config::initialize(function($cfg) {
       $cfg->set_connections([
           'development' => 'mysql://user:pass@localhost/app?charset=utf8mb4'
       ]);
   });
   ```

4. **Configurar my.ini:**
   ```ini
   [client]
   default-character-set = utf8mb4
   
   [mysql]
   default-character-set = utf8mb4
   
   [mysqld]
   character-set-server = utf8mb4
   collation-server = utf8mb4_unicode_ci
   init_connect = 'SET NAMES utf8mb4'
   ```

---

## 🔒 Problemas de Permissões

### ❌ **Erro: "Access denied" para operações específicas**

**Soluções:**

1. **Verificar permissões do utilizador:**
   ```sql
   SHOW GRANTS FOR 'username'@'localhost';
   ```

2. **Conceder permissões específicas:**
   ```sql
   -- Permissões básicas para desenvolvimento
   GRANT SELECT, INSERT, UPDATE, DELETE ON minha_app.* TO 'dev_user'@'localhost';
   
   -- Permissões completas
   GRANT ALL PRIVILEGES ON minha_app.* TO 'admin_user'@'localhost';
   
   -- Aplicar mudanças
   FLUSH PRIVILEGES;
   ```

3. **Criar utilizador com permissões adequadas:**
   ```sql
   CREATE USER 'app_user'@'localhost' IDENTIFIED BY 'senha_segura';
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER 
   ON minha_app.* TO 'app_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

---

## 📁 Problemas de Importação/Exportação

### ❌ **Erro: "MySQL server has gone away" durante importação**

**Soluções:**

1. **Aumentar timeouts e tamanhos:**
   ```ini
   # No my.ini
   max_allowed_packet = 1024M
   wait_timeout = 28800
   interactive_timeout = 28800
   net_read_timeout = 600
   net_write_timeout = 600
   ```

2. **Importar em lotes menores:**
   ```bash
   # Dividir arquivo SQL grande
   split -l 10000 arquivo_grande.sql arquivo_parte_
   
   # Importar partes individuais
   mysql -u root -p minha_app < arquivo_parte_aa
   mysql -u root -p minha_app < arquivo_parte_ab
   ```

3. **Usar linha de comando para importações grandes:**
   ```bash
   # Mais eficiente que phpMyAdmin
   mysql -u root -p --force minha_app < backup.sql
   ```

### ❌ **Erro de charset durante importação**

**Soluções:**

1. **Especificar charset na importação:**
   ```bash
   mysql -u root -p --default-character-set=utf8mb4 minha_app < backup.sql
   ```

2. **Converter arquivo antes da importação:**
   ```bash
   # No Windows (PowerShell)
   Get-Content backup.sql -Encoding UTF8 | Set-Content backup_utf8.sql -Encoding UTF8
   ```

---

## 🌐 Problemas do phpMyAdmin

### ❌ **phpMyAdmin não abre ou dá erro 403/404**

**Soluções:**

1. **Verificar configuração do Apache:**
   ```apache
   # No httpd.conf, verificar se existe:
   Include conf/extra/phpmyadmin.conf
   ```

2. **Verificar alias do phpMyAdmin:**
   ```apache
   # No phpmyadmin.conf
   Alias /phpmyadmin "C:/wamp64/apps/phpmyadmin5.2.0/"
   
   <Directory "C:/wamp64/apps/phpmyadmin5.2.0/">
       Options +Indexes +FollowSymLinks +MultiViews
       AllowOverride all
       Require local
       # Ou para acesso externo:
       # Require all granted
   </Directory>
   ```

3. **Configurar config.inc.php:**
   ```php
   // No phpMyAdmin/config.inc.php
   $cfg['Servers'][$i]['host'] = 'localhost';
   $cfg['Servers'][$i]['port'] = '3306';
   $cfg['Servers'][$i]['user'] = 'root';
   $cfg['Servers'][$i]['password'] = '';
   $cfg['Servers'][$i]['auth_type'] = 'config';
   ```

### ❌ **Erro: "The mbstring extension is missing"**

**Soluções:**

1. **Ativar extensão mbstring:**
   ```ini
   # No php.ini
   extension=mbstring
   ```

2. **Reiniciar Apache após mudança**

---

## 🔗 Problemas de ActiveRecord/ORM

### ❌ **ActiveRecord não conecta ao MySQL**

**Soluções:**

1. **Configuração correta do ActiveRecord:**
   ```php
   // config/database.php
   require_once 'vendor/autoload.php';
   
   ActiveRecord\Config::initialize(function($cfg) {
       $cfg->set_model_directory('models');
       $cfg->set_connections([
           'development' => 'mysql://root:@localhost/minha_app?charset=utf8mb4',
           'production' => 'mysql://user:pass@localhost/minha_app?charset=utf8mb4'
       ]);
       $cfg->set_default_connection('development');
   });
   ```

2. **Verificar se o driver PDO MySQL está instalado:**
   ```php
   // Testar se PDO MySQL está disponível
   if (extension_loaded('pdo_mysql')) {
       echo "PDO MySQL está instalado";
   } else {
       echo "PDO MySQL NÃO está instalado";
   }
   
   // Listar drivers PDO disponíveis
   print_r(PDO::getAvailableDrivers());
   ```

3. **Ativar extensão PDO MySQL:**
   ```ini
   # No php.ini
   extension=pdo_mysql
   ```

### ❌ **Erros de relacionamento no ActiveRecord**

**Soluções:**

1. **Definir relacionamentos corretamente:**
   ```php
   // Model User.php
   class User extends ActiveRecord\Model {
       static $has_many = [
           ['posts', 'foreign_key' => 'user_id'],
           ['comments', 'foreign_key' => 'user_id']
       ];
       
       static $has_one = [
           ['profile', 'class_name' => 'UserProfile', 'foreign_key' => 'user_id']
       ];
   }
   
   // Model Post.php
   class Post extends ActiveRecord\Model {
       static $belongs_to = [
           ['user', 'foreign_key' => 'user_id'],
           ['category', 'foreign_key' => 'category_id']
       ];
       
       static $has_many = [
           ['comments', 'foreign_key' => 'post_id'],
           ['tags', 'through' => 'post_tags']
       ];
   }
   ```

---

## 💻 Comandos Úteis

### 🔍 **Diagnóstico do Sistema**

```bash
# Verificar versão do MySQL
mysql --version

# Verificar status dos serviços WAMP
net start | findstr -i mysql
net start | findstr -i apache

# Verificar portas em uso
netstat -an | findstr :3306
netstat -an | findstr :80
```

### 🗃️ **Comandos MySQL Úteis**

```sql
-- Verificar status do servidor
SHOW STATUS;

-- Verificar variáveis de configuração
SHOW VARIABLES LIKE 'character_set%';
SHOW VARIABLES LIKE 'collation%';

-- Verificar processos ativos
SHOW PROCESSLIST;

-- Verificar tamanho das bases de dados
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables 
GROUP BY table_schema;

-- Verificar fragmentação das tabelas
SELECT 
    table_name,
    ROUND(data_free / 1024 / 1024, 2) AS fragmented_mb
FROM information_schema.tables
WHERE table_schema = 'minha_app' 
    AND data_free > 0;

-- Otimizar tabelas fragmentadas
OPTIMIZE TABLE nome_da_tabela;
```

### 📊 **Monitorização de Performance**

```sql
-- Ativar logs de performance
SET GLOBAL general_log = 'ON';
SET GLOBAL general_log_file = 'C:/wamp64/logs/mysql-general.log';

-- Verificar cache de consultas
SHOW STATUS LIKE 'Qcache%';

-- Verificar índices não utilizados
SELECT 
    OBJECT_SCHEMA,
    OBJECT_NAME,
    INDEX_NAME,
    COUNT_FETCH,
    COUNT_INSERT,
    COUNT_UPDATE,
    COUNT_DELETE
FROM performance_schema.table_io_waits_summary_by_index_usage
WHERE INDEX_NAME IS NOT NULL
    AND COUNT_STAR = 0
    AND OBJECT_SCHEMA != 'mysql'
ORDER BY OBJECT_SCHEMA, OBJECT_NAME;
```

---

## 🆘 Resolução de Emergência

### 🔥 **MySQL não inicia de forma alguma**

1. **Verificar logs de erro:**
   ```
   C:\wamp64\logs\mysql.log
   C:\wamp64\bin\mysql\mysql[versão]\data\[hostname].err
   ```

2. **Reparar instalação:**
   - Parar todos os serviços WAMP
   - Executar como administrador:
   ```bash
   C:\wamp64\bin\mysql\mysql[versão]\bin\mysqld --install
   C:\wamp64\bin\mysql\mysql[versão]\bin\mysqld --initialize
   ```

3. **Última opção - Reinstalar MySQL:**
   - Fazer backup das bases de dados
   - Desinstalar WAMP
   - Limpar registos
   - Reinstalar WAMP
   - Restaurar backups

---

## 📚 Recursos Adicionais

- **Logs importantes:**
  - `C:\wamp64\logs\apache_error.log`
  - `C:\wamp64\logs\mysql.log`
  - `C:\wamp64\logs\php_errors.log`

- **Ficheiros de configuração:**
  - `C:\wamp64\bin\apache\apache[versão]\conf\httpd.conf`
  - `C:\wamp64\bin\mysql\mysql[versão]\my.ini`
  - `C:\wamp64\bin\php\php[versão]\php.ini`

- **Ferramentas úteis:**
  - phpMyAdmin para gestão visual
  - MySQL Workbench para design de BD
  - HeidiSQL como alternativa ao phpMyAdmin

---

💡 **Dica:** Sempre faça backup das suas bases de dados antes de fazer alterações importantes nas configurações!