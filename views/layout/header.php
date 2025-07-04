<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? htmlspecialchars($title) . ' - ' : '' ?>GEstufas - Sistema de Gestão</title>
    
    <!-- Bootstrap CSS -->
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="public/img/logo-ipleiria.png">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .navbar-nav .nav-link {
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover {
            color: var(--primary-color) !important;
            transform: translateY(-1px);
        }
        
        .navbar-nav .nav-link.active {
            color: var(--primary-color) !important;
            font-weight: 600;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.375rem;
        }
        
        .dropdown-item:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .badge-notification {
            position: absolute;
            top: -5px;
            right: -10px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .navbar-toggler {
            border: none;
            padding: 0.25rem 0.5rem;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        /* Animação para o menu mobile */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background-color: white;
                border-radius: 0.375rem;
                margin-top: 0.5rem;
                padding: 1rem;
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            }
        }
        
        /* Loading spinner */
        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }
        
        /* Breadcrumb personalizado */
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 0;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: var(--secondary-color);
        }
        
        /* Estilo para links ativos */
        .nav-item.active .nav-link {
            background-color: rgba(13, 110, 253, 0.1);
            border-radius: 0.375rem;
        }
    </style>
</head>
<body>
    <!-- Loading Spinner -->
    <div class="loading-spinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Carregando...</span>
        </div>
    </div>

    <!-- Navbar Principal -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid">
            <!-- Logo e Nome -->
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="public/img/logo-ipleiria.png" alt="Logo" width="40" height="40" class="me-2">
                <span class="text-primary">GEstufas</span>
            </a>

            <!-- Toggle button para mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menu de navegação -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- Dashboard/Home -->
                    <li class="nav-item <?= (isset($_GET['controller']) && $_GET['controller'] === 'home') || !isset($_GET['controller']) ? 'active' : '' ?>">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-2"></i>
                            Dashboard
                        </a>
                    </li>

                    <!-- Posts -->
                    <li class="nav-item dropdown <?= (isset($_GET['controller']) && $_GET['controller'] === 'posts') ? 'active' : '' ?>">
                        <a class="nav-link dropdown-toggle" href="#" id="postsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-newspaper me-2"></i>
                            Posts
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?controller=posts&action=index">
                                <i class="fas fa-list me-2"></i>Ver Todos os Posts
                            </a></li>
                            <li><a class="dropdown-item" href="index.php?controller=posts&action=create">
                                <i class="fas fa-plus me-2"></i>Criar Novo Post
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?controller=profile&action=posts">
                                <i class="fas fa-user-edit me-2"></i>Meus Posts
                            </a></li>
                        </ul>
                    </li>

                    <!-- Projetos -->
                    <li class="nav-item dropdown <?= (isset($_GET['controller']) && $_GET['controller'] === 'projects') ? 'active' : '' ?>">
                        <a class="nav-link dropdown-toggle" href="#" id="projectsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-project-diagram me-2"></i>
                            Projetos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?controller=projects&action=index">
                                <i class="fas fa-list me-2"></i>Ver Todos os Projetos
                            </a></li>
                            <li><a class="dropdown-item" href="index.php?controller=projects&action=create">
                                <i class="fas fa-plus me-2"></i>Criar Novo Projeto
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?controller=profile&action=projects">
                                <i class="fas fa-user-cog me-2"></i>Meus Projetos
                            </a></li>
                        </ul>
                    </li>

                    <!-- Comunidade -->
                    <li class="nav-item dropdown <?= (isset($_GET['controller']) && $_GET['controller'] === 'community') ? 'active' : '' ?>">
                        <a class="nav-link dropdown-toggle" href="#" id="communityDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users me-2"></i>
                            Comunidade
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?controller=community&action=index">
                                <i class="fas fa-comments me-2"></i>Discussões
                            </a></li>
                            <li><a class="dropdown-item" href="index.php?controller=users&action=index">
                                <i class="fas fa-users me-2"></i>Membros
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?controller=community&action=events">
                                <i class="fas fa-calendar me-2"></i>Eventos (Em Breve)
                            </a></li>
                        </ul>
                    </li>

                    <!-- Gestão (apenas para administradores) -->
                    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-warning" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-2"></i>
                            Gestão
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?controller=users&action=index">
                                <i class="fas fa-users-cog me-2"></i>Gerir Utilizadores
                            </a></li>
                            <li><a class="dropdown-item" href="index.php?controller=admin&action=posts">
                                <i class="fas fa-newspaper me-2"></i>Gerir Posts
                            </a></li>
                            <li><a class="dropdown-item" href="index.php?controller=admin&action=projects">
                                <i class="fas fa-project-diagram me-2"></i>Gerir Projetos
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?controller=admin&action=stats">
                                <i class="fas fa-chart-bar me-2"></i>Estatísticas
                            </a></li>
                            <li><a class="dropdown-item" href="index.php?controller=admin&action=logs">
                                <i class="fas fa-file-alt me-2"></i>Logs do Sistema
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>

                <!-- Menu do usuário (lado direito) -->
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Notificações (placeholder) -->
                        <li class="nav-item dropdown">
                            <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <span class="badge-notification">3</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                                <li><h6 class="dropdown-header">Notificações</h6></li>
                                <li><a class="dropdown-item" href="#">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-comment text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <h6 class="mb-0">Novo comentário</h6>
                                            <small class="text-muted">Há 2 minutos</small>
                                        </div>
                                    </div>
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="#">Ver todas as notificações</a></li>
                            </ul>
                        </li>

                        <!-- Menu do perfil -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-2"></i>
                                <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Utilizador') ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="index.php?controller=profile&action=index">
                                    <i class="fas fa-user me-2"></i>Meu Perfil
                                </a></li>
                                <li><a class="dropdown-item" href="index.php?controller=profile&action=edit">
                                    <i class="fas fa-edit me-2"></i>Editar Perfil
                                </a></li>
                                <li><a class="dropdown-item" href="index.php?controller=profile&action=posts">
                                    <i class="fas fa-newspaper me-2"></i>Meus Posts
                                </a></li>
                                <li><a class="dropdown-item" href="index.php?controller=profile&action=projects">
                                    <i class="fas fa-project-diagram me-2"></i>Meus Projetos
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="index.php?controller=profile&action=settings">
                                    <i class="fas fa-cogs me-2"></i>Configurações
                                </a></li>
                                <li><a class="dropdown-item text-danger" href="index.php?controller=auth&action=logout">
                                    <i class="fas fa-sign-out-alt me-2"></i>Sair
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Botões de login/registo -->
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?controller=auth&action=login">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white ms-2" href="index.php?controller=auth&action=register">
                                <i class="fas fa-user-plus me-2"></i>
                                Registar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb (se definido) -->
    <?php if (isset($breadcrumb) && !empty($breadcrumb)): ?>
    <div class="container-fluid py-2 bg-light border-bottom">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                <?php foreach ($breadcrumb as $item): ?>
                    <?php if (isset($item['url'])): ?>
                        <li class="breadcrumb-item"><a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['text']) ?></a></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($item['text']) ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
    </div>
    <?php endif; ?>

    <!-- Conteúdo principal -->
    <main role="main">
