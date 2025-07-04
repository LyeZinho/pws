<?php
/**
 * View: posts/show.php
 * 
 * Exibe detalhes completos de um post com comentários
 * 
 * Variáveis disponíveis:
 * - $title: Título da página
 * - $post: Objeto do post
 * - $comments: Array de comentários
 * - $canEdit: Boolean se o usuário pode editar
 * - $currentUserId: ID do usuário atual
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
        
        <!-- Se há erro, mostrar apenas o botão de voltar -->
        <div class="text-center">
            <a href="index.php?controller=posts&action=index" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>
                Voltar aos Posts
            </a>
        </div>
    <?php else: ?>
        <!-- Cabeçalho com navegação -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="index.php?controller=posts&action=index">Posts</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    <?= htmlspecialchars($post->title) ?>
                                </li>
                            </ol>
                        </nav>
                    </div>
                    <div class="btn-group">
                        <a href="index.php?controller=posts&action=index" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Voltar à Lista
                        </a>
                        <?php if (isset($canEdit) && $canEdit): ?>
                            <a href="index.php?controller=posts&action=edit&id=<?= $post->id ?>" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-2"></i>
                                Editar
                            </a>
                            <a href="index.php?controller=posts&action=delete&id=<?= $post->id ?>" 
                               class="btn btn-outline-danger"
                               onclick="return confirm('Tem certeza que deseja eliminar este post?')">
                                <i class="fas fa-trash me-2"></i>
                                Eliminar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conteúdo principal do post -->
        <div class="row">
            <div class="col-lg-8">
                <!-- Post principal -->
                <article class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h3 mb-0"><?= htmlspecialchars($post->title) ?></h1>
                    </div>
                    <div class="card-body">
                        <!-- Meta informações -->
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-user-circle fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">
                                        <?= isset($post->user) ? htmlspecialchars($post->user->name) : 'Autor Desconhecido' ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?= isset($post->user) ? htmlspecialchars($post->user->email) : '' ?>
                                    </small>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted">
                                    <small>
                                        <i class="fas fa-calendar me-1"></i>
                                        Criado em <?= date('d/m/Y \à\s H:i', strtotime($post->created_at)) ?>
                                    </small>
                                </div>
                                <?php if ($post->updated_at !== $post->created_at): ?>
                                    <div class="text-muted">
                                        <small>
                                            <i class="fas fa-edit me-1"></i>
                                            Atualizado em <?= date('d/m/Y \à\s H:i', strtotime($post->updated_at)) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tags -->
                        <?php if (!empty($post->tags)): ?>
                            <div class="mb-3">
                                <i class="fas fa-tags text-muted me-2"></i>
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

                        <!-- Conteúdo do post -->
                        <div class="post-content">
                            <?= nl2br(htmlspecialchars($post->content)) ?>
                        </div>
                    </div>
                    
                    <!-- Rodapé do post com ações -->
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted">
                                    <i class="fas fa-comments me-1"></i>
                                    <?= count($comments) ?> comentário<?= count($comments) !== 1 ? 's' : '' ?>
                                </span>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="scrollToComments()">
                                    <i class="fas fa-comment me-1"></i>
                                    Comentar
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="sharePost()">
                                    <i class="fas fa-share me-1"></i>
                                    Partilhar
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Sidebar com informações adicionais -->
            <div class="col-lg-4">
                <!-- Informações do autor -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>
                            Sobre o Autor
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <i class="fas fa-user-circle fa-4x text-primary mb-3"></i>
                        <h6><?= isset($post->user) ? htmlspecialchars($post->user->name) : 'Autor Desconhecido' ?></h6>
                        <p class="text-muted small mb-3">
                            <?= isset($post->user) ? htmlspecialchars($post->user->email) : '' ?>
                        </p>
                        <?php if (isset($post->user)): ?>
                            <a href="index.php?controller=users&action=show&id=<?= $post->user->id ?>" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>
                                Ver Perfil
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Estatísticas do post -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Estatísticas
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h4 class="text-primary mb-0"><?= count($comments) ?></h4>
                                    <small class="text-muted">Comentários</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h4 class="text-info mb-0"><?= str_word_count($post->content) ?></h4>
                                <small class="text-muted">Palavras</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Posts relacionados (simulado) -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-newspaper me-2"></i>
                            Posts Relacionados
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Funcionalidade de posts relacionados será implementada em breve.
                        </p>
                        <a href="index.php?controller=posts&action=index" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-list me-1"></i>
                            Ver Todos os Posts
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção de comentários -->
        <div class="row mt-4" id="comments-section">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-comments me-2"></i>
                            Comentários (<?= count($comments) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Formulário para novo comentário -->
                        <form method="POST" action="index.php?controller=posts&action=comment" class="mb-4">
                            <input type="hidden" name="post_id" value="<?= $post->id ?>">
                            
                            <div class="mb-3">
                                <label for="comment-content" class="form-label">
                                    <i class="fas fa-comment me-2"></i>
                                    Adicionar Comentário
                                </label>
                                <textarea class="form-control" 
                                          id="comment-content" 
                                          name="content" 
                                          rows="3"
                                          placeholder="Escreva o seu comentário aqui..."
                                          required></textarea>
                                <div class="form-text">
                                    Seja respeitoso e construtivo nos seus comentários.
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    Publicar Comentário
                                </button>
                            </div>
                        </form>

                        <!-- Lista de comentários -->
                        <?php if (!empty($comments)): ?>
                            <div class="comments-list">
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment mb-3 p-3 border rounded">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-circle fa-lg text-muted me-2"></i>
                                                <div>
                                                    <h6 class="mb-0">
                                                        <?= isset($comment->user) ? htmlspecialchars($comment->user->name) : 'Usuário Desconhecido' ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y \à\s H:i', strtotime($comment->created_at)) ?>
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <!-- Opções do comentário (para o autor) -->
                                            <?php if (isset($currentUserId) && isset($comment->user_id) && $currentUserId == $comment->user_id): ?>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                            type="button" 
                                                            data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="editComment(<?= $comment->id ?>)">
                                                                <i class="fas fa-edit me-2"></i>Editar
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#" onclick="deleteComment(<?= $comment->id ?>)">
                                                                <i class="fas fa-trash me-2"></i>Eliminar
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="comment-content">
                                            <?= nl2br(htmlspecialchars($comment->content)) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Nenhum comentário ainda</h6>
                                <p class="text-muted">Seja o primeiro a comentar este post!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript para funcionalidades adicionais -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismissal das mensagens de alerta
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

// Scroll suave para os comentários
function scrollToComments() {
    document.getElementById('comments-section').scrollIntoView({ 
        behavior: 'smooth' 
    });
    
    // Focar no campo de comentário
    setTimeout(function() {
        document.getElementById('comment-content').focus();
    }, 500);
}

// Partilhar post
function sharePost() {
    if (navigator.share) {
        navigator.share({
            title: '<?= htmlspecialchars($post->title) ?>',
            text: '<?= htmlspecialchars(substr($post->content, 0, 100)) ?>...',
            url: window.location.href
        });
    } else {
        // Fallback: copiar URL para clipboard
        navigator.clipboard.writeText(window.location.href).then(function() {
            // Mostrar feedback
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-1"></i>URL Copiada!';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-outline-secondary');
            
            setTimeout(function() {
                btn.innerHTML = originalText;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
            }, 2000);
        });
    }
}

// Editar comentário (placeholder)
function editComment(commentId) {
    alert('Funcionalidade de edição de comentários será implementada em breve.');
}

// Eliminar comentário (placeholder)
function deleteComment(commentId) {
    if (confirm('Tem certeza que deseja eliminar este comentário?')) {
        alert('Funcionalidade de eliminação de comentários será implementada em breve.');
    }
}

// Validação do formulário de comentário
document.querySelector('form').addEventListener('submit', function(e) {
    const content = document.getElementById('comment-content').value.trim();
    
    if (content.length < 3) {
        e.preventDefault();
        alert('O comentário deve ter pelo menos 3 caracteres.');
        document.getElementById('comment-content').focus();
    }
});

// Auto-resize do textarea
const textarea = document.getElementById('comment-content');
textarea.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});
</script>
