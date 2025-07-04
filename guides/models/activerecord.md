# 🔗 ActiveRecord ORM - Guia Completo

## Visão Geral

O **ActiveRecord** é o ORM (Object-Relational Mapping) utilizado no sistema GEstufas. Ele fornece uma interface orientada a objetos para interagir com a base de dados, permitindo operações CRUD (Create, Read, Update, Delete) de forma simples e intuitiva.

## 📚 Conceitos Fundamentais

### **O que é ActiveRecord?**
- **Padrão de Design:** Cada classe representa uma tabela da BD
- **Instância = Registro:** Cada objeto representa uma linha da tabela
- **Métodos Automáticos:** CRUD gerado automaticamente
- **Relacionamentos:** Associações entre tabelas simplificadas

### **Vantagens**
- ✅ Código limpo e legível
- ✅ Validações automáticas
- ✅ Relacionamentos simples
- ✅ Migrações (parcial)
- ✅ Proteção contra SQL Injection

## ⚙️ Configuração

### **Configuração Inicial (startup/config.php)**
```php
<?php
require_once 'vendor/autoload.php';

// Configuração do ActiveRecord
ActiveRecord\Config::initialize(function($cfg) {
    // Diretório dos models
    $cfg->set_model_directory('models');
    
    // Conexões de base de dados
    $cfg->set_connections([
        'development' => 'mysql://root:@localhost/gestufas?charset=utf8mb4',
        'production' => 'mysql://user:pass@host/gestufas?charset=utf8mb4'
    ]);
    
    // Ambiente padrão
    $cfg->set_default_connection('development');
    
    // Configurações adicionais
    $cfg->set_logging(true); // Log de queries SQL
});
```

### **Configuração Avançada**
```php
ActiveRecord\Config::initialize(function($cfg) {
    $cfg->set_model_directory('models');
    $cfg->set_connections([
        'development' => [
            'connection_string' => 'mysql://root:@localhost/gestufas',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci'
        ]
    ]);
    
    // Cache de conexões
    $cfg->set_cache('memcache://localhost:11211');
    
    // Log personalizado
    $cfg->set_logger(new FileLogger('logs/activerecord.log'));
});
```

## 📊 Criação de Models

### **Model Básico**
```php
<?php
// models/User.php
class User extends ActiveRecord\Model {
    // O nome da tabela é inferido automaticamente: "users"
    // Pode ser especificado explicitamente:
    static $table_name = 'users';
    
    // Chave primária (padrão: 'id')
    static $primary_key = 'id';
    
    // Conexão específica (opcional)
    static $connection = 'development';
}
```

### **Model com Configurações**
```php
<?php
class Post extends ActiveRecord\Model {
    static $table_name = 'posts';
    
    // Campos que podem ser atribuídos em massa
    static $attr_accessible = [
        'title', 'content', 'user_id', 'published'
    ];
    
    // Campos protegidos
    static $attr_protected = [
        'id', 'created_at', 'updated_at'
    ];
    
    // Timestamps automáticos
    static $timestamps = true; // created_at, updated_at
    
    // Campos de data
    static $datetime = [
        'created_at', 'updated_at', 'published_at'
    ];
}
```

## 🔍 Operações de Leitura (Read)

### **Buscar Todos os Registros**
```php
// Buscar todos os utilizadores
$users = User::all();

// Com condições
$activeUsers = User::all([
    'conditions' => 'active = 1',
    'order' => 'name ASC',
    'limit' => 10
]);

// Com array de condições
$users = User::all([
    'conditions' => ['active = ? AND role = ?', 1, 'admin'],
    'select' => 'id, name, email'
]);
```

### **Buscar por ID**
```php
// Buscar por chave primária
$user = User::find(1);

// Buscar múltiplos IDs
$users = User::find([1, 2, 3]);

// find() com condições
$user = User::find('first', [
    'conditions' => ['email = ?', 'user@example.com']
]);
```

### **Métodos de Busca**
```php
// Primeiro registro
$firstUser = User::first();
$firstActive = User::first(['conditions' => 'active = 1']);

// Último registro
$lastUser = User::last();

// Contar registros
$totalUsers = User::count();
$activeCount = User::count(['conditions' => 'active = 1']);

// Verificar existência
$exists = User::exists(1);
$emailExists = User::exists(['conditions' => ['email = ?', 'test@test.com']]);
```

### **Consultas Complexas**
```php
// Query builder
$users = User::find('all', [
    'conditions' => [
        'active = ? AND created_at > ? AND role IN (?)',
        1,
        '2024-01-01',
        ['admin', 'editor']
    ],
    'order' => 'created_at DESC',
    'limit' => 20,
    'offset' => 40,
    'include' => ['posts'] // Eager loading
]);

// SQL personalizado
$users = User::find_by_sql(
    "SELECT * FROM users WHERE YEAR(created_at) = ? AND active = 1",
    [2024]
);
```

## ✏️ Operações de Criação (Create)

### **Criar Novo Registro**
```php
// Método 1: new + save
$user = new User();
$user->name = 'João Silva';
$user->email = 'joao@example.com';
$user->password = password_hash('123456', PASSWORD_DEFAULT);
$user->save();

// Método 2: create (salva automaticamente)
$user = User::create([
    'name' => 'Maria Santos',
    'email' => 'maria@example.com',
    'password' => password_hash('654321', PASSWORD_DEFAULT)
]);

// Método 3: atribuição em massa
$user = new User([
    'name' => 'Pedro Costa',
    'email' => 'pedro@example.com'
]);
$user->save();
```

### **Validação na Criação**
```php
$user = new User();
$user->name = 'Nome Válido';
$user->email = 'email-invalido'; // Email inválido

if ($user->save()) {
    echo "Utilizador criado com sucesso!";
} else {
    // Mostrar erros de validação
    foreach ($user->errors->full_messages() as $error) {
        echo "Erro: $error\n";
    }
}
```

## 🔄 Operações de Atualização (Update)

### **Atualizar Registro Existente**
```php
// Buscar e atualizar
$user = User::find(1);
$user->name = 'Novo Nome';
$user->email = 'novo@email.com';
$user->save();

// Atualização direta
$user = User::find(1);
$user->update_attributes([
    'name' => 'Nome Atualizado',
    'active' => true
]);

// Atualização em massa
User::update_all(
    "active = 0",
    "last_login < '2023-01-01'"
);
```

### **Métodos de Atualização**
```php
// save() - salva alterações
$user = User::find(1);
$user->name = 'Novo Nome';
$success = $user->save(); // retorna true/false

// update_attributes() - atualiza e salva
$success = $user->update_attributes([
    'name' => 'Nome',
    'email' => 'email@test.com'
]);

// update_attribute() - atualiza um campo sem validação
$user->update_attribute('last_login', new DateTime());
```

## 🗑️ Operações de Eliminação (Delete)

### **Eliminar Registros**
```php
// Buscar e eliminar
$user = User::find(1);
$user->delete();

// Eliminar por ID
User::delete(1);

// Eliminar múltiplos IDs
User::delete([1, 2, 3]);

// Eliminar com condições
User::delete_all("active = 0 AND created_at < '2023-01-01'");
```

### **Soft Delete (Opcional)**
```php
// Implementar soft delete manualmente
class User extends ActiveRecord\Model {
    static $before_delete = ['soft_delete'];
    
    public function soft_delete() {
        $this->deleted_at = new DateTime();
        $this->save();
        
        // Prevenir eliminação real
        return false;
    }
    
    // Scope para registros ativos
    public static function active() {
        return static::all(['conditions' => 'deleted_at IS NULL']);
    }
}
```

## 🔗 Relacionamentos

### **Has Many (Um para Muitos)**
```php
class User extends ActiveRecord\Model {
    static $has_many = [
        ['posts'],
        ['comments'],
        ['projects', 'foreign_key' => 'owner_id']
    ];
}

// Usar relacionamento
$user = User::find(1);
$posts = $user->posts; // Retorna array de Posts
$postCount = count($user->posts);
```

### **Belongs To (Muitos para Um)**
```php
class Post extends ActiveRecord\Model {
    static $belongs_to = [
        ['user'],
        ['category', 'class_name' => 'PostCategory']
    ];
}

// Usar relacionamento
$post = Post::find(1);
$author = $post->user; // Retorna objeto User
echo $post->user->name;
```

### **Has Many Through (Muitos para Muitos)**
```php
class User extends ActiveRecord\Model {
    static $has_many = [
        ['posts'],
        ['tags', 'through' => 'posts']
    ];
}

class Post extends ActiveRecord\Model {
    static $belongs_to = [['user']];
    static $has_many = [['tags', 'through' => 'post_tags']];
}

class Tag extends ActiveRecord\Model {
    static $has_many = [['posts', 'through' => 'post_tags']];
}
```

### **Relacionamentos Personalizados**
```php
class User extends ActiveRecord\Model {
    static $has_many = [
        [
            'recent_posts',
            'class_name' => 'Post',
            'conditions' => 'created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)',
            'order' => 'created_at DESC'
        ]
    ];
}
```

## ✅ Validações

### **Validações Básicas**
```php
class User extends ActiveRecord\Model {
    // Campos obrigatórios
    static $validates_presence_of = [
        ['name', 'message' => 'Nome é obrigatório'],
        ['email']
    ];
    
    // Unicidade
    static $validates_uniqueness_of = [
        ['email', 'message' => 'Email já existe']
    ];
    
    // Formato (regex)
    static $validates_format_of = [
        ['email', 'with' => '/\A[^@\s]+@[^@\s]+\z/', 'message' => 'Email inválido']
    ];
    
    // Tamanho
    static $validates_length_of = [
        ['name', 'minimum' => 2, 'maximum' => 100],
        ['password', 'minimum' => 6]
    ];
    
    // Inclusão em lista
    static $validates_inclusion_of = [
        ['role', 'in' => ['admin', 'editor', 'user']]
    ];
}
```

### **Validações Personalizadas**
```php
class User extends ActiveRecord\Model {
    static $validates_presence_of = [['name', 'email']];
    
    // Validação personalizada
    static $before_validation = ['validate_email_domain'];
    
    public function validate_email_domain() {
        if ($this->email && !str_ends_with($this->email, '@example.com')) {
            $this->errors->add('email', 'deve ser do domínio @example.com');
        }
    }
    
    // Validação condicional
    static $before_save = ['validate_password_confirmation'];
    
    public function validate_password_confirmation() {
        if ($this->is_new_record() && $this->password !== $this->password_confirmation) {
            $this->errors->add('password_confirmation', 'não confere');
        }
    }
}
```

## 🔄 Callbacks

### **Callbacks Disponíveis**
```php
class User extends ActiveRecord\Model {
    // Antes da validação
    static $before_validation = ['normalize_data'];
    
    // Depois da validação
    static $after_validation = ['log_validation'];
    
    // Antes de salvar
    static $before_save = ['encrypt_password'];
    
    // Depois de salvar
    static $after_save = ['send_welcome_email'];
    
    // Antes de criar
    static $before_create = ['set_defaults'];
    
    // Depois de criar
    static $after_create = ['create_profile'];
    
    // Antes de atualizar
    static $before_update = ['log_changes'];
    
    // Depois de atualizar
    static $after_update = ['clear_cache'];
    
    // Antes de eliminar
    static $before_delete = ['check_dependencies'];
    
    // Depois de eliminar
    static $after_delete = ['cleanup_files'];
    
    public function encrypt_password() {
        if ($this->password && $this->is_dirty('password')) {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        }
    }
    
    public function set_defaults() {
        $this->role = $this->role ?: 'user';
        $this->active = $this->active ?? true;
    }
}
```

## 🔧 Métodos Utilitários

### **Verificação de Estado**
```php
$user = new User();
$user->is_new_record(); // true (ainda não salvo)

$user = User::find(1);
$user->is_new_record(); // false (já existe na BD)

$user->name = 'Novo Nome';
$user->is_dirty(); // true (tem alterações não salvas)
$user->is_dirty('name'); // true (campo específico alterado)

$user->save();
$user->is_dirty(); // false (alterações salvas)
```

### **Métodos de Conversão**
```php
$user = User::find(1);

// Para array
$userArray = $user->to_array();

// Para JSON
$userJson = $user->to_json();

// Attributes
$attributes = $user->attributes();

// Valores anteriores
$user->name = 'Novo Nome';
$oldName = $user->name_was(); // valor anterior
```

### **Transações**
```php
// Transação básica
ActiveRecord\Connection::get_connection()->transaction(function() {
    $user = User::create(['name' => 'João']);
    $post = Post::create(['title' => 'Título', 'user_id' => $user->id]);
});

// Com tratamento de erros
try {
    ActiveRecord\Connection::get_connection()->transaction(function() {
        // Operações que devem ser atómicas
        $user = User::create(['name' => 'Maria']);
        $profile = Profile::create(['user_id' => $user->id, 'bio' => 'Bio']);
        
        if (!$user->valid() || !$profile->valid()) {
            throw new Exception('Dados inválidos');
        }
    });
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
```

## 📈 Performance e Otimização

### **Eager Loading**
```php
// Problema N+1
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->user->name; // Query para cada post
}

// Solução: Eager Loading
$posts = Post::all(['include' => ['user']]);
foreach ($posts as $post) {
    echo $post->user->name; // User já carregado
}

// Múltiplos relacionamentos
$posts = Post::all(['include' => ['user', 'comments']]);
```

### **Cache de Queries**
```php
// Cache manual
class User extends ActiveRecord\Model {
    public static function findActiveUsers() {
        $cacheKey = 'active_users';
        $users = Cache::get($cacheKey);
        
        if (!$users) {
            $users = static::all(['conditions' => 'active = 1']);
            Cache::put($cacheKey, $users, 3600); // 1 hora
        }
        
        return $users;
    }
}
```

### **Consultas Eficientes**
```php
// Evitar
$users = User::all();
$activeUsers = array_filter($users, fn($u) => $u->active);

// Preferir
$activeUsers = User::all(['conditions' => 'active = 1']);

// Select específico
$users = User::all([
    'select' => 'id, name, email', // Apenas campos necessários
    'limit' => 20
]);
```

## 🔍 Debug e Logs

### **Log de Queries SQL**
```php
// Ativar logging
ActiveRecord\Config::initialize(function($cfg) {
    $cfg->set_logging(true);
    $cfg->set_logger(new ActiveRecord\DatabaseLogger());
});

// Ver última query
echo ActiveRecord\Connection::get_connection()->get_last_query();
```

### **Debug de Models**
```php
$user = User::find(1);

// Ver attributes
var_dump($user->attributes());

// Ver erros de validação
if (!$user->save()) {
    var_dump($user->errors->full_messages());
}

// Ver relacionamentos carregados
var_dump($user->__relationships);
```

---

## 📋 Exemplo Prático Completo

```php
<?php
// models/Article.php
class Article extends ActiveRecord\Model {
    static $table_name = 'articles';
    
    // Relacionamentos
    static $belongs_to = [['user', 'foreign_key' => 'author_id']];
    static $has_many = [['comments'], ['tags', 'through' => 'article_tags']];
    
    // Validações
    static $validates_presence_of = [['title', 'content']];
    static $validates_length_of = [
        ['title', 'minimum' => 5, 'maximum' => 200],
        ['content', 'minimum' => 50]
    ];
    
    // Callbacks
    static $before_save = ['generate_slug', 'set_published_at'];
    static $after_create = ['notify_followers'];
    
    // Scopes personalizados
    public static function published() {
        return static::all(['conditions' => 'published = 1 AND published_at <= NOW()']);
    }
    
    public static function byAuthor($authorId) {
        return static::all(['conditions' => ['author_id = ?', $authorId]]);
    }
    
    // Métodos personalizados
    public function generate_slug() {
        if ($this->is_dirty('title')) {
            $this->slug = strtolower(str_replace(' ', '-', $this->title));
        }
    }
    
    public function set_published_at() {
        if ($this->published && !$this->published_at) {
            $this->published_at = new DateTime();
        }
    }
    
    public function notify_followers() {
        // Notificar seguidores do autor
        $followers = $this->user->followers;
        foreach ($followers as $follower) {
            // Enviar notificação
        }
    }
    
    public function getExcerpt($length = 150) {
        return substr(strip_tags($this->content), 0, $length) . '...';
    }
    
    public function isPublished() {
        return $this->published && $this->published_at <= new DateTime();
    }
}

// Uso do model
$article = new Article([
    'title' => 'Meu Primeiro Artigo',
    'content' => 'Conteúdo do artigo com mais de 50 caracteres...',
    'author_id' => 1
]);

if ($article->save()) {
    echo "Artigo criado: " . $article->title;
    echo "Slug: " . $article->slug;
    echo "Autor: " . $article->user->name;
} else {
    foreach ($article->errors->full_messages() as $error) {
        echo "Erro: $error";
    }
}
```

O ActiveRecord fornece uma interface poderosa e intuitiva para trabalhar com dados, mantendo o código limpo e organizando a lógica de negócio nos models onde pertence.
