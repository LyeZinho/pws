# MVC com ActiveRecord - Guia Completo de Implementação

## Índice
- [Introdução](#introdução)
- [Pré-requisitos](#pré-requisitos)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Setup do ActiveRecord](#setup-do-activerecord)
- [Models com ActiveRecord](#models-com-activerecord)
- [Controllers](#controllers)
- [Views](#views)
- [Rotas](#rotas)
- [Configuração do Banco](#configuração-do-banco)
- [Migrations com ActiveRecord](#migrations-com-activerecord)
- [Exemplos Práticos](#exemplos-práticos)
- [Relacionamentos](#relacionamentos)
- [Validações](#validações)
- [Queries Avançadas](#queries-avançadas)
- [Troubleshooting](#troubleshooting)
- [Boas Práticas](#boas-práticas)

## Introdução

Este guia implementa um sistema MVC completo usando **PHP ActiveRecord**, uma biblioteca ORM que simplifica drasticamente o trabalho com bancos de dados em PHP, seguindo o padrão Active Record usado em frameworks como Ruby on Rails.

### Benefícios do ActiveRecord:
- **Mapeamento automático** de tabelas para classes
- **Relacionamentos declarativos** (hasMany, belongsTo, etc.)
- **Validações integradas** nos models
- **Query builder fluente** e intuitivo
- **Migrations** para versionamento do banco
- **Callbacks** para eventos do modelo

## Pré-requisitos

1. **Guias anteriores**:
   - [01-mvc-basico.md](01-mvc-basico.md)
   - [02-mvc-mysql.md](02-mvc-mysql.md)
   - [03-mvc-autenticacao.md](03-mvc-autenticacao.md)

2. **Ambiente**:
   - PHP 7.4+
   - MySQL 5.7+
   - Composer
   - WAMP/XAMPP

## Estrutura do Projeto

```
mvc-activerecord/
├── composer.json
├── composer.lock
├── config/
│   ├── database.php
│   └── app.php
├── app/
│   ├── models/
│   │   ├── BaseModel.php
│   │   ├── User.php
│   │   ├── Post.php
│   │   └── Comment.php
│   ├── controllers/
│   │   ├── BaseController.php
│   │   ├── HomeController.php
│   │   ├── UserController.php
│   │   ├── PostController.php
│   │   └── AuthController.php
│   └── views/
│       ├── layouts/
│       │   └── main.php
│       ├── users/
│       ├── posts/
│       └── auth/
├── core/
│   ├── Router.php
│   └── App.php
├── public/
│   ├── index.php
│   ├── css/
│   └── js/
├── migrations/
├── seeds/
├── logs/
└── vendor/
```

## Setup do ActiveRecord

### 1. Instalar via Composer

```bash
# Navegue até o diretório do projeto
cd mvc-activerecord

# Initialize composer se ainda não existir
composer init

# Instalar ActiveRecord
composer require php-activerecord/php-activerecord

# Instalar dependências opcionais
composer require vlucas/phpdotenv
composer require monolog/monolog
```

### 2. composer.json Completo

```json
{
    "name": "seu-usuario/mvc-activerecord",
    "description": "Sistema MVC com PHP ActiveRecord",
    "type": "project",
    "require": {
        "php": ">=7.4",
        "php-activerecord/php-activerecord": "^1.19",
        "vlucas/phpdotenv": "^5.4",
        "monolog/monolog": "^2.8"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Core\\": "core/"
        }
    },
    "scripts": {
        "serve": "php -S localhost:8000 -t public/",
        "migrate": "php migrations/migrate.php",
        "seed": "php seeds/seed.php"
    }
}
```

### 3. Configuração do Banco (.env)

```env
# .env
DB_HOST=localhost
DB_NAME=mvc_activerecord
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

# App Config
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Logs
LOG_LEVEL=debug
```

### 4. Configuração Principal

```php
<?php
// config/database.php

use ActiveRecord\Config;

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configurar ActiveRecord
Config::initialize(function($cfg) {
    $cfg->set_model_directory(__DIR__ . '/../app/models');
    $cfg->set_connections([
        'development' => sprintf(
            'mysql://%s:%s@%s/%s?charset=%s',
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'],
            $_ENV['DB_HOST'],
            $_ENV['DB_NAME'],
            $_ENV['DB_CHARSET']
        )
    ]);
    
    $cfg->set_default_connection('development');
    
    // Logs de SQL em desenvolvimento
    if ($_ENV['APP_DEBUG'] === 'true') {
        $cfg->set_logging(true);
        $cfg->set_logger(function($message) {
            error_log("[ActiveRecord] " . $message);
        });
    }
});
```

## Models com ActiveRecord

### 1. BaseModel

```php
<?php
// app/models/BaseModel.php

namespace App\Models;

use ActiveRecord\Model;
use ActiveRecord\DateTime;

abstract class BaseModel extends Model
{
    // Timestamps automáticos
    static $before_create = ['set_timestamps_create'];
    static $before_update = ['set_timestamps_update'];
    
    /**
     * Define timestamps na criação
     */
    public function set_timestamps_create()
    {
        $now = new DateTime();
        $this->created_at = $now;
        $this->updated_at = $now;
    }
    
    /**
     * Atualiza timestamp na modificação
     */
    public function set_timestamps_update()
    {
        $this->updated_at = new DateTime();
    }
    
    /**
     * Scope para registros ativos
     */
    public static function active()
    {
        return static::find('all', [
            'conditions' => ['deleted_at IS NULL']
        ]);
    }
    
    /**
     * Soft delete
     */
    public function softDelete()
    {
        $this->deleted_at = new DateTime();
        return $this->save();
    }
    
    /**
     * Verifica se está deletado
     */
    public function isDeleted()
    {
        return !is_null($this->deleted_at);
    }
    
    /**
     * Formatar data para exibição
     */
    public function formatDate($field, $format = 'd/m/Y H:i')
    {
        if ($this->$field instanceof DateTime) {
            return $this->$field->format($format);
        }
        return $this->$field ? date($format, strtotime($this->$field)) : '';
    }
    
    /**
     * Converter para array (útil para APIs)
     */
    public function toArray($exclude = [])
    {
        $attributes = $this->attributes();
        
        // Remover campos sensíveis por padrão
        $defaultExclude = ['password', 'password_hash', 'deleted_at'];
        $exclude = array_merge($defaultExclude, $exclude);
        
        foreach ($exclude as $field) {
            unset($attributes[$field]);
        }
        
        return $attributes;
    }
}
```

### 2. Model User

```php
<?php
// app/models/User.php

namespace App\Models;

use ActiveRecord\DateTime;

class User extends BaseModel
{
    // Nome da tabela (opcional se seguir convenção)
    static $table_name = 'users';
    
    // Chave primária (opcional se for 'id')
    static $primary_key = 'id';
    
    // Relacionamentos
    static $has_many = [
        ['posts', 'class_name' => 'Post'],
        ['comments', 'class_name' => 'Comment']
    ];
    
    // Validações
    static $validates_presence_of = [
        ['name', 'message' => 'Nome é obrigatório'],
        ['email', 'message' => 'Email é obrigatório']
    ];
    
    static $validates_uniqueness_of = [
        ['email', 'message' => 'Este email já está em uso']
    ];
    
    static $validates_format_of = [
        ['email', 'with' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/', 'message' => 'Email inválido']
    ];
    
    static $validates_length_of = [
        ['name', 'minimum' => 2, 'maximum' => 100],
        ['password', 'minimum' => 6, 'on' => 'create']
    ];
    
    // Callbacks
    static $before_create = ['hash_password', 'set_timestamps_create'];
    static $before_update = ['hash_password_if_changed', 'set_timestamps_update'];
    
    /**
     * Hash da senha antes de salvar
     */
    public function hash_password()
    {
        if (!empty($this->password)) {
            $this->password_hash = password_hash($this->password, PASSWORD_DEFAULT);
            unset($this->password); // Remove a senha em texto plano
        }
    }
    
    /**
     * Hash da senha apenas se foi alterada
     */
    public function hash_password_if_changed()
    {
        if ($this->changed('password') && !empty($this->password)) {
            $this->hash_password();
        }
    }
    
    /**
     * Verificar senha
     */
    public function checkPassword($password)
    {
        return password_verify($password, $this->password_hash);
    }
    
    /**
     * Buscar por email
     */
    public static function findByEmail($email)
    {
        return static::find('first', [
            'conditions' => ['email = ?', $email]
        ]);
    }
    
    /**
     * Scope para usuários ativos
     */
    public static function active()
    {
        return static::find('all', [
            'conditions' => ['active = ? AND deleted_at IS NULL', true]
        ]);
    }
    
    /**
     * Nome completo
     */
    public function getFullName()
    {
        return trim($this->name . ' ' . ($this->last_name ?? ''));
    }
    
    /**
     * Avatar padrão
     */
    public function getAvatar()
    {
        return $this->avatar ?: 'https://via.placeholder.com/150x150?text=' . substr($this->name, 0, 1);
    }
    
    /**
     * Estatísticas do usuário
     */
    public function getStats()
    {
        return [
            'posts_count' => $this->posts()->count(),
            'comments_count' => $this->comments()->count(),
            'member_since' => $this->formatDate('created_at', 'd/m/Y')
        ];
    }
}
```

### 3. Model Post

```php
<?php
// app/models/Post.php

namespace App\Models;

use ActiveRecord\DateTime;

class Post extends BaseModel
{
    static $table_name = 'posts';
    
    // Relacionamentos
    static $belongs_to = [
        ['user', 'class_name' => 'User', 'foreign_key' => 'user_id']
    ];
    
    static $has_many = [
        ['comments', 'class_name' => 'Comment', 'order' => 'created_at DESC']
    ];
    
    // Validações
    static $validates_presence_of = [
        ['title', 'message' => 'Título é obrigatório'],
        ['content', 'message' => 'Conteúdo é obrigatório'],
        ['user_id', 'message' => 'Usuário é obrigatório']
    ];
    
    static $validates_length_of = [
        ['title', 'minimum' => 5, 'maximum' => 255],
        ['content', 'minimum' => 10]
    ];
    
    // Callbacks
    static $before_save = ['generate_slug'];
    
    /**
     * Gerar slug automaticamente
     */
    public function generate_slug()
    {
        if (empty($this->slug) || $this->changed('title')) {
            $this->slug = $this->createSlug($this->title);
        }
    }
    
    /**
     * Criar slug único
     */
    private function createSlug($text)
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
        $originalSlug = $slug;
        $counter = 1;
        
        // Garantir que o slug seja único
        while (static::find('first', ['conditions' => ['slug = ? AND id != ?', $slug, $this->id ?: 0]])) {
            $slug = $originalSlug . '-' . $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Buscar por slug
     */
    public static function findBySlug($slug)
    {
        return static::find('first', [
            'conditions' => ['slug = ?', $slug],
            'include' => ['user']
        ]);
    }
    
    /**
     * Posts publicados
     */
    public static function published()
    {
        return static::find('all', [
            'conditions' => ['status = ? AND published_at <= ?', 'published', new DateTime()],
            'order' => 'published_at DESC',
            'include' => ['user']
        ]);
    }
    
    /**
     * Posts por categoria
     */
    public static function byCategory($category)
    {
        return static::find('all', [
            'conditions' => ['category = ? AND status = ?', $category, 'published'],
            'order' => 'published_at DESC'
        ]);
    }
    
    /**
     * Resumo do post
     */
    public function getExcerpt($length = 150)
    {
        $content = strip_tags($this->content);
        return strlen($content) > $length ? substr($content, 0, $length) . '...' : $content;
    }
    
    /**
     * URL do post
     */
    public function getUrl()
    {
        return "/posts/{$this->slug}";
    }
    
    /**
     * Verificar se está publicado
     */
    public function isPublished()
    {
        return $this->status === 'published' && 
               $this->published_at && 
               $this->published_at <= new DateTime();
    }
    
    /**
     * Publicar post
     */
    public function publish()
    {
        $this->status = 'published';
        $this->published_at = new DateTime();
        return $this->save();
    }
}
```

### 4. Model Comment

```php
<?php
// app/models/Comment.php

namespace App\Models;

class Comment extends BaseModel
{
    static $table_name = 'comments';
    
    // Relacionamentos
    static $belongs_to = [
        ['user', 'class_name' => 'User'],
        ['post', 'class_name' => 'Post']
    ];
    
    // Validações
    static $validates_presence_of = [
        ['content', 'message' => 'Comentário é obrigatório'],
        ['user_id', 'message' => 'Usuário é obrigatório'],
        ['post_id', 'message' => 'Post é obrigatório']
    ];
    
    static $validates_length_of = [
        ['content', 'minimum' => 3, 'maximum' => 1000]
    ];
    
    /**
     * Comentários aprovados
     */
    public static function approved()
    {
        return static::find('all', [
            'conditions' => ['status = ?', 'approved'],
            'order' => 'created_at ASC'
        ]);
    }
    
    /**
     * Aprovar comentário
     */
    public function approve()
    {
        $this->status = 'approved';
        return $this->save();
    }
    
    /**
     * Verificar se está aprovado
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }
}
```

## Controllers

### 1. BaseController

```php
<?php
// app/controllers/BaseController.php

namespace App\Controllers;

abstract class BaseController
{
    protected $request;
    protected $response;
    protected $user;
    
    public function __construct()
    {
        session_start();
        $this->loadCurrentUser();
    }
    
    /**
     * Carregar usuário atual
     */
    protected function loadCurrentUser()
    {
        if (isset($_SESSION['user_id'])) {
            $this->user = \App\Models\User::find($_SESSION['user_id']);
        }
    }
    
    /**
     * Verificar se usuário está logado
     */
    protected function requireAuth()
    {
        if (!$this->user) {
            $this->redirect('/login');
            exit;
        }
    }
    
    /**
     * Renderizar view
     */
    protected function render($view, $data = [])
    {
        // Dados globais disponíveis em todas as views
        $data['user'] = $this->user;
        $data['flash'] = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        
        extract($data);
        
        ob_start();
        include __DIR__ . "/../views/{$view}.php";
        $content = ob_get_clean();
        
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Retornar JSON
     */
    protected function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Redirecionar
     */
    protected function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Definir flash message
     */
    protected function flash($type, $message)
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }
    
    /**
     * Validar dados
     */
    protected function validate($rules, $data)
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = $rule['message'] ?? "Campo {$field} é obrigatório";
                continue;
            }
            
            if (isset($rule['min']) && strlen($value) < $rule['min']) {
                $errors[$field] = "Campo {$field} deve ter pelo menos {$rule['min']} caracteres";
                continue;
            }
            
            if (isset($rule['max']) && strlen($value) > $rule['max']) {
                $errors[$field] = "Campo {$field} deve ter no máximo {$rule['max']} caracteres";
                continue;
            }
            
            if (isset($rule['email']) && $rule['email'] && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "Campo {$field} deve ser um email válido";
                continue;
            }
        }
        
        return $errors;
    }
}
```

### 2. PostController

```php
<?php
// app/controllers/PostController.php

namespace App\Controllers;

use App\Models\Post;
use App\Models\Comment;

class PostController extends BaseController
{
    /**
     * Listar posts
     */
    public function index()
    {
        $page = $_GET['page'] ?? 1;
        $perPage = 10;
        
        // Paginação com ActiveRecord
        $posts = Post::find('all', [
            'conditions' => ['status = ?', 'published'],
            'order' => 'published_at DESC',
            'include' => ['user'],
            'limit' => $perPage,
            'offset' => ($page - 1) * $perPage
        ]);
        
        $total = Post::count(['conditions' => ['status = ?', 'published']]);
        $totalPages = ceil($total / $perPage);
        
        $this->render('posts/index', [
            'posts' => $posts,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }
    
    /**
     * Exibir post
     */
    public function show($slug)
    {
        $post = Post::findBySlug($slug);
        
        if (!$post || !$post->isPublished()) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }
        
        // Carregar comentários aprovados
        $comments = Comment::find('all', [
            'conditions' => ['post_id = ? AND status = ?', $post->id, 'approved'],
            'include' => ['user'],
            'order' => 'created_at ASC'
        ]);
        
        $this->render('posts/show', [
            'post' => $post,
            'comments' => $comments
        ]);
    }
    
    /**
     * Formulário de novo post
     */
    public function create()
    {
        $this->requireAuth();
        $this->render('posts/create');
    }
    
    /**
     * Salvar novo post
     */
    public function store()
    {
        $this->requireAuth();
        
        $data = [
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'category' => $_POST['category'] ?? '',
            'status' => $_POST['status'] ?? 'draft'
        ];
        
        // Validações manuais adicionais
        $errors = $this->validate([
            'title' => ['required' => true, 'min' => 5, 'max' => 255],
            'content' => ['required' => true, 'min' => 10],
            'category' => ['required' => true]
        ], $data);
        
        if (!empty($errors)) {
            $this->render('posts/create', [
                'errors' => $errors,
                'post' => (object) $data
            ]);
            return;
        }
        
        try {
            $post = new Post($data);
            $post->user_id = $this->user->id;
            
            if ($data['status'] === 'published') {
                $post->published_at = new \ActiveRecord\DateTime();
            }
            
            if ($post->save()) {
                $this->flash('success', 'Post criado com sucesso!');
                $this->redirect("/posts/{$post->slug}");
            } else {
                $this->render('posts/create', [
                    'errors' => $post->errors->get_raw_errors(),
                    'post' => $post
                ]);
            }
        } catch (\Exception $e) {
            $this->flash('error', 'Erro ao criar post: ' . $e->getMessage());
            $this->render('posts/create', ['post' => (object) $data]);
        }
    }
    
    /**
     * Formulário de edição
     */
    public function edit($id)
    {
        $this->requireAuth();
        
        $post = Post::find($id);
        
        if (!$post || $post->user_id != $this->user->id) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }
        
        $this->render('posts/edit', ['post' => $post]);
    }
    
    /**
     * Atualizar post
     */
    public function update($id)
    {
        $this->requireAuth();
        
        $post = Post::find($id);
        
        if (!$post || $post->user_id != $this->user->id) {
            http_response_code(404);
            $this->render('errors/404');
            return;
        }
        
        $data = [
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'category' => $_POST['category'] ?? '',
            'status' => $_POST['status'] ?? 'draft'
        ];
        
        // Atualizar campos
        foreach ($data as $key => $value) {
            $post->$key = $value;
        }
        
        if ($data['status'] === 'published' && !$post->published_at) {
            $post->published_at = new \ActiveRecord\DateTime();
        }
        
        try {
            if ($post->save()) {
                $this->flash('success', 'Post atualizado com sucesso!');
                $this->redirect("/posts/{$post->slug}");
            } else {
                $this->render('posts/edit', [
                    'errors' => $post->errors->get_raw_errors(),
                    'post' => $post
                ]);
            }
        } catch (\Exception $e) {
            $this->flash('error', 'Erro ao atualizar post: ' . $e->getMessage());
            $this->render('posts/edit', ['post' => $post]);
        }
    }
    
    /**
     * Deletar post
     */
    public function delete($id)
    {
        $this->requireAuth();
        
        $post = Post::find($id);
        
        if (!$post || $post->user_id != $this->user->id) {
            $this->json(['error' => 'Post não encontrado'], 404);
            return;
        }
        
        try {
            $post->softDelete();
            $this->json(['success' => true, 'message' => 'Post deletado com sucesso']);
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao deletar post: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Adicionar comentário
     */
    public function addComment($postId)
    {
        $this->requireAuth();
        
        $post = Post::find($postId);
        if (!$post) {
            $this->json(['error' => 'Post não encontrado'], 404);
            return;
        }
        
        $content = $_POST['content'] ?? '';
        
        if (empty($content) || strlen($content) < 3) {
            $this->json(['error' => 'Comentário deve ter pelo menos 3 caracteres'], 400);
            return;
        }
        
        try {
            $comment = new Comment([
                'content' => $content,
                'post_id' => $post->id,
                'user_id' => $this->user->id,
                'status' => 'pending' // Comentários precisam de aprovação
            ]);
            
            if ($comment->save()) {
                $this->json([
                    'success' => true,
                    'message' => 'Comentário enviado para aprovação',
                    'comment' => $comment->toArray()
                ]);
            } else {
                $this->json(['error' => 'Erro ao salvar comentário'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Erro ao criar comentário: ' . $e->getMessage()], 500);
        }
    }
}
```

## Views

### 1. Layout Principal

```php
<?php
// app/views/layouts/main.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'MVC com ActiveRecord' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">MVC ActiveRecord</a>
            
            <div class="navbar-nav ms-auto">
                <?php if ($user): ?>
                    <span class="navbar-text me-3">
                        Olá, <?= htmlspecialchars($user->name) ?>!
                    </span>
                    <a class="nav-link" href="/posts/create">Novo Post</a>
                    <a class="nav-link" href="/profile">Perfil</a>
                    <a class="nav-link" href="/logout">Sair</a>
                <?php else: ?>
                    <a class="nav-link" href="/login">Login</a>
                    <a class="nav-link" href="/register">Registrar</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        <?php if (!empty($flash)): ?>
            <?php foreach ($flash as $message): ?>
                <div class="alert alert-<?= $message['type'] === 'error' ? 'danger' : $message['type'] ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);
    </script>
</body>
</html>
```

### 2. Lista de Posts

```php
<?php
// app/views/posts/index.php
?>
<div class="row">
    <div class="col-lg-8">
        <h1>Posts Recentes</h1>
        
        <?php if (empty($posts)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Nenhum post encontrado.
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <article class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title">
                            <a href="<?= $post->getUrl() ?>" class="text-decoration-none">
                                <?= htmlspecialchars($post->title) ?>
                            </a>
                        </h2>
                        
                        <div class="text-muted mb-3">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($post->user->name) ?>
                            <i class="fas fa-calendar ms-3"></i> <?= $post->formatDate('published_at') ?>
                            <i class="fas fa-tag ms-3"></i> <?= htmlspecialchars($post->category) ?>
                        </div>
                        
                        <p class="card-text"><?= htmlspecialchars($post->getExcerpt()) ?></p>
                        
                        <a href="<?= $post->getUrl() ?>" class="btn btn-primary">
                            Ler mais <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
            
            <!-- Paginação -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Navegação de posts">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-tags"></i> Categorias</h5>
            </div>
            <div class="card-body">
                <?php
                $categories = \App\Models\Post::find_by_sql(
                    "SELECT category, COUNT(*) as count FROM posts WHERE status = 'published' GROUP BY category ORDER BY count DESC"
                );
                ?>
                <?php foreach ($categories as $cat): ?>
                    <a href="/posts?category=<?= urlencode($cat->category) ?>" class="badge bg-secondary me-2 mb-2">
                        <?= htmlspecialchars($cat->category) ?> (<?= $cat->count ?>)
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
```

### 3. Detalhes do Post

```php
<?php
// app/views/posts/show.php
?>
<article class="mb-5">
    <header class="mb-4">
        <h1><?= htmlspecialchars($post->title) ?></h1>
        
        <div class="text-muted mb-3">
            <i class="fas fa-user"></i> <?= htmlspecialchars($post->user->name) ?>
            <i class="fas fa-calendar ms-3"></i> <?= $post->formatDate('published_at') ?>
            <i class="fas fa-tag ms-3"></i> <?= htmlspecialchars($post->category) ?>
        </div>
        
        <?php if ($user && $post->user_id == $user->id): ?>
            <div class="mb-3">
                <a href="/posts/<?= $post->id ?>/edit" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <button onclick="deletePost(<?= $post->id ?>)" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-trash"></i> Deletar
                </button>
            </div>
        <?php endif; ?>
    </header>
    
    <div class="content">
        <?= nl2br(htmlspecialchars($post->content)) ?>
    </div>
</article>

<!-- Comentários -->
<section class="comments">
    <h3><i class="fas fa-comments"></i> Comentários (<?= count($comments) ?>)</h3>
    
    <?php if ($user): ?>
        <form id="comment-form" class="mb-4">
            <div class="mb-3">
                <label for="comment-content" class="form-label">Seu comentário:</label>
                <textarea class="form-control" id="comment-content" name="content" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Enviar
            </button>
        </form>
    <?php else: ?>
        <div class="alert alert-info">
            <a href="/login">Faça login</a> para comentar.
        </div>
    <?php endif; ?>
    
    <div id="comments-list">
        <?php if (empty($comments)): ?>
            <p class="text-muted">Ainda não há comentários.</p>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <strong><?= htmlspecialchars($comment->user->name) ?></strong>
                            <small class="text-muted"><?= $comment->formatDate('created_at') ?></small>
                        </div>
                        <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($comment->content)) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<script>
// Envio de comentário via AJAX
document.getElementById('comment-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const content = document.getElementById('comment-content').value;
    const btn = this.querySelector('button[type="submit"]');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    
    try {
        const response = await fetch('/posts/<?= $post->id ?>/comments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'content=' + encodeURIComponent(content)
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('comment-content').value = '';
            alert('Comentário enviado para aprovação!');
        } else {
            alert('Erro: ' + result.error);
        }
    } catch (error) {
        alert('Erro ao enviar comentário');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
    }
});

// Deletar post
async function deletePost(id) {
    if (!confirm('Tem certeza que deseja deletar este post?')) return;
    
    try {
        const response = await fetch('/posts/' + id, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Post deletado com sucesso!');
            window.location.href = '/posts';
        } else {
            alert('Erro: ' + result.error);
        }
    } catch (error) {
        alert('Erro ao deletar post');
    }
}
</script>
```

## Migrations com ActiveRecord

### 1. Sistema de Migrations

```php
<?php
// migrations/migrate.php

require_once __DIR__ . '/../config/database.php';

class Migration
{
    private $connection;
    
    public function __construct()
    {
        $this->connection = ActiveRecord\ConnectionManager::get_connection();
    }
    
    public function createTable($tableName, $callback)
    {
        $table = new TableBuilder($tableName, $this->connection);
        $callback($table);
        $table->execute();
    }
    
    public function dropTable($tableName)
    {
        $this->connection->query("DROP TABLE IF EXISTS `{$tableName}`");
    }
    
    public function addColumn($tableName, $columnName, $type, $options = [])
    {
        $sql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$type}";
        
        if (isset($options['null']) && !$options['null']) {
            $sql .= ' NOT NULL';
        }
        
        if (isset($options['default'])) {
            $sql .= " DEFAULT '{$options['default']}'";
        }
        
        $this->connection->query($sql);
    }
    
    public function removeColumn($tableName, $columnName)
    {
        $this->connection->query("ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`");
    }
}

class TableBuilder
{
    private $tableName;
    private $connection;
    private $columns = [];
    private $indexes = [];
    
    public function __construct($tableName, $connection)
    {
        $this->tableName = $tableName;
        $this->connection = $connection;
    }
    
    public function id()
    {
        $this->columns[] = "`id` INT AUTO_INCREMENT PRIMARY KEY";
        return $this;
    }
    
    public function string($name, $length = 255)
    {
        $this->columns[] = "`{$name}` VARCHAR({$length})";
        return $this;
    }
    
    public function text($name)
    {
        $this->columns[] = "`{$name}` TEXT";
        return $this;
    }
    
    public function integer($name)
    {
        $this->columns[] = "`{$name}` INT";
        return $this;
    }
    
    public function boolean($name)
    {
        $this->columns[] = "`{$name}` BOOLEAN DEFAULT FALSE";
        return $this;
    }
    
    public function timestamp($name)
    {
        $this->columns[] = "`{$name}` TIMESTAMP NULL";
        return $this;
    }
    
    public function timestamps()
    {
        $this->columns[] = "`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        return $this;
    }
    
    public function softDeletes()
    {
        $this->columns[] = "`deleted_at` TIMESTAMP NULL";
        return $this;
    }
    
    public function index($columns)
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        $this->indexes[] = "INDEX (`" . implode('`, `', $columns) . "`)";
        return $this;
    }
    
    public function unique($columns)
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        $this->indexes[] = "UNIQUE KEY (`" . implode('`, `', $columns) . "`)";
        return $this;
    }
    
    public function foreign($column, $references)
    {
        $this->indexes[] = "FOREIGN KEY (`{$column}`) REFERENCES {$references}";
        return $this;
    }
    
    public function execute()
    {
        $sql = "CREATE TABLE `{$this->tableName}` (\n";
        $sql .= implode(",\n", $this->columns);
        
        if (!empty($this->indexes)) {
            $sql .= ",\n" . implode(",\n", $this->indexes);
        }
        
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->connection->query($sql);
        echo "Created table: {$this->tableName}\n";
    }
}

// Executar migrations
$migration = new Migration();

// Migration: Create users table
$migration->createTable('users', function($table) {
    $table->id();
    $table->string('name', 100);
    $table->string('email')->unique(['email']);
    $table->string('password_hash');
    $table->boolean('active');
    $table->string('avatar')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

// Migration: Create posts table
$migration->createTable('posts', function($table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique(['slug']);
    $table->text('content');
    $table->string('category', 50);
    $table->string('status', 20); // draft, published
    $table->integer('user_id');
    $table->timestamp('published_at');
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['status', 'published_at']);
    $table->index(['category']);
    $table->foreign('user_id', 'users(id) ON DELETE CASCADE');
});

// Migration: Create comments table
$migration->createTable('comments', function($table) {
    $table->id();
    $table->text('content');
    $table->integer('user_id');
    $table->integer('post_id');
    $table->string('status', 20); // pending, approved, rejected
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['post_id', 'status']);
    $table->foreign('user_id', 'users(id) ON DELETE CASCADE');
    $table->foreign('post_id', 'posts(id) ON DELETE CASCADE');
});

echo "All migrations completed successfully!\n";
```

## Exemplos Práticos

### 1. CRUD Completo

```php
<?php
// Exemplo de uso dos models

// Criar usuário
$user = new User([
    'name' => 'João Silva',
    'email' => 'joao@email.com',
    'password' => 'senha123',
    'active' => true
]);

if ($user->save()) {
    echo "Usuário criado: ID {$user->id}\n";
} else {
    print_r($user->errors->get_raw_errors());
}

// Buscar usuários
$users = User::active();
foreach ($users as $user) {
    echo "{$user->name} ({$user->email})\n";
}

// Buscar com condições
$user = User::find('first', [
    'conditions' => ['email = ?', 'joao@email.com']
]);

// Relacionamentos
$posts = $user->posts; // Posts do usuário
$comments = $user->comments; // Comentários do usuário

// Criar post
$post = new Post([
    'title' => 'Meu primeiro post',
    'content' => 'Conteúdo do post...',
    'category' => 'tecnologia',
    'status' => 'published',
    'user_id' => $user->id
]);

$post->save();

// Buscar posts com relacionamentos
$posts = Post::find('all', [
    'include' => ['user', 'comments'],
    'conditions' => ['status = ?', 'published'],
    'order' => 'published_at DESC'
]);

foreach ($posts as $post) {
    echo "{$post->title} por {$post->user->name}\n";
    echo "Comentários: " . count($post->comments) . "\n";
}
```

### 2. Queries Avançadas

```php
<?php
// Estatísticas avançadas

// Posts por mês
$postsByMonth = Post::find_by_sql("
    SELECT 
        DATE_FORMAT(published_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM posts 
    WHERE status = 'published'
    GROUP BY month 
    ORDER BY month DESC
");

// Usuários mais ativos
$activeUsers = User::find_by_sql("
    SELECT 
        u.*,
        COUNT(p.id) as posts_count,
        COUNT(c.id) as comments_count
    FROM users u
    LEFT JOIN posts p ON u.id = p.user_id
    LEFT JOIN comments c ON u.id = c.user_id
    WHERE u.active = 1
    GROUP BY u.id
    HAVING posts_count > 0 OR comments_count > 0
    ORDER BY (posts_count + comments_count) DESC
    LIMIT 10
");

// Posts populares (com mais comentários)
$popularPosts = Post::find_by_sql("
    SELECT 
        p.*,
        COUNT(c.id) as comments_count
    FROM posts p
    LEFT JOIN comments c ON p.id = c.post_id AND c.status = 'approved'
    WHERE p.status = 'published'
    GROUP BY p.id
    ORDER BY comments_count DESC, p.published_at DESC
    LIMIT 5
");
```

### 3. Scopes Personalizados

```php
<?php
// Adicionar ao model Post

class Post extends BaseModel
{
    // ... código existente ...
    
    /**
     * Scope para posts recentes
     */
    public static function recent($limit = 5)
    {
        return static::find('all', [
            'conditions' => ['status = ?', 'published'],
            'order' => 'published_at DESC',
            'limit' => $limit,
            'include' => ['user']
        ]);
    }
    
    /**
     * Scope para posts populares
     */
    public static function popular($limit = 5)
    {
        return static::find_by_sql("
            SELECT p.*, COUNT(c.id) as comments_count
            FROM posts p
            LEFT JOIN comments c ON p.id = c.post_id AND c.status = 'approved'
            WHERE p.status = 'published'
            GROUP BY p.id
            ORDER BY comments_count DESC, p.published_at DESC
            LIMIT {$limit}
        ");
    }
    
    /**
     * Scope para busca de texto
     */
    public static function search($query, $limit = 10)
    {
        return static::find('all', [
            'conditions' => [
                'status = ? AND (title LIKE ? OR content LIKE ?)',
                'published',
                "%{$query}%",
                "%{$query}%"
            ],
            'order' => 'published_at DESC',
            'limit' => $limit,
            'include' => ['user']
        ]);
    }
}

// Uso dos scopes
$recentPosts = Post::recent(10);
$popularPosts = Post::popular(5);
$searchResults = Post::search('php activerecord');
```

## Troubleshooting

### 1. Problemas Comuns

#### Erro de Conexão
```php
// Verificar configuração
try {
    $connection = ActiveRecord\ConnectionManager::get_connection();
    echo "Conexão OK: " . $connection->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
} catch (Exception $e) {
    echo "Erro de conexão: " . $e->getMessage();
}
```

#### Models não Encontrados
```php
// Verificar se o model está carregado
if (class_exists('App\\Models\\User')) {
    echo "Model User encontrado\n";
} else {
    echo "Model User NÃO encontrado - verificar autoload\n";
}

// Verificar diretório dos models
echo "Diretório dos models: " . ActiveRecord\Config::instance()->get_model_directory();
```

#### Problemas de Relacionamentos
```php
// Debug de relacionamentos
$user = User::find(1);

// Verificar se o relacionamento existe
try {
    $posts = $user->posts;
    echo "Posts encontrados: " . count($posts) . "\n";
} catch (Exception $e) {
    echo "Erro no relacionamento: " . $e->getMessage() . "\n";
}
```

### 2. Logging e Debug

```php
<?php
// Habilitar logs detalhados

// Em config/database.php
Config::initialize(function($cfg) {
    // ... outras configurações ...
    
    // Logs detalhados
    $cfg->set_logging(true);
    $cfg->set_logger(function($message) {
        $log = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
        file_put_contents(__DIR__ . '/../logs/activerecord.log', $log, FILE_APPEND);
        if ($_ENV['APP_DEBUG'] === 'true') {
            echo $log;
        }
    });
});
```

### 3. Ferramentas de Debug

```php
<?php
// debug_ar.php - Script de debug

require_once 'config/database.php';

// Função helper para debug
function debug_activerecord()
{
    echo "=== ActiveRecord Debug ===\n\n";
    
    // Informações da conexão
    echo "1. Conexão:\n";
    try {
        $conn = ActiveRecord\ConnectionManager::get_connection();
        echo "   ✓ Conectado ao MySQL\n";
        echo "   Versão: " . $conn->connection->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
        echo "   Base: " . $conn->connection->query('SELECT DATABASE()')->fetchColumn() . "\n\n";
    } catch (Exception $e) {
        echo "   ✗ Erro: " . $e->getMessage() . "\n\n";
        return;
    }
    
    // Verificar tabelas
    echo "2. Tabelas:\n";
    $tables = $conn->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "   ✓ {$table}\n";
    }
    echo "\n";
    
    // Verificar models
    echo "3. Models:\n";
    $models = ['User', 'Post', 'Comment'];
    foreach ($models as $model) {
        $class = "App\\Models\\{$model}";
        if (class_exists($class)) {
            echo "   ✓ {$model}\n";
            try {
                $count = $class::count();
                echo "     Registros: {$count}\n";
            } catch (Exception $e) {
                echo "     Erro: " . $e->getMessage() . "\n";
            }
        } else {
            echo "   ✗ {$model} não encontrado\n";
        }
    }
    echo "\n";
    
    // Testar relacionamentos
    echo "4. Relacionamentos:\n";
    try {
        $user = User::first();
        if ($user) {
            echo "   ✓ Usuário: {$user->name}\n";
            echo "     Posts: " . count($user->posts) . "\n";
            echo "     Comentários: " . count($user->comments) . "\n";
        } else {
            echo "   ! Nenhum usuário encontrado\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Erro: " . $e->getMessage() . "\n";
    }
}

// Executar debug
debug_activerecord();
```

## Boas Práticas

### 1. Estrutura de Models

```php
<?php
// Exemplo de model bem estruturado

class User extends BaseModel
{
    // 1. Configurações da tabela
    static $table_name = 'users';
    static $primary_key = 'id';
    
    // 2. Relacionamentos
    static $has_many = [
        ['posts', 'class_name' => 'Post'],
        ['comments', 'class_name' => 'Comment']
    ];
    
    // 3. Validações
    static $validates_presence_of = [
        ['name', 'message' => 'Nome é obrigatório'],
        ['email', 'message' => 'Email é obrigatório']
    ];
    
    // 4. Callbacks
    static $before_create = ['hash_password', 'set_timestamps_create'];
    
    // 5. Scopes estáticos
    public static function active() { /* ... */ }
    
    // 6. Métodos de instância
    public function getFullName() { /* ... */ }
    
    // 7. Métodos utilitários
    public function toArray($exclude = []) { /* ... */ }
}
```

### 2. Performance

```php
<?php
// Otimizações de performance

// Usar select específico
$users = User::find('all', [
    'select' => 'id, name, email',
    'conditions' => ['active = ?', true]
]);

// Eager loading para evitar N+1
$posts = Post::find('all', [
    'include' => ['user', 'comments'] // Carrega relacionamentos
]);

// Paginação eficiente
$posts = Post::find('all', [
    'limit' => 10,
    'offset' => ($page - 1) * 10,
    'order' => 'created_at DESC'
]);

// Cache de queries frequentes
class CachedQueries
{
    public static function popularPosts($ttl = 3600)
    {
        $cacheKey = 'popular_posts';
        $cached = apcu_fetch($cacheKey);
        
        if ($cached === false) {
            $posts = Post::popular(5);
            apcu_store($cacheKey, $posts, $ttl);
            return $posts;
        }
        
        return $cached;
    }
}
```

### 3. Segurança

```php
<?php
// Práticas de segurança

class SecureController extends BaseController
{
    /**
     * Sanitizar entrada
     */
    protected function sanitizeInput($data)
    {
        return array_map(function($value) {
            return is_string($value) ? trim(strip_tags($value)) : $value;
        }, $data);
    }
    
    /**
     * Verificar CSRF token
     */
    protected function verifyCsrfToken()
    {
        $token = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        if (!hash_equals($sessionToken, $token)) {
            throw new Exception('Token CSRF inválido');
        }
    }
    
    /**
     * Rate limiting
     */
    protected function checkRateLimit($action, $limit = 10, $window = 60)
    {
        $key = "rate_limit:{$action}:" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $current = apcu_fetch($key) ?: 0;
        
        if ($current >= $limit) {
            http_response_code(429);
            throw new Exception('Muitas tentativas. Tente novamente em ' . $window . ' segundos.');
        }
        
        apcu_store($key, $current + 1, $window);
    }
}
```

---

## Conclusão

Este guia apresenta uma implementação completa de um sistema MVC usando PHP ActiveRecord, demonstrando:

- **Setup e configuração** completa do ActiveRecord
- **Models ricos** com validações, relacionamentos e callbacks
- **Controllers organizados** com tratamento de erros
- **Views responsivas** com Bootstrap
- **Sistema de migrations** para versionamento
- **Exemplos práticos** de uso
- **Troubleshooting** e debugging
- **Boas práticas** de segurança e performance

O ActiveRecord simplifica drasticamente o desenvolvimento em PHP, oferecendo uma camada de abstração poderosa e intuitiva para trabalhar com bancos de dados relacionais.
