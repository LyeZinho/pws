# Guia MVC Básico - Implementação do Zero

## Índice
- [Introdução ao Padrão MVC](#introdução-ao-padrão-mvc)
- [Estrutura de Arquivos](#estrutura-de-arquivos)
- [Implementação do Autoloader](#implementação-do-autoloader)
- [Criando o Router](#criando-o-router)
- [Implementando Controllers](#implementando-controllers)
- [Criando Views](#criando-views)
- [Implementando Models](#implementando-models)
- [Setup do Ambiente](#setup-do-ambiente)
- [Exemplo Completo](#exemplo-completo)
- [Resolução de Problemas](#resolução-de-problemas)

## Introdução ao Padrão MVC

O padrão **Model-View-Controller (MVC)** é uma arquitetura de software que separa a aplicação em três componentes principais:

- **Model**: Gerencia dados e lógica de negócio
- **View**: Interface do usuário e apresentação
- **Controller**: Coordena interações entre Model e View

### Vantagens do MVC:
- Separação clara de responsabilidades
- Código mais organizado e manutenível
- Facilita testes unitários
- Permite trabalho em equipe
- Reutilização de componentes

## Estrutura de Arquivos

```
projeto-mvc/
├── app/
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   ├── HomeController.php
│   │   └── UserController.php
│   ├── Models/
│   │   ├── BaseModel.php
│   │   └── User.php
│   └── Views/
│       ├── layouts/
│       │   └── main.php
│       ├── home/
│       │   └── index.php
│       └── users/
│           ├── index.php
│           ├── show.php
│           └── create.php
├── core/
│   ├── Application.php
│   ├── Router.php
│   ├── Controller.php
│   ├── Model.php
│   └── View.php
├── public/
│   ├── css/
│   ├── js/
│   ├── img/
│   └── index.php
├── config/
│   └── config.php
└── .htaccess
```

## Implementação do Autoloader

### core/Application.php
```php
<?php
namespace Core;

class Application {
    private $router;
    private static $instance;
    
    public function __construct() {
        $this->router = new Router();
        self::$instance = $this;
        
        // Autoloader
        spl_autoload_register([$this, 'autoload']);
    }
    
    public static function getInstance() {
        return self::$instance;
    }
    
    public function autoload($className) {
        // Converter namespace para caminho
        $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        
        // Tentar carregar da pasta app/
        $appFile = __DIR__ . '/../app/' . $className . '.php';
        if (file_exists($appFile)) {
            require_once $appFile;
            return;
        }
        
        // Tentar carregar da pasta core/
        $coreFile = __DIR__ . '/' . str_replace('Core' . DIRECTORY_SEPARATOR, '', $className) . '.php';
        if (file_exists($coreFile)) {
            require_once $coreFile;
            return;
        }
    }
    
    public function run() {
        try {
            $this->router->dispatch();
        } catch (Exception $e) {
            $this->handleError($e);
        }
    }
    
    public function getRouter() {
        return $this->router;
    }
    
    private function handleError($exception) {
        http_response_code(500);
        echo "Erro: " . $exception->getMessage();
        
        // Em produção, logar o erro e mostrar página amigável
        if (!defined('DEBUG') || !DEBUG) {
            error_log($exception->getMessage());
            include __DIR__ . '/../app/Views/errors/500.php';
        }
    }
}
```

## Criando o Router

### core/Router.php
```php
<?php
namespace Core;

class Router {
    private $routes = [];
    private $currentController = 'Home';
    private $currentAction = 'index';
    private $params = [];
    
    public function addRoute($pattern, $controller, $action = 'index', $method = 'GET') {
        $this->routes[] = [
            'pattern' => $pattern,
            'controller' => $controller,
            'action' => $action,
            'method' => strtoupper($method)
        ];
    }
    
    public function get($pattern, $controller, $action = 'index') {
        $this->addRoute($pattern, $controller, $action, 'GET');
    }
    
    public function post($pattern, $controller, $action = 'index') {
        $this->addRoute($pattern, $controller, $action, 'POST');
    }
    
    public function dispatch() {
        $url = $this->getUrl();
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Tentar encontrar rota correspondente
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchRoute($route['pattern'], $url)) {
                $this->currentController = $route['controller'];
                $this->currentAction = $route['action'];
                break;
            }
        }
        
        // Se não encontrou rota, usar URL padrão
        if (empty($this->routes) || !isset($route)) {
            $this->parseUrl($url);
        }
        
        $this->callController();
    }
    
    private function getUrl() {
        $url = $_GET['url'] ?? '';
        return rtrim($url, '/');
    }
    
    private function parseUrl($url) {
        if (!empty($url)) {
            $urlParts = explode('/', filter_var($url, FILTER_SANITIZE_URL));
            
            if (isset($urlParts[0])) {
                $this->currentController = ucfirst($urlParts[0]);
            }
            
            if (isset($urlParts[1])) {
                $this->currentAction = $urlParts[1];
            }
            
            // Parâmetros extras
            if (count($urlParts) > 2) {
                $this->params = array_slice($urlParts, 2);
            }
        }
    }
    
    private function matchRoute($pattern, $url) {
        // Converter padrão para regex
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = preg_replace('/\{(\w+)\}/', '([^\/]+)', $pattern);
        $pattern = '/^' . $pattern . '$/';
        
        if (preg_match($pattern, $url, $matches)) {
            $this->params = array_slice($matches, 1);
            return true;
        }
        
        return false;
    }
    
    private function callController() {
        $controllerClass = 'App\\Controllers\\' . $this->currentController . 'Controller';
        
        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller {$controllerClass} não encontrado");
        }
        
        $controller = new $controllerClass();
        
        if (!method_exists($controller, $this->currentAction)) {
            throw new \Exception("Método {$this->currentAction} não encontrado no controller {$controllerClass}");
        }
        
        call_user_func_array([$controller, $this->currentAction], $this->params);
    }
}
```

## Implementando Controllers

### core/Controller.php
```php
<?php
namespace Core;

class Controller {
    protected $view;
    
    public function __construct() {
        $this->view = new View();
    }
    
    protected function render($viewPath, $data = []) {
        $this->view->render($viewPath, $data);
    }
    
    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }
    
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function getInput($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        
        return $_POST[$key] ?? $default;
    }
    
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field] = "Campo {$field} é obrigatório";
            }
            
            if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "Campo {$field} deve ser um email válido";
            }
            
            if (preg_match('/min:(\d+)/', $rule, $matches)) {
                $min = $matches[1];
                if (strlen($value) < $min) {
                    $errors[$field] = "Campo {$field} deve ter pelo menos {$min} caracteres";
                }
            }
        }
        
        return $errors;
    }
}
```

### app/Controllers/BaseController.php
```php
<?php
namespace App\Controllers;

use Core\Controller;

class BaseController extends Controller {
    
    protected function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }
    
    protected function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
    
    protected function setMessage($type, $message) {
        $_SESSION['flash'][$type] = $message;
    }
    
    protected function getMessages() {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }
}
```

### app/Controllers/HomeController.php
```php
<?php
namespace App\Controllers;

class HomeController extends BaseController {
    
    public function index() {
        $data = [
            'title' => 'Página Inicial',
            'message' => 'Bem-vindo ao MVC Framework!',
            'currentUser' => $this->getCurrentUser()
        ];
        
        $this->render('home/index', $data);
    }
    
    public function about() {
        $data = [
            'title' => 'Sobre',
            'description' => 'Framework MVC criado do zero'
        ];
        
        $this->render('home/about', $data);
    }
    
    public function contact() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = $this->getInput();
            
            $errors = $this->validate($input, [
                'name' => 'required',
                'email' => 'required|email',
                'message' => 'required|min:10'
            ]);
            
            if (empty($errors)) {
                // Processar envio de contato
                $this->setMessage('success', 'Mensagem enviada com sucesso!');
                $this->redirect('/contact');
            } else {
                $data = [
                    'title' => 'Contato',
                    'errors' => $errors,
                    'old' => $input
                ];
                
                $this->render('home/contact', $data);
            }
        } else {
            $data = ['title' => 'Contato'];
            $this->render('home/contact', $data);
        }
    }
}
```

## Criando Views

### core/View.php
```php
<?php
namespace Core;

class View {
    private $layout = 'layouts/main';
    private $data = [];
    
    public function render($viewPath, $data = []) {
        $this->data = $data;
        
        // Extrair variáveis para o escopo da view
        extract($data);
        
        // Capturar conteúdo da view
        ob_start();
        include $this->getViewFile($viewPath);
        $content = ob_get_clean();
        
        // Incluir layout
        include $this->getViewFile($this->layout);
    }
    
    public function setLayout($layout) {
        $this->layout = $layout;
    }
    
    private function getViewFile($viewPath) {
        $file = __DIR__ . '/../app/Views/' . $viewPath . '.php';
        
        if (!file_exists($file)) {
            throw new \Exception("View {$viewPath} não encontrada");
        }
        
        return $file;
    }
    
    public function escape($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    public function url($path) {
        return rtrim($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'], '/') . '/' . ltrim($path, '/');
    }
}
```

### app/Views/layouts/main.php
```php
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'MVC Framework' ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        nav {
            background: #333;
            color: white;
            padding: 1rem 0;
        }
        
        nav ul {
            list-style: none;
            display: flex;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        nav ul li {
            margin-right: 20px;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
        }
        
        nav ul li a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <nav>
        <ul>
            <li><a href="/">Início</a></li>
            <li><a href="/about">Sobre</a></li>
            <li><a href="/contact">Contato</a></li>
            <li><a href="/users">Usuários</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <?php if (isset($_SESSION['flash'])): ?>
            <?php foreach ($_SESSION['flash'] as $type => $message): ?>
                <div class="alert <?= $type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endforeach; ?>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
        
        <?= $content ?>
    </div>
</body>
</html>
```

### app/Views/home/index.php
```php
<h1><?= htmlspecialchars($title) ?></h1>
<p><?= htmlspecialchars($message) ?></p>

<?php if ($currentUser): ?>
    <p>Olá, <?= htmlspecialchars($currentUser['name']) ?>!</p>
<?php else: ?>
    <p>Você não está logado.</p>
<?php endif; ?>

<h2>Recursos do Framework</h2>
<ul>
    <li>Roteamento flexível</li>
    <li>Controllers organizados</li>
    <li>Views com layout</li>
    <li>Validação de dados</li>
    <li>Mensagens flash</li>
</ul>
```

### app/Views/home/about.php
```php
<h1><?= htmlspecialchars($title) ?></h1>
<p><?= htmlspecialchars($description) ?></p>

<h2>Arquitetura MVC</h2>
<h3>Model</h3>
<p>Responsável pelos dados e lógica de negócio da aplicação.</p>

<h3>View</h3>
<p>Responsável pela apresentação dos dados ao usuário.</p>

<h3>Controller</h3>
<p>Responsável por coordenar as interações entre Model e View.</p>
```

### app/Views/home/contact.php
<h1><?= $title ?></h1>

<?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="form-group">
        <label for="name">Nome:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>">
    </div>
    
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>">
    </div>
    
    <div class="form-group">
        <label for="message">Mensagem:</label>
        <textarea id="message" name="message" rows="5"><?= htmlspecialchars($old['message'] ?? '') ?></textarea>
    </div>
    
    <button type="submit" class="btn">Enviar</button>
</form>
```

## Implementando Models

### core/Model.php
```php
<?php
namespace Core;

class Model {
    protected $data = [];
    
    public function __get($property) {
        return $this->data[$property] ?? null;
    }
    
    public function __set($property, $value) {
        $this->data[$property] = $value;
    }
    
    public function toArray() {
        return $this->data;
    }
    
    public function fill($data) {
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
    }
    
    protected function validate($rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $this->data[$field] ?? null;
            $ruleList = explode('|', $rule);
            
            foreach ($ruleList as $singleRule) {
                if ($singleRule === 'required' && empty($value)) {
                    $errors[$field] = "Campo {$field} é obrigatório";
                    break;
                }
                
                if ($singleRule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "Campo {$field} deve ser um email válido";
                    break;
                }
                
                if (preg_match('/min:(\d+)/', $singleRule, $matches)) {
                    $min = $matches[1];
                    if (strlen($value) < $min) {
                        $errors[$field] = "Campo {$field} deve ter pelo menos {$min} caracteres";
                        break;
                    }
                }
            }
        }
        
        return $errors;
    }
}
```

### app/Models/User.php
```php
<?php
namespace App\Models;

use Core\Model;

class User extends Model {
    private static $users = [
        1 => ['id' => 1, 'name' => 'João Silva', 'email' => 'joao@exemplo.com'],
        2 => ['id' => 2, 'name' => 'Maria Santos', 'email' => 'maria@exemplo.com'],
        3 => ['id' => 3, 'name' => 'Pedro Costa', 'email' => 'pedro@exemplo.com']
    ];
    
    public static function all() {
        $users = [];
        foreach (self::$users as $userData) {
            $user = new self();
            $user->fill($userData);
            $users[] = $user;
        }
        return $users;
    }
    
    public static function find($id) {
        if (isset(self::$users[$id])) {
            $user = new self();
            $user->fill(self::$users[$id]);
            return $user;
        }
        return null;
    }
    
    public function save() {
        $errors = $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email'
        ]);
        
        if (!empty($errors)) {
            return $errors;
        }
        
        if (!$this->id) {
            // Novo usuário
            $this->id = max(array_keys(self::$users)) + 1;
        }
        
        self::$users[$this->id] = $this->toArray();
        return true;
    }
    
    public function delete() {
        if ($this->id && isset(self::$users[$this->id])) {
            unset(self::$users[$this->id]);
            return true;
        }
        return false;
    }
}
```

## Setup do Ambiente

### 1. Estrutura de Arquivos

Crie a estrutura de pastas conforme mostrado no início do guia.

### 2. Configuração do Apache (.htaccess)

Crie o arquivo `.htaccess` na raiz do projeto:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/index.php?url=$1 [QSA,L]
```

### 3. Arquivo de Entrada (public/index.php)

```php
<?php
session_start();

// Definir constantes
define('ROOT_PATH', dirname(__DIR__));
define('DEBUG', true);

// Incluir arquivos principais
require_once ROOT_PATH . '/core/Application.php';

// Configurar error reporting
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

try {
    // Inicializar aplicação
    $app = new Core\Application();
    
    // Definir rotas
    $router = $app->getRouter();
    
    // Rotas da aplicação
    $router->get('/', 'Home', 'index');
    $router->get('/about', 'Home', 'about');
    $router->get('/contact', 'Home', 'contact');
    $router->post('/contact', 'Home', 'contact');
    
    // Rotas de usuários
    $router->get('/users', 'User', 'index');
    $router->get('/users/{id}', 'User', 'show');
    $router->get('/users/create', 'User', 'create');
    $router->post('/users', 'User', 'store');
    
    // Executar aplicação
    $app->run();
    
} catch (Exception $e) {
    if (DEBUG) {
        echo "Erro: " . $e->getMessage();
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    } else {
        echo "Erro interno do servidor";
        error_log($e->getMessage());
    }
}
```

### 4. Controller de Usuários

### app/Controllers/UserController.php
```php
<?php
namespace App\Controllers;

use App\Models\User;

class UserController extends BaseController {
    
    public function index() {
        $users = User::all();
        
        $data = [
            'title' => 'Lista de Usuários',
            'users' => $users
        ];
        
        $this->render('users/index', $data);
    }
    
    public function show($id) {
        $user = User::find($id);
        
        if (!$user) {
            $this->setMessage('error', 'Usuário não encontrado');
            $this->redirect('/users');
        }
        
        $data = [
            'title' => 'Detalhes do Usuário',
            'user' => $user
        ];
        
        $this->render('users/show', $data);
    }
    
    public function create() {
        $data = ['title' => 'Novo Usuário'];
        $this->render('users/create', $data);
    }
    
    public function store() {
        $input = $this->getInput();
        
        $user = new User();
        $user->fill($input);
        
        $result = $user->save();
        
        if ($result === true) {
            $this->setMessage('success', 'Usuário criado com sucesso!');
            $this->redirect('/users');
        } else {
            $data = [
                'title' => 'Novo Usuário',
                'errors' => $result,
                'old' => $input
            ];
            
            $this->render('users/create', $data);
        }
    }
}
```

### 5. Views de Usuários

### app/Views/users/index.php
```php
<h1><?= $title ?></h1>

<a href="/users/create" class="btn">Novo Usuário</a>

<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
    <thead>
        <tr style="background: #f8f9fa;">
            <th style="border: 1px solid #ddd; padding: 10px;">ID</th>
            <th style="border: 1px solid #ddd; padding: 10px;">Nome</th>
            <th style="border: 1px solid #ddd; padding: 10px;">Email</th>
            <th style="border: 1px solid #ddd; padding: 10px;">Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <td style="border: 1px solid #ddd; padding: 10px;"><?= $user->id ?></td>
            <td style="border: 1px solid #ddd; padding: 10px;"><?= htmlspecialchars($user->name) ?></td>
            <td style="border: 1px solid #ddd; padding: 10px;"><?= htmlspecialchars($user->email) ?></td>
            <td style="border: 1px solid #ddd; padding: 10px;">
                <a href="/users/<?= $user->id ?>">Ver</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

### app/Views/users/show.php
```php
<h1><?= $title ?></h1>

<div style="background: #f8f9fa; padding: 20px; border-radius: 5px;">
    <h2><?= htmlspecialchars($user->name) ?></h2>
    <p><strong>ID:</strong> <?= $user->id ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user->email) ?></p>
</div>

<a href="/users" class="btn" style="margin-top: 20px;">Voltar</a>
```

### app/Views/users/create.php
```php
<h1><?= $title ?></h1>

<?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/users">
    <div class="form-group">
        <label for="name">Nome:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>">
    </div>
    
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>">
    </div>
    
    <button type="submit" class="btn">Salvar</button>
    <a href="/users" class="btn" style="background: #6c757d;">Cancelar</a>
</form>
```

## Exemplo Completo

### Testando a Aplicação

1. **Página Inicial**: Acesse `http://localhost/projeto-mvc/`
2. **Lista de Usuários**: Acesse `http://localhost/projeto-mvc/users`
3. **Criar Usuário**: Acesse `http://localhost/projeto-mvc/users/create`
4. **Ver Usuário**: Acesse `http://localhost/projeto-mvc/users/1`
5. **Contato**: Acesse `http://localhost/projeto-mvc/contact`

### Fluxo de Execução

1. **Requisição chega ao index.php**
2. **Router analisa a URL e determina Controller/Action**
3. **Controller processa a lógica**
4. **Model gerencia os dados**
5. **View renderiza a resposta**
6. **Resposta é enviada ao navegador**

## Resolução de Problemas

### Problema 1: "Class not found"
**Sintomas**: Erro de classe não encontrada
**Soluções**:
```php
// Verificar se o autoloader está funcionando
echo "Tentando carregar: " . $className . "\n";
echo "Arquivo: " . $appFile . "\n";
echo "Existe: " . (file_exists($appFile) ? 'Sim' : 'Não') . "\n";

// Verificar namespaces
namespace App\Controllers; // Deve estar no topo do arquivo

// Verificar nomes de arquivos (case-sensitive no Linux)
```

### Problema 2: "View not found"
**Sintomas**: Erro de view não encontrada
**Soluções**:
```php
// Verificar caminho da view
$viewPath = __DIR__ . '/../app/Views/' . $viewPath . '.php';
echo "Procurando view em: " . $viewPath . "\n";

// Verificar se a estrutura de pastas está correta
// app/Views/home/index.php
// app/Views/users/index.php
```

### Problema 3: URLs não funcionam
**Sintomas**: 404 em todas as páginas exceto index
**Soluções**:
```apache
# Verificar .htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/index.php?url=$1 [QSA,L]

# Verificar se mod_rewrite está ativado
# No WAMP: Apache modules -> rewrite_module
```

### Problema 4: "Session already started"
**Sintomas**: Warning sobre sessão já iniciada
**Soluções**:
```php
// Verificar antes de iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ou usar session_start() apenas uma vez no index.php
```

### Problema 5: CSS/JS não carrega
**Sintomas**: Estilos não aplicados
**Soluções**:
```apache
# Adicionar ao .htaccess
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
# Isso permite arquivos estáticos

# Ou criar .htaccess específico em public/
<Files ~ "\.(css|js|png|jpg|gif)$">
    Allow from all
</Files>
```

### Problema 6: Parâmetros não funcionam
**Sintomas**: Parâmetros da URL não chegam ao controller
**Soluções**:
```php
// Verificar método matchRoute
private function matchRoute($pattern, $url) {
    echo "Pattern: $pattern\n";
    echo "URL: $url\n";
    
    $pattern = str_replace('/', '\/', $pattern);
    $pattern = preg_replace('/\{(\w+)\}/', '([^\/]+)', $pattern);
    $pattern = '/^' . $pattern . '$/';
    
    echo "Regex: $pattern\n";
    
    if (preg_match($pattern, $url, $matches)) {
        print_r($matches);
        $this->params = array_slice($matches, 1);
        return true;
    }
    
    return false;
}
```

### Debug Helper

```php
// Adicionar ao Controller base para debug
protected function debug($data, $die = false) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    if ($die) {
        die();
    }
}

// Usar nos controllers
$this->debug($_POST);
$this->debug($this->params);
```

---

**Importante**: Este é um exemplo básico de MVC. Em produção, considere usar frameworks estabelecidos como Laravel, Symfony ou CodeIgniter que oferecem mais recursos e segurança.
