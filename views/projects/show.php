<?php
/**
 * View: projects/show.php
 * 
 * Exibe detalhes completos de um projeto
 * 
 * Variáveis disponíveis:
 * - $title: Título da página
 * - $project: Objeto do projeto
 * - $members: Array de membros
 * - $canEdit: Boolean se o usuário pode editar
 * - $currentUserId: ID do usuário atual
 * - $stats: Estatísticas do projeto
 * - $error: Mensagem de erro (opcional)
 * 
 * @package GEstufas\Views\Projects
 * @version 1.0.0
 */

// Verificar se há mensagens de sucesso ou erro
$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
$infoMessage = isset($_SESSION['info_message']) ? $_SESSION['info_message'] : '';

// Limpar mensagens da sessão
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
unset($_SESSION['info_message']);

// Definir cores e labels para status
$statusColors = [
    'active' => 'success',
    'completed' => 'primary',
    'on_hold' => 'warning'
];

$statusLabels = [
    'active' => 'Ativo',
    'completed' => 'Concluído',
    'on_hold' => 'Em Pausa'
];

$statusIcons = [
    'active' => 'play-circle',
    'completed' => 'check-circle',
    'on_hold' => 'pause-circle'
];
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

    <?php if (!empty($infoMessage)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <?= htmlspecialchars($infoMessage) ?>
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
            <a href="index.php?controller=projects&action=index" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>
                Voltar aos Projetos
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
                                    <a href="index.php?controller=projects&action=index">Projetos</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    <?= htmlspecialchars($project->title) ?>
                                </li>
                            </ol>
                        </nav>
                    </div>
                    <div class="btn-group">
                        <a href="index.php?controller=projects&action=index" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Voltar à Lista
                        </a>
                        <?php if (isset($canEdit) && $canEdit): ?>
                            <a href="index.php?controller=projects&action=edit&id=<?= $project->id ?>" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-2"></i>
                                Editar
                            </a>
                            <a href="index.php?controller=projects&action=delete&id=<?= $project->id ?>" 
                               class="btn btn-outline-danger"
                               onclick="return confirm('Tem certeza que deseja eliminar este projeto?')">
                                <i class="fas fa-trash me-2"></i>
                                Eliminar
                            </a>
                        <?php else: ?>
                            <a href="index.php?controller=projects&action=join&id=<?= $project->id ?>" 
                               class="btn btn-success">
                                <i class="fas fa-plus-circle me-2"></i>
                                Aderir ao Projeto
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Conteúdo principal do projeto -->
            <div class="col-lg-8">
                <!-- Informações principais -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="h3 mb-0"><?= htmlspecialchars($project->title) ?></h1>
                            <span class="badge bg-<?= $statusColors[$project->status] ?? 'secondary' ?> fs-6">
                                <i class="fas fa-<?= $statusIcons[$project->status] ?? 'circle' ?> me-1"></i>
                                <?= $statusLabels[$project->status] ?? ucfirst($project->status) ?>
                            </span>
                        </div>
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
                                        <?= isset($project->user) ? htmlspecialchars($project->user->name) : 'Criador Desconhecido' ?>
                                    </h6>
                                    <small class="text-muted">Criador do projeto</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted">
                                    <small>
                                        <i class="fas fa-calendar me-1"></i>
                                        Criado em <?= date('d/m/Y', strtotime($project->created_at)) ?>
                                    </small>
                                </div>
                                <?php if ($project->updated_at !== $project->created_at): ?>
                                    <div class="text-muted">
                                        <small>
                                            <i class="fas fa-edit me-1"></i>
                                            Atualizado em <?= date('d/m/Y', strtotime($project->updated_at)) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Descrição do projeto -->
                        <div class="mb-4">
                            <h5>
                                <i class="fas fa-info-circle me-2"></i>
                                Sobre o Projeto
                            </h5>
                            <p class="lead"><?= nl2br(htmlspecialchars($project->description)) ?></p>
                        </div>

                        <!-- Tecnologias -->
                        <?php if (!empty($project->technologies)): ?>
                            <div class="mb-4">
                                <h5>
                                    <i class="fas fa-code me-2"></i>
                                    Tecnologias Utilizadas
                                </h5>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php 
                                    $technologies = explode(',', $project->technologies);
                                    foreach ($technologies as $tech): 
                                        $tech = trim($tech);
                                        if (!empty($tech)):
                                    ?>
                                        <span class="badge bg-light text-dark fs-6 px-3 py-2"><?= htmlspecialchars($tech) ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Links do projeto -->
                        <?php if (!empty($project->repository_url) || !empty($project->live_url)): ?>
                            <div class="mb-4">
                                <h5>
                                    <i class="fas fa-link me-2"></i>
                                    Links e Recursos
                                </h5>
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php if (!empty($project->repository_url)): ?>
                                        <a href="<?= htmlspecialchars($project->repository_url) ?>" 
                                           target="_blank" 
                                           class="btn btn-outline-dark">
                                            <i class="fab fa-github me-2"></i>
                                            Ver Código-fonte
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($project->live_url)): ?>
                                        <a href="<?= htmlspecialchars($project->live_url) ?>" 
                                           target="_blank" 
                                           class="btn btn-outline-info">
                                            <i class="fas fa-external-link-alt me-2"></i>
                                            Ver Demo Online
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Membros do projeto -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>
                            Membros do Projeto (<?= count($members) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($members)): ?>
                            <div class="row">
                                <?php foreach ($members as $member): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="d-flex align-items-center p-3 border rounded">
                                            <div class="me-3">
                                                <i class="fas fa-user-circle fa-2x text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?= htmlspecialchars($member->name) ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($member->email) ?></small>
                                                <?php if ($member->id == $project->user_id): ?>
                                                    <div>
                                                        <span class="badge bg-primary">Criador</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <a href="index.php?controller=users&action=show&id=<?= $member->id ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Nenhum membro ainda</h6>
                                <p class="text-muted">Seja o primeiro a aderir a este projeto!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar com informações adicionais -->
            <div class="col-lg-4">
                <!-- Estatísticas do projeto -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Estatísticas
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h4 class="text-primary mb-0"><?= $stats['total_members'] ?></h4>
                                    <small class="text-muted">Membros</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h4 class="text-info mb-0"><?= $stats['days_since_creation'] ?></h4>
                                <small class="text-muted">Dias de Vida</small>
                            </div>
                        </div>
                        <hr>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h5 class="text-success mb-0"><?= $stats['last_update_days'] ?></h5>
                                    <small class="text-muted">Dias Desde Última Atualização</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h5 class="text-warning mb-0"><?= str_word_count($project->description) ?></h5>
                                <small class="text-muted">Palavras na Descrição</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informações do criador -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>
                            Sobre o Criador
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <i class="fas fa-user-circle fa-4x text-primary mb-3"></i>
                        <h6><?= isset($project->user) ? htmlspecialchars($project->user->name) : 'Criador Desconhecido' ?></h6>
                        <p class="text-muted small mb-3">
                            <?= isset($project->user) ? htmlspecialchars($project->user->email) : '' ?>
                        </p>
                        <?php if (isset($project->user)): ?>
                            <a href="index.php?controller=users&action=show&id=<?= $project->user->id ?>" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>
                                Ver Perfil Completo
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ações rápidas -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-bolt me-2"></i>
                            Ações Rápidas
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if (!empty($project->repository_url)): ?>
                                <a href="<?= htmlspecialchars($project->repository_url) ?>" 
                                   target="_blank" 
                                   class="btn btn-outline-dark btn-sm">
                                    <i class="fab fa-github me-2"></i>
                                    Clonar Repositório
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($project->live_url)): ?>
                                <a href="<?= htmlspecialchars($project->live_url) ?>" 
                                   target="_blank" 
                                   class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-external-link-alt me-2"></i>
                                    Testar Online
                                </a>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="shareProject()">
                                <i class="fas fa-share me-2"></i>
                                Partilhar Projeto
                            </button>
                            
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="reportIssue()">
                                <i class="fas fa-bug me-2"></i>
                                Reportar Problema
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Projetos relacionados (placeholder) -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-project-diagram me-2"></i>
                            Projetos Relacionados
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Funcionalidade de projetos relacionados será implementada em breve.
                        </p>
                        <a href="index.php?controller=projects&action=index" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-list me-1"></i>
                            Ver Todos os Projetos
                        </a>
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
            
            if (confirm('Tem certeza que deseja eliminar este projeto?\n\nEsta ação não pode ser desfeita.')) {
                window.location.href = this.href;
            }
        });
        
        // Remover o onclick inline
        link.removeAttribute('onclick');
    });
    
    // Animações para estatísticas
    animateCounters();
});

// Partilhar projeto
function shareProject() {
    const projectTitle = '<?= htmlspecialchars($project->title) ?>';
    const projectDescription = '<?= htmlspecialchars(substr($project->description, 0, 100)) ?>...';
    
    if (navigator.share) {
        navigator.share({
            title: `Projeto: ${projectTitle}`,
            text: projectDescription,
            url: window.location.href
        });
    } else {
        // Fallback: copiar URL para clipboard
        navigator.clipboard.writeText(window.location.href).then(function() {
            // Mostrar feedback
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-2"></i>URL Copiada!';
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

// Reportar problema (placeholder)
function reportIssue() {
    alert('Funcionalidade de reportar problemas será implementada em breve.\n\nPor enquanto, contacte o criador do projeto diretamente.');
}

// Animar contadores
function animateCounters() {
    const counters = document.querySelectorAll('.card-body h4, .card-body h5');
    
    counters.forEach(counter => {
        const target = parseInt(counter.textContent) || 0;
        const increment = target / 20;
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                counter.textContent = target;
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(current);
            }
        }, 50);
    });
}

// Tooltip para tecnologias
document.addEventListener('DOMContentLoaded', function() {
    const techBadges = document.querySelectorAll('.badge');
    techBadges.forEach(badge => {
        badge.setAttribute('data-bs-toggle', 'tooltip');
        badge.setAttribute('title', `Tecnologia: ${badge.textContent}`);
    });
    
    // Inicializar tooltips se Bootstrap estiver disponível
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
</script>

<!-- CSS adicional -->
<style>
.badge {
    font-size: 0.9em;
}

.btn-group .btn {
    border-radius: 0.375rem;
}

.gap-2 {
    gap: 0.5rem;
}

.border-end {
    border-right: 1px solid #dee2e6 !important;
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.5s ease-out;
}

.card:nth-child(2) {
    animation-delay: 0.1s;
}

.card:nth-child(3) {
    animation-delay: 0.2s;
}

@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-bottom: 0.25rem;
    }
    
    .d-flex.gap-2 {
        flex-direction: column;
    }
}
</style>
