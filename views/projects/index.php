<?php
/**
 * View: projects/index.php
 * 
 * Lista todos os projetos do sistema com paginação e filtros
 * 
 * Variáveis disponíveis:
 * - $title: Título da página
 * - $projects: Array de projetos
 * - $currentPage: Página atual
 * - $totalPages: Total de páginas
 * - $totalProjects: Total de projetos
 * - $perPage: Projetos por página
 * - $statusFilter: Filtro de status atual
 * - $searchTerm: Termo de busca atual
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

// Definir cores para status
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
?>

<div class="container-fluid py-4">
    <!-- Cabeçalho da página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-project-diagram text-primary me-2"></i>
                        <?= htmlspecialchars($title) ?>
                    </h2>
                    <p class="text-muted mb-0">
                        Gerir e colaborar em projetos da comunidade
                    </p>
                </div>
                <div>
                    <a href="index.php?controller=projects&action=create" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Novo Projeto
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
    <?php endif; ?>

    <!-- Filtros e busca -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" action="index.php" class="row g-3">
                        <input type="hidden" name="controller" value="projects">
                        <input type="hidden" name="action" value="index">
                        
                        <div class="col-md-4">
                            <label for="search" class="form-label">
                                <i class="fas fa-search me-1"></i>
                                Buscar Projetos
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="search" 
                                   name="search" 
                                   value="<?= htmlspecialchars($searchTerm ?? '') ?>"
                                   placeholder="Título ou descrição...">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="status" class="form-label">
                                <i class="fas fa-filter me-1"></i>
                                Status
                            </label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Todos os Status</option>
                                <option value="active" <?= ($statusFilter ?? '') === 'active' ? 'selected' : '' ?>>
                                    Ativo
                                </option>
                                <option value="completed" <?= ($statusFilter ?? '') === 'completed' ? 'selected' : '' ?>>
                                    Concluído
                                </option>
                                <option value="on_hold" <?= ($statusFilter ?? '') === 'on_hold' ? 'selected' : '' ?>>
                                    Em Pausa
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="btn-group w-100">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-search me-1"></i>
                                    Filtrar
                                </button>
                                <a href="index.php?controller=projects&action=index" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>
                                    Limpar
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <select class="form-select" onchange="changeSort(this.value)">
                                <option value="newest">Mais Recentes</option>
                                <option value="oldest">Mais Antigos</option>
                                <option value="title">Título A-Z</option>
                                <option value="status">Status</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Estatísticas rápidas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-0">Total de Projetos</h5>
                            <h3 class="mb-0"><?= isset($totalProjects) ? $totalProjects : 0 ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-project-diagram fa-2x"></i>
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
                            <h5 class="card-title mb-0">Projetos Ativos</h5>
                            <h3 class="mb-0" id="activeCount">
                                <?php
                                $activeCount = 0;
                                if (isset($projects)) {
                                    foreach ($projects as $project) {
                                        if ($project->status === 'active') $activeCount++;
                                    }
                                }
                                echo $activeCount;
                                ?>
                            </h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-play-circle fa-2x"></i>
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
                            <h5 class="card-title mb-0">Concluídos</h5>
                            <h3 class="mb-0" id="completedCount">
                                <?php
                                $completedCount = 0;
                                if (isset($projects)) {
                                    foreach ($projects as $project) {
                                        if ($project->status === 'completed') $completedCount++;
                                    }
                                }
                                echo $completedCount;
                                ?>
                            </h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
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
                            <h5 class="card-title mb-0">Em Pausa</h5>
                            <h3 class="mb-0" id="onHoldCount">
                                <?php
                                $onHoldCount = 0;
                                if (isset($projects)) {
                                    foreach ($projects as $project) {
                                        if ($project->status === 'on_hold') $onHoldCount++;
                                    }
                                }
                                echo $onHoldCount;
                                ?>
                            </h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-pause-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de projetos -->
    <div class="row">
        <div class="col-12">
            <?php if (!empty($projects) && is_array($projects)): ?>
                <div class="row">
                    <?php foreach ($projects as $project): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-0 shadow-sm project-card" data-status="<?= $project->status ?>">
                                <div class="card-body d-flex flex-column">
                                    <!-- Status badge -->
                                    <div class="mb-2">
                                        <span class="badge bg-<?= $statusColors[$project->status] ?? 'secondary' ?>">
                                            <i class="fas fa-circle me-1"></i>
                                            <?= $statusLabels[$project->status] ?? ucfirst($project->status) ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Título do projeto -->
                                    <h5 class="card-title text-primary mb-2">
                                        <a href="index.php?controller=projects&action=show&id=<?= $project->id ?>" 
                                           class="text-decoration-none">
                                            <?= htmlspecialchars($project->title) ?>
                                        </a>
                                    </h5>
                                    
                                    <!-- Descrição resumida -->
                                    <p class="card-text text-muted flex-grow-1">
                                        <?= htmlspecialchars(substr($project->description, 0, 120)) ?>
                                        <?= strlen($project->description) > 120 ? '...' : '' ?>
                                    </p>
                                    
                                    <!-- Tecnologias -->
                                    <?php if (!empty($project->technologies)): ?>
                                        <div class="mb-3">
                                            <?php 
                                            $technologies = explode(',', $project->technologies);
                                            $displayTechs = array_slice($technologies, 0, 3); // Mostrar apenas 3
                                            foreach ($displayTechs as $tech): 
                                                $tech = trim($tech);
                                                if (!empty($tech)):
                                            ?>
                                                <span class="badge bg-light text-dark me-1 mb-1"><?= htmlspecialchars($tech) ?></span>
                                            <?php 
                                                endif;
                                            endforeach;
                                            if (count($technologies) > 3):
                                            ?>
                                                <span class="badge bg-secondary">+<?= count($technologies) - 3 ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Links do projeto -->
                                    <div class="mb-3">
                                        <div class="btn-group btn-group-sm w-100">
                                            <?php if (!empty($project->repository_url)): ?>
                                                <a href="<?= htmlspecialchars($project->repository_url) ?>" 
                                                   target="_blank" 
                                                   class="btn btn-outline-dark" 
                                                   title="Repositório">
                                                    <i class="fab fa-github"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($project->live_url)): ?>
                                                <a href="<?= htmlspecialchars($project->live_url) ?>" 
                                                   target="_blank" 
                                                   class="btn btn-outline-info" 
                                                   title="Demo Online">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="index.php?controller=projects&action=show&id=<?= $project->id ?>" 
                                               class="btn btn-outline-primary flex-grow-1">
                                                <i class="fas fa-eye me-1"></i>
                                                Ver Detalhes
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <!-- Informações do criador e meta -->
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                <?= isset($project->user) ? htmlspecialchars($project->user->name) : 'Criador Desconhecido' ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-users me-1"></i>
                                                <?= isset($project->member_count) ? $project->member_count : 1 ?>
                                            </small>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= date('d/m/Y', strtotime($project->created_at)) ?>
                                            </small>
                                            
                                            <!-- Botões de ação para o criador -->
                                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $project->user_id): ?>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="index.php?controller=projects&action=edit&id=<?= $project->id ?>" 
                                                       class="btn btn-outline-secondary btn-sm" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="index.php?controller=projects&action=delete&id=<?= $project->id ?>" 
                                                       class="btn btn-outline-danger btn-sm" 
                                                       title="Eliminar"
                                                       onclick="return confirm('Tem certeza que deseja eliminar este projeto?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <a href="index.php?controller=projects&action=join&id=<?= $project->id ?>" 
                                                   class="btn btn-outline-success btn-sm" title="Aderir ao Projeto">
                                                    <i class="fas fa-plus-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-project-diagram fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum projeto encontrado</h5>
                        <?php if (!empty($searchTerm) || !empty($statusFilter)): ?>
                            <p class="text-muted">
                                Tente ajustar os filtros de busca ou 
                                <a href="index.php?controller=projects&action=index">ver todos os projetos</a>
                            </p>
                        <?php else: ?>
                            <p class="text-muted">Seja o primeiro a criar um projeto!</p>
                            <a href="index.php?controller=projects&action=create" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                                Criar Primeiro Projeto
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Paginação -->
    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <div class="row mt-4">
            <div class="col-12">
                <nav aria-label="Paginação de projetos">
                    <ul class="pagination justify-content-center">
                        <!-- Primeira página -->
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildPaginationUrl(1, $statusFilter, $searchTerm) ?>">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Página anterior -->
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildPaginationUrl($currentPage - 1, $statusFilter, $searchTerm) ?>">
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
                                <a class="page-link" href="<?= buildPaginationUrl($i, $statusFilter, $searchTerm) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Próxima página -->
                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildPaginationUrl($currentPage + 1, $statusFilter, $searchTerm) ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Última página -->
                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildPaginationUrl($totalPages, $statusFilter, $searchTerm) ?>">
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
                        (<?= $totalProjects ?> projetos no total)
                    </small>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Função helper para construir URLs de paginação
function buildPaginationUrl($page, $status = '', $search = '') {
    $url = 'index.php?controller=projects&action=index&page=' . $page;
    if (!empty($status)) {
        $url .= '&status=' . urlencode($status);
    }
    if (!empty($search)) {
        $url .= '&search=' . urlencode($search);
    }
    return $url;
}
?>

<!-- JavaScript adicional para funcionalidades -->
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
    
    // Animação dos cards ao hover
    const projectCards = document.querySelectorAll('.project-card');
    projectCards.forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});

// Função para alterar ordenação
function changeSort(sortBy) {
    const url = new URL(window.location);
    url.searchParams.set('sort', sortBy);
    url.searchParams.set('page', '1'); // Reset para primeira página
    window.location = url;
}

// Filtro rápido por status
function filterByStatus(status) {
    const statusSelect = document.getElementById('status');
    statusSelect.value = status;
    statusSelect.closest('form').submit();
}

// Busca em tempo real (debounced)
let searchTimeout;
document.getElementById('search')?.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (this.value.length >= 3 || this.value.length === 0) {
            this.closest('form').submit();
        }
    }, 500);
});
</script>

<!-- CSS adicional para animações -->
<style>
.project-card {
    transition: all 0.3s ease;
}

.project-card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.badge {
    font-size: 0.75em;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.775rem;
}

@media (max-width: 768px) {
    .project-card {
        margin-bottom: 1rem;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        border-radius: 0.25rem !important;
        margin-bottom: 0.25rem;
    }
}
</style>
