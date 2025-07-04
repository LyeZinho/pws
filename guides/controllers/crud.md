# 🎮 CRUD Básico - Guia Completo

## Visão Geral

CRUD (Create, Read, Update, Delete) são as quatro operações básicas de qualquer sistema de gestão de dados. Este guia mostra como implementar operações CRUD completas no sistema GEstufas usando o padrão MVC.

## 📊 Estrutura CRUD

### **Ações Padrão do Controller**
```php
class UserController extends Controller {
    public function index()   // Listar todos (Read)
    public function show()    // Mostrar um (Read)
    public function create()  // Formulário de criação
    public function store()   // Salvar novo (Create)
    public function edit()    // Formulário de edição
    public function update()  // Atualizar existente (Update)
    public function delete()  // Eliminar (Delete)
}
```

### **Mapeamento de URLs**
```
GET  ?c=users&a=index     → index()  (listar)
GET  ?c=users&a=show&id=1 → show()   (mostrar)
GET  ?c=users&a=create    → create() (formulário criar)
POST ?c=users&a=store     → store()  (salvar)
GET  ?c=users&a=edit&id=1 → edit()   (formulário editar)
POST ?c=users&a=update    → update() (atualizar)
POST ?c=users&a=delete    → delete() (eliminar)
```

## 📖 Read (Listar e Mostrar)

### **1. Index - Listar Todos**
```php
<?php
// controllers/UserController.php
class UserController extends Controller {
    
    /**
     * Listar todos os utilizadores
     */
    public function index() {
        try {
            // Buscar todos os utilizadores com paginação
            $page = (int)($_GET['page'] ?? 1);
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            
            // Buscar utilizadores
            $users = User::all([
                'order' => 'created_at DESC',
                'limit' => $perPage,
                'offset' => $offset
            ]);
            
            // Contar total para paginação
            $totalUsers = User::count();
            $totalPages = ceil($totalUsers / $perPage);
            
            // Dados para a view
            $data = [
                'users' => $users,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalUsers' => $totalUsers
            ];
            
            $this->render('users/index', $data);
            
        } catch (Exception $e) {
            $this->setFlash('error', 'Erro ao carregar utilizadores: ' . $e->getMessage());
            $this->render('users/index', ['users' => []]);
        }
    }
}
```

#### **View correspondente (views/users/index.php)**
```php
<?php $this->layout = 'layout/main'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Utilizadores</h1>
        <a href="?c=users&a=create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Novo Utilizador
        </a>
    </div>
    
    <?php if (empty($users)): ?>
        <div class="alert alert-info">
            <h4>Nenhum utilizador encontrado</h4>
            <p>Ainda não existem utilizadores no sistema.</p>
            <a href="?c=users&a=create" class="btn btn-primary">Criar Primeiro Utilizador</a>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user->id ?></td>
                                    <td><?= htmlspecialchars($user->name) ?></td>
                                    <td><?= htmlspecialchars($user->email) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($user->created_at)) ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="?c=users&a=show&id=<?= $user->id ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?c=users&a=edit&id=<?= $user->id ?>" 
                                               class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(<?= $user->id ?>, '<?= htmlspecialchars($user->name) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Paginação">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="?c=users&a=index&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
                <div class="text-muted mt-3">
                    Total: <?= $totalUsers ?> utilizadores
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de confirmação de eliminação -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja eliminar o utilizador <strong id="userName"></strong>?</p>
                <p class="text-danger">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(userId, userName) {
    document.getElementById('userName').textContent = userName;
    document.getElementById('deleteForm').action = '?c=users&a=delete&id=' + userId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
```

### **2. Show - Mostrar Um**
```php
/**
 * Mostrar um utilizador específico
 */
public function show($id = null) {
    try {
        // Validar ID
        $id = $id ?? $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            $this->setFlash('error', 'ID de utilizador inválido');
            $this->redirect('?c=users');
            return;
        }
        
        // Buscar utilizador com relacionamentos
        $user = User::find($id, [
            'include' => ['posts', 'projects']
        ]);
        
        if (!$user) {
            $this->setFlash('error', 'Utilizador não encontrado');
            $this->redirect('?c=users');
            return;
        }
        
        // Estatísticas do utilizador
        $stats = [
            'totalPosts' => count($user->posts),
            'totalProjects' => count($user->projects),
            'memberSince' => $user->created_at
        ];
        
        $this->render('users/show', [
            'user' => $user,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        $this->setFlash('error', 'Erro ao carregar utilizador: ' . $e->getMessage());
        $this->redirect('?c=users');
    }
}
```

## ➕ Create (Criar)

### **1. Create - Formulário de Criação**
```php
/**
 * Mostrar formulário de criação
 */
public function create() {
    // Dados para populate selects, etc.
    $data = [
        'roles' => ['user' => 'Utilizador', 'admin' => 'Administrador'],
        'user' => new User() // Objeto vazio para o formulário
    ];
    
    $this->render('users/create', $data);
}
```

### **2. Store - Salvar Novo**
```php
/**
 * Salvar novo utilizador
 */
public function store() {
    try {
        // Verificar se é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?c=users&a=create');
            return;
        }
        
        // Validar dados de entrada
        $data = $this->validateUserData($_POST);
        
        // Encriptar password
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // Criar utilizador
        $user = new User($data);
        
        if ($user->save()) {
            $this->setFlash('success', 'Utilizador criado com sucesso!');
            $this->redirect('?c=users&a=show&id=' . $user->id);
        } else {
            // Erros de validação
            $errors = $user->errors->full_messages();
            $this->setFlash('error', 'Erro ao criar utilizador: ' . implode(', ', $errors));
            
            // Voltar ao formulário com dados
            $this->render('users/create', [
                'user' => $user,
                'roles' => ['user' => 'Utilizador', 'admin' => 'Administrador'],
                'formData' => $_POST
            ]);
        }
        
    } catch (Exception $e) {
        $this->setFlash('error', 'Erro interno: ' . $e->getMessage());
        $this->redirect('?c=users&a=create');
    }
}

/**
 * Validar dados do utilizador
 */
private function validateUserData($data) {
    $validated = [];
    
    // Nome (obrigatório)
    $validated['name'] = trim($data['name'] ?? '');
    if (empty($validated['name'])) {
        throw new Exception('Nome é obrigatório');
    }
    
    // Email (obrigatório e único)
    $validated['email'] = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    if (!$validated['email']) {
        throw new Exception('Email inválido');
    }
    
    // Verificar se email já existe
    $existingUser = User::first(['conditions' => ['email = ?', $validated['email']]]);
    if ($existingUser) {
        throw new Exception('Email já está em uso');
    }
    
    // Password (obrigatório na criação)
    $validated['password'] = $data['password'] ?? '';
    if (empty($validated['password'])) {
        throw new Exception('Password é obrigatória');
    }
    if (strlen($validated['password']) < 6) {
        throw new Exception('Password deve ter pelo menos 6 caracteres');
    }
    
    // Role (opcional, padrão 'user')
    $validated['role'] = in_array($data['role'] ?? '', ['user', 'admin']) 
                        ? $data['role'] 
                        : 'user';
    
    // Active (opcional, padrão true)
    $validated['active'] = isset($data['active']) ? (bool)$data['active'] : true;
    
    return $validated;
}
```

#### **View de Criação (views/users/create.php)**
```php
<?php $this->layout = 'layout/main'; ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Criar Novo Utilizador</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="?c=users&a=store" id="userForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nome *</label>
                                    <input type="text" 
                                           class="form-control <?= isset($user) && $user->errors->on('name') ? 'is-invalid' : '' ?>" 
                                           id="name" 
                                           name="name" 
                                           value="<?= htmlspecialchars($formData['name'] ?? $user->name ?? '') ?>"
                                           required>
                                    <?php if (isset($user) && $user->errors->on('name')): ?>
                                        <div class="invalid-feedback">
                                            <?= $user->errors->on('name') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" 
                                           class="form-control <?= isset($user) && $user->errors->on('email') ? 'is-invalid' : '' ?>" 
                                           id="email" 
                                           name="email" 
                                           value="<?= htmlspecialchars($formData['email'] ?? $user->email ?? '') ?>"
                                           required>
                                    <?php if (isset($user) && $user->errors->on('email')): ?>
                                        <div class="invalid-feedback">
                                            <?= $user->errors->on('email') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           required>
                                    <div class="form-text">Mínimo 6 caracteres</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Confirmar Password *</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirmation" 
                                           name="password_confirmation" 
                                           required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role">
                                        <?php foreach ($roles as $value => $label): ?>
                                            <option value="<?= $value ?>" 
                                                    <?= ($formData['role'] ?? $user->role ?? 'user') === $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="active" 
                                               name="active" 
                                               value="1"
                                               <?= ($formData['active'] ?? $user->active ?? true) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="active">
                                            Utilizador Ativo
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="?c=users" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Criar Utilizador
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validação de password
document.getElementById('userForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmation = document.getElementById('password_confirmation').value;
    
    if (password !== confirmation) {
        e.preventDefault();
        alert('As passwords não coincidem!');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        alert('A password deve ter pelo menos 6 caracteres!');
        return false;
    }
});
</script>
```

## ✏️ Update (Atualizar)

### **1. Edit - Formulário de Edição**
```php
/**
 * Mostrar formulário de edição
 */
public function edit($id = null) {
    try {
        $id = $id ?? $_GET['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            $this->setFlash('error', 'ID de utilizador inválido');
            $this->redirect('?c=users');
            return;
        }
        
        $user = User::find($id);
        if (!$user) {
            $this->setFlash('error', 'Utilizador não encontrado');
            $this->redirect('?c=users');
            return;
        }
        
        $data = [
            'user' => $user,
            'roles' => ['user' => 'Utilizador', 'admin' => 'Administrador']
        ];
        
        $this->render('users/edit', $data);
        
    } catch (Exception $e) {
        $this->setFlash('error', 'Erro ao carregar utilizador: ' . $e->getMessage());
        $this->redirect('?c=users');
    }
}
```

### **2. Update - Atualizar Dados**
```php
/**
 * Atualizar utilizador existente
 */
public function update($id = null) {
    try {
        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('?c=users');
            return;
        }
        
        $id = $id ?? $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            $this->setFlash('error', 'ID de utilizador inválido');
            $this->redirect('?c=users');
            return;
        }
        
        // Buscar utilizador
        $user = User::find($id);
        if (!$user) {
            $this->setFlash('error', 'Utilizador não encontrado');
            $this->redirect('?c=users');
            return;
        }
        
        // Validar dados (sem password obrigatória)
        $data = $this->validateUserDataForUpdate($_POST, $user);
        
        // Atualizar utilizador
        $user->update_attributes($data);
        
        if ($user->save()) {
            $this->setFlash('success', 'Utilizador atualizado com sucesso!');
            $this->redirect('?c=users&a=show&id=' . $user->id);
        } else {
            $errors = $user->errors->full_messages();
            $this->setFlash('error', 'Erro ao atualizar: ' . implode(', ', $errors));
            $this->redirect('?c=users&a=edit&id=' . $id);
        }
        
    } catch (Exception $e) {
        $this->setFlash('error', 'Erro interno: ' . $e->getMessage());
        $this->redirect('?c=users&a=edit&id=' . ($id ?? ''));
    }
}

/**
 * Validar dados para atualização
 */
private function validateUserDataForUpdate($data, $currentUser) {
    $validated = [];
    
    // Nome
    $validated['name'] = trim($data['name'] ?? '');
    if (empty($validated['name'])) {
        throw new Exception('Nome é obrigatório');
    }
    
    // Email
    $validated['email'] = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    if (!$validated['email']) {
        throw new Exception('Email inválido');
    }
    
    // Verificar se email já existe (exceto o atual)
    $existingUser = User::first([
        'conditions' => ['email = ? AND id != ?', $validated['email'], $currentUser->id]
    ]);
    if ($existingUser) {
        throw new Exception('Email já está em uso');
    }
    
    // Password (opcional na atualização)
    if (!empty($data['password'])) {
        if (strlen($data['password']) < 6) {
            throw new Exception('Password deve ter pelo menos 6 caracteres');
        }
        $validated['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    
    // Role
    $validated['role'] = in_array($data['role'] ?? '', ['user', 'admin']) 
                        ? $data['role'] 
                        : $currentUser->role;
    
    // Active
    $validated['active'] = isset($data['active']) ? (bool)$data['active'] : false;
    
    return $validated;
}
```

## 🗑️ Delete (Eliminar)

### **Delete - Eliminar Utilizador**
```php
/**
 * Eliminar utilizador
 */
public function delete($id = null) {
    try {
        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlash('error', 'Método não permitido');
            $this->redirect('?c=users');
            return;
        }
        
        $id = $id ?? $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            $this->setFlash('error', 'ID de utilizador inválido');
            $this->redirect('?c=users');
            return;
        }
        
        // Buscar utilizador
        $user = User::find($id);
        if (!$user) {
            $this->setFlash('error', 'Utilizador não encontrado');
            $this->redirect('?c=users');
            return;
        }
        
        // Verificar se não é o utilizador atual
        if (Auth::getCurrentUser() && Auth::getCurrentUser()->id == $user->id) {
            $this->setFlash('error', 'Não pode eliminar a sua própria conta');
            $this->redirect('?c=users');
            return;
        }
        
        // Verificar dependências
        $postsCount = count($user->posts ?? []);
        $projectsCount = count($user->projects ?? []);
        
        if ($postsCount > 0 || $projectsCount > 0) {
            $this->setFlash('error', 
                "Não é possível eliminar utilizador com $postsCount posts e $projectsCount projetos. " .
                "Elimine primeiro os dados associados."
            );
            $this->redirect('?c=users&a=show&id=' . $id);
            return;
        }
        
        // Eliminar utilizador
        $userName = $user->name;
        if ($user->delete()) {
            $this->setFlash('success', "Utilizador '$userName' eliminado com sucesso!");
        } else {
            $this->setFlash('error', 'Erro ao eliminar utilizador');
        }
        
        $this->redirect('?c=users');
        
    } catch (Exception $e) {
        $this->setFlash('error', 'Erro interno: ' . $e->getMessage());
        $this->redirect('?c=users');
    }
}
```

## 🔒 Segurança e Validações

### **Middleware de Autenticação**
```php
class UserController extends Controller {
    
    public function __construct() {
        parent::__construct();
        
        // Requer autenticação para todas as ações
        $this->requireAuth();
        
        // Verificar permissões específicas
        $adminActions = ['create', 'store', 'edit', 'update', 'delete'];
        if (in_array($this->getCurrentAction(), $adminActions)) {
            $this->requireRole('admin');
        }
    }
    
    private function getCurrentAction() {
        return $_GET['a'] ?? 'index';
    }
    
    private function requireRole($role) {
        $currentUser = Auth::getCurrentUser();
        if (!$currentUser || $currentUser->role !== $role) {
            $this->setFlash('error', 'Sem permissões suficientes');
            $this->redirect('?c=home');
            exit;
        }
    }
}
```

### **Validação CSRF**
```php
// No formulário
<input type="hidden" name="csrf_token" value="<?= $this->generateCSRFToken() ?>">

// No controller
private function validateCSRFToken() {
    $token = $_POST['csrf_token'] ?? '';
    if (!$this->verifyCSRFToken($token)) {
        throw new Exception('Token CSRF inválido');
    }
}
```

## 📋 Exemplo de Controller CRUD Completo

```php
<?php
// controllers/UserController.php

class UserController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth(); // Requer autenticação
    }
    
    // [READ] Listar utilizadores
    public function index() { /* código do index */ }
    
    // [READ] Mostrar utilizador
    public function show($id = null) { /* código do show */ }
    
    // [CREATE] Formulário criação
    public function create() { /* código do create */ }
    
    // [CREATE] Salvar novo
    public function store() { /* código do store */ }
    
    // [UPDATE] Formulário edição
    public function edit($id = null) { /* código do edit */ }
    
    // [UPDATE] Atualizar
    public function update($id = null) { /* código do update */ }
    
    // [DELETE] Eliminar
    public function delete($id = null) { /* código do delete */ }
    
    // Métodos auxiliares privados
    private function validateUserData($data) { /* validação */ }
    private function validateUserDataForUpdate($data, $user) { /* validação */ }
}
```

---

Este guia fornece uma base sólida para implementar operações CRUD completas. Lembre-se sempre de validar dados de entrada, tratar erros adequadamente e implementar medidas de segurança apropriadas.
