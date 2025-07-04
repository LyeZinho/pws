# Guia MVC com MySQL - Implementação com Banco de Dados

## Índice
- [Introdução](#introdução)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Configuração do Banco de Dados](#configuração-do-banco-de-dados)
- [Classe Database](#classe-database)
- [Model com MySQL](#model-com-mysql)
- [Controllers com Dados](#controllers-com-dados)
- [CRUD Completo](#crud-completo)
- [Validação Avançada](#validação-avançada)
- [Setup do Ambiente](#setup-do-ambiente)
- [Resolução de Problemas](#resolução-de-problemas)

## Introdução

Este guia implementa um sistema MVC conectado ao MySQL, incluindo operações CRUD completas, validação de dados e tratamento de erros de banco de dados.

### Recursos Implementados:
- Conexão PDO com MySQL
- Models com operações CRUD
- Relacionamentos entre tabelas
- Validação de dados robusta
- Tratamento de erros de banco
- Migrations simples
- Seeds para dados de teste

## Estrutura do Projeto

```
projeto-mvc-mysql/
├── app/
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   ├── HomeController.php
│   │   ├── UserController.php
│   │   └── PostController.php
│   ├── Models/
│   │   ├── BaseModel.php
│   │   ├── User.php
│   │   └── Post.php
│   └── Views/
│       ├── layouts/
│       │   └── main.php
│       ├── home/
│       │   └── index.php
│       ├── users/
│       │   ├── index.php
│       │   ├── show.php
│       │   ├── create.php
│       │   └── edit.php
│       └── posts/
│           ├── index.php
│           ├── show.php
│           ├── create.php
│           └── edit.php
├── core/
│   ├── Application.php
│   ├── Router.php
│   ├── Controller.php
│   ├── Model.php
│   ├── Database.php
│   └── Migration.php
├── config/
│   ├── database.php
│   └── config.php
├── database/
│   ├── migrations/
│   │   ├── 001_create_users_table.php
│   │   └── 002_create_posts_table.php
│   └── seeds/
│       ├── UserSeeder.php
│       └── PostSeeder.php
├── public/
│   └── index.php
└── .htaccess
```

## Configuração do Banco de Dados

### config/database.php
```php
<?php
return [
    'default' => 'mysql',
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'mvc_database',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ]
    ]
];
```

### Arquivo .env (criar na raiz)
```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=mvc_database
DB_USERNAME=root
DB_PASSWORD=

# Application Configuration
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/projeto-mvc-mysql
```

## Classe Database

### core/Database.php
```php
<?php
namespace Core;

class Database {
    private static $instance = null;
    private $connection;
    private $config;
    
    private function __construct() {
        $this->config = require __DIR__ . '/../config/database.php';
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        $defaultConnection = $this->config['default'];
        $config = $this->config['connections'][$defaultConnection];
        
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
        
        try {
            $this->connection = new \PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
            
            // Configurar timezone
            $this->connection->exec("SET time_zone = '+00:00'");
            
        } catch (\PDOException $e) {
            throw new \Exception("Erro de conexão com banco de dados: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            throw new \Exception("Erro na consulta SQL: " . $e->getMessage() . " | SQL: " . $sql);
        }
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function tableExists($tableName) {
        $sql = "SELECT COUNT(*) FROM information_schema.tables 
                WHERE table_schema = DATABASE() AND table_name = ?";
        $result = $this->fetch($sql, [$tableName]);
        return $result['COUNT(*)'] > 0;
    }
}
```

## Model com MySQL

### core/Model.php
```php
<?php
namespace Core;

class Model {
    protected static $table;
    protected static $primaryKey = 'id';
    protected static $fillable = [];
    protected static $hidden = [];
    protected static $timestamps = true;
    
    protected $attributes = [];
    protected $original = [];
    protected $exists = false;
    
    protected static $db;
    
    public function __construct($attributes = []) {
        if (!static::$db) {
            static::$db = Database::getInstance();
        }
        
        $this->fill($attributes);
        $this->syncOriginal();
    }
    
    public static function getTableName() {
        if (static::$table) {
            return static::$table;
        }
        
        // Gerar nome da tabela a partir da classe
        $className = (new \ReflectionClass(static::class))->getShortName();
        return strtolower($className) . 's';
    }
    
    // Métodos de busca
    public static function all($columns = ['*']) {
        $sql = "SELECT " . implode(', ', $columns) . " FROM " . static::getTableName();
        $results = static::$db->fetchAll($sql);
        
        return static::hydrate($results);
    }
    
    public static function find($id, $columns = ['*']) {
        $sql = "SELECT " . implode(', ', $columns) . " FROM " . static::getTableName() . 
               " WHERE " . static::$primaryKey . " = ? LIMIT 1";
        
        $result = static::$db->fetch($sql, [$id]);
        
        return $result ? static::newFromBuilder($result) : null;
    }
    
    public static function where($column, $operator = null, $value = null) {
        return new QueryBuilder(static::class, static::$db);
    }
    
    public static function create($attributes) {
        $instance = new static($attributes);
        $instance->save();
        return $instance;
    }
    
    // Métodos de persistência
    public function save() {
        if ($this->exists) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }
    
    protected function insert() {
        $attributes = $this->getAttributesForInsert();
        
        if (static::$timestamps) {
            $attributes['created_at'] = $attributes['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $columns = array_keys($attributes);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO " . static::getTableName() . 
               " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        static::$db->execute($sql, array_values($attributes));
        
        $this->attributes[static::$primaryKey] = static::$db->lastInsertId();
        $this->exists = true;
        $this->syncOriginal();
        
        return true;
    }
    
    protected function update() {
        $attributes = $this->getAttributesForUpdate();
        
        if (static::$timestamps && isset($this->attributes['updated_at'])) {
            $attributes['updated_at'] = date('Y-m-d H:i:s');
        }
        
        if (empty($attributes)) {
            return true;
        }
        
        $sets = [];
        foreach (array_keys($attributes) as $column) {
            $sets[] = "$column = ?";
        }
        
        $sql = "UPDATE " . static::getTableName() . 
               " SET " . implode(', ', $sets) . 
               " WHERE " . static::$primaryKey . " = ?";
        
        $values = array_values($attributes);
        $values[] = $this->getKey();
        
        $affected = static::$db->execute($sql, $values);
        $this->syncOriginal();
        
        return $affected > 0;
    }
    
    public function delete() {
        if (!$this->exists) {
            return false;
        }
        
        $sql = "DELETE FROM " . static::getTableName() . 
               " WHERE " . static::$primaryKey . " = ?";
        
        $affected = static::$db->execute($sql, [$this->getKey()]);
        
        if ($affected > 0) {
            $this->exists = false;
        }
        
        return $affected > 0;
    }
    
    // Métodos auxiliares
    public function fill($attributes) {
        foreach ($attributes as $key => $value) {
            if (empty(static::$fillable) || in_array($key, static::$fillable)) {
                $this->attributes[$key] = $value;
            }
        }
    }
    
    public function getKey() {
        return $this->attributes[static::$primaryKey] ?? null;
    }
    
    public function toArray() {
        $attributes = $this->attributes;
        
        // Remover campos ocultos
        foreach (static::$hidden as $hidden) {
            unset($attributes[$hidden]);
        }
        
        return $attributes;
    }
    
    protected function getAttributesForInsert() {
        $attributes = $this->attributes;
        unset($attributes[static::$primaryKey]); // Remove PK para auto-increment
        return $attributes;
    }
    
    protected function getAttributesForUpdate() {
        $dirty = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        
        unset($dirty[static::$primaryKey]); // Não atualizar PK
        return $dirty;
    }
    
    protected function syncOriginal() {
        $this->original = $this->attributes;
    }
    
    protected static function hydrate($results) {
        $models = [];
        
        foreach ($results as $result) {
            $models[] = static::newFromBuilder($result);
        }
        
        return $models;
    }
    
    protected static function newFromBuilder($attributes) {
        $instance = new static();
        $instance->attributes = $attributes;
        $instance->exists = true;
        $instance->syncOriginal();
        
        return $instance;
    }
    
    // Magic methods
    public function __get($key) {
        return $this->attributes[$key] ?? null;
    }
    
    public function __set($key, $value) {
        $this->attributes[$key] = $value;
    }
    
    public function __isset($key) {
        return isset($this->attributes[$key]);
    }
    
    public function __unset($key) {
        unset($this->attributes[$key]);
    }
}
```

### Query Builder Simples

### core/QueryBuilder.php
```php
<?php
namespace Core;

class QueryBuilder {
    protected $model;
    protected $db;
    protected $wheres = [];
    protected $orders = [];
    protected $limits;
    protected $offset;
    
    public function __construct($model, $db) {
        $this->model = $model;
        $this->db = $db;
    }
    
    public function where($column, $operator = null, $value = null) {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    public function orWhere($column, $operator = null, $value = null) {
        $this->where($column, $operator, $value);
        $this->wheres[count($this->wheres) - 1]['boolean'] = 'OR';
        
        return $this;
    }
    
    public function orderBy($column, $direction = 'ASC') {
        $this->orders[] = ['column' => $column, 'direction' => strtoupper($direction)];
        return $this;
    }
    
    public function limit($limit) {
        $this->limits = $limit;
        return $this;
    }
    
    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }
    
    public function get($columns = ['*']) {
        $sql = $this->buildSelectSql($columns);
        $bindings = $this->getBindings();
        
        $results = $this->db->fetchAll($sql, $bindings);
        return $this->model::hydrate($results);
    }
    
    public function first($columns = ['*']) {
        $this->limit(1);
        $results = $this->get($columns);
        return count($results) > 0 ? $results[0] : null;
    }
    
    public function count() {
        $sql = $this->buildSelectSql(['COUNT(*) as aggregate']);
        $bindings = $this->getBindings();
        
        $result = $this->db->fetch($sql, $bindings);
        return (int) $result['aggregate'];
    }
    
    protected function buildSelectSql($columns) {
        $table = $this->model::getTableName();
        $sql = "SELECT " . implode(', ', $columns) . " FROM $table";
        
        if (!empty($this->wheres)) {
            $sql .= " WHERE " . $this->buildWhereClause();
        }
        
        if (!empty($this->orders)) {
            $orderClauses = [];
            foreach ($this->orders as $order) {
                $orderClauses[] = $order['column'] . ' ' . $order['direction'];
            }
            $sql .= " ORDER BY " . implode(', ', $orderClauses);
        }
        
        if ($this->limits) {
            $sql .= " LIMIT " . $this->limits;
        }
        
        if ($this->offset) {
            $sql .= " OFFSET " . $this->offset;
        }
        
        return $sql;
    }
    
    protected function buildWhereClause() {
        $clauses = [];
        
        foreach ($this->wheres as $i => $where) {
            $clause = $where['column'] . ' ' . $where['operator'] . ' ?';
            
            if ($i > 0) {
                $clause = $where['boolean'] . ' ' . $clause;
            }
            
            $clauses[] = $clause;
        }
        
        return implode(' ', $clauses);
    }
    
    protected function getBindings() {
        $bindings = [];
        
        foreach ($this->wheres as $where) {
            $bindings[] = $where['value'];
        }
        
        return $bindings;
    }
}
```

## Model com MySQL

### app/Models/User.php
```php
<?php
namespace App\Models;

use Core\Model;

class User extends Model {
    protected static $table = 'users';
    protected static $fillable = ['name', 'email', 'password'];
    protected static $hidden = ['password'];
    
    public function validate() {
        $errors = [];
        
        // Validar nome
        if (empty($this->name)) {
            $errors['name'] = 'Nome é obrigatório';
        } elseif (strlen($this->name) < 2) {
            $errors['name'] = 'Nome deve ter pelo menos 2 caracteres';
        }
        
        // Validar email
        if (empty($this->email)) {
            $errors['email'] = 'Email é obrigatório';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email deve ser válido';
        } elseif ($this->emailExists()) {
            $errors['email'] = 'Email já está sendo usado';
        }
        
        // Validar senha
        if (empty($this->password)) {
            $errors['password'] = 'Senha é obrigatória';
        } elseif (strlen($this->password) < 6) {
            $errors['password'] = 'Senha deve ter pelo menos 6 caracteres';
        }
        
        return $errors;
    }
    
    protected function emailExists() {
        $sql = "SELECT COUNT(*) as count FROM " . static::getTableName() . " WHERE email = ?";
        
        if ($this->exists) {
            $sql .= " AND " . static::$primaryKey . " != ?";
            $result = static::$db->fetch($sql, [$this->email, $this->getKey()]);
        } else {
            $result = static::$db->fetch($sql, [$this->email]);
        }
        
        return $result['count'] > 0;
    }
    
    public function save() {
        $errors = $this->validate();
        
        if (!empty($errors)) {
            return $errors;
        }
        
        // Hash da senha antes de salvar
        if (isset($this->attributes['password']) && !empty($this->attributes['password'])) {
            $this->attributes['password'] = password_hash($this->attributes['password'], PASSWORD_DEFAULT);
        }
        
        return parent::save();
    }
    
    public function posts() {
        return Post::where('user_id', $this->getKey())->get();
    }
    
    public static function findByEmail($email) {
        $sql = "SELECT * FROM " . static::getTableName() . " WHERE email = ? LIMIT 1";
        $result = static::$db->fetch($sql, [$email]);
        
        return $result ? static::newFromBuilder($result) : null;
    }
    
    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }
}
```

### app/Models/Post.php
```php
<?php
namespace App\Models;

use Core\Model;

class Post extends Model {
    protected static $table = 'posts';
    protected static $fillable = ['title', 'content', 'user_id', 'status'];
    
    public function validate() {
        $errors = [];
        
        if (empty($this->title)) {
            $errors['title'] = 'Título é obrigatório';
        } elseif (strlen($this->title) < 5) {
            $errors['title'] = 'Título deve ter pelo menos 5 caracteres';
        }
        
        if (empty($this->content)) {
            $errors['content'] = 'Conteúdo é obrigatório';
        } elseif (strlen($this->content) < 10) {
            $errors['content'] = 'Conteúdo deve ter pelo menos 10 caracteres';
        }
        
        if (empty($this->user_id)) {
            $errors['user_id'] = 'Usuário é obrigatório';
        }
        
        return $errors;
    }
    
    public function save() {
        $errors = $this->validate();
        
        if (!empty($errors)) {
            return $errors;
        }
        
        // Status padrão
        if (empty($this->status)) {
            $this->status = 'draft';
        }
        
        return parent::save();
    }
    
    public function user() {
        return User::find($this->user_id);
    }
    
    public static function getByStatus($status = 'published') {
        $sql = "SELECT * FROM " . static::getTableName() . " WHERE status = ? ORDER BY created_at DESC";
        $results = static::$db->fetchAll($sql, [$status]);
        
        return static::hydrate($results);
    }
    
    public static function getWithUser($limit = null) {
        $sql = "SELECT p.*, u.name as user_name, u.email as user_email 
                FROM " . static::getTableName() . " p 
                LEFT JOIN users u ON p.user_id = u.id 
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int) $limit;
        }
        
        $results = static::$db->fetchAll($sql);
        
        $posts = [];
        foreach ($results as $result) {
            $post = static::newFromBuilder($result);
            $post->user_name = $result['user_name'];
            $post->user_email = $result['user_email'];
            $posts[] = $post;
        }
        
        return $posts;
    }
}
```

## Controllers com Dados

### app/Controllers/UserController.php
```php
<?php
namespace App\Controllers;

use App\Models\User;

class UserController extends BaseController {
    
    public function index() {
        try {
            $users = User::all();
            
            $data = [
                'title' => 'Lista de Usuários',
                'users' => $users
            ];
            
            $this->render('users/index', $data);
            
        } catch (\Exception $e) {
            $this->setMessage('error', 'Erro ao carregar usuários: ' . $e->getMessage());
            $this->render('home/index', ['title' => 'Erro']);
        }
    }
    
    public function show($id) {
        try {
            $user = User::find($id);
            
            if (!$user) {
                $this->setMessage('error', 'Usuário não encontrado');
                $this->redirect('/users');
                return;
            }
            
            $posts = $user->posts();
            
            $data = [
                'title' => 'Detalhes do Usuário',
                'user' => $user,
                'posts' => $posts
            ];
            
            $this->render('users/show', $data);
            
        } catch (\Exception $e) {
            $this->setMessage('error', 'Erro ao carregar usuário: ' . $e->getMessage());
            $this->redirect('/users');
        }
    }
    
    public function create() {
        $data = ['title' => 'Novo Usuário'];
        $this->render('users/create', $data);
    }
    
    public function store() {
        try {
            $input = $this->getInput();
            
            $user = new User();
            $user->fill($input);
            
            $result = $user->save();
            
            if ($result === true) {
                $this->setMessage('success', 'Usuário criado com sucesso!');
                $this->redirect('/users/' . $user->getKey());
            } else {
                $data = [
                    'title' => 'Novo Usuário',
                    'errors' => $result,
                    'old' => $input
                ];
                
                $this->render('users/create', $data);
            }
            
        } catch (\Exception $e) {
            $this->setMessage('error', 'Erro ao criar usuário: ' . $e->getMessage());
            $this->redirect('/users/create');
        }
    }
    
    public function edit($id) {
        try {
            $user = User::find($id);
            
            if (!$user) {
                $this->setMessage('error', 'Usuário não encontrado');
                $this->redirect('/users');
                return;
            }
            
            $data = [
                'title' => 'Editar Usuário',
                'user' => $user
            ];
            
            $this->render('users/edit', $data);
            
        } catch (\Exception $e) {
            $this->setMessage('error', 'Erro ao carregar usuário: ' . $e->getMessage());
            $this->redirect('/users');
        }
    }
    
    public function update($id) {
        try {
            $user = User::find($id);
            
            if (!$user) {
                $this->setMessage('error', 'Usuário não encontrado');
                $this->redirect('/users');
                return;
            }
            
            $input = $this->getInput();
            
            // Não atualizar senha se vazia
            if (empty($input['password'])) {
                unset($input['password']);
            }
            
            $user->fill($input);
            $result = $user->save();
            
            if ($result === true) {
                $this->setMessage('success', 'Usuário atualizado com sucesso!');
                $this->redirect('/users/' . $user->getKey());
            } else {
                $data = [
                    'title' => 'Editar Usuário',
                    'user' => $user,
                    'errors' => $result,
                    'old' => $input
                ];
                
                $this->render('users/edit', $data);
            }
            
        } catch (\Exception $e) {
            $this->setMessage('error', 'Erro ao atualizar usuário: ' . $e->getMessage());
            $this->redirect('/users');
        }
    }
    
    public function destroy($id) {
        try {
            $user = User::find($id);
            
            if (!$user) {
                $this->setMessage('error', 'Usuário não encontrado');
                $this->redirect('/users');
                return;
            }
            
            if ($user->delete()) {
                $this->setMessage('success', 'Usuário removido com sucesso!');
            } else {
                $this->setMessage('error', 'Erro ao remover usuário');
            }
            
            $this->redirect('/users');
            
        } catch (\Exception $e) {
            $this->setMessage('error', 'Erro ao remover usuário: ' . $e->getMessage());
            $this->redirect('/users');
        }
    }
}
```

### app/Controllers/PostController.php
```php
<?php
namespace App\Controllers;

use App\Models\Post;
use App\Models\User;

class PostController extends BaseController {
    
    public function index() {
        try {
            $posts = Post::getWithUser(20);
            
            $data = [
                'title' => 'Lista de Posts',
                'posts' => $posts
            ];
            
            $this->render('posts/index', $data);
            
        } catch (\Exception $e) {
            $this->setMessage('error', 'Erro ao carregar posts: ' . $e->getMessage());
            $this->render('home/index', ['title' => 'Erro']);
        }
    }
    
    public function show($id) {
        try {
            $post = Post::find($id);
            
            if (!$post) {
                $this->setMessage('error', 'Post não encontrado');
                $this->redirect('/posts');
                return;
            }
            
            $user = $post->user();
            
            $data = [
                'title' => $post->title,
                'post' => $post,
                'user' => $user
            ];
            
            $this->render('posts/show', $data);
            
        } catch (\Exception $e) {
            $this->setMessage('error', 'Erro ao carregar post: ' . $e->getMessage());
            $this->redirect('/posts');
        }
    }
    
    public function create() {
        try {
            $users = User::all();
            
            $data = [
                'title' => 'Novo Post',
                'users' => $users
            ];
            
            $this->render('posts/create', $data);
            
        } catch (\Exception $e) {
            $this->setMessage('error', 'Erro ao carregar formulário: ' . $e->getMessage());
            $this->redirect('/posts');
        }
    }
    
    public function store() {
        try {
            $input = $this->getInput();
            
            $post = new Post();
            $post->fill($input);
            
            $result = $post->save();
            
            if ($result === true) {
                $this->setMessage('success', 'Post criado com sucesso!');
                $this->redirect('/posts/' . $post->getKey());
            } else {
                $users = User::all();
                
                $data = [
                    'title' => 'Novo Post',
                    'users' => $users,
                    'errors' => $result,
                    'old' => $input
                ];
                
                $this->render('posts/create', $data);
            }
            
        } catch (\Exception $e) {
            $this->setMessage('error', 'Erro ao criar post: ' . $e->getMessage());
            $this->redirect('/posts/create');
        }
    }
}
```

## CRUD Completo

### app/Views/users/index.php
```php
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1><?= $title ?></h1>
    <a href="/users/create" class="btn">Novo Usuário</a>
</div>

<?php if (empty($users)): ?>
    <p>Nenhum usuário encontrado.</p>
<?php else: ?>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8f9fa;">
                <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">ID</th>
                <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Nome</th>
                <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Email</th>
                <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Criado em</th>
                <th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td style="border: 1px solid #ddd; padding: 10px;"><?= $user->id ?></td>
                <td style="border: 1px solid #ddd; padding: 10px;"><?= htmlspecialchars($user->name) ?></td>
                <td style="border: 1px solid #ddd; padding: 10px;"><?= htmlspecialchars($user->email) ?></td>
                <td style="border: 1px solid #ddd; padding: 10px;">
                    <?= date('d/m/Y H:i', strtotime($user->created_at)) ?>
                </td>
                <td style="border: 1px solid #ddd; padding: 10px;">
                    <a href="/users/<?= $user->id ?>" style="margin-right: 10px;">Ver</a>
                    <a href="/users/<?= $user->id ?>/edit" style="margin-right: 10px;">Editar</a>
                    <a href="/users/<?= $user->id ?>/delete" 
                       onclick="return confirm('Tem certeza que deseja remover este usuário?')"
                       style="color: red;">Remover</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
```

### app/Views/users/create.php
```php
<h1><?= $title ?></h1>

<?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert error">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $field => $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/users">
    <div class="form-group">
        <label for="name">Nome:</label>
        <input type="text" 
               id="name" 
               name="name" 
               value="<?= htmlspecialchars($old['name'] ?? '') ?>"
               class="<?= isset($errors['name']) ? 'error' : '' ?>">
    </div>
    
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" 
               id="email" 
               name="email" 
               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
               class="<?= isset($errors['email']) ? 'error' : '' ?>">
    </div>
    
    <div class="form-group">
        <label for="password">Senha:</label>
        <input type="password" 
               id="password" 
               name="password"
               class="<?= isset($errors['password']) ? 'error' : '' ?>">
    </div>
    
    <div style="margin-top: 20px;">
        <button type="submit" class="btn">Salvar</button>
        <a href="/users" class="btn" style="background: #6c757d; margin-left: 10px;">Cancelar</a>
    </div>
</form>

<style>
.form-group input.error {
    border-color: #dc3545;
}
</style>
```

### app/Views/users/edit.php
```php
<h1><?= $title ?></h1>

<?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert error">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $field => $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/users/<?= $user->id ?>">
    <input type="hidden" name="_method" value="PUT">
    
    <div class="form-group">
        <label for="name">Nome:</label>
        <input type="text" 
               id="name" 
               name="name" 
               value="<?= htmlspecialchars($old['name'] ?? $user->name) ?>"
               class="<?= isset($errors['name']) ? 'error' : '' ?>">
    </div>
    
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" 
               id="email" 
               name="email" 
               value="<?= htmlspecialchars($old['email'] ?? $user->email) ?>"
               class="<?= isset($errors['email']) ? 'error' : '' ?>">
    </div>
    
    <div class="form-group">
        <label for="password">Nova Senha (deixe vazio para manter atual):</label>
        <input type="password" 
               id="password" 
               name="password"
               class="<?= isset($errors['password']) ? 'error' : '' ?>">
    </div>
    
    <div style="margin-top: 20px;">
        <button type="submit" class="btn">Atualizar</button>
        <a href="/users/<?= $user->id ?>" class="btn" style="background: #6c757d; margin-left: 10px;">Cancelar</a>
    </div>
</form>
```

## Setup do Ambiente

### 1. Criação do Banco de Dados

```sql
-- Criar o banco de dados
CREATE DATABASE mvc_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usar o banco
USE mvc_database;

-- Tabela de usuários
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de posts
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Índices para performance
CREATE INDEX idx_posts_user_id ON posts(user_id);
CREATE INDEX idx_posts_status ON posts(status);
CREATE INDEX idx_posts_created_at ON posts(created_at);
```

### 2. Dados de Teste

```sql
-- Inserir usuários de teste
INSERT INTO users (name, email, password) VALUES
('João Silva', 'joao@exemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- password
('Maria Santos', 'maria@exemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Pedro Costa', 'pedro@exemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Inserir posts de teste
INSERT INTO posts (title, content, user_id, status) VALUES
('Primeiro Post', 'Este é o conteúdo do primeiro post. Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 1, 'published'),
('Segundo Post', 'Conteúdo do segundo post com mais informações interessantes.', 2, 'published'),
('Post em Rascunho', 'Este post ainda está sendo escrito.', 1, 'draft'),
('Tutorial PHP', 'Como criar uma aplicação MVC do zero usando PHP e MySQL.', 3, 'published');
```

### 3. Configuração do Router Atualizada

Atualize o `public/index.php` para incluir rotas de CRUD:

```php
// Rotas de usuários
$router->get('/users', 'User', 'index');
$router->get('/users/create', 'User', 'create');
$router->post('/users', 'User', 'store');
$router->get('/users/{id}', 'User', 'show');
$router->get('/users/{id}/edit', 'User', 'edit');
$router->post('/users/{id}', 'User', 'update');
$router->get('/users/{id}/delete', 'User', 'destroy');

// Rotas de posts
$router->get('/posts', 'Post', 'index');
$router->get('/posts/create', 'Post', 'create');
$router->post('/posts', 'Post', 'store');
$router->get('/posts/{id}', 'Post', 'show');
$router->get('/posts/{id}/edit', 'Post', 'edit');
$router->post('/posts/{id}', 'Post', 'update');
```

## Resolução de Problemas

### Problema 1: "Connection refused"
**Sintomas**: Erro de conexão com MySQL
**Soluções**:
```php
// Verificar se o MySQL está rodando
// No WAMP: Verificar se o serviço MySQL está verde

// Testar conexão diretamente
try {
    $pdo = new PDO("mysql:host=localhost;dbname=mvc_database", "root", "");
    echo "Conexão OK";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}

// Verificar configurações no config/database.php
```

### Problema 2: "Table doesn't exist"
**Sintomas**: Erro indicando que tabela não existe
**Soluções**:
```sql
-- Verificar se o banco foi criado
SHOW DATABASES;

-- Verificar se as tabelas existem
USE mvc_database;
SHOW TABLES;

-- Verificar estrutura da tabela
DESCRIBE users;
DESCRIBE posts;
```

### Problema 3: "Call to undefined method"
**Sintomas**: Erro de método não encontrado no Model
**Soluções**:
```php
// Verificar se a classe Database está sendo carregada
$db = \Core\Database::getInstance();
var_dump($db);

// Verificar se os métodos existem
if (method_exists($db, 'fetchAll')) {
    echo "Método existe";
} else {
    echo "Método não existe";
}
```

### Problema 4: "Foreign key constraint fails"
**Sintomas**: Erro ao inserir/deletar com chaves estrangeiras
**Soluções**:
```sql
-- Verificar integridade referencial
SELECT * FROM posts WHERE user_id NOT IN (SELECT id FROM users);

-- Temporariamente desabilitar checks (só para debug)
SET FOREIGN_KEY_CHECKS = 0;
-- Executar operação
SET FOREIGN_KEY_CHECKS = 1;
```

### Problema 5: "Headers already sent"
**Sintomas**: Erro ao fazer redirect
**Soluções**:
```php
// Verificar se não há output antes do redirect
// Remover espaços em branco antes de <?php

// Usar output buffering se necessário
ob_start();
// seu código
ob_end_clean();
header("Location: /users");
exit;
```

### Problema 6: Validação não funciona
**Sintomas**: Dados inválidos são salvos
**Soluções**:
```php
// Debug da validação
public function save() {
    $errors = $this->validate();
    
    // Debug
    var_dump('Errors:', $errors);
    var_dump('Attributes:', $this->attributes);
    
    if (!empty($errors)) {
        return $errors;
    }
    
    return parent::save();
}
```

### Debug Helper Avançado

```php
// Adicionar ao BaseController
protected function debugSql($sql, $params = []) {
    echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px 0; border: 1px solid #ddd;'>";
    echo "<strong>SQL:</strong> " . htmlspecialchars($sql) . "<br>";
    echo "<strong>Params:</strong> " . htmlspecialchars(json_encode($params));
    echo "</div>";
}

// Usar nos controllers
$this->debugSql("SELECT * FROM users WHERE id = ?", [$id]);
```

---

**Importante**: Este exemplo implementa as funcionalidades básicas de um ORM. Para produção, considere usar ORMs estabelecidos como Eloquent, Doctrine ou frameworks como Laravel que incluem essas funcionalidades de forma mais robusta e segura.
