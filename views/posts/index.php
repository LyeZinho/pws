<?php
/**
 * View: posts/index.php
 * 
 * Lista todos os posts do sistema com paginação
 * 
 * Variáveis disponíveis:
 * - $title: Título da página
 * - $posts: Array de posts
 * - $currentPage: Página atual
 * - $totalPages: Total de páginas
 * - $totalPosts: Total de posts
 * - $perPage: Posts por página
 * - $error: Mensagem de erro (opcional)
 * 
 * @package GEstufas\Views\Posts
 * @version 1.0.0
 */

// Verificar se há mensagens de sucesso ou erro
$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Limpar mensagens da sessão
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<div class="container-fluid py-4">
    <!-- Cabeçalho da página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-newspaper text-primary me-2"></i>
                        <?= htmlspecialchars($title) ?>
                    </h2>
                    <p class="text-muted mb-0">
                        Gerir e visualizar todos os posts da comunidade
                    </p>
                </div>
                <div>
                    <a href="index.php?controller=posts&action=create" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Novo Post
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mensagens de feedback -->
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($successMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($errorMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Estatísticas rápidas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Total de Posts</h5>
                            <h3 class="mb-0"><?= isset($totalPosts) ? $totalPosts : 0 ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-newspaper fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Página Atual</h5>
                            <h3 class="mb-0"><?= isset($currentPage) ? $currentPage : 1 ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Total de Páginas</h5>
                            <h3 class="mb-0"><?= isset($totalPages) ? $totalPages : 1 ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-copy fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Posts por Página</h5>
                            <h3 class="mb-0"><?= isset($perPage) ? $perPage : 10 ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de posts -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Lista de Posts
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($posts) && is_array($posts)): ?>
                        <div class="row">
                            <?php foreach ($posts as $post): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body d-flex flex-column">
                                            <!-- Título do post -->
                                            <h6 class="card-title text-primary">
                                                <a href="index.php?controller=posts&action=show&id=<?= $post->id ?>" 
                                                   class="text-decoration-none">
                                                    <?= htmlspecialchars($post->title) ?>
                                                </a>
                                            </h6>
                                            
                                            <!-- Excerpt do conteúdo -->
                                            <p class="card-text text-muted flex-grow-1">
                                                <?= htmlspecialchars(substr($post->content, 0, 150)) ?>
                                                <?= strlen($post->content) > 150 ? '...' : '' ?>
                                            </p>
                                            
                                            <!-- Tags -->
                                            <?php if (!empty($post->tags)): ?>
                                                <div class="mb-3">
                                                    <?php 
                                                    $tags = explode(',', $post->tags);
                                                    foreach ($tags as $tag): 
                                                        $tag = trim($tag);
                                                        if (!empty($tag)):
                                                    ?>
                                                        <span class="badge bg-secondary me-1"><?= htmlspecialchars($tag) ?></span>
                                                    <?php 
                                                        endif;
                                                    endforeach; 
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Informações do autor e meta -->
                                            <div class="mt-auto">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user me-1"></i>
                                                        <?= isset($post->user) ? htmlspecialchars($post->user->name) : 'Autor Desconhecido' ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="fas fa-comments me-1"></i>
                                                        <?= isset($post->comment_count) ? $post->comment_count : 0 ?>
                                                    </small>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between align-items-center mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= date('d/m/Y H:i', strtotime($post->created_at)) ?>
                                                    </small>
                                                    
                                                    <!-- Botões de ação -->
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="index.php?controller=posts&action=show&id=<?= $post->id ?>" 
                                                           class="btn btn-outline-primary btn-sm" title="Ver Post">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post->user_id): ?>
                                                            <a href="index.php?controller=posts&action=edit&id=<?= $post->id ?>" 
                                                               class="btn btn-outline-secondary btn-sm" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="index.php?controller=posts&action=delete&id=<?= $post->id ?>" 
                                                               class="btn btn-outline-danger btn-sm" 
                                                               title="Eliminar"
                                                               onclick="return confirm('Tem certeza que deseja eliminar este post?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhum post encontrado</h5>
                            <p class="text-muted">Seja o primeiro a criar um post!</p>
                            <a href="index.php?controller=posts&action=create" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                                Criar Primeiro Post
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Paginação -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="row mt-4">
            <div class="col-12">
                <nav aria-label="Paginação de posts">
                    <ul class="pagination justify-content-center">
                        <!-- Primeira página -->
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="index.php?controller=posts&action=index&page=1">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Página anterior -->
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="index.php?controller=posts&action=index&page=<?= $currentPage - 1 ?>">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Páginas numeradas -->
                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="index.php?controller=posts&action=index&page=<?= $i ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Próxima página -->
                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="index.php?controller=posts&action=index&page=<?= $currentPage + 1 ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Última página -->
                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="index.php?controller=posts&action=index&page=<?= $totalPages ?>">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <!-- Informações da paginação -->
                <div class="text-center text-muted">
                    <small>
                        Mostrando página <?= $currentPage ?> de <?= $totalPages ?> 
                        (<?= $totalPosts ?> posts no total)
                    </small>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript adicional para melhorar a UX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismissal das mensagens de alerta após 5 segundos
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Confirmação de eliminação melhorada
    const deleteLinks = document.querySelectorAll('a[onclick*="confirm"]');
    deleteLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Tem certeza que deseja eliminar este post?\n\nEsta ação não pode ser desfeita e todos os comentários associados também serão eliminados.')) {
                window.location.href = this.href;
            }
        });
        
        // Remover o onclick inline
        link.removeAttribute('onclick');
    });
});
</script>
