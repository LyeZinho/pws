# Guia de Frontend - Sistema GEstufas

## Introdução

Este guia explica como trabalhar com o frontend no sistema GEstufas, incluindo comunicação com o backend, uso do Bootstrap, JavaScript/AJAX, e melhores práticas para criar interfaces responsivas e interativas.

## Estrutura do Frontend

### Arquivos e Pastas

```
public/
├── css/
│   ├── bootstrap.min.css       # Framework CSS
│   └── custom.css              # Estilos personalizados
├── js/
│   ├── bootstrap.bundle.min.js # Framework JavaScript
│   └── app.js                  # JavaScript personalizado
└── img/
    └── logo-ipleiria.png       # Imagens e assets

views/
├── layout/
│   ├── header.php              # Cabeçalho comum
│   ├── footer.php              # Rodapé comum
│   └── navbar.php              # Barra de navegação
├── users/                      # Views de usuários
├── posts/                      # Views de posts
└── projects/                   # Views de projetos
```

### Layout Base

O sistema utiliza um layout comum com header, navbar e footer compartilhados:

```php
<!-- views/layout/header.php -->
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'GEstufas' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS Personalizado -->
    <link href="public/css/custom.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <main class="container mt-4">
```

## Comunicação Frontend ↔ Backend

### 1. Requisições GET - Navegação Simples

#### Links de Navegação

```php
<!-- Listar usuários -->
<a href="?c=users&a=index" class="btn btn-primary">Ver Usuários</a>

<!-- Ver usuário específico -->
<a href="?c=users&a=show&id=<?= $user->id ?>" class="btn btn-info">
    Ver Perfil
</a>

<!-- Editar usuário -->
<a href="?c=users&a=edit&id=<?= $user->id ?>" class="btn btn-warning">
    Editar
</a>

<!-- Eliminar com confirmação -->
<a href="?c=users&a=delete&id=<?= $user->id ?>" 
   class="btn btn-danger"
   onclick="return confirm('Tem certeza que deseja eliminar este usuário?')">
    Eliminar
</a>
```

#### Passagem de Parâmetros via URL

```php
<!-- Paginação -->
<a href="?c=posts&a=index&page=<?= $currentPage + 1 ?>">Próxima</a>

<!-- Filtros -->
<a href="?c=posts&a=index&category=<?= $category ?>&status=active">
    Filtrar por Categoria
</a>

<!-- Ordenação -->
<a href="?c=users&a=index&sort=name&order=asc">Ordenar por Nome</a>
```

### 2. Requisições POST - Formulários

#### Formulário de Criação

```php
<!-- views/users/create.php -->
<form method="POST" action="?c=users&a=store" class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Nome</label>
        <input type="text" 
               class="form-control" 
               id="name" 
               name="name" 
               value="<?= htmlspecialchars($old['name'] ?? '') ?>"
               required>
    </div>
    
    <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input type="email" 
               class="form-control" 
               id="email" 
               name="email" 
               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
               required>
    </div>
    
    <div class="col-12">
        <label for="bio" class="form-label">Biografia</label>
        <textarea class="form-control" 
                  id="bio" 
                  name="bio" 
                  rows="3"><?= htmlspecialchars($old['bio'] ?? '') ?></textarea>
    </div>
    
    <div class="col-12">
        <button type="submit" class="btn btn-primary">Criar Usuário</button>
        <a href="?c=users&a=index" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
```

#### Formulário de Edição

```php
<!-- views/users/edit.php -->
<form method="POST" action="?c=users&a=update&id=<?= $user->id ?>" class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Nome</label>
        <input type="text" 
               class="form-control" 
               id="name" 
               name="name" 
               value="<?= htmlspecialchars($user->name) ?>"
               required>
    </div>
    
    <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input type="email" 
               class="form-control" 
               id="email" 
               name="email" 
               value="<?= htmlspecialchars($user->email) ?>"
               required>
    </div>
    
    <div class="col-12">
        <button type="submit" class="btn btn-warning">Atualizar</button>
        <a href="?c=users&a=show&id=<?= $user->id ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
```

### 3. Backend - Processamento de Dados

#### Controller - Receber e Processar POST

```php
// UserController.php
public function store()
{
    try {
        // Capturar dados do formulário
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $bio = $_POST['bio'] ?? '';
        
        // Validação
        if (empty($name) || empty($email)) {
            throw new Exception('Nome e email são obrigatórios');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        // Criar usuário
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->bio = $bio;
        $user->created_at = date('Y-m-d H:i:s');
        
        if ($user->save()) {
            // Sucesso - redirecionar com mensagem
            $this->redirect('?c=users&a=index&success=' . urlencode('Usuário criado com sucesso'));
        } else {
            throw new Exception('Erro ao salvar usuário');
        }
        
    } catch (Exception $e) {
        // Erro - voltar ao formulário com dados preenchidos
        $old = $_POST; // Preservar dados do formulário
        $error = $e->getMessage();
        
        $this->view('users/create', [
            'old' => $old,
            'error' => $error
        ]);
    }
}
```

#### Controller - Enviar Dados para Frontend

```php
public function index()
{
    // Buscar dados da base de dados
    $users = User::all(['limit' => 10, 'order' => 'name ASC']);
    $totalUsers = User::count();
    
    // Dados de paginação
    $page = $_GET['page'] ?? 1;
    $perPage = 10;
    $totalPages = ceil($totalUsers / $perPage);
    
    // Filtros aplicados
    $filters = [
        'search' => $_GET['search'] ?? '',
        'status' => $_GET['status'] ?? 'all'
    ];
    
    // Enviar dados para a view
    $this->view('users/index', [
        'users' => $users,
        'totalUsers' => $totalUsers,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'filters' => $filters,
        'title' => 'Gestão de Usuários'
    ]);
}
```

## Uso do Bootstrap

### Components Básicos

#### Alertas para Mensagens

```php
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i>
        <?= htmlspecialchars($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i>
        <?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
```

#### Cards para Listagens

```php
<div class="row">
    <?php foreach ($posts as $post): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($post->title) ?></h5>
                    <p class="card-text"><?= substr(htmlspecialchars($post->content), 0, 100) ?>...</p>
                    <small class="text-muted">
                        Por <?= htmlspecialchars($post->user->name) ?> 
                        em <?= date('d/m/Y', strtotime($post->created_at)) ?>
                    </small>
                </div>
                <div class="card-footer">
                    <a href="?c=posts&a=show&id=<?= $post->id ?>" class="btn btn-primary btn-sm">
                        Ler Mais
                    </a>
                    <?php if (Auth::user() && Auth::user()->id == $post->user_id): ?>
                        <a href="?c=posts&a=edit&id=<?= $post->id ?>" class="btn btn-warning btn-sm">
                            Editar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
```

#### Tabelas Responsivas

```php
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Criado em</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user->id ?></td>
                    <td><?= htmlspecialchars($user->name) ?></td>
                    <td><?= htmlspecialchars($user->email) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($user->created_at)) ?></td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="?c=users&a=show&id=<?= $user->id ?>" 
                               class="btn btn-info btn-sm">Ver</a>
                            <a href="?c=users&a=edit&id=<?= $user->id ?>" 
                               class="btn btn-warning btn-sm">Editar</a>
                            <a href="?c=users&a=delete&id=<?= $user->id ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Eliminar usuário?')">Eliminar</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

#### Paginação

```php
<?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination justify-content-center">
            <!-- Primeira página -->
            <?php if ($currentPage > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?c=users&a=index&page=1">Primeira</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?c=users&a=index&page=<?= $currentPage - 1 ?>">Anterior</a>
                </li>
            <?php endif; ?>
            
            <!-- Páginas numeradas -->
            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="?c=users&a=index&page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            
            <!-- Última página -->
            <?php if ($currentPage < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?c=users&a=index&page=<?= $currentPage + 1 ?>">Próxima</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?c=users&a=index&page=<?= $totalPages ?>">Última</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>
```

## JavaScript e AJAX

### 1. Configuração Básica

```javascript
// public/js/app.js

// Configuração global
const App = {
    baseUrl: window.location.origin + window.location.pathname,
    
    // Helper para fazer requisições AJAX
    ajax: function(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        return fetch(url, { ...defaults, ...options })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            });
    },
    
    // Mostrar alertas
    showAlert: function(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.container');
        container.insertBefore(alertDiv, container.firstChild);
    }
};
```

### 2. Requisições AJAX para o Backend

#### Criar Endpoint de API no Controller

```php
// UserController.php
public function api_search()
{
    // Definir que é uma resposta JSON
    header('Content-Type: application/json');
    
    try {
        $search = $_GET['search'] ?? '';
        $limit = $_GET['limit'] ?? 10;
        
        // Buscar usuários
        $users = User::find('all', [
            'conditions' => ['name LIKE ? OR email LIKE ?', "%$search%", "%$search%"],
            'limit' => $limit,
            'order' => 'name ASC'
        ]);
        
        // Preparar dados para JSON
        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_url' => "?c=users&a=show&id={$user->id}"
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $result,
            'count' => count($result)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
```

#### Frontend - Busca com AJAX

```html
<!-- Campo de pesquisa -->
<div class="mb-3">
    <input type="text" 
           id="searchUsers" 
           class="form-control" 
           placeholder="Pesquisar usuários...">
</div>

<!-- Resultados -->
<div id="searchResults"></div>

<script>
// Busca em tempo real
document.getElementById('searchUsers').addEventListener('input', function() {
    const query = this.value.trim();
    
    if (query.length < 2) {
        document.getElementById('searchResults').innerHTML = '';
        return;
    }
    
    // Fazer requisição AJAX
    App.ajax(`?c=users&a=api_search&search=${encodeURIComponent(query)}`)
        .then(response => {
            if (response.success) {
                displaySearchResults(response.data);
            } else {
                App.showAlert(response.error, 'danger');
            }
        })
        .catch(error => {
            console.error('Erro na pesquisa:', error);
            App.showAlert('Erro ao pesquisar usuários', 'danger');
        });
});

function displaySearchResults(users) {
    const resultsDiv = document.getElementById('searchResults');
    
    if (users.length === 0) {
        resultsDiv.innerHTML = '<p class="text-muted">Nenhum usuário encontrado</p>';
        return;
    }
    
    let html = '<div class="list-group">';
    users.forEach(user => {
        html += `
            <a href="${user.profile_url}" class="list-group-item list-group-item-action">
                <strong>${user.name}</strong><br>
                <small class="text-muted">${user.email}</small>
            </a>
        `;
    });
    html += '</div>';
    
    resultsDiv.innerHTML = html;
}
</script>
```

### 3. Formulários com AJAX

#### Envio de Comentário sem Reload

```php
// PostController.php - Endpoint para comentários
public function api_comment()
{
    header('Content-Type: application/json');
    
    try {
        // Verificar autenticação
        if (!Auth::check()) {
            throw new Exception('Login necessário');
        }
        
        $post_id = $_POST['post_id'] ?? null;
        $content = $_POST['content'] ?? '';
        
        if (!$post_id || empty($content)) {
            throw new Exception('Dados inválidos');
        }
        
        // Criar comentário
        $comment = new Comment();
        $comment->post_id = $post_id;
        $comment->user_id = Auth::user()->id;
        $comment->content = $content;
        $comment->created_at = date('Y-m-d H:i:s');
        
        if ($comment->save()) {
            // Buscar dados completos do comentário
            $comment = Comment::find($comment->id, ['include' => ['user']]);
            
            echo json_encode([
                'success' => true,
                'comment' => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'user_name' => $comment->user->name,
                    'created_at' => date('d/m/Y H:i', strtotime($comment->created_at))
                ]
            ]);
        } else {
            throw new Exception('Erro ao salvar comentário');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
```

```html
<!-- Formulário de comentário -->
<form id="commentForm" class="mt-3">
    <input type="hidden" name="post_id" value="<?= $post->id ?>">
    <div class="mb-3">
        <textarea name="content" 
                  class="form-control" 
                  rows="3" 
                  placeholder="Escreva seu comentário..." 
                  required></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Comentar</button>
</form>

<!-- Lista de comentários -->
<div id="commentsList" class="mt-4">
    <?php foreach ($comments as $comment): ?>
        <div class="card mb-2">
            <div class="card-body">
                <p><?= htmlspecialchars($comment->content) ?></p>
                <small class="text-muted">
                    Por <?= htmlspecialchars($comment->user->name) ?> 
                    em <?= date('d/m/Y H:i', strtotime($comment->created_at)) ?>
                </small>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.getElementById('commentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('?c=posts&a=api_comment', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Adicionar comentário à lista
            const commentHtml = `
                <div class="card mb-2">
                    <div class="card-body">
                        <p>${data.comment.content}</p>
                        <small class="text-muted">
                            Por ${data.comment.user_name} em ${data.comment.created_at}
                        </small>
                    </div>
                </div>
            `;
            
            document.getElementById('commentsList').insertAdjacentHTML('beforeend', commentHtml);
            
            // Limpar formulário
            this.reset();
            
            App.showAlert('Comentário adicionado com sucesso!', 'success');
        } else {
            App.showAlert(data.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        App.showAlert('Erro ao enviar comentário', 'danger');
    });
});
</script>
```

### 4. Carregamento Dinâmico de Conteúdo

#### Carregamento de Posts com Scroll Infinito

```php
// PostController.php
public function api_load_more()
{
    header('Content-Type: application/json');
    
    $page = $_GET['page'] ?? 1;
    $perPage = 5;
    $offset = ($page - 1) * $perPage;
    
    $posts = Post::find('all', [
        'include' => ['user'],
        'limit' => $perPage,
        'offset' => $offset,
        'order' => 'created_at DESC'
    ]);
    
    $html = '';
    foreach ($posts as $post) {
        $html .= render_post_card($post); // função helper
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'has_more' => count($posts) == $perPage
    ]);
}

function render_post_card($post) {
    return "
        <div class='card mb-3'>
            <div class='card-body'>
                <h5>{$post->title}</h5>
                <p>" . substr($post->content, 0, 200) . "...</p>
                <small>Por {$post->user->name}</small>
            </div>
        </div>
    ";
}
```

```html
<div id="postsContainer">
    <!-- Posts carregados aqui -->
</div>

<div id="loadMoreBtn" class="text-center">
    <button class="btn btn-outline-primary" onclick="loadMorePosts()">
        Carregar Mais Posts
    </button>
</div>

<script>
let currentPage = 1;
let loading = false;

function loadMorePosts() {
    if (loading) return;
    
    loading = true;
    const btn = document.querySelector('#loadMoreBtn button');
    btn.textContent = 'Carregando...';
    btn.disabled = true;
    
    App.ajax(`?c=posts&a=api_load_more&page=${currentPage + 1}`)
        .then(response => {
            if (response.success) {
                document.getElementById('postsContainer').insertAdjacentHTML('beforeend', response.html);
                currentPage++;
                
                if (!response.has_more) {
                    document.getElementById('loadMoreBtn').style.display = 'none';
                }
            }
        })
        .finally(() => {
            loading = false;
            btn.textContent = 'Carregar Mais Posts';
            btn.disabled = false;
        });
}

// Carregamento automático ao rolar para baixo
window.addEventListener('scroll', function() {
    if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 1000) {
        loadMorePosts();
    }
});
</script>
```

## Validação no Frontend

### 1. Validação de Formulários em Tempo Real

```html
<form id="userForm" novalidate>
    <div class="mb-3">
        <label for="name" class="form-label">Nome</label>
        <input type="text" class="form-control" id="name" name="name" required>
        <div class="invalid-feedback"></div>
    </div>
    
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" required>
        <div class="invalid-feedback"></div>
    </div>
    
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
        <div class="invalid-feedback"></div>
        <div class="form-text">Mínimo 8 caracteres</div>
    </div>
    
    <button type="submit" class="btn btn-primary">Criar Usuário</button>
</form>

<script>
// Validação em tempo real
document.getElementById('userForm').addEventListener('input', function(e) {
    validateField(e.target);
});

document.getElementById('userForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (validateForm()) {
        // Enviar formulário
        submitForm();
    }
});

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let message = '';
    
    // Validação por tipo de campo
    switch (field.name) {
        case 'name':
            if (value.length < 2) {
                isValid = false;
                message = 'Nome deve ter pelo menos 2 caracteres';
            }
            break;
            
        case 'email':
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                message = 'Email inválido';
            }
            break;
            
        case 'password':
            if (value.length < 8) {
                isValid = false;
                message = 'Password deve ter pelo menos 8 caracteres';
            }
            break;
    }
    
    // Aplicar classes de validação
    if (isValid) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
        field.nextElementSibling.textContent = message;
    }
    
    return isValid;
}

function validateForm() {
    const fields = document.querySelectorAll('#userForm input[required]');
    let isValid = true;
    
    fields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function submitForm() {
    const formData = new FormData(document.getElementById('userForm'));
    
    fetch('?c=users&a=store', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            App.showAlert('Usuário criado com sucesso!', 'success');
            document.getElementById('userForm').reset();
        } else {
            App.showAlert(data.error, 'danger');
        }
    })
    .catch(error => {
        App.showAlert('Erro ao criar usuário', 'danger');
    });
}
</script>
```

## Responsividade e UX

### 1. Design Responsivo com Bootstrap

```html
<!-- Grid responsivo -->
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar (desktop) / Offcanvas (mobile) -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="sidebar">
                <!-- Menu lateral -->
            </div>
        </div>
        
        <!-- Conteúdo principal -->
        <div class="col-lg-9">
            <div class="row">
                <?php foreach ($posts as $post): ?>
                    <div class="col-sm-6 col-md-4 col-xl-3 mb-4">
                        <!-- Card do post -->
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Menu móvel (offcanvas) -->
<div class="offcanvas offcanvas-start d-lg-none" id="sidebar">
    <div class="offcanvas-header">
        <h5>Menu</h5>
        <button class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <!-- Mesmo conteúdo do sidebar -->
    </div>
</div>
```

### 2. Loading States e Feedback

```javascript
// Mostrar loading durante operações
function showLoading(element) {
    const originalText = element.textContent;
    element.textContent = 'Carregando...';
    element.disabled = true;
    
    return function hideLoading() {
        element.textContent = originalText;
        element.disabled = false;
    };
}

// Uso
document.getElementById('saveBtn').addEventListener('click', function() {
    const hideLoading = showLoading(this);
    
    // Fazer operação
    App.ajax('/save-data', { method: 'POST' })
        .then(response => {
            App.showAlert('Dados salvos!', 'success');
        })
        .finally(() => {
            hideLoading();
        });
});
```

### 3. Confirmações Elegantes

```javascript
// Substituir confirm() nativo por modal Bootstrap
function confirmAction(message, onConfirm) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmBtn">Confirmar</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    modal.querySelector('#confirmBtn').addEventListener('click', () => {
        onConfirm();
        bsModal.hide();
    });
    
    modal.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(modal);
    });
}

// Uso nos links de eliminar
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        
        confirmAction('Tem certeza que deseja eliminar este item?', () => {
            window.location.href = this.href;
        });
    });
});
```

## Melhores Práticas

### 1. Segurança no Frontend

```php
<!-- Sempre escapar dados vindos do backend -->
<h1><?= htmlspecialchars($post->title) ?></h1>
<p><?= nl2br(htmlspecialchars($post->content)) ?></p>

<!-- Para dados JSON -->
<script>
const postData = <?= json_encode([
    'id' => $post->id,
    'title' => $post->title,
    'content' => $post->content
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
```

### 2. Performance

```javascript
// Debounce para pesquisas
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Uso
const debouncedSearch = debounce(function(query) {
    // Fazer pesquisa
}, 300);

document.getElementById('search').addEventListener('input', function() {
    debouncedSearch(this.value);
});
```

### 3. Acessibilidade

```html
<!-- Labels corretos -->
<label for="email" class="form-label">Email</label>
<input type="email" id="email" class="form-control" required 
       aria-describedby="emailHelp">
<div id="emailHelp" class="form-text">Nunca compartilhamos seu email</div>

<!-- Estados de loading -->
<button type="submit" aria-describedby="loadingText">
    Salvar
    <span id="loadingText" class="visually-hidden">Carregando...</span>
</button>

<!-- Alertas para leitores de tela -->
<div role="alert" aria-live="polite" id="messages"></div>
```

### 4. Organização do Código

```javascript
// Organizar por módulos
const UserModule = {
    init() {
        this.bindEvents();
    },
    
    bindEvents() {
        document.getElementById('userForm')?.addEventListener('submit', this.handleSubmit.bind(this));
    },
    
    handleSubmit(e) {
        // Lógica do submit
    },
    
    validateUser(userData) {
        // Validação
    }
};

// Inicializar quando DOM carregar
document.addEventListener('DOMContentLoaded', function() {
    UserModule.init();
});
```

## Conclusão

O frontend do sistema GEstufas combina a simplicidade do PHP com a interatividade do JavaScript e a elegância do Bootstrap. Use este guia como referência para criar interfaces responsivas, acessíveis e fáceis de usar.

Lembre-se sempre de:
- Validar dados no frontend E no backend
- Escapar dados vindos do servidor
- Fornecer feedback visual para o usuário
- Manter o código organizado e legível
- Testar em diferentes dispositivos e navegadores
