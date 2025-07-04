<?php
/**
 * View: projects/edit.php
 * 
 * Formulário para editar um projeto existente
 * 
 * Variáveis disponíveis:
 * - $title: Título da página
 * - $project: Objeto do projeto a ser editado
 * - $errors: Array de erros de validação (opcional)
 * - $formData: Dados do formulário preservados (opcional)
 * 
 * @package GEstufas\Views\Projects
 * @version 1.0.0
 */

// Verificar se há dados do formulário preservados ou usar dados do projeto
$formData = isset($formData) ? $formData : [];
$title_value = isset($formData['title']) ? htmlspecialchars($formData['title']) : htmlspecialchars($project->title);
$description_value = isset($formData['description']) ? htmlspecialchars($formData['description']) : htmlspecialchars($project->description);
$technologies_value = isset($formData['technologies']) ? htmlspecialchars($formData['technologies']) : htmlspecialchars($project->technologies);
$repository_url_value = isset($formData['repository_url']) ? htmlspecialchars($formData['repository_url']) : htmlspecialchars($project->repository_url);
$live_url_value = isset($formData['live_url']) ? htmlspecialchars($formData['live_url']) : htmlspecialchars($project->live_url);
$status_value = isset($formData['status']) ? $formData['status'] : $project->status;
?>

<div class="container-fluid py-4">
    <!-- Cabeçalho da página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="index.php?controller=projects&action=index">Projetos</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="index.php?controller=projects&action=show&id=<?= $project->id ?>">
                                    <?= htmlspecialchars($project->title) ?>
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Editar</li>
                        </ol>
                    </nav>
                    <h2 class="mb-1">
                        <i class="fas fa-edit text-warning me-2"></i>
                        <?= htmlspecialchars($title) ?>
                    </h2>
                    <p class="text-muted mb-0">
                        Modificar as informações do seu projeto
                    </p>
                </div>
                <div class="btn-group">
                    <a href="index.php?controller=projects&action=show&id=<?= $project->id ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Voltar ao Projeto
                    </a>
                    <a href="index.php?controller=projects&action=index" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i>
                        Todos os Projetos
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mensagens de erro -->
    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-danger" role="alert">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erros de Validação
                    </h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Informações do projeto -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Informações do Projeto
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">Projeto ID:</small>
                            <p class="mb-2"><strong>#<?= $project->id ?></strong></p>
                            
                            <small class="text-muted">Criado em:</small>
                            <p class="mb-2"><?= date('d/m/Y \à\s H:i', strtotime($project->created_at)) ?></p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Última atualização:</small>
                            <p class="mb-2"><?= date('d/m/Y \à\s H:i', strtotime($project->updated_at)) ?></p>
                            
                            <small class="text-muted">Status atual:</small>
                            <p class="mb-2">
                                <span class="badge bg-<?= $project->status === 'active' ? 'success' : ($project->status === 'completed' ? 'primary' : 'warning') ?>">
                                    <?= ucfirst($project->status) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulário de edição -->
    <form method="POST" action="index.php?controller=projects&action=edit&id=<?= $project->id ?>" id="editProjectForm">
        <div class="row">
            <!-- Coluna principal com formulário -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-edit me-2"></i>
                            Editar Projeto
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Título -->
                        <div class="mb-3">
                            <label for="title" class="form-label">
                                <i class="fas fa-heading me-2"></i>
                                Título do Projeto <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title" 
                                   name="title" 
                                   value="<?= $title_value ?>"
                                   placeholder="Digite um título claro e descritivo"
                                   maxlength="255"
                                   required>
                            <div class="form-text">
                                O título deve ter entre 3 e 255 caracteres e ser único.
                            </div>
                        </div>

                        <!-- Descrição -->
                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-2"></i>
                                Descrição <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="8"
                                      placeholder="Descreva detalhadamente o seu projeto..."
                                      required><?= $description_value ?></textarea>
                            <div class="form-text">
                                Inclua o objetivo, funcionalidades principais e benefícios do projeto.
                            </div>
                        </div>

                        <!-- Tecnologias -->
                        <div class="mb-3">
                            <label for="technologies" class="form-label">
                                <i class="fas fa-code me-2"></i>
                                Tecnologias Utilizadas
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="technologies" 
                                   name="technologies" 
                                   value="<?= $technologies_value ?>"
                                   placeholder="PHP, MySQL, JavaScript, HTML, CSS">
                            <div class="form-text">
                                Separe as tecnologias com vírgulas. Exemplo: PHP, MySQL, Bootstrap
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">
                                <i class="fas fa-traffic-light me-2"></i>
                                Status do Projeto <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?= $status_value === 'active' ? 'selected' : '' ?>>
                                    Ativo - Em desenvolvimento
                                </option>
                                <option value="completed" <?= $status_value === 'completed' ? 'selected' : '' ?>>
                                    Concluído - Projeto finalizado
                                </option>
                                <option value="on_hold" <?= $status_value === 'on_hold' ? 'selected' : '' ?>>
                                    Em Pausa - Temporariamente suspenso
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Links e recursos -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-link me-2"></i>
                            Links e Recursos
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- URL do Repositório -->
                        <div class="mb-3">
                            <label for="repository_url" class="form-label">
                                <i class="fab fa-github me-2"></i>
                                URL do Repositório
                            </label>
                            <input type="url" 
                                   class="form-control" 
                                   id="repository_url" 
                                   name="repository_url" 
                                   value="<?= $repository_url_value ?>"
                                   placeholder="https://github.com/usuario/projeto">
                            <div class="form-text">
                                Link para o código-fonte no GitHub, GitLab ou similar.
                            </div>
                        </div>

                        <!-- URL de Demonstração -->
                        <div class="mb-3">
                            <label for="live_url" class="form-label">
                                <i class="fas fa-external-link-alt me-2"></i>
                                URL de Demonstração
                            </label>
                            <input type="url" 
                                   class="form-control" 
                                   id="live_url" 
                                   name="live_url" 
                                   value="<?= $live_url_value ?>"
                                   placeholder="https://meusite.com/demo">
                            <div class="form-text">
                                Link para uma versão online do projeto, se disponível.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar com comparação e ajuda -->
            <div class="col-lg-4">
                <!-- Comparação de alterações -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-exchange-alt me-2"></i>
                                Comparação
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-dark" id="toggleComparison">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body d-none" id="comparisonView">
                        <div class="mb-3">
                            <h6 class="text-muted">Original:</h6>
                            <div class="bg-light p-2 rounded small" style="max-height: 150px; overflow-y: auto;">
                                <strong><?= htmlspecialchars($project->title) ?></strong><br>
                                <small class="text-muted">
                                    <?= htmlspecialchars(substr($project->description, 0, 100)) ?>...
                                </small>
                            </div>
                        </div>
                        <div>
                            <h6 class="text-muted">Atual:</h6>
                            <div class="bg-warning bg-opacity-10 p-2 rounded small" style="max-height: 150px; overflow-y: auto;" id="currentPreview">
                                <!-- Will be updated via JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Histórico de alterações (simulado) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>
                            Histórico de Alterações
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($project->created_at)) ?></small>
                                    <p class="mb-0 small">Projeto criado</p>
                                </div>
                            </div>
                            <?php if ($project->updated_at !== $project->created_at): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-warning"></div>
                                    <div class="timeline-content">
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($project->updated_at)) ?></small>
                                        <p class="mb-0 small">Última atualização</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <small class="text-muted">Agora</small>
                                    <p class="mb-0 small">Editando...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dicas de edição -->
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-lightbulb me-2"></i>
                            Dicas para Edição
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Mantenha a clareza:</strong> Atualize informações desatualizadas
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Status correto:</strong> Reflita o estado atual do projeto
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Links funcionais:</strong> Verifique se ainda estão ativos
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Tecnologias atuais:</strong> Adicione novas ferramentas usadas
                            </li>
                            <li class="mb-0">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Descrição completa:</strong> Inclua novas funcionalidades
                            </li>
                        </ul>
                        
                        <div class="alert alert-info mt-3 mb-0">
                            <small>
                                <i class="fas fa-info-circle me-1"></i>
                                As alterações ficam registradas no histórico.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de ação -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <a href="index.php?controller=projects&action=show&id=<?= $project->id ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>
                                    Cancelar
                                </a>
                            </div>
                            
                            <div>
                                <button type="button" class="btn btn-outline-info me-2" id="previewChanges">
                                    <i class="fas fa-eye me-2"></i>
                                    Pré-visualizar
                                </button>
                                <button type="button" class="btn btn-outline-warning me-2" id="saveAsDraft">
                                    <i class="fas fa-save me-2"></i>
                                    Guardar Rascunho
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check me-2"></i>
                                    Atualizar Projeto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- JavaScript para funcionalidades avançadas -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const titleInput = document.getElementById('title');
    const descriptionTextarea = document.getElementById('description');
    const technologiesInput = document.getElementById('technologies');
    const statusSelect = document.getElementById('status');
    const repositoryUrlInput = document.getElementById('repository_url');
    const liveUrlInput = document.getElementById('live_url');
    const form = document.getElementById('editProjectForm');
    const toggleComparisonBtn = document.getElementById('toggleComparison');
    const comparisonView = document.getElementById('comparisonView');
    const currentPreview = document.getElementById('currentPreview');
    
    // Dados originais do projeto
    const originalData = {
        title: <?= json_encode($project->title) ?>,
        description: <?= json_encode($project->description) ?>,
        technologies: <?= json_encode($project->technologies) ?>,
        repository_url: <?= json_encode($project->repository_url) ?>,
        live_url: <?= json_encode($project->live_url) ?>,
        status: <?= json_encode($project->status) ?>
    };
    
    // Contadores de caracteres
    function addCharacterCounter(element, max = null) {
        const counter = document.createElement('small');
        counter.className = 'text-muted float-end';
        element.parentNode.appendChild(counter);
        
        function updateCounter() {
            const length = element.value.length;
            counter.textContent = max ? `${length}/${max} caracteres` : `${length} caracteres`;
            if (max && length > max) {
                counter.className = 'text-danger float-end';
                element.classList.add('is-invalid');
            } else {
                counter.className = 'text-muted float-end';
                element.classList.remove('is-invalid');
            }
        }
        
        element.addEventListener('input', updateCounter);
        updateCounter();
    }
    
    addCharacterCounter(titleInput, 255);
    addCharacterCounter(descriptionTextarea);
    
    // Toggle da comparação
    toggleComparisonBtn.addEventListener('click', function() {
        if (comparisonView.classList.contains('d-none')) {
            comparisonView.classList.remove('d-none');
            this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            updateCurrentPreview();
        } else {
            comparisonView.classList.add('d-none');
            this.innerHTML = '<i class="fas fa-eye"></i>';
        }
    });
    
    // Atualizar pré-visualização atual
    function updateCurrentPreview() {
        const title = titleInput.value.trim();
        const description = descriptionTextarea.value.trim();
        
        let html = '';
        
        if (title) {
            html += `<strong>${escapeHtml(title)}</strong><br>`;
        }
        
        if (description) {
            const truncatedDesc = description.length > 100 ? description.substring(0, 100) + '...' : description;
            html += `<small class="text-muted">${escapeHtml(truncatedDesc)}</small>`;
        }
        
        currentPreview.innerHTML = html || '<p class="text-muted">Pré-visualização vazia...</p>';
    }
    
    // Detectar alterações
    function hasChanges() {
        return titleInput.value !== originalData.title ||
               descriptionTextarea.value !== originalData.description ||
               technologiesInput.value !== originalData.technologies ||
               repositoryUrlInput.value !== originalData.repository_url ||
               liveUrlInput.value !== originalData.live_url ||
               statusSelect.value !== originalData.status;
    }
    
    // Indicador visual de alterações
    function updateChangeIndicator() {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (hasChanges()) {
            submitBtn.classList.remove('btn-success');
            submitBtn.classList.add('btn-warning');
            submitBtn.innerHTML = '<i class="fas fa-exclamation me-2"></i>Atualizar Projeto (Alterado)';
        } else {
            submitBtn.classList.remove('btn-warning');
            submitBtn.classList.add('btn-success');
            submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Atualizar Projeto';
        }
    }
    
    // Event listeners para detectar alterações
    [titleInput, descriptionTextarea, technologiesInput, repositoryUrlInput, liveUrlInput, statusSelect].forEach(element => {
        element.addEventListener('input', updateChangeIndicator);
    });
    
    // Atualizar preview automático quando a comparação está visível
    [titleInput, descriptionTextarea].forEach(element => {
        element.addEventListener('input', function() {
            if (!comparisonView.classList.contains('d-none')) {
                updateCurrentPreview();
            }
        });
    });
    
    // Pré-visualizar alterações
    document.getElementById('previewChanges').addEventListener('click', function() {
        if (comparisonView.classList.contains('d-none')) {
            toggleComparisonBtn.click();
        } else {
            updateCurrentPreview();
        }
    });
    
    // Validação do formulário
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validar título
        if (titleInput.value.trim().length < 3) {
            titleInput.classList.add('is-invalid');
            isValid = false;
        } else {
            titleInput.classList.remove('is-invalid');
            titleInput.classList.add('is-valid');
        }
        
        // Validar descrição
        if (descriptionTextarea.value.trim().length < 10) {
            descriptionTextarea.classList.add('is-invalid');
            isValid = false;
        } else {
            descriptionTextarea.classList.remove('is-invalid');
            descriptionTextarea.classList.add('is-valid');
        }
        
        // Validar URLs se fornecidas
        if (repositoryUrlInput.value.trim() && !isValidUrl(repositoryUrlInput.value.trim())) {
            repositoryUrlInput.classList.add('is-invalid');
            isValid = false;
        } else {
            repositoryUrlInput.classList.remove('is-invalid');
        }
        
        if (liveUrlInput.value.trim() && !isValidUrl(liveUrlInput.value.trim())) {
            liveUrlInput.classList.add('is-invalid');
            isValid = false;
        } else {
            liveUrlInput.classList.remove('is-invalid');
        }
        
        if (!isValid) {
            e.preventDefault();
            
            // Scroll para o primeiro campo inválido
            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
            }
        } else if (!hasChanges()) {
            e.preventDefault();
            alert('Nenhuma alteração foi detectada no projeto.');
        }
    });
    
    // Função para validar URL
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Funcionalidade de guardar como rascunho
    document.getElementById('saveAsDraft').addEventListener('click', function() {
        const draftData = {
            id: <?= $project->id ?>,
            title: titleInput.value,
            description: descriptionTextarea.value,
            technologies: technologiesInput.value,
            repository_url: repositoryUrlInput.value,
            live_url: liveUrlInput.value,
            status: statusSelect.value,
            timestamp: new Date().toISOString()
        };
        
        localStorage.setItem('project_edit_draft_<?= $project->id ?>', JSON.stringify(draftData));
        
        // Mostrar feedback
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fas fa-check me-2"></i>Rascunho Guardado!';
        this.classList.remove('btn-outline-warning');
        this.classList.add('btn-success');
        
        setTimeout(() => {
            this.innerHTML = originalText;
            this.classList.remove('btn-success');
            this.classList.add('btn-outline-warning');
        }, 2000);
    });
    
    // Carregar rascunho se existir
    function loadDraft() {
        const draft = localStorage.getItem('project_edit_draft_<?= $project->id ?>');
        if (draft) {
            const draftData = JSON.parse(draft);
            
            if (confirm('Foi encontrado um rascunho de edição guardado. Deseja carregá-lo?')) {
                titleInput.value = draftData.title || '';
                descriptionTextarea.value = draftData.description || '';
                technologiesInput.value = draftData.technologies || '';
                repositoryUrlInput.value = draftData.repository_url || '';
                liveUrlInput.value = draftData.live_url || '';
                statusSelect.value = draftData.status || 'active';
                
                updateChangeIndicator();
                
                if (!comparisonView.classList.contains('d-none')) {
                    updateCurrentPreview();
                }
            }
        }
    }
    
    // Carregar rascunho ao carregar a página
    loadDraft();
    
    // Limpar rascunho após envio bem-sucedido
    form.addEventListener('submit', function() {
        localStorage.removeItem('project_edit_draft_<?= $project->id ?>');
    });
    
    // Aviso antes de sair com alterações não guardadas
    window.addEventListener('beforeunload', function(e) {
        if (hasChanges()) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
    
    // Auto-save a cada 30 segundos
    setInterval(function() {
        if (hasChanges()) {
            const draftData = {
                id: <?= $project->id ?>,
                title: titleInput.value,
                description: descriptionTextarea.value,
                technologies: technologiesInput.value,
                repository_url: repositoryUrlInput.value,
                live_url: liveUrlInput.value,
                status: statusSelect.value,
                timestamp: new Date().toISOString()
            };
            localStorage.setItem('project_edit_draft_auto_<?= $project->id ?>', JSON.stringify(draftData));
        }
    }, 30000);
});
</script>

<!-- CSS adicional -->
<style>
.timeline {
    position: relative;
    padding-left: 20px;
}

.timeline-item {
    position: relative;
    margin-bottom: 15px;
}

.timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.timeline:before {
    content: '';
    position: absolute;
    left: -21px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.is-invalid {
    border-color: #dc3545;
}

.is-valid {
    border-color: #28a745;
}

@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        border-radius: 0.25rem !important;
        margin-bottom: 0.25rem;
    }
}
</style>
