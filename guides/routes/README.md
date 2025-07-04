# Guia de Rotas - Sistema GEstufas

## Introdução

O sistema de rotas do GEstufas é responsável por mapear URLs para controllers e actions específicos. Este guia explica como funcionam as rotas, como criar novas rotas e como utilizar o sistema de roteamento.

## Estrutura do Sistema de Rotas

### Arquivos Principais

- **`routes.php`** - Configuração de todas as rotas
- **`framework/Router.php`** - Classe principal do roteador
- **`index.php`** - Ponto de entrada que carrega o roteador

### Como Funciona

O sistema utiliza parâmetros GET para determinar qual controller e action executar:
- `?c=controller` - Define o controller
- `?a=action` - Define a action (método)

```
URL: index.php?c=users&a=create
Executa: UserController::create()
```

## Configuração de Rotas

### Formato Básico

Todas as rotas são definidas no arquivo `routes.php` usando este formato:

```php
'controllerName' => [
    'actionName' => ['MÉTODO', 'ControllerClass', 'methodName'],
]
```

### Exemplo de Rota

```php
'users' => [
    'index' => ['GET', 'UserController', 'index'],           // Listar usuários
    'create' => ['GET', 'UserController', 'create'],         // Formulário criar
    'store' => ['POST', 'UserController', 'store'],          // Salvar novo usuário
    'edit' => ['GET', 'UserController', 'edit'],             // Formulário editar
    'update' => ['POST', 'UserController', 'update'],        // Atualizar usuário
    'delete' => ['GET', 'UserController', 'delete'],         // Eliminar usuário
]
```

### Métodos HTTP Suportados

```php
// Apenas GET
'show' => ['GET', 'UserController', 'show'],

// Apenas POST
'store' => ['POST', 'UserController', 'store'],

// GET ou POST
'login' => ['GET|POST', 'AuthController', 'login'],

// Múltiplos métodos
'api' => ['GET|POST|PUT|DELETE', 'ApiController', 'endpoint'],
```

## Exemplos Práticos

### 1. CRUD Completo de Usuários

```php
'users' => [
    // Listar todos os usuários
    // URL: ?c=users&a=index
    'index' => ['GET', 'UserController', 'index'],
    
    // Mostrar usuário específico
    // URL: ?c=users&a=show&id=1
    'show' => ['GET', 'UserController', 'show'],
    
    // Formulário para criar usuário
    // URL: ?c=users&a=create
    'create' => ['GET', 'UserController', 'create'],
    
    // Processar criação (form POST)
    // URL: ?c=users&a=store (POST)
    'store' => ['POST', 'UserController', 'store'],
    
    // Formulário para editar usuário
    // URL: ?c=users&a=edit&id=1
    'edit' => ['GET', 'UserController', 'edit'],
    
    // Processar edição (form POST)
    // URL: ?c=users&a=update&id=1 (POST)
    'update' => ['POST', 'UserController', 'update'],
    
    // Eliminar usuário
    // URL: ?c=users&a=delete&id=1
    'delete' => ['GET', 'UserController', 'delete'],
],
```

### 2. Sistema de Autenticação

```php
'auth' => [
    // Página de login (GET) ou processar login (POST)
    'login' => ['GET|POST', 'AuthController', 'login'],
    
    // Página de registo (GET) ou processar registo (POST)
    'register' => ['GET|POST', 'AuthController', 'register'],
    
    // Logout do sistema
    'logout' => ['GET', 'AuthController', 'logout'],
],
```

### 3. Sistema de Posts com Comentários

```php
'posts' => [
    'index' => ['GET', 'PostController', 'index'],
    'show' => ['GET', 'PostController', 'show'],
    'create' => ['GET', 'PostController', 'create'],
    'store' => ['POST', 'PostController', 'store'],
    'edit' => ['GET', 'PostController', 'edit'],
    'update' => ['POST', 'PostController', 'update'],
    'delete' => ['GET', 'PostController', 'delete'],
    
    // Adicionar comentário (apenas POST)
    'comment' => ['POST', 'PostController', 'comment'],
],
```

## Criando Novas Rotas

### Passo 1: Definir a Rota

Adicionar no arquivo `routes.php`:

```php
'produtos' => [
    'index' => ['GET', 'ProductController', 'index'],
    'create' => ['GET', 'ProductController', 'create'],
    'store' => ['POST', 'ProductController', 'store'],
    'show' => ['GET', 'ProductController', 'show'],
    'edit' => ['GET', 'ProductController', 'edit'],
    'update' => ['POST', 'ProductController', 'update'],
    'delete' => ['GET', 'ProductController', 'delete'],
],
```

### Passo 2: Criar o Controller

```php
<?php
/**
 * Controller para gestão de produtos
 */
class ProductController extends Controller
{
    /**
     * Listar todos os produtos
     * URL: ?c=produtos&a=index
     */
    public function index()
    {
        // Buscar produtos da base de dados
        $products = Product::all();
        
        // Carregar view com os produtos
        $this->view('products/index', ['products' => $products]);
    }
    
    /**
     * Mostrar formulário para criar produto
     * URL: ?c=produtos&a=create
     */
    public function create()
    {
        $this->view('products/create');
    }
    
    /**
     * Processar criação de produto
     * URL: ?c=produtos&a=store (POST)
     */
    public function store()
    {
        // Validar dados do formulário
        if (empty($_POST['name']) || empty($_POST['price'])) {
            $this->redirect('?c=produtos&a=create&error=Dados inválidos');
            return;
        }
        
        // Criar novo produto
        $product = new Product();
        $product->name = $_POST['name'];
        $product->price = $_POST['price'];
        $product->description = $_POST['description'] ?? '';
        
        if ($product->save()) {
            $this->redirect('?c=produtos&a=index&success=Produto criado');
        } else {
            $this->redirect('?c=produtos&a=create&error=Erro ao criar produto');
        }
    }
}
```

### Passo 3: Criar as Views

```php
<!-- views/products/index.php -->
<div class="container">
    <h1>Produtos</h1>
    
    <a href="?c=produtos&a=create" class="btn btn-primary">Novo Produto</a>
    
    <div class="row">
        <?php foreach ($products as $product): ?>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5><?= htmlspecialchars($product->name) ?></h5>
                        <p>€<?= number_format($product->price, 2) ?></p>
                        
                        <a href="?c=produtos&a=show&id=<?= $product->id ?>" class="btn btn-info">Ver</a>
                        <a href="?c=produtos&a=edit&id=<?= $product->id ?>" class="btn btn-warning">Editar</a>
                        <a href="?c=produtos&a=delete&id=<?= $product->id ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Eliminar produto?')">Eliminar</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
```

## Passagem de Parâmetros

### Via GET (URL)

```php
// URL: ?c=users&a=show&id=123
public function show()
{
    $id = $_GET['id'] ?? null;
    if (!$id) {
        $this->redirect('?c=users&a=index&error=ID necessário');
        return;
    }
    
    $user = User::find($id);
    $this->view('users/show', ['user' => $user]);
}
```

### Via POST (Formulários)

```php
// Formulário HTML
<form method="POST" action="?c=users&a=store">
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <button type="submit">Criar Usuário</button>
</form>

// Controller
public function store()
{
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    
    // Validar e salvar...
}
```

## Redirecionamentos

### Redirecionamento Simples

```php
// Redirecionar para outra página
$this->redirect('?c=users&a=index');
```

### Redirecionamento com Mensagens

```php
// Sucesso
$this->redirect('?c=users&a=index&success=Usuário criado com sucesso');

// Erro
$this->redirect('?c=users&a=create&error=Dados inválidos');

// Na view, capturar as mensagens:
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>
```

## Proteção de Rotas

### Verificação de Autenticação

```php
class UserController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Verificar se o usuário está logado
        if (!Auth::check()) {
            $this->redirect('?c=auth&a=login&error=Login necessário');
            exit;
        }
    }
    
    public function index()
    {
        // Apenas usuários logados chegam aqui
        $users = User::all();
        $this->view('users/index', ['users' => $users]);
    }
}
```

### Verificação de Permissões

```php
public function delete()
{
    // Verificar se é admin
    if (!Auth::user()->isAdmin()) {
        $this->redirect('?c=users&a=index&error=Sem permissões');
        return;
    }
    
    $id = $_GET['id'] ?? null;
    // Processar eliminação...
}
```

## Rotas de API

### Configuração

```php
'api' => [
    'users' => ['GET', 'ApiController', 'users'],
    'posts' => ['GET', 'ApiController', 'posts'],
    'user' => ['GET|POST|PUT|DELETE', 'ApiController', 'user'],
],
```

### Controller de API

```php
class ApiController extends Controller
{
    public function users()
    {
        // Definir cabeçalho JSON
        header('Content-Type: application/json');
        
        // Buscar usuários
        $users = User::all();
        
        // Retornar JSON
        echo json_encode([
            'success' => true,
            'data' => $users,
            'count' => count($users)
        ]);
    }
    
    public function user()
    {
        header('Content-Type: application/json');
        
        $method = $_SERVER['REQUEST_METHOD'];
        $id = $_GET['id'] ?? null;
        
        switch ($method) {
            case 'GET':
                // Buscar usuário
                $user = User::find($id);
                echo json_encode(['data' => $user]);
                break;
                
            case 'POST':
                // Criar usuário
                $data = json_decode(file_get_contents('php://input'), true);
                $user = new User($data);
                $user->save();
                echo json_encode(['success' => true, 'id' => $user->id]);
                break;
                
            case 'PUT':
                // Atualizar usuário
                $data = json_decode(file_get_contents('php://input'), true);
                $user = User::find($id);
                $user->update_attributes($data);
                echo json_encode(['success' => true]);
                break;
                
            case 'DELETE':
                // Eliminar usuário
                $user = User::find($id);
                $user->delete();
                echo json_encode(['success' => true]);
                break;
        }
    }
}
```

## Debugging de Rotas

### Ativar Debug

No arquivo `config/config.php`:

```php
define('APP_DEBUG', true);
```

### Logs de Debug

O router automaticamente gera logs quando o debug está ativo:

```
Router Debug - Controller: users, Action: index, Method: GET
Router Debug - Route found: GET -> UserController::index
```

### Debug Manual

```php
// No controller
public function index()
{
    if (defined('APP_DEBUG') && APP_DEBUG) {
        error_log("UserController::index executado");
        error_log("GET parameters: " . print_r($_GET, true));
        error_log("POST parameters: " . print_r($_POST, true));
    }
    
    // Lógica do método...
}
```

## Melhores Práticas

### 1. Nomenclatura Consistente

```php
// Usar nomes descritivos e padrão REST
'users' => [
    'index' => ['GET', 'UserController', 'index'],      // Listar
    'show' => ['GET', 'UserController', 'show'],        // Mostrar
    'create' => ['GET', 'UserController', 'create'],    // Formulário criar
    'store' => ['POST', 'UserController', 'store'],     // Salvar
    'edit' => ['GET', 'UserController', 'edit'],        // Formulário editar
    'update' => ['POST', 'UserController', 'update'],   // Atualizar
    'delete' => ['GET', 'UserController', 'delete'],    // Eliminar
],
```

### 2. Organização por Recursos

```php
// Agrupar rotas relacionadas
'users' => [...],      // Gestão de usuários
'posts' => [...],      // Gestão de posts
'projects' => [...],   // Gestão de projetos
'auth' => [...],       // Autenticação
'profile' => [...],    // Perfil do usuário
```

### 3. Validação de Parâmetros

```php
public function show()
{
    $id = $_GET['id'] ?? null;
    
    // Validar se ID foi fornecido
    if (!$id) {
        $this->redirect('?c=users&a=index&error=ID necessário');
        return;
    }
    
    // Validar se ID é numérico
    if (!is_numeric($id)) {
        $this->redirect('?c=users&a=index&error=ID inválido');
        return;
    }
    
    // Buscar usuário
    $user = User::find($id);
    if (!$user) {
        $this->redirect('?c=users&a=index&error=Usuário não encontrado');
        return;
    }
    
    $this->view('users/show', ['user' => $user]);
}
```

### 4. Tratamento de Erros

```php
public function store()
{
    try {
        // Validação
        if (empty($_POST['name'])) {
            throw new Exception('Nome é obrigatório');
        }
        
        // Criação
        $user = new User($_POST);
        if (!$user->save()) {
            throw new Exception('Erro ao salvar usuário');
        }
        
        $this->redirect('?c=users&a=index&success=Usuário criado');
        
    } catch (Exception $e) {
        error_log("Erro ao criar usuário: " . $e->getMessage());
        $this->redirect('?c=users&a=create&error=' . urlencode($e->getMessage()));
    }
}
```

### 5. URLs Amigáveis (Opcional)

Para implementar URLs amigáveis, pode usar `.htaccess`:

```apache
# .htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^/]+)/([^/]+)/?$ index.php?c=$1&a=$2 [QSA,L]
RewriteRule ^([^/]+)/?$ index.php?c=$1&a=index [QSA,L]
```

Permitindo URLs como:
- `/users` → `?c=users&a=index`
- `/users/create` → `?c=users&a=create`
- `/posts/show?id=1` → `?c=posts&a=show&id=1`

## Resolução de Problemas

### Rota não encontrada (404)

1. Verificar se a rota está definida em `routes.php`
2. Verificar se os nomes estão corretos (case-sensitive)
3. Verificar se o controller existe
4. Verificar se o método existe no controller

### Método não permitido (405)

1. Verificar se o método HTTP está correto na rota
2. Verificar se o formulário está usando o método correto (GET/POST)

### Controller não encontrado

1. Verificar se a classe do controller existe
2. Verificar se o arquivo está na pasta correta
3. Verificar se o autoload está funcionando

### Parâmetros não chegam

1. Verificar se está usando $_GET ou $_POST corretamente
2. Verificar se os nomes dos campos estão corretos
3. Usar `print_r($_GET)` e `print_r($_POST)` para debug

## Conclusão

O sistema de rotas do GEstufas é simples mas poderoso, permitindo mapear URLs para controllers de forma clara e organizada. Siga as convenções REST e as melhores práticas apresentadas para manter o código limpo e fácil de manter.

Para mais exemplos, consulte os controllers existentes em `controllers/` e as rotas em `routes.php`.
