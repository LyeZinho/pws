# 🎨 Layouts - Sistema de Templates

## Visão Geral

O sistema de layouts do GEstufas permite criar templates reutilizáveis que definem a estrutura comum das páginas, evitando repetição de código e mantendo consistência visual em toda a aplicação.

## 📂 Estrutura de Layouts

```
views/
├── layout/
│   ├── main.php           # Layout principal
│   ├── admin.php          # Layout administrativo
│   ├── auth.php           # Layout para autenticação
│   └── components/        # Componentes reutilizáveis
│       ├── navbar.php     # Barra de navegação
│       ├── footer.php     # Rodapé
│       ├── sidebar.php    # Barra lateral
│       ├── flash-messages.php # Mensagens flash
│       └── breadcrumb.php # Breadcrumbs
└── users/
    ├── index.php          # View que usa layout
    ├── show.php
    └── create.php
```

## 🏗️ Layout Principal

### **Layout Base (views/layout/main.php)**
```php
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'GEstufas - Sistema de Gestão' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CSS personalizado -->
    <link href="public/css/custom.css" rel="stylesheet">
    
    <!-- CSS adicional da página -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?= $css ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Meta tags adicionais -->
    <?php if (isset($metaTags)): ?>
        <?php foreach ($metaTags as $name => $content): ?>
            <meta name="<?= $name ?>" content="<?= htmlspecialchars($content) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?= $bodyClass ?? '' ?>">
    
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>
    
    <!-- Breadcrumbs (se definidos) -->
    <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
        <?php include 'components/breadcrumb.php'; ?>
    <?php endif; ?>
    
    <!-- Container principal -->
    <main class="main-content">
        <!-- Mensagens flash -->
        <?php include 'components/flash-messages.php'; ?>
        
        <!-- Conteúdo da página -->
        <div class="content-wrapper">
            <?= $content ?>
        </div>
    </main>
    
    <!-- Rodapé -->
    <?php include 'components/footer.php'; ?>
    
    <!-- Scripts JavaScript -->
    <script src="public/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/app.js"></script>
    
    <!-- JavaScript adicional da página -->
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- JavaScript inline da página -->
    <?php if (isset($inlineJS)): ?>
        <script>
            <?= $inlineJS ?>
        </script>
    <?php endif; ?>
    
</body>
</html>
```

## 🧩 Componentes Reutilizáveis

### **1. Navbar (views/layout/components/navbar.php)**
```php
<?php
$currentUser = Auth::getCurrentUser();
$currentController = $_GET['c'] ?? 'home';
$currentAction = $_GET['a'] ?? 'index';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <!-- Logo/Brand -->
        <a class="navbar-brand" href="?c=home">
            <img src="public/img/logo-ipleiria.png" alt="GEstufas" height="30" class="me-2">
            GEstufas
        </a>
        
        <!-- Mobile toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- Home -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentController === 'home' ? 'active' : '' ?>" 
                       href="?c=home">
                        <i class="fas fa-home"></i> Início
                    </a>
                </li>
                
                <?php if ($currentUser): ?>
                    <!-- Community -->
                    <li class="nav-item">
                        <a class="nav-link <?= $currentController === 'community' ? 'active' : '' ?>" 
                           href="?c=community">
                            <i class="fas fa-users"></i> Comunidade
                        </a>
                    </li>
                    
                    <!-- Posts -->
                    <li class="nav-item">
                        <a class="nav-link <?= $currentController === 'posts' ? 'active' : '' ?>" 
                           href="?c=posts">
                            <i class="fas fa-newspaper"></i> Posts
                        </a>
                    </li>
                    
                    <!-- Projects -->
                    <li class="nav-item">
                        <a class="nav-link <?= $currentController === 'projects' ? 'active' : '' ?>" 
                           href="?c=projects">
                            <i class="fas fa-project-diagram"></i> Projetos
                        </a>
                    </li>
                    
                    <!-- Admin menu -->
                    <?php if ($currentUser->role === 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-cog"></i> Admin
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="?c=users">
                                        <i class="fas fa-users"></i> Utilizadores
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="?c=admin&a=stats">
                                        <i class="fas fa-chart-bar"></i> Estatísticas
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="?c=admin&a=logs">
                                        <i class="fas fa-file-alt"></i> Logs
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            
            <!-- User menu -->
            <ul class="navbar-nav">
                <?php if ($currentUser): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="<?= $currentUser->getAvatarUrl() ?>" 
                                 alt="Avatar" 
                                 class="rounded-circle me-1" 
                                 width="24" 
                                 height="24">
                            <?= htmlspecialchars($currentUser->name) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="?c=profile">
                                    <i class="fas fa-user"></i> Meu Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="?c=profile&a=settings">
                                    <i class="fas fa-cog"></i> Configurações
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="?c=auth&a=logout">
                                    <i class="fas fa-sign-out-alt"></i> Sair
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="?c=auth&a=login">
                            <i class="fas fa-sign-in-alt"></i> Entrar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?c=auth&a=register">
                            <i class="fas fa-user-plus"></i> Registar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
```

### **2. Flash Messages (views/layout/components/flash-messages.php)**
```php
<?php
// Mensagens flash são definidas no controller com $this->setFlash()
$flashTypes = ['success', 'error', 'warning', 'info'];
?>

<div class="flash-messages">
    <?php foreach ($flashTypes as $type): ?>
        <?php if (isset($_SESSION['flash'][$type])): ?>
            <?php
            $message = $_SESSION['flash'][$type];
            unset($_SESSION['flash'][$type]); // Remove após mostrar
            
            // Mapear tipos para classes Bootstrap
            $alertClass = match($type) {
                'success' => 'alert-success',
                'error' => 'alert-danger',
                'warning' => 'alert-warning',
                'info' => 'alert-info',
                default => 'alert-secondary'
            };
            
            $icon = match($type) {
                'success' => 'fas fa-check-circle',
                'error' => 'fas fa-exclamation-triangle',
                'warning' => 'fas fa-exclamation-circle',
                'info' => 'fas fa-info-circle',
                default => 'fas fa-bell'
            };
            ?>
            
            <div class="alert <?= $alertClass ?> alert-dismissible fade show" role="alert">
                <i class="<?= $icon ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<style>
.flash-messages {
    position: sticky;
    top: 0;
    z-index: 1050;
}

.flash-messages .alert {
    margin: 0;
    border-radius: 0;
    border-width: 0 0 1px 0;
}
</style>
```

### **3. Breadcrumbs (views/layout/components/breadcrumb.php)**
```php
<?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
    <nav aria-label="breadcrumb" class="bg-light border-bottom">
        <div class="container">
            <ol class="breadcrumb mb-0 py-3">
                <li class="breadcrumb-item">
                    <a href="?c=home">
                        <i class="fas fa-home"></i> Início
                    </a>
                </li>
                
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <?php if ($index === count($breadcrumbs) - 1): ?>
                        <!-- Último item (atual) -->
                        <li class="breadcrumb-item active" aria-current="page">
                            <?= htmlspecialchars($crumb['title']) ?>
                        </li>
                    <?php else: ?>
                        <!-- Item com link -->
                        <li class="breadcrumb-item">
                            <a href="<?= $crumb['url'] ?>">
                                <?= htmlspecialchars($crumb['title']) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </div>
    </nav>
<?php endif; ?>
```

### **4. Footer (views/layout/components/footer.php)**
```php
<footer class="bg-dark text-light mt-5">
    <div class="container py-4">
        <div class="row">
            <div class="col-md-6">
                <h5>GEstufas</h5>
                <p class="text-muted">
                    Sistema de gestão de estufas e projetos agrícolas.
                    Desenvolvido com PHP MVC e ActiveRecord.
                </p>
            </div>
            <div class="col-md-3">
                <h6>Links Úteis</h6>
                <ul class="list-unstyled">
                    <li><a href="?c=home" class="text-light">Início</a></li>
                    <li><a href="?c=community" class="text-light">Comunidade</a></li>
                    <li><a href="?c=posts" class="text-light">Posts</a></li>
                    <li><a href="?c=projects" class="text-light">Projetos</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6>Suporte</h6>
                <ul class="list-unstyled">
                    <li><a href="?c=help" class="text-light">Ajuda</a></li>
                    <li><a href="?c=contact" class="text-light">Contacto</a></li>
                    <li><a href="?c=privacy" class="text-light">Privacidade</a></li>
                </ul>
            </div>
        </div>
        <hr class="my-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <small class="text-muted">
                    © <?= date('Y') ?> GEstufas. Todos os direitos reservados.
                </small>
            </div>
            <div class="col-md-6 text-md-end">
                <small class="text-muted">
                    Versão 1.0.0 | 
                    <a href="https://www.ipleiria.pt" class="text-light">IPLeiria</a>
                </small>
            </div>
        </div>
    </div>
</footer>
```

## 🎭 Layouts Especializados

### **Layout de Autenticação (views/layout/auth.php)**
```php
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Autenticação - GEstufas' ?></title>
    
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .auth-card {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card auth-card">
                    <div class="card-body p-5">
                        <!-- Logo -->
                        <div class="text-center mb-4">
                            <img src="public/img/logo-ipleiria.png" alt="GEstufas" height="60">
                            <h3 class="mt-3">GEstufas</h3>
                        </div>
                        
                        <!-- Flash messages -->
                        <?php include 'components/flash-messages.php'; ?>
                        
                        <!-- Content -->
                        <?= $content ?>
                    </div>
                </div>
                
                <!-- Links adicionais -->
                <div class="text-center mt-3">
                    <a href="?c=home" class="text-white">
                        <i class="fas fa-arrow-left"></i> Voltar ao início
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="public/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

### **Layout Administrativo (views/layout/admin.php)**
```php
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin - GEstufas' ?></title>
    
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="public/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'components/admin-sidebar.php'; ?>
        
        <!-- Main content wrapper -->
        <div class="main-wrapper">
            <!-- Top navbar -->
            <?php include 'components/admin-navbar.php'; ?>
            
            <!-- Content -->
            <main class="content">
                <div class="container-fluid">
                    <!-- Breadcrumbs -->
                    <?php if (isset($breadcrumbs)): ?>
                        <?php include 'components/breadcrumb.php'; ?>
                    <?php endif; ?>
                    
                    <!-- Flash messages -->
                    <?php include 'components/flash-messages.php'; ?>
                    
                    <!-- Page content -->
                    <?= $content ?>
                </div>
            </main>
        </div>
    </div>
    
    <script src="public/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/admin.js"></script>
</body>
</html>
```

## 🎯 Uso dos Layouts

### **1. Definir Layout numa View**
```php
<?php
// views/users/index.php
$this->layout = 'layout/main';  // Define o layout a usar
?>

<div class="container mt-4">
    <h1>Lista de Utilizadores</h1>
    <!-- resto do conteúdo -->
</div>
```

### **2. Passar Dados para o Layout (Controller)**
```php
// controllers/UserController.php
public function index() {
    $users = User::all();
    
    $this->render('users/index', [
        'users' => $users,
        'title' => 'Utilizadores - GEstufas',
        'breadcrumbs' => [
            ['title' => 'Utilizadores', 'url' => '?c=users']
        ],
        'additionalCSS' => ['public/css/users.css'],
        'additionalJS' => ['public/js/users.js'],
        'metaTags' => [
            'description' => 'Lista de utilizadores do sistema GEstufas',
            'keywords' => 'utilizadores, gestão, sistema'
        ]
    ]);
}
```

### **3. Layout Condicional**
```php
// controllers/AuthController.php
public function login() {
    $this->render('auth/login', [
        'title' => 'Login - GEstufas'
    ], 'layout/auth');  // Terceiro parâmetro define layout específico
}
```

## 📱 Layout Responsivo

### **CSS Personalizado (public/css/custom.css)**
```css
/* Layout responsivo */
.main-content {
    min-height: calc(100vh - 140px);
    padding-top: 20px;
}

.content-wrapper {
    margin-bottom: 40px;
}

/* Navbar customizations */
.navbar-brand img {
    filter: brightness(0) invert(1);
}

/* Flash messages */
.flash-messages .alert {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .main-content {
        padding-top: 10px;
    }
    
    .container {
        padding-left: 15px;
        padding-right: 15px;
    }
    
    .card-body {
        padding: 1rem;
    }
}

/* Print styles */
@media print {
    .navbar,
    .flash-messages,
    footer,
    .btn,
    .pagination {
        display: none !important;
    }
    
    .main-content {
        padding: 0;
        min-height: auto;
    }
}
```

## 🔧 JavaScript para Layouts

### **Script Principal (public/js/app.js)**
```javascript
// Inicialização geral da aplicação
document.addEventListener('DOMContentLoaded', function() {
    
    // Auto-hide flash messages após 5 segundos
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Confirmação de eliminação
    window.confirmDelete = function(message = 'Tem certeza que deseja eliminar?') {
        return confirm(message);
    };
    
    // AJAX setup
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        // Setup para requisições AJAX com CSRF token
        fetch.defaults = {
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content')
            }
        };
    }
});

// Função helper para mostrar loading
window.showLoading = function(element) {
    element.disabled = true;
    element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A processar...';
};

// Função helper para esconder loading
window.hideLoading = function(element, originalText) {
    element.disabled = false;
    element.innerHTML = originalText;
};
```

---

O sistema de layouts fornece uma estrutura flexível e reutilizável que mantém a consistência visual em toda a aplicação, facilitando a manutenção e permitindo personalizações específicas quando necessário.
