# 🏗️ Arquitetura MVC - Sistema GEstufas

## Visão Geral

O Sistema GEstufas implementa o padrão **Model-View-Controller (MVC)** com algumas extensões modernas:
- **ActiveRecord** como ORM
- **Router** personalizado
- **Sistema de Autenticação** integrado
- **Bootstrap 5** para frontend

## 📊 Componentes da Arquitetura

### 1. **Entry Point (index.php)**
```php
<?php
// Ponto de entrada único da aplicação
require_once 'startup/config.php';
require_once 'vendor/autoload.php';

// Carrega configurações
$config = require 'config/config.php';

// Inicia roteamento
require_once 'routes.php';
```

**Responsabilidades:**
- Carregamento de dependências
- Configuração inicial
- Inicialização do sistema de rotas

### 2. **Router (core/Router.php)**
```php
class Router {
    private static $routes = [];
    
    public static function route($controller, $action, $params = []) {
        // Carrega e executa controller
    }
}
```

**Responsabilidades:**
- Análise de URLs
- Mapeamento de rotas
- Carregamento de controllers
- Passagem de parâmetros

### 3. **Controllers**
```php
class UserController extends Controller {
    public function index() {
        // Lógica de negócio
        $users = User::all();
        $this->render('users/index', ['users' => $users]);
    }
}
```

**Responsabilidades:**
- Lógica de negócio
- Interação com models
- Renderização de views
- Gestão de autenticação

### 4. **Models (ActiveRecord)**
```php
class User extends ActiveRecord\Model {
    static $table_name = 'users';
    static $validates_presence_of = [
        ['name', 'email']
    ];
}
```

**Responsabilidades:**
- Acesso aos dados
- Validações
- Relacionamentos
- Regras de negócio

### 5. **Views**
```php
<!-- views/users/index.php -->
<div class="container">
    <?php foreach($users as $user): ?>
        <div class="user-card">
            <h3><?= htmlspecialchars($user->name) ?></h3>
        </div>
    <?php endforeach; ?>
</div>
```

**Responsabilidades:**
- Apresentação de dados
- Interface do utilizador
- Templates HTML
- Integração com CSS/JS

## 🔄 Fluxo de Requisição

### 1. **Requisição HTTP**
```
GET /index.php?c=users&a=show&id=1
```

### 2. **Processamento (index.php)**
```php
// 1. Carregamento de configurações
require_once 'startup/config.php';

// 2. Autoload das classes
require_once 'vendor/autoload.php';

// 3. Inicialização do router
require_once 'routes.php';
```

### 3. **Roteamento (Router)**
```php
// Analisa parâmetros da URL
$controller = $_GET['c'] ?? 'home';  // users
$action = $_GET['a'] ?? 'index';     // show
$id = $_GET['id'] ?? null;           // 1

// Carrega controller
Router::route($controller, $action, ['id' => $id]);
```

### 4. **Execução do Controller**
```php
class UserController extends Controller {
    public function show($id) {
        // 1. Validação de entrada
        if (!$id) {
            $this->redirect('?c=users');
            return;
        }
        
        // 2. Busca de dados
        $user = User::find($id);
        
        // 3. Verificação de existência
        if (!$user) {
            $this->setFlash('error', 'Utilizador não encontrado');
            $this->redirect('?c=users');
            return;
        }
        
        // 4. Renderização
        $this->render('users/show', ['user' => $user]);
    }
}
```

### 5. **Renderização da View**
```php
<!-- views/users/show.php -->
<?php $this->layout = 'layout/main'; ?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h2><?= htmlspecialchars($user->name) ?></h2>
        </div>
        <div class="card-body">
            <p><strong>Email:</strong> <?= htmlspecialchars($user->email) ?></p>
            <p><strong>Criado em:</strong> <?= $user->created_at ?></p>
        </div>
    </div>
</div>
```

### 6. **Resposta HTML**
```html
<!DOCTYPE html>
<html>
<head>
    <title>GEstufas</title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h2>João Silva</h2>
            </div>
            <!-- ... resto do HTML ... -->
        </div>
    </div>
</body>
</html>
```

## 📂 Estrutura de Diretórios

```
gestufas/
├── config/                 # Configurações
│   ├── config.php         # Config principal
│   └── database.php       # Config BD
├── controllers/           # Controllers MVC
│   ├── Controller.php     # Base controller
│   ├── HomeController.php # Controller home
│   ├── UserController.php # Controller utilizadores
│   └── ...
├── core/                  # Classes principais
│   ├── Router.php         # Sistema de rotas
│   └── Controller.php     # Controller base
├── models/                # Models ActiveRecord
│   ├── User.php           # Model utilizador
│   ├── Post.php           # Model post
│   └── ...
├── views/                 # Templates HTML
│   ├── layout/            # Layouts partilhados
│   │   ├── main.php       # Layout principal
│   │   └── components/    # Componentes
│   ├── users/             # Views utilizadores
│   ├── posts/             # Views posts
│   └── ...
├── public/                # Ficheiros públicos
│   ├── css/               # Estilos CSS
│   ├── js/                # Scripts JavaScript
│   └── img/               # Imagens
├── startup/               # Inicialização
│   └── config.php         # Config inicial
├── index.php              # Entry point
├── routes.php             # Definição de rotas
└── composer.json          # Dependências
```

## 🔐 Sistema de Autenticação

### **Session Management**
```php
// Verificação de autenticação
if (!Auth::isLoggedIn()) {
    header('Location: ?c=auth&a=login');
    exit;
}

// Acesso ao utilizador atual
$currentUser = Auth::getCurrentUser();
```

### **Proteção de Rotas**
```php
class UserController extends Controller {
    public function __construct() {
        // Requer autenticação para todas as ações
        $this->requireAuth();
    }
    
    public function delete($id) {
        // Requer permissões especiais
        if (!Auth::can('delete_users')) {
            $this->setFlash('error', 'Sem permissões');
            $this->redirect('?c=users');
            return;
        }
        
        // Lógica de eliminação...
    }
}
```

## 🗃️ Camada de Dados (ActiveRecord)

### **Configuração**
```php
// config/config.php
ActiveRecord\Config::initialize(function($cfg) {
    $cfg->set_model_directory('models');
    $cfg->set_connections([
        'development' => 'mysql://user:pass@localhost/gestufas'
    ]);
});
```

### **Relacionamentos**
```php
class User extends ActiveRecord\Model {
    // Um utilizador tem muitos posts
    static $has_many = [
        ['posts']
    ];
}

class Post extends ActiveRecord\Model {
    // Um post pertence a um utilizador
    static $belongs_to = [
        ['user']
    ];
}
```

### **Validações**
```php
class User extends ActiveRecord\Model {
    static $validates_presence_of = [
        ['name', 'email']
    ];
    
    static $validates_uniqueness_of = [
        ['email']
    ];
    
    static $validates_format_of = [
        ['email', 'with' => '/\A[^@\s]+@[^@\s]+\z/']
    ];
}
```

## 🎨 Sistema de Views

### **Layouts**
```php
<!-- views/layout/main.php -->
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?? 'GEstufas' ?></title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'components/navbar.php'; ?>
    
    <main class="container mt-4">
        <?php include 'components/flash-messages.php'; ?>
        <?= $content ?>
    </main>
    
    <script src="public/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

### **Componentes Reutilizáveis**
```php
<!-- views/layout/components/navbar.php -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="?c=home">GEstufas</a>
        
        <div class="navbar-nav ms-auto">
            <?php if (Auth::isLoggedIn()): ?>
                <a class="nav-link" href="?c=auth&a=logout">Sair</a>
            <?php else: ?>
                <a class="nav-link" href="?c=auth&a=login">Entrar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
```

## 🔧 Configuração e Ambiente

### **Configuração de Desenvolvimento**
```php
// config/config.php
return [
    'database' => [
        'host' => 'localhost',
        'database' => 'gestufas',
        'username' => 'root',
        'password' => '',
    ],
    'app' => [
        'debug' => true,
        'timezone' => 'Europe/Lisbon',
    ],
    'session' => [
        'name' => 'gestufas_session',
        'lifetime' => 86400, // 24 horas
    ]
];
```

### **Autoload e Dependências**
```json
// composer.json
{
    "require": {
        "php-activerecord/php-activerecord": "^1.19",
        "nesbot/carbon": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "Controllers\\": "controllers/",
            "Models\\": "models/",
            "Core\\": "core/"
        }
    }
}
```

## 📈 Melhores Práticas

### **Organização de Código**
- Um controller por entidade principal
- Models focados na lógica de dados
- Views simples e focadas na apresentação
- Componentes reutilizáveis em `layout/components/`

### **Segurança**
- Validação de entrada em controllers
- Escape de saída em views (`htmlspecialchars()`)
- Verificação de autenticação e permissões
- Proteção CSRF em formulários

### **Performance**
- Cache de configurações
- Lazy loading de relacionamentos
- Otimização de queries do ActiveRecord
- Compressão de assets CSS/JS

---

Esta arquitetura fornece uma base sólida para desenvolvimento de aplicações web em PHP, mantendo a simplicidade e flexibilidade do padrão MVC enquanto aproveita as vantagens modernas do ActiveRecord e Bootstrap.
