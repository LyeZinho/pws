# Guia de Exemplos Práticos - Sistema GEstufas

## Introdução

Este guia contém exemplos completos e práticos de como implementar funcionalidades no sistema GEstufas. Cada exemplo inclui o código do controller, model, view e rotas, com comentários detalhados.

## Índice de Exemplos

1. [CRUD Completo de Categorias](#1-crud-completo-de-categorias)
2. [Sistema de Likes em Posts](#2-sistema-de-likes-em-posts)
3. [Upload de Imagens](#3-upload-de-imagens)
4. [Sistema de Notificações](#4-sistema-de-notificações)
5. [Pesquisa Avançada com Filtros](#5-pesquisa-avançada-com-filtros)
6. [Dashboard com Estatísticas](#6-dashboard-com-estatísticas)
7. [Sistema de Tags](#7-sistema-de-tags)
8. [Exportação de Dados](#8-exportação-de-dados)

---

## 1. CRUD Completo de Categorias

### 1.1 Rota

```php
// routes.php
'categories' => [
    'index' => ['GET', 'CategoryController', 'index'],           // Listar categorias
    'show' => ['GET', 'CategoryController', 'show'],             // Mostrar categoria
    'create' => ['GET', 'CategoryController', 'create'],         // Formulário criar
    'store' => ['POST', 'CategoryController', 'store'],          // Salvar categoria
    'edit' => ['GET', 'CategoryController', 'edit'],             // Formulário editar
    'update' => ['POST', 'CategoryController', 'update'],        // Atualizar categoria
    'delete' => ['GET', 'CategoryController', 'delete'],         // Eliminar categoria
],
```

### 1.2 Model

```php
<?php
// models/Category.php

/**
 * Model Category - Representa categorias do sistema
 * 
 * Tabela: categories
 * Campos: id, name, description, slug, created_at, updated_at
 */
class Category extends ActiveRecord\Model
{
    // Relacionamentos
    static $has_many = [
        ['posts', 'foreign_key' => 'category_id']
    ];
    
    // Validações
    static $validates_presence_of = [
        ['name', 'message' => 'Nome é obrigatório'],
        ['slug', 'message' => 'Slug é obrigatório']
    ];
    
    static $validates_uniqueness_of = [
        ['slug', 'message' => 'Slug já existe']
    ];
    
    static $validates_length_of = [
        ['name', 'minimum' => 2, 'maximum' => 100]
    ];
    
    /**
     * Gerar slug automaticamente a partir do nome
     */
    public function before_save()
    {
        if (empty($this->slug) && !empty($this->name)) {
            $this->slug = $this->generateSlug($this->name);
        }
        
        if (!$this->created_at) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        $this->updated_at = date('Y-m-d H:i:s');
    }
    
    /**
     * Gerar slug único
     */
    private function generateSlug($text)
    {
        // Converter para lowercase e substituir espaços por hífens
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
        
        // Verificar se já existe
        $originalSlug = $slug;
        $counter = 1;
        
        while (self::find_by_slug($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Buscar categoria por slug
     */
    public static function findBySlug($slug)
    {
        return self::find_by_slug($slug);
    }
    
    /**
     * Contar posts na categoria
     */
    public function getPostsCount()
    {
        return count($this->posts);
    }
    
    /**
     * Buscar categorias com contagem de posts
     */
    public static function withPostsCount()
    {
        $categories = self::all();
        
        foreach ($categories as $category) {
            $category->posts_count = $category->getPostsCount();
        }
        
        return $categories;
    }
}
```

### 1.3 Controller

```php
<?php
// controllers/CategoryController.php

/**
 * CategoryController - Gestão de categorias
 * 
 * Responsável por todas as operações CRUD das categorias
 */
class CategoryController extends Controller
{
    /**
     * Construtor - verificar permissões
     */
    public function __construct()
    {
        parent::__construct();
        
        // Verificar se o usuário está logado
        if (!Auth::check()) {
            $this->redirect('?c=auth&a=login&error=' . urlencode('Login necessário'));
            exit;
        }
        
        // Verificar se é admin (para operações de escrita)
        $writeActions = ['create', 'store', 'edit', 'update', 'delete'];
        $currentAction = $_GET['a'] ?? 'index';
        
        if (in_array($currentAction, $writeActions) && !Auth::user()->isAdmin()) {
            $this->redirect('?c=categories&a=index&error=' . urlencode('Sem permissões'));
            exit;
        }
    }
    
    /**
     * Listar todas as categorias
     * URL: ?c=categories&a=index
     */
    public function index()
    {
        try {
            // Buscar categorias com contagem de posts
            $categories = Category::withPostsCount();
            
            // Informações para a view
            $data = [
                'categories' => $categories,
                'totalCategories' => count($categories),
                'title' => 'Gestão de Categorias'
            ];
            
            $this->view('categories/index', $data);
            
        } catch (Exception $e) {
            error_log("Erro ao listar categorias: " . $e->getMessage());
            $this->redirect('?c=home&a=index&error=' . urlencode('Erro ao carregar categorias'));
        }
    }
    
    /**
     * Mostrar categoria específica
     * URL: ?c=categories&a=show&id=1
     */
    public function show()
    {
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id || !is_numeric($id)) {
                $this->redirect('?c=categories&a=index&error=' . urlencode('ID inválido'));
                return;
            }
            
            // Buscar categoria com posts
            $category = Category::find($id, ['include' => ['posts']]);
            
            if (!$category) {
                $this->redirect('?c=categories&a=index&error=' . urlencode('Categoria não encontrada'));
                return;
            }
            
            $data = [
                'category' => $category,
                'posts' => $category->posts,
                'title' => $category->name
            ];
            
            $this->view('categories/show', $data);
            
        } catch (Exception $e) {
            error_log("Erro ao mostrar categoria: " . $e->getMessage());
            $this->redirect('?c=categories&a=index&error=' . urlencode('Erro ao carregar categoria'));
        }
    }
    
    /**
     * Mostrar formulário para criar categoria
     * URL: ?c=categories&a=create
     */
    public function create()
    {
        $data = [
            'title' => 'Nova Categoria',
            'category' => new Category(), // Categoria vazia para o formulário
            'errors' => []
        ];
        
        $this->view('categories/create', $data);
    }
    
    /**
     * Processar criação de categoria
     * URL: ?c=categories&a=store (POST)
     */
    public function store()
    {
        try {
            // Capturar dados do formulário
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            
            // Criar nova categoria
            $category = new Category();
            $category->name = $name;
            $category->description = $description;
            $category->slug = $slug;
            
            // Tentar salvar
            if ($category->save()) {
                $this->redirect('?c=categories&a=index&success=' . urlencode('Categoria criada com sucesso'));
            } else {
                // Erros de validação
                $errors = [];
                foreach ($category->errors->full_messages() as $error) {
                    $errors[] = $error;
                }
                
                $data = [
                    'title' => 'Nova Categoria',
                    'category' => $category,
                    'errors' => $errors,
                    'old' => $_POST
                ];
                
                $this->view('categories/create', $data);
            }
            
        } catch (Exception $e) {
            error_log("Erro ao criar categoria: " . $e->getMessage());
            
            $data = [
                'title' => 'Nova Categoria',
                'category' => new Category(),
                'errors' => ['Erro interno do servidor'],
                'old' => $_POST
            ];
            
            $this->view('categories/create', $data);
        }
    }
    
    /**
     * Mostrar formulário para editar categoria
     * URL: ?c=categories&a=edit&id=1
     */
    public function edit()
    {
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id || !is_numeric($id)) {
                $this->redirect('?c=categories&a=index&error=' . urlencode('ID inválido'));
                return;
            }
            
            $category = Category::find($id);
            
            if (!$category) {
                $this->redirect('?c=categories&a=index&error=' . urlencode('Categoria não encontrada'));
                return;
            }
            
            $data = [
                'title' => 'Editar Categoria',
                'category' => $category,
                'errors' => []
            ];
            
            $this->view('categories/edit', $data);
            
        } catch (Exception $e) {
            error_log("Erro ao editar categoria: " . $e->getMessage());
            $this->redirect('?c=categories&a=index&error=' . urlencode('Erro ao carregar categoria'));
        }
    }
    
    /**
     * Processar atualização de categoria
     * URL: ?c=categories&a=update&id=1 (POST)
     */
    public function update()
    {
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id || !is_numeric($id)) {
                $this->redirect('?c=categories&a=index&error=' . urlencode('ID inválido'));
                return;
            }
            
            $category = Category::find($id);
            
            if (!$category) {
                $this->redirect('?c=categories&a=index&error=' . urlencode('Categoria não encontrada'));
                return;
            }
            
            // Atualizar dados
            $category->name = trim($_POST['name'] ?? '');
            $category->description = trim($_POST['description'] ?? '');
            $category->slug = trim($_POST['slug'] ?? '');
            
            if ($category->save()) {
                $this->redirect('?c=categories&a=show&id=' . $category->id . '&success=' . urlencode('Categoria atualizada'));
            } else {
                // Erros de validação
                $errors = [];
                foreach ($category->errors->full_messages() as $error) {
                    $errors[] = $error;
                }
                
                $data = [
                    'title' => 'Editar Categoria',
                    'category' => $category,
                    'errors' => $errors,
                    'old' => $_POST
                ];
                
                $this->view('categories/edit', $data);
            }
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar categoria: " . $e->getMessage());
            $this->redirect('?c=categories&a=index&error=' . urlencode('Erro ao atualizar categoria'));
        }
    }
    
    /**
     * Eliminar categoria
     * URL: ?c=categories&a=delete&id=1
     */
    public function delete()
    {
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id || !is_numeric($id)) {
                $this->redirect('?c=categories&a=index&error=' . urlencode('ID inválido'));
                return;
            }
            
            $category = Category::find($id);
            
            if (!$category) {
                $this->redirect('?c=categories&a=index&error=' . urlencode('Categoria não encontrada'));
                return;
            }
            
            // Verificar se há posts associados
            if ($category->getPostsCount() > 0) {
                $this->redirect('?c=categories&a=index&error=' . urlencode('Não é possível eliminar categoria com posts'));
                return;
            }
            
            if ($category->delete()) {
                $this->redirect('?c=categories&a=index&success=' . urlencode('Categoria eliminada'));
            } else {
                $this->redirect('?c=categories&a=index&error=' . urlencode('Erro ao eliminar categoria'));
            }
            
        } catch (Exception $e) {
            error_log("Erro ao eliminar categoria: " . $e->getMessage());
            $this->redirect('?c=categories&a=index&error=' . urlencode('Erro ao eliminar categoria'));
        }
    }
}
```

### 1.4 Views

#### 1.4.1 Lista de Categorias

```php
<!-- views/categories/index.php -->
<?php include_once __DIR__ . '/../layout/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestão de Categorias</h1>
        <?php if (Auth::user() && Auth::user()->isAdmin()): ?>
            <a href="?c=categories&a=create" class="btn btn-primary">
                <i class="bi bi-plus"></i> Nova Categoria
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Alertas -->
    <?php include_once __DIR__ . '/../layout/alerts.php'; ?>
    
    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title"><?= $totalCategories ?></h5>
                    <p class="card-text">Total de Categorias</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Lista de categorias -->
    <?php if (empty($categories)): ?>
        <div class="alert alert-info">
            <h4>Nenhuma categoria encontrada</h4>
            <p>Comece criando sua primeira categoria.</p>
            <?php if (Auth::user() && Auth::user()->isAdmin()): ?>
                <a href="?c=categories&a=create" class="btn btn-primary">Criar Categoria</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($categories as $category): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($category->name) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($category->description ?: 'Sem descrição') ?></p>
                            <p class="card-text">
                                <small class="text-muted">
                                    <?= $category->posts_count ?> post(s)
                                </small>
                            </p>
                        </div>
                        <div class="card-footer">
                            <div class="btn-group w-100" role="group">
                                <a href="?c=categories&a=show&id=<?= $category->id ?>" 
                                   class="btn btn-outline-primary btn-sm">Ver</a>
                                   
                                <?php if (Auth::user() && Auth::user()->isAdmin()): ?>
                                    <a href="?c=categories&a=edit&id=<?= $category->id ?>" 
                                       class="btn btn-outline-warning btn-sm">Editar</a>
                                    <a href="?c=categories&a=delete&id=<?= $category->id ?>" 
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Eliminar categoria \'<?= htmlspecialchars($category->name) ?>\'?')">
                                       Eliminar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>
```

#### 1.4.2 Formulário de Criação

```php
<!-- views/categories/create.php -->
<?php include_once __DIR__ . '/../layout/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Nova Categoria</h3>
                </div>
                <div class="card-body">
                    <!-- Erros de validação -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h6>Corrija os seguintes erros:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Formulário -->
                    <form method="POST" action="?c=categories&a=store" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome da Categoria *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?= htmlspecialchars($old['name'] ?? $category->name ?? '') ?>"
                                   required>
                            <div class="form-text">Nome único da categoria</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="slug" 
                                   name="slug" 
                                   value="<?= htmlspecialchars($old['slug'] ?? $category->slug ?? '') ?>"
                                   placeholder="URL amigável (opcional)">
                            <div class="form-text">Se deixar vazio, será gerado automaticamente</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="3"><?= htmlspecialchars($old['description'] ?? $category->description ?? '') ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="?c=categories&a=index" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Criar Categoria</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para gerar slug automaticamente -->
<script>
document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const slugField = document.getElementById('slug');
    
    // Se o slug estiver vazio, gerar automaticamente
    if (!slugField.value) {
        const slug = name.toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .trim('-');
        slugField.value = slug;
    }
});
</script>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>
```

### 1.5 Schema da Tabela

```sql
-- Script SQL para criar a tabela categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_name (name)
);

-- Inserir algumas categorias de exemplo
INSERT INTO categories (name, slug, description) VALUES
('Tecnologia', 'tecnologia', 'Posts sobre tecnologia e inovação'),
('Ciência', 'ciencia', 'Artigos científicos e descobertas'),
('Educação', 'educacao', 'Conteúdo educativo e aprendizagem');
```

---

## 2. Sistema de Likes em Posts

### 2.1 Model do Like

```php
<?php
// models/Like.php

/**
 * Model Like - Sistema de likes em posts
 * 
 * Tabela: likes
 * Campos: id, user_id, post_id, created_at
 */
class Like extends ActiveRecord\Model
{
    // Relacionamentos
    static $belongs_to = [
        ['user'],
        ['post']
    ];
    
    // Validações
    static $validates_presence_of = [
        ['user_id', 'message' => 'Usuário é obrigatório'],
        ['post_id', 'message' => 'Post é obrigatório']
    ];
    
    static $validates_uniqueness_of = [
        ['user_id', 'scope' => 'post_id', 'message' => 'Usuário já curtiu este post']
    ];
    
    /**
     * Verificar se um usuário curtiu um post
     */
    public static function hasUserLiked($userId, $postId)
    {
        return self::find_by_user_id_and_post_id($userId, $postId) !== null;
    }
    
    /**
     * Alternar like (curtir/descurtir)
     */
    public static function toggle($userId, $postId)
    {
        $like = self::find_by_user_id_and_post_id($userId, $postId);
        
        if ($like) {
            // Remover like
            $like->delete();
            return false; // Descurtiu
        } else {
            // Adicionar like
            $like = new self();
            $like->user_id = $userId;
            $like->post_id = $postId;
            $like->created_at = date('Y-m-d H:i:s');
            $like->save();
            return true; // Curtiu
        }
    }
    
    /**
     * Contar likes de um post
     */
    public static function countForPost($postId)
    {
        return self::count(['conditions' => ['post_id = ?', $postId]]);
    }
}
```

### 2.2 Adicionar ao Model Post

```php
// Adicionar ao models/Post.php

// Relacionamento com likes
static $has_many = [
    // ... outros relacionamentos existentes
    ['likes', 'foreign_key' => 'post_id']
];

/**
 * Contar likes do post
 */
public function getLikesCount()
{
    return Like::countForPost($this->id);
}

/**
 * Verificar se um usuário curtiu o post
 */
public function isLikedBy($userId)
{
    return Like::hasUserLiked($userId, $this->id);
}

/**
 * Alternar like do usuário
 */
public function toggleLike($userId)
{
    return Like::toggle($userId, $this->id);
}
```

### 2.3 Controller para Likes

```php
<?php
// controllers/LikeController.php

/**
 * LikeController - Gestão de likes
 */
class LikeController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Verificar se o usuário está logado
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Login necessário']);
            exit;
        }
    }
    
    /**
     * Alternar like (AJAX)
     * URL: ?c=likes&a=toggle (POST)
     */
    public function toggle()
    {
        header('Content-Type: application/json');
        
        try {
            $postId = $_POST['post_id'] ?? null;
            
            if (!$postId || !is_numeric($postId)) {
                throw new Exception('Post ID inválido');
            }
            
            // Verificar se o post existe
            $post = Post::find($postId);
            if (!$post) {
                throw new Exception('Post não encontrado');
            }
            
            // Alternar like
            $liked = Like::toggle(Auth::user()->id, $postId);
            $likesCount = Like::countForPost($postId);
            
            echo json_encode([
                'success' => true,
                'liked' => $liked,
                'likes_count' => $likesCount
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Listar quem curtiu um post
     * URL: ?c=likes&a=users&post_id=1
     */
    public function users()
    {
        try {
            $postId = $_GET['post_id'] ?? null;
            
            if (!$postId || !is_numeric($postId)) {
                $this->redirect('?c=posts&a=index&error=' . urlencode('Post ID inválido'));
                return;
            }
            
            $post = Post::find($postId);
            if (!$post) {
                $this->redirect('?c=posts&a=index&error=' . urlencode('Post não encontrado'));
                return;
            }
            
            // Buscar likes com usuários
            $likes = Like::find('all', [
                'conditions' => ['post_id = ?', $postId],
                'include' => ['user'],
                'order' => 'created_at DESC'
            ]);
            
            $data = [
                'post' => $post,
                'likes' => $likes,
                'title' => 'Quem curtiu este post'
            ];
            
            $this->view('likes/users', $data);
            
        } catch (Exception $e) {
            error_log("Erro ao listar likes: " . $e->getMessage());
            $this->redirect('?c=posts&a=index&error=' . urlencode('Erro ao carregar likes'));
        }
    }
}
```

### 2.4 Adicionar Rota

```php
// Em routes.php
'likes' => [
    'toggle' => ['POST', 'LikeController', 'toggle'],
    'users' => ['GET', 'LikeController', 'users'],
],
```

### 2.5 Frontend - Botão de Like

```php
<!-- Component de like para incluir nas views de posts -->
<!-- views/components/like_button.php -->

<?php
$likesCount = $post->getLikesCount();
$isLiked = Auth::check() ? $post->isLikedBy(Auth::user()->id) : false;
?>

<div class="like-container" data-post-id="<?= $post->id ?>">
    <?php if (Auth::check()): ?>
        <button class="btn btn-sm like-btn <?= $isLiked ? 'btn-danger' : 'btn-outline-danger' ?>" 
                onclick="toggleLike(<?= $post->id ?>)">
            <i class="bi <?= $isLiked ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
            <span class="likes-count"><?= $likesCount ?></span>
        </button>
    <?php else: ?>
        <a href="?c=auth&a=login" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-heart"></i>
            <span><?= $likesCount ?></span>
        </a>
    <?php endif; ?>
    
    <?php if ($likesCount > 0): ?>
        <a href="?c=likes&a=users&post_id=<?= $post->id ?>" 
           class="btn btn-sm btn-link text-muted">
            Ver quem curtiu
        </a>
    <?php endif; ?>
</div>

<script>
function toggleLike(postId) {
    const container = document.querySelector(`[data-post-id="${postId}"]`);
    const button = container.querySelector('.like-btn');
    const icon = button.querySelector('i');
    const countSpan = button.querySelector('.likes-count');
    
    // Desabilitar botão durante requisição
    button.disabled = true;
    
    fetch('?c=likes&a=toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `post_id=${postId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Atualizar visual do botão
            if (data.liked) {
                button.classList.remove('btn-outline-danger');
                button.classList.add('btn-danger');
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill');
            } else {
                button.classList.remove('btn-danger');
                button.classList.add('btn-outline-danger');
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
            }
            
            // Atualizar contagem
            countSpan.textContent = data.likes_count;
        } else {
            alert(data.error);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar like');
    })
    .finally(() => {
        button.disabled = false;
    });
}
</script>
```

### 2.6 Usar o Component

```php
<!-- Em views/posts/show.php -->
<div class="post-actions mt-3">
    <?php include __DIR__ . '/../components/like_button.php'; ?>
</div>

<!-- Em views/posts/index.php (lista de posts) -->
<?php foreach ($posts as $post): ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5><?= htmlspecialchars($post->title) ?></h5>
            <p><?= htmlspecialchars(substr($post->content, 0, 200)) ?>...</p>
        </div>
        <div class="card-footer">
            <?php include __DIR__ . '/../components/like_button.php'; ?>
        </div>
    </div>
<?php endforeach; ?>
```

### 2.7 Schema da Tabela

```sql
-- Script SQL para tabela de likes
CREATE TABLE likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_user_post (user_id, post_id),
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id)
);
```

---

## 3. Upload de Imagens

### 3.1 Helper para Upload

```php
<?php
// helpers/FileUpload.php

/**
 * Helper para upload de arquivos
 */
class FileUpload
{
    private $uploadDir;
    private $allowedTypes;
    private $maxSize;
    
    public function __construct($uploadDir = 'public/uploads/', $maxSize = 2097152) // 2MB
    {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        $this->maxSize = $maxSize;
        $this->allowedTypes = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif'
        ];
        
        // Criar diretório se não existir
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Fazer upload de um arquivo
     */
    public function upload($file, $subfolder = '')
    {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload do arquivo');
        }
        
        // Validar tipo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            throw new Exception('Tipo de arquivo não permitido');
        }
        
        // Validar tamanho
        if ($file['size'] > $this->maxSize) {
            throw new Exception('Arquivo muito grande. Máximo: ' . $this->formatBytes($this->maxSize));
        }
        
        // Gerar nome único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        
        // Diretório de destino
        $targetDir = $this->uploadDir . ($subfolder ? rtrim($subfolder, '/') . '/' : '');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $targetPath = $targetDir . $filename;
        
        // Mover arquivo
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Erro ao salvar arquivo');
        }
        
        return [
            'filename' => $filename,
            'path' => $targetPath,
            'url' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $targetPath),
            'size' => $file['size'],
            'type' => $mimeType
        ];
    }
    
    /**
     * Redimensionar imagem
     */
    public function resize($imagePath, $maxWidth = 800, $maxHeight = 600)
    {
        if (!file_exists($imagePath)) {
            throw new Exception('Arquivo não encontrado');
        }
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            throw new Exception('Arquivo não é uma imagem válida');
        }
        
        list($width, $height, $type) = $imageInfo;
        
        // Verificar se precisa redimensionar
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return; // Não precisa redimensionar
        }
        
        // Calcular novas dimensões mantendo proporção
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = intval($width * $ratio);
        $newHeight = intval($height * $ratio);
        
        // Criar nova imagem
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Carregar imagem original
        switch ($type) {
            case IMAGETYPE_JPEG:
                $originalImage = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $originalImage = imagecreatefrompng($imagePath);
                // Preservar transparência
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                break;
            case IMAGETYPE_GIF:
                $originalImage = imagecreatefromgif($imagePath);
                break;
            default:
                throw new Exception('Tipo de imagem não suportado');
        }
        
        // Redimensionar
        imagecopyresampled($newImage, $originalImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Salvar
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $imagePath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $imagePath);
                break;
            case IMAGETYPE_GIF:
                imagegif($newImage, $imagePath);
                break;
        }
        
        // Limpar memória
        imagedestroy($newImage);
        imagedestroy($originalImage);
    }
    
    /**
     * Eliminar arquivo
     */
    public function delete($filePath)
    {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
    
    private function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}
```

### 3.2 Adicionar ao Model Post

```php
// Adicionar ao models/Post.php

/**
 * Upload de imagem do post
 */
public function uploadImage($imageFile)
{
    try {
        $upload = new FileUpload('public/uploads/posts/');
        $result = $upload->upload($imageFile);
        
        // Redimensionar para otimizar
        $upload->resize($result['path'], 800, 600);
        
        // Salvar caminho no modelo
        $this->image_url = $result['url'];
        
        return $result;
    } catch (Exception $e) {
        throw new Exception('Erro ao fazer upload da imagem: ' . $e->getMessage());
    }
}

/**
 * Eliminar imagem do post
 */
public function deleteImage()
{
    if ($this->image_url && file_exists($_SERVER['DOCUMENT_ROOT'] . $this->image_url)) {
        $upload = new FileUpload();
        return $upload->delete($_SERVER['DOCUMENT_ROOT'] . $this->image_url);
    }
    return false;
}
```

### 3.3 Atualizar Controller de Posts

```php
// Adicionar ao PostController::store()

public function store()
{
    try {
        // ... código existente para validação ...
        
        // Criar post
        $post = new Post();
        $post->title = $_POST['title'];
        $post->content = $_POST['content'];
        $post->user_id = Auth::user()->id;
        $post->category_id = $_POST['category_id'] ?? null;
        
        // Upload de imagem (se fornecida)
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $post->uploadImage($_FILES['image']);
        }
        
        if ($post->save()) {
            $this->redirect('?c=posts&a=show&id=' . $post->id . '&success=' . urlencode('Post criado com sucesso'));
        } else {
            // ... tratamento de erros ...
        }
        
    } catch (Exception $e) {
        // ... tratamento de erros ...
    }
}

// Adicionar ao PostController::update()

public function update()
{
    try {
        // ... buscar post existente ...
        
        // Atualizar dados
        $post->title = $_POST['title'];
        $post->content = $_POST['content'];
        $post->category_id = $_POST['category_id'] ?? null;
        
        // Upload de nova imagem (se fornecida)
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Eliminar imagem anterior
            $post->deleteImage();
            
            // Upload da nova
            $post->uploadImage($_FILES['image']);
        }
        
        if ($post->save()) {
            $this->redirect('?c=posts&a=show&id=' . $post->id . '&success=' . urlencode('Post atualizado'));
        } else {
            // ... tratamento de erros ...
        }
        
    } catch (Exception $e) {
        // ... tratamento de erros ...
    }
}
```

### 3.4 Formulário com Upload

```php
<!-- views/posts/create.php -->
<form method="POST" action="?c=posts&a=store" enctype="multipart/form-data" novalidate>
    <div class="mb-3">
        <label for="title" class="form-label">Título *</label>
        <input type="text" class="form-control" id="title" name="title" required>
    </div>
    
    <div class="mb-3">
        <label for="content" class="form-label">Conteúdo *</label>
        <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
    </div>
    
    <div class="mb-3">
        <label for="category_id" class="form-label">Categoria</label>
        <select class="form-select" id="category_id" name="category_id">
            <option value="">Selecione uma categoria</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category->id ?>"><?= htmlspecialchars($category->name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="mb-3">
        <label for="image" class="form-label">Imagem</label>
        <input type="file" 
               class="form-control" 
               id="image" 
               name="image" 
               accept="image/*"
               onchange="previewImage(this)">
        <div class="form-text">Formatos aceites: JPG, PNG, GIF. Tamanho máximo: 2MB</div>
        
        <!-- Preview da imagem -->
        <div id="imagePreview" class="mt-2" style="display: none;">
            <img id="preview" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px;">
        </div>
    </div>
    
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <a href="?c=posts&a=index" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">Criar Post</button>
    </div>
</form>

<script>
function previewImage(input) {
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.style.display = 'block';
        };
        
        reader.readAsDataURL(input.files[0]);
    } else {
        previewContainer.style.display = 'none';
    }
}
</script>
```

### 3.5 Exibir Imagem no Post

```php
<!-- views/posts/show.php -->
<article class="post">
    <header class="mb-4">
        <h1><?= htmlspecialchars($post->title) ?></h1>
        <div class="post-meta">
            <small class="text-muted">
                Por <?= htmlspecialchars($post->user->name) ?> 
                em <?= date('d/m/Y H:i', strtotime($post->created_at)) ?>
            </small>
        </div>
    </header>
    
    <?php if ($post->image_url): ?>
        <div class="post-image mb-4">
            <img src="<?= htmlspecialchars($post->image_url) ?>" 
                 alt="<?= htmlspecialchars($post->title) ?>"
                 class="img-fluid rounded">
        </div>
    <?php endif; ?>
    
    <div class="post-content">
        <?= nl2br(htmlspecialchars($post->content)) ?>
    </div>
</article>
```

### 3.6 Schema da Tabela

```sql
-- Adicionar coluna image_url à tabela posts
ALTER TABLE posts ADD COLUMN image_url VARCHAR(255) NULL AFTER content;
```

---

## 4. Sistema de Notificações

### 4.1 Model de Notificação

```php
<?php
// models/Notification.php

/**
 * Model Notification - Sistema de notificações
 * 
 * Tabela: notifications
 * Campos: id, user_id, type, title, message, data, read_at, created_at
 */
class Notification extends ActiveRecord\Model
{
    // Relacionamentos
    static $belongs_to = [
        ['user']
    ];
    
    // Tipos de notificação
    const TYPE_LIKE = 'like';
    const TYPE_COMMENT = 'comment';
    const TYPE_FOLLOW = 'follow';
    const TYPE_MENTION = 'mention';
    const TYPE_SYSTEM = 'system';
    
    // Validações
    static $validates_presence_of = [
        ['user_id', 'message' => 'Usuário é obrigatório'],
        ['type', 'message' => 'Tipo é obrigatório'],
        ['title', 'message' => 'Título é obrigatório']
    ];
    
    static $validates_inclusion_of = [
        ['type', 'in' => [self::TYPE_LIKE, self::TYPE_COMMENT, self::TYPE_FOLLOW, self::TYPE_MENTION, self::TYPE_SYSTEM]]
    ];
    
    public function before_save()
    {
        if (!$this->created_at) {
            $this->created_at = date('Y-m-d H:i:s');
        }
    }
    
    /**
     * Criar notificação de like
     */
    public static function createLike($userId, $postId, $likerName)
    {
        $notification = new self();
        $notification->user_id = $userId;
        $notification->type = self::TYPE_LIKE;
        $notification->title = 'Novo like no seu post';
        $notification->message = "{$likerName} curtiu o seu post";
        $notification->data = json_encode(['post_id' => $postId]);
        
        return $notification->save();
    }
    
    /**
     * Criar notificação de comentário
     */
    public static function createComment($userId, $postId, $commenterName)
    {
        $notification = new self();
        $notification->user_id = $userId;
        $notification->type = self::TYPE_COMMENT;
        $notification->title = 'Novo comentário no seu post';
        $notification->message = "{$commenterName} comentou no seu post";
        $notification->data = json_encode(['post_id' => $postId]);
        
        return $notification->save();
    }
    
    /**
     * Marcar como lida
     */
    public function markAsRead()
    {
        $this->read_at = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    /**
     * Verificar se foi lida
     */
    public function isRead()
    {
        return $this->read_at !== null;
    }
    
    /**
     * Buscar notificações não lidas de um usuário
     */
    public static function getUnreadForUser($userId)
    {
        return self::find('all', [
            'conditions' => ['user_id = ? AND read_at IS NULL', $userId],
            'order' => 'created_at DESC'
        ]);
    }
    
    /**
     * Contar notificações não lidas
     */
    public static function countUnreadForUser($userId)
    {
        return self::count([
            'conditions' => ['user_id = ? AND read_at IS NULL', $userId]
        ]);
    }
    
    /**
     * Marcar todas como lidas
     */
    public static function markAllAsReadForUser($userId)
    {
        $notifications = self::find('all', [
            'conditions' => ['user_id = ? AND read_at IS NULL', $userId]
        ]);
        
        foreach ($notifications as $notification) {
            $notification->markAsRead();
        }
        
        return true;
    }
    
    /**
     * Obter dados decodificados
     */
    public function getData()
    {
        return $this->data ? json_decode($this->data, true) : [];
    }
    
    /**
     * Gerar URL da notificação
     */
    public function getUrl()
    {
        $data = $this->getData();
        
        switch ($this->type) {
            case self::TYPE_LIKE:
            case self::TYPE_COMMENT:
                return $data['post_id'] ? "?c=posts&a=show&id={$data['post_id']}" : '#';
            default:
                return '#';
        }
    }
}
```

### 4.2 Controller de Notificações

```php
<?php
// controllers/NotificationController.php

/**
 * NotificationController - Gestão de notificações
 */
class NotificationController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        
        if (!Auth::check()) {
            $this->redirect('?c=auth&a=login');
            exit;
        }
    }
    
    /**
     * Listar notificações do usuário
     * URL: ?c=notifications&a=index
     */
    public function index()
    {
        $userId = Auth::user()->id;
        $page = $_GET['page'] ?? 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // Buscar notificações
        $notifications = Notification::find('all', [
            'conditions' => ['user_id = ?', $userId],
            'order' => 'created_at DESC',
            'limit' => $perPage,
            'offset' => $offset
        ]);
        
        $totalNotifications = Notification::count(['conditions' => ['user_id = ?', $userId]]);
        $totalPages = ceil($totalNotifications / $perPage);
        
        $data = [
            'notifications' => $notifications,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'title' => 'Minhas Notificações'
        ];
        
        $this->view('notifications/index', $data);
    }
    
    /**
     * Marcar notificação como lida (AJAX)
     * URL: ?c=notifications&a=mark_read (POST)
     */
    public function mark_read()
    {
        header('Content-Type: application/json');
        
        try {
            $notificationId = $_POST['notification_id'] ?? null;
            
            if (!$notificationId) {
                throw new Exception('ID da notificação é obrigatório');
            }
            
            $notification = Notification::find($notificationId);
            
            if (!$notification || $notification->user_id != Auth::user()->id) {
                throw new Exception('Notificação não encontrada');
            }
            
            $notification->markAsRead();
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Marcar todas como lidas (AJAX)
     * URL: ?c=notifications&a=mark_all_read (POST)
     */
    public function mark_all_read()
    {
        header('Content-Type: application/json');
        
        try {
            Notification::markAllAsReadForUser(Auth::user()->id);
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Obter notificações não lidas (AJAX)
     * URL: ?c=notifications&a=unread
     */
    public function unread()
    {
        header('Content-Type: application/json');
        
        try {
            $notifications = Notification::getUnreadForUser(Auth::user()->id);
            $count = count($notifications);
            
            $result = [];
            foreach ($notifications as $notification) {
                $result[] = [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'url' => $notification->getUrl(),
                    'created_at' => $notification->created_at,
                    'type' => $notification->type
                ];
            }
            
            echo json_encode([
                'success' => true,
                'notifications' => $result,
                'count' => $count
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
```

### 4.3 Integrar com Likes e Comentários

```php
// Atualizar LikeController::toggle()
public function toggle()
{
    // ... código existente ...
    
    // Se curtiu (não descurtiu), criar notificação
    if ($liked) {
        // Não notificar se o usuário curtiu o próprio post
        if ($post->user_id != Auth::user()->id) {
            Notification::createLike(
                $post->user_id,
                $post->id,
                Auth::user()->name
            );
        }
    }
    
    // ... resto do código ...
}

// Atualizar PostController::comment() (ou onde processar comentários)
public function comment()
{
    // ... código para salvar comentário ...
    
    if ($comment->save()) {
        // Criar notificação para o autor do post
        if ($post->user_id != Auth::user()->id) {
            Notification::createComment(
                $post->user_id,
                $post->id,
                Auth::user()->name
            );
        }
        
        // ... resto do código ...
    }
}
```

### 4.4 Widget de Notificações

```php
<!-- views/components/notifications_widget.php -->
<?php if (Auth::check()): ?>
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" 
                type="button" 
                id="notificationsDropdown" 
                data-bs-toggle="dropdown">
            <i class="bi bi-bell"></i>
            <span class="badge bg-danger" id="notificationsBadge" style="display: none;">0</span>
        </button>
        
        <div class="dropdown-menu dropdown-menu-end" style="width: 300px;">
            <div class="dropdown-header d-flex justify-content-between align-items-center">
                <span>Notificações</span>
                <button class="btn btn-sm btn-link" onclick="markAllNotificationsRead()">
                    Marcar todas como lidas
                </button>
            </div>
            
            <div id="notificationsList" class="dropdown-divider">
                <!-- Notificações carregadas via AJAX -->
            </div>
            
            <div class="dropdown-footer">
                <a href="?c=notifications&a=index" class="dropdown-item text-center">
                    Ver todas as notificações
                </a>
            </div>
        </div>
    </div>
    
    <script>
    // Carregar notificações ao abrir o dropdown
    document.getElementById('notificationsDropdown').addEventListener('click', loadNotifications);
    
    // Carregar notificações periodicamente
    setInterval(loadNotifications, 30000); // A cada 30 segundos
    
    // Carregar na inicialização
    document.addEventListener('DOMContentLoaded', loadNotifications);
    
    function loadNotifications() {
        fetch('?c=notifications&a=unread')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationsUI(data.notifications, data.count);
                }
            })
            .catch(error => console.error('Erro ao carregar notificações:', error));
    }
    
    function updateNotificationsUI(notifications, count) {
        const badge = document.getElementById('notificationsBadge');
        const list = document.getElementById('notificationsList');
        
        // Atualizar badge
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'inline';
        } else {
            badge.style.display = 'none';
        }
        
        // Atualizar lista
        if (notifications.length === 0) {
            list.innerHTML = '<div class="dropdown-item text-muted">Nenhuma notificação</div>';
        } else {
            let html = '';
            notifications.slice(0, 5).forEach(notification => { // Mostrar apenas 5
                html += `
                    <a href="${notification.url}" 
                       class="dropdown-item notification-item" 
                       data-id="${notification.id}"
                       onclick="markNotificationRead(${notification.id})">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <strong>${notification.title}</strong><br>
                                <small class="text-muted">${notification.message}</small><br>
                                <small class="text-muted">${formatDate(notification.created_at)}</small>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge bg-primary">Novo</span>
                            </div>
                        </div>
                    </a>
                `;
            });
            list.innerHTML = html;
        }
    }
    
    function markNotificationRead(notificationId) {
        fetch('?c=notifications&a=mark_read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `notification_id=${notificationId}`
        });
    }
    
    function markAllNotificationsRead() {
        fetch('?c=notifications&a=mark_all_read', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications(); // Recarregar
            }
        });
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = (now - date) / 1000; // segundos
        
        if (diff < 60) return 'Agora mesmo';
        if (diff < 3600) return Math.floor(diff / 60) + 'm';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        return Math.floor(diff / 86400) + 'd';
    }
    </script>
<?php endif; ?>
```

### 4.5 Página de Notificações

```php
<!-- views/notifications/index.php -->
<?php include_once __DIR__ . '/../layout/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Minhas Notificações</h1>
        <button class="btn btn-outline-secondary" onclick="markAllAsRead()">
            Marcar todas como lidas
        </button>
    </div>
    
    <?php if (empty($notifications)): ?>
        <div class="alert alert-info text-center">
            <h4>Nenhuma notificação</h4>
            <p>Você não possui notificações no momento.</p>
        </div>
    <?php else: ?>
        <div class="notifications-list">
            <?php foreach ($notifications as $notification): ?>
                <div class="card mb-2 notification-item <?= $notification->isRead() ? 'read' : 'unread' ?>"
                     data-id="<?= $notification->id ?>">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <h6 class="card-title">
                                    <?= htmlspecialchars($notification->title) ?>
                                    <?php if (!$notification->isRead()): ?>
                                        <span class="badge bg-primary ms-2">Novo</span>
                                    <?php endif; ?>
                                </h6>
                                <p class="card-text"><?= htmlspecialchars($notification->message) ?></p>
                                <small class="text-muted">
                                    <?= date('d/m/Y H:i', strtotime($notification->created_at)) ?>
                                </small>
                            </div>
                            <div class="flex-shrink-0">
                                <?php if ($notification->getUrl() !== '#'): ?>
                                    <a href="<?= $notification->getUrl() ?>" 
                                       class="btn btn-sm btn-outline-primary"
                                       onclick="markAsRead(<?= $notification->id ?>)">
                                        Ver
                                    </a>
                                <?php endif; ?>
                                <?php if (!$notification->isRead()): ?>
                                    <button class="btn btn-sm btn-outline-secondary ms-1" 
                                            onclick="markAsRead(<?= $notification->id ?>)">
                                        Marcar como lida
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Paginação -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?c=notifications&a=index&page=<?= $i ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.notification-item.unread {
    border-left: 4px solid #007bff;
    background-color: #f8f9fa;
}

.notification-item.read {
    opacity: 0.7;
}
</style>

<script>
function markAsRead(notificationId) {
    fetch('?c=notifications&a=mark_read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `notification_id=${notificationId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const item = document.querySelector(`[data-id="${notificationId}"]`);
            item.classList.remove('unread');
            item.classList.add('read');
            
            // Remover badge "Novo"
            const badge = item.querySelector('.badge');
            if (badge) badge.remove();
        }
    });
}

function markAllAsRead() {
    fetch('?c=notifications&a=mark_all_read', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>
```

### 4.6 Schema da Tabela

```sql
-- Tabela de notificações
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('like', 'comment', 'follow', 'mention', 'system') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_read_at (read_at),
    INDEX idx_created_at (created_at)
);
```

---

## 5. Dashboard com Estatísticas

### 5.1 Model de Estatísticas

```php
<?php
// models/Statistics.php

/**
 * Helper para estatísticas do sistema
 */
class Statistics
{
    /**
     * Estatísticas gerais do sistema
     */
    public static function getSystemStats()
    {
        return [
            'total_users' => User::count(),
            'total_posts' => Post::count(),
            'total_projects' => Project::count(),
            'total_comments' => Comment::count(),
            'active_users_today' => self::getActiveUsersToday(),
            'posts_this_month' => self::getPostsThisMonth(),
            'top_categories' => self::getTopCategories(),
            'recent_activity' => self::getRecentActivity()
        ];
    }
    
    /**
     * Usuários ativos hoje
     */
    public static function getActiveUsersToday()
    {
        $today = date('Y-m-d');
        return User::count([
            'conditions' => ['last_login >= ?', $today . ' 00:00:00']
        ]);
    }
    
    /**
     * Posts criados este mês
     */
    public static function getPostsThisMonth()
    {
        $startOfMonth = date('Y-m-01');
        return Post::count([
            'conditions' => ['created_at >= ?', $startOfMonth]
        ]);
    }
    
    /**
     * Categorias mais populares
     */
    public static function getTopCategories($limit = 5)
    {
        $sql = "
            SELECT c.id, c.name, COUNT(p.id) as posts_count
            FROM categories c
            LEFT JOIN posts p ON c.id = p.category_id
            GROUP BY c.id, c.name
            ORDER BY posts_count DESC
            LIMIT ?
        ";
        
        $connection = ActiveRecord\ConnectionManager::get_connection();
        $result = $connection->query($sql, [$limit]);
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Atividade recente
     */
    public static function getRecentActivity($limit = 10)
    {
        $activities = [];
        
        // Posts recentes
        $recentPosts = Post::find('all', [
            'include' => ['user'],
            'limit' => $limit,
            'order' => 'created_at DESC'
        ]);
        
        foreach ($recentPosts as $post) {
            $activities[] = [
                'type' => 'post',
                'user' => $post->user->name,
                'action' => 'criou um post',
                'title' => $post->title,
                'url' => "?c=posts&a=show&id={$post->id}",
                'created_at' => $post->created_at
            ];
        }
        
        // Comentários recentes
        $recentComments = Comment::find('all', [
            'include' => ['user', 'post'],
            'limit' => $limit,
            'order' => 'created_at DESC'
        ]);
        
        foreach ($recentComments as $comment) {
            $activities[] = [
                'type' => 'comment',
                'user' => $comment->user->name,
                'action' => 'comentou no post',
                'title' => $comment->post->title,
                'url' => "?c=posts&a=show&id={$comment->post->id}",
                'created_at' => $comment->created_at
            ];
        }
        
        // Ordenar por data e limitar
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($activities, 0, $limit);
    }
    
    /**
     * Estatísticas de posts por mês
     */
    public static function getPostsPerMonth($months = 12)
    {
        $sql = "
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM posts 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ";
        
        $connection = ActiveRecord\ConnectionManager::get_connection();
        $result = $connection->query($sql, [$months]);
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Posts mais populares (por likes)
     */
    public static function getMostLikedPosts($limit = 5)
    {
        $sql = "
            SELECT p.id, p.title, p.user_id, u.name as user_name,
                   COUNT(l.id) as likes_count
            FROM posts p
            LEFT JOIN likes l ON p.id = l.post_id
            LEFT JOIN users u ON p.user_id = u.id
            GROUP BY p.id, p.title, p.user_id, u.name
            ORDER BY likes_count DESC
            LIMIT ?
        ";
        
        $connection = ActiveRecord\ConnectionManager::get_connection();
        $result = $connection->query($sql, [$limit]);
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Usuários mais ativos (por posts)
     */
    public static function getMostActiveUsers($limit = 5)
    {
        $sql = "
            SELECT u.id, u.name, u.email,
                   COUNT(p.id) as posts_count
            FROM users u
            LEFT JOIN posts p ON u.id = p.user_id
            GROUP BY u.id, u.name, u.email
            ORDER BY posts_count DESC
            LIMIT ?
        ";
        
        $connection = ActiveRecord\ConnectionManager::get_connection();
        $result = $connection->query($sql, [$limit]);
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
```

### 5.2 Controller do Dashboard

```php
<?php
// controllers/DashboardController.php

/**
 * DashboardController - Dashboard administrativo
 */
class DashboardController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Verificar se é admin
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            $this->redirect('?c=home&a=index&error=' . urlencode('Acesso negado'));
            exit;
        }
    }
    
    /**
     * Dashboard principal
     * URL: ?c=dashboard&a=index
     */
    public function index()
    {
        try {
            // Estatísticas gerais
            $stats = Statistics::getSystemStats();
            
            // Dados para gráficos
            $postsPerMonth = Statistics::getPostsPerMonth(6);
            $topCategories = Statistics::getTopCategories(5);
            $mostLikedPosts = Statistics::getMostLikedPosts(5);
            $mostActiveUsers = Statistics::getMostActiveUsers(5);
            
            $data = [
                'stats' => $stats,
                'postsPerMonth' => $postsPerMonth,
                'topCategories' => $topCategories,
                'mostLikedPosts' => $mostLikedPosts,
                'mostActiveUsers' => $mostActiveUsers,
                'title' => 'Dashboard Administrativo'
            ];
            
            $this->view('dashboard/index', $data);
            
        } catch (Exception $e) {
            error_log("Erro no dashboard: " . $e->getMessage());
            $this->redirect('?c=home&a=index&error=' . urlencode('Erro ao carregar dashboard'));
        }
    }
    
    /**
     * API para dados do gráfico (AJAX)
     * URL: ?c=dashboard&a=chart_data
     */
    public function chart_data()
    {
        header('Content-Type: application/json');
        
        try {
            $type = $_GET['type'] ?? 'posts_per_month';
            
            switch ($type) {
                case 'posts_per_month':
                    $data = Statistics::getPostsPerMonth(12);
                    break;
                case 'categories':
                    $data = Statistics::getTopCategories(10);
                    break;
                default:
                    throw new Exception('Tipo de gráfico inválido');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

### 5.3 View do Dashboard

```php
<!-- views/dashboard/index.php -->
<?php include_once __DIR__ . '/../layout/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Dashboard Administrativo</h1>
        <small class="text-muted">Última atualização: <?= date('d/m/Y H:i') ?></small>
    </div>
    
    <!-- Cards de estatísticas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3><?= number_format($stats['total_users']) ?></h3>
                            <p class="mb-0">Total de Usuários</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-people fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3><?= number_format($stats['total_posts']) ?></h3>
                            <p class="mb-0">Total de Posts</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-file-text fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3><?= number_format($stats['total_projects']) ?></h3>
                            <p class="mb-0">Total de Projetos</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-folder fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h3><?= number_format($stats['active_users_today']) ?></h3>
                            <p class="mb-0">Ativos Hoje</p>
                        </div>
                        <div class="align-self-center">
                            <i class="bi bi-activity fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Gráfico de Posts por Mês -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Posts por Mês</h5>
                </div>
                <div class="card-body">
                    <canvas id="postsChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Categorias Mais Populares -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Categorias Populares</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoriesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Posts Mais Curtidos -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Posts Mais Curtidos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Post</th>
                                    <th>Autor</th>
                                    <th>Likes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mostLikedPosts as $post): ?>
                                    <tr>
                                        <td>
                                            <a href="?c=posts&a=show&id=<?= $post['id'] ?>">
                                                <?= htmlspecialchars(substr($post['title'], 0, 30)) ?>...
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($post['user_name']) ?></td>
                                        <td>
                                            <span class="badge bg-danger"><?= $post['likes_count'] ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Usuários Mais Ativos -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Usuários Mais Ativos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Email</th>
                                    <th>Posts</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mostActiveUsers as $user): ?>
                                    <tr>
                                        <td>
                                            <a href="?c=users&a=show&id=<?= $user['id'] ?>">
                                                <?= htmlspecialchars($user['name']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?= $user['posts_count'] ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Atividade Recente -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Atividade Recente</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($stats['recent_activity'] as $activity): ?>
                            <div class="timeline-item mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="timeline-icon bg-<?= $activity['type'] === 'post' ? 'primary' : 'success' ?> text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-<?= $activity['type'] === 'post' ? 'file-text' : 'chat' ?>"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="mb-1">
                                            <strong><?= htmlspecialchars($activity['user']) ?></strong>
                                            <?= $activity['action'] ?>
                                            <a href="<?= $activity['url'] ?>"><?= htmlspecialchars($activity['title']) ?></a>
                                        </p>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Gráfico de Posts por Mês
const postsData = <?= json_encode($postsPerMonth) ?>;
const postsLabels = postsData.map(item => {
    const [year, month] = item.month.split('-');
    return new Date(year, month - 1).toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' });
});
const postsValues = postsData.map(item => item.count);

new Chart(document.getElementById('postsChart'), {
    type: 'line',
    data: {
        labels: postsLabels,
        datasets: [{
            label: 'Posts',
            data: postsValues,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Gráfico de Categorias
const categoriesData = <?= json_encode($topCategories) ?>;
const categoriesLabels = categoriesData.map(item => item.name);
const categoriesValues = categoriesData.map(item => item.posts_count);

new Chart(document.getElementById('categoriesChart'), {
    type: 'doughnut',
    data: {
        labels: categoriesLabels,
        datasets: [{
            data: categoriesValues,
            backgroundColor: [
                '#007bff',
                '#28a745',
                '#ffc107',
                '#dc3545',
                '#6f42c1'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<style>
.timeline-icon {
    font-size: 14px;
}

.timeline-item:last-child {
    border-bottom: none;
}
</style>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>
```

### 5.4 Adicionar Rota

```php
// Em routes.php
'dashboard' => [
    'index' => ['GET', 'DashboardController', 'index'],
    'chart_data' => ['GET', 'DashboardController', 'chart_data'],
],
```

---

## Conclusão

Este guia completo de exemplos práticos mostra como implementar funcionalidades avançadas no sistema GEstufas:

1. **CRUD Completo** - Sistema básico de gestão com validações e interface responsiva
2. **Sistema de Likes** - Interação entre usuários com AJAX e notificações
3. **Upload de Imagens** - Gestão de arquivos com validação e otimização
4. **Sistema de Notificações** - Comunicação em tempo real entre usuários
5. **Dashboard** - Visualização de estatísticas com gráficos

### Principais Conceitos Demonstrados

- **Padrão MVC** - Separação clara de responsabilidades
- **ActiveRecord** - ORM para gestão da base de dados
- **AJAX** - Comunicação assíncrona frontend/backend
- **Validação** - Tanto no frontend quanto no backend
- **Segurança** - Autenticação, autorização e sanitização de dados
- **UX/UI** - Interface responsiva com Bootstrap
- **Performance** - Otimização de consultas e carregamento

### Melhores Práticas Aplicadas

1. **Validação dupla** (frontend + backend)
2. **Tratamento de erros** completo
3. **Logs de debug** para troubleshooting
4. **Código comentado** para manutenibilidade
5. **Interface responsiva** para todos os dispositivos
6. **Segurança** em todas as operações

Use estes exemplos como base para criar suas próprias funcionalidades, sempre seguindo os padrões e convenções estabelecidos no sistema GEstufas.
