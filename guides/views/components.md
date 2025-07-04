# 🧩 Componentes - Elementos Reutilizáveis

## Visão Geral

Os componentes são elementos de interface reutilizáveis que podem ser incluídos em múltiplas views, promovendo consistência visual e facilitando a manutenção do código.

## 📂 Estrutura de Componentes

```
views/
├── layout/
│   └── components/          # Componentes globais
│       ├── navbar.php       # Navegação principal
│       ├── footer.php       # Rodapé
│       ├── flash-messages.php
│       ├── breadcrumb.php
│       ├── pagination.php   # Paginação
│       ├── search-form.php  # Formulário de pesquisa
│       ├── modal-confirm.php # Modal de confirmação
│       └── loading-spinner.php
└── components/              # Componentes específicos
    ├── user-card.php        # Card de utilizador
    ├── post-preview.php     # Preview de post
    ├── project-badge.php    # Badge de projeto
    ├── comment-item.php     # Item de comentário
    └── tag-list.php         # Lista de tags
```

## 🎯 Componentes Globais

### **1. Paginação (views/layout/components/pagination.php)**
```php
<?php
/**
 * Componente de paginação reutilizável
 * 
 * Variáveis esperadas:
 * - $currentPage: Página atual
 * - $totalPages: Total de páginas
 * - $baseUrl: URL base (sem parâmetro page)
 * - $showInfo: Mostrar informações (opcional)
 * - $maxLinks: Máximo de links a mostrar (opcional, padrão 5)
 */

$currentPage = $currentPage ?? 1;
$totalPages = $totalPages ?? 1;
$baseUrl = $baseUrl ?? '';
$showInfo = $showInfo ?? true;
$maxLinks = $maxLinks ?? 5;

// Calcular range de páginas a mostrar
$startPage = max(1, $currentPage - floor($maxLinks / 2));
$endPage = min($totalPages, $startPage + $maxLinks - 1);
$startPage = max(1, $endPage - $maxLinks + 1);

if ($totalPages <= 1) return; // Não mostrar se só há 1 página
?>

<nav aria-label="Paginação" class="d-flex justify-content-between align-items-center">
    <!-- Informações da paginação -->
    <?php if ($showInfo): ?>
        <div class="pagination-info">
            <small class="text-muted">
                Página <?= $currentPage ?> de <?= $totalPages ?>
                <?php if (isset($totalItems)): ?>
                    (<?= $totalItems ?> items total)
                <?php endif; ?>
            </small>
        </div>
    <?php endif; ?>
    
    <!-- Links de paginação -->
    <ul class="pagination pagination-sm mb-0">
        <!-- Primeira página -->
        <?php if ($currentPage > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>&page=1" title="Primeira página">
                    <i class="fas fa-angle-double-left"></i>
                </a>
            </li>
        <?php endif; ?>
        
        <!-- Página anterior -->
        <?php if ($currentPage > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>&page=<?= $currentPage - 1 ?>" title="Página anterior">
                    <i class="fas fa-angle-left"></i>
                </a>
            </li>
        <?php endif; ?>
        
        <!-- Páginas numeradas -->
        <?php for ($page = $startPage; $page <= $endPage; $page++): ?>
            <li class="page-item <?= $page === $currentPage ? 'active' : '' ?>">
                <?php if ($page === $currentPage): ?>
                    <span class="page-link"><?= $page ?></span>
                <?php else: ?>
                    <a class="page-link" href="<?= $baseUrl ?>&page=<?= $page ?>"><?= $page ?></a>
                <?php endif; ?>
            </li>
        <?php endfor; ?>
        
        <!-- Página seguinte -->
        <?php if ($currentPage < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>&page=<?= $currentPage + 1 ?>" title="Página seguinte">
                    <i class="fas fa-angle-right"></i>
                </a>
            </li>
        <?php endif; ?>
        
        <!-- Última página -->
        <?php if ($currentPage < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>&page=<?= $totalPages ?>" title="Última página">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<?php
// Exemplo de uso:
// include 'layout/components/pagination.php';
// ou
// $this->renderComponent('pagination', [
//     'currentPage' => $page,
//     'totalPages' => $totalPages,
//     'baseUrl' => '?c=users&a=index',
//     'totalItems' => $totalUsers
// ]);
?>
```

### **2. Modal de Confirmação (views/layout/components/modal-confirm.php)**
```php
<?php
/**
 * Modal de confirmação reutilizável
 * 
 * Variáveis opcionais:
 * - $modalId: ID do modal (padrão: 'confirmModal')
 * - $title: Título do modal (padrão: 'Confirmar Ação')
 * - $message: Mensagem padrão
 * - $confirmText: Texto do botão de confirmação
 * - $cancelText: Texto do botão de cancelamento
 * - $confirmClass: Classe do botão de confirmação
 */

$modalId = $modalId ?? 'confirmModal';
$title = $title ?? 'Confirmar Ação';
$message = $message ?? 'Tem certeza que deseja continuar?';
$confirmText = $confirmText ?? 'Confirmar';
$cancelText = $cancelText ?? 'Cancelar';
$confirmClass = $confirmClass ?? 'btn-danger';
?>

<div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?= $modalId ?>Title"><?= htmlspecialchars($title) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle text-warning me-3" style="font-size: 2rem;"></i>
                    <div>
                        <p id="<?= $modalId ?>Message" class="mb-0"><?= htmlspecialchars($message) ?></p>
                        <small class="text-muted">Esta ação não pode ser desfeita.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?= htmlspecialchars($cancelText) ?>
                </button>
                <button type="button" class="btn <?= $confirmClass ?>" id="<?= $modalId ?>Confirm">
                    <?= htmlspecialchars($confirmText) ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Funções JavaScript para o modal
window.<?= $modalId ?> = {
    show: function(message, confirmCallback, title) {
        const modal = document.getElementById('<?= $modalId ?>');
        const messageEl = document.getElementById('<?= $modalId ?>Message');
        const titleEl = document.getElementById('<?= $modalId ?>Title');
        const confirmBtn = document.getElementById('<?= $modalId ?>Confirm');
        
        if (message) messageEl.textContent = message;
        if (title) titleEl.textContent = title;
        
        // Remover event listeners anteriores
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        // Adicionar novo event listener
        newConfirmBtn.addEventListener('click', function() {
            bootstrap.Modal.getInstance(modal).hide();
            if (confirmCallback) confirmCallback();
        });
        
        new bootstrap.Modal(modal).show();
    }
};

// Função global para confirmação de eliminação
window.confirmDelete = function(url, itemName, callback) {
    window.<?= $modalId ?>.show(
        `Tem certeza que deseja eliminar "${itemName}"?`,
        function() {
            if (callback) {
                callback();
            } else {
                window.location.href = url;
            }
        },
        'Confirmar Eliminação'
    );
};
</script>
```

### **3. Formulário de Pesquisa (views/layout/components/search-form.php)**
```php
<?php
/**
 * Formulário de pesquisa reutilizável
 * 
 * Variáveis esperadas:
 * - $searchUrl: URL para onde enviar a pesquisa
 * - $searchValue: Valor atual da pesquisa
 * - $placeholder: Placeholder do input
 * - $filters: Array de filtros adicionais (opcional)
 */

$searchUrl = $searchUrl ?? '';
$searchValue = $searchValue ?? ($_GET['search'] ?? '');
$placeholder = $placeholder ?? 'Pesquisar...';
$filters = $filters ?? [];
?>

<form method="GET" action="<?= $searchUrl ?>" class="search-form">
    <!-- Preservar parâmetros existentes (exceto search e page) -->
    <?php foreach ($_GET as $key => $value): ?>
        <?php if (!in_array($key, ['search', 'page'])): ?>
            <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
        <?php endif; ?>
    <?php endforeach; ?>
    
    <div class="input-group">
        <input type="text" 
               class="form-control" 
               name="search" 
               value="<?= htmlspecialchars($searchValue) ?>" 
               placeholder="<?= htmlspecialchars($placeholder) ?>"
               autocomplete="off">
        
        <!-- Filtros adicionais -->
        <?php if (!empty($filters)): ?>
            <?php foreach ($filters as $filter): ?>
                <select class="form-select" name="<?= htmlspecialchars($filter['name']) ?>" style="max-width: 150px;">
                    <option value=""><?= htmlspecialchars($filter['label']) ?></option>
                    <?php foreach ($filter['options'] as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" 
                                <?= ($_GET[$filter['name']] ?? '') === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <button class="btn btn-outline-secondary" type="submit">
            <i class="fas fa-search"></i>
        </button>
        
        <?php if ($searchValue || !empty(array_filter(array_intersect_key($_GET, array_flip(array_column($filters, 'name')))))): ?>
            <a href="<?= $searchUrl ?>" class="btn btn-outline-danger" title="Limpar pesquisa">
                <i class="fas fa-times"></i>
            </a>
        <?php endif; ?>
    </div>
</form>

<style>
.search-form .input-group > * {
    z-index: 1;
}
.search-form .input-group > *:focus {
    z-index: 2;
}
</style>
```

## 🎨 Componentes Específicos

### **1. Card de Utilizador (views/components/user-card.php)**
```php
<?php
/**
 * Card de utilizador reutilizável
 * 
 * Variáveis esperadas:
 * - $user: Objeto User
 * - $showActions: Mostrar botões de ação (opcional)
 * - $cardClass: Classes CSS adicionais (opcional)
 */

if (!isset($user) || !$user) {
    return;
}

$showActions = $showActions ?? false;
$cardClass = $cardClass ?? '';
?>

<div class="card user-card <?= $cardClass ?>">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <!-- Avatar -->
            <div class="me-3">
                <img src="<?= $user->getAvatarUrl() ?>" 
                     alt="Avatar de <?= htmlspecialchars($user->name) ?>"
                     class="rounded-circle"
                     width="50" 
                     height="50">
            </div>
            
            <!-- Informações -->
            <div class="flex-grow-1">
                <h6 class="card-title mb-1">
                    <a href="?c=users&a=show&id=<?= $user->id ?>" class="text-decoration-none">
                        <?= htmlspecialchars($user->name) ?>
                    </a>
                </h6>
                
                <p class="card-text text-muted small mb-1">
                    <?= htmlspecialchars($user->email) ?>
                </p>
                
                <div class="user-meta">
                    <span class="badge bg-<?= $user->role === 'admin' ? 'danger' : 'primary' ?> me-1">
                        <?= ucfirst($user->role) ?>
                    </span>
                    
                    <span class="badge bg-<?= $user->active ? 'success' : 'secondary' ?>">
                        <?= $user->active ? 'Ativo' : 'Inativo' ?>
                    </span>
                    
                    <small class="text-muted ms-2">
                        Membro desde <?= date('d/m/Y', strtotime($user->created_at)) ?>
                    </small>
                </div>
            </div>
            
            <!-- Ações -->
            <?php if ($showActions): ?>
                <div class="ms-2">
                    <div class="btn-group" role="group">
                        <a href="?c=users&a=show&id=<?= $user->id ?>" 
                           class="btn btn-sm btn-outline-info" 
                           title="Ver perfil">
                            <i class="fas fa-eye"></i>
                        </a>
                        
                        <?php if (Auth::getCurrentUser() && Auth::getCurrentUser()->role === 'admin'): ?>
                            <a href="?c=users&a=edit&id=<?= $user->id ?>" 
                               class="btn btn-sm btn-outline-warning" 
                               title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Bio (se existir) -->
        <?php if (!empty($user->bio)): ?>
            <div class="mt-2">
                <small class="text-muted">
                    <?= htmlspecialchars(substr($user->bio, 0, 100)) ?>
                    <?= strlen($user->bio) > 100 ? '...' : '' ?>
                </small>
            </div>
        <?php endif; ?>
        
        <!-- Estatísticas rápidas -->
        <?php if (isset($user->posts) || isset($user->projects)): ?>
            <div class="mt-2 user-stats">
                <?php if (isset($user->posts)): ?>
                    <small class="text-muted me-3">
                        <i class="fas fa-newspaper"></i> <?= count($user->posts) ?> posts
                    </small>
                <?php endif; ?>
                
                <?php if (isset($user->projects)): ?>
                    <small class="text-muted">
                        <i class="fas fa-project-diagram"></i> <?= count($user->projects) ?> projetos
                    </small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.user-card {
    transition: box-shadow 0.2s ease;
}

.user-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.user-card .card-title a:hover {
    text-decoration: underline !important;
}
</style>
```

### **2. Preview de Post (views/components/post-preview.php)**
```php
<?php
/**
 * Preview de post reutilizável
 * 
 * Variáveis esperadas:
 * - $post: Objeto Post
 * - $showAuthor: Mostrar informações do autor (opcional)
 * - $showActions: Mostrar botões de ação (opcional)
 * - $excerptLength: Tamanho do excerpt (opcional)
 */

if (!isset($post) || !$post) {
    return;
}

$showAuthor = $showAuthor ?? true;
$showActions = $showActions ?? false;
$excerptLength = $excerptLength ?? 150;
?>

<article class="card post-preview mb-3">
    <div class="card-body">
        <!-- Header do post -->
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="flex-grow-1">
                <h5 class="card-title mb-1">
                    <a href="?c=posts&a=show&id=<?= $post->id ?>" class="text-decoration-none">
                        <?= htmlspecialchars($post->title) ?>
                    </a>
                </h5>
                
                <!-- Meta informações -->
                <div class="post-meta text-muted small">
                    <?php if ($showAuthor && isset($post->user)): ?>
                        <span class="me-2">
                            <i class="fas fa-user"></i>
                            <a href="?c=users&a=show&id=<?= $post->user->id ?>" class="text-muted text-decoration-none">
                                <?= htmlspecialchars($post->user->name) ?>
                            </a>
                        </span>
                    <?php endif; ?>
                    
                    <span class="me-2">
                        <i class="fas fa-calendar"></i>
                        <?= date('d/m/Y H:i', strtotime($post->created_at)) ?>
                    </span>
                    
                    <?php if (isset($post->comments)): ?>
                        <span class="me-2">
                            <i class="fas fa-comments"></i>
                            <?= count($post->comments) ?> comentários
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($post->published): ?>
                        <span class="badge bg-success">Publicado</span>
                    <?php else: ?>
                        <span class="badge bg-warning">Rascunho</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Ações -->
            <?php if ($showActions && Auth::getCurrentUser()): ?>
                <div class="ms-2">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="?c=posts&a=show&id=<?= $post->id ?>">
                                    <i class="fas fa-eye"></i> Ver post
                                </a>
                            </li>
                            
                            <?php if (Auth::getCurrentUser()->id === $post->user_id || Auth::getCurrentUser()->role === 'admin'): ?>
                                <li>
                                    <a class="dropdown-item" href="?c=posts&a=edit&id=<?= $post->id ?>">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button class="dropdown-item text-danger" 
                                            onclick="confirmDelete('?c=posts&a=delete&id=<?= $post->id ?>', '<?= htmlspecialchars($post->title) ?>')">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Conteúdo -->
        <div class="post-content">
            <p class="card-text">
                <?= htmlspecialchars(substr(strip_tags($post->content), 0, $excerptLength)) ?>
                <?= strlen(strip_tags($post->content)) > $excerptLength ? '...' : '' ?>
            </p>
        </div>
        
        <!-- Tags (se existirem) -->
        <?php if (isset($post->tags) && !empty($post->tags)): ?>
            <div class="post-tags mt-2">
                <?php foreach ($post->tags as $tag): ?>
                    <span class="badge bg-light text-dark me-1"><?= htmlspecialchars($tag->name) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="post-footer mt-3 pt-2 border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="post-stats text-muted small">
                    <?php if (isset($post->likes)): ?>
                        <span class="me-3">
                            <i class="fas fa-heart"></i> <?= count($post->likes) ?> likes
                        </span>
                    <?php endif; ?>
                    
                    <span>
                        <i class="fas fa-eye"></i> <?= $post->views ?? 0 ?> visualizações
                    </span>
                </div>
                
                <a href="?c=posts&a=show&id=<?= $post->id ?>" class="btn btn-sm btn-outline-primary">
                    Ler mais <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</article>

<style>
.post-preview {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.post-preview:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.post-preview .card-title a:hover {
    color: var(--bs-primary) !important;
}

.post-meta a:hover {
    text-decoration: underline !important;
}
</style>
```

## 🔧 Sistema de Renderização de Componentes

### **Helper para Controller (Controller.php)**
```php
class Controller {
    // ... métodos existentes ...
    
    /**
     * Renderizar componente
     */
    protected function renderComponent($componentName, $data = []) {
        $componentPath = "views/components/{$componentName}.php";
        
        if (!file_exists($componentPath)) {
            $componentPath = "views/layout/components/{$componentName}.php";
        }
        
        if (file_exists($componentPath)) {
            // Extrair variáveis para o escopo do componente
            extract($data);
            
            // Buffer de saída
            ob_start();
            include $componentPath;
            return ob_get_clean();
        }
        
        return "<!-- Componente '{$componentName}' não encontrado -->";
    }
    
    /**
     * Incluir componente na view atual
     */
    protected function includeComponent($componentName, $data = []) {
        echo $this->renderComponent($componentName, $data);
    }
}
```

### **Uso nos Controllers**
```php
class PostController extends Controller {
    public function index() {
        $posts = Post::all(['include' => ['user', 'tags']]);
        
        $this->render('posts/index', [
            'posts' => $posts,
            'searchForm' => $this->renderComponent('search-form', [
                'searchUrl' => '?c=posts',
                'placeholder' => 'Pesquisar posts...',
                'filters' => [
                    [
                        'name' => 'status',
                        'label' => 'Status',
                        'options' => [
                            'published' => 'Publicado',
                            'draft' => 'Rascunho'
                        ]
                    ]
                ]
            ])
        ]);
    }
}
```

### **Uso nas Views**
```php
<!-- views/posts/index.php -->
<?php $this->layout = 'layout/main'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Posts</h1>
        <a href="?c=posts&a=create" class="btn btn-primary">Novo Post</a>
    </div>
    
    <!-- Formulário de pesquisa -->
    <?= $searchForm ?>
    
    <!-- Lista de posts -->
    <div class="row">
        <?php foreach ($posts as $post): ?>
            <div class="col-md-6 mb-3">
                <?php include 'components/post-preview.php'; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Paginação -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <?php include 'layout/components/pagination.php'; ?>
    <?php endif; ?>
</div>

<!-- Modal de confirmação -->
<?php include 'layout/components/modal-confirm.php'; ?>
```

---

Os componentes reutilizáveis permitem manter consistência visual, reduzir duplicação de código e facilitar a manutenção da interface. Use componentes para elementos que aparecem em múltiplas páginas ou que têm lógica de apresentação complexa.
