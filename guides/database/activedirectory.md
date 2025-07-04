# 🐛 Guia Completo de Resolução de Erros no ActiveRecord PHP

Este guia fornece soluções detalhadas para erros comuns e incomuns do ActiveRecord, com metodologia passo a passo para identificação e mitigação.

## 📋 Índice
- [Metodologia de Diagnóstico](#metodologia-de-diagnóstico)
- [Erros de Conexão](#erros-de-conexão)
- [Erros de Configuração](#erros-de-configuração)
- [Erros de Models](#erros-de-models)
- [Erros de Relacionamentos](#erros-de-relacionamentos)
- [Erros de Validações](#erros-de-validações)
- [Erros de Consultas](#erros-de-consultas)
- [Erros de Performance](#erros-de-performance)
- [Erros de Charset/Encoding](#erros-de-charsetencoding)
- [Erros Avançados](#erros-avançados)
- [Debugging e Logs](#debugging-e-logs)
- [Prevenção de Erros](#prevenção-de-erros)

---

## 🔍 Metodologia de Diagnóstico

### 📊 **Processo Sistemático de Identificação**

1. **Coleta de Informações**
   ```php
   // Ativar debugging completo
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   
   // Ativar logs do ActiveRecord
   ActiveRecord\Config::initialize(function($cfg) {
       $cfg->set_logging(true);
       $cfg->set_logger(new ActiveRecord\CallbackLogger(function($sql) {
           echo "[SQL] " . $sql . "\n";
       }));
   });
   ```

2. **Verificação da Stack de Erro**
   ```php
   try {
       // Código que gera erro
       $user = User::find(1);
   } catch (Exception $e) {
       echo "Erro: " . $e->getMessage() . "\n";
       echo "Arquivo: " . $e->getFile() . "\n";
       echo "Linha: " . $e->getLine() . "\n";
       echo "Stack Trace:\n" . $e->getTraceAsString();
   }
   ```

3. **Testes Isolados**
   ```php
   // Testar conexão básica
   try {
       $connection = ActiveRecord\Connection::instance();
       echo "Conexão OK\n";
   } catch (Exception $e) {
       echo "Erro de conexão: " . $e->getMessage();
   }
   ```

---

## 🔌 Erros de Conexão

### ❌ **Erro: "could not find driver"**

**🔍 Identificação:**
```
ActiveRecord\DatabaseException: could not find driver
```

**📋 Passo a Passo:**

1. **Verificar extensões PHP:**
   ```php
   // Verificar se PDO está instalado
   if (!extension_loaded('pdo')) {
       die('PDO não está instalado');
   }
   
   // Verificar drivers disponíveis
   print_r(PDO::getAvailableDrivers());
   ```

2. **Ativar extensão no php.ini:**
   ```ini
   ; Descomentar estas linhas
   extension=pdo
   extension=pdo_mysql
   extension=pdo_sqlite
   ```

3. **Reiniciar servidor web**

4. **Testar novamente:**
   ```php
   ActiveRecord\Config::initialize(function($cfg) {
       $cfg->set_connections([
           'development' => 'mysql://root:@localhost/test'
       ]);
   });
   ```

### ❌ **Erro: "SQLSTATE[HY000] [2002] Connection refused"**

**🔍 Identificação:**
```
ActiveRecord\DatabaseException: SQLSTATE[HY000] [2002] Connection refused
```

**📋 Passo a Passo:**

1. **Verificar se MySQL está ativo:**
   ```bash
   # Windows
   net start | findstr -i mysql
   
   # Ou verificar serviços WAMP
   ```

2. **Testar conexão direta:**
   ```php
   $host = 'localhost';
   $port = 3306;
   $socket = @fsockopen($host, $port, $errno, $errstr, 5);
   if (!$socket) {
       echo "MySQL não está acessível: $errstr ($errno)";
   } else {
       echo "MySQL está acessível";
       fclose($socket);
   }
   ```

3. **Verificar configuração de porta:**
   ```php
   // Testar diferentes portas
   $configs = [
       'mysql://root:@localhost:3306/test',
       'mysql://root:@localhost:3307/test',
       'mysql://root:@127.0.0.1:3306/test'
   ];
   
   foreach ($configs as $config) {
       try {
           $conn = new PDO($config);
           echo "Sucesso com: $config\n";
           break;
       } catch (Exception $e) {
           echo "Falhou: $config\n";
       }
   }
   ```

### ❌ **Erro: "Access denied for user"**

**🔍 Identificação:**
```
SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'
```

**📋 Passo a Passo:**

1. **Verificar credenciais:**
   ```php
   // Testar diferentes combinações
   $credentials = [
       ['user' => 'root', 'pass' => ''],
       ['user' => 'root', 'pass' => 'root'],
       ['user' => 'root', 'pass' => 'password']
   ];
   
   foreach ($credentials as $cred) {
       try {
           $dsn = "mysql:host=localhost;dbname=test";
           $pdo = new PDO($dsn, $cred['user'], $cred['pass']);
           echo "Sucesso: user={$cred['user']}, pass={$cred['pass']}\n";
           break;
       } catch (Exception $e) {
           echo "Falhou: user={$cred['user']}\n";
       }
   }
   ```

2. **Resetar senha do MySQL (se necessário):**
   ```sql
   -- No phpMyAdmin ou linha de comando
   UPDATE mysql.user SET Password=PASSWORD('') WHERE User='root';
   FLUSH PRIVILEGES;
   ```

3. **Criar novo utilizador:**
   ```sql
   CREATE USER 'app_user'@'localhost' IDENTIFIED BY 'senha123';
   GRANT ALL PRIVILEGES ON app_database.* TO 'app_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

---

## ⚙️ Erros de Configuração

### ❌ **Erro: "No connection configured for environment"**

**🔍 Identificação:**
```
ActiveRecord\ConfigException: No connection configured for environment: production
```

**📋 Passo a Passo:**

1. **Verificar configuração de ambientes:**
   ```php
   // config/database.php
   ActiveRecord\Config::initialize(function($cfg) {
       $cfg->set_connections([
           'development' => 'mysql://root:@localhost/app_dev',
           'production' => 'mysql://user:pass@localhost/app_prod',
           'testing' => 'sqlite://./test.db'
       ]);
       
       // Definir ambiente padrão
       $cfg->set_default_connection('development');
   });
   ```

2. **Verificar ambiente atual:**
   ```php
   echo "Ambiente atual: " . ActiveRecord\Config::instance()->get_default_connection();
   
   // Forçar ambiente específico
   ActiveRecord\Config::instance()->set_default_connection('development');
   ```

3. **Configuração dinâmica baseada em ambiente:**
   ```php
   $environment = $_ENV['APP_ENV'] ?? 'development';
   
   $connections = [
       'development' => 'mysql://root:@localhost/app_dev',
       'production' => 'mysql://prod_user:' . $_ENV['DB_PASS'] . '@localhost/app_prod'
   ];
   
   ActiveRecord\Config::initialize(function($cfg) use ($connections, $environment) {
       $cfg->set_connections($connections);
       $cfg->set_default_connection($environment);
   });
   ```

### ❌ **Erro: "Model directory does not exist"**

**🔍 Identificação:**
```
ActiveRecord\ConfigException: Model directory does not exist: /path/to/models
```

**📋 Passo a Passo:**

1. **Verificar estrutura de diretórios:**
   ```php
   $model_dir = __DIR__ . '/models';
   if (!is_dir($model_dir)) {
       echo "Diretório não existe: $model_dir\n";
       mkdir($model_dir, 0755, true);
       echo "Diretório criado\n";
   }
   ```

2. **Configurar caminhos absolutos:**
   ```php
   ActiveRecord\Config::initialize(function($cfg) {
       $cfg->set_model_directory(realpath(__DIR__ . '/models'));
       
       // Ou múltiplos diretórios
       $cfg->set_model_directory([
           realpath(__DIR__ . '/models'),
           realpath(__DIR__ . '/app/models')
       ]);
   });
   ```

3. **Verificar permissões:**
   ```php
   $model_dir = __DIR__ . '/models';
   if (!is_readable($model_dir)) {
       echo "Sem permissão de leitura: $model_dir\n";
       chmod($model_dir, 0755);
   }
   ```

---

## 🏗️ Erros de Models

### ❌ **Erro: "Table 'database.table_name' doesn't exist"**

**🔍 Identificação:**
```
ActiveRecord\DatabaseException: Table 'app.users' doesn't exist
```

**📋 Passo a Passo:**

1. **Verificar nome da tabela:**
   ```php
   class User extends ActiveRecord\Model {
       // Especificar nome da tabela explicitamente
       static $table_name = 'users';
       
       // Ou verificar convenção
       public static function table_name() {
           return parent::table_name();
       }
   }
   
   // Testar
   echo "Tabela esperada: " . User::table_name() . "\n";
   ```

2. **Verificar se tabela existe no banco:**
   ```php
   try {
       $connection = ActiveRecord\Connection::instance();
       $tables = $connection->query("SHOW TABLES");
       echo "Tabelas disponíveis:\n";
       foreach ($tables as $table) {
           print_r($table);
       }
   } catch (Exception $e) {
       echo "Erro ao listar tabelas: " . $e->getMessage();
   }
   ```

3. **Criar tabela se não existir:**
   ```sql
   CREATE TABLE IF NOT EXISTS users (
       id INT AUTO_INCREMENT PRIMARY KEY,
       name VARCHAR(100) NOT NULL,
       email VARCHAR(150) UNIQUE NOT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
   );
   ```

### ❌ **Erro: "Class 'ModelName' not found"**

**🔍 Identificação:**
```
Fatal error: Class 'User' not found
```

**📋 Passo a Passo:**

1. **Verificar autoload:**
   ```php
   // Verificar se arquivo do model existe
   $model_file = __DIR__ . '/models/User.php';
   if (!file_exists($model_file)) {
       echo "Arquivo do model não existe: $model_file\n";
   }
   
   // Incluir manualmente para testar
   require_once $model_file;
   ```

2. **Verificar estrutura do model:**
   ```php
   // models/User.php
   <?php
   class User extends ActiveRecord\Model {
       // Código do model
   }
   ```

3. **Configurar autoload corretamente:**
   ```php
   // Usar composer autoload
   require_once 'vendor/autoload.php';
   
   // Ou configurar diretório de models
   ActiveRecord\Config::initialize(function($cfg) {
       $cfg->set_model_directory(__DIR__ . '/models');
   });
   ```

### ❌ **Erro: "Column not found"**

**🔍 Identificação:**
```
ActiveRecord\DatabaseException: Column 'column_name' not found
```

**📋 Passo a Passo:**

1. **Verificar estrutura da tabela:**
   ```php
   try {
       $connection = ActiveRecord\Connection::instance();
       $columns = $connection->query("DESCRIBE users");
       echo "Colunas da tabela users:\n";
       foreach ($columns as $column) {
           print_r($column);
       }
   } catch (Exception $e) {
       echo "Erro: " . $e->getMessage();
   }
   ```

2. **Verificar atributos do model:**
   ```php
   class User extends ActiveRecord\Model {
       // Definir atributos explicitamente
       static $attr_accessible = ['name', 'email', 'password'];
       static $attr_protected = ['id', 'created_at', 'updated_at'];
   }
   
   // Testar atributos
   $user = new User();
   print_r($user->attributes());
   ```

3. **Adicionar coluna ausente:**
   ```sql
   ALTER TABLE users ADD COLUMN missing_column VARCHAR(255);
   ```

---

## 🔗 Erros de Relacionamentos

### ❌ **Erro: "Unknown relationship"**

**🔍 Identificação:**
```
ActiveRecord\RelationshipException: Unknown relationship: posts for User
```

**📋 Passo a Passo:**

1. **Verificar definição de relacionamentos:**
   ```php
   class User extends ActiveRecord\Model {
       // Verificar sintaxe correta
       static $has_many = [
           ['posts'],                    // Básico
           ['comments', 'foreign_key' => 'user_id'],  // Com chave estrangeira
           ['projects', 'through' => 'project_members']  // Através de tabela
       ];
       
       static $has_one = [
           ['profile', 'class_name' => 'UserProfile']
       ];
       
       static $belongs_to = [
           ['company']
       ];
   }
   ```

2. **Testar relacionamentos:**
   ```php
   // Verificar se relacionamentos estão definidos
   $relationships = User::reflectOnAllAssociations();
   echo "Relacionamentos definidos:\n";
   foreach ($relationships as $rel) {
       echo "- " . $rel->attr_name . " (" . get_class($rel) . ")\n";
   }
   ```

3. **Debuggar relacionamento específico:**
   ```php
   try {
       $user = User::find(1);
       echo "Testando relacionamento posts:\n";
       $posts = $user->posts;
       echo "Posts encontrados: " . count($posts) . "\n";
   } catch (Exception $e) {
       echo "Erro no relacionamento: " . $e->getMessage() . "\n";
       echo "Verifique se:\n";
       echo "- A tabela 'posts' existe\n";
       echo "- A coluna 'user_id' existe na tabela posts\n";
       echo "- O model 'Post' está definido corretamente\n";
   }
   ```

### ❌ **Erro: "Foreign key constraint fails"**

**🔍 Identificação:**
```
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row
```

**📋 Passo a Passo:**

1. **Identificar a violação:**
   ```php
   try {
       $post = new Post();
       $post->title = "Novo Post";
       $post->user_id = 999; // ID que não existe
       $post->save();
   } catch (ActiveRecord\DatabaseException $e) {
       echo "Erro de integridade: " . $e->getMessage() . "\n";
       
       // Verificar se o user_id existe
       $user_exists = User::exists(999);
       if (!$user_exists) {
           echo "Usuário com ID 999 não existe\n";
       }
   }
   ```

2. **Validar antes de salvar:**
   ```php
   class Post extends ActiveRecord\Model {
       static $validates_presence_of = [
           ['user_id', 'message' => 'Usuário é obrigatório']
       ];
       
       static $validates_inclusion_of = [
           ['user_id', 'in' => 'get_valid_user_ids', 'message' => 'Usuário inválido']
       ];
       
       public function get_valid_user_ids() {
           return User::find('all', ['select' => 'id'])->to_array();
       }
   }
   ```

3. **Resolver com transação:**
   ```php
   ActiveRecord\Connection::instance()->transaction(function() {
       // Criar usuário primeiro
       $user = User::create(['name' => 'Novo Usuário', 'email' => 'novo@exemplo.com']);
       
       // Depois criar post
       $post = Post::create([
           'title' => 'Novo Post',
           'user_id' => $user->id
       ]);
   });
   ```

---

## ✅ Erros de Validações

### ❌ **Erro: "RecordInvalid"**

**🔍 Identificação:**
```
ActiveRecord\RecordInvalid: Validation failed: Email can't be blank
```

**📋 Passo a Passo:**

1. **Capturar e analisar erros de validação:**
   ```php
   try {
       $user = new User();
       $user->name = "João";
       // email não definido
       $user->save();
   } catch (ActiveRecord\RecordInvalid $e) {
       echo "Validação falhou:\n";
       $errors = $e->get_model()->errors;
       foreach ($errors->full_messages() as $message) {
           echo "- $message\n";
       }
   }
   ```

2. **Verificar validações definidas:**
   ```php
   class User extends ActiveRecord\Model {
       static $validates_presence_of = [
           ['name', 'message' => 'Nome é obrigatório'],
           ['email', 'message' => 'Email é obrigatório']
       ];
       
       static $validates_uniqueness_of = [
           ['email', 'message' => 'Email já está em uso']
       ];
       
       static $validates_format_of = [
           ['email', 'with' => '/\A[^@\s]+@[^@\s]+\z/', 'message' => 'Email inválido']
       ];
   }
   ```

3. **Validação condicional:**
   ```php
   // Usar is_valid() para testar sem salvar
   $user = new User(['name' => 'João']);
   if (!$user->is_valid()) {
       echo "Erros encontrados:\n";
       foreach ($user->errors->full_messages() as $message) {
           echo "- $message\n";
       }
   } else {
       $user->save();
   }
   ```

### ❌ **Erro: "Validation callback error"**

**🔍 Identificação:**
```
Fatal error in validation callback method
```

**📋 Passo a Passo:**

1. **Verificar callbacks de validação:**
   ```php
   class User extends ActiveRecord\Model {
       static $before_validation = ['normalize_email'];
       static $after_validation = ['log_validation'];
       
       public function normalize_email() {
           if ($this->email) {
               $this->email = strtolower(trim($this->email));
           }
       }
       
       public function log_validation() {
           error_log("Validação executada para user: " . $this->name);
       }
   }
   ```

2. **Tratar erros em callbacks:**
   ```php
   public function normalize_email() {
       try {
           if (isset($this->email) && !empty($this->email)) {
               $this->email = strtolower(trim($this->email));
           }
       } catch (Exception $e) {
           error_log("Erro na normalização do email: " . $e->getMessage());
           throw new ActiveRecord\ValidationsArgumentError("Erro na validação do email");
       }
   }
   ```

---

## 🔍 Erros de Consultas

### ❌ **Erro: "RecordNotFound"**

**🔍 Identificação:**
```
ActiveRecord\RecordNotFound: Could not find User with id 123
```

**📋 Passo a Passo:**

1. **Usar métodos seguros:**
   ```php
   // Em vez de find() que gera exceção
   $user = User::find(123);  // Gera exceção se não encontrar
   
   // Use find_by_id() que retorna null
   $user = User::find_by_id(123);
   if ($user) {
       echo "Usuário encontrado: " . $user->name;
   } else {
       echo "Usuário não encontrado";
   }
   
   // Ou use exists()
   if (User::exists(123)) {
       $user = User::find(123);
   }
   ```

2. **Capturar exceção:**
   ```php
   try {
       $user = User::find(123);
       echo "Usuário: " . $user->name;
   } catch (ActiveRecord\RecordNotFound $e) {
       echo "Usuário não encontrado: " . $e->getMessage();
       // Lógica alternativa
       $user = User::first(); // Pegar primeiro usuário
   }
   ```

3. **Consultas mais seguras:**
   ```php
   // find_or_create_by
   $user = User::find_or_create_by(['email' => 'novo@exemplo.com'], [
       'name' => 'Novo Usuário'
   ]);
   
   // find_or_initialize_by
   $user = User::find_or_initialize_by(['email' => 'outro@exemplo.com']);
   if ($user->is_new_record()) {
       $user->name = 'Outro Usuário';
       $user->save();
   }
   ```

### ❌ **Erro: "SQL Syntax Error"**

**🔍 Identificação:**
```
ActiveRecord\DatabaseException: You have an error in your SQL syntax
```

**📋 Passo a Passo:**

1. **Ativar log de SQL:**
   ```php
   ActiveRecord\Config::initialize(function($cfg) {
       $cfg->set_logging(true);
       $cfg->set_logger(new ActiveRecord\CallbackLogger(function($sql) {
           echo "[SQL DEBUG] $sql\n";
       }));
   });
   ```

2. **Verificar consultas complexas:**
   ```php
   try {
       // Consulta problemática
       $users = User::find('all', [
           'conditions' => "name LIKE '%João%' AND status = 'active'",
           'order' => 'created_at DESC',
           'limit' => 10
       ]);
   } catch (ActiveRecord\DatabaseException $e) {
       echo "Erro SQL: " . $e->getMessage() . "\n";
       
       // Testar consulta mais simples
       $users = User::find('all', ['limit' => 1]);
       echo "Consulta simples funcionou\n";
   }
   ```

3. **Usar parâmetros seguros:**
   ```php
   // Em vez de concatenar strings
   $name = "João'; DROP TABLE users; --";
   
   // ERRADO:
   // $users = User::find('all', ['conditions' => "name = '$name'"]);
   
   // CORRETO:
   $users = User::find('all', [
       'conditions' => ['name = ?', $name]
   ]);
   
   // Ou com placeholders nomeados
   $users = User::find('all', [
       'conditions' => ['name = :name', [':name' => $name]]
   ]);
   ```

---

## 🚀 Erros de Performance

### ❌ **Problema: "N+1 Query Problem"**

**🔍 Identificação:**
```php
// Código que causa N+1 queries
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->user->name; // Cada iteração executa uma query
}
```

**📋 Passo a Passo:**

1. **Detectar o problema:**
   ```php
   // Contar queries executadas
   $query_count = 0;
   ActiveRecord\Config::initialize(function($cfg) use (&$query_count) {
       $cfg->set_logging(true);
       $cfg->set_logger(new ActiveRecord\CallbackLogger(function($sql) use (&$query_count) {
           $query_count++;
           echo "[$query_count] $sql\n";
       }));
   });
   
   $posts = Post::all();
   echo "Queries até agora: $query_count\n"; // 1 query
   
   foreach ($posts as $post) {
       echo $post->user->name; // +1 query por post
   }
   echo "Total de queries: $query_count\n"; // 1 + N queries
   ```

2. **Resolver com includes:**
   ```php
   // Carregar relacionamentos antecipadamente
   $posts = Post::find('all', [
       'include' => ['user', 'category', 'comments']
   ]);
   
   foreach ($posts as $post) {
       echo $post->user->name; // Sem queries adicionais
       echo $post->category->name;
       echo count($post->comments);
   }
   ```

3. **Includes condicionais:**
   ```php
   // Include apenas o que for necessário
   $posts = Post::find('all', [
       'include' => ['user'],
       'conditions' => ['status = ?', 'published']
   ]);
   ```

### ❌ **Problema: "Memory Limit Exceeded"**

**🔍 Identificação:**
```
Fatal error: Allowed memory size exhausted
```

**📋 Passo a Passo:**

1. **Identificar consultas grandes:**
   ```php
   // Problemático - carrega tudo na memória
   $all_posts = Post::all(); // 100k+ registros
   
   // Melhor - usar paginação
   $page = 1;
   $per_page = 100;
   $posts = Post::find('all', [
       'limit' => $per_page,
       'offset' => ($page - 1) * $per_page
   ]);
   ```

2. **Usar batch processing:**
   ```php
   // Processar em lotes
   $batch_size = 1000;
   $offset = 0;
   
   do {
       $posts = Post::find('all', [
           'limit' => $batch_size,
           'offset' => $offset
       ]);
       
       foreach ($posts as $post) {
           // Processar post
           echo "Processando: " . $post->title . "\n";
       }
       
       $offset += $batch_size;
       
       // Limpar memória
       unset($posts);
       if (function_exists('gc_collect_cycles')) {
           gc_collect_cycles();
       }
       
   } while (count($posts) == $batch_size);
   ```

3. **Usar select específico:**
   ```php
   // Em vez de carregar todos os campos
   $posts = Post::all();
   
   // Carregar apenas campos necessários
   $posts = Post::find('all', [
       'select' => 'id, title, created_at'
   ]);
   ```

---

## 🔤 Erros de Charset/Encoding

### ❌ **Problema: "Caracteres especiais corrompidos"**

**🔍 Identificação:**
```
// Acentos aparecem como: Ã§Ã£o, Ã¡, Ã©
echo $user->name; // "JoÃ£o" em vez de "João"
```

**📋 Passo a Passo:**

1. **Verificar charset da conexão:**
   ```php
   try {
       $connection = ActiveRecord\Connection::instance();
       $charset = $connection->query("SELECT @@character_set_connection")[0];
       echo "Charset da conexão: " . $charset['@@character_set_connection'] . "\n";
       
       $collation = $connection->query("SELECT @@collation_connection")[0];
       echo "Collation da conexão: " . $collation['@@collation_connection'] . "\n";
   } catch (Exception $e) {
       echo "Erro ao verificar charset: " . $e->getMessage();
   }
   ```

2. **Configurar charset na conexão:**
   ```php
   ActiveRecord\Config::initialize(function($cfg) {
       $cfg->set_connections([
           'development' => 'mysql://root:@localhost/app?charset=utf8mb4'
       ]);
   });
   
   // Ou forçar charset após conexão
   try {
       $connection = ActiveRecord\Connection::instance();
       $connection->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
   } catch (Exception $e) {
       echo "Erro ao definir charset: " . $e->getMessage();
   }
   ```

3. **Verificar charset da tabela:**
   ```php
   try {
       $connection = ActiveRecord\Connection::instance();
       $table_info = $connection->query("SHOW CREATE TABLE users")[0];
       echo "Estrutura da tabela:\n" . $table_info['Create Table'] . "\n";
   } catch (Exception $e) {
       echo "Erro: " . $e->getMessage();
   }
   ```

4. **Converter dados existentes:**
   ```sql
   -- Converter tabela para UTF8MB4
   ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   
   -- Converter coluna específica
   ALTER TABLE users MODIFY COLUMN name VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

---

## 🔬 Erros Avançados

### ❌ **Erro: "Deadlock detected"**

**🔍 Identificação:**
```
SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock
```

**📋 Passo a Passo:**

1. **Identificar deadlocks:**
   ```php
   try {
       ActiveRecord\Connection::instance()->transaction(function() {
           $user1 = User::find(1, ['lock' => true]);
           $user2 = User::find(2, ['lock' => true]);
           
           $user1->name = "Novo Nome 1";
           $user2->name = "Novo Nome 2";
           
           $user1->save();
           $user2->save();
       });
   } catch (ActiveRecord\DatabaseException $e) {
       if (strpos($e->getMessage(), 'Deadlock') !== false) {
           echo "Deadlock detectado, tentando novamente...\n";
           // Implementar retry logic
           sleep(rand(1, 3)); // Wait random time
           // Tentar novamente
       }
   }
   ```

2. **Implementar retry automático:**
   ```php
   function executeWithRetry($callback, $maxRetries = 3) {
       $attempts = 0;
       
       while ($attempts < $maxRetries) {
           try {
               return ActiveRecord\Connection::instance()->transaction($callback);
           } catch (ActiveRecord\DatabaseException $e) {
               $attempts++;
               
               if (strpos($e->getMessage(), 'Deadlock') !== false && $attempts < $maxRetries) {
                   echo "Deadlock detectado, tentativa $attempts/$maxRetries\n";
                   sleep(pow(2, $attempts) + rand(0, 1000) / 1000); // Exponential backoff
                   continue;
               }
               
               throw $e;
           }
       }
   }
   
   // Uso
   executeWithRetry(function() {
       $user = User::find(1);
       $user->last_login = new DateTime();
       $user->save();
   });
   ```

### ❌ **Erro: "Connection lost during query"**

**🔍 Identificação:**
```
SQLSTATE[HY000]: General error: 2006 MySQL server has gone away
```

**📋 Passo a Passo:**

1. **Implementar reconexão automática:**
   ```php
   class ReconnectingConnection extends ActiveRecord\Connection {
       public function query($sql, $values = []) {
           try {
               return parent::query($sql, $values);
           } catch (ActiveRecord\DatabaseException $e) {
               if (strpos($e->getMessage(), 'server has gone away') !== false) {
                   echo "Conexão perdida, reconectando...\n";
                   $this->connection = null; // Forçar nova conexão
                   return parent::query($sql, $values);
               }
               throw $e;
           }
       }
   }
   ```

2. **Verificar timeouts do MySQL:**
   ```sql
   SHOW VARIABLES LIKE 'wait_timeout';
   SHOW VARIABLES LIKE 'interactive_timeout';
   
   -- Aumentar timeouts se necessário
   SET GLOBAL wait_timeout = 28800;
   SET GLOBAL interactive_timeout = 28800;
   ```

3. **Implementar keep-alive:**
   ```php
   class KeepAliveConnection {
       private $lastActivity;
       private $timeout = 3600; // 1 hora
       
       public function ping() {
           if (time() - $this->lastActivity > $this->timeout) {
               try {
                   ActiveRecord\Connection::instance()->query("SELECT 1");
                   $this->lastActivity = time();
               } catch (Exception $e) {
                   // Reconectar
                   ActiveRecord\Config::initialize(function($cfg) {
                       // Reconfigurar conexão
                   });
               }
           }
       }
   }
   ```

---

## 🐛 Debugging e Logs

### **Sistema de Logging Avançado**

```php
class AdvancedLogger {
    private $logFile;
    private $logLevel;
    
    public function __construct($logFile = 'activerecord.log', $logLevel = 'DEBUG') {
        $this->logFile = $logFile;
        $this->logLevel = $logLevel;
    }
    
    public function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logLine = "[$timestamp] [$level] $message $contextStr\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    public function logQuery($sql, $values = [], $time = null) {
        $timeStr = $time ? " ({$time}ms)" : '';
        $valuesStr = !empty($values) ? " [" . implode(', ', $values) . "]" : '';
        $this->log('SQL', $sql . $valuesStr . $timeStr);
    }
}

// Configurar logger avançado
$logger = new AdvancedLogger('logs/activerecord.log');

ActiveRecord\Config::initialize(function($cfg) use ($logger) {
    $cfg->set_logging(true);
    $cfg->set_logger(new ActiveRecord\CallbackLogger(function($sql, $values = []) use ($logger) {
        $start = microtime(true);
        $logger->logQuery($sql, $values, round((microtime(true) - $start) * 1000, 2));
    }));
});
```

### **Debugging de Performance**

```php
class PerformanceProfiler {
    private $queries = [];
    private $startTime;
    
    public function start() {
        $this->startTime = microtime(true);
        $this->queries = [];
    }
    
    public function logQuery($sql) {
        $this->queries[] = [
            'sql' => $sql,
            'time' => microtime(true),
            'memory' => memory_get_usage()
        ];
    }
    
    public function getReport() {
        $totalTime = microtime(true) - $this->startTime;
        $totalQueries = count($this->queries);
        $peakMemory = memory_get_peak_usage();
        
        return [
            'total_time' => $totalTime,
            'total_queries' => $totalQueries,
            'peak_memory' => $peakMemory,
            'queries' => $this->queries
        ];
    }
    
    public function printReport() {
        $report = $this->getReport();
        echo "=== Performance Report ===\n";
        echo "Total Time: " . round($report['total_time'], 4) . "s\n";
        echo "Total Queries: " . $report['total_queries'] . "\n";
        echo "Peak Memory: " . round($report['peak_memory'] / 1024 / 1024, 2) . "MB\n";
        echo "Avg Time per Query: " . round($report['total_time'] / max($report['total_queries'], 1), 4) . "s\n";
    }
}
```

---

## 🛡️ Prevenção de Erros

### **Configuração Robusta**

```php
// config/activerecord_config.php
class ActiveRecordConfig {
    public static function initialize() {
        try {
            ActiveRecord\Config::initialize(function($cfg) {
                // Conexões com fallback
                $cfg->set_connections([
                    'development' => self::getDatabaseUrl('development'),
                    'production' => self::getDatabaseUrl('production'),
                    'testing' => 'sqlite://./tests/test.db'
                ]);
                
                // Configurações de segurança
                $cfg->set_model_directory([
                    realpath(__DIR__ . '/../models'),
                    realpath(__DIR__ . '/../app/models')
                ]);
                
                // Logging condicional
                if ($_ENV['AR_LOGGING'] ?? false) {
                    $cfg->set_logging(true);
                    $cfg->set_logger(new self::createLogger());
                }
                
                $cfg->set_default_connection($_ENV['APP_ENV'] ?? 'development');
            });
            
            // Teste de conexão
            self::testConnection();
            
        } catch (Exception $e) {
            error_log("Erro na configuração do ActiveRecord: " . $e->getMessage());
            throw new RuntimeException("Falha na inicialização do banco de dados");
        }
    }
    
    private static function getDatabaseUrl($env) {
        $configs = [
            'development' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? '3306',
                'database' => $_ENV['DB_NAME'] ?? 'app_dev',
                'username' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASS'] ?? ''
            ],
            'production' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? '3306',
                'database' => $_ENV['DB_NAME'] ?? 'app_prod',
                'username' => $_ENV['DB_USER'] ?? 'app_user',
                'password' => $_ENV['DB_PASS'] ?? ''
            ]
        ];
        
        $config = $configs[$env];
        return sprintf(
            'mysql://%s:%s@%s:%s/%s?charset=utf8mb4',
            $config['username'],
            $config['password'],
            $config['host'],
            $config['port'],
            $config['database']
        );
    }
    
    private static function testConnection() {
        try {
            $connection = ActiveRecord\Connection::instance();
            $connection->query("SELECT 1");
        } catch (Exception $e) {
            throw new RuntimeException("Teste de conexão falhou: " . $e->getMessage());
        }
    }
}
```

### **Base Model com Validações Padrão**

```php
// models/BaseModel.php
abstract class BaseModel extends ActiveRecord\Model {
    // Validações básicas para todos os models
    static $validates_presence_of = [
        ['created_at'],
        ['updated_at']
    ];
    
    // Callbacks padrão
    static $before_save = ['update_timestamp'];
    static $before_create = ['set_created_at'];
    static $before_update = ['set_updated_at'];
    
    public function update_timestamp() {
        $this->updated_at = new DateTime();
    }
    
    public function set_created_at() {
        if (!$this->created_at) {
            $this->created_at = new DateTime();
        }
    }
    
    public function set_updated_at() {
        $this->updated_at = new DateTime();
    }
    
    // Método para safe save
    public function safe_save($validate = true) {
        try {
            return $this->save($validate);
        } catch (ActiveRecord\RecordInvalid $e) {
            error_log("Validation failed for " . get_class($this) . ": " . implode(', ', $this->errors->full_messages()));
            return false;
        } catch (ActiveRecord\DatabaseException $e) {
            error_log("Database error for " . get_class($this) . ": " . $e->getMessage());
            return false;
        }
    }
    
    // Método para debug
    public function debug_info() {
        return [
            'class' => get_class($this),
            'table' => $this->table_name(),
            'attributes' => $this->attributes(),
            'errors' => $this->errors->full_messages(),
            'is_new' => $this->is_new_record(),
            'is_dirty' => $this->is_dirty()
        ];
    }
}
```

---

## 🔧 Ferramentas de Diagnóstico

### **Health Check Completo**

```php
class ActiveRecordHealthCheck {
    public static function run() {
        $results = [];
        
        // Teste de conexão
        $results['connection'] = self::testConnection();
        
        // Teste de models
        $results['models'] = self::testModels();
        
        // Teste de relacionamentos
        $results['relationships'] = self::testRelationships();
        
        // Teste de performance
        $results['performance'] = self::testPerformance();
        
        return $results;
    }
    
    private static function testConnection() {
        try {
            $connection = ActiveRecord\Connection::instance();
            $result = $connection->query("SELECT 1 as test");
            return [
                'status' => 'OK',
                'message' => 'Conexão funcionando',
                'result' => $result[0]['test']
            ];
        } catch (Exception $e) {
            return [
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ];
        }
    }
    
    private static function testModels() {
        $models = ['User', 'Post', 'Category']; // Adicionar seus models
        $results = [];
        
        foreach ($models as $model) {
            try {
                if (class_exists($model)) {
                    $count = $model::count();
                    $results[$model] = [
                        'status' => 'OK',
                        'count' => $count
                    ];
                } else {
                    $results[$model] = [
                        'status' => 'ERROR',
                        'message' => 'Classe não encontrada'
                    ];
                }
            } catch (Exception $e) {
                $results[$model] = [
                    'status' => 'ERROR',
                    'message' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    private static function testRelationships() {
        try {
            // Testar um relacionamento conhecido
            $user = User::first();
            if ($user) {
                $posts = $user->posts;
                return [
                    'status' => 'OK',
                    'message' => 'Relacionamentos funcionando',
                    'sample_posts' => count($posts)
                ];
            }
            return [
                'status' => 'WARNING',
                'message' => 'Nenhum usuário para testar relacionamentos'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ];
        }
    }
    
    private static function testPerformance() {
        $start = microtime(true);
        
        try {
            // Query simples
            $users = User::find('all', ['limit' => 10]);
            
            $time = microtime(true) - $start;
            
            return [
                'status' => $time < 1 ? 'OK' : 'WARNING',
                'query_time' => round($time, 4),
                'records_found' => count($users)
            ];
        } catch (Exception $e) {
            return [
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ];
        }
    }
}

// Uso
$health = ActiveRecordHealthCheck::run();
print_r($health);
```

---

Este guia fornece uma abordagem sistemática para identificar, diagnosticar e resolver problemas no ActiveRecord. Sempre comece com a metodologia de diagnóstico e depois siga para a seção específica do seu problema.

💡 **Lembre-se:** Quando em dúvida, sempre ative o logging e analise as queries SQL que estão sendo geradas!