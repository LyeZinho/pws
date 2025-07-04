# 🎮 Guia de Controllers - Controladores MVC

## 📋 Introdução aos Controllers

Os **Controllers** no sistema GEstufas são responsáveis por processar requisições HTTP, coordenar a lógica de negócio entre Models e Views, e retornar respostas adequadas ao utilizador. Eles são o "C" do padrão MVC.

---

## 🏗️ Estrutura Básica de um Controller

### **Exemplo Simples**
```php
<?php
/**
 * ProductController - Gestão de produtos
 * 
 * Este controller gere todas as operações relacionadas com produtos:
 * - Listagem de produtos
 * - Visualização de produto específico
 * - Criação de novos produtos
 * - Edição de produtos existentes
 * - Remoção de produtos
 * 
 * @package Controllers
 */
class ProductController extends Controller {
    
    /**
     * index() - Lista todos os produtos
     * 
     * GET /index.php?c=products&a=index
     */
    public function index() {
        // 1. Verificar autenticação (se necessário)
        $this->authenticationFilter();
        
        try {
            // 2. Obter dados do modelo
            $products = Product::find('all', array(
                'include' => array('category', 'user'),
                'order' => 'created_at DESC',
                'limit' => 20
            ));
            
            // 3. Preparar dados para a view
            $data = [
                'title' => 'Lista de Produtos',
                'products' => $products,
                'totalProducts' => Product::count()
            ];
            
            // 4. Renderizar view
            $this->renderView('products', 'index', $data);
            
        } catch (Exception $e) {
            // 5. Tratamento de erros
            error_log("Erro no ProductController::index() - " . $e->getMessage());
            
            $data = [
                'title' => 'Erro - Produtos',
                'error' => 'Erro ao carregar produtos.',
                'products' => []
            ];
            
            $this->renderView('products', 'index', $data);
        }
    }
    
    /**
     * show() - Mostra um produto específico
     * 
     * GET /index.php?c=products&a=show&id=1
     */
    public function show() {
        // Obter ID da URL
        $productId = $this->getHTTPGetParam('id');
        
        if (!$productId) {
            $this->redirectToRoute('products', 'index');
            return;
        }
        
        try {
            $product = Product::find($productId, array(
                'include' => array('category', 'user', 'reviews')
            ));
            
            if (!$product) {
                $_SESSION['error_message'] = 'Produto não encontrado.';
                $this->redirectToRoute('products', 'index');
                return;
            }
            
            $data = [
                'title' => $product->name,
                'product' => $product
            ];
            
            $this->renderView('products', 'show', $data);
            
        } catch (Exception $e) {
            error_log("Erro no ProductController::show() - " . $e->getMessage());
            $this->redirectToRoute('products', 'index');
        }
    }
    
    /**
     * create() - Formulário para criar produto
     * 
     * GET /index.php?c=products&a=create
     */
    public function create() {
        $this->authenticationFilter();
        
        // Obter categorias para o formulário
        $categories = Category::find('all', array('order' => 'name ASC'));
        
        $data = [
            'title' => 'Criar Produto',
            'categories' => $categories
        ];
        
        $this->renderView('products', 'create', $data);
    }
    
    /**
     * store() - Salvar novo produto
     * 
     * POST /index.php?c=products&a=store
     */
    public function store() {
        $this->authenticationFilter();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToRoute('products', 'create');
            return;
        }
        
        try {
            // Obter dados do formulário
            $name = trim($this->getHTTPPostParam('name'));
            $description = trim($this->getHTTPPostParam('description'));
            $price = floatval($this->getHTTPPostParam('price'));
            $categoryId = intval($this->getHTTPPostParam('category_id'));
            
            // Criar novo produto
            $product = new Product();
            $product->name = $name;
            $product->description = $description;
            $product->price = $price;
            $product->category_id = $categoryId;
            $product->user_id = $_SESSION['user_id'];
            
            if ($product->save()) {
                $_SESSION['success_message'] = 'Produto criado com sucesso!';
                $this->redirectToRoute('products', 'show', ['id' => $product->id]);
            } else {
                // Mostrar erros de validação
                $categories = Category::find('all', array('order' => 'name ASC'));
                
                $data = [
                    'title' => 'Criar Produto',
                    'product' => $product,
                    'categories' => $categories,
                    'errors' => $product->errors->full_messages()
                ];
                
                $this->renderView('products', 'create', $data);
            }
            
        } catch (Exception $e) {
            error_log("Erro no ProductController::store() - " . $e->getMessage());
            $_SESSION['error_message'] = 'Erro ao criar produto.';
            $this->redirectToRoute('products', 'create');
        }
    }
}
```

---

## 🔄 Operações CRUD Completas

### **Read Operations (Leitura)**
```php
/**
 * Diferentes tipos de listagem
 */
public function index() {
    // Listagem simples
    $products = Product::all();
    
    // Listagem com paginação
    $page = intval($this->getHTTPGetParam('page')) ?: 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    $products = Product::find('all', array(
        'limit' => $perPage,
        'offset' => $offset,
        'order' => 'created_at DESC'
    ));
    
    $totalProducts = Product::count();
    $totalPages = ceil($totalProducts / $perPage);
    
    // Listagem com filtros
    $category = $this->getHTTPGetParam('category');
    $search = $this->getHTTPGetParam('search');
    
    $conditions = array();
    $params = array();
    
    if ($category) {
        $conditions[] = 'category_id = ?';
        $params[] = $category;
    }
    
    if ($search) {
        $conditions[] = 'name LIKE ?';
        $params[] = '%' . $search . '%';
    }
    
    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = implode(' AND ', $conditions);
        array_unshift($params, $whereClause);
    }
    
    $products = Product::find('all', array(
        'conditions' => $params,
        'include' => array('category'),
        'order' => 'name ASC'
    ));
    
    $data = [
        'title' => 'Produtos',
        'products' => $products,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'search' => $search,
        'selectedCategory' => $category
    ];
    
    $this->renderView('products', 'index', $data);
}
```

### **Create Operations (Criação)**
```php
/**
 * Mostrar formulário de criação
 */
public function create() {
    $this->authenticationFilter();
    
    // Obter dados necessários para o formulário
    $categories = Category::find('all', array('order' => 'name ASC'));
    $users = User::find('all', array('order' => 'name ASC'));
    
    $data = [
        'title' => 'Criar Produto',
        'categories' => $categories,
        'users' => $users
    ];
    
    $this->renderView('products', 'create', $data);
}

/**
 * Processar criação
 */
public function store() {
    $this->authenticationFilter();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirectToRoute('products', 'create');
        return;
    }
    
    try {
        // Validações personalizadas
        $errors = [];
        
        $name = trim($this->getHTTPPostParam('name'));
        if (empty($name)) {
            $errors[] = 'Nome é obrigatório.';
        }
        
        $price = $this->getHTTPPostParam('price');
        if (!is_numeric($price) || $price <= 0) {
            $errors[] = 'Preço deve ser um número maior que zero.';
        }
        
        // Se há erros de validação, retornar ao formulário
        if (!empty($errors)) {
            $categories = Category::find('all', array('order' => 'name ASC'));
            
            $data = [
                'title' => 'Criar Produto',
                'categories' => $categories,
                'errors' => $errors,
                'formData' => $_POST // Manter dados preenchidos
            ];
            
            $this->renderView('products', 'create', $data);
            return;
        }
        
        // Criar produto
        $product = new Product();
        $product->name = $name;
        $product->description = $this->getHTTPPostParam('description');
        $product->price = floatval($price);
        $product->category_id = intval($this->getHTTPPostParam('category_id'));
        $product->user_id = $_SESSION['user_id'];
        $product->active = 1;
        $product->created_at = date('Y-m-d H:i:s');
        
        if ($product->save()) {
            $_SESSION['success_message'] = 'Produto criado com sucesso!';
            $this->redirectToRoute('products', 'show', ['id' => $product->id]);
        } else {
            $categories = Category::find('all', array('order' => 'name ASC'));
            
            $data = [
                'title' => 'Criar Produto',
                'categories' => $categories,
                'errors' => $product->errors->full_messages(),
                'formData' => $_POST
            ];
            
            $this->renderView('products', 'create', $data);
        }
        
    } catch (Exception $e) {
        error_log("Erro no ProductController::store() - " . $e->getMessage());
        $_SESSION['error_message'] = 'Erro interno. Tente novamente.';
        $this->redirectToRoute('products', 'create');
    }
}
```

### **Update Operations (Actualização)**
```php
/**
 * Mostrar formulário de edição
 */
public function edit() {
    $this->authenticationFilter();
    
    $productId = $this->getHTTPGetParam('id');
    if (!$productId) {
        $this->redirectToRoute('products', 'index');
        return;
    }
    
    try {
        $product = Product::find($productId);
        
        if (!$product) {
            $_SESSION['error_message'] = 'Produto não encontrado.';
            $this->redirectToRoute('products', 'index');
            return;
        }
        
        // Verificar permissões
        if ($product->user_id != $_SESSION['user_id'] && !$this->isAdmin()) {
            $_SESSION['error_message'] = 'Não tem permissão para editar este produto.';
            $this->redirectToRoute('products', 'index');
            return;
        }
        
        $categories = Category::find('all', array('order' => 'name ASC'));
        
        $data = [
            'title' => 'Editar Produto: ' . $product->name,
            'product' => $product,
            'categories' => $categories
        ];
        
        $this->renderView('products', 'edit', $data);
        
    } catch (Exception $e) {
        error_log("Erro no ProductController::edit() - " . $e->getMessage());
        $this->redirectToRoute('products', 'index');
    }
}

/**
 * Processar actualização
 */
public function update() {
    $this->authenticationFilter();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirectToRoute('products', 'index');
        return;
    }
    
    $productId = $this->getHTTPPostParam('id');
    if (!$productId) {
        $this->redirectToRoute('products', 'index');
        return;
    }
    
    try {
        $product = Product::find($productId);
        
        if (!$product) {
            $_SESSION['error_message'] = 'Produto não encontrado.';
            $this->redirectToRoute('products', 'index');
            return;
        }
        
        // Verificar permissões
        if ($product->user_id != $_SESSION['user_id'] && !$this->isAdmin()) {
            $_SESSION['error_message'] = 'Não tem permissão para editar este produto.';
            $this->redirectToRoute('products', 'index');
            return;
        }
        
        // Actualizar dados
        $product->name = trim($this->getHTTPPostParam('name'));
        $product->description = trim($this->getHTTPPostParam('description'));
        $product->price = floatval($this->getHTTPPostParam('price'));
        $product->category_id = intval($this->getHTTPPostParam('category_id'));
        $product->updated_at = date('Y-m-d H:i:s');
        
        if ($product->save()) {
            $_SESSION['success_message'] = 'Produto actualizado com sucesso!';
            $this->redirectToRoute('products', 'show', ['id' => $product->id]);
        } else {
            $categories = Category::find('all', array('order' => 'name ASC'));
            
            $data = [
                'title' => 'Editar Produto: ' . $product->name,
                'product' => $product,
                'categories' => $categories,
                'errors' => $product->errors->full_messages()
            ];
            
            $this->renderView('products', 'edit', $data);
        }
        
    } catch (Exception $e) {
        error_log("Erro no ProductController::update() - " . $e->getMessage());
        $_SESSION['error_message'] = 'Erro ao actualizar produto.';
        $this->redirectToRoute('products', 'index');
    }
}
```

### **Delete Operations (Remoção)**
```php
/**
 * Remover produto
 */
public function delete() {
    $this->authenticationFilter();
    
    $productId = $this->getHTTPGetParam('id');
    if (!$productId) {
        $this->redirectToRoute('products', 'index');
        return;
    }
    
    try {
        $product = Product::find($productId);
        
        if (!$product) {
            $_SESSION['error_message'] = 'Produto não encontrado.';
            $this->redirectToRoute('products', 'index');
            return;
        }
        
        // Verificar permissões
        if ($product->user_id != $_SESSION['user_id'] && !$this->isAdmin()) {
            $_SESSION['error_message'] = 'Não tem permissão para remover este produto.';
            $this->redirectToRoute('products', 'index');
            return;
        }
        
        // Verificar se pode ser removido (sem pedidos associados)
        $orderItems = OrderItem::count(array(
            'conditions' => array('product_id = ?', $productId)
        ));
        
        if ($orderItems > 0) {
            $_SESSION['error_message'] = 'Não é possível remover produto com pedidos associados.';
            $this->redirectToRoute('products', 'show', ['id' => $productId]);
            return;
        }
        
        $productName = $product->name;
        
        if ($product->delete()) {
            $_SESSION['success_message'] = "Produto '$productName' removido com sucesso!";
            error_log("Produto removido - ID: $productId, Nome: $productName, User: {$_SESSION['user_id']}");
        } else {
            $_SESSION['error_message'] = 'Erro ao remover produto.';
        }
        
        $this->redirectToRoute('products', 'index');
        
    } catch (Exception $e) {
        error_log("Erro no ProductController::delete() - " . $e->getMessage());
        $_SESSION['error_message'] = 'Erro interno ao remover produto.';
        $this->redirectToRoute('products', 'index');
    }
}
```

---

## 🔐 Autenticação e Permissões

### **Filtro de Autenticação**
```php
/**
 * Verificar se utilizador está autenticado
 */
protected function authenticationFilter() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error_message'] = 'Precisa de fazer login para aceder a esta página.';
        $this->redirectToRoute('auth', 'login');
        exit;
    }
}

/**
 * Verificar se utilizador é administrador
 */
protected function adminFilter() {
    $this->authenticationFilter();
    
    $user = User::find($_SESSION['user_id']);
    if (!$user || $user->role !== 'admin') {
        $_SESSION['error_message'] = 'Acesso negado. Apenas administradores.';
        $this->redirectToRoute('home', 'index');
        exit;
    }
}

/**
 * Verificar se utilizador é dono do recurso
 */
protected function ownerFilter($resourceUserId) {
    $this->authenticationFilter();
    
    if ($_SESSION['user_id'] != $resourceUserId && !$this->isAdmin()) {
        $_SESSION['error_message'] = 'Não tem permissão para aceder a este recurso.';
        $this->redirectToRoute('home', 'index');
        exit;
    }
}

/**
 * Verificar se utilizador é admin
 */
protected function isAdmin() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $user = User::find($_SESSION['user_id']);
    return $user && $user->role === 'admin';
}
```

### **Exemplo com Diferentes Níveis de Acesso**
```php
class ProductController extends Controller {
    
    /**
     * Lista produtos - acesso público
     */
    public function index() {
        // Sem filtro de autenticação - acesso público
        $products = Product::find('all', array(
            'conditions' => array('active = ?', true),
            'order' => 'created_at DESC'
        ));
        
        $data = ['products' => $products];
        $this->renderView('products', 'index', $data);
    }
    
    /**
     * Criar produto - apenas utilizadores autenticados
     */
    public function create() {
        $this->authenticationFilter();
        
        $data = ['title' => 'Criar Produto'];
        $this->renderView('products', 'create', $data);
    }
    
    /**
     * Editar produto - apenas dono ou admin
     */
    public function edit() {
        $this->authenticationFilter();
        
        $productId = $this->getHTTPGetParam('id');
        $product = Product::find($productId);
        
        if (!$product) {
            $this->redirectToRoute('products', 'index');
            return;
        }
        
        $this->ownerFilter($product->user_id);
        
        $data = ['product' => $product];
        $this->renderView('products', 'edit', $data);
    }
    
    /**
     * Administração - apenas admins
     */
    public function admin() {
        $this->adminFilter();
        
        $products = Product::find('all', array(
            'include' => array('user'),
            'order' => 'created_at DESC'
        ));
        
        $data = ['products' => $products];
        $this->renderView('products', 'admin', $data);
    }
}
```

---

## 📡 Comunicação Frontend/Backend

### **Processar Requisições AJAX**
```php
/**
 * API para buscar produtos via AJAX
 */
public function api_search() {
    $this->authenticationFilter();
    
    // Definir header para JSON
    header('Content-Type: application/json');
    
    try {
        $search = $this->getHTTPGetParam('q');
        $category = $this->getHTTPGetParam('category');
        $limit = intval($this->getHTTPGetParam('limit')) ?: 10;
        
        $conditions = array();
        $params = array();
        
        if ($search) {
            $conditions[] = 'name LIKE ?';
            $params[] = '%' . $search . '%';
        }
        
        if ($category) {
            $conditions[] = 'category_id = ?';
            $params[] = $category;
        }
        
        $whereClause = '';
        if (!empty($conditions)) {
            $whereClause = implode(' AND ', $conditions);
            array_unshift($params, $whereClause);
        }
        
        $products = Product::find('all', array(
            'conditions' => $params,
            'include' => array('category'),
            'limit' => $limit,
            'order' => 'name ASC'
        ));
        
        // Converter para array para JSON
        $result = array();
        foreach ($products as $product) {
            $result[] = array(
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'category' => $product->category->name,
                'url' => $product->getUrl()
            );
        }
        
        echo json_encode(array(
            'success' => true,
            'data' => $result,
            'total' => count($result)
        ));
        
    } catch (Exception $e) {
        error_log("Erro na API de pesquisa - " . $e->getMessage());
        
        echo json_encode(array(
            'success' => false,
            'error' => 'Erro interno do servidor'
        ));
    }
    
    exit; // Importante para não renderizar view
}

/**
 * API para actualizar stock via AJAX
 */
public function api_update_stock() {
    $this->authenticationFilter();
    
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(array('success' => false, 'error' => 'Método não permitido'));
        exit;
    }
    
    try {
        $productId = $this->getHTTPPostParam('product_id');
        $newStock = intval($this->getHTTPPostParam('stock'));
        
        $product = Product::find($productId);
        
        if (!$product) {
            echo json_encode(array('success' => false, 'error' => 'Produto não encontrado'));
            exit;
        }
        
        // Verificar permissões
        if ($product->user_id != $_SESSION['user_id'] && !$this->isAdmin()) {
            echo json_encode(array('success' => false, 'error' => 'Sem permissão'));
            exit;
        }
        
        $product->stock = $newStock;
        $product->updated_at = date('Y-m-d H:i:s');
        
        if ($product->save()) {
            echo json_encode(array(
                'success' => true,
                'message' => 'Stock actualizado com sucesso',
                'new_stock' => $product->stock
            ));
        } else {
            echo json_encode(array(
                'success' => false,
                'error' => 'Erro ao actualizar stock'
            ));
        }
        
    } catch (Exception $e) {
        error_log("Erro na API de stock - " . $e->getMessage());
        
        echo json_encode(array(
            'success' => false,
            'error' => 'Erro interno do servidor'
        ));
    }
    
    exit;
}
```

### **Upload de Ficheiros**
```php
/**
 * Upload de imagem de produto
 */
public function upload_image() {
    $this->authenticationFilter();
    
    $productId = $this->getHTTPPostParam('product_id');
    $product = Product::find($productId);
    
    if (!$product) {
        $_SESSION['error_message'] = 'Produto não encontrado.';
        $this->redirectToRoute('products', 'index');
        return;
    }
    
    // Verificar permissões
    $this->ownerFilter($product->user_id);
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        try {
            $uploadDir = 'uploads/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Validar tipo de ficheiro
            $allowedTypes = array('image/jpeg', 'image/png', 'image/gif');
            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                $_SESSION['error_message'] = 'Tipo de ficheiro não permitido. Use JPEG, PNG ou GIF.';
                $this->redirectToRoute('products', 'edit', ['id' => $productId]);
                return;
            }
            
            // Validar tamanho (máximo 5MB)
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($_FILES['image']['size'] > $maxSize) {
                $_SESSION['error_message'] = 'Ficheiro muito grande. Máximo 5MB.';
                $this->redirectToRoute('products', 'edit', ['id' => $productId]);
                return;
            }
            
            // Gerar nome único
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'product_' . $productId . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                // Actualizar produto com nova imagem
                $product->image = $filename;
                $product->save();
                
                $_SESSION['success_message'] = 'Imagem carregada com sucesso!';
            } else {
                $_SESSION['error_message'] = 'Erro ao carregar imagem.';
            }
            
        } catch (Exception $e) {
            error_log("Erro no upload - " . $e->getMessage());
            $_SESSION['error_message'] = 'Erro interno ao carregar imagem.';
        }
    } else {
        $_SESSION['error_message'] = 'Nenhuma imagem seleccionada ou erro no upload.';
    }
    
    $this->redirectToRoute('products', 'edit', ['id' => $productId]);
}
```

---

## 🛠️ Métodos Auxiliares Úteis

### **Validações Personalizadas**
```php
class ProductController extends Controller {
    
    /**
     * Validar dados de produto
     */
    private function validateProductData($data) {
        $errors = array();
        
        // Nome obrigatório
        if (empty(trim($data['name']))) {
            $errors[] = 'Nome é obrigatório.';
        } elseif (strlen(trim($data['name'])) < 3) {
            $errors[] = 'Nome deve ter pelo menos 3 caracteres.';
        }
        
        // Preço válido
        if (!isset($data['price']) || !is_numeric($data['price'])) {
            $errors[] = 'Preço deve ser um número.';
        } elseif (floatval($data['price']) <= 0) {
            $errors[] = 'Preço deve ser maior que zero.';
        }
        
        // Categoria válida
        if (empty($data['category_id']) || !is_numeric($data['category_id'])) {
            $errors[] = 'Categoria é obrigatória.';
        } else {
            $category = Category::find($data['category_id']);
            if (!$category) {
                $errors[] = 'Categoria inválida.';
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitizar dados de entrada
     */
    private function sanitizeProductData($data) {
        return array(
            'name' => trim(strip_tags($data['name'])),
            'description' => trim($data['description']),
            'price' => floatval($data['price']),
            'category_id' => intval($data['category_id']),
            'stock' => intval($data['stock'])
        );
    }
    
    /**
     * Verificar se produto pode ser editado
     */
    private function canEditProduct($product) {
        if (!$product) {
            return false;
        }
        
        // Admin pode editar tudo
        if ($this->isAdmin()) {
            return true;
        }
        
        // Dono pode editar
        if ($product->user_id == $_SESSION['user_id']) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Log de actividade
     */
    private function logActivity($action, $productId, $details = '') {
        $userId = $_SESSION['user_id'] ?? 'N/A';
        $message = "Produto $action - User: $userId, Product: $productId";
        
        if ($details) {
            $message .= ", Details: $details";
        }
        
        error_log($message);
    }
}
```

### **Paginação Avançada**
```php
/**
 * Implementar paginação completa
 */
public function index() {
    try {
        // Parâmetros de paginação
        $page = max(1, intval($this->getHTTPGetParam('page')));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;
        
        // Parâmetros de filtro
        $search = $this->getHTTPGetParam('search');
        $category = $this->getHTTPGetParam('category');
        $sortBy = $this->getHTTPGetParam('sort') ?: 'created_at';
        $sortOrder = $this->getHTTPGetParam('order') ?: 'DESC';
        
        // Construir condições
        $conditions = array('active = ?');
        $params = array(true);
        
        if ($search) {
            $conditions[] = 'name LIKE ?';
            $params[] = '%' . $search . '%';
        }
        
        if ($category) {
            $conditions[] = 'category_id = ?';
            $params[] = $category;
        }
        
        $whereClause = implode(' AND ', $conditions);
        array_unshift($params, $whereClause);
        
        // Validar ordenação
        $allowedSort = array('name', 'price', 'created_at');
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'created_at';
        }
        
        $allowedOrder = array('ASC', 'DESC');
        if (!in_array($sortOrder, $allowedOrder)) {
            $sortOrder = 'DESC';
        }
        
        // Buscar produtos
        $products = Product::find('all', array(
            'conditions' => $params,
            'include' => array('category', 'user'),
            'order' => "$sortBy $sortOrder",
            'limit' => $perPage,
            'offset' => $offset
        ));
        
        // Contar total
        $totalProducts = Product::count(array(
            'conditions' => $params
        ));
        
        $totalPages = ceil($totalProducts / $perPage);
        
        // Obter categorias para filtro
        $categories = Category::find('all', array('order' => 'name ASC'));
        
        $data = array(
            'title' => 'Produtos',
            'products' => $products,
            'categories' => $categories,
            'pagination' => array(
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalProducts,
                'perPage' => $perPage,
                'hasNext' => $page < $totalPages,
                'hasPrev' => $page > 1
            ),
            'filters' => array(
                'search' => $search,
                'category' => $category,
                'sort' => $sortBy,
                'order' => $sortOrder
            )
        );
        
        $this->renderView('products', 'index', $data);
        
    } catch (Exception $e) {
        error_log("Erro na listagem de produtos - " . $e->getMessage());
        
        $data = array(
            'title' => 'Erro - Produtos',
            'error' => 'Erro ao carregar produtos.',
            'products' => array()
        );
        
        $this->renderView('products', 'index', $data);
    }
}
```

---

## 🚀 Dicas e Melhores Práticas

### **1. Estrutura do Controller**
- Um controller por entidade principal (User, Product, Post)
- Métodos claros seguindo convenções REST
- Separação clara entre lógica de apresentação e negócio

### **2. Tratamento de Erros**
- Sempre usar try/catch em operações de base de dados
- Log detalhado de erros para debugging
- Mensagens de erro amigáveis para utilizador

### **3. Validações**
- Validar dados tanto no cliente quanto no servidor
- Sanitizar sempre dados de entrada
- Usar mensagens de erro específicas

### **4. Segurança**
- Filtros de autenticação em métodos sensíveis
- Verificação de permissões adequadas
- Escape de dados para prevenir XSS

### **5. Performance**
- Usar paginação em listagens grandes
- Incluir relacionamentos apenas quando necessário
- Cache para consultas frequentes

### **6. Manutenibilidade**
- Métodos pequenos e focados
- Comentários em lógica complexa
- Nomenclatura clara e consistente

---

Este guia fornece uma base sólida para criar Controllers robustos no sistema GEstufas. Para mais exemplos específicos, consulte os outros guias na pasta `examples/`.
