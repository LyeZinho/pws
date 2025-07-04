# 🖼️ Guia de Views - Interface de Utilizador

## 📋 Introdução às Views

As **Views** no sistema GEstufas são responsáveis pela apresentação dos dados ao utilizador. Elas recebem dados dos Controllers e renderizam HTML usando PHP, Bootstrap 5, e JavaScript quando necessário.

---

## 🏗️ Estrutura Básica de uma View

### **Estrutura de Ficheiros**
```
views/
├── layout/
│   ├── header.php          # Cabeçalho comum
│   ├── footer.php          # Rodapé comum
│   └── default.php         # Layout principal
├── products/               # Views do ProductController
│   ├── index.php          # Lista de produtos
│   ├── show.php           # Detalhe do produto
│   ├── create.php         # Formulário de criação
│   └── edit.php           # Formulário de edição
├── users/                 # Views do UserController
│   ├── index.php
│   ├── show.php
│   ├── create.php
│   └── edit.php
└── components/            # Componentes reutilizáveis
    ├── pagination.php
    ├── alerts.php
    └── modal.php
```

### **Exemplo de View Simples**
```php
<?php
/**
 * View: products/index.php
 * 
 * Lista todos os produtos com paginação e filtros
 * 
 * Variáveis disponíveis:
 * - $title: Título da página
 * - $products: Array de produtos
 * - $categories: Array de categorias para filtro
 * - $pagination: Dados de paginação
 * - $filters: Filtros aplicados
 */

// Incluir cabeçalho
include_once 'views/layout/header.php';
?>

<div class="container-fluid py-4">
    <!-- Cabeçalho da página -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-box text-primary me-2"></i>
                        <?= htmlspecialchars($title) ?>
                    </h2>
                    <p class="text-muted mb-0">
                        Gerir e visualizar todos os produtos
                    </p>
                </div>
                <div>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="index.php?c=products&a=create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            Novo Produto
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Mensagens de feedback -->
    <?php include_once 'views/components/alerts.php'; ?>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3">
                <input type="hidden" name="c" value="products">
                <input type="hidden" name="a" value="index">
                
                <div class="col-md-4">
                    <label for="search" class="form-label">Pesquisar</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>" 
                           placeholder="Nome do produto...">
                </div>
                
                <div class="col-md-3">
                    <label for="category" class="form-label">Categoria</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">Todas as categorias</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category->id ?>" 
                                    <?= ($filters['category'] ?? '') == $category->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="sort" class="form-label">Ordenar por</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="created_at" <?= ($filters['sort'] ?? '') == 'created_at' ? 'selected' : '' ?>>
                            Data
                        </option>
                        <option value="name" <?= ($filters['sort'] ?? '') == 'name' ? 'selected' : '' ?>>
                            Nome
                        </option>
                        <option value="price" <?= ($filters['sort'] ?? '') == 'price' ? 'selected' : '' ?>>
                            Preço
                        </option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="order" class="form-label">Ordem</label>
                    <select class="form-select" id="order" name="order">
                        <option value="DESC" <?= ($filters['order'] ?? '') == 'DESC' ? 'selected' : '' ?>>
                            Decrescente
                        </option>
                        <option value="ASC" <?= ($filters['order'] ?? '') == 'ASC' ? 'selected' : '' ?>>
                            Crescente
                        </option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de produtos -->
    <?php if (!empty($products)): ?>
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <!-- Imagem do produto -->
                        <div class="card-img-top position-relative" style="height: 200px; overflow: hidden;">
                            <?php if (!empty($product->image)): ?>
                                <img src="uploads/products/<?= htmlspecialchars($product->image) ?>" 
                                     class="w-100 h-100" style="object-fit: cover;" 
                                     alt="<?= htmlspecialchars($product->name) ?>">
                            <?php else: ?>
                                <div class="w-100 h-100 bg-light d-flex align-items-center justify-content-center">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Badge de categoria -->
                            <span class="badge bg-primary position-absolute top-0 start-0 m-2">
                                <?= htmlspecialchars($product->category->name) ?>
                            </span>
                        </div>
                        
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">
                                <a href="index.php?c=products&a=show&id=<?= $product->id ?>" 
                                   class="text-decoration-none">
                                    <?= htmlspecialchars($product->name) ?>
                                </a>
                            </h5>
                            
                            <p class="card-text text-muted small flex-grow-1">
                                <?= htmlspecialchars(substr($product->description, 0, 100)) ?>
                                <?= strlen($product->description) > 100 ? '...' : '' ?>
                            </p>
                            
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="h5 mb-0 text-primary">
                                        €<?= number_format($product->getFinalPrice(), 2, ',', '.') ?>
                                    </span>
                                    
                                    <?php if ($product->getDiscountPercentage() > 0): ?>
                                        <span class="badge bg-success">
                                            -<?= $product->getDiscountPercentage() ?>%
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Por: <?= htmlspecialchars($product->user->name) ?>
                                    </small>
                                    
                                    <?php if ($product->inStock()): ?>
                                        <span class="badge bg-success">Em stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Esgotado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php?c=products&a=show&id=<?= $product->id ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>
                                    Ver
                                </a>
                                
                                <?php if (isset($_SESSION['user_id']) && 
                                         ($product->user_id == $_SESSION['user_id'] || 
                                          (isset($currentUser) && $currentUser->isAdmin()))): ?>
                                    <a href="index.php?c=products&a=edit&id=<?= $product->id ?>" 
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit me-1"></i>
                                        Editar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Paginação -->
        <?php include_once 'views/components/pagination.php'; ?>
        
    <?php else: ?>
        <!-- Nenhum produto encontrado -->
        <div class="text-center py-5">
            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">Nenhum produto encontrado</h4>
            <p class="text-muted">
                <?php if (!empty($filters['search']) || !empty($filters['category'])): ?>
                    Tente ajustar os filtros de pesquisa.
                <?php else: ?>
                    Ainda não existem produtos no sistema.
                <?php endif; ?>
            </p>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="index.php?c=products&a=create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Criar Primeiro Produto
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Incluir rodapé
include_once 'views/layout/footer.php';
?>
```

---

## 📝 Formulários

### **Formulário de Criação**
```php
<?php
/**
 * View: products/create.php
 * 
 * Formulário para criar novo produto
 */

include_once 'views/layout/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Cabeçalho -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-plus text-primary me-2"></i>
                        <?= htmlspecialchars($title) ?>
                    </h2>
                    <p class="text-muted mb-0">Preencha os dados do novo produto</p>
                </div>
                <a href="index.php?c=products&a=index" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Voltar
                </a>
            </div>

            <!-- Mensagens de erro -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h6 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erro na validação
                    </h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Formulário -->
            <div class="card shadow">
                <div class="card-body">
                    <form method="POST" action="index.php?c=products&a=store" enctype="multipart/form-data" 
                          id="productForm" novalidate>
                        
                        <!-- Nome do produto -->
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Nome do Produto <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control <?= !empty($errors) && empty($formData['name'] ?? '') ? 'is-invalid' : '' ?>" 
                                   id="name" name="name" 
                                   value="<?= htmlspecialchars($formData['name'] ?? '') ?>" 
                                   required maxlength="255">
                            <div class="form-text">Nome que aparecerá na listagem</div>
                            <div class="invalid-feedback">
                                Por favor, insira o nome do produto.
                            </div>
                        </div>

                        <!-- Descrição -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="4" maxlength="2000"><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
                            <div class="form-text">Descrição detalhada do produto (máximo 2000 caracteres)</div>
                        </div>

                        <!-- Preço e Stock em linha -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="price" class="form-label">
                                        Preço (€) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">€</span>
                                        <input type="number" 
                                               class="form-control <?= !empty($errors) && empty($formData['price'] ?? '') ? 'is-invalid' : '' ?>" 
                                               id="price" name="price" 
                                               value="<?= htmlspecialchars($formData['price'] ?? '') ?>" 
                                               step="0.01" min="0.01" required>
                                        <div class="invalid-feedback">
                                            Insira um preço válido.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="stock" class="form-label">Stock</label>
                                    <input type="number" class="form-control" id="stock" name="stock" 
                                           value="<?= htmlspecialchars($formData['stock'] ?? '0') ?>" 
                                           min="0">
                                    <div class="form-text">Quantidade disponível</div>
                                </div>
                            </div>
                        </div>

                        <!-- Categoria -->
                        <div class="mb-3">
                            <label for="category_id" class="form-label">
                                Categoria <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?= !empty($errors) && empty($formData['category_id'] ?? '') ? 'is-invalid' : '' ?>" 
                                    id="category_id" name="category_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category->id ?>" 
                                            <?= ($formData['category_id'] ?? '') == $category->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione uma categoria.
                            </div>
                        </div>

                        <!-- Upload de imagem -->
                        <div class="mb-3">
                            <label for="image" class="form-label">Imagem do Produto</label>
                            <input type="file" class="form-control" id="image" name="image" 
                                   accept="image/jpeg,image/png,image/gif">
                            <div class="form-text">
                                Formatos aceites: JPEG, PNG, GIF. Tamanho máximo: 5MB.
                            </div>
                        </div>

                        <!-- Preço promocional (opcional) -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="hasPromotion" 
                                       <?= !empty($formData['sale_price']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="hasPromotion">
                                    Produto em promoção
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="promotionPrice" 
                             style="display: <?= !empty($formData['sale_price']) ? 'block' : 'none' ?>">
                            <label for="sale_price" class="form-label">Preço Promocional (€)</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" class="form-control" id="sale_price" name="sale_price" 
                                       value="<?= htmlspecialchars($formData['sale_price'] ?? '') ?>" 
                                       step="0.01" min="0.01">
                            </div>
                            <div class="form-text">Preço promocional deve ser menor que o preço normal</div>
                        </div>

                        <!-- Botões -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php?c=products&a=index" class="btn btn-outline-secondary me-md-2">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Criar Produto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para validação e interactividade -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle preço promocional
    const hasPromotionCheck = document.getElementById('hasPromotion');
    const promotionPriceDiv = document.getElementById('promotionPrice');
    const salePriceInput = document.getElementById('sale_price');
    
    hasPromotionCheck.addEventListener('change', function() {
        if (this.checked) {
            promotionPriceDiv.style.display = 'block';
            salePriceInput.required = true;
        } else {
            promotionPriceDiv.style.display = 'none';
            salePriceInput.required = false;
            salePriceInput.value = '';
        }
    });
    
    // Validação do formulário
    const form = document.getElementById('productForm');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Validação personalizada: preço promocional deve ser menor
        const price = parseFloat(document.getElementById('price').value);
        const salePrice = parseFloat(document.getElementById('sale_price').value);
        
        if (hasPromotionCheck.checked && salePrice && salePrice >= price) {
            event.preventDefault();
            alert('O preço promocional deve ser menor que o preço normal.');
            return;
        }
        
        form.classList.add('was-validated');
    });
    
    // Preview da imagem
    const imageInput = document.getElementById('image');
    imageInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            // Verificar tamanho
            if (file.size > 5 * 1024 * 1024) { // 5MB
                alert('Ficheiro muito grande. Máximo 5MB.');
                this.value = '';
                return;
            }
            
            // Verificar tipo
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Tipo de ficheiro não permitido. Use JPEG, PNG ou GIF.');
                this.value = '';
                return;
            }
        }
    });
});
</script>

<?php include_once 'views/layout/footer.php'; ?>
```

### **Formulário de Edição**
```php
<?php
/**
 * View: products/edit.php
 * 
 * Formulário para editar produto existente
 */

include_once 'views/layout/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Cabeçalho -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-edit text-warning me-2"></i>
                        <?= htmlspecialchars($title) ?>
                    </h2>
                    <p class="text-muted mb-0">Actualizar dados do produto</p>
                </div>
                <div>
                    <a href="index.php?c=products&a=show&id=<?= $product->id ?>" 
                       class="btn btn-outline-secondary me-2">
                        <i class="fas fa-eye me-2"></i>
                        Ver Produto
                    </a>
                    <a href="index.php?c=products&a=index" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Voltar
                    </a>
                </div>
            </div>

            <!-- Mensagens -->
            <?php include_once 'views/components/alerts.php'; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Erro na validação</h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Formulário -->
            <div class="card shadow">
                <div class="card-body">
                    <form method="POST" action="index.php?c=products&a=update" 
                          enctype="multipart/form-data" id="editForm">
                        <input type="hidden" name="id" value="<?= $product->id ?>">
                        
                        <!-- Imagem actual -->
                        <?php if (!empty($product->image)): ?>
                            <div class="mb-3">
                                <label class="form-label">Imagem Actual</label>
                                <div class="border rounded p-3 bg-light">
                                    <img src="uploads/products/<?= htmlspecialchars($product->image) ?>" 
                                         class="img-thumbnail" style="max-height: 200px;" 
                                         alt="Imagem actual">
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="removeCurrentImage()">
                                            <i class="fas fa-trash me-1"></i>
                                            Remover Imagem
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Nome -->
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Nome do Produto <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($product->name) ?>" 
                                   required maxlength="255">
                        </div>

                        <!-- Descrição -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="4" maxlength="2000"><?= htmlspecialchars($product->description) ?></textarea>
                        </div>

                        <!-- Preço e Stock -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="price" class="form-label">
                                        Preço (€) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">€</span>
                                        <input type="number" class="form-control" id="price" name="price" 
                                               value="<?= $product->price ?>" 
                                               step="0.01" min="0.01" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="stock" class="form-label">Stock</label>
                                    <input type="number" class="form-control" id="stock" name="stock" 
                                           value="<?= $product->stock ?>" min="0">
                                </div>
                            </div>
                        </div>

                        <!-- Categoria -->
                        <div class="mb-3">
                            <label for="category_id" class="form-label">
                                Categoria <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category->id ?>" 
                                            <?= $product->category_id == $category->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Nova imagem -->
                        <div class="mb-3">
                            <label for="new_image" class="form-label">
                                <?= !empty($product->image) ? 'Nova Imagem' : 'Imagem do Produto' ?>
                            </label>
                            <input type="file" class="form-control" id="new_image" name="new_image" 
                                   accept="image/jpeg,image/png,image/gif">
                            <div class="form-text">
                                <?= !empty($product->image) ? 'Deixe vazio para manter a imagem actual. ' : '' ?>
                                Formatos: JPEG, PNG, GIF. Máximo: 5MB.
                            </div>
                        </div>

                        <!-- Preço promocional -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="hasPromotion" 
                                       <?= !empty($product->sale_price) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="hasPromotion">
                                    Produto em promoção
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="promotionPrice" 
                             style="display: <?= !empty($product->sale_price) ? 'block' : 'none' ?>">
                            <label for="sale_price" class="form-label">Preço Promocional (€)</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" class="form-control" id="sale_price" name="sale_price" 
                                       value="<?= $product->sale_price ?>" 
                                       step="0.01" min="0.01">
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="active" name="active" 
                                       value="1" <?= $product->active ? 'checked' : '' ?>>
                                <label class="form-check-label" for="active">
                                    Produto activo (visível na listagem)
                                </label>
                            </div>
                        </div>

                        <!-- Botões -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                            <div>
                                <?php if ($product->canEdit($_SESSION['user_id'])): ?>
                                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                        <i class="fas fa-trash me-2"></i>
                                        Eliminar Produto
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex">
                                <a href="index.php?c=products&a=show&id=<?= $product->id ?>" 
                                   class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-times me-2"></i>
                                    Cancelar
                                </a>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save me-2"></i>
                                    Actualizar Produto
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmação de remoção -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                    Confirmar Eliminação
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem a certeza que pretende eliminar este produto?</p>
                <p class="text-muted">
                    <strong><?= htmlspecialchars($product->name) ?></strong>
                </p>
                <p class="text-danger small">
                    <i class="fas fa-warning me-1"></i>
                    Esta acção não pode ser revertida.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <a href="index.php?c=products&a=delete&id=<?= $product->id ?>" 
                   class="btn btn-danger">
                    <i class="fas fa-trash me-2"></i>
                    Eliminar
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Função para confirmar eliminação
function confirmDelete() {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Função para remover imagem actual
function removeCurrentImage() {
    if (confirm('Tem a certeza que pretende remover a imagem actual?')) {
        // Criar formulário hidden para enviar requisição
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?c=products&a=remove_image';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'product_id';
        input.value = '<?= $product->id ?>';
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

// Toggle preço promocional
document.getElementById('hasPromotion').addEventListener('change', function() {
    const promotionDiv = document.getElementById('promotionPrice');
    const salePriceInput = document.getElementById('sale_price');
    
    if (this.checked) {
        promotionDiv.style.display = 'block';
    } else {
        promotionDiv.style.display = 'none';
        salePriceInput.value = '';
    }
});
</script>

<?php include_once 'views/layout/footer.php'; ?>
```

---

## 🧩 Componentes Reutilizáveis

### **Componente de Paginação**
```php
<?php
/**
 * Component: pagination.php
 * 
 * Componente reutilizável para paginação
 * 
 * Variáveis necessárias:
 * - $pagination: Array com dados de paginação
 * - $filters: Array com filtros actuais (opcional)
 */

if (isset($pagination) && $pagination['totalPages'] > 1):
    $currentPage = $pagination['currentPage'];
    $totalPages = $pagination['totalPages'];
    $hasNext = $pagination['hasNext'];
    $hasPrev = $pagination['hasPrev'];
    
    // Construir query string para manter filtros
    $queryParams = $_GET;
    unset($queryParams['page']); // Remover page actual
    $baseQuery = http_build_query($queryParams);
    $baseUrl = 'index.php?' . $baseQuery;
    
    // Calcular range de páginas a mostrar
    $range = 2; // Mostrar 2 páginas antes e depois da actual
    $start = max(1, $currentPage - $range);
    $end = min($totalPages, $currentPage + $range);
?>

<nav aria-label="Paginação" class="mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="text-muted">
            Página <?= $currentPage ?> de <?= $totalPages ?> 
            (<?= $pagination['totalItems'] ?> 
            <?= $pagination['totalItems'] == 1 ? 'item' : 'itens' ?>)
        </div>
        
        <div class="btn-group" role="group">
            <select class="form-select form-select-sm" onchange="changePageSize(this.value)" 
                    style="max-width: 150px;">
                <option value="10" <?= ($pagination['perPage'] ?? 10) == 10 ? 'selected' : '' ?>>
                    10 por página
                </option>
                <option value="20" <?= ($pagination['perPage'] ?? 10) == 20 ? 'selected' : '' ?>>
                    20 por página
                </option>
                <option value="50" <?= ($pagination['perPage'] ?? 10) == 50 ? 'selected' : '' ?>>
                    50 por página
                </option>
            </select>
        </div>
    </div>
    
    <ul class="pagination justify-content-center">
        <!-- Primeira página -->
        <?php if ($currentPage > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>&page=1" aria-label="Primeira">
                    <i class="fas fa-angle-double-left"></i>
                </a>
            </li>
        <?php endif; ?>
        
        <!-- Página anterior -->
        <?php if ($hasPrev): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>&page=<?= $currentPage - 1 ?>" 
                   aria-label="Anterior">
                    <i class="fas fa-angle-left"></i>
                </a>
            </li>
        <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">
                    <i class="fas fa-angle-left"></i>
                </span>
            </li>
        <?php endif; ?>
        
        <!-- Reticências no início -->
        <?php if ($start > 1): ?>
            <li class="page-item disabled">
                <span class="page-link">...</span>
            </li>
        <?php endif; ?>
        
        <!-- Páginas numeradas -->
        <?php for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                <?php if ($i == $currentPage): ?>
                    <span class="page-link"><?= $i ?></span>
                <?php else: ?>
                    <a class="page-link" href="<?= $baseUrl ?>&page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            </li>
        <?php endfor; ?>
        
        <!-- Reticências no fim -->
        <?php if ($end < $totalPages): ?>
            <li class="page-item disabled">
                <span class="page-link">...</span>
            </li>
        <?php endif; ?>
        
        <!-- Próxima página -->
        <?php if ($hasNext): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>&page=<?= $currentPage + 1 ?>" 
                   aria-label="Próxima">
                    <i class="fas fa-angle-right"></i>
                </a>
            </li>
        <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">
                    <i class="fas fa-angle-right"></i>
                </span>
            </li>
        <?php endif; ?>
        
        <!-- Última página -->
        <?php if ($currentPage < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $baseUrl ?>&page=<?= $totalPages ?>" 
                   aria-label="Última">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<script>
function changePageSize(perPage) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('per_page', perPage);
    urlParams.set('page', '1'); // Voltar à primeira página
    window.location.search = urlParams.toString();
}
</script>

<?php endif; ?>
```

### **Componente de Alertas**
```php
<?php
/**
 * Component: alerts.php
 * 
 * Mostra mensagens de sucesso, erro, warning, info
 */

// Mensagem de sucesso
if (isset($_SESSION['success_message'])):
?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle me-2"></i>
            <div><?= htmlspecialchars($_SESSION['success_message']) ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php
    unset($_SESSION['success_message']);
endif;

// Mensagem de erro
if (isset($_SESSION['error_message'])):
?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-circle me-2"></i>
            <div><?= htmlspecialchars($_SESSION['error_message']) ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php
    unset($_SESSION['error_message']);
endif;

// Mensagem de warning
if (isset($_SESSION['warning_message'])):
?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <div><?= htmlspecialchars($_SESSION['warning_message']) ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php
    unset($_SESSION['warning_message']);
endif;

// Mensagem de info
if (isset($_SESSION['info_message'])):
?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle me-2"></i>
            <div><?= htmlspecialchars($_SESSION['info_message']) ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php
    unset($_SESSION['info_message']);
endif;
?>
```

### **Modal Genérico**
```php
<?php
/**
 * Component: modal.php
 * 
 * Modal genérico reutilizável
 * 
 * Parâmetros:
 * - $modalId: ID do modal
 * - $modalTitle: Título do modal
 * - $modalBody: Conteúdo do modal
 * - $modalSize: Tamanho (sm, lg, xl)
 * - $modalButtons: Array com botões
 */

$modalId = $modalId ?? 'genericModal';
$modalTitle = $modalTitle ?? 'Modal';
$modalSize = $modalSize ?? '';
$modalButtons = $modalButtons ?? [];
?>

<div class="modal fade" id="<?= $modalId ?>" tabindex="-1" 
     aria-labelledby="<?= $modalId ?>Label" aria-hidden="true">
    <div class="modal-dialog <?= $modalSize ? 'modal-' . $modalSize : '' ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?= $modalId ?>Label">
                    <?= htmlspecialchars($modalTitle) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" 
                        aria-label="Fechar"></button>
            </div>
            
            <div class="modal-body">
                <?= $modalBody ?? '' ?>
            </div>
            
            <?php if (!empty($modalButtons)): ?>
                <div class="modal-footer">
                    <?php foreach ($modalButtons as $button): ?>
                        <button type="<?= $button['type'] ?? 'button' ?>" 
                                class="btn <?= $button['class'] ?? 'btn-secondary' ?>"
                                <?= isset($button['dismiss']) && $button['dismiss'] ? 'data-bs-dismiss="modal"' : '' ?>
                                <?= isset($button['onclick']) ? 'onclick="' . htmlspecialchars($button['onclick']) . '"' : '' ?>>
                            <?= isset($button['icon']) ? '<i class="' . $button['icon'] . ' me-2"></i>' : '' ?>
                            <?= htmlspecialchars($button['text']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
```

---

## 📱 JavaScript e AJAX

### **Pesquisa em Tempo Real**
```javascript
/**
 * Pesquisa de produtos em tempo real
 */
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    const resultsDiv = document.getElementById('searchResults');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Limpar timeout anterior
            clearTimeout(searchTimeout);
            
            // Aguardar 300ms antes de pesquisar
            searchTimeout = setTimeout(() => {
                if (query.length >= 2) {
                    performSearch(query);
                } else {
                    resultsDiv.innerHTML = '';
                    resultsDiv.style.display = 'none';
                }
            }, 300);
        });
    }
    
    function performSearch(query) {
        // Mostrar loading
        resultsDiv.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> A pesquisar...</div>';
        resultsDiv.style.display = 'block';
        
        // Fazer requisição AJAX
        fetch(`index.php?c=products&a=api_search&q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResults(data.data);
                } else {
                    resultsDiv.innerHTML = '<div class="text-danger p-3">Erro na pesquisa</div>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                resultsDiv.innerHTML = '<div class="text-danger p-3">Erro de ligação</div>';
            });
    }
    
    function displayResults(products) {
        if (products.length === 0) {
            resultsDiv.innerHTML = '<div class="text-muted p-3">Nenhum produto encontrado</div>';
            return;
        }
        
        let html = '<div class="list-group">';
        products.forEach(product => {
            html += `
                <a href="${product.url}" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${escapeHtml(product.name)}</h6>
                        <small class="text-primary">€${product.price}</small>
                    </div>
                    <small class="text-muted">${escapeHtml(product.category)}</small>
                </a>
            `;
        });
        html += '</div>';
        
        resultsDiv.innerHTML = html;
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Fechar resultados ao clicar fora
    document.addEventListener('click', function(event) {
        if (!searchInput.contains(event.target) && !resultsDiv.contains(event.target)) {
            resultsDiv.style.display = 'none';
        }
    });
});
```

### **Upload de Ficheiros com Preview**
```javascript
/**
 * Upload de ficheiros com preview
 */
function setupFileUpload() {
    const fileInput = document.getElementById('image');
    const previewContainer = document.getElementById('imagePreview');
    
    if (fileInput) {
        fileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            
            if (file) {
                // Validar ficheiro
                if (!validateFile(file)) {
                    this.value = '';
                    return;
                }
                
                // Mostrar preview
                showImagePreview(file);
            } else {
                hideImagePreview();
            }
        });
    }
    
    function validateFile(file) {
        // Verificar tamanho (5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Ficheiro muito grande. Máximo 5MB.');
            return false;
        }
        
        // Verificar tipo
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Tipo de ficheiro não permitido. Use JPEG, PNG ou GIF.');
            return false;
        }
        
        return true;
    }
    
    function showImagePreview(file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewContainer.innerHTML = `
                <div class="border rounded p-3 bg-light">
                    <img src="${e.target.result}" class="img-thumbnail" 
                         style="max-height: 200px;" alt="Preview">
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-danger" 
                                onclick="removePreview()">
                            <i class="fas fa-times me-1"></i>
                            Remover
                        </button>
                    </div>
                </div>
            `;
            previewContainer.style.display = 'block';
        };
        
        reader.readAsDataURL(file);
    }
    
    function hideImagePreview() {
        previewContainer.innerHTML = '';
        previewContainer.style.display = 'none';
    }
    
    // Função global para remover preview
    window.removePreview = function() {
        fileInput.value = '';
        hideImagePreview();
    };
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', setupFileUpload);
```

---

## 🚀 Dicas e Melhores Práticas

### **1. Segurança**
- Sempre usar `htmlspecialchars()` para escape de HTML
- Validar dados no servidor, não apenas no cliente
- Verificar permissões antes de mostrar botões de acção

### **2. Performance**
- Usar lazy loading para imagens
- Minimizar JavaScript e CSS
- Implementar cache de views quando possível

### **3. UX/UI**
- Feedback visual para acções do utilizador
- Loading states para operações assíncronas
- Validação em tempo real em formulários

### **4. Acessibilidade**
- Usar labels apropriados em formulários
- Implementar navegação por teclado
- Contraste adequado de cores

### **5. Responsividade**
- Mobile-first design
- Testar em diferentes tamanhos de ecrã
- Usar classes Bootstrap adequadas

### **6. Manutenibilidade**
- Componentes reutilizáveis
- Código JavaScript modular
- Comentários em lógica complexa

---

Este guia fornece uma base sólida para criar Views modernas e responsivas no sistema GEstufas. Para mais exemplos, consulte os outros guias nesta pasta.
