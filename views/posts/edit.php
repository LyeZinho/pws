<?php
/**
 * View: posts/edit.php
 * 
 * Formulário para editar um post existente
 * 
 * Variáveis disponíveis:
 * - $title: Título da página
 * - $post: Objeto do post a ser editado
 * - $errors: Array de erros de validação (opcional)
 * - $formData: Dados do formulário preservados (opcional)
 * 
 * @package GEstufas\Views\Posts
 * @version 1.0.0
 */

// Verificar se há dados do formulário preservados ou usar dados do post
$formData = isset($formData) ? $formData : [];
$title_value = isset($formData['title']) ? htmlspecialchars($formData['title']) : htmlspecialchars($post->title);
$content_value = isset($formData['content']) ? htmlspecialchars($formData['content']) : htmlspecialchars($post->content);
$tags_value = isset($formData['tags']) ? htmlspecialchars($formData['tags']) : htmlspecialchars($post->tags);
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
                                <a href="index.php?controller=posts&action=index">Posts</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="index.php?controller=posts&action=show&id=<?= $post->id ?>">
                                    <?= htmlspecialchars($post->title) ?>
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
                        Modificar as informações do seu post
                    </p>
                </div>
                <div class="btn-group">
                    <a href="index.php?controller=posts&action=show&id=<?= $post->id ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Voltar ao Post
                    </a>
                    <a href="index.php?controller=posts&action=index" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i>
                        Todos os Posts
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

    <!-- Informações do post -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Informações do Post
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">Post ID:</small>
                            <p class="mb-2"><strong>#<?= $post->id ?></strong></p>
                            
                            <small class="text-muted">Criado em:</small>
                            <p class="mb-2"><?= date('d/m/Y \à\s H:i', strtotime($post->created_at)) ?></p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Última atualização:</small>
                            <p class="mb-2"><?= date('d/m/Y \à\s H:i', strtotime($post->updated_at)) ?></p>
                            
                            <small class="text-muted">Autor:</small>
                            <p class="mb-2">
                                <?= isset($post->user) ? htmlspecialchars($post->user->name) : 'Autor Desconhecido' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulário de edição -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Editar Post
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?controller=posts&action=edit&id=<?= $post->id ?>" id="editPostForm">
                        <!-- Título -->
                        <div class="mb-3">
                            <label for="title" class="form-label">
                                <i class="fas fa-heading me-2"></i>
                                Título do Post <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title" 
                                   name="title" 
                                   value="<?= $title_value ?>"
                                   placeholder="Digite um título atrativo para o seu post"
                                   maxlength="255"
                                   required>
                            <div class="form-text">
                                O título deve ter entre 3 e 255 caracteres.
                            </div>
                            <div class="invalid-feedback">
                                Por favor, forneça um título válido.
                            </div>
                        </div>

                        <!-- Conteúdo -->
                        <div class="mb-3">
                            <label for="content" class="form-label">
                                <i class="fas fa-align-left me-2"></i>
                                Conteúdo <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" 
                                      id="content" 
                                      name="content" 
                                      rows="10"
                                      placeholder="Escreva o conteúdo do seu post aqui..."
                                      required><?= $content_value ?></textarea>
                            <div class="form-text">
                                O conteúdo deve ter pelo menos 10 caracteres. Use Markdown para formatação.
                            </div>
                            <div class="invalid-feedback">
                                Por favor, forneça conteúdo válido.
                            </div>
                        </div>

                        <!-- Tags -->
                        <div class="mb-4">
                            <label for="tags" class="form-label">
                                <i class="fas fa-tags me-2"></i>
                                Tags
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="tags" 
                                   name="tags" 
                                   value="<?= $tags_value ?>"
                                   placeholder="tecnologia, programação, php, web">
                            <div class="form-text">
                                Separe as tags com vírgulas. Exemplo: tecnologia, programação, php
                            </div>
                        </div>

                        <!-- Comparison view (Before/After) -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">
                                    <i class="fas fa-exchange-alt me-2"></i>
                                    Comparação de Alterações
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-info" id="toggleComparison">
                                    <i class="fas fa-eye me-1"></i>
                                    Mostrar Comparação
                                </button>
                            </div>
                            <div class="card d-none" id="comparisonView">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                Versão Original
                                            </h6>
                                            <div class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">
                                                <h6><?= htmlspecialchars($post->title) ?></h6>
                                                <p><?= nl2br(htmlspecialchars($post->content)) ?></p>
                                                <?php if (!empty($post->tags)): ?>
                                                    <div>
                                                        <small class="text-muted">Tags:</small>
                                                        <?php 
                                                        $original_tags = explode(',', $post->tags);
                                                        foreach ($original_tags as $tag): 
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
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted">
                                                <i class="fas fa-edit me-1"></i>
                                                Pré-visualização Atual
                                            </h6>
                                            <div class="bg-warning bg-opacity-10 p-3 rounded" style="max-height: 300px; overflow-y: auto;" id="currentPreview">
                                                <!-- Will be updated via JavaScript -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botões de ação -->
                        <div class="d-flex justify-content-between">
                            <div>
                                <a href="index.php?controller=posts&action=show&id=<?= $post->id ?>" class="btn btn-outline-secondary">
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
                                    Atualizar Post
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Dicas e ajuda -->
    <div class="row mt-4">
        <div class="col-lg-8 mx-auto">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Dicas para Edição
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-check-circle text-success me-2"></i>Boas Práticas</h6>
                            <ul class="list-unstyled">
                                <li>• Revise cuidadosamente o conteúdo</li>
                                <li>• Mantenha o contexto original</li>
                                <li>• Corrija erros gramaticais</li>
                                <li>• Atualize informações desatualizadas</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-exclamation-triangle text-warning me-2"></i>Cuidados</h6>
                            <ul class="list-unstyled">
                                <li>• Não altere o sentido original</li>
                                <li>• Mantenha as referências válidas</li>
                                <li>• Considere comentários existentes</li>
                                <li>• Use a comparação para verificar</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Lembre-se:</strong> As alterações ficam permanentemente registradas e outros utilizadores 
                        poderão ver que o post foi editado.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para funcionalidades avançadas -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const titleInput = document.getElementById('title');
    const contentTextarea = document.getElementById('content');
    const tagsInput = document.getElementById('tags');
    const form = document.getElementById('editPostForm');
    const toggleComparisonBtn = document.getElementById('toggleComparison');
    const comparisonView = document.getElementById('comparisonView');
    const currentPreview = document.getElementById('currentPreview');
    
    // Dados originais do post
    const originalData = {
        title: <?= json_encode($post->title) ?>,
        content: <?= json_encode($post->content) ?>,
        tags: <?= json_encode($post->tags) ?>
    };
    
    // Contador de caracteres para o título
    const titleCounter = document.createElement('small');
    titleCounter.className = 'text-muted';
    titleInput.parentNode.appendChild(titleCounter);
    
    function updateTitleCounter() {
        const length = titleInput.value.length;
        titleCounter.textContent = `${length}/255 caracteres`;
        titleCounter.className = length > 255 ? 'text-danger' : 'text-muted';
    }
    
    titleInput.addEventListener('input', updateTitleCounter);
    updateTitleCounter();
    
    // Contador de caracteres para o conteúdo
    const contentCounter = document.createElement('small');
    contentCounter.className = 'text-muted';
    contentTextarea.parentNode.appendChild(contentCounter);
    
    function updateContentCounter() {
        const length = contentTextarea.value.length;
        contentCounter.textContent = `${length} caracteres`;
        contentCounter.className = length < 10 ? 'text-warning' : 'text-muted';
    }
    
    contentTextarea.addEventListener('input', updateContentCounter);
    updateContentCounter();
    
    // Toggle da comparação
    toggleComparisonBtn.addEventListener('click', function() {
        if (comparisonView.classList.contains('d-none')) {
            comparisonView.classList.remove('d-none');
            this.innerHTML = '<i class="fas fa-eye-slash me-1"></i>Ocultar Comparação';
            updateCurrentPreview();
        } else {
            comparisonView.classList.add('d-none');
            this.innerHTML = '<i class="fas fa-eye me-1"></i>Mostrar Comparação';
        }
    });
    
    // Atualizar pré-visualização atual
    function updateCurrentPreview() {
        const title = titleInput.value.trim();
        const content = contentTextarea.value.trim();
        const tags = tagsInput.value.trim();
        
        let html = '';
        
        if (title) {
            html += `<h6>${escapeHtml(title)}</h6>`;
        }
        
        if (content) {
            html += `<p>${escapeHtml(content).replace(/\n/g, '<br>')}</p>`;
        }
        
        if (tags) {
            html += '<div><small class="text-muted">Tags:</small> ';
            const tagArray = tags.split(',');
            tagArray.forEach(tag => {
                tag = tag.trim();
                if (tag) {
                    html += `<span class="badge bg-secondary me-1">${escapeHtml(tag)}</span>`;
                }
            });
            html += '</div>';
        }
        
        currentPreview.innerHTML = html || '<p class="text-muted">Pré-visualização vazia...</p>';
    }
    
    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Detectar alterações
    function hasChanges() {
        return titleInput.value !== originalData.title ||
               contentTextarea.value !== originalData.content ||
               tagsInput.value !== originalData.tags;
    }
    
    // Indicador visual de alterações
    function updateChangeIndicator() {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (hasChanges()) {
            submitBtn.classList.remove('btn-success');
            submitBtn.classList.add('btn-warning');
            submitBtn.innerHTML = '<i class="fas fa-exclamation me-2"></i>Atualizar Post (Alterado)';
        } else {
            submitBtn.classList.remove('btn-warning');
            submitBtn.classList.add('btn-success');
            submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Atualizar Post';
        }
    }
    
    // Event listeners para detectar alterações
    titleInput.addEventListener('input', updateChangeIndicator);
    contentTextarea.addEventListener('input', updateChangeIndicator);
    tagsInput.addEventListener('input', updateChangeIndicator);
    
    // Atualizar preview automático quando a comparação está visível
    titleInput.addEventListener('input', function() {
        if (!comparisonView.classList.contains('d-none')) {
            updateCurrentPreview();
        }
    });
    
    contentTextarea.addEventListener('input', function() {
        if (!comparisonView.classList.contains('d-none')) {
            updateCurrentPreview();
        }
    });
    
    tagsInput.addEventListener('input', function() {
        if (!comparisonView.classList.contains('d-none')) {
            updateCurrentPreview();
        }
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
        
        // Validar conteúdo
        if (contentTextarea.value.trim().length < 10) {
            contentTextarea.classList.add('is-invalid');
            isValid = false;
        } else {
            contentTextarea.classList.remove('is-invalid');
            contentTextarea.classList.add('is-valid');
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
            alert('Nenhuma alteração foi detectada no post.');
        }
    });
    
    // Funcionalidade de guardar como rascunho
    document.getElementById('saveAsDraft').addEventListener('click', function() {
        const draftData = {
            id: <?= $post->id ?>,
            title: titleInput.value,
            content: contentTextarea.value,
            tags: tagsInput.value,
            timestamp: new Date().toISOString()
        };
        
        localStorage.setItem('post_edit_draft_<?= $post->id ?>', JSON.stringify(draftData));
        
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
        const draft = localStorage.getItem('post_edit_draft_<?= $post->id ?>');
        if (draft) {
            const draftData = JSON.parse(draft);
            
            if (confirm('Foi encontrado um rascunho de edição guardado. Deseja carregá-lo?')) {
                titleInput.value = draftData.title || '';
                contentTextarea.value = draftData.content || '';
                tagsInput.value = draftData.tags || '';
                
                updateTitleCounter();
                updateContentCounter();
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
        localStorage.removeItem('post_edit_draft_<?= $post->id ?>');
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
                id: <?= $post->id ?>,
                title: titleInput.value,
                content: contentTextarea.value,
                tags: tagsInput.value,
                timestamp: new Date().toISOString()
            };
            localStorage.setItem('post_edit_draft_auto_<?= $post->id ?>', JSON.stringify(draftData));
        }
    }, 30000);
});
</script>
