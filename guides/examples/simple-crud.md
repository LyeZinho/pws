# Exemplos de Código - Sistema CRUD Simples

## 1. Exemplo Completo: Gestão de Tarefas (Tasks)

### 1.1 Criar a Tabela

```sql
-- Script SQL para tabela de tarefas
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    user_id INT NOT NULL,
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
);
```

### 1.2 Model Task

```php
<?php
// models/Task.php

/**
 * Model Task - Representa tarefas do usuário
 * 
 * Exemplo simples de como criar um model com ActiveRecord
 */
class Task extends ActiveRecord\Model
{
    // Relacionamentos
    static $belongs_to = [
        ['user'] // Uma tarefa pertence a um usuário
    ];
    
    // Validações
    static $validates_presence_of = [
        ['title', 'message' => 'Título é obrigatório'],
        ['user_id', 'message' => 'Usuário é obrigatório']
    ];
    
    static $validates_length_of = [
        ['title', 'minimum' => 3, 'maximum' => 255, 'message' => 'Título deve ter entre 3 e 255 caracteres']
    ];
    
    static $validates_inclusion_of = [
        ['status', 'in' => ['pending', 'in_progress', 'completed'], 'message' => 'Status inválido'],
        ['priority', 'in' => ['low', 'medium', 'high'], 'message' => 'Prioridade inválida']
    ];
    
    /**
     * Executar antes de salvar
     */
    public function before_save()
    {
        if (!$this->created_at) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        $this->updated_at = date('Y-m-d H:i:s');
    }
    
    /**
     * Marcar tarefa como completa
     */
    public function markAsCompleted()
    {
        $this->status = 'completed';
        return $this->save();
    }
    
    /**
     * Verificar se tarefa está atrasada
     */
    public function isOverdue()
    {
        if (!$this->due_date || $this->status === 'completed') {
            return false;
        }
        
        return strtotime($this->due_date) < strtotime(date('Y-m-d'));
    }
    
    /**
     * Obter cor da prioridade para a interface
     */
    public function getPriorityColor()
    {
        switch ($this->priority) {
            case 'high': return 'danger';
            case 'medium': return 'warning';
            case 'low': return 'success';
            default: return 'secondary';
        }
    }
    
    /**
     * Obter cor do status para a interface
     */
    public function getStatusColor()
    {
        switch ($this->status) {
            case 'completed': return 'success';
            case 'in_progress': return 'primary';
            case 'pending': return 'secondary';
            default: return 'secondary';
        }
    }
    
    /**
     * Buscar tarefas do usuário por status
     */
    public static function findByUserAndStatus($userId, $status = null)
    {
        $conditions = ['user_id = ?', $userId];
        
        if ($status) {
            $conditions = ['user_id = ? AND status = ?', $userId, $status];
        }
        
        return self::find('all', [
            'conditions' => $conditions,
            'order' => 'due_date ASC, priority DESC, created_at DESC'
        ]);
    }
    
    /**
     * Contar tarefas por status para um usuário
     */
    public static function countByStatus($userId)
    {
        return [
            'pending' => self::count(['conditions' => ['user_id = ? AND status = ?', $userId, 'pending']]),
            'in_progress' => self::count(['conditions' => ['user_id = ? AND status = ?', $userId, 'in_progress']]),
            'completed' => self::count(['conditions' => ['user_id = ? AND status = ?', $userId, 'completed']])
        ];
    }
}
```

### 1.3 Controller TaskController

```php
<?php
// controllers/TaskController.php

/**
 * TaskController - Gestão de tarefas
 * 
 * Exemplo completo de controller CRUD
 */
class TaskController extends Controller
{
    /**
     * Construtor - verificar autenticação
     */
    public function __construct()
    {
        parent::__construct();
        
        // Verificar se o usuário está logado
        if (!Auth::check()) {
            $this->redirect('?c=auth&a=login&error=' . urlencode('É necessário fazer login'));
            exit;
        }
    }
    
    /**
     * Listar tarefas do usuário
     * URL: ?c=tasks&a=index
     * Métodos GET aceites: ?status=pending, ?priority=high, etc.
     */
    public function index()
    {
        try {
            $userId = Auth::user()->id;
            
            // Filtros da URL
            $statusFilter = $_GET['status'] ?? null;
            $priorityFilter = $_GET['priority'] ?? null;
            
            // Buscar tarefas
            if ($statusFilter) {
                $tasks = Task::findByUserAndStatus($userId, $statusFilter);
            } else {
                $tasks = Task::findByUserAndStatus($userId);
            }
            
            // Aplicar filtro de prioridade se especificado
            if ($priorityFilter && in_array($priorityFilter, ['low', 'medium', 'high'])) {
                $tasks = array_filter($tasks, function($task) use ($priorityFilter) {
                    return $task->priority === $priorityFilter;
                });
            }
            
            // Estatísticas
            $stats = Task::countByStatus($userId);
            
            // Dados para a view
            $data = [
                'tasks' => $tasks,
                'stats' => $stats,
                'currentStatus' => $statusFilter,
                'currentPriority' => $priorityFilter,
                'title' => 'Minhas Tarefas'
            ];
            
            $this->view('tasks/index', $data);
            
        } catch (Exception $e) {
            error_log("Erro ao listar tarefas: " . $e->getMessage());
            $this->redirect('?c=home&a=index&error=' . urlencode('Erro ao carregar tarefas'));
        }
    }
    
    /**
     * Processar criação de tarefa
     * URL: ?c=tasks&a=store (POST)
     */
    public function store()
    {
        try {
            // Capturar dados do formulário POST
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = $_POST['status'] ?? 'pending';
            $priority = $_POST['priority'] ?? 'medium';
            $dueDate = $_POST['due_date'] ?? null;
            
            // Criar nova tarefa
            $task = new Task();
            $task->title = $title;
            $task->description = $description;
            $task->status = $status;
            $task->priority = $priority;
            $task->user_id = Auth::user()->id;
            $task->due_date = $dueDate ?: null;
            
            // Tentar salvar
            if ($task->save()) {
                // Sucesso - redirecionar
                $this->redirect('?c=tasks&a=index&success=' . urlencode('Tarefa criada com sucesso'));
            } else {
                // Erros de validação do ActiveRecord
                $errors = [];
                foreach ($task->errors->full_messages() as $error) {
                    $errors[] = $error;
                }
                
                // Mostrar formulário novamente com erros
                $data = [
                    'task' => $task,
                    'title' => 'Nova Tarefa',
                    'errors' => $errors,
                    'old' => $_POST // Preservar dados preenchidos
                ];
                
                $this->view('tasks/create', $data);
            }
            
        } catch (Exception $e) {
            error_log("Erro ao criar tarefa: " . $e->getMessage());
            
            $data = [
                'task' => new Task(),
                'title' => 'Nova Tarefa',
                'errors' => ['Erro interno do servidor. Tente novamente.'],
                'old' => $_POST
            ];
            
            $this->view('tasks/create', $data);
        }
    }
}
```

### 1.4 Rotas

```php
// Adicionar em routes.php
'tasks' => [
    'index' => ['GET', 'TaskController', 'index'],           // Listar tarefas
    'show' => ['GET', 'TaskController', 'show'],             // Mostrar tarefa
    'create' => ['GET', 'TaskController', 'create'],         // Formulário criar
    'store' => ['POST', 'TaskController', 'store'],          // Salvar nova tarefa
    'edit' => ['GET', 'TaskController', 'edit'],             // Formulário editar
    'update' => ['POST', 'TaskController', 'update'],        // Atualizar tarefa
    'delete' => ['GET', 'TaskController', 'delete'],         // Eliminar tarefa
    'change_status' => ['POST', 'TaskController', 'change_status'], // AJAX: mudar status
],
```

### 1.5 View - Formulário de Criação

```php
<!-- views/tasks/create.php -->
<?php include_once __DIR__ . '/../layout/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Nova Tarefa</h3>
                </div>
                <div class="card-body">
                    <!-- Mostrar erros de validação -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h6>Corrija os seguintes erros:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Formulário -->
                    <form method="POST" action="?c=tasks&a=store" novalidate>
                        <div class="mb-3">
                            <label for="title" class="form-label">Título da Tarefa *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title" 
                                   name="title" 
                                   value="<?= htmlspecialchars($old['title'] ?? $task->title ?? '') ?>"
                                   required>
                            <div class="form-text">Título claro e descritivo da tarefa</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="4"><?= htmlspecialchars($old['description'] ?? $task->description ?? '') ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="priority" class="form-label">Prioridade</label>
                                <select class="form-select" id="priority" name="priority">
                                    <?php 
                                    $selectedPriority = $old['priority'] ?? $task->priority ?? 'medium';
                                    ?>
                                    <option value="low" <?= $selectedPriority === 'low' ? 'selected' : '' ?>>
                                        Baixa
                                    </option>
                                    <option value="medium" <?= $selectedPriority === 'medium' ? 'selected' : '' ?>>
                                        Média
                                    </option>
                                    <option value="high" <?= $selectedPriority === 'high' ? 'selected' : '' ?>>
                                        Alta
                                    </option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">Prazo</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="due_date" 
                                       name="due_date" 
                                       value="<?= htmlspecialchars($old['due_date'] ?? $task->due_date ?? '') ?>"
                                       min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                            <a href="?c=tasks&a=index" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Criar Tarefa</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>
```

## 2. Exemplo: Como Fazer Requisições GET/POST

### 2.1 Requisições GET - Passagem de Dados via URL

```php
// Controller recebe dados via $_GET
public function show()
{
    // Capturar parâmetro da URL: ?c=tasks&a=show&id=123
    $id = $_GET['id'] ?? null;
    
    // Validar
    if (!$id || !is_numeric($id)) {
        $this->redirect('?c=tasks&a=index&error=' . urlencode('ID inválido'));
        return;
    }
    
    // Buscar dados
    $task = Task::find($id);
    
    // Enviar para view
    $this->view('tasks/show', ['task' => $task]);
}

// Links na view enviam dados via GET
echo '<a href="?c=tasks&a=show&id=' . $task->id . '">Ver Tarefa</a>';
echo '<a href="?c=tasks&a=index&status=completed">Ver Completas</a>';
echo '<a href="?c=tasks&a=edit&id=' . $task->id . '&from=dashboard">Editar</a>';
```

### 2.2 Requisições POST - Formulários

```php
// Formulário HTML envia dados via POST
?>
<form method="POST" action="?c=tasks&a=store">
    <input type="text" name="title" value="Minha tarefa" required>
    <textarea name="description">Descrição da tarefa</textarea>
    <select name="priority">
        <option value="low">Baixa</option>
        <option value="medium">Média</option>
        <option value="high">Alta</option>
    </select>
    <button type="submit">Criar Tarefa</button>
</form>
<?php

// Controller recebe dados via $_POST
public function store()
{
    // Capturar dados do formulário
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    
    // Validar dados
    if (empty($title)) {
        $this->redirect('?c=tasks&a=create&error=' . urlencode('Título é obrigatório'));
        return;
    }
    
    // Processar dados
    $task = new Task();
    $task->title = $title;
    $task->description = $description;
    $task->priority = $priority;
    $task->user_id = Auth::user()->id;
    
    if ($task->save()) {
        // Sucesso
        $this->redirect('?c=tasks&a=index&success=' . urlencode('Tarefa criada'));
    } else {
        // Erro
        $this->redirect('?c=tasks&a=create&error=' . urlencode('Erro ao criar tarefa'));
    }
}
```

### 2.3 AJAX - Requisições Assíncronas

```javascript
// JavaScript para enviar dados via AJAX
function createTaskAjax() {
    const formData = new FormData();
    formData.append('title', document.getElementById('title').value);
    formData.append('description', document.getElementById('description').value);
    formData.append('priority', document.getElementById('priority').value);
    
    fetch('?c=tasks&a=ajax_store', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Tarefa criada com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro de conexão');
    });
}
```

```php
// Controller responde com JSON para AJAX
public function ajax_store()
{
    header('Content-Type: application/json');
    
    try {
        $title = $_POST['title'] ?? '';
        
        if (empty($title)) {
            throw new Exception('Título é obrigatório');
        }
        
        $task = new Task();
        $task->title = $title;
        $task->description = $_POST['description'] ?? '';
        $task->priority = $_POST['priority'] ?? 'medium';
        $task->user_id = Auth::user()->id;
        
        if ($task->save()) {
            echo json_encode([
                'success' => true,
                'message' => 'Tarefa criada com sucesso',
                'task_id' => $task->id
            ]);
        } else {
            throw new Exception('Erro ao salvar tarefa');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
```

## 3. Como Usar o ActiveRecord ORM

### 3.1 Operações Básicas

```php
// Criar novo registro
$task = new Task();
$task->title = 'Nova Tarefa';
$task->description = 'Descrição';
$task->user_id = 1;
$task->save(); // INSERT

// Buscar por ID
$task = Task::find(1); // SELECT * FROM tasks WHERE id = 1

// Buscar todos
$tasks = Task::all(); // SELECT * FROM tasks

// Buscar com condições
$tasks = Task::find('all', [
    'conditions' => ['status = ?', 'pending'],
    'order' => 'created_at DESC',
    'limit' => 10
]);

// Buscar um registro
$task = Task::find('first', [
    'conditions' => ['user_id = ? AND status = ?', 1, 'pending']
]);

// Atualizar
$task = Task::find(1);
$task->title = 'Título Atualizado';
$task->save(); // UPDATE

// Eliminar
$task = Task::find(1);
$task->delete(); // DELETE

// Contar registros
$count = Task::count(); // SELECT COUNT(*) FROM tasks
$count = Task::count(['conditions' => ['status = ?', 'pending']]);
```

### 3.2 Relacionamentos

```php
// No Model
class Task extends ActiveRecord\Model
{
    static $belongs_to = [
        ['user'] // task pertence a user
    ];
}

class User extends ActiveRecord\Model
{
    static $has_many = [
        ['tasks'] // user tem muitas tasks
    ];
}

// Usar relacionamentos
$task = Task::find(1, ['include' => ['user']]);
echo $task->user->name; // Acesso direto ao usuário

$user = User::find(1, ['include' => ['tasks']]);
foreach ($user->tasks as $task) {
    echo $task->title;
}

// Criar com relacionamento
$user = User::find(1);
$task = $user->tasks->build([
    'title' => 'Nova Tarefa',
    'description' => 'Descrição'
]);
$task->save();
```

### 3.3 Validações

```php
class Task extends ActiveRecord\Model
{
    // Campo obrigatório
    static $validates_presence_of = [
        ['title', 'message' => 'Título é obrigatório']
    ];
    
    // Tamanho do campo
    static $validates_length_of = [
        ['title', 'minimum' => 3, 'maximum' => 255]
    ];
    
    // Valores permitidos
    static $validates_inclusion_of = [
        ['status', 'in' => ['pending', 'in_progress', 'completed']]
    ];
    
    // Unicidade
    static $validates_uniqueness_of = [
        ['title', 'scope' => 'user_id', 'message' => 'Você já tem uma tarefa com este título']
    ];
    
    // Validação customizada
    static $validates_format_of = [
        ['email', 'with' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/']
    ];
}

// Verificar se é válido
$task = new Task();
$task->title = '';

if ($task->is_valid()) {
    $task->save();
} else {
    foreach ($task->errors->full_messages() as $error) {
        echo $error . "\n";
    }
}
```

### 3.4 Callbacks (Hooks)

```php
class Task extends ActiveRecord\Model
{
    // Antes de salvar (INSERT ou UPDATE)
    public function before_save()
    {
        $this->updated_at = date('Y-m-d H:i:s');
    }
    
    // Antes de criar (apenas INSERT)
    public function before_create()
    {
        $this->created_at = date('Y-m-d H:i:s');
    }
    
    // Após salvar
    public function after_save()
    {
        // Log da operação
        error_log("Tarefa {$this->id} foi salva");
    }
    
    // Antes de eliminar
    public function before_destroy()
    {
        // Limpar dados relacionados
        $this->comments->delete_all();
    }
}
```

Este guia mostra exemplos práticos de como:
- Criar CRUDs completos
- Fazer requisições GET/POST
- Usar o ActiveRecord ORM
- Implementar validações
- Trabalhar com relacionamentos
- Usar callbacks do sistema

Use estes exemplos como base para suas implementações!
