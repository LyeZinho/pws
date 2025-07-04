<?php
/**
 * View: projects/create.php
 * 
 * Formulário para criar um novo projeto
 * 
 * Variáveis disponíveis:
 * - $title: Título da página
 * - $errors: Array de erros de validação (opcional)
 * - $formData: Dados do formulário preservados (opcional)
 * 
 * @package GEstufas\Views\Projects
 * @version 1.0.0
 */

// Verificar se há dados do formulário preservados
$formData = isset($formData) ? $formData : [];
$title_value = isset($formData['title']) ? htmlspecialchars($formData['title']) : '';
$description_value = isset($formData['description']) ? htmlspecialchars($formData['description']) : '';
$technologies_value = isset($formData['technologies']) ? htmlspecialchars($formData['technologies']) : '';
$repository_url_value = isset($formData['repository_url']) ? htmlspecialchars($formData['repository_url']) : '';
$live_url_value = isset($formData['live_url']) ? htmlspecialchars($formData['live_url']) : '';
$status_value = isset($formData['status']) ? $formData['status'] : 'active';
?>

<div class="container-fluid py-4">
    <!-- Cabeçalho da página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-plus-circle text-primary me-2"></i>
                        <?= htmlspecialchars($title) ?>
                    </h2>
                    <p class="text-muted mb-0">
                        Criar um novo projeto para partilhar com a comunidade
                    </p>
                </div>
                <div>
                    <a href="index.php?controller=projects&action=index" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Voltar à Lista
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

    <!-- Formulário de criação -->
    <form method="POST" action="index.php?controller=projects&action=create" id="createProjectForm">
        <div class="row">
            <!-- Coluna principal com formulário -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Informações Básicas
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
                                      rows="6"
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

            <!-- Sidebar com ajuda e pré-visualização -->
            <div class="col-lg-4">
                <!-- Pré-visualização -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-eye me-2"></i>
                            Pré-visualização
                        </h6>
                    </div>
                    <div class="card-body" id="preview">
                        <div class="text-muted">
                            <i class="fas fa-project-diagram fa-2x mb-2"></i>
                            <p>Preencha os campos para ver a pré-visualização do projeto.</p>
                        </div>
                    </div>
                </div>

                <!-- Dicas -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-lightbulb me-2"></i>
                            Dicas para um Bom Projeto
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Título claro:</strong> Use palavras-chave descritivas
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Descrição completa:</strong> Explique o problema que resolve
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Tecnologias:</strong> Liste todas as ferramentas usadas
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Links válidos:</strong> Verifique se funcionam corretamente
                            </li>
                            <li class="mb-0">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Status correto:</strong> Mantenha atualizado
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Exemplos de tecnologias -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-code me-2"></i>
                            Tecnologias Populares
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="h6">Frontend</h6>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-primary tech-tag" data-tech="HTML">HTML</span>
                                <span class="badge bg-primary tech-tag" data-tech="CSS">CSS</span>
                                <span class="badge bg-primary tech-tag" data-tech="JavaScript">JavaScript</span>
                                <span class="badge bg-primary tech-tag" data-tech="Bootstrap">Bootstrap</span>
                                <span class="badge bg-primary tech-tag" data-tech="React">React</span>
                                <span class="badge bg-primary tech-tag" data-tech="Vue.js">Vue.js</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="h6">Backend</h6>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-success tech-tag" data-tech="PHP">PHP</span>
                                <span class="badge bg-success tech-tag" data-tech="Node.js">Node.js</span>
                                <span class="badge bg-success tech-tag" data-tech="Python">Python</span>
                                <span class="badge bg-success tech-tag" data-tech="Laravel">Laravel</span>
                                <span class="badge bg-success tech-tag" data-tech="Express">Express</span>
                            </div>
                        </div>
                        
                        <div>
                            <h6 class="h6">Bases de Dados</h6>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-warning tech-tag" data-tech="MySQL">MySQL</span>
                                <span class="badge bg-warning tech-tag" data-tech="PostgreSQL">PostgreSQL</span>
                                <span class="badge bg-warning tech-tag" data-tech="MongoDB">MongoDB</span>
                                <span class="badge bg-warning tech-tag" data-tech="SQLite">SQLite</span>
                            </div>
                        </div>
                        
                        <div class="form-text mt-2">
                            Clique nas tecnologias para adicionar automaticamente.
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
                            <a href="index.php?controller=projects&action=index" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </a>
                            
                            <div>
                                <button type="button" class="btn btn-outline-info me-2" id="saveAsDraft">
                                    <i class="fas fa-save me-2"></i>
                                    Guardar Rascunho
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>
                                    Criar Projeto
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
    const preview = document.getElementById('preview');
    const form = document.getElementById('createProjectForm');
    
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
    
    // Atualizar pré-visualização
    function updatePreview() {
        const title = titleInput.value.trim();
        const description = descriptionTextarea.value.trim();
        const technologies = technologiesInput.value.trim();
        const status = statusSelect.value;
        const repositoryUrl = repositoryUrlInput.value.trim();
        const liveUrl = liveUrlInput.value.trim();
        
        let html = '';
        
        if (title) {
            html += `<h6 class="text-primary">${escapeHtml(title)}</h6>`;
        } else {
            html += `<h6 class="text-muted">Título do Projeto</h6>`;
        }
        
        // Status badge
        const statusColors = {
            'active': 'success',
            'completed': 'primary',
            'on_hold': 'warning'
        };
        const statusLabels = {
            'active': 'Ativo',
            'completed': 'Concluído',
            'on_hold': 'Em Pausa'
        };
        
        html += `<div class="mb-2">
            <span class="badge bg-${statusColors[status]}">${statusLabels[status]}</span>
        </div>`;
        
        if (description) {
            const truncatedDesc = description.length > 120 ? description.substring(0, 120) + '...' : description;
            html += `<p class="text-muted">${escapeHtml(truncatedDesc)}</p>`;
        } else {
            html += `<p class="text-muted">Descrição do projeto...</p>`;
        }
        
        if (technologies) {
            html += '<div class="mb-2">';
            const techArray = technologies.split(',');
            techArray.forEach(tech => {
                tech = tech.trim();
                if (tech) {
                    html += `<span class="badge bg-light text-dark me-1">${escapeHtml(tech)}</span>`;
                }
            });
            html += '</div>';
        }
        
        if (repositoryUrl || liveUrl) {
            html += '<div class="btn-group btn-group-sm w-100">';
            if (repositoryUrl) {
                html += '<a href="#" class="btn btn-outline-dark disabled"><i class="fab fa-github"></i></a>';
            }
            if (liveUrl) {
                html += '<a href="#" class="btn btn-outline-info disabled"><i class="fas fa-external-link-alt"></i></a>';
            }
            html += '</div>';
        }
        
        preview.innerHTML = html;
    }
    
    // Event listeners para atualizar preview
    [titleInput, descriptionTextarea, technologiesInput, statusSelect, repositoryUrlInput, liveUrlInput].forEach(element => {
        element.addEventListener('input', updatePreview);
    });
    
    // Função para escapar HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Adicionar tecnologias ao clicar nas tags
    document.querySelectorAll('.tech-tag').forEach(tag => {
        tag.addEventListener('click', function() {
            const tech = this.dataset.tech;
            const currentTechs = technologiesInput.value.trim();
            
            if (currentTechs) {
                if (!currentTechs.split(',').map(t => t.trim()).includes(tech)) {
                    technologiesInput.value = currentTechs + ', ' + tech;
                }
            } else {
                technologiesInput.value = tech;
            }
            
            updatePreview();
            
            // Feedback visual
            this.classList.add('bg-dark');
            setTimeout(() => {
                this.classList.remove('bg-dark');
            }, 200);
        });
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
    
    // Funcionalidade de guardar como rascunho
    document.getElementById('saveAsDraft').addEventListener('click', function() {
        const draftData = {
            title: titleInput.value,
            description: descriptionTextarea.value,
            technologies: technologiesInput.value,
            repository_url: repositoryUrlInput.value,
            live_url: liveUrlInput.value,
            status: statusSelect.value,
            timestamp: new Date().toISOString()
        };
        
        localStorage.setItem('project_draft', JSON.stringify(draftData));
        
        // Mostrar feedback
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fas fa-check me-2"></i>Rascunho Guardado!';
        this.classList.remove('btn-outline-info');
        this.classList.add('btn-success');
        
        setTimeout(() => {
            this.innerHTML = originalText;
            this.classList.remove('btn-success');
            this.classList.add('btn-outline-info');
        }, 2000);
    });
    
    // Carregar rascunho se existir
    function loadDraft() {
        const draft = localStorage.getItem('project_draft');
        if (draft) {
            const draftData = JSON.parse(draft);
            
            if (confirm('Foi encontrado um rascunho guardado. Deseja carregá-lo?')) {
                titleInput.value = draftData.title || '';
                descriptionTextarea.value = draftData.description || '';
                technologiesInput.value = draftData.technologies || '';
                repositoryUrlInput.value = draftData.repository_url || '';
                liveUrlInput.value = draftData.live_url || '';
                statusSelect.value = draftData.status || 'active';
                
                updatePreview();
            }
        }
    }
    
    // Carregar rascunho ao carregar a página (se os campos estiverem vazios)
    if (!titleInput.value && !descriptionTextarea.value) {
        loadDraft();
    }
    
    // Limpar rascunho após envio bem-sucedido
    form.addEventListener('submit', function() {
        localStorage.removeItem('project_draft');
    });
    
    // Auto-save a cada 30 segundos
    setInterval(function() {
        if (titleInput.value || descriptionTextarea.value) {
            const draftData = {
                title: titleInput.value,
                description: descriptionTextarea.value,
                technologies: technologiesInput.value,
                repository_url: repositoryUrlInput.value,
                live_url: liveUrlInput.value,
                status: statusSelect.value,
                timestamp: new Date().toISOString()
            };
            localStorage.setItem('project_draft_auto', JSON.stringify(draftData));
        }
    }, 30000);
    
    // Atualizar preview inicial
    updatePreview();
});
</script>

<!-- CSS adicional -->
<style>
.tech-tag {
    cursor: pointer;
    transition: all 0.2s ease;
}

.tech-tag:hover {
    transform: scale(1.05);
}

.is-invalid {
    border-color: #dc3545;
}

.is-valid {
    border-color: #28a745;
}

.gap-1 {
    gap: 0.25rem;
}

#preview {
    min-height: 150px;
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
