<?php
/**
 * View: posts/create.php
 * 
 * Formulário para criar um novo post
 * 
 * Variáveis disponíveis:
 * - $title: Título da página
 * - $errors: Array de erros de validação (opcional)
 * - $formData: Dados do formulário preservados (opcional)
 * 
 * @package GEstufas\Views\Posts
 * @version 1.0.0
 */

// Verificar se há dados do formulário preservados
$formData = isset($formData) ? $formData : [];
$title_value = isset($formData['title']) ? htmlspecialchars($formData['title']) : '';
$content_value = isset($formData['content']) ? htmlspecialchars($formData['content']) : '';
$tags_value = isset($formData['tags']) ? htmlspecialchars($formData['tags']) : '';
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
                        Criar um novo post para partilhar com a comunidade
                    </p>
                </div>
                <div>
                    <a href="index.php?controller=posts&action=index" class="btn btn-outline-secondary">
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
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Detalhes do Post
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?controller=posts&action=create" id="createPostForm">
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
                                      rows="8"
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

                        <!-- Preview do conteúdo -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">
                                    <i class="fas fa-eye me-2"></i>
                                    Pré-visualização
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-info" id="togglePreview">
                                    <i class="fas fa-sync me-1"></i>
                                    Atualizar Preview
                                </button>
                            </div>
                            <div class="card">
                                <div class="card-body bg-light" id="contentPreview">
                                    <p class="text-muted">Digite o conteúdo acima para ver a pré-visualização...</p>
                                </div>
                            </div>
                        </div>

                        <!-- Botões de ação -->
                        <div class="d-flex justify-content-between">
                            <a href="index.php?controller=posts&action=index" class="btn btn-outline-secondary">
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
                                    Criar Post
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
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Dicas para um Bom Post
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-check-circle text-success me-2"></i>Títulos Eficazes</h6>
                            <ul class="list-unstyled">
                                <li>• Seja claro e específico</li>
                                <li>• Use palavras-chave relevantes</li>
                                <li>• Mantenha entre 3-10 palavras</li>
                                <li>• Desperte curiosidade</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-edit text-primary me-2"></i>Conteúdo de Qualidade</h6>
                            <ul class="list-unstyled">
                                <li>• Estruture com parágrafos</li>
                                <li>• Use listas quando apropriado</li>
                                <li>• Inclua exemplos práticos</li>
                                <li>• Revise antes de publicar</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mt-3 p-3 bg-light rounded">
                        <h6><i class="fas fa-markdown me-2"></i>Formatação Markdown Suportada</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <small>
                                    <code>**texto em negrito**</code><br>
                                    <code>*texto em itálico*</code><br>
                                    <code>`código inline`</code>
                                </small>
                            </div>
                            <div class="col-md-6">
                                <small>
                                    <code># Título Principal</code><br>
                                    <code>## Subtítulo</code><br>
                                    <code>- Item de lista</code>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para melhorar a experiência do utilizador -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const titleInput = document.getElementById('title');
    const contentTextarea = document.getElementById('content');
    const tagsInput = document.getElementById('tags');
    const previewDiv = document.getElementById('contentPreview');
    const togglePreviewBtn = document.getElementById('togglePreview');
    const form = document.getElementById('createPostForm');
    
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
    
    // Preview do conteúdo
    function updatePreview() {
        const content = contentTextarea.value.trim();
        if (content) {
            // Conversão básica de Markdown para HTML
            let html = content
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/`(.*?)`/g, '<code>$1</code>')
                .replace(/^# (.*$)/gm, '<h1>$1</h1>')
                .replace(/^## (.*$)/gm, '<h2>$1</h2>')
                .replace(/^### (.*$)/gm, '<h3>$1</h3>')
                .replace(/^- (.*$)/gm, '<li>$1</li>')
                .replace(/\n/g, '<br>');
            
            // Envolver listas em <ul>
            html = html.replace(/(<li>.*<\/li>)/g, '<ul>$1</ul>');
            
            previewDiv.innerHTML = html;
        } else {
            previewDiv.innerHTML = '<p class="text-muted">Digite o conteúdo acima para ver a pré-visualização...</p>';
        }
    }
    
    // Atualizar preview automaticamente
    contentTextarea.addEventListener('input', updatePreview);
    togglePreviewBtn.addEventListener('click', updatePreview);
    
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
        }
    });
    
    // Funcionalidade de guardar como rascunho (localStorage)
    const saveAsDraftBtn = document.getElementById('saveAsDraft');
    
    saveAsDraftBtn.addEventListener('click', function() {
        const draftData = {
            title: titleInput.value,
            content: contentTextarea.value,
            tags: tagsInput.value,
            timestamp: new Date().toISOString()
        };
        
        localStorage.setItem('post_draft', JSON.stringify(draftData));
        
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
        const draft = localStorage.getItem('post_draft');
        if (draft) {
            const draftData = JSON.parse(draft);
            
            if (confirm('Foi encontrado um rascunho guardado. Deseja carregá-lo?')) {
                titleInput.value = draftData.title || '';
                contentTextarea.value = draftData.content || '';
                tagsInput.value = draftData.tags || '';
                
                updateTitleCounter();
                updateContentCounter();
                updatePreview();
            }
        }
    }
    
    // Carregar rascunho ao carregar a página (se os campos estiverem vazios)
    if (!titleInput.value && !contentTextarea.value) {
        loadDraft();
    }
    
    // Limpar rascunho após envio bem-sucedido
    form.addEventListener('submit', function() {
        localStorage.removeItem('post_draft');
    });
    
    // Auto-save a cada 30 segundos
    setInterval(function() {
        if (titleInput.value || contentTextarea.value) {
            const draftData = {
                title: titleInput.value,
                content: contentTextarea.value,
                tags: tagsInput.value,
                timestamp: new Date().toISOString()
            };
            localStorage.setItem('post_draft_auto', JSON.stringify(draftData));
        }
    }, 30000);
});
</script>
